<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimetableController;

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

    // Password Reset Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout Route (requires authentication)
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
});

// HOD Routes
Route::middleware(['auth', 'role:hod'])->prefix('hod')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'hod'])->name('hod.dashboard');
    Route::post('/timetable/generate', [TimetableController::class, 'generate'])->name('hod.timetable.generate');
    Route::post('/timetable/{timetable}/activate', [TimetableController::class, 'activate'])->name('hod.timetable.activate');
    Route::post('/timetable/{timetable}/delete', [TimetableController::class, 'destroy'])->name('hod.timetable.delete');
});

// Professor Routes
Route::middleware(['auth', 'role:professor'])->prefix('professor')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'professor'])->name('professor.dashboard');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'student'])->name('student.dashboard');
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
