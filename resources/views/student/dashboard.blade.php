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
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#section-today" class="nav-link">
        <span class="icon">&#128197;</span> Today's Classes
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
            <p>View your class schedule, enrolled courses, and academic information.</p>
        </div>
        <a href="#section-timetable" class="banner-btn">View Timetable</a>
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
        <div class="dashboard-card" id="section-courses">
            <div class="card-header">
                <h3>My Courses</h3>
                <a href="#section-courses" class="badge badge-primary">{{ $courseCount ?? 0 }} Enrolled</a>
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
                                    @php
                                        $assignment = $course->sections->flatMap(fn($s) => $s->assignments)->first();
                                    @endphp
                                    <td>{{ $assignment?->teacher?->name ?? 'TBA' }}</td>
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
        <div class="dashboard-card" id="section-today">
            <div class="card-header">
                <h3>Today's Classes</h3>
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
                                    <p>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }} | Room: {{ $slot->room->room_number ?? 'TBA' }} | Prof: {{ $slot->teacher->name ?? 'TBA' }}</p>
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
                        <div class="info-label">Semester</div>
                        <div class="info-value">{{ $semester ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
