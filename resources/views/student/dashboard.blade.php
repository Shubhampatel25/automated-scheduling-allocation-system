@extends('layouts.dashboard')

@section('title', 'Student Dashboard')
@section('role-label', 'Student Panel')
@section('page-title', 'Student Dashboard')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('student.dashboard') }}" class="nav-link active">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Academics</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128100;</span> My Teachers
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
            <p>View your class schedule, enrolled courses, and academic information.</p>
        </div>
        <a href="#" class="banner-btn">View Timetable</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount ?? 0 }}</h3>
                <p>Enrolled Courses</p>
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
            <div class="stat-icon purple">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount ?? 0 }}</h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $totalCredits ?? 0 }}</h3>
                <p>Total Credits</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Enrolled Courses -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>My Courses</h3>
                <span class="badge badge-primary">{{ $courseCount ?? 0 }} Enrolled</span>
            </div>
            <div class="card-body">
                @if(isset($enrolledCourses) && count($enrolledCourses) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Teacher</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolledCourses as $course)
                                <tr>
                                    <td>{{ $course->code ?? 'N/A' }}</td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>{{ $course->teacher->name ?? 'TBA' }}</td>
                                    <td>{{ $course->credits ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses enrolled yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Today's Classes</h3>
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
                                    <p>{{ $slot->time_slot ?? '' }} | Room: {{ $slot->room->name ?? 'TBA' }} | Prof: {{ $slot->teacher->name ?? 'TBA' }}</p>
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
                            <p>No timetable generated yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Student Profile -->
        <div class="dashboard-card">
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
                        <div class="info-label">Semester</div>
                        <div class="info-value">{{ $semester ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
