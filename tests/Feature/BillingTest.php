<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_can_check_in_using_hourly_rate_and_view_estimate(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $room = $this->createRoom(['rate' => null, 'rate_hourly' => 125]);

        $this->actingAs($reception)
            ->get('/checkin')
            ->assertOk()
            ->assertSee('Estimated accommodation cost')
            ->assertSee('data-rate-hourly="125.00"', false)
            ->assertSee('data-inclusions=', false)
            ->assertSee('selectedRoomDetails');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('TV')
            ->assertSee('Wi-Fi')
            ->assertSee('roomClicked(')
            ->assertSee('roomModalInclusions');

        $this->get('/bookings')
            ->assertOk()
            ->assertSee('reservationRoomDetails')
            ->assertSee('data-inclusions=', false)
            ->assertSee('reservationDetailsModal')
            ->assertSee('function showReservationDetails(button)')
            ->assertDontSee('alert(this.dataset.info)', false);

        $this->post('/checkin', [
            'guest_name' => 'Taylor Guest',
            'guest_type' => 'Visitor',
            'contact_no' => '555-0100',
            'address' => 'Sample address',
            'no_of_guests' => 1,
            'check_out_datetime' => now()->addHours(4)->format('Y-m-d\TH:i'),
            'verified' => '1',
            'id_type' => 'Company ID',
            'id_number' => 'ID-100',
            'id_surrendered' => '1',
            'room_id' => $room->id,
            'billing_rate_type' => 'hourly',
        ])->assertRedirect();

        $booking = Booking::where('guest_name', 'Taylor Guest')->firstOrFail();
        $this->assertSame('hourly', $booking->billing_rate_type);
        $this->assertEquals(125, $booking->billing_rate);
        $this->assertNotNull($booking->checked_in_at);

        // a checked-in guest leaves the Reservations page and appears on Check-out
        $this->get('/bookings')
            ->assertOk()
            ->assertSee('reservationDetailsModal')
            ->assertDontSee('Taylor Guest');

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Taylor Guest');

        $this->get('/checkin/'.$booking->id.'/slip')
            ->assertOk()
            ->assertSee('Room inclusions')
            ->assertSee('TV')
            ->assertSee('Wi-Fi')
            ->assertSee('Per hour: ₱125.00');
    }

    public function test_checkout_bill_itemizes_charges_records_partial_payments_and_blocks_id_until_paid(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $room = $this->createRoom(['rate' => 1000]);
        $checkIn = now()->subDays(2)->setTime(14, 0);
        $booking = Booking::create([
            'guest_name' => 'Jordan Guest',
            'guest_type' => 'Visitor',
            'contact_no' => '555-0101',
            'address' => 'Sample address',
            'room_id' => $room->id,
            'no_of_guests' => 2,
            'check_in' => $checkIn->toDateString(),
            'check_in_time' => '14:00',
            'check_out' => now()->toDateString(),
            'check_out_time' => '12:00',
            'status' => 'Checked In',
            'verified' => true,
            'id_surrendered' => true,
            'id_type' => 'Company ID',
            'id_number' => 'ID-101',
            'billing_rate_type' => 'nightly',
            'billing_rate' => 1000,
            'checked_in_at' => $checkIn,
        ]);

        $this->actingAs($reception)
            ->post('/checkout/'.$booking->id.'/verify')
            ->assertRedirect();

        $this->post('/checkout/'.$booking->id.'/inspect', [
            'inspection_notes' => 'Room inspected',
            'damage_notes' => 'One item damaged',
            'additional_charges' => [
                ['category' => 'Extra service', 'description' => 'Laundry', 'amount' => '300'],
                ['category' => 'Damage', 'description' => 'Broken lamp', 'amount' => '100'],
            ],
            'room_after' => 'Maintenance',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame(2, $booking->billingUnits());
        $this->assertEquals(2000, $booking->accommodationCharge());
        $this->assertEquals(400, $booking->additionalChargeTotal());
        $this->assertEquals(2400, $booking->totalAmount());
        $this->assertSame('Inspection', $room->fresh()->status);

        $this->post('/checkout/'.$booking->id.'/settle', ['amount' => '1000'])
            ->assertRedirect();
        $booking->refresh();
        $this->assertSame('Partially Paid', $booking->paymentStatus());
        $this->assertEquals(1400, $booking->balance());
        $this->assertSame(4, $booking->checkout_step);

        $this->post('/checkout/'.$booking->id.'/return-id')
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertFalse($booking->fresh()->id_returned);

        $this->post('/checkout/'.$booking->id.'/settle', ['amount' => '1401'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertEquals(1000, $booking->fresh()->amount_paid);

        $this->post('/checkout/'.$booking->id.'/settle', ['amount' => '1400'])
            ->assertRedirect();
        $booking->refresh();
        $this->assertSame('Paid', $booking->paymentStatus());
        $this->assertTrue($booking->charges_paid);
        $this->assertSame(5, $booking->checkout_step);
        $this->assertSame('Inspection', $room->fresh()->status);

        $this->post('/checkout/'.$booking->id.'/return-id')->assertRedirect();
        $this->post('/checkout/'.$booking->id.'/record', ['checkout_notes' => 'Completed'])
            ->assertRedirect();

        $this->get('/billing/'.$booking->id)
            ->assertOk()
            ->assertSee('Guest bill')
            ->assertSee('Jordan Guest')
            ->assertSee('Laundry')
            ->assertSee('Broken lamp')
            ->assertSee('2,400.00')
            ->assertSee('Paid');

        $this->get('/reports')
            ->assertOk()
            ->assertSee('2,000.00')
            ->assertSee('400.00')
            ->assertSee('2,400.00')
            ->assertSee('View bill');

        $this->assertSame('Maintenance', $room->fresh()->status);
    }

    public function test_hourly_rate_rounds_up_and_day_tour_counts_calendar_days(): void
    {
        $booking = new Booking([
            'billing_rate_type' => 'hourly',
            'checked_in_at' => Carbon::parse('2026-10-05 10:00:00'),
            'checkout_verified_at' => Carbon::parse('2026-10-05 13:01:00'),
        ]);

        $this->assertSame(4, $booking->billingUnits());

        $booking->billing_rate_type = 'daytour';
        $booking->checked_in_at = Carbon::parse('2026-10-05 23:30:00');
        $booking->checkout_verified_at = Carbon::parse('2026-10-07 00:15:00');

        $this->assertSame(3, $booking->billingUnits());
    }

    private function createRoom(array $rates): Room
    {
        $location = Location::create(['name' => 'Guest Villa']);

        return Room::create(array_merge([
            'room_no' => 'T-101',
            'location_id' => $location->id,
            'capacity' => 4,
            'rate' => 900,
            'rate_hourly' => null,
            'rate_daytour' => null,
            'status' => 'Available',
            'inclusions' => '["TV","Wi-Fi"]',
        ], $rates));
    }
}
