<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_guest_has_a_menu_under_their_name(): void
    {
        $guest = User::factory()->create(['role' => 'guest', 'name' => 'Juan Dela Cruz']);

        $this->get('/')->assertOk()->assertSee('faqModal')->assertDontSee('Send feedback');

        $this->actingAs($guest)->get('/')
            ->assertOk()
            ->assertSee('<summary>Juan</summary>', false)
            ->assertSee('Send feedback')
            ->assertSee('Ask a question')
            ->assertSee('Frequently asked questions')
            ->assertSee('Dark mode')
            ->assertSee('Logout');
    }

    public function test_guest_sends_feedback_and_a_question_and_the_admin_reads_them(): void
    {
        $guest = User::factory()->create(['role' => 'guest']);

        $this->post('/messages', ['type' => 'feedback', 'body' => 'Not logged in'])->assertRedirect('/login');

        $this->actingAs($guest)->post('/messages', ['type' => 'feedback', 'body' => ''])->assertSessionHasErrors('body');
        $this->post('/messages', ['type' => 'complaint', 'body' => 'Wrong type here'])->assertSessionHasErrors('type');

        $this->post('/messages', ['type' => 'feedback', 'body' => 'The booking form was easy to use.'])->assertSessionHas('success');
        $this->post('/messages', ['type' => 'question', 'body' => 'Can I change my dates?'])->assertSessionHas('success');

        $this->assertSame(2, Message::where('user_id', $guest->id)->whereNull('read_at')->count());

        // reception has no messages page; the admin does, and opening it marks them read
        $this->actingAs(User::factory()->create(['role' => 'reception']))->get('/admin/messages')->assertRedirect('/dashboard');

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/messages')
            ->assertOk()
            ->assertSee('The booking form was easy to use.')
            ->assertSee('Can I change my dates?')
            ->assertSee($guest->email);

        $this->assertSame(0, Message::whereNull('read_at')->count());
    }
}
