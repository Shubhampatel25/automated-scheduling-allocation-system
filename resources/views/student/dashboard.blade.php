@extends('layouts.dashboard')

@section('title', 'Student Dashboard')
@section('role-label', 'Student Panel')
@section('page-title', 'Dashboard')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
/* ── Student Profile Banner ── */
.student-profile-banner {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    border-radius: 14px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    color: #fff;
    box-shadow: 0 4px 16px rgba(37,99,235,0.18);
}
.profile-avatar {
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    flex-shrink: 0;
    border: 2.5px solid rgba(255,255,255,0.35);
    letter-spacing: -1px;
}
.profile-info { flex: 1; min-width: 0; }
.profile-info h2 { margin: 0; font-size: 1.2rem; font-weight: 800; line-height: 1.3; }
.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 7px;
    font-size: 0.8rem;
    opacity: 0.88;
    align-items: center;
}
.profile-meta-item { display: flex; align-items: center; gap: 5px; }
.profile-meta-divider { opacity: 0.3; }
.status-badge-active {
    background: rgba(16,185,129,0.22);
    color: #6ee7b7;
    padding: 2px 10px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.73rem;
    letter-spacing: 0.03em;
    border: 1px solid rgba(110,231,183,0.3);
}
.register-btn {
    background: #fff;
    color: #1e3a5f;
    padding: 10px 22px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}
.register-btn:hover { background: #dbeafe; color: #1e40af; }
@media(max-width:640px){
    .student-profile-banner { flex-direction: column; align-items: flex-start; padding: 18px 20px; }
    .register-btn { width: 100%; text-align: center; }
}

/* ── Alert Banners ── */
.dash-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 18px;
    border-radius: 10px;
    margin-bottom: 12px;
    border: 1.5px solid;
    font-size: 0.875rem;
}
.dash-alert.warning { background: #fffbeb; border-color: #fde68a; color: #78350f; }
.dash-alert.danger  { background: #fff1f2; border-color: #fecaca; color: #7f1d1d; }
.dash-alert.success { background: #ede9fe; border-color: #c4b5fd; color: #4c1d95; }
.dash-alert.info    { background: #d1fae5; border-color: #a7f3d0; color: #065f46; }
.dash-alert-icon { font-size: 1.2rem; flex-shrink: 0; }
.dash-alert-body { flex: 1; }
.dash-alert-body strong { display: block; margin-bottom: 1px; }
.dash-alert-body p { margin: 0; font-size: 0.81rem; opacity: 0.85; }
.dash-alert-action {
    background: #1d4ed8;
    color: #fff;
    padding: 7px 16px;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s;
    flex-shrink: 0;
}
.dash-alert-action:hover { background: #1e40af; }
.dash-alert-action.orange { background: #d97706; }
.dash-alert-action.orange:hover { background: #b45309; }

/* ── Stats Grid ── */
.stats-grid-modern {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media(max-width:900px) { .stats-grid-modern { grid-template-columns: 1fr 1fr; } }
@media(max-width:480px) { .stats-grid-modern { grid-template-columns: 1fr; } }

.stat-card-m {
    background: #fff;
    border-radius: 12px;
    padding: 18px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stat-icon-m {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.stat-card-m.blue  .stat-icon-m { background: #eff6ff; }
.stat-card-m.green .stat-icon-m { background: #f0fdf4; }
.stat-card-m.purple .stat-icon-m { background: #faf5ff; }
.stat-card-m.orange .stat-icon-m { background: #fff7ed; }
.stat-num-m { font-size: 1.65rem; font-weight: 800; color: #111827; line-height: 1.1; }
.stat-label-m { font-size: 0.76rem; color: #6b7280; margin-top: 3px; font-weight: 500; }

/* ── Section Label ── */
.sec-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #9ca3af;
    margin-bottom: 11px;
}

/* ── Quick Actions ── */
.qa-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
@media(max-width:1100px) { .qa-grid { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:600px)  { .qa-grid { grid-template-columns: repeat(2, 1fr); } }

.qa-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 10px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    color: #374151;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
    position: relative;
    cursor: pointer;
}
.qa-card:hover {
    border-color: #4f46e5;
    box-shadow: 0 4px 14px rgba(79,70,229,0.1);
    transform: translateY(-2px);
    color: #4f46e5;
}
.qa-card.qa-urgent { border-color: #fca5a5; background: #fff8f8; }
.qa-card.qa-urgent:hover { border-color: #ef4444; box-shadow: 0 4px 14px rgba(239,68,68,0.1); color: #dc2626; }
.qa-icon-big { font-size: 1.55rem; }
.qa-label-sm { font-size: 0.75rem; font-weight: 600; text-align: center; line-height: 1.3; }
.qa-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 9px;
    height: 9px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid #fff;
}

/* ── Academic Summary Card ── */
.academic-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}
.academic-card-header {
    background: #f9fafb;
    padding: 11px 20px;
    font-size: 0.73rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.academic-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
@media(max-width:640px) { .academic-grid { grid-template-columns: 1fr 1fr; } }
.ac-item {
    padding: 16px 20px;
    border-right: 1px solid #f3f4f6;
}
.ac-item:last-child { border-right: none; }
.ac-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; font-weight: 600; margin-bottom: 5px; }
.ac-value { font-size: 0.9rem; font-weight: 700; color: #111827; }
</style>
@endpush

@section('content')

{{-- Flash Messages --}}
@if(session('semester_advanced'))
<div class="dash-alert success" style="margin-bottom:16px;">
    <span class="dash-alert-icon">&#127881;</span>
    <div class="dash-alert-body">
        <strong>Congratulations! You have been advanced to Semester {{ session('semester_advanced') }}.</strong>
        <p>You passed your previous semester requirements.</p>
    </div>
    <a href="{{ route('student.register-courses') }}" class="dash-alert-action">
        Register Semester {{ session('semester_advanced') }} &rarr;
    </a>
</div>
@endif

@if(session('success'))
<div class="dash-alert info" style="margin-bottom:16px;">
    <span class="dash-alert-icon">&#10003;</span>
    <div class="dash-alert-body">{{ session('success') }}</div>
</div>
@endif
@if(session('error'))
<div class="dash-alert danger" style="margin-bottom:16px;">
    <span class="dash-alert-icon">&#9888;</span>
    <div class="dash-alert-body">{{ session('error') }}</div>
</div>
@endif

{{-- ── Student Profile Banner ── --}}
<div class="student-profile-banner">
    <div class="profile-avatar">
        {{ strtoupper(substr($studentRecord?->name ?? Auth::user()->username, 0, 2)) }}
    </div>
    <div class="profile-info">
        <h2>{{ $studentRecord?->name ?? Auth::user()->username }}</h2>
        <div class="profile-meta">
            <span class="profile-meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                {{ $studentRecord?->roll_no ?? Auth::user()->username }}
            </span>
            <span class="profile-meta-divider">&bull;</span>
            <span class="profile-meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                {{ $department }}
            </span>
            <span class="profile-meta-divider">&bull;</span>
            <span class="profile-meta-item">
                Semester {{ $semester }}
            </span>
            <span class="profile-meta-divider">&bull;</span>
            <span class="status-badge-active">&#11044; Active</span>
        </div>
    </div>
    <a href="{{ route('student.register-courses') }}" class="register-btn">+ Register Courses</a>
</div>

{{-- ── Important Alerts ── --}}
@if($feeRecord && $feeRecord->status !== 'paid')
<div class="dash-alert warning">
    <span class="dash-alert-icon">&#128176;</span>
    <div class="dash-alert-body">
        <strong>Tuition Fee Due &mdash; Semester {{ $semester }}</strong>
        <p>Your fee is <strong>{{ ucfirst($feeRecord->status) }}</strong>. Full payment is required to register for courses.</p>
    </div>
    <a href="{{ route('student.fee-payment') }}" class="dash-alert-action orange">Pay Now &rarr;</a>
</div>
@endif

@if($failedCoursesCount > 0)
<div class="dash-alert danger">
    <span class="dash-alert-icon">&#9888;</span>
    <div class="dash-alert-body">
        <strong>{{ $failedCoursesCount }} Failed Course(s) Require Attention</strong>
        @if($unpaidRetakeFeesCount > 0)
            <p>Pay the retake fee for <strong>{{ $unpaidRetakeFeesCount }}</strong> course(s) to unlock re-registration.</p>
        @else
            <p>Your retake fees are paid. Go to Register Courses to re-enrol.</p>
        @endif
    </div>
    @if($unpaidRetakeFeesCount > 0)
        <a href="{{ route('student.fee-payment') }}" class="dash-alert-action">Pay Retake Fee &rarr;</a>
    @else
        <a href="{{ route('student.register-courses') }}" class="dash-alert-action">Re-Register &rarr;</a>
    @endif
</div>
@endif

{{-- ── Stats Cards ── --}}
<div class="stats-grid-modern">
    <div class="stat-card-m blue">
        <div class="stat-icon-m">&#128218;</div>
        <div>
            <div class="stat-num-m">{{ $courseCount }}</div>
            <div class="stat-label-m">Enrolled Courses</div>
        </div>
    </div>
    <div class="stat-card-m green">
        <div class="stat-icon-m">&#127891;</div>
        <div>
            <div class="stat-num-m">{{ $totalCredits }}</div>
            <div class="stat-label-m">Current Credits</div>
        </div>
    </div>
    <div class="stat-card-m purple">
        <div class="stat-icon-m">&#128104;&#8205;&#127979;</div>
        <div>
            <div class="stat-num-m">{{ $teacherCount }}</div>
            <div class="stat-label-m">Instructors</div>
        </div>
    </div>
    <div class="stat-card-m orange">
        <div class="stat-icon-m">&#128197;</div>
        <div>
            <div class="stat-num-m">{{ $classesPerWeek }}</div>
            <div class="stat-label-m">Classes / Week</div>
        </div>
    </div>
</div>

{{-- ── Quick Actions ── --}}
<div class="sec-label">Quick Actions</div>
<div class="qa-grid">
    <a href="{{ route('student.register-courses') }}" class="qa-card">
        <div class="qa-icon-big">&#10133;</div>
        <div class="qa-label-sm">Register Courses</div>
    </a>
    <a href="{{ route('student.my-courses') }}" class="qa-card">
        <div class="qa-icon-big">&#128218;</div>
        <div class="qa-label-sm">My Courses</div>
    </a>
    <a href="{{ route('student.timetable') }}" class="qa-card">
        <div class="qa-icon-big">&#128197;</div>
        <div class="qa-label-sm">My Timetable</div>
    </a>
    <a href="{{ route('student.today') }}" class="qa-card">
        <div class="qa-icon-big">&#128336;</div>
        <div class="qa-label-sm">Today's Classes</div>
    </a>
    <a href="{{ route('student.fee-payment') }}" class="qa-card {{ ($feeRecord && $feeRecord->status !== 'paid') || $unpaidRetakeFeesCount > 0 ? 'qa-urgent' : '' }}">
        <div class="qa-icon-big">&#128179;</div>
        <div class="qa-label-sm">Fee Payment</div>
        @if(($feeRecord && $feeRecord->status !== 'paid') || $unpaidRetakeFeesCount > 0)
            <div class="qa-dot"></div>
        @endif
    </a>
    <a href="{{ route('student.profile') }}" class="qa-card">
        <div class="qa-icon-big">&#128100;</div>
        <div class="qa-label-sm">My Profile</div>
    </a>
</div>

{{-- ── Academic Summary ── --}}
<div class="academic-card">
    <div class="academic-card-header">
        <span>Current Academic Term</span>
        <span style="font-size:0.78rem;font-weight:600;color:#6b7280;text-transform:none;letter-spacing:0;">
            {{ now()->format('F Y') }}
        </span>
    </div>
    <div class="academic-grid">
        <div class="ac-item">
            <div class="ac-label">Program / Department</div>
            <div class="ac-value">{{ $department }}</div>
        </div>
        <div class="ac-item">
            <div class="ac-label">Current Semester</div>
            <div class="ac-value">Semester {{ $semester }}</div>
        </div>
        <div class="ac-item">
            <div class="ac-label">Academic Year</div>
            <div class="ac-value">{{ now()->year }}&ndash;{{ now()->year + 1 }}</div>
        </div>
        <div class="ac-item">
            <div class="ac-label">Tuition Fee Status</div>
            <div class="ac-value">
                @if(!$feeRecord)
                    <span style="color:#9ca3af;">Not Set</span>
                @elseif($feeRecord->status === 'paid')
                    <span style="color:#059669;">&#10003; Paid</span>
                @elseif($feeRecord->status === 'partial')
                    <span style="color:#2563eb;">Partial</span>
                @elseif($feeRecord->status === 'overdue')
                    <span style="color:#dc2626;">&#9888; Overdue</span>
                @else
                    <span style="color:#d97706;">Pending</span>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
