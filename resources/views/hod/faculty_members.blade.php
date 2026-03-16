@extends('layouts.dashboard')

@section('title', 'Faculty Members')
@section('role-label', 'Head of Department')
@section('page-title', 'Faculty Members')

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

/* Overview + Snapshot */
.overview-row { display: grid; grid-template-columns: 1fr 300px; gap: 16px; margin-bottom: 20px; }
.overview-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.overview-label { display: inline-block; background: #ede9fe; color: #6366f1; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 12px; }
.overview-card h3 { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 0 0 6px; }
.overview-card p  { font-size: .85rem; color: #64748b; margin: 0 0 20px; }
.mini-stats { display: flex; gap: 12px; }
.mini-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; flex: 1; }
.mini-stat h4 { font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: 0; }
.mini-stat p  { font-size: .72rem; font-weight: 600; color: #64748b; margin: 3px 0 0; text-transform: uppercase; letter-spacing: .05em; }

.snapshot-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.snapshot-label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
.snapshot-card h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 20px; }
.snapshot-row { margin-bottom: 14px; }
.snapshot-row .label { font-size: .75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.snapshot-row .value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }

/* Stat cards */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 20px 22px; display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-icon.blue   { background: #dbeafe; }
.stat-icon.green  { background: #dcfce7; }
.stat-icon.purple { background: #ede9fe; }
.stat-icon.orange { background: #fff7ed; }
.stat-details h3 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0; }
.stat-details p  { font-size: .8rem; color: #64748b; margin: 2px 0 0; }

/* Directory card */
.card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
.card-top h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.card-controls { display: flex; align-items: center; gap: 8px; }
.search-wrap { position: relative; }
.search-wrap input { padding: 7px 12px 7px 32px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; width: 210px; color: #374151; }
.search-wrap input:focus { outline: none; border-color: #6366f1; }
.search-wrap .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .82rem; }
.filter-label { font-size: .85rem; color: #374151; font-weight: 600; }
.filter-select { padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; color: #374151; }
.apply-btn { padding: 7px 14px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: .85rem; font-weight: 600; cursor: pointer; }
.apply-btn:hover { background: #4f46e5; }

.data-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.data-table th { text-align: left; padding: 10px 14px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
.data-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
.data-table tbody tr:hover { background: #f8fafc; }

.faculty-name { font-weight: 600; color: #1e293b; }
.course-tag { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: .75rem; font-weight: 600; background: #ede9fe; color: #6366f1; margin: 2px 2px 2px 0; }
.schedule-load { font-size: .85rem; color: #334155; }
.schedule-load .hours { font-size: .78rem; color: #64748b; }
.badge-active   { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600; background: #dcfce7; color: #16a34a; }
.badge-inactive { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600; background: #fee2e2; color: #dc2626; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 10px; }

@media(max-width: 1000px) { .overview-row { grid-template-columns: 1fr; } }
@media(max-width: 800px)  { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 500px)  { .stats-row { grid-template-columns: 1fr; } }
</style>

<!-- Page Header -->
<div class="page-header">
    <h2>Department Faculty</h2>
    <div class="breadcrumb">
        <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Faculty Members
    </div>
</div>

<!-- Overview + Snapshot -->
<div class="overview-row">
    <div class="overview-card">
        <div class="overview-label">Department Overview</div>
        <h3>{{ $department->name ?? 'Department' }}</h3>
        <p>Review faculty availability, assigned workload, and active teaching coverage for your department from one screen.</p>
        <div class="mini-stats">
            <div class="mini-stat">
                <h4>{{ $facultyMembersCount }}</h4>
                <p>Faculty Members</p>
            </div>
            <div class="mini-stat">
                <h4>{{ $courseOwners }}</h4>
                <p>Course Owners</p>
            </div>
            <div class="mini-stat">
                <h4>{{ $activeRightNow }}</h4>
                <p>Active Right Now</p>
            </div>
        </div>
    </div>

    <div class="snapshot-card">
        <div class="snapshot-label">Department Snapshot</div>
        <h3>{{ $totalFaculty }} Faculty Members</h3>
        <div class="snapshot-row">
            <div class="label">Assigned Faculty</div>
            <div class="value">{{ $assignedFaculty }}</div>
        </div>
        <div class="snapshot-row">
            <div class="label">Active Weekly Hours</div>
            <div class="value">{{ $activeWeeklyHours }}</div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon blue">&#128100;</div>
        <div class="stat-details">
            <h3>{{ $totalFaculty }}</h3>
            <p>Total Faculty</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-details">
            <h3>{{ $activeMembers }}</h3>
            <p>Active Members</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#128218;</div>
        <div class="stat-details">
            <h3>{{ $handlingCourses }}</h3>
            <p>Handling Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9200;</div>
        <div class="stat-details">
            <h3>{{ $totalWeeklyHours }}</h3>
            <p>Total Weekly Hours</p>
        </div>
    </div>
</div>

<!-- Faculty Directory -->
<div class="card">
    <div class="card-top">
        <h3>Faculty Directory</h3>
        <div class="card-controls">
            <div class="search-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="search-input" placeholder="Search name, email, ID..." oninput="filterTable()">
            </div>
            <span class="filter-label">Status</span>
            <select id="status-filter" class="filter-select">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button class="apply-btn" onclick="filterTable()">Apply</button>
        </div>
    </div>

    @if($teachers->count() > 0)
        <table class="data-table" id="faculty-table">
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Employee ID</th>
                    <th>Contact</th>
                    <th>Assigned Courses</th>
                    <th>Schedule Load</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teachers as $teacher)
                    <tr data-status="{{ $teacher->status }}">
                        <td class="faculty-name">{{ $teacher->name }}</td>
                        <td>{{ $teacher->employee_id ?? 'N/A' }}</td>
                        <td>{{ $teacher->email ?? 'N/A' }}</td>
                        <td>
                            @php
                                $courses = $teacher->courseAssignments
                                    ->map(fn($a) => $a->courseSection->course->name ?? null)
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp
                            @if($courses->count())
                                <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;">{{ $courses->count() }} courses</div>
                                @foreach($courses as $cname)
                                    <span class="course-tag">{{ $cname }}</span>
                                @endforeach
                            @else
                                <span style="color:#94a3b8;font-style:italic;font-size:.85rem;">No courses assigned</span>
                            @endif
                        </td>
                        <td>
                            <div class="schedule-load">{{ $teacher->classes_per_week }} classes / week</div>
                            <div class="schedule-load hours">{{ $teacher->hours_per_week }} hours scheduled</div>
                        </td>
                        <td>
                            @if(($teacher->status ?? '') === 'active')
                                <span class="badge-active">Active</span>
                            @else
                                <span class="badge-inactive">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128100;</div>
            <p>No faculty members found for this department.</p>
        </div>
    @endif
</div>

<script>
function filterTable() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const rows   = document.querySelectorAll('#faculty-table tbody tr');

    rows.forEach(row => {
        const matchSearch = row.textContent.toLowerCase().includes(search);
        const matchStatus = status === 'all' || row.dataset.status === status;
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}
</script>

@endsection
