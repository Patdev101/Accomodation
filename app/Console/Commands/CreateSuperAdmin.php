<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Signature('user:superadmin {email : Email address of the super admin} {--name=Super Admin : Name shown in the system}')]
#[Description('Create the super admin account, or turn an existing account into the super admin')]
class CreateSuperAdmin extends Command
{
    /**
     * Works without email: a new account gets a temporary password that is shown once.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['role' => 'superadmin', 'active' => true]);
            $this->info("{$user->name} ({$email}) is now the super admin. Their password is unchanged.");

            return self::SUCCESS;
        }

        $password = Str::password(14, symbols: false);

        User::create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'active' => true,
        ]);

        $this->info("Super admin account created for {$email}.");
        $this->line("Temporary password: {$password}");
        $this->warn('This password is shown only once. Log in and change it with "Forgot password?" or "Send password link".');

        return self::SUCCESS;
    }
}
