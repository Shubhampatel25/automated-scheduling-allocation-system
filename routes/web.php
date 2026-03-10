<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HodController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\StudentCourseRegistrationController;
use App\Http\Controllers\FeePaymentController;

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

    // Manage Teachers
    Route::get('/teachers',              [TeacherController::class, 'index'])->name('admin.teachers.index');
    Route::post('/teachers',             [TeacherController::class, 'store'])->name('admin.teachers.store');
    Route::put('/teachers/{teacher}',    [TeacherController::class, 'update'])->name('admin.teachers.update');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');

    // Manage Students
    Route::get('/students',              [StudentController::class, 'index'])->name('admin.students.index');
    Route::post('/students',             [StudentController::class, 'store'])->name('admin.students.store');
    Route::put('/students/{student}',    [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');

    // Manage Courses
    Route::get('/courses',             [CourseController::class, 'index'])->name('admin.courses.index');
    Route::post('/courses',            [CourseController::class, 'store'])->name('admin.courses.store');
    Route::put('/courses/{course}',    [CourseController::class, 'update'])->name('admin.courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('admin.courses.destroy');

    // Manage HODs
    Route::get('/hods',          [HodController::class, 'index'])->name('admin.hods.index');
    Route::post('/hods',         [HodController::class, 'store'])->name('admin.hods.store');
    Route::put('/hods/{hod}',    [HodController::class, 'update'])->name('admin.hods.update');
    Route::delete('/hods/{hod}', [HodController::class, 'destroy'])->name('admin.hods.destroy');

    // Manage Rooms
    Route::get('/rooms',           [RoomController::class, 'index'])->name('admin.rooms.index');
    Route::post('/rooms',          [RoomController::class, 'store'])->name('admin.rooms.store');
    Route::put('/rooms/{room}',    [RoomController::class, 'update'])->name('admin.rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('admin.rooms.destroy');

    // Manage Departments
    Route::get('/departments',                  [DepartmentController::class, 'index'])->name('admin.departments.index');
    Route::post('/departments',                 [DepartmentController::class, 'store'])->name('admin.departments.store');
    Route::put('/departments/{department}',     [DepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/departments/{department}',  [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');

    // Fee Payments
    Route::get('/fee-payments',                          [FeePaymentController::class, 'index'])->name('admin.fee-payments.index');
    Route::post('/fee-payments',                         [FeePaymentController::class, 'store'])->name('admin.fee-payments.store');
    Route::put('/fee-payments/{feePayment}',             [FeePaymentController::class, 'update'])->name('admin.fee-payments.update');
    Route::delete('/fee-payments/{feePayment}',          [FeePaymentController::class, 'destroy'])->name('admin.fee-payments.destroy');
    Route::post('/fee-payments/generate-pending',        [FeePaymentController::class, 'generatePending'])->name('admin.fee-payments.generate');
    Route::get('/fee-payments/student-fee/{student}',   [FeePaymentController::class, 'getStudentFee'])->name('admin.fee-payments.student-fee');

    // Course Registrations
    Route::post('/registrations/{registration}/complete', [StudentCourseRegistrationController::class, 'complete'])->name('admin.registrations.complete');

    // Scheduling & System pages
    Route::get('/schedule',  [DashboardController::class, 'schedule'])->name('admin.schedule');
    Route::get('/conflicts', [DashboardController::class, 'conflicts'])->name('admin.conflicts');
    Route::get('/activity',  [DashboardController::class, 'activity'])->name('admin.activity');
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

    // Students page
    Route::get('/students', [ProfessorController::class, 'students'])->name('professor.students');

    // Availability page + CRUD
    Route::get('/availability',                          [ProfessorController::class, 'availability'])->name('professor.availability');
    Route::post('/availability',                         [TeacherAvailabilityController::class, 'store'])->name('professor.availability.store');
    Route::delete('/availability/{teacherAvailability}', [TeacherAvailabilityController::class, 'destroy'])->name('professor.availability.destroy');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'student'])->name('student.dashboard');
    Route::post('/courses/register', [StudentCourseRegistrationController::class, 'register'])->name('student.courses.register');
    Route::post('/courses/{registration}/drop', [StudentCourseRegistrationController::class, 'drop'])->name('student.courses.drop');
    Route::post('/fees/{feePayment}/pay', [FeePaymentController::class, 'studentPay'])->name('student.fees.pay');
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
