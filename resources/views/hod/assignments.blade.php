@extends('layouts.dashboard')

@section('title', 'Course Assignments')
@section('role-label', 'Head of Department')
@section('page-title', 'Course Assignments')

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

.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 20px 24px; display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-icon.purple { background: #ede9fe; }
.stat-icon.blue   { background: #dbeafe; }
.stat-icon.green  { background: #dcfce7; }
.stat-details h3 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.stat-details p  { font-size: .82rem; color: #64748b; margin: 2px 0 0; }

.card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 24px; }
.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
.card-top h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
.card-controls { display: flex; align-items: center; gap: 10px; }
.showing { font-size: .82rem; color: #64748b; white-space: nowrap; }
.search-wrap { position: relative; }
.search-wrap input { padding: 7px 12px 7px 32px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; color: #374151; width: 240px; }
.search-wrap input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.search-wrap .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; }

.data-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.data-table th { text-align: left; padding: 10px 14px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
.data-table td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.data-table tbody tr:hover { background: #f8fafc; }
.course-name { font-weight: 600; color: #1e293b; }

.badge-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600; background: #dcfce7; color: #16a34a; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 10px; }

.assign-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: #6366f1; color: #fff; border-radius: 8px; font-size: .87rem; font-weight: 600; text-decoration: none; }
.assign-btn:hover { background: #4f46e5; }

@media(max-width: 700px) { .stats-row { grid-template-columns: 1fr; } }
</style>

<!-- Page Header -->
<div class="page-header">
    <h2>Course Assignments</h2>
    <div class="breadcrumb">
        <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Course Assignments
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon purple">&#128221;</div>
        <div class="stat-details">
            <h3>{{ $totalAssignments }}</h3>
            <p>Total Assignments</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">&#128100;</div>
        <div class="stat-details">
            <h3>{{ $teachersAssigned }}</h3>
            <p>Teachers Assigned</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#128218;</div>
        <div class="stat-details">
            <h3>{{ $coursesCovered }}</h3>
            <p>Courses Covered</p>
        </div>
    </div>
</div>

<!-- Assignments Table -->
<div class="card">
    <div class="card-top">
        <h3>Course Assignments</h3>
        <div class="card-controls">
            <span class="showing" id="showing-count">Showing {{ $totalAssignments }} of {{ $totalAssignments }}</span>
            <div class="search-wrap">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="search-input" placeholder="Search course or teacher..." oninput="filterTable()">
            </div>
            <a href="{{ route('hod.assign-course') }}" class="assign-btn">&#43; Assign Course</a>
        </div>
    </div>

    @if($assignments->count() > 0)
        <table class="data-table" id="assignments-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Teacher</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignments as $assignment)
                    <tr>
                        <td class="course-name">{{ $assignment->courseSection->course->name ?? 'N/A' }}</td>
                        <td>{{ $assignment->courseSection->section_number ? 'Section ' . $assignment->courseSection->section_number : 'N/A' }}</td>
                        <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
                        <td><span class="badge-status">Assigned</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128221;</div>
            <p>No course assignments found. <a href="{{ route('hod.assign-course') }}" style="color:#6366f1;">Assign a course</a> to get started.</p>
        </div>
    @endif
</div>

<script>
function filterTable() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const rows   = document.querySelectorAll('#assignments-table tbody tr');
    let visible  = 0;

    rows.forEach(row => {
        const match = row.textContent.toLowerCase().includes(search);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    document.getElementById('showing-count').textContent =
        `Showing ${visible} of {{ $totalAssignments }}`;
}
</script>

@endsection
