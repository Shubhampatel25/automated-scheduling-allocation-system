@extends('layouts.dashboard')

@section('title', 'Department Conflicts')
@section('role-label', 'Head of Department')
@section('page-title', 'Conflicts')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.breadcrumb { font-size: .85rem; color: #64748b; }
.breadcrumb a { color: #6366f1; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

/* Stat cards */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; text-align: center; }
.stat-card .num { font-size: 2rem; font-weight: 800; margin-bottom: 4px; }
.stat-card .label { font-size: .82rem; color: #64748b; }
.num-red    { color: #dc2626; }
.num-green  { color: #16a34a; }
.num-orange { color: #d97706; }
.num-blue   { color: #6366f1; }

/* Filter card */
.filter-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 20px 24px; margin-bottom: 20px; }
.filter-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.filter-card-top h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.filter-card-top span { font-size: .82rem; color: #94a3b8; }
.search-label { font-size: .82rem; font-weight: 700; color: #64748b; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
.filter-row { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 5px; }
.filter-group label { font-size: .8rem; font-weight: 600; color: #374151; }
.filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; color: #374151; background: #fff; }
.filter-group select:focus, .filter-group input:focus { outline: none; border-color: #6366f1; }
.filter-group input { width: 220px; }
.btn-apply { padding: 8px 20px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: .87rem; font-weight: 600; cursor: pointer; }
.btn-apply:hover { background: #4f46e5; }
.btn-reset { padding: 8px 18px; background: #f1f5f9; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-size: .87rem; font-weight: 600; cursor: pointer; }
.btn-reset:hover { background: #e2e8f0; }

/* Conflicts table */
.card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card-top h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.records-found { font-size: .82rem; color: #64748b; }

.data-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.data-table th { text-align: left; padding: 10px 12px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: .73rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
.data-table td { padding: 13px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
.data-table tbody tr:hover { background: #f8fafc; }

.type-badge { display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: .75rem; font-weight: 700; }
.type-room    { background: #fee2e2; color: #dc2626; }
.type-teacher { background: #fef3c7; color: #92400e; }
.type-student { background: #e0f2fe; color: #0369a1; }
.type-other   { background: #f1f5f9; color: #64748b; }

.against { font-size: .75rem; color: #94a3b8; margin-top: 3px; }

.status-resolved   { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; background: #dcfce7; color: #16a34a; }
.status-unresolved { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; background: #fee2e2; color: #dc2626; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 10px; }

@media(max-width: 900px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 500px) { .stats-row { grid-template-columns: 1fr; } }
</style>

<!-- Page Header -->
<div class="page-header">
    <h2>Department Conflicts</h2>
    <div class="breadcrumb">
        <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Conflicts
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="num num-red">{{ $unresolved }}</div>
        <div class="label">Unresolved</div>
    </div>
    <div class="stat-card">
        <div class="num num-green">{{ $resolved }}</div>
        <div class="label">Resolved</div>
    </div>
    <div class="stat-card">
        <div class="num num-orange">{{ $teacherConflicts }}</div>
        <div class="label">Teacher Conflicts</div>
    </div>
    <div class="stat-card">
        <div class="num num-blue">{{ $roomConflicts }}</div>
        <div class="label">Room Conflicts</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <div class="filter-card-top">
        <h3>{{ $department->name ?? 'Department' }}</h3>
        <span>Conflict monitoring</span>
    </div>
    <div class="search-label">&#128269; Search</div>
    <div class="filter-row">
        <div class="filter-group">
            <label>Status</label>
            <select id="filter-status">
                <option value="all">All Status</option>
                <option value="unresolved">Unresolved</option>
                <option value="resolved">Resolved</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Conflict Type</label>
            <select id="filter-type">
                <option value="all">All Types</option>
                <option value="room">Room Overlap</option>
                <option value="teacher">Teacher Overlap</option>
                <option value="student">Student Overlap</option>
            </select>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <input type="text" id="filter-search" placeholder="Search description...">
        </div>
        <button class="btn-apply" onclick="applyFilters()">Apply Filter</button>
        <button class="btn-reset" onclick="resetFilters()">Reset</button>
    </div>
</div>

<!-- Conflict List -->
<div class="card">
    <div class="card-top">
        <h3>Conflict List</h3>
        <span class="records-found" id="records-count">{{ $conflicts->count() }} records found</span>
    </div>

    @if($conflicts->count() > 0)
        <table class="data-table" id="conflicts-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Room</th>
                    <th>Day &amp; Time</th>
                    <th>Detected</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conflicts as $index => $conflict)
                    @php
                        $ctype   = $conflict->conflict_type ?? '';
                        $slot1   = $conflict->slot1;
                        $slot2   = $conflict->slot2;

                        if (str_contains($ctype, 'room'))    { $typeClass = 'type-room';    $typeLabel = 'Room Overlap'; }
                        elseif (str_contains($ctype, 'teacher')) { $typeClass = 'type-teacher'; $typeLabel = 'Teacher Overlap'; }
                        elseif (str_contains($ctype, 'student')) { $typeClass = 'type-student'; $typeLabel = 'Student Overlap'; }
                        else { $typeClass = 'type-other'; $typeLabel = ucfirst(str_replace('_', ' ', $ctype)); }
                    @endphp
                    <tr data-status="{{ $conflict->status }}" data-type="{{ strtolower(str_contains($ctype,'room') ? 'room' : (str_contains($ctype,'teacher') ? 'teacher' : (str_contains($ctype,'student') ? 'student' : 'other'))) }}">
                        <td>{{ $index + 1 }}</td>
                        <td><span class="type-badge {{ $typeClass }}">{{ $typeLabel }}</span></td>
                        <td>{{ $conflict->description ?? 'N/A' }}</td>
                        <td>
                            {{ $slot1?->courseSection?->course?->name ?? 'N/A' }}
                            @if($slot2?->courseSection?->course?->name)
                                <div class="against">Against: {{ $slot2->courseSection->course->name }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $slot1?->teacher?->name ?? 'N/A' }}
                            @if($slot2?->teacher?->name)
                                <div class="against">Against: {{ $slot2->teacher->name }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $slot1?->room?->room_number ?? 'N/A' }}
                            @if($slot2?->room?->room_number)
                                <div class="against">Against: {{ $slot2->room->room_number }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $slot1?->day_of_week ?? 'N/A' }}<br>
                            <span style="font-size:.78rem;color:#64748b;">
                                {{ $slot1 ? substr($slot1->start_time,0,5).' - '.substr($slot1->end_time,0,5) : '' }}
                            </span>
                        </td>
                        <td>{{ $conflict->detected_at ? $conflict->detected_at->format('d M Y') : 'N/A' }}</td>
                        <td>
                            @if(($conflict->status ?? '') === 'resolved')
                                <span class="status-resolved">Resolved</span>
                            @else
                                <span class="status-unresolved">Unresolved</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#9888;</div>
            <p>No conflicts found for this department.</p>
        </div>
    @endif
</div>

<script>
const totalRecords = {{ $conflicts->count() }};

function applyFilters() {
    const status = document.getElementById('filter-status').value;
    const type   = document.getElementById('filter-type').value;
    const search = document.getElementById('filter-search').value.toLowerCase();
    const rows   = document.querySelectorAll('#conflicts-table tbody tr');
    let visible  = 0;

    rows.forEach(row => {
        const matchStatus = status === 'all' || row.dataset.status === status;
        const matchType   = type   === 'all' || row.dataset.type   === type;
        const matchSearch = !search || row.textContent.toLowerCase().includes(search);
        const show = matchStatus && matchType && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('records-count').textContent = visible + ' records found';
}

function resetFilters() {
    document.getElementById('filter-status').value = 'all';
    document.getElementById('filter-type').value   = 'all';
    document.getElementById('filter-search').value = '';
    document.querySelectorAll('#conflicts-table tbody tr').forEach(r => r.style.display = '');
    document.getElementById('records-count').textContent = totalRecords + ' records found';
}
</script>

@endsection
