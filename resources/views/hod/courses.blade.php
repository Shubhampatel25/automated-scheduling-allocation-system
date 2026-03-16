@extends('layouts.dashboard')

@section('title', 'Department Courses')
@section('role-label', 'Head of Department')
@section('page-title', 'Department Courses')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { margin-bottom: 24px; }
.page-header h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.breadcrumb { font-size: .85rem; color: #64748b; }
.breadcrumb a { color: #6366f1; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 20px 24px; display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-icon.purple { background: #ede9fe; }
.stat-icon.green  { background: #dcfce7; }
.stat-icon.orange { background: #fff7ed; }
.stat-icon.blue   { background: #dbeafe; }
.stat-details h3 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.stat-details p  { font-size: .82rem; color: #64748b; margin: 2px 0 0; }

.card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
.card-top h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.card-controls { display: flex; align-items: center; gap: 10px; }
.card-controls select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; color: #374151; background: #fff; }
.search-wrap { position: relative; }
.search-wrap input { padding: 7px 12px 7px 32px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; color: #374151; width: 220px; }
.search-wrap input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.search-wrap .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; }
.showing { font-size: .82rem; color: #64748b; white-space: nowrap; }

.data-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.data-table th { text-align: left; padding: 10px 14px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
.data-table td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.data-table tbody tr:hover { background: #f8fafc; }
.course-name { font-weight: 600; color: #1e293b; }
.assigned   { color: #16a34a; font-weight: 600; }
.unassigned { color: #dc2626; font-style: italic; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 10px; }

@media(max-width: 900px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 560px) { .stats-row { grid-template-columns: 1fr; } }
</style>

<!-- Page Header -->
<div class="page-header">
    <h2>Department Courses</h2>
    <div class="breadcrumb">
        <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Department Courses
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon purple">&#128218;</div>
        <div class="stat-details">
            <h3>{{ $totalCourses }}</h3>
            <p>Total Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-details">
            <h3>{{ $assignedCourses }}</h3>
            <p>Assigned Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9888;</div>
        <div class="stat-details">
            <h3>{{ $unassignedCourses }}</h3>
            <p>Unassigned Courses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">&#128101;</div>
        <div class="stat-details">
            <h3>{{ $totalStudents }}</h3>
            <p>Total Students Enrolled</p>
        </div>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-top">
        <h3>Department Courses</h3>
        <div class="card-controls">
            <span class="showing" id="showing-count">Showing {{ $totalCourses }} of {{ $totalCourses }}</span>
            <select id="filter-select" onchange="filterTable()">
                <option value="all">All Courses</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
            <div class="search-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="search-input" placeholder="Search by code or name..." oninput="filterTable()">
            </div>
        </div>
    </div>

    @if($courses->count() > 0)
        <table class="data-table" id="courses-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Assigned To</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $course)
                    @php
                        $firstAssignment = $course->sections->flatMap(fn($s) => $s->assignments)->first();
                        $teacherName = $firstAssignment?->teacher?->name;
                    @endphp
                    <tr data-assigned="{{ $teacherName ? 'assigned' : 'unassigned' }}">
                        <td>{{ $course->code }}</td>
                        <td class="course-name">{{ $course->name }}</td>
                        <td>
                            @if($teacherName)
                                <span class="assigned">{{ $teacherName }}</span>
                            @else
                                <span class="unassigned">Unassigned</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128218;</div>
            <p>No courses found for this department.</p>
        </div>
    @endif
</div>

<script>
function filterTable() {
    const filter = document.getElementById('filter-select').value;
    const search = document.getElementById('search-input').value.toLowerCase();
    const rows   = document.querySelectorAll('#courses-table tbody tr');
    let visible  = 0;

    rows.forEach(row => {
        const assigned = row.dataset.assigned;
        const text     = row.textContent.toLowerCase();
        const matchFilter = filter === 'all' || assigned === filter;
        const matchSearch = text.includes(search);
        const show = matchFilter && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('showing-count').textContent =
        `Showing ${visible} of {{ $totalCourses }}`;
}
</script>

@endsection
