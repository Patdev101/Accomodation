<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => User::where('role', '!=', 'guest')->orderBy('role')->orderBy('name')->get(), 'editing' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'role' => 'required|in:admin,reception',
        ]);

        // the admin never knows the password: the account starts with a random one
        // and the user sets their own from the link sent to their email
        $data['password'] = Hash::make(Str::random(40));
        $user = User::create($data);

        Password::sendResetLink(['email' => $user->email]);

        return redirect('/admin/users')->with('success', 'Account created. A link to set the password was sent to '.$user->email.'.');
    }

    public function edit(User $user)
    {
        return view('admin.users', ['users' => User::where('role', '!=', 'guest')->orderBy('role')->orderBy('name')->get(), 'editing' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:admin,reception',
        ]);

        // the admin cannot remove their own admin role
        if ($user->id == $request->user()->id && $data['role'] != 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.')->withInput();
        }

        $user->update($data);

        return redirect('/admin/users')->with('success', 'Account updated.');
    }

    // email the user a new link to set their password (first time, or when they forgot it)
    public function sendLink(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

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

        $user->update(['active' => ! $user->active]);

        return back()->with('success', $user->name.' is now '.($user->active ? 'active' : 'deactivated').'.');
    }
}
