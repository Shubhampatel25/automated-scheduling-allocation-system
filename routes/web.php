<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout Route (requires authentication)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

// HOD Routes
Route::middleware(['auth', 'role:hod'])->prefix('hod')->group(function () {
    Route::get('/dashboard', function () {
        return view('hod.dashboard');
    })->name('hod.dashboard');
});

// Professor Routes
Route::middleware(['auth', 'role:professor'])->prefix('professor')->group(function () {
    Route::get('/dashboard', function () {
        return view('professor.dashboard');
    })->name('professor.dashboard');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', function () {
        return view('student.dashboard');
    })->name('student.dashboard');
});

// General Dashboard (for all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'hod':
                return redirect()->route('hod.dashboard');
            case 'professor':
                return redirect()->route('professor.dashboard');
            case 'student':
                return redirect()->route('student.dashboard');
            default:
                return view('dashboard');
        }
    })->name('dashboard');
});