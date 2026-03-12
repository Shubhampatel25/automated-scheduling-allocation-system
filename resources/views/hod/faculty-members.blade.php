@extends('layouts.dashboard')

@section('title', 'Faculty Members')
@section('role-label', 'Head of Department')
@section('page-title', 'Faculty Members')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('hod.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Department</div>
    <a href="{{ route('hod.faculty.index') }}" class="nav-link active">
        <span class="icon">&#128100;</span> Faculty Members
    </a>
    <a href="{{ route('hod.dashboard') }}#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="{{ route('hod.dashboard') }}#section-assignments" class="nav-link">
        <span class="icon">&#128221;</span> Course Assignments
    </a>
    <a href="{{ route('hod.dashboard') }}#section-conflicts" class="nav-link">
        <span class="icon">&#9888;</span> Conflicts
    </a>

    <div class="nav-section-title">Scheduling</div>
    <a href="{{ route('hod.dashboard') }}#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> Department Timetable
    </a>
    <a href="{{ route('hod.dashboard') }}#section-workload" class="nav-link">
        <span class="icon">&#128202;</span> Faculty Workload
    </a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
    .faculty-summary {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    .faculty-banner {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        color: #1f2937;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f7;
    }

    .faculty-banner-tag {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 20px;
        background: rgba(102, 126, 234, 0.12);
        color: #667eea;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .faculty-banner h2 {
        font-size: 24px;
        line-height: 1.2;
        margin-bottom: 8px;
        color: #1f2937;
    }

    .faculty-banner p {
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
        max-width: 520px;
    }

    .faculty-banner-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 18px;
    }

    .faculty-banner-metric {
        min-width: 120px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #f8faff;
        border: 1px solid #e5e7eb;
    }

    .faculty-banner-metric span {
        display: block;
    }

    .faculty-banner-metric .metric-value {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #111827;
    }

    .faculty-banner-metric .metric-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        color: #6b7280;
    }

    .faculty-side-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .faculty-side-card .meta-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #9ca3af;
        margin-bottom: 6px;
    }

    .faculty-side-card .meta-value {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 18px;
    }

    .faculty-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .faculty-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 12px;
        font-weight: 500;
    }

    .faculty-empty-inline {
        color: #9ca3af;
        font-size: 13px;
    }

    @media (max-width: 992px) {
        .faculty-summary {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .faculty-banner {
            padding: 20px;
        }

        .faculty-banner h2 {
            font-size: 22px;
        }
    }
</style>
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Department Faculty</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Faculty Members
        </div>
    </div>
</div>

<div class="faculty-summary">
    <div class="faculty-banner">
        <div class="faculty-banner-top">
            <div class="faculty-banner-tag">Department Overview</div>
            <h2>{{ $department->name ?? 'Department Not Assigned' }}</h2>
            <p>Review faculty availability, assigned workload, and active teaching coverage for your department from one screen.</p>
        </div>
        <div class="faculty-banner-bottom">
            <div class="faculty-banner-metrics">
                <div class="faculty-banner-metric">
                    <span class="metric-value">{{ $facultyCount }}</span>
                    <span class="metric-label">Faculty Members</span>
                </div>
                <div class="faculty-banner-metric">
                    <span class="metric-value">{{ $assignedFacultyCount }}</span>
                    <span class="metric-label">Course Owners</span>
                </div>
                <div class="faculty-banner-metric">
                    <span class="metric-value">{{ $activeFacultyCount }}</span>
                    <span class="metric-label">Active Right Now</span>
                </div>
            </div>
        </div>
    </div>
    <div class="faculty-side-card">
        <div class="meta-label">Department Snapshot</div>
        <div class="meta-value">{{ $facultyCount }} Faculty Members</div>
        <div class="meta-label">Assigned Faculty</div>
        <div class="meta-value">{{ $assignedFacultyCount }}</div>
        <div class="meta-label">Active Weekly Hours</div>
        <div class="meta-value">{{ $totalWeeklyHours }}</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#128100;</div>
        <div class="stat-details">
            <h3>{{ $facultyCount }}</h3>
            <p>Total Faculty</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10004;</div>
        <div class="stat-details">
            <h3>{{ $activeFacultyCount }}</h3>
            <p>Active Members</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#128218;</div>
        <div class="stat-details">
            <h3>{{ $assignedFacultyCount }}</h3>
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

<div class="dashboard-card">
    <div class="card-header">
        <h3>Faculty Directory</h3>
        <div class="table-toolbar" style="margin-bottom:0; gap: 12px;">
            <form method="GET" action="{{ route('hod.faculty.index') }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div class="search-wrap">
                    <span class="si">&#128269;</span>
                    <input type="text" name="search" placeholder="Search name, email, ID..." value="{{ request('search') }}">
                </div>
                <div class="rows-label">
                    Status
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-add">Apply</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        @if($facultyMembers->count() > 0)
            <table class="data-table">
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
                    @foreach($facultyMembers as $faculty)
                        <tr>
                            <td>{{ $faculty->name }}</td>
                            <td>{{ $faculty->employee_id ?? 'N/A' }}</td>
                            <td>{{ $faculty->email }}</td>
                            <td>
                                <div>{{ $faculty->courses_count }} course{{ $faculty->courses_count === 1 ? '' : 's' }}</div>
                                @if($faculty->assigned_courses_preview->isNotEmpty())
                                    <div class="faculty-chip-list" style="margin-top:8px;">
                                        @foreach($faculty->assigned_courses_preview as $courseName)
                                            <span class="faculty-chip">{{ $courseName }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="faculty-empty-inline">No assignments yet</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $faculty->classes_per_week }} classes / week</div>
                                <div class="faculty-empty-inline">{{ number_format($faculty->hours_per_week, 1) }} hours scheduled</div>
                            </td>
                            <td>
                                <span class="status {{ $faculty->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ ucfirst($faculty->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:16px;">
                {{ $facultyMembers->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128100;</div>
                <p>No faculty members found for the current filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection
