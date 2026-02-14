@extends('layouts.dashboard')

@section('title', 'HOD Dashboard')
@section('role-label', 'Head of Department')
@section('page-title', 'HOD Dashboard')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('hod.dashboard') }}" class="nav-link active">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Department</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128100;</span> Faculty Members
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128221;</span> Course Assignments
    </a>

    <div class="nav-section-title">Scheduling</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> Generate Timetable
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> Department Timetable
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128203;</span> Approve Schedule
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#9888;</span> Conflicts
    </a>

    <div class="nav-section-title">Reports</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128202;</span> Faculty Workload
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128196;</span> Department Report
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Manage your department's courses, faculty, and scheduling from this panel.</p>
        </div>
        <a href="#" class="banner-btn">Generate Timetable</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $facultyCount ?? 0 }}</h3>
                <p>Faculty Members</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount ?? 0 }}</h3>
                <p>Department Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128221;</div>
            <div class="stat-details">
                <h3>{{ $assignmentCount ?? 0 }}</h3>
                <p>Course Assignments</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#9888;</div>
            <div class="stat-details">
                <h3>{{ $conflictCount ?? 0 }}</h3>
                <p>Schedule Conflicts</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#" class="action-btn">
            <div class="action-icon">&#128221;</div>
            Assign Course
        </a>
        <a href="#" class="action-btn">
            <div class="action-icon">&#128197;</div>
            Generate Timetable
        </a>
        <a href="#" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="#" class="action-btn">
            <div class="action-icon">&#128202;</div>
            Faculty Workload
        </a>
        <a href="#" class="action-btn">
            <div class="action-icon">&#128203;</div>
            Approve Schedule
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Faculty Members -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Faculty Members</h3>
                <span class="badge badge-primary">View All</span>
            </div>
            <div class="card-body">
                @if(isset($facultyMembers) && count($facultyMembers) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Courses</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facultyMembers as $faculty)
                                <tr>
                                    <td>{{ $faculty->name ?? 'N/A' }}</td>
                                    <td>{{ $faculty->courses_count ?? 0 }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128100;</div>
                        <p>No faculty members found</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Department Courses -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Department Courses</h3>
                <span class="badge badge-success">View All</span>
            </div>
            <div class="card-body">
                @if(isset($departmentCourses) && count($departmentCourses) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentCourses as $course)
                                <tr>
                                    <td>{{ $course->code ?? 'N/A' }}</td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>{{ $course->teacher->name ?? 'Unassigned' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses found for this department</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Schedule Conflicts -->
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h3>Schedule Conflicts</h3>
                <span class="badge badge-danger">{{ $conflictCount ?? 0 }} Issues</span>
            </div>
            <div class="card-body">
                @if(isset($conflicts) && count($conflicts) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Day</th>
                                <th>Time Slot</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conflicts as $conflict)
                                <tr>
                                    <td>
                                        <span class="badge {{ ($conflict->type ?? '') === 'room' ? 'badge-danger' : (($conflict->type ?? '') === 'teacher' ? 'badge-warning' : 'badge-primary') }}">
                                            {{ ucfirst($conflict->type ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $conflict->description ?? 'N/A' }}</td>
                                    <td>{{ $conflict->day ?? 'N/A' }}</td>
                                    <td>{{ $conflict->time_slot ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status {{ ($conflict->status ?? '') === 'resolved' ? 'status-active' : 'status-inactive' }}">
                                            {{ ucfirst($conflict->status ?? 'Unresolved') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#9888;</div>
                        <p>No scheduling conflicts found</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Department Timetable Preview -->
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h3>Department Timetable</h3>
                <span class="badge badge-warning">Full View</span>
            </div>
            <div class="card-body">
                <div class="timetable-container">
                    @if(isset($timetableSlots) && count($timetableSlots) > 0)
                        <table class="timetable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['09:00 - 10:00', '10:00 - 11:00', '11:00 - 12:00', '12:00 - 01:00', '02:00 - 03:00', '03:00 - 04:00'] as $time)
                                    <tr>
                                        <td class="time-col">{{ $time }}</td>
                                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                            <td>
                                                @php
                                                    $slot = collect($timetableSlots)->first(fn($s) => ($s->day ?? '') === $day && ($s->time_slot ?? '') === $time);
                                                @endphp
                                                @if($slot)
                                                    <div class="slot">
                                                        <div class="course-name">{{ $slot->course->name ?? '' }}</div>
                                                        <div class="room-name">{{ $slot->room->name ?? '' }}</div>
                                                        <div class="teacher-name">{{ $slot->teacher->name ?? '' }}</div>
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
                            <p>No timetable generated yet for this department</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
