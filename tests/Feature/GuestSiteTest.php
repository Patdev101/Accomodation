<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Location;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_the_rooms_but_booking_needs_an_account(): void
    {
        $room = $this->createRoom();

        $this->get('/')
            ->assertOk()
            ->assertSee('T-101')
            ->assertSee('₱900.00')
            ->assertSee('Wi-Fi')
            ->assertSee('/signup?room='.$room->id, false)
            ->assertSee('All rights reserved');

        $this->get('/book')->assertRedirect('/login');
    }

    public function test_signup_needs_the_terms_and_creates_a_guest_account(): void
    {
        $form = [
            'name' => 'Taylor Guest',
            'email' => 'taylor@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ];

        $this->post('/signup', $form)->assertSessionHasErrors('terms');
        $this->assertGuest();

        $this->post('/signup', $form + ['terms' => '1', 'room' => 3])->assertRedirect('/book?room=3');

        $user = User::where('email', 'taylor@example.com')->firstOrFail();
        $this->assertSame('guest', $user->role);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertAuthenticatedAs($user);

        // a guest account cannot open the staff sides
        $this->get('/dashboard')->assertRedirect('/');
        $this->get('/admin')->assertRedirect('/');
    }

    public function test_guest_can_reserve_a_room_and_cancel_it(): void
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $room = $this->createRoom();
        $form = [
            'room_id' => $room->id,
            'contact_no' => '0917 123 4567',
            'no_of_guests' => 2,
            'check_in_datetime' => now()->addDays(3)->format('Y-m-d').'T14:00',
            'check_out_datetime' => now()->addDays(5)->format('Y-m-d').'T12:00',
            'id_type' => 'Passport',
            'id_photo' => UploadedFile::fake()->image('passport.jpg'),
        ];
        Storage::fake();

        // the home page greets a logged-in guest by first name
        $guest->update(['name' => 'Juan Dela Cruz']);
        $this->actingAs($guest)->get('/')->assertOk()->assertSee('Welcome, Juan!')->assertDontSee('A comfortable place');

        // logging in takes a guest to the home page
        $this->post('/login', ['email' => $guest->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($guest);

        $this->actingAs($guest)->get('/book?room='.$room->id)->assertOk()->assertSee('T-101');

        $this->post('/book', ['no_of_guests' => 9] + $form)->assertSessionHas('error');
        $this->assertSame(0, Booking::count());

        $this->post('/book', $form)->assertRedirect('/my-reservations');

        $booking = Booking::firstOrFail();
        $this->assertSame($guest->id, $booking->user_id);
        $this->assertSame($guest->name, $booking->guest_name);
        // it waits for approval, and the uploaded ID is kept in private storage
        $this->assertSame('Pending', $booking->status);
        Storage::assertExists($booking->id_photo);

        // a reservation without an ID is refused
        $this->post('/book', ['id_photo' => null, 'room_id' => $room->id] + $form)->assertSessionHasErrors('id_photo');

        // the same dates cannot be reserved twice
        $this->post('/book', $form)->assertSessionHas('error');
        $this->assertSame(1, Booking::count());

        // another guest cannot cancel it, the owner can
        $this->actingAs(User::factory()->create(['role' => 'guest']))
            ->post('/my-reservations/'.$booking->id.'/cancel')
            ->assertForbidden();

        $this->actingAs($guest)->get('/my-reservations')->assertOk()->assertSee('T-101')->assertSee('Waiting for approval');
        $this->post('/my-reservations/'.$booking->id.'/cancel')->assertRedirect();
        $this->assertSame('Cancelled', $booking->fresh()->status);
    }

    public function test_admin_sets_the_contact_details_shown_in_the_public_footer(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Contact us');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/contact')->assertOk();

        $this->put('/admin/contact', ['facebook' => 'javascript:alert(1)'])->assertSessionHasErrors('facebook');

        $this->put('/admin/contact', [
            'facebook' => 'https://www.facebook.com/example',
            'messenger' => '',
            'contact_no' => '0917 123 4567',
        ])->assertRedirect('/admin/contact');

        $this->assertEqualsCanonicalizing(['facebook', 'contact_no'], array_keys(Setting::contact()));

        $this->post('/logout');

        $this->get('/')
            ->assertOk()
            ->assertSee('Contact us')
            ->assertSee('https://www.facebook.com/example', false)
            ->assertSee('tel:09171234567', false)
            ->assertDontSee('Messenger');

        // reception cannot change them
        $this->actingAs(User::factory()->create(['role' => 'reception']))
            ->put('/admin/contact', ['contact_no' => '123'])
            ->assertRedirect('/dashboard');
    }

    public function test_admin_uploads_and_removes_the_home_page_banner_photo(): void
    {
        Storage::fake('public');

        $this->get('/')->assertOk()->assertDontSee('with-photo');

        // only the admin can change it
        $this->actingAs(User::factory()->create(['role' => 'reception']))
            ->post('/admin/banner', ['banner_photo' => UploadedFile::fake()->image('front.jpg')])
            ->assertRedirect('/dashboard');

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/banner', ['banner_photo' => UploadedFile::fake()->create('notes.pdf', 10)])
            ->assertSessionHasErrors('banner_photo');

        $this->post('/admin/banner', ['banner_photo' => UploadedFile::fake()->image('front.jpg')])
            ->assertRedirect('/admin/contact');

        $first = Setting::where('name', 'banner_photo')->value('value');
        Storage::disk('public')->assertExists($first);
        $this->get('/admin/contact')->assertOk()->assertSee('Remove photo');

        // a new photo replaces the old file
        $this->post('/admin/banner', ['banner_photo' => UploadedFile::fake()->image('pool.png')]);
        Storage::disk('public')->assertMissing($first);
        $second = Setting::where('name', 'banner_photo')->value('value');

        $this->post('/logout');
        $this->get('/')->assertOk()->assertSee('with-photo')->assertSee($second);

        // a video can be used instead of a photo
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/banner', ['banner_photo' => UploadedFile::fake()->create('tour.mp4', 500, 'video/mp4')])
            ->assertRedirect('/admin/contact');
        Storage::disk('public')->assertMissing($second);
        $second = Setting::where('name', 'banner_photo')->value('value');
        $this->assertTrue(Setting::bannerIsVideo());
        $this->get('/admin/contact')->assertOk()->assertSee('Remove video');

        $this->post('/logout');
        $this->get('/')->assertOk()->assertSee('hero-video')->assertSee('Pause video')->assertSee($second);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete('/admin/banner')->assertRedirect('/admin/contact');
        Storage::disk('public')->assertMissing($second);
        $this->assertNull(Setting::bannerPhotoUrl());
    }

    private function createRoom(): Room
    {
        return Room::create([
            'room_no' => 'T-101',
            'location_id' => Location::create(['name' => 'Guest Villa'])->id,
            'capacity' => 4,
            'rate' => 900,
            'status' => 'Available',
            'inclusions' => '["TV","Wi-Fi"]',
        ]);
    }
}
