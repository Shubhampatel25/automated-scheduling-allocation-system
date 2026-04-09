@extends('layouts.dashboard')

@section('title', 'Student Timetable')
@section('role-label', 'Head of Department')
@section('page-title', 'Student Timetable')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@push('styles')
<style>
@media print { .no-print { display:none !important; } }
.breadcrumb { font-size:.82rem; color:#64748b; margin-bottom:16px; }
.breadcrumb a { color:#6366f1; text-decoration:none; }
.tt-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.tt-header h2 { font-size:1.3rem; font-weight:700; color:#1e293b; margin:0; }
.btn-print { padding:8px 18px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; }
.meta-bar { display:flex; gap:24px; flex-wrap:wrap; background:#fff; border-radius:10px; padding:16px 20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.meta-item .lbl { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
.meta-item .val { font-size:.95rem; font-weight:700; color:#1e293b; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
.status-active { background:#dcfce7; color:#16a34a; }
.status-draft  { background:#fef9c3; color:#854d0e; }
.draft-alert { background:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; margin-bottom:16px; border:1px solid #fde68a; font-size:.87rem; }
.filter-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.filter-row label { font-size:.82rem; font-weight:600; color:#374151; }
.filter-row select { padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:.85rem; }
.filter-row button { padding:6px 14px; background:#6366f1; color:#fff; border:none; border-radius:6px; font-size:.82rem; font-weight:600; cursor:pointer; }
table.timetable { width:100%; border-collapse:collapse; font-size:.85rem; min-width:700px; }
table.timetable th { background:#6366f1; color:#fff; font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; padding:10px 12px; border:1px solid #4f46e5; text-align:center; }
table.timetable th.time-h { text-align:left; background:#4f46e5; }
table.timetable td { border:1px solid #e2e8f0; padding:6px 8px; vertical-align:top; }
table.timetable td.time-col { background:#f8f9ff; font-size:.78rem; font-weight:600; color:#6366f1; white-space:nowrap; padding:10px 12px; }
.slot { background:#eef2ff; border-radius:6px; padding:7px 9px; border-left:3px solid #6366f1; }
.slot.lab-slot { background:#fef9c3; border-left-color:#d97706; }
.course-name { font-weight:700; color:#3730a3; font-size:.83rem; margin-bottom:3px; }
.room-name { font-size:.75rem; background:#6366f1; color:#fff; padding:2px 7px; border-radius:4px; display:inline-block; margin-bottom:2px; }
.room-name.lab-room { background:#d97706; }
.teacher-name { font-size:.75rem; color:#475569; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
</style>
@endpush

@section('content')

<div class="breadcrumb no-print">
    <a href="{{ route('hod.dashboard') }}">Dashboard</a> /
    <a href="{{ route('hod.students') }}">Students</a> /
    Timetable &mdash; {{ $student->name }}
</div>

<div class="tt-header">
    <h2>&#128197; Timetable &mdash; {{ $student->name }}</h2>
    <button class="btn-print no-print" onclick="window.print()">&#128438; Print</button>
</div>

@if($isDraft)
<div class="draft-alert">
    &#9888; <strong>Draft Schedule</strong> &mdash; This timetable is pending final approval. Times may still change.
</div>
@endif

@if($timetable)
<div class="meta-bar">
    <div class="meta-item"><div class="lbl">Student</div><div class="val">{{ $student->name }}</div></div>
    <div class="meta-item"><div class="lbl">Roll No</div><div class="val">{{ $student->roll_no }}</div></div>
    <div class="meta-item"><div class="lbl">Semester</div><div class="val">{{ $semester }}</div></div>
    <div class="meta-item">
        <div class="lbl">Status</div>
        <div class="val"><span class="status-badge status-{{ $timetable->status }}">{{ ucfirst($timetable->status) }}</span></div>
    </div>
    <div class="meta-item"><div class="lbl">Term / Year</div><div class="val">{{ $timetable->term }} {{ $timetable->year }}</div></div>
    @if($timetable->generated_at)
    <div class="meta-item"><div class="lbl">Generated</div><div class="val">{{ $timetable->generated_at->format('d M Y') }}</div></div>
    @endif
</div>
@endif

<div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
    @php
        $days       = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        $allCourses = $weeklySchedule->map(fn($s) => $s->courseSection?->course)->filter()->unique('id')->sortBy('name');
        $periods    = $weeklySchedule->map(fn($s) => ['start'=>substr($s->start_time,0,5),'end'=>substr($s->end_time,0,5)])
                          ->unique('start')->sortBy('start')->values();
    @endphp

    @if($weeklySchedule->count() > 0)
    <div class="filter-row no-print">
        <label>Day:</label>
        <select id="dayFilter" onchange="applyFilters()">
            <option value="all">All Days</option>
            @foreach($days as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
        </select>
        <label>Course:</label>
        <select id="courseFilter" onchange="applyFilters()">
            <option value="all">All Courses</option>
            @foreach($allCourses as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <button onclick="clearFilters()">Clear</button>
    </div>

        <table class="timetable" id="ttTable">
            <thead>
                <tr>
                    <th class="time-h">Time</th>
                    @foreach($days as $day)<th class="day-col" data-day="{{ $day }}">{{ $day }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                <tr>
                    <td class="time-col">{{ $period['start'] }} – {{ $period['end'] }}</td>
                    @foreach($days as $day)
                        @php
                            $slot = $weeklySchedule->first(
                                fn($s) => $s->day_of_week === $day && substr($s->start_time,0,5) === $period['start']
                            );
                        @endphp
                        <td class="day-col" data-day="{{ $day }}"
                            data-course="{{ $slot?->courseSection?->course?->id ?? '' }}">
                            @if($slot)
                                @php
                                    $isLab    = $slot->component === 'lab';
                                    $isRetake = !empty($retakeCourseIds)
                                        && in_array($slot->courseSection?->course?->id, $retakeCourseIds);
                                @endphp
                                <div class="slot {{ $isLab ? 'lab-slot' : '' }}"
                                     style="{{ $isRetake ? 'border-left:3px solid #dc2626;' : '' }}">
                                    <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                    @if($isRetake)
                                        <div style="font-size:.62rem;color:#dc2626;font-weight:700;letter-spacing:.04em;margin-bottom:1px;">&#8635; RETAKE</div>
                                    @endif
                                    <div><span class="room-name {{ $isLab ? 'lab-room' : '' }}">
                                        &#127968; {{ $slot->room->room_number ?? '' }}
                                    </span></div>
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
            <div style="font-size:3rem;margin-bottom:12px;">&#128197;</div>
            <p>No timetable available for {{ $student->name }} (Semester {{ $semester }}).</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
function applyFilters() {
    const day    = document.getElementById('dayFilter').value;
    const course = document.getElementById('courseFilter').value;
    document.querySelectorAll('#ttTable thead th.day-col').forEach(th => {
        th.style.display = (day === 'all' || th.dataset.day === day) ? '' : 'none';
    });
    document.querySelectorAll('#ttTable tbody tr').forEach(tr => {
        tr.querySelectorAll('td.day-col').forEach(td => {
            td.style.display = (day === 'all' || td.dataset.day === day) ? '' : 'none';
        });
    });
}
function clearFilters() {
    document.getElementById('dayFilter').value    = 'all';
    document.getElementById('courseFilter').value = 'all';
    applyFilters();
}
</script>
@endpush

@endsection
