<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => $this->accounts(), 'editing' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            // an admin can only create reception accounts; the super admin can create any
            'role' => ['required', Rule::in(array_keys($request->user()->assignableRoles()))],
        ]);

        // the admin never knows the password: the account starts with a random one
        // and the user sets their own from the link sent to their email
        $data['password'] = Hash::make(Str::random(40));
        $user = User::create($data);

        if (! $this->emailPasswordLink($user)) {
            return redirect('/admin/users')->with('error', 'Account created, but the email to '.$user->email.' could not be sent. Check the mail settings, then use "Send password link".');
        }

        return redirect('/admin/users')->with('success', 'Account created. A link to set the password was sent to '.$user->email.'.');
    }

    public function edit(Request $request, User $user)
    {
        if (! $request->user()->canManage($user)) {
            return redirect('/admin/users')->with('error', 'Only the super admin can change admin accounts.');
        }

        return view('admin.users', ['users' => $this->accounts(), 'editing' => $user]);
    }

    public function update(Request $request, User $user)
    {
        if (! $request->user()->canManage($user)) {
            return redirect('/admin/users')->with('error', 'Only the super admin can change admin accounts.');
        }

        $data = $request->validate([
            'name' => 'required|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys($request->user()->assignableRoles()))],
        ]);

        // nobody can change their own role, so there is always someone who can manage accounts
        if ($user->id == $request->user()->id && $data['role'] != $user->role) {
            return back()->with('error', 'You cannot change your own role.')->withInput();
        }

        $user->update($data);

        return redirect('/admin/users')->with('success', 'Account updated.');
    }

    // email the user a new link to set their password (first time, or when they forgot it)
    public function sendLink(Request $request, User $user)
    {
        if (! $request->user()->canManage($user)) {
            return back()->with('error', 'Only the super admin can send a link to an admin account.');
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (Throwable $error) {
            report($error);

            return back()->with('error', 'The email to '.$user->email.' could not be sent. Check the mail settings and try again.');
        }

        if ($status == Password::RESET_THROTTLED) {
            return back()->with('error', 'A link was just sent to '.$user->email.'. Wait a minute before sending another.');
        }

        return back()->with('success', 'A link to set the password was sent to '.$user->email.'.');
    }

    // turn an account off or on (off = cannot log in)
    public function toggle(Request $request, User $user)
    {
        if ($user->id == $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'A super admin account cannot be deactivated.');
        }

        if (! $request->user()->canDeactivate($user)) {
            return back()->with('error', 'Only the super admin can deactivate an admin account.');
        }

        $user->update(['active' => ! $user->active]);

        return back()->with('success', $user->name.' is now '.($user->active ? 'active' : 'deactivated').'.');
    }

    // every account, super admins first, then admins, then reception
    private function accounts()
    {
        $order = array_flip(array_keys(User::ROLES));

        return User::orderBy('name')->get()->sortBy(fn ($user) => $order[$user->role] ?? 99)->values();
    }

    // false when the mail server refuses the email (wrong mail settings)
    private function emailPasswordLink(User $user): bool
    {
        try {
            Password::sendResetLink(['email' => $user->email]);

            return true;
        } catch (Throwable $error) {
            report($error);

            return false;
        }
    }
}
