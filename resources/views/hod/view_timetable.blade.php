@extends('layouts.dashboard')

@section('title', 'View Timetable')
@section('role-label', 'Head of Department')
@section('page-title', 'View Timetable')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }
.filter-bar { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px 24px; margin-bottom:24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.filter-bar label { font-size:.85rem; font-weight:600; color:#374151; }
.filter-bar select { padding:9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; background:#fff; outline:none; }
.filter-bar select:focus { border-color:#6366f1; }
.btn-filter { padding:9px 20px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; }
.btn-filter:hover { background:#4f46e5; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.status-active   { background:#dcfce7; color:#16a34a; }
.status-draft    { background:#fef9c3; color:#854d0e; }
.status-archived { background:#f1f5f9; color:#64748b; }
.tt-meta { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px 24px; margin-bottom:24px; display:flex; gap:32px; flex-wrap:wrap; }
.tt-meta-item { display:flex; flex-direction:column; gap:3px; }
.tt-meta-item .label { font-size:.78rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.tt-meta-item .value { font-size:1rem; font-weight:700; color:#1e293b; }
.timetable-wrapper { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px; overflow-x:auto; }
.timetable-wrapper h3 { font-size:1.05rem; font-weight:600; color:#1e293b; margin:0 0 16px; }
table.tt { width:100%; border-collapse:collapse; min-width:700px; font-size:.85rem; }
table.tt th { background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; padding:10px 12px; text-align:center; border:1px solid #e2e8f0; }
table.tt th.time-h { text-align:left; }
table.tt td { border:1px solid #e2e8f0; padding:6px 8px; vertical-align:top; min-height:60px; }
table.tt td.time-col { background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; white-space:nowrap; padding:10px 12px; text-align:left; }
.slot-cell { background:#eef2ff; border-radius:6px; padding:7px 9px; border-left:3px solid #6366f1; }
.slot-cell.lab-slot { background:#fef9c3; border-left-color:#d97706; }
.slot-course { font-weight:700; color:#3730a3; font-size:.83rem; margin-bottom:4px; line-height:1.3; }
.slot-room-wrap { display:inline-flex; align-items:center; gap:4px; background:#6366f1; color:#fff; border-radius:4px; padding:2px 7px; font-size:.74rem; font-weight:700; margin-bottom:3px; }
.slot-room-wrap.lab-room { background:#d97706; }
.slot-teacher { font-size:.75rem; color:#475569; margin-bottom:2px; }
.slot-badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:.7rem; font-weight:600; margin-top:2px; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab    { background:#fef3c7; color:#92400e; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:3rem; margin-bottom:12px; }
.legend { display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
.slot-edit-link { display:block; margin-top:4px; font-size:.7rem; color:#6366f1; text-decoration:none; text-align:right; opacity:.7; }
.slot-edit-link:hover { opacity:1; text-decoration:underline; }
.legend-item { display:flex; align-items:center; gap:6px; font-size:.82rem; color:#374151; }
.legend-color { width:14px; height:14px; border-radius:3px; }
</style>

<div class="page-header">
    <h2>&#128197; Department Timetable</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

<!-- Timetable Selector -->
<div class="filter-bar">
    <label for="timetable_id">Select Timetable:</label>
    <form method="GET" action="{{ route('hod.view-timetable') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <select name="timetable_id" id="timetable_id" onchange="this.form.submit()">
            <option value="">-- No Timetable --</option>
            @foreach($timetables as $tt)
                <option value="{{ $tt->id }}"
                    {{ ($selectedTimetable && $selectedTimetable->id == $tt->id) ? 'selected' : '' }}>
                    {{ $tt->term }} {{ $tt->year }}
                    @if($tt->semester) · Sem {{ $tt->semester }} @endif
                    · {{ ucfirst($tt->status) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn-filter">Load</button>
    </form>

    @if($selectedTimetable)
        <span class="status-badge status-{{ $selectedTimetable->status }}">
            {{ ucfirst($selectedTimetable->status) }}
        </span>
        <span style="font-size:.85rem;color:#64748b;">
            {{ $timetableSlots->count() }} slots scheduled
        </span>
    @endif
</div>

@if($selectedTimetable)
    <!-- Timetable Meta -->
    <div class="tt-meta">
        <div class="tt-meta-item">
            <span class="label">Term</span>
            <span class="value">{{ $selectedTimetable->term }} {{ $selectedTimetable->year }}</span>
        </div>
        @if($selectedTimetable->semester)
        <div class="tt-meta-item">
            <span class="label">Semester</span>
            <span class="value">{{ $selectedTimetable->semester }}</span>
        </div>
        @endif
        <div class="tt-meta-item">
            <span class="label">Status</span>
            <span class="value">
                <span class="status-badge status-{{ $selectedTimetable->status }}">
                    {{ ucfirst($selectedTimetable->status) }}
                </span>
            </span>
        </div>
        <div class="tt-meta-item">
            <span class="label">Total Slots</span>
            <span class="value">{{ $timetableSlots->count() }}</span>
        </div>
        <div class="tt-meta-item">
            <span class="label">Generated At</span>
            <span class="value">{{ $selectedTimetable->generated_at ? $selectedTimetable->generated_at->format('d M Y') : 'N/A' }}</span>
        </div>
    </div>

    <!-- Timetable Grid -->
    <div class="timetable-wrapper">
        <h3>Weekly Schedule</h3>

        <div class="legend">
            <div class="legend-item"><div class="legend-color" style="background:#eef2ff;border:1px solid #c7d2fe;"></div> Slot</div>
            <div class="legend-item"><span class="slot-badge badge-theory">Theory</span></div>
            <div class="legend-item"><span class="slot-badge badge-lab">Lab</span></div>
        </div>

        @php
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
            $times = [
                ['label' => '08:00 – 09:30', 'start' => '08:00'],
                ['label' => '09:40 – 11:10', 'start' => '09:40'],
                ['label' => '11:20 – 12:50', 'start' => '11:20'],
                ['label' => '13:50 – 15:20', 'start' => '13:50'],
                ['label' => '15:30 – 17:00', 'start' => '15:30'],
            ];
        @endphp

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
                    @foreach($times as $time)
                        <tr>
                            <td class="time-col">{{ $time['label'] }}</td>
                            @foreach($days as $day)
                                <td>
                                    @php
                                        $slot = $timetableSlots->first(fn($s) =>
                                            $s->day_of_week === $day &&
                                            substr($s->start_time, 0, 5) === $time['start']
                                        );
                                    @endphp
                                    @if($slot)
                                        @php $isLab = $slot->component === 'lab'; @endphp
                                        <div class="slot-cell {{ $isLab ? 'lab-slot' : '' }}">
                                            <div class="slot-course">
                                                {{ $slot->courseSection->course->name ?? 'N/A' }}
                                            </div>
                                            <div>
                                                <span class="slot-room-wrap {{ $isLab ? 'lab-room' : '' }}">
                                                    &#127968; {{ $slot->room->room_number ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="slot-teacher">
                                                &#128100; {{ $slot->teacher->name ?? 'N/A' }}
                                            </div>
                                            @if($slot->component)
                                                <span class="slot-badge badge-{{ $slot->component }}">
                                                    {{ ucfirst($slot->component) }}
                                                </span>
                                            @endif
                                            <a href="{{ route('hod.timetable.slot.edit', $slot->id) }}"
                                               class="slot-edit-link" title="Edit slot">&#9998;</a>
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
                <p>This timetable has no scheduled slots.</p>
            </div>
        @endif
    </div>

@else
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:60px 20px;text-align:center;color:#94a3b8;">
        <div style="font-size:3rem;margin-bottom:12px;">&#128197;</div>
        @if($timetables->count() > 0)
            <p>Select a timetable from the dropdown above to view it.</p>
        @else
            <p>No timetables exist for your department yet.</p>
            <a href="{{ route('hod.generate-timetable') }}"
               style="display:inline-block;margin-top:12px;padding:10px 22px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">
                Generate First Timetable
            </a>
        @endif
    </div>
@endif

@endsection
