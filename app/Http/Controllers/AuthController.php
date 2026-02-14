<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        // Validate
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Credentials
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'status' => 'active', // only active users
        ];

        // Attempt login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            $request->session()->regenerate();

            // Redirect by role
            return match (Auth::user()->role) {
                'admin' => redirect()->route('admin.dashboard'),
                'hod' => redirect()->route('hod.dashboard'),
                'professor' => redirect()->route('professor.dashboard'),
                'student' => redirect()->route('student.dashboard'),
                default => redirect()->route('dashboard'),
            };
        }

        // Failed login
        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Show forgot password form
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Send password reset link to email
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // Show reset password form
    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    // Handle password reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ]);

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password has been reset successfully! Please login with your new password.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}
