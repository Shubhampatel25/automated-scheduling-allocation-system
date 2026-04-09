@extends('layouts.dashboard')

@section('title', 'My Teaching Timetable')
@section('role-label', 'Professor Panel')
@section('page-title', 'My Teaching Timetable')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('professor.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Teaching</div>
    <a href="{{ route('professor.timetable') }}" class="nav-link active">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="{{ route('professor.students') }}" class="nav-link">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="{{ route('professor.availability') }}" class="nav-link">
        <span class="icon">&#128336;</span> Set Availability
    </a>

    <div class="nav-section-title">Account</div>
    <a href="{{ route('professor.dashboard') }}#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@section('content')

<style>
@media print { .no-print { display:none !important; } }

.breadcrumb { font-size:.82rem; color:#64748b; margin-bottom:14px; }
.breadcrumb a { color:#6366f1; text-decoration:none; }
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.page-header h2 { font-size:1.35rem; font-weight:700; color:#1e293b; margin:0; }
.btn-print { padding:8px 20px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; }
.btn-print:hover { background:#4f46e5; }

.meta-bar { display:flex; gap:24px; flex-wrap:wrap; background:#fff; border-radius:10px;
            padding:16px 22px; margin-bottom:22px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.meta-item .lbl { font-size:.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase;
                  letter-spacing:.06em; margin-bottom:2px; }
.meta-item .val { font-size:.95rem; font-weight:700; color:#1e293b; }

.tt-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07);
              padding:22px; overflow-x:auto; }
.tt-wrapper h3 { font-size:1.05rem; font-weight:700; color:#1e293b; margin:0 0 18px; }

table.tt { width:100%; border-collapse:collapse; font-size:.85rem; min-width:700px; }
table.tt th { background:#f8fafc; color:#64748b; font-weight:600; font-size:.78rem;
              text-transform:uppercase; letter-spacing:.05em; padding:10px 14px;
              border:1px solid #e2e8f0; text-align:center; }
table.tt th.time-h { text-align:left; min-width:110px; }
table.tt td { border:1px solid #e2e8f0; padding:6px 8px; vertical-align:top; min-height:60px; }
table.tt td.time-col { background:#f8fafc; color:#64748b; font-weight:600; font-size:.78rem;
                       white-space:nowrap; padding:10px 14px; }

.slot-card { border-radius:7px; padding:8px 10px; border-left:3px solid #6366f1;
             background:#eef2ff; margin-bottom:4px; }
.slot-card.lab-slot { background:#fef9c3; border-left-color:#d97706; }
.slot-course { font-weight:700; color:#3730a3; font-size:.82rem; line-height:1.3; margin-bottom:3px; }
.slot-room { display:inline-block; font-size:.72rem; font-weight:700; color:#fff;
             background:#6366f1; padding:2px 7px; border-radius:4px; margin-bottom:3px; }
.slot-room.lab-room { background:#d97706; }
.slot-sem { display:inline-block; font-size:.68rem; font-weight:700; color:#6366f1;
            background:#ede9fe; padding:1px 7px; border-radius:10px; margin-left:3px; }
.slot-comp { display:inline-block; font-size:.68rem; font-weight:600; padding:1px 6px;
             border-radius:4px; margin-top:2px; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab    { background:#fef3c7; color:#92400e; }

.summary-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.chip { background:#f1f5f9; border-radius:20px; padding:5px 14px; font-size:.8rem;
        font-weight:600; color:#475569; }
.chip strong { color:#1e293b; }

.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state .ei { font-size:3rem; margin-bottom:12px; }
</style>

<div class="breadcrumb no-print">
    <a href="{{ route('professor.dashboard') }}">Dashboard</a> / My Teaching Timetable
</div>

<div class="page-header">
    <h2>&#128197; My Teaching Timetable</h2>
    <button class="btn-print no-print" onclick="window.print()">&#128438; Print</button>
</div>

@if($teacher)
<div class="meta-bar">
    <div class="meta-item">
        <div class="lbl">Name</div>
        <div class="val">{{ $teacher->name }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Employee ID</div>
        <div class="val">{{ $teacher->employee_id ?? 'N/A' }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Department</div>
        <div class="val">{{ $teacher->department->name ?? 'N/A' }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Classes / Week</div>
        <div class="val">{{ $classesPerWeek }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Hours / Week</div>
        <div class="val">{{ $hoursPerWeek }} h</div>
    </div>
</div>
@endif

<div class="tt-wrapper">
    <h3>All Active Semesters — Combined Weekly View</h3>

    @php
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

        $periods = $timetableSlots
            ->map(fn($s) => [
                'start' => substr($s->start_time, 0, 5),
                'end'   => substr($s->end_time,   0, 5),
            ])
            ->unique('start')
            ->sortBy('start')
            ->values();

        $semestersFound = $timetableSlots
            ->map(fn($s) => $s->timetable?->semester)
            ->filter()
            ->unique()
            ->sort()
            ->values();
    @endphp

    @if($semestersFound->count() > 0)
    <div class="summary-row no-print">
        <div class="chip">Showing <strong>{{ $semestersFound->count() }}</strong> semester(s):</div>
        @foreach($semestersFound as $sem)
            <div class="chip">Sem <strong>{{ $sem }}</strong></div>
        @endforeach
    </div>
    @endif

    @if($timetableSlots->count() > 0)
        <table class="tt">
            <thead>
                <tr>
                    <th class="time-h">Time</th>
                    @foreach($days as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                <tr>
                    <td class="time-col">{{ $period['start'] }} – {{ $period['end'] }}</td>
                    @foreach($days as $day)
                        <td>
                            @php
                                $daySlots = $timetableSlots->filter(
                                    fn($s) => $s->day_of_week === $day
                                           && substr($s->start_time, 0, 5) === $period['start']
                                );
                            @endphp
                            @foreach($daySlots as $slot)
                                @php $isLab = $slot->component === 'lab'; @endphp
                                <div class="slot-card {{ $isLab ? 'lab-slot' : '' }}">
                                    <div class="slot-course">
                                        {{ $slot->courseSection->course->name ?? 'N/A' }}
                                    </div>
                                    <div>
                                        <span class="slot-room {{ $isLab ? 'lab-room' : '' }}">
                                            &#127968; {{ $slot->room->room_number ?? 'N/A' }}
                                        </span>
                                        @if($slot->timetable?->semester)
                                            <span class="slot-sem">Sem {{ $slot->timetable->semester }}</span>
                                        @endif
                                    </div>
                                    @if($slot->component)
                                        <div>
                                            <span class="slot-comp badge-{{ $slot->component }}">
                                                {{ ucfirst($slot->component) }}
                                            </span>
                                        </div>
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
            <div class="ei">&#128197;</div>
            <p>No active teaching slots found for your account.</p>
            <p style="font-size:.85rem;color:#94a3b8;margin-top:6px;">
                Your schedule will appear here once the HOD activates a timetable that includes you.
            </p>
        </div>
    @endif
</div>

@endsection
