<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // a deactivated account cannot log in
        $user = User::where('email', $data['email'])->first();
        if ($user && ! $user->active) {
            return back()->with('error', 'This account is deactivated. Ask the admin.')->withInput();
        }

        if (Auth::attempt($data)) {
            $request->session()->regenerate();

            // a guest always lands on the home page, which welcomes them by name
            if (Auth::user()->role == 'guest') {
                return redirect('/');
            }

            // the admin starts on the admin page, reception on the dashboard
            return redirect(Auth::user()->role == 'admin' ? '/admin' : '/dashboard');
        }

        return back()->with('error', 'Wrong email or password.')->withInput();
    }

    public function logout(Request $request)
    {
        // a guest goes back to the public site, staff to the login page
        $wasGuest = $request->user() && $request->user()->role == 'guest';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($wasGuest ? '/' : '/login');
    }

    // ================= SET / FORGOT PASSWORD =================

    public function showForgot()
    {
        return view('forgot-password');
    }

    // email a link to set a new password
    public function sendLink(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        Password::sendResetLink($data);

        // same message whether the email exists or not, so nobody can test which emails have accounts
        return back()->with('success', 'If that email has an account, a link to set the password was sent to it.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->update(['password' => Hash::make($password)]);
        });

        if ($status != Password::PASSWORD_RESET) {
            return back()->with('error', 'This link is not valid any more. Ask for a new one.')->withInput();
        }

        return redirect('/login')->with('success', 'Password saved. You can log in now.');
    }
}
