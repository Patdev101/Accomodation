<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
            // the admin sets the first password; the user can change it after logging in
            'password' => 'required|min:8',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return redirect('/admin/users')->with('success', 'Account created for '.$user->name.'. Give them their email and password; they can change the password after logging in.');
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
            // empty = keep the current password; filled = the admin resets it (for a forgotten password)
            'password' => 'nullable|min:8',
        ]);

        // nobody can change their own role, so there is always someone who can manage accounts
        if ($user->id == $request->user()->id && $data['role'] != $user->role) {
            return back()->with('error', 'You cannot change your own role.')->withInput();
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return redirect('/admin/users')->with('success', isset($data['password'])
            ? 'Account updated and the new password saved. Give it to '.$user->name.'.'
            : 'Account updated.');
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
}
