@extends('layouts.dashboard')

@section('title', 'Faculty Workload')
@section('role-label', 'Head of Department')
@section('page-title', 'Faculty Workload')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }
.summary-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:28px; }
.summary-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px; display:flex; align-items:center; gap:14px; }
.summary-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.icon-blue   { background:#eff6ff; }
.icon-green  { background:#f0fdf4; }
.icon-purple { background:#faf5ff; }
.summary-card h3 { font-size:1.5rem; font-weight:700; color:#1e293b; margin:0; }
.summary-card p  { font-size:.8rem; color:#64748b; margin:0; }
.workload-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:24px; margin-bottom:20px; }
.workload-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
.faculty-name { font-size:1rem; font-weight:700; color:#1e293b; }
.faculty-meta { font-size:.82rem; color:#64748b; margin-top:2px; }
.workload-stats { display:flex; gap:24px; }
.wl-stat { display:flex; flex-direction:column; align-items:center; }
.wl-stat .val { font-size:1.3rem; font-weight:700; color:#6366f1; }
.wl-stat .lbl { font-size:.72rem; color:#64748b; text-align:center; }
.progress-bar-wrap { margin:12px 0 16px; }
.progress-label { display:flex; justify-content:space-between; font-size:.8rem; color:#64748b; margin-bottom:4px; }
.progress-bar { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
.progress-fill { height:100%; border-radius:4px; background:#6366f1; transition:width .3s; }
.progress-fill.overload { background:#ef4444; }
.progress-fill.normal   { background:#22c55e; }
.schedule-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.schedule-table th { padding:8px 12px; background:#f8fafc; color:#64748b; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; text-align:left; border-bottom:1px solid #e2e8f0; }
.schedule-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; color:#334155; }
.schedule-table tbody tr:last-child td { border-bottom:none; }
.badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab    { background:#fef3c7; color:#92400e; }
.no-schedule { font-size:.85rem; color:#94a3b8; font-style:italic; padding:8px 0; }
.collapsible-btn { background:none; border:none; color:#6366f1; font-size:.82rem; font-weight:600; cursor:pointer; padding:0; }
.collapsible-btn:hover { text-decoration:underline; }
.hidden { display:none; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:3rem; margin-bottom:12px; }
</style>

<div class="page-header">
    <h2>&#128202; Faculty Workload</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

@php
    $totalFaculty    = $facultyWorkload->count();
    $totalCourses    = $facultyWorkload->sum('courses_count');
    $totalHours      = $facultyWorkload->sum('hours_per_week');
    $overloadedCount = $facultyWorkload->filter(fn($f) => $f->hours_per_week > 20)->count();
@endphp

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="summary-icon icon-blue">&#128100;</div>
        <div>
            <h3>{{ $totalFaculty }}</h3>
            <p>Total Faculty</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon icon-green">&#128218;</div>
        <div>
            <h3>{{ $totalCourses }}</h3>
            <p>Total Assignments</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon icon-purple">&#9201;</div>
        <div>
            <h3>{{ number_format($totalHours, 1) }}</h3>
            <p>Total Hours / Week</p>
        </div>
    </div>
    @if($overloadedCount > 0)
    <div class="summary-card" style="border:1px solid #fca5a5;">
        <div class="summary-icon" style="background:#fee2e2;">&#9888;</div>
        <div>
            <h3 style="color:#dc2626;">{{ $overloadedCount }}</h3>
            <p>Overloaded (&gt;20h/week)</p>
        </div>
    </div>
    @endif
</div>

<!-- Per-Faculty Cards -->
@if($facultyWorkload->count() > 0)
    @foreach($facultyWorkload as $faculty)
        @php
            $maxHours = 20;
            $pct      = min(100, ($faculty->hours_per_week / $maxHours) * 100);
            $isOver   = $faculty->hours_per_week > $maxHours;
        @endphp
        <div class="workload-card">
            <div class="workload-card-header">
                <div>
                    <div class="faculty-name">{{ $faculty->name }}</div>
                    <div class="faculty-meta">Employee ID: {{ $faculty->employee_id ?? 'N/A' }}</div>
                </div>
                <div class="workload-stats">
                    <div class="wl-stat">
                        <span class="val">{{ $faculty->courses_count }}</span>
                        <span class="lbl">Courses</span>
                    </div>
                    <div class="wl-stat">
                        <span class="val">{{ $faculty->classes_per_week }}</span>
                        <span class="lbl">Classes/wk</span>
                    </div>
                    <div class="wl-stat">
                        <span class="val" style="{{ $isOver ? 'color:#ef4444;' : '' }}">
                            {{ $faculty->hours_per_week }}h
                        </span>
                        <span class="lbl">Hours/wk</span>
                    </div>
                </div>
            </div>

            <!-- Workload Progress Bar -->
            <div class="progress-bar-wrap">
                <div class="progress-label">
                    <span>Workload</span>
                    <span>{{ $faculty->hours_per_week }}h / {{ $maxHours }}h max</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill {{ $isOver ? 'overload' : 'normal' }}"
                         style="width:{{ $pct }}%"></div>
                </div>
            </div>

            <!-- Schedule Details (collapsible) -->
            @if($faculty->schedule && $faculty->schedule->count() > 0)
                <button class="collapsible-btn" onclick="toggleSchedule('sched-{{ $faculty->id }}')">
                    &#128197; View Schedule ({{ $faculty->schedule->count() }} slots)
                </button>
                <div id="sched-{{ $faculty->id }}" class="hidden" style="margin-top:10px;">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Course</th>
                                <th>Room</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faculty->schedule as $slot)
                                <tr>
                                    <td>{{ $slot->day_of_week }}</td>
                                    <td>{{ substr($slot->start_time, 0, 5) }} – {{ substr($slot->end_time, 0, 5) }}</td>
                                    <td>{{ $slot->courseSection->course->name ?? 'N/A' }}</td>
                                    <td>{{ $slot->room->room_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($slot->component)
                                            <span class="badge badge-{{ $slot->component }}">
                                                {{ ucfirst($slot->component) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="no-schedule">No scheduled classes in the active timetable.</p>
            @endif
        </div>
    @endforeach
@else
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);">
        <div class="empty-state">
            <div class="empty-icon">&#128202;</div>
            <p>No faculty members found in your department.</p>
        </div>
    </div>
@endif

@push('scripts')
<script>
function toggleSchedule(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.toggle('hidden');
    }
}
</script>
@endpush

@endsection
