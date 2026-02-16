@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('role-label', 'Admin Panel')
@section('page-title', 'Admin Dashboard')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link active">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="#section-teachers" class="nav-link">
        <span class="icon">&#128100;</span> Teachers
    </a>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="#section-activity" class="nav-link">
        <span class="icon">&#128196;</span> Activity Logs
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Manage the entire scheduling system from here. Monitor departments, courses, and timetables.</p>
        </div>
        <a href="#section-activity" class="banner-btn">View Activity</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#127979;</div>
            <div class="stat-details">
                <h3>{{ $departmentCount ?? 0 }}</h3>
                <p>Departments</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount ?? 0 }}</h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount ?? 0 }}</h3>
                <p>Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#127970;</div>
            <div class="stat-details">
                <h3>{{ $roomCount ?? 0 }}</h3>
                <p>Rooms</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">&#128101;</div>
            <div class="stat-details">
                <h3>{{ $studentCount ?? 0 }}</h3>
                <p>Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">&#9888;</div>
            <div class="stat-details">
                <h3>{{ $conflictCount ?? 0 }}</h3>
                <p>Conflicts</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#section-teachers" class="action-btn">
            <div class="action-icon">&#128100;</div>
            View Teachers
        </a>
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            View Courses
        </a>
        <a href="#section-activity" class="action-btn">
            <div class="action-icon">&#128196;</div>
            View Activity
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Recent Teachers -->
        <div class="dashboard-card" id="section-teachers">
            <div class="card-header">
                <h3>Recent Teachers</h3>
                <a href="#section-teachers" class="badge badge-primary">View All</a>
            </div>
            <div class="card-body">
                @if(isset($recentTeachers) && count($recentTeachers) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTeachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->name ?? 'N/A' }}</td>
                                    <td>{{ $teacher->department->name ?? 'N/A' }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128100;</div>
                        <p>No teachers added yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="dashboard-card" id="section-courses">
            <div class="card-header">
                <h3>Recent Courses</h3>
                <a href="#section-courses" class="badge badge-success">View All</a>
            </div>
            <div class="card-body">
                @if(isset($recentCourses) && count($recentCourses) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCourses as $course)
                                <tr>
                                    <td>{{ $course->code ?? 'N/A' }}</td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>{{ $course->credits ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses added yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card full-width" id="section-activity">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <a href="#section-activity" class="badge badge-warning">View All</a>
            </div>
            <div class="card-body">
                @if(isset($recentActivities) && count($recentActivities) > 0)
                    <ul class="activity-list">
                        @foreach($recentActivities as $activity)
                            <li class="activity-item">
                                <div class="activity-dot blue"></div>
                                <div class="activity-content">
                                    <h4>{{ $activity->action ?? 'Activity' }}</h4>
                                    <p>{{ $activity->created_at?->diffForHumans() ?? '' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128196;</div>
                        <p>No recent activity</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
