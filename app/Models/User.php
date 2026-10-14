<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // the kinds of account, highest first, with the name shown on screen
    const ROLES = [
        'superadmin' => 'Super admin',
        'admin' => 'Admin',
        'reception' => 'Reception',
    ];

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    // locations, rooms and accounts
    public function canManageSetup(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    // dashboard, check-in, check-out, reservations, calendar and guest log
    public function canUseFrontDesk(): bool
    {
        return in_array($this->role, ['reception', 'superadmin']);
    }

    // the super admin manages every account; an admin manages reception accounts and their own
    public function canManage(User $other): bool
    {
        return $this->isSuperAdmin() || $other->role === 'reception' || $other->id === $this->id;
    }

    // a super admin account can never be deactivated, so the system always has one
    public function canDeactivate(User $other): bool
    {
        return $other->id !== $this->id && ! $other->isSuperAdmin() && $this->canManage($other);
    }

    /**
     * The roles this user may give to an account.
     *
     * @return array<string, string>
     */
    public function assignableRoles(): array
    {
        return $this->isSuperAdmin() ? self::ROLES : ['reception' => self::ROLES['reception']];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }
}
