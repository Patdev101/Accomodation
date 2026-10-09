<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_approves_a_pending_reservation_and_the_guest_is_told(): void
    {
        Notification::fake();
        $guest = User::factory()->create(['role' => 'guest']);
        $reception = User::factory()->create(['role' => 'reception']);
        $booking = $this->createPendingBooking($guest);

        // not yet a reservation for the front desk, and not in the Guest Log
        $this->actingAs($reception)->get('/bookings')->assertOk()->assertDontSee($guest->name);
        $this->get('/reports')->assertOk()->assertDontSee($guest->name);
        $this->get('/calendar?month='.$booking->check_in->format('Y-m'))->assertOk()->assertSee('Awaiting approval');

        $this->get('/approvals')
            ->assertOk()
            ->assertSee($guest->name)
            ->assertSee('/approvals/'.$booking->id.'/id', false);

        $this->get('/approvals/'.$booking->id.'/id')->assertOk();

        $this->post('/approvals/'.$booking->id.'/approve')->assertRedirect()->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('Reserved', $booking->status);
        $this->assertSame($reception->id, $booking->reviewed_by);
        Notification::assertSentTo($guest, ReservationReviewed::class);

        // now it is on the Reservations page, and cannot be reviewed twice
        $this->get('/bookings')->assertOk()->assertSee($guest->name);
        $this->post('/approvals/'.$booking->id.'/approve')->assertSessionHas('error');

        // the guest sees the confirmation once
        $this->actingAs($guest)->get('/')->assertOk()->assertSee('Your reservation is confirmed.');
        $this->get('/')->assertOk()->assertDontSee('Your reservation is confirmed.');
        $this->get('/my-reservations')->assertOk()->assertSee('Confirmed');
    }

    public function test_admin_declines_with_a_reason_and_the_room_is_free_again(): void
    {
        Notification::fake();
        $guest = User::factory()->create(['role' => 'guest']);
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->createPendingBooking($guest);

        // while it waits, the room is held
        $this->assertTrue(Booking::roomTaken($booking->room_id, $booking->check_in->format('Y-m-d'), $booking->check_out->format('Y-m-d')));

        $this->actingAs($admin)->get('/approvals')->assertOk()->assertSee($guest->name);

        $this->post('/approvals/'.$booking->id.'/decline')->assertSessionHasErrors('decline_reason');
        $this->post('/approvals/'.$booking->id.'/decline', ['decline_reason' => 'ID photo is blurred'])->assertRedirect();

        $booking->refresh();
        $this->assertSame('Declined', $booking->status);
        $this->assertFalse(Booking::roomTaken($booking->room_id, $booking->check_in->format('Y-m-d'), $booking->check_out->format('Y-m-d')));
        Notification::assertSentTo($guest, ReservationReviewed::class);

        // (this page load shows the "declined" message, which names the guest)
        $this->get('/approvals')->assertOk();

        // a declined request is not in reception's Guest Log or calendar
        $reception = User::factory()->create(['role' => 'reception']);
        $this->actingAs($reception)->get('/reports')->assertOk()->assertDontSee($guest->name);
        $this->get('/calendar?month='.$booking->check_in->format('Y-m'))->assertOk()->assertDontSee($guest->name);

        $this->actingAs($guest)->get('/my-reservations')
            ->assertOk()
            ->assertSee('was not approved')
            ->assertSee('ID photo is blurred');
    }

    public function test_guests_cannot_approve_or_open_another_guests_id(): void
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $booking = $this->createPendingBooking($guest);

        $this->actingAs(User::factory()->create(['role' => 'guest']));

        $this->get('/approvals')->assertRedirect('/');
        $this->post('/approvals/'.$booking->id.'/approve')->assertRedirect('/');
        $this->get('/approvals/'.$booking->id.'/id')->assertForbidden();
        $this->assertSame('Pending', $booking->fresh()->status);

        // the guest who uploaded it can open their own
        $this->actingAs($guest)->get('/approvals/'.$booking->id.'/id')->assertOk();
    }

    private function createPendingBooking(User $guest): Booking
    {
        Storage::fake();

        $room = Room::create([
            'room_no' => 'T-101',
            'location_id' => Location::create(['name' => 'Guest Villa'])->id,
            'capacity' => 4,
            'rate' => 900,
            'status' => 'Available',
        ]);

        return Booking::create([
            'user_id' => $guest->id,
            'guest_name' => $guest->name,
            'email' => $guest->email,
            'contact_no' => '0917 123 4567',
            'room_id' => $room->id,
            'no_of_guests' => 2,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_in_time' => '14:00',
            'check_out' => now()->addDays(5)->toDateString(),
            'check_out_time' => '12:00',
            'status' => 'Pending',
            'id_type' => 'Passport',
            'id_photo' => UploadedFile::fake()->image('passport.jpg')->store('ids'),
        ]);
    }
}
