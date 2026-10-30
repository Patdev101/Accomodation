<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-30 08:00:00');
    }

    public function test_night_arrival_encoded_in_the_morning_is_billed_from_the_real_arrival(): void
    {
        $reception = User::factory()->create(['role' => 'reception', 'name' => 'Ana']);
        $room = $this->createRoom();

        $this->actingAs($reception)->post('/checkin', $this->checkinData($room, [
            'actual_check_in' => '2026-10-29T22:00',
            'checkin_time_reason' => 'Arrived at night, form no. 123',
            'check_out_datetime' => '2026-10-31T12:00',
        ]))->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame('2026-10-29 22:00', $booking->checked_in_at->format('Y-m-d H:i'));
        $this->assertSame('Arrived at night, form no. 123', $booking->checkin_time_reason);
        $this->assertSame('Ana', $booking->checkin_recorded_by);
        $this->assertSame('2026-10-30 08:00', $booking->checkin_recorded_at->format('Y-m-d H:i'));

        // Oct 29 night to Oct 31 = 2 nights x 500
        $this->assertSame(2, $booking->billingUnits());
        $this->assertEquals(1000, $booking->accommodationCharge());

        $this->get('/billing/'.$booking->id)->assertOk()->assertSee('Arrived at night, form no. 123');
    }

    public function test_an_earlier_time_needs_a_reason_and_cannot_go_back_more_than_24_hours(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $room = $this->createRoom();

        $this->actingAs($reception)->post('/checkin', $this->checkinData($room, ['actual_check_in' => '2026-10-29T22:00']))
            ->assertSessionHasErrors('checkin_time_reason');

        $this->actingAs($reception)->post('/checkin', $this->checkinData($room, [
            'actual_check_in' => '2026-10-29T07:00', 'checkin_time_reason' => 'Too old',
        ]))->assertSessionHasErrors('actual_check_in');

        $this->actingAs($reception)->post('/checkin', $this->checkinData($room, ['actual_check_in' => '2026-10-30T10:00']))
            ->assertSessionHasErrors('actual_check_in');

        $this->assertSame(0, Booking::count());
    }

    public function test_the_check_out_time_can_be_set_earlier_with_a_reason(): void
    {
        $reception = User::factory()->create(['role' => 'reception', 'name' => 'Ana']);
        $room = $this->createRoom();
        $this->actingAs($reception)->post('/checkin', $this->checkinData($room, [
            'actual_check_in' => '2026-10-30T07:50',
            'check_out_datetime' => '2026-10-31T12:00',
        ]));
        $booking = Booking::firstOrFail();
        $this->assertNull($booking->checkin_time_reason);

        Carbon::setTestNow('2026-10-31 13:00:00');

        $this->post('/checkout/'.$booking->id.'/verify', ['actual_check_out' => '2026-10-31T06:00'])
            ->assertSessionHasErrors('checkout_time_reason');

        // never before the check-in
        $this->post('/checkout/'.$booking->id.'/verify', ['actual_check_out' => '2026-10-30T07:00', 'checkout_time_reason' => 'x'])
            ->assertSessionHasErrors('actual_check_out');

        $this->post('/checkout/'.$booking->id.'/verify', [
            'actual_check_out' => '2026-10-31T06:00', 'checkout_time_reason' => 'Left early morning',
        ])->assertRedirect();

        $booking->refresh();
        $this->assertSame('2026-10-31 06:00', $booking->checkout_verified_at->format('Y-m-d H:i'));
        $this->assertSame('Left early morning', $booking->checkout_time_reason);
        $this->assertSame('Ana', $booking->checkout_recorded_by);
    }

    private function checkinData(Room $room, array $overrides = []): array
    {
        return array_merge([
            'guest_name' => 'Night Guest',
            'guest_type' => 'Visitor',
            'contact_no' => '555-0100',
            'address' => 'Sample address',
            'no_of_guests' => 1,
            'check_out_datetime' => '2026-10-31T12:00',
            'verified' => '1',
            'id_type' => 'Company ID',
            'id_number' => 'ID-100',
            'id_surrendered' => '1',
            'room_id' => $room->id,
            'billing_rate_type' => 'nightly',
        ], $overrides);
    }

    private function createRoom(): Room
    {
        $location = Location::create(['name' => 'Guest Villa']);

        return Room::create([
            'room_no' => 'T-101',
            'location_id' => $location->id,
            'capacity' => 4,
            'rate' => 500,
            'status' => 'Available',
            'inclusions' => '["TV","Wi-Fi"]',
        ]);
    }
}
