<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_room_with_hourly_and_day_tour_rates_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = Location::create(['name' => 'Guest Villa']);

        $this->actingAs($admin)
            ->post('/rooms', [
                'room_no' => 'H-101',
                'location_id' => $location->id,
                'capacity' => 2,
                'rate' => '',
                'rate_hourly' => '150',
                'rate_daytour' => '750',
                'status' => 'Available',
            ])
            ->assertRedirect('/rooms');

        $room = Room::where('room_no', 'H-101')->firstOrFail();

        $this->assertNull($room->rate);
        $this->assertSame(
            'Per hour: ₱150.00 · Daily: ₱750.00',
            $room->rateSummary()
        );

        $reception = User::factory()->create(['role' => 'reception']);

        $this->actingAs($reception)
            ->get('/checkin')
            ->assertOk()
            ->assertSee('H-101 (good for 2) — Per hour: ₱150.00 · Daily: ₱750.00');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Per hour')
            ->assertSee('₱150.00')
            ->assertSee('Daily')
            ->assertSee('₱750.00');

        $this->get('/bookings')
            ->assertOk()
            ->assertSee('H-101 — Guest Villa')
            ->assertSee('Per hour: ₱150.00 · Daily: ₱750.00');
    }

    public function test_admin_must_set_at_least_one_rate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = Location::create(['name' => 'Barracks']);

        $this->actingAs($admin)
            ->from('/rooms/create')
            ->post('/rooms', [
                'room_no' => 'R-101',
                'location_id' => $location->id,
                'capacity' => 2,
                'rate' => '',
                'rate_hourly' => '',
                'rate_daytour' => '',
                'status' => 'Available',
            ])
            ->assertSessionHasErrors('rate');

        $this->assertDatabaseMissing('rooms', ['room_no' => 'R-101']);
    }
}
