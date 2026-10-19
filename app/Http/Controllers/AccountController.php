<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// "Change password" for whoever is logged in (reception, admin or super admin)
class AccountController extends Controller
{
    // My account: your details and the form to change your password
    public function show()
    {
        return view('account.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed|different:current_password',
        ], [
            'current_password.current_password' => 'The current password is not correct.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return redirect('/account')->with('success', 'Your password has been changed. Use the new one next time you log in.');
    }
}
