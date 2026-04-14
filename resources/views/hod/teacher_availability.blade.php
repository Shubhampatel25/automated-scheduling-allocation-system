@extends('layouts.dashboard')

@section('title', 'Teacher Availability')
@section('role-label', 'Head of Department')
@section('page-title', 'Teacher Availability')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

@php
    $days  = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    $times = [
        ['label' => '08:00 – 09:30', 'start' => '08:00', 'end' => '09:30'],
        ['label' => '09:40 – 11:10', 'start' => '09:40', 'end' => '11:10'],
        ['label' => '11:20 – 12:50', 'start' => '11:20', 'end' => '12:50'],
        ['label' => '13:50 – 15:20', 'start' => '13:50', 'end' => '15:20'],
        ['label' => '15:30 – 17:00', 'start' => '15:30', 'end' => '17:00'],
    ];

    /*
     * Returns 'available' | 'booked' | 'unavailable' for a given teacher/day/slot.
     * available  = teacher declared this window as available AND not booked
     * booked     = active timetable slot exists in this window (regardless of declared avail)
     * unavailable = no declaration found
     */
    function slotStatus($teacherId, $day, $slotStart, $slotEnd, $availabilities, $bookedSlots): string
    {
        $booked = ($bookedSlots[$teacherId] ?? collect())->first(function ($s) use ($day, $slotStart) {
            return $s->day_of_week === $day
                && substr($s->start_time, 0, 5) === $slotStart;
        });
        if ($booked) return 'booked';

        $avail = ($availabilities[$teacherId] ?? collect())->first(function ($a) use ($day, $slotStart, $slotEnd) {
            return $a->day_of_week === $day
                && substr($a->start_time, 0, 5) <= $slotStart
                && substr($a->end_time, 0, 5)   >= $slotEnd;
        });
        return $avail ? 'available' : 'unavailable';
    }
@endphp

<style>
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.breadcrumb { font-size: .85rem; color: #64748b; }
.breadcrumb a { color: #6366f1; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

/* Legend */
.legend { display: flex; gap: 18px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
.legend-item { display: flex; align-items: center; gap: 7px; font-size: .82rem; color: #334155; }
.legend-dot { width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; }
.legend-dot.available   { background: #dcfce7; border: 1.5px solid #16a34a; }
.legend-dot.booked      { background: #fee2e2; border: 1.5px solid #dc2626; }
.legend-dot.unavailable { background: #f1f5f9; border: 1.5px solid #cbd5e1; }

/* Summary stat cards */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 18px 20px; display: flex; align-items: center; gap: 12px; }
.stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.stat-icon.green  { background: #dcfce7; }
.stat-icon.red    { background: #fee2e2; }
.stat-icon.blue   { background: #dbeafe; }
.stat-card h3 { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0; }
.stat-card p  { font-size: .76rem; color: #64748b; margin: 2px 0 0; }

/* Teacher grid card */
.teacher-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: 20px; overflow: hidden; }
.teacher-card-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px; }
.teacher-name { font-weight: 700; color: #1e293b; font-size: .97rem; }
.teacher-meta { font-size: .8rem; color: #64748b; }
.teacher-badges { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.badge-free   { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 700; background: #dcfce7; color: #16a34a; }
.badge-busy   { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 700; background: #fee2e2; color: #dc2626; }
.badge-noavail { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 700; background: #f1f5f9; color: #64748b; }

/* Availability grid table */
.avail-table-wrap { overflow-x: auto; padding: 0 20px 20px; }
table.avail-table { width: 100%; border-collapse: collapse; min-width: 640px; font-size: .8rem; }
table.avail-table th { padding: 9px 10px; background: #f8fafc; color: #64748b; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #e2e8f0; text-align: center; }
table.avail-table th.time-col { text-align: left; width: 115px; background: #f8fafc; }
table.avail-table td { padding: 6px 8px; border: 1px solid #f1f5f9; text-align: center; height: 54px; vertical-align: middle; }
table.avail-table td.time-lbl { background: #f8fafc; font-weight: 600; color: #475569; font-size: .76rem; text-align: left; white-space: nowrap; }

.cell-available {
    background: #dcfce7;
    border-radius: 7px;
    color: #166534;
    font-size: .72rem;
    font-weight: 700;
    padding: 4px 6px;
    display: flex; align-items: center; justify-content: center; gap: 4px;
}
.cell-booked {
    background: #fee2e2;
    border-radius: 7px;
    color: #991b1b;
    font-size: .72rem;
    font-weight: 700;
    padding: 4px 6px;
    display: flex; align-items: center; justify-content: center; gap: 4px;
}
.cell-unavailable {
    color: #cbd5e1;
    font-size: .78rem;
}

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state .empty-icon { font-size: 2.8rem; margin-bottom: 10px; }

/* Search */
.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.search-wrap { position: relative; }
.search-wrap input { padding: 8px 12px 8px 32px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; width: 240px; color: #374151; }
.search-wrap input:focus { outline: none; border-color: #6366f1; }
.search-wrap .si { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

@media(max-width: 600px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
</style>

<!-- Page Header -->
<div class="page-header">
    <h2>&#128197; Teacher Availability</h2>
    <div class="breadcrumb">
        <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Teacher Availability
    </div>
</div>

@if($teachers->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">&#128100;</div>
        <p>No active faculty members found in your department.</p>
    </div>
@else

@php
    // Pre-compute summary totals
    $totalAvail  = 0;
    $totalBooked = 0;
    $totalSlots  = count($teachers) * count($days) * count($times);
    foreach ($teachers as $t) {
        foreach ($days as $d) {
            foreach ($times as $tm) {
                $s = slotStatus($t->id, $d, $tm['start'], $tm['end'], $availabilities, $bookedSlots);
                if ($s === 'available') $totalAvail++;
                if ($s === 'booked')    $totalBooked++;
            }
        }
    }
@endphp

<!-- Summary -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon blue">&#128100;</div>
        <div><h3>{{ $teachers->count() }}</h3><p>Active Teachers</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div><h3>{{ $totalAvail }}</h3><p>Free Slots (declared)</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#128337;</div>
        <div><h3>{{ $totalBooked }}</h3><p>Booked Slots (active)</p></div>
    </div>
</div>

<!-- Legend -->
<div class="legend">
    <strong style="font-size:.82rem;color:#374151;">Legend:</strong>
    <div class="legend-item"><div class="legend-dot available"></div> Available (declared free)</div>
    <div class="legend-item"><div class="legend-dot booked"></div> Booked (active timetable slot)</div>
    <div class="legend-item"><div class="legend-dot unavailable"></div> Not declared</div>
</div>

<!-- Search -->
<div class="toolbar">
    <div class="search-wrap">
        <span class="si">&#128269;</span>
        <input type="text" id="teacherSearch" placeholder="Search teacher..." oninput="filterTeachers()">
    </div>
</div>

<!-- Per-teacher availability grids -->
<div id="teacherGrids">
@foreach($teachers as $teacher)
    @php
        $tAvail  = ($availabilities[$teacher->id] ?? collect());
        $tBooked = ($bookedSlots[$teacher->id] ?? collect());
        $freeCount   = 0;
        $bookedCount = 0;
        foreach ($days as $d) {
            foreach ($times as $tm) {
                $s = slotStatus($teacher->id, $d, $tm['start'], $tm['end'], $availabilities, $bookedSlots);
                if ($s === 'available') $freeCount++;
                if ($s === 'booked')    $bookedCount++;
            }
        }
    @endphp
    <div class="teacher-card" data-name="{{ strtolower($teacher->name) }}">
        <div class="teacher-card-header">
            <div>
                <div class="teacher-name">{{ $teacher->name }}</div>
                <div class="teacher-meta">{{ $teacher->employee_id ?? 'N/A' }} &middot; {{ $teacher->email ?? '' }}</div>
            </div>
            <div class="teacher-badges">
                @if($freeCount > 0)
                    <span class="badge-free">{{ $freeCount }} free slot{{ $freeCount !== 1 ? 's' : '' }}</span>
                @endif
                @if($bookedCount > 0)
                    <span class="badge-busy">{{ $bookedCount }} booked</span>
                @endif
                @if($tAvail->isEmpty())
                    <span class="badge-noavail">No availability declared</span>
                @endif
            </div>
        </div>

        <div class="avail-table-wrap">
            <table class="avail-table">
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
                            <td class="time-lbl">{{ $time['label'] }}</td>
                            @foreach($days as $day)
                                @php
                                    $status = slotStatus($teacher->id, $day, $time['start'], $time['end'], $availabilities, $bookedSlots);
                                    // Find booked slot detail for tooltip
                                    $bookedDetail = $tBooked->first(fn($s) =>
                                        $s->day_of_week === $day &&
                                        substr($s->start_time, 0, 5) === $time['start']
                                    );
                                @endphp
                                <td>
                                    @if($status === 'booked')
                                        <div class="cell-booked" title="{{ $bookedDetail?->courseSection?->course?->code ?? 'Booked' }}">
                                            &#128280;
                                            @if($bookedDetail?->courseSection?->course?->code)
                                                <span>{{ $bookedDetail->courseSection->course->code }}</span>
                                            @else
                                                <span>Busy</span>
                                            @endif
                                        </div>
                                    @elseif($status === 'available')
                                        <div class="cell-available">&#10003; Free</div>
                                    @else
                                        <span class="cell-unavailable">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
</div>

@endif

<script>
function filterTeachers() {
    const q = document.getElementById('teacherSearch').value.toLowerCase().trim();
    document.querySelectorAll('#teacherGrids .teacher-card').forEach(card => {
        card.style.display = (!q || card.dataset.name.includes(q)) ? '' : 'none';
    });
}
</script>

@endsection
