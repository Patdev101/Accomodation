<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_reception_account_with_a_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'role' => 'reception',
            'password' => 'first-pass-1',
            'password_confirmation' => 'first-pass-1',
        ])->assertRedirect('/admin/users');

        $ana = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('first-pass-1', $ana->password));
        $this->assertSame('reception', $ana->role);
    }

    public function test_a_password_is_required_when_creating_an_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/users', ['name' => 'No Pass', 'email' => 'nopass@example.com', 'role' => 'reception'])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Short', 'email' => 'typo@example.com', 'role' => 'reception', 'password' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'nopass@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'typo@example.com']);
    }

    public function test_admin_can_reset_a_reception_password_and_leaving_it_empty_keeps_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reception = User::factory()->create(['role' => 'reception', 'password' => Hash::make('old-pass-1')]);

        $this->actingAs($admin)->put("/admin/users/{$reception->id}", ['name' => 'Renamed', 'email' => $reception->email, 'role' => 'reception']);
        $this->assertTrue(Hash::check('old-pass-1', $reception->fresh()->password));

        $this->actingAs($admin)->put("/admin/users/{$reception->id}", [
            'name' => 'Renamed', 'email' => $reception->email, 'role' => 'reception',
            'password' => 'reset-pass-1', 'password_confirmation' => 'reset-pass-1',
        ]);
        $this->assertTrue(Hash::check('reset-pass-1', $reception->fresh()->password));
    }

    public function test_reception_changes_their_own_password(): void
    {
        $reception = User::factory()->create(['role' => 'reception', 'password' => Hash::make('given-pass-1')]);

        $this->actingAs($reception)->get('/account')->assertOk()->assertSee('Change password')->assertSee($reception->email);

        // the current password must be right
        $this->actingAs($reception)->put('/account/password', [
            'current_password' => 'wrong-pass', 'password' => 'my-own-pass-1', 'password_confirmation' => 'my-own-pass-1',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('given-pass-1', $reception->fresh()->password));

        $this->actingAs($reception)->put('/account/password', [
            'current_password' => 'given-pass-1', 'password' => 'my-own-pass-1', 'password_confirmation' => 'my-own-pass-1',
        ])->assertRedirect('/account');
        $this->assertTrue(Hash::check('my-own-pass-1', $reception->fresh()->password));
    }

    public function test_reception_cannot_open_the_accounts_page(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);

        $this->actingAs($reception)->get('/admin/users')->assertRedirect('/dashboard');
    }
}
