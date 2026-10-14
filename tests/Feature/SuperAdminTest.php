<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_both_the_setup_and_the_front_desk(): void
    {
        $superAdmin = User::factory()->create(['role' => 'superadmin']);

        foreach (['/admin', '/rooms', '/admin/users', '/dashboard', '/checkin', '/checkout', '/bookings', '/calendar', '/reports'] as $page) {
            $this->actingAs($superAdmin)->get($page)->assertOk();
        }
    }

    public function test_admin_and_reception_keep_their_own_side_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reception = User::factory()->create(['role' => 'reception']);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($reception)->get('/admin')->assertRedirect('/dashboard');
    }

    public function test_super_admin_manages_admin_accounts_but_an_admin_cannot(): void
    {
        $superAdmin = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Other Admin']);
        $reception = User::factory()->create(['role' => 'reception']);

        // an admin cannot touch another admin or create one
        $this->actingAs($admin)->post("/admin/users/{$otherAdmin->id}/toggle");
        $this->assertTrue($otherAdmin->fresh()->active);

        $this->actingAs($admin)->put("/admin/users/{$otherAdmin->id}", ['name' => 'Changed', 'email' => $otherAdmin->email, 'role' => 'admin']);
        $this->assertSame('Other Admin', $otherAdmin->fresh()->name);

        $this->actingAs($admin)->post('/admin/users', ['name' => 'New', 'email' => 'new-admin@example.com', 'role' => 'admin'])
            ->assertSessionHasErrors('role');

        // an admin still manages reception accounts
        $this->actingAs($admin)->post("/admin/users/{$reception->id}/toggle");
        $this->assertFalse($reception->fresh()->active);

        // the super admin manages admin accounts
        $this->actingAs($superAdmin)->post("/admin/users/{$otherAdmin->id}/toggle");
        $this->assertFalse($otherAdmin->fresh()->active);

        $this->actingAs($superAdmin)->put("/admin/users/{$admin->id}", ['name' => 'Renamed', 'email' => $admin->email, 'role' => 'reception']);
        $this->assertSame('reception', $admin->fresh()->role);
    }

    public function test_a_super_admin_can_never_be_deactivated(): void
    {
        $superAdmin = User::factory()->create(['role' => 'superadmin']);
        $secondSuperAdmin = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post("/admin/users/{$superAdmin->id}/toggle");
        $this->actingAs($secondSuperAdmin)->post("/admin/users/{$superAdmin->id}/toggle");
        $this->actingAs($superAdmin)->post("/admin/users/{$superAdmin->id}/toggle");

        $this->assertTrue($superAdmin->fresh()->active);
    }

    public function test_command_creates_the_super_admin(): void
    {
        $this->artisan('user:superadmin', ['email' => 'boss@example.com', '--name' => 'Sir Paul'])
            ->expectsOutputToContain('Temporary password:')
            ->assertSuccessful();

        $this->assertSame('superadmin', User::where('email', 'boss@example.com')->value('role'));
    }
}
