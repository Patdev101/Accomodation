<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adds_and_removes_room_photos_and_guests_see_them(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $form = [
            'room_no' => 'T-101',
            'location_id' => Location::create(['name' => 'Guest Villa'])->id,
            'capacity' => 4,
            'rate' => 900,
            'status' => 'Available',
        ];

        // a room without photos shows a placeholder on the public site
        $this->actingAs($admin)->post('/rooms', $form)->assertRedirect('/rooms');
        $room = Room::firstOrFail();
        $this->assertSame([], $room->photos);

        $this->put('/rooms/'.$room->id, $form + ['photos' => [UploadedFile::fake()->create('notes.pdf', 10)]])
            ->assertSessionHasErrors('photos.0');

        $this->put('/rooms/'.$room->id, $form + ['photos' => [
            UploadedFile::fake()->image('bed.jpg'),
            UploadedFile::fake()->image('bath.png'),
        ]])->assertRedirect('/rooms');

        $photos = $room->fresh()->photos;
        $this->assertCount(2, $photos);
        Storage::disk('public')->assertExists($photos);

        $this->get('/rooms/'.$room->id.'/edit')->assertOk()->assertSee('remove_photos[]', false);

        // ticking Remove deletes the file too
        $this->put('/rooms/'.$room->id, $form + ['remove_photos' => [$photos[0]]])->assertRedirect('/rooms');

        $this->assertSame([$photos[1]], $room->fresh()->photos);
        Storage::disk('public')->assertMissing($photos[0]);

        $this->post('/logout');

        $this->get('/')
            ->assertOk()
            ->assertSee('See the photos of T-101')
            ->assertSee('1 photo')
            ->assertSee('photoModal');
    }

    public function test_a_room_without_photos_shows_a_placeholder(): void
    {
        Room::create([
            'room_no' => 'T-102',
            'location_id' => Location::create(['name' => 'Barracks'])->id,
            'capacity' => 2,
            'rate' => 500,
            'status' => 'Available',
        ]);

        $this->get('/')->assertOk()->assertSee('No photos yet')->assertDontSee('See the photos of T-102');
    }
}
