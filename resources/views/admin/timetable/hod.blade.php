@extends('layouts.dashboard')

@section('title', 'HOD Teaching Timetable')
@section('role-label', 'Admin Panel')
@section('page-title', 'HOD Teaching Timetable')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<style>
@media print { .no-print { display:none !important; } }
.breadcrumb-nav { font-size:.82rem; color:#6b7280; margin-bottom:16px; }
.breadcrumb-nav a { color:#4f46e5; text-decoration:none; }
.tt-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.tt-header h2 { font-size:1.3rem; font-weight:700; color:#1e293b; margin:0; }
.btn-print { padding:8px 18px; background:#4f46e5; color:#fff; border:none; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; }
.meta-bar { display:flex; gap:24px; flex-wrap:wrap; background:#fff; border-radius:10px; padding:16px 20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.meta-item .lbl { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
.meta-item .val { font-size:.95rem; font-weight:700; color:#1e293b; }
.filter-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.filter-row label { font-size:.82rem; font-weight:600; color:#374151; }
.filter-row select { padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:.85rem; }
.filter-row button { padding:6px 14px; background:#6366f1; color:#fff; border:none; border-radius:6px; font-size:.82rem; font-weight:600; cursor:pointer; }
table.timetable { width:100%; border-collapse:collapse; font-size:.85rem; min-width:700px; }
table.timetable th { background:#6366f1; color:#fff; font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; padding:10px 12px; border:1px solid #4f46e5; text-align:center; }
table.timetable th.time-h { text-align:left; background:#4f46e5; }
table.timetable td { border:1px solid #e2e8f0; padding:6px 8px; vertical-align:top; }
table.timetable td.time-col { background:#f8f9ff; font-size:.78rem; font-weight:600; color:#6366f1; white-space:nowrap; padding:10px 12px; }
.slot-card { background:#eef2ff; border-radius:6px; padding:7px 9px; border-left:3px solid #6366f1; margin-bottom:4px; }
.slot-card.lab-slot { background:#fef9c3; border-left-color:#d97706; }
.course-name { font-weight:700; color:#3730a3; font-size:.83rem; margin-bottom:3px; }
.room-name { font-size:.75rem; background:#6366f1; color:#fff; padding:2px 7px; border-radius:4px; display:inline-block; margin-bottom:2px; }
.room-name.lab-room { background:#d97706; }
.sem-badge { font-size:.68rem; font-weight:700; color:#6366f1; background:#ede9fe; padding:1px 7px; border-radius:10px; margin-left:3px; }
.comp-badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:.7rem; font-weight:600; margin-top:2px; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab    { background:#fef3c7; color:#92400e; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.summary-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.chip { background:#f1f5f9; border-radius:20px; padding:5px 14px; font-size:.8rem; font-weight:600; color:#475569; }
.chip strong { color:#1e293b; }
</style>
@endpush

@section('content')

<div class="breadcrumb-nav no-print">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    <a href="{{ route('admin.hods.index') }}">HODs</a> /
    Teaching Timetable &mdash; {{ $teacher->name }}
</div>

<div class="tt-header">
    <h2>&#128197; Teaching Timetable &mdash; {{ $teacher->name }}</h2>
    <button class="btn-print no-print" onclick="window.print()">&#128438; Print</button>
</div>

<div class="meta-bar">
    <div class="meta-item"><div class="lbl">HOD</div><div class="val">{{ $teacher->name }}</div></div>
    <div class="meta-item"><div class="lbl">Employee ID</div><div class="val">{{ $teacher->employee_id ?? 'N/A' }}</div></div>
    <div class="meta-item"><div class="lbl">Department</div><div class="val">{{ $teacher->department->name ?? 'N/A' }}</div></div>
    <div class="meta-item"><div class="lbl">Classes / Week</div><div class="val">{{ $slots->count() }}</div></div>
    <div class="meta-item">
        <div class="lbl">Hours / Week</div>
        <div class="val">{{ round($slots->sum(fn($s) => (strtotime($s->end_time) - strtotime($s->start_time)) / 3600), 1) }} h</div>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header"><h3>All Active Semesters — Combined Weekly View</h3></div>
    <div class="card-body" style="overflow-x:auto;">

        @php
            $days    = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
            $periods = $slots->map(fn($s) => [
                            'start' => substr($s->start_time,0,5),
                            'end'   => substr($s->end_time,0,5),
                        ])->unique('start')->sortBy('start')->values();

            $allCourses     = $slots->map(fn($s) => $s->courseSection?->course)->filter()->unique('id')->sortBy('name');
            $semestersFound = $slots->map(fn($s) => $s->timetable?->semester)->filter()->unique()->sort()->values();
        @endphp

        @if($semestersFound->count() > 0)
        <div class="summary-row no-print">
            <div class="chip">Showing <strong>{{ $semestersFound->count() }}</strong> semester(s):</div>
            @foreach($semestersFound as $sem)
                <div class="chip">Sem <strong>{{ $sem }}</strong></div>
            @endforeach
        </div>
        @endif

        @if($slots->count() > 0)
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
        @endif

        @if($slots->count() > 0)
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
                                $daySlots = $slots->filter(
                                    fn($s) => $s->day_of_week === $day
                                           && substr($s->start_time,0,5) === $period['start']
                                );
                            @endphp
                            <td class="day-col" data-day="{{ $day }}"
                                data-course="{{ $daySlots->first()?->courseSection?->course?->id ?? '' }}">
                                @foreach($daySlots as $slot)
                                    @php $isLab = $slot->component === 'lab'; @endphp
                                    <div class="slot-card {{ $isLab ? 'lab-slot' : '' }}">
                                        <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                        <div>
                                            <span class="room-name {{ $isLab ? 'lab-room' : '' }}">
                                                &#127968; {{ $slot->room->room_number ?? '' }}
                                            </span>
                                            @if($slot->timetable?->semester)
                                                <span class="sem-badge">Sem {{ $slot->timetable->semester }}</span>
                                            @endif
                                        </div>
                                        @if($slot->component)
                                            <span class="comp-badge badge-{{ $slot->component }}">{{ ucfirst($slot->component) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <div style="font-size:3rem;margin-bottom:12px;">&#128197;</div>
                <p>{{ $teacher->name }} has no active teaching slots scheduled.</p>
            </div>
        @endif
    </div>
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
