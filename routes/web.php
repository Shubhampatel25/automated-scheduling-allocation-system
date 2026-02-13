<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', fn () => redirect('/login'));

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    // ✅ name the POST route
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Logout Route (requires authentication)
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('admin.dashboard');
});

// HOD Routes
Route::middleware(['auth', 'role:hod'])->prefix('hod')->group(function () {
    Route::get('/dashboard', fn () => view('hod.dashboard'))->name('hod.dashboard');
});

// Professor Routes
Route::middleware(['auth', 'role:professor'])->prefix('professor')->group(function () {
    Route::get('/dashboard', fn () => view('professor.dashboard'))->name('professor.dashboard');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', fn () => view('student.dashboard'))->name('student.dashboard');
});

// General Dashboard (for all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'hod' => redirect()->route('hod.dashboard'),
            'professor' => redirect()->route('professor.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            default => view('dashboard'),
        };
    })->name('dashboard');
});
