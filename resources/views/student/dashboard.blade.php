@extends('layouts.dashboard')

@section('title', 'Student Dashboard')
@section('role-label', 'Student Panel')
@section('page-title', 'Student Dashboard')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Register for courses, view your schedule, and manage your academic information.</p>
        </div>
        <a href="{{ route('student.register-courses') }}" class="banner-btn">Register Courses</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount }}</h3>
                <p>Enrolled Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128197;</div>
            <div class="stat-details">
                <h3>{{ $classesPerWeek }}</h3>
                <p>Classes / Week</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount }}</h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $totalCredits }}</h3>
                <p>Total Credits</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #a7f3d0;">
        &#10003; {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fee2e2;color:#991b1b;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #fca5a5;">
        &#9888; {{ session('error') }}
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="{{ route('student.register-courses') }}" class="action-btn">
            <div class="action-icon">&#43;</div>
            Register Courses
        </a>
        <a href="{{ route('student.my-courses') }}" class="action-btn">
            <div class="action-icon">&#128218;</div>
            My Courses
        </a>
        <a href="{{ route('student.timetable') }}" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="{{ route('student.today') }}" class="action-btn">
            <div class="action-icon">&#128336;</div>
            Today's Classes
        </a>
        <a href="{{ route('student.fee-payment') }}" class="action-btn">
            <div class="action-icon">&#128178;</div>
            Fee Payment
        </a>
        <a href="{{ route('student.profile') }}" class="action-btn">
            <div class="action-icon">&#128100;</div>
            My Profile
        </a>
    </div>

    <!-- Fee status alert on dashboard -->
    @if($feeRecord && $feeRecord->status !== 'paid')
    <div style="background:#fef3c7;color:#92400e;padding:14px 18px;border-radius:8px;margin-top:16px;border:1px solid #fde68a;display:flex;align-items:center;gap:10px;">
        <span style="font-size:1.2rem">&#9888;</span>
        <div>
            <strong>Fee Payment Pending</strong> &mdash; Your Semester {{ $semester }} fee is
            <strong>{{ ucfirst($feeRecord->status) }}</strong>. Course registration requires full payment.
            <a href="{{ route('student.fee-payment') }}" style="color:#92400e;text-decoration:underline;margin-left:6px;">Pay Now &rarr;</a>
        </div>
    </div>
    @endif
@endsection
