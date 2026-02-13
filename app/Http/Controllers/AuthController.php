<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
