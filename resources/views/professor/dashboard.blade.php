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
    <a href="#" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128336;</span> Set Availability
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> View Schedule
    </a>

    <div class="nav-section-title">Account</div>
    <a href="#" class="nav-link">
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
        <a href="#" class="banner-btn">Set Availability</a>
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

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Assigned Courses -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>My Courses</h3>
                <span class="badge badge-primary">{{ $courseCount ?? 0 }} Courses</span>
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
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Today's Schedule</h3>
                <span class="badge badge-success">{{ now()->format('l') }}</span>
            </div>
            <div class="card-body">
                @if(isset($todaySchedule) && count($todaySchedule) > 0)
                    <ul class="activity-list">
                        @foreach($todaySchedule as $slot)
                            <li class="activity-item">
                                <div class="activity-dot blue"></div>
                                <div class="activity-content">
                                    <h4>{{ $slot->course->name ?? 'N/A' }}</h4>
                                    <p>{{ $slot->time_slot ?? '' }} | Room: {{ $slot->room->name ?? 'TBA' }}</p>
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

        <!-- Weekly Timetable -->
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h3>My Weekly Timetable</h3>
                <span class="badge badge-warning">Full View</span>
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
                                @foreach(['09:00 - 10:00', '10:00 - 11:00', '11:00 - 12:00', '12:00 - 01:00', '02:00 - 03:00', '03:00 - 04:00'] as $time)
                                    <tr>
                                        <td class="time-col">{{ $time }}</td>
                                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                            <td>
                                                @php
                                                    $slot = collect($weeklySchedule)->first(fn($s) => ($s->day ?? '') === $day && ($s->time_slot ?? '') === $time);
                                                @endphp
                                                @if($slot)
                                                    <div class="slot">
                                                        <div class="course-name">{{ $slot->course->name ?? '' }}</div>
                                                        <div class="room-name">{{ $slot->room->name ?? '' }}</div>
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
    </div>
@endsection
