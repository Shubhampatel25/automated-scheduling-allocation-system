@extends('layouts.dashboard')

@section('title', 'Professor Dashboard')
@section('role-label', 'Professor Panel')
@section('page-title', 'Professor Dashboard')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('professor.dashboard') }}" class="nav-link active">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Teaching</div>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#section-today" class="nav-link">
        <span class="icon">&#128197;</span> Today's Schedule
    </a>
    <a href="#section-students" class="nav-link">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="#section-availability" class="nav-link">
        <span class="icon">&#128336;</span> Set Availability
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> View Schedule
    </a>

    <div class="nav-section-title">Account</div>
    <a href="#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>View your teaching schedule, assigned courses, and manage your availability.</p>
        </div>
        <a href="#section-availability" class="banner-btn">Set Availability</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount ?? 0 }}</h3>
                <p>Assigned Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128197;</div>
            <div class="stat-details">
                <h3>{{ $classesPerWeek ?? 0 }}</h3>
                <p>Classes / Week</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128101;</div>
            <div class="stat-details">
                <h3>{{ $studentCount ?? 0 }}</h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $hoursPerWeek ?? 0 }}</h3>
                <p>Hours / Week</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            My Courses
        </a>
        <a href="#section-timetable" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="#section-availability" class="action-btn">
            <div class="action-icon">&#128336;</div>
            Set Availability
        </a>
        <a href="#section-students" class="action-btn">
            <div class="action-icon">&#128101;</div>
            My Students
        </a>
        <a href="#section-profile" class="action-btn">
            <div class="action-icon">&#128100;</div>
            My Profile
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Assigned Courses -->
        <div class="dashboard-card" id="section-courses">
            <div class="card-header">
                <h3>My Courses</h3>
                <a href="#section-courses" class="badge badge-primary">{{ $courseCount ?? 0 }} Courses</a>
            </div>
            <div class="card-body">
                @if(isset($assignedCourses) && count($assignedCourses) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedCourses as $course)
                                <tr>
                                    <td>{{ $course->code ?? 'N/A' }}</td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>{{ $course->credits ?? 'N/A' }}</td>
                                    <td>{{ $course->students_count ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses assigned yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="dashboard-card" id="section-today">
            <div class="card-header">
                <h3>Today's Schedule</h3>
                <a href="#section-today" class="badge badge-success">{{ now()->format('l') }}</a>
            </div>
            <div class="card-body">
                @if(isset($todaySchedule) && count($todaySchedule) > 0)
                    <ul class="activity-list">
                        @foreach($todaySchedule as $slot)
                            <li class="activity-item">
                                <div class="activity-dot blue"></div>
                                <div class="activity-content">
                                    <h4>{{ $slot->courseSection->course->name ?? 'N/A' }}</h4>
                                    <p>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }} | Room: {{ $slot->room->room_number ?? 'TBA' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128197;</div>
                        <p>No classes scheduled for today</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- My Students -->
        <div class="dashboard-card full-width" id="section-students">
            <div class="card-header">
                <h3>My Students</h3>
                <span class="badge badge-primary">{{ $studentCount ?? 0 }} Students</span>
            </div>
            <div class="card-body">
                @if(isset($myStudents) && count($myStudents) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myStudents as $student)
                                <tr>
                                    <td>{{ $student->name ?? 'N/A' }}</td>
                                    <td>{{ $student->course->name ?? 'N/A' }}</td>
                                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128101;</div>
                        <p>No students found</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Set Availability -->
        <div class="dashboard-card full-width" id="section-availability">
            <div class="card-header">
                <h3>My Availability</h3>
                <span class="badge badge-success">This Week</span>
            </div>
            <div class="card-body">
                @if(isset($availability) && count($availability) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availability as $slot)
                                <tr>
                                    <td>{{ $slot->day_of_week ?? 'N/A' }}</td>
                                    <td>{{ $slot->start_time ?? 'N/A' }}</td>
                                    <td>{{ $slot->end_time ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status {{ ($slot->is_available ?? false) ? 'status-active' : 'status-inactive' }}">
                                            {{ ($slot->is_available ?? false) ? 'Available' : 'Unavailable' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128336;</div>
                        <p>No availability set yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Weekly Timetable -->
        <div class="dashboard-card full-width" id="section-timetable">
            <div class="card-header">
                <h3>My Weekly Timetable</h3>
                <a href="#section-timetable" class="badge badge-warning">Full View</a>
            </div>
            <div class="card-body">
                <div class="timetable-container">
                    @if(isset($weeklySchedule) && count($weeklySchedule) > 0)
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
                                @foreach(['08:00 - 09:30', '09:30 - 11:00', '11:00 - 12:30', '13:00 - 14:30', '14:30 - 16:00'] as $time)
                                    <tr>
                                        <td class="time-col">{{ $time }}</td>
                                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                            <td>
                                                @php
                                                    [$startStr] = explode(' - ', $time);
                                                    $slot = collect($weeklySchedule)->first(fn($s) => ($s->day_of_week ?? '') === $day && substr($s->start_time, 0, 5) === $startStr);
                                                @endphp
                                                @if($slot)
                                                    <div class="slot">
                                                        <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                                        <div class="room-name">{{ $slot->room->room_number ?? '' }}</div>
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
                            <p>No timetable generated yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- My Profile -->
        <div class="dashboard-card" id="section-profile">
            <div class="card-header">
                <h3>My Profile</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ Auth::user()->username }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value">{{ $department ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div class="info-value">Professor</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
