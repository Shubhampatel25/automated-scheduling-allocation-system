@extends('layouts.dashboard')

@section('title', 'Department Timetable')
@section('role-label', 'Head of Department')
@section('page-title', 'Department Timetable')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }

.selector-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px 24px; margin-bottom:24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.selector-card label { font-size:.88rem; font-weight:600; color:#374151; white-space:nowrap; }
.selector-card select { padding:9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; color:#1e293b; background:#fff; min-width:280px; }
.selector-card select:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.badge-active   { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.76rem; font-weight:600; background:#dcfce7; color:#16a34a; }
.badge-semester { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.76rem; font-weight:600; background:#ede9fe; color:#6366f1; }

/* Summary row */
.summary-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:24px; }
.sum-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:18px 20px; display:flex; align-items:center; gap:12px; }
.sum-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.sum-icon.purple { background:#ede9fe; }
.sum-icon.blue   { background:#dbeafe; }
.sum-icon.green  { background:#dcfce7; }
.sum-icon.orange { background:#fff7ed; }
.sum-card h3 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.sum-card p  { font-size:.78rem; color:#64748b; margin:2px 0 0; }

/* Timetable grid */
.tt-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:24px; overflow-x:auto; }
.tt-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.tt-card-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0; }

table.timetable { width:100%; border-collapse:collapse; min-width:700px; font-size:.83rem; }
table.timetable th { padding:10px 8px; background:#6366f1; color:#fff; text-align:center; font-weight:600; font-size:.8rem; }
table.timetable th.time-col { background:#4f46e5; width:110px; }
table.timetable td { padding:6px; border:1px solid #e2e8f0; vertical-align:top; text-align:center; height:80px; }
table.timetable td.time-label { background:#f8fafc; font-weight:600; color:#475569; font-size:.78rem; text-align:center; vertical-align:middle; border:1px solid #e2e8f0; }

.slot-cell { background:#ede9fe; border-radius:8px; padding:7px 8px; height:100%; display:flex; flex-direction:column; justify-content:center; gap:3px; border-left:3px solid #6366f1; }
.slot-cell.lab-cell  { background:#fef3c7; border-left-color:#d97706; }
.slot-cell .course-name { font-weight:700; color:#1e293b; font-size:.79rem; line-height:1.25; }
.slot-cell .teacher  { font-size:.72rem; color:#4f46e5; }
.slot-cell .room     { display:inline-flex; align-items:center; gap:3px; background:#6366f1; color:#fff; border-radius:4px; padding:2px 6px; font-size:.7rem; font-weight:700; width:fit-content; }
.slot-cell.lab-cell .room { background:#d97706; }
.slot-cell .comp-tag { display:inline-block; padding:1px 6px; border-radius:8px; font-size:.68rem; font-weight:700; background:rgba(99,102,241,.15); color:#4f46e5; margin-top:1px; }
.slot-cell.lab-cell .comp-tag { background:rgba(217,119,6,.15); color:#92400e; }

.empty-slot { color:#cbd5e1; font-size:.75rem; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:3rem; margin-bottom:12px; }

.no-timetable-box { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:60px 20px; text-align:center; color:#94a3b8; }
.no-timetable-box .icon { font-size:3rem; margin-bottom:12px; }
.no-timetable-box p { margin:0 0 16px; }
.btn-go { display:inline-block; padding:10px 22px; background:#6366f1; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem; }
.btn-go:hover { background:#4f46e5; }

@media(max-width:600px) { .summary-row { grid-template-columns:repeat(2,1fr); } }
</style>

<!-- Header -->
<div class="page-header">
    <h2>&#128197; Department Timetable</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

@if($activeTimetables->isEmpty())
    <div class="no-timetable-box">
        <div class="icon">&#128197;</div>
        <p>No active timetable found for <strong>{{ $department->name ?? 'your department' }}</strong>.</p>
        <a href="{{ route('hod.generate-timetable') }}" class="btn-go">Generate Timetable</a>
    </div>
@else

    <!-- Semester Selector -->
    <div class="selector-card">
        <label for="tt-select">&#128197; Select Semester Timetable:</label>
        <select id="tt-select" onchange="location.href='{{ route('hod.department-timetable') }}?timetable_id='+this.value">
            @foreach($activeTimetables as $tt)
                <option value="{{ $tt->id }}" {{ $selectedTimetable?->id == $tt->id ? 'selected' : '' }}>
                    Semester {{ $tt->semester }} — {{ $tt->term }} {{ $tt->year }}
                    ({{ $tt->slot_count }} slots)
                </option>
            @endforeach
        </select>
        @if($selectedTimetable)
            <span class="badge-active">&#9989; Active</span>
            <span class="badge-semester">Semester {{ $selectedTimetable->semester }}</span>
        @endif
    </div>

    @if($selectedTimetable && $timetableSlots->isNotEmpty())
        @php
            $days  = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
            $times = [
                ['label' => '08:00 – 09:30', 'start' => '08:00'],
                ['label' => '09:40 – 11:10', 'start' => '09:40'],
                ['label' => '11:20 – 12:50', 'start' => '11:20'],
                ['label' => '13:50 – 15:20', 'start' => '13:50'],
                ['label' => '15:30 – 17:00', 'start' => '15:30'],
            ];

            // index slots by day + start
            $slotMap = [];
            foreach ($timetableSlots as $slot) {
                $key = $slot->day_of_week . '|' . substr($slot->start_time, 0, 5);
                $slotMap[$key][] = $slot;
            }

            $uniqueTeachers = $timetableSlots->pluck('teacher_id')->unique()->count();
            $uniqueRooms    = $timetableSlots->pluck('room_id')->unique()->count();
            $uniqueCourses  = $timetableSlots->pluck('courseSection.course_id')->unique()->count();
        @endphp

        <!-- Summary -->
        <div class="summary-row">
            <div class="sum-card">
                <div class="sum-icon purple">&#128197;</div>
                <div><h3>{{ $timetableSlots->count() }}</h3><p>Total Slots</p></div>
            </div>
            <div class="sum-card">
                <div class="sum-icon blue">&#128218;</div>
                <div><h3>{{ $uniqueCourses }}</h3><p>Courses Scheduled</p></div>
            </div>
            <div class="sum-card">
                <div class="sum-icon green">&#128100;</div>
                <div><h3>{{ $uniqueTeachers }}</h3><p>Teachers Active</p></div>
            </div>
            <div class="sum-card">
                <div class="sum-icon orange">&#127968;</div>
                <div><h3>{{ $uniqueRooms }}</h3><p>Rooms Used</p></div>
            </div>
        </div>

        <!-- Timetable Grid -->
        <div class="tt-card">
            <div class="tt-card-header">
                <h3>Weekly Schedule — {{ $department->name ?? '' }} / Semester {{ $selectedTimetable->semester }}</h3>
                <span style="font-size:.82rem;color:#64748b;">{{ $selectedTimetable->term }} {{ $selectedTimetable->year }}</span>
            </div>

            <table class="timetable">
                <thead>
                    <tr>
                        <th class="time-col">Time</th>
                        @foreach($days as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($times as $time)
                        <tr>
                            <td class="time-label">{{ $time['label'] }}</td>
                            @foreach($days as $day)
                                @php
                                    $key   = $day . '|' . $time['start'];
                                    $slots = $slotMap[$key] ?? [];
                                @endphp
                                <td>
                                    @forelse($slots as $s)
                                        <div class="slot-cell {{ $s->component === 'lab' ? 'lab-cell' : '' }}">
                                            <div class="course-name">
                                                {{ $s->courseSection?->course?->code ?? 'N/A' }}
                                            </div>
                                            <div class="teacher">{{ $s->teacher?->name ?? '—' }}</div>
                                            <div class="room">{{ $s->room?->room_number ?? '—' }}</div>
                                            <span class="comp-tag">{{ ucfirst($s->component ?? '') }}</span>
                                        </div>
                                    @empty
                                        <span class="empty-slot">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @elseif($selectedTimetable)
        <div class="tt-card">
            <div class="empty-state">
                <div class="empty-icon">&#128197;</div>
                <p>No slots scheduled for this timetable yet.</p>
            </div>
        </div>
    @endif

@endif

@endsection
