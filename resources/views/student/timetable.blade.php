@extends('layouts.dashboard')

@section('title', 'My Timetable')
@section('role-label', 'Student Panel')
@section('page-title', 'My Timetable')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
@media print { .no-print { display:none !important; } }
.tt-breadcrumb { font-size:.82rem; color:#6b7280; margin-bottom:12px; }
.tt-breadcrumb a { color:#6366f1; text-decoration:none; }
.tt-filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.tt-filter-row label { font-size:.82rem; font-weight:600; color:#374151; }
.tt-filter-row select { padding:5px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:.83rem; }
.tt-filter-row button { padding:5px 12px; background:#6366f1; color:#fff; border:none; border-radius:6px; font-size:.8rem; font-weight:600; cursor:pointer; }
.btn-print-tt { padding:7px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; font-size:.83rem; font-weight:600; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="tt-breadcrumb no-print">
    <a href="{{ route('student.dashboard') }}">Dashboard</a> / My Timetable
</div>

@if(isset($timetableIsDraft) && $timetableIsDraft)
<div style="background:#fef3c7;color:#92400e;padding:11px 16px;border-radius:8px;margin-bottom:16px;border:1px solid #fde68a;font-size:0.87rem;display:flex;align-items:center;gap:10px;">
    <span style="font-size:1rem;">&#9888;</span>
    <span><strong>Draft Schedule</strong> &mdash; Your timetable is pending final approval by your HOD. Times or rooms may still change.</span>
</div>
@endif
<div class="dashboard-card">
    <div class="card-header">
        <div>
            <h3 style="margin:0;">My Weekly Timetable &mdash; Semester {{ $semester }}</h3>
            <p style="margin:4px 0 0;font-size:0.78rem;color:#6b7280;">{{ now()->format('F Y') }} &bull; {{ now()->format('l') }} Today</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            @if($weeklySchedule->count() > 0)
                @php $ttStatus = isset($timetableIsDraft) && $timetableIsDraft ? 'draft' : 'active'; @endphp
                <span class="badge {{ $ttStatus === 'active' ? 'badge-success' : 'badge-warning' }}"
                      style="font-size:.78rem;padding:3px 10px;">
                    {{ ucfirst($ttStatus) }}
                </span>
            @endif
            <button class="btn-print-tt no-print" onclick="window.print()">&#128438; Print</button>
            <span class="badge badge-warning">&#128197; Week View</span>
        </div>
    </div>
    <div class="card-body">
        <div class="timetable-container">
            @if($weeklySchedule->count() > 0)
                @php
                    // Build time periods dynamically from actual DB slot times — no hardcoding
                    $periods = $weeklySchedule
                        ->map(fn($s) => [
                            'start' => substr($s->start_time, 0, 5),
                            'end'   => substr($s->end_time,   0, 5),
                            'key'   => substr($s->start_time, 0, 5),
                        ])
                        ->unique('key')
                        ->sortBy('start')
                        ->values();

                    $days       = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                    $allCourses = $weeklySchedule->map(fn($s) => $s->courseSection?->course)
                                      ->filter()->unique('id')->sortBy('name');
                @endphp

                {{-- Day + Course filters --}}
                <div class="tt-filter-row no-print">
                    <label>Day:</label>
                    <select id="ttDayFilter" onchange="ttApplyFilters()">
                        <option value="all">All Days</option>
                        @foreach($days as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                    </select>
                    <label>Course:</label>
                    <select id="ttCourseFilter" onchange="ttApplyFilters()">
                        <option value="all">All Courses</option>
                        @foreach($allCourses as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <button onclick="ttClearFilters()">Clear</button>
                </div>

                <table class="timetable" id="ttStudentTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            @foreach($days as $day)<th class="day-hdr" data-day="{{ $day }}">{{ $day }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                        <tr>
                            <td class="time-col">{{ $period['start'] }} – {{ $period['end'] }}</td>
                            @foreach($days as $day)
                                @php
                                    $slot = $weeklySchedule->first(
                                        fn($s) => $s->day_of_week === $day
                                               && substr($s->start_time, 0, 5) === $period['start']
                                    );
                                @endphp
                                <td class="day-cell" data-day="{{ $day }}"
                                    data-course="{{ $slot?->courseSection?->course?->id ?? '' }}">
                                    @if($slot)
                                        @php
                                            // Mark as retake if this course is in the student's net-failed list.
                                            // $retakeCourseIds is [] for new students — no badge shown.
                                            $isRetake = !empty($retakeCourseIds)
                                                && in_array($slot->courseSection?->course?->id, $retakeCourseIds);
                                        @endphp
                                        <div class="slot" style="{{ $isRetake ? 'border-left:3px solid #dc2626;padding-left:6px;' : '' }}">
                                            <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                            @if($isRetake)
                                                <div style="font-size:0.62rem;color:#dc2626;font-weight:700;letter-spacing:.04em;margin-bottom:1px;">&#8635; RETAKE</div>
                                            @endif
                                            <div class="room-name">&#127968; {{ $slot->room->room_number ?? '' }}</div>
                                            <div class="teacher-name">{{ $slot->teacher->name ?? '' }}</div>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-icon">&#128197;</div>
                    <p>No timetable available for Semester {{ $semester }} yet.</p>
                    <p class="empty-hint" style="margin-top:8px;">
                        <a href="{{ route('student.register-courses') }}" style="color:#6366f1;font-weight:600;">Register for courses first</a>
                        &mdash; your timetable will appear once a schedule is published.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function ttApplyFilters() {
    const day    = document.getElementById('ttDayFilter').value;
    const course = document.getElementById('ttCourseFilter').value;
    document.querySelectorAll('#ttStudentTable th.day-hdr').forEach(th => {
        th.style.display = (day === 'all' || th.dataset.day === day) ? '' : 'none';
    });
    document.querySelectorAll('#ttStudentTable tbody tr').forEach(tr => {
        tr.querySelectorAll('td.day-cell').forEach(td => {
            td.style.display = (day === 'all' || td.dataset.day === day) ? '' : 'none';
        });
    });
}
function ttClearFilters() {
    document.getElementById('ttDayFilter').value    = 'all';
    document.getElementById('ttCourseFilter').value = 'all';
    ttApplyFilters();
}
</script>
@endpush

@endsection
