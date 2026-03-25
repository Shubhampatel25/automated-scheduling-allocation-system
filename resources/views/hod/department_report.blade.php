@extends('layouts.dashboard')

@section('title', 'Department Report')
@section('role-label', 'Head of Department')
@section('page-title', 'Department Report')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }

/* Dept banner */
.dept-banner { background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:14px; padding:28px 32px; margin-bottom:24px; color:#fff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; }
.dept-banner h3 { font-size:1.5rem; font-weight:800; margin:0 0 4px; }
.dept-banner p  { font-size:.9rem; opacity:.85; margin:0; }
.dept-banner .meta { display:flex; gap:20px; flex-wrap:wrap; }
.dept-banner .meta-item { display:flex; flex-direction:column; align-items:center; }
.dept-banner .meta-item span:first-child { font-size:1.6rem; font-weight:800; }
.dept-banner .meta-item span:last-child  { font-size:.75rem; opacity:.8; text-transform:uppercase; letter-spacing:.05em; }

/* Section heading */
.section-title { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px; }

/* Stat grid */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:24px; }
.stat-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px; display:flex; align-items:center; gap:14px; }
.stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.si-purple { background:#ede9fe; }
.si-green  { background:#dcfce7; }
.si-blue   { background:#dbeafe; }
.si-orange { background:#fff7ed; }
.si-red    { background:#fee2e2; }
.stat-card h3 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.stat-card p  { font-size:.78rem; color:#64748b; margin:2px 0 0; }

/* Cards row */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
.card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:24px; }

/* Bar chart */
.bar-chart { display:flex; flex-direction:column; gap:10px; }
.bar-row { display:flex; align-items:center; gap:10px; }
.bar-label { font-size:.82rem; color:#374151; font-weight:600; min-width:80px; }
.bar-track { flex:1; background:#f1f5f9; border-radius:6px; height:20px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:6px; background:#6366f1; transition:width .4s; }
.bar-fill.green  { background:#22c55e; }
.bar-fill.orange { background:#f59e0b; }
.bar-count { font-size:.82rem; color:#64748b; min-width:28px; text-align:right; font-weight:600; }

/* Donut-style badges */
.type-list { display:flex; flex-wrap:wrap; gap:10px; }
.type-chip { background:#f1f5f9; border-radius:10px; padding:10px 16px; display:flex; flex-direction:column; align-items:center; min-width:90px; }
.type-chip .num  { font-size:1.4rem; font-weight:800; color:#6366f1; }
.type-chip .lbl  { font-size:.72rem; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }

/* Faculty table */
.data-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.data-table th { text-align:left; padding:9px 12px; background:#f8fafc; color:#64748b; font-weight:600; font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
.data-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#334155; }
.data-table tbody tr:hover { background:#f8fafc; }
.badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:.75rem; font-weight:600; }
.badge-active   { background:#dcfce7; color:#16a34a; }
.badge-inactive { background:#fee2e2; color:#dc2626; }

/* Timetable status */
.tt-status-list { display:flex; flex-direction:column; gap:10px; }
.tt-row { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#f8fafc; border-radius:8px; font-size:.85rem; }
.tt-row .tt-name  { font-weight:600; color:#1e293b; }
.tt-row .tt-meta  { font-size:.78rem; color:#64748b; margin-top:2px; }
.status-pill { padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
.status-active   { background:#dcfce7; color:#16a34a; }
.status-draft    { background:#fef9c3; color:#854d0e; }
.status-archived { background:#f1f5f9; color:#64748b; }

/* Day schedule */
.day-bars { display:flex; align-items:flex-end; gap:8px; height:80px; margin-top:10px; }
.day-bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; }
.day-bar { width:100%; background:#6366f1; border-radius:4px 4px 0 0; min-height:4px; transition:height .3s; }
.day-bar-label { font-size:.7rem; color:#64748b; font-weight:600; }
.day-bar-count { font-size:.7rem; color:#6366f1; font-weight:700; }

.empty-state { text-align:center; padding:30px 20px; color:#94a3b8; }

@media(max-width:900px) { .two-col { grid-template-columns:1fr; } }
@media(max-width:560px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
</style>

<!-- Header -->
<div class="page-header">
    <h2>&#128196; Department Report</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

<!-- Department Banner -->
<div class="dept-banner">
    <div>
        <h3>{{ $department->name ?? 'Department' }}</h3>
        <p>Code: {{ $department->code ?? 'N/A' }} &nbsp;&bull;&nbsp; Report generated: {{ now()->format('d M Y, H:i') }}</p>
    </div>
    <div class="meta">
        <div class="meta-item">
            <span>{{ $totalCourses }}</span>
            <span>Courses</span>
        </div>
        <div class="meta-item">
            <span>{{ $totalTeachers }}</span>
            <span>Faculty</span>
        </div>
        <div class="meta-item">
            <span>{{ $totalTimetables }}</span>
            <span>Timetables</span>
        </div>
        <div class="meta-item">
            <span>{{ $totalConflicts }}</span>
            <span>Conflicts</span>
        </div>
    </div>
</div>

<!-- ── COURSES SECTION ───────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon si-purple">&#128218;</div>
        <div><h3>{{ $totalCourses }}</h3><p>Total Courses</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-green">&#9989;</div>
        <div><h3>{{ $activeCourses }}</h3><p>Active Courses</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-blue">&#128221;</div>
        <div><h3>{{ $assignedCourses }}</h3><p>Assigned Courses</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-orange">&#9888;</div>
        <div><h3>{{ $totalCourses - $assignedCourses }}</h3><p>Unassigned</p></div>
    </div>
</div>

<div class="two-col">
    <!-- Courses by Semester -->
    <div class="card">
        <div class="section-title">&#128218; Courses by Semester</div>
        @if($coursesBySemester->isNotEmpty())
            @php $maxSem = $coursesBySemester->max() ?: 1; @endphp
            <div class="bar-chart">
                @foreach($coursesBySemester as $sem => $count)
                    <div class="bar-row">
                        <div class="bar-label">Sem {{ $sem }}</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ round(($count/$maxSem)*100) }}%"></div>
                        </div>
                        <div class="bar-count">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">No courses found.</div>
        @endif
    </div>

    <!-- Courses by Type -->
    <div class="card">
        <div class="section-title">&#128196; Courses by Type</div>
        @if($coursesByType->isNotEmpty())
            <div class="type-list">
                @foreach($coursesByType as $type => $count)
                    <div class="type-chip">
                        <span class="num">{{ $count }}</span>
                        <span class="lbl">{{ ucfirst(str_replace('_',' ',$type)) }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">No courses found.</div>
        @endif
    </div>
</div>

<!-- ── FACULTY SECTION ───────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon si-blue">&#128100;</div>
        <div><h3>{{ $totalTeachers }}</h3><p>Total Faculty</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-green">&#9989;</div>
        <div><h3>{{ $activeTeachers }}</h3><p>Active Faculty</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-purple">&#128218;</div>
        <div><h3>{{ $assignedTeachers }}</h3><p>Teaching Courses</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-orange">&#9200;</div>
        <div><h3>{{ $totalHours }}h</h3><p>Weekly Hours (Active TT)</p></div>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="section-title">&#128100; Faculty Overview</div>
    @if($teachers->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Employee ID</th>
                    <th>Courses Assigned</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teachers as $t)
                    <tr>
                        <td style="font-weight:600;color:#1e293b;">{{ $t->name }}</td>
                        <td>{{ $t->employee_id ?? 'N/A' }}</td>
                        <td>{{ $t->assignments_count }}</td>
                        <td>
                            <span class="badge {{ $t->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                {{ ucfirst($t->status ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">No faculty members found.</div>
    @endif
</div>

<!-- ── TIMETABLE SECTION ─────────────────────────────────── -->
<div class="two-col">
    <!-- Timetable Status List -->
    <div class="card">
        <div class="section-title">&#128197; Timetable History</div>
        @if($timetables->isNotEmpty())
            <div class="tt-status-list">
                @foreach($timetables as $tt)
                    <div class="tt-row">
                        <div>
                            <div class="tt-name">Semester {{ $tt->semester }} — {{ $tt->term }} {{ $tt->year }}</div>
                            <div class="tt-meta">
                                {{ $tt->slot_count }} slots &bull;
                                {{ $tt->conflict_count > 0 ? $tt->conflict_count.' conflicts' : 'No conflicts' }} &bull;
                                {{ $tt->generated_at ? $tt->generated_at->format('d M Y') : 'N/A' }}
                            </div>
                        </div>
                        <span class="status-pill status-{{ $tt->status }}">{{ ucfirst($tt->status) }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">No timetables generated yet.</div>
        @endif
    </div>

    <!-- Slots by Day (active timetable) -->
    <div class="card">
        <div class="section-title">&#128200; Classes per Day (Active Timetable)</div>
        @if($slotsByDay->isNotEmpty())
            @php
                $days    = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                $maxSlot = $slotsByDay->max() ?: 1;
                $shortDay = ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed','Thursday'=>'Thu','Friday'=>'Fri'];
            @endphp
            <div class="day-bars">
                @foreach($days as $day)
                    @php $cnt = $slotsByDay->get($day, 0); @endphp
                    <div class="day-bar-wrap">
                        <div class="day-bar-count">{{ $cnt }}</div>
                        <div class="day-bar" style="height:{{ $maxSlot > 0 ? round(($cnt/$maxSlot)*60) : 4 }}px;"></div>
                        <div class="day-bar-label">{{ $shortDay[$day] }}</div>
                    </div>
                @endforeach
            </div>
            <p style="font-size:.78rem;color:#94a3b8;margin:12px 0 0;">
                Total: {{ $activeSlots->count() }} scheduled slots &bull; {{ $totalHours }}h / week
            </p>
        @elseif($activeTimetable)
            <div class="empty-state">Active timetable has no slots yet.</div>
        @else
            <div class="empty-state">No active timetable. <a href="{{ route('hod.generate-timetable') }}" style="color:#6366f1;">Generate one</a>.</div>
        @endif
    </div>
</div>

@endsection
