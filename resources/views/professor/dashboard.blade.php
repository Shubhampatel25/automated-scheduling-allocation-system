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
        <span class="icon">&#9200;</span> Today's Schedule
    </a>
    <a href="{{ route('professor.students') }}" class="nav-link">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="{{ route('professor.availability') }}" class="nav-link">
        <span class="icon">&#128336;</span> Set Availability
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
            <h2>Welcome, {{ $teacher->name ?? Auth::user()->username }}!</h2>
            <p>View your teaching schedule, assigned courses, and manage your availability.</p>
        </div>
        <a href="{{ route('professor.availability') }}" class="banner-btn">Set Availability</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount }}</h3>
                <p>Assigned Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128197;</div>
            <div class="stat-details">
                <h3>{{ $classesPerWeek }}</h3>
                <p>Classes / Week</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128101;</div>
            <div class="stat-details">
                <h3>{{ $studentCount }}</h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $hoursPerWeek }}</h3>
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
        <a href="{{ route('professor.availability') }}" class="action-btn">
            <div class="action-icon">&#128336;</div>
            Set Availability
        </a>
        <a href="{{ route('professor.students') }}" class="action-btn">
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
                <span class="badge badge-primary">{{ $courseCount }} Courses</span>
            </div>
            <div class="card-body">
                @if($assignedCourses->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Term / Year</th>
                                <th>Students Enrolled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedCourses as $course)
                                @php $sec = $course->sections->first(); @endphp
                                <tr>
                                    <td>{{ $course->code }}</td>
                                    <td>{{ $course->name }}</td>
                                    <td>{{ $course->credits }}</td>
                                    <td>
                                        @if($sec)
                                            <span style="font-size:0.75rem;background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:8px;font-weight:600;">
                                                {{ $sec->term }} {{ $sec->year }}
                                            </span>
                                        @else
                                            <span style="color:#9ca3af;">—</span>
                                        @endif
                                    </td>
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
                <span class="badge badge-success">{{ now()->format('l, d M') }}</span>
            </div>
            <div class="card-body">
                @if($todaySchedule->isNotEmpty())
                    <ul class="activity-list">
                        @foreach($todaySchedule as $slot)
                            <li class="activity-item">
                                <div class="activity-dot blue"></div>
                                <div class="activity-content">
                                    <h4>{{ $slot->courseSection->course->name ?? 'N/A' }}</h4>
                                    <p>
                                        {{ substr($slot->start_time, 0, 5) }} – {{ substr($slot->end_time, 0, 5) }}
                                        &nbsp;|&nbsp; Room: {{ $slot->room->room_number ?? 'TBA' }}
                                    </p>
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

        <!-- Students quick-link card -->
        <div class="dashboard-card" id="section-students">
            <div class="card-header">
                <h3>My Students</h3>
                <span class="badge badge-primary">{{ $studentCount }} Total</span>
            </div>
            <div class="card-body">
                @if($myStudents->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myStudents->take(5) as $student)
                                <tr>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->department->name ?? '—' }}</td>
                                    <td>
                                        <span class="status {{ $student->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                            {{ ucfirst($student->status ?? 'active') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($myStudents->count() > 5)
                        <div style="text-align:center;margin-top:12px;">
                            <a href="{{ route('professor.students') }}"
                               style="color:#6366f1;font-size:0.85rem;font-weight:500;text-decoration:none;">
                                View all {{ $studentCount }} students &rarr;
                            </a>
                        </div>
                    @endif
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128101;</div>
                        <p>No students enrolled yet</p>
                        <a href="{{ route('professor.students') }}"
                           style="color:#6366f1;font-size:0.82rem;margin-top:6px;display:inline-block;">
                            View Students page
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Availability quick-link card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>My Availability</h3>
                <span class="badge badge-success">{{ $availability->count() }} Slot(s)</span>
            </div>
            <div class="card-body">
                @if($availability->isNotEmpty())
                    <ul class="activity-list">
                        @foreach($availability->take(5) as $slot)
                            <li class="activity-item">
                                <div class="activity-dot green"></div>
                                <div class="activity-content">
                                    <h4>{{ $slot->day_of_week }}</h4>
                                    <p>{{ substr($slot->start_time, 0, 5) }} – {{ substr($slot->end_time, 0, 5) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div style="text-align:center;margin-top:12px;">
                        <a href="{{ route('professor.availability') }}"
                           style="color:#6366f1;font-size:0.85rem;font-weight:500;text-decoration:none;">
                            Manage availability &rarr;
                        </a>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128336;</div>
                        <p>No availability set yet</p>
                        <a href="{{ route('professor.availability') }}"
                           style="color:#6366f1;font-size:0.82rem;margin-top:6px;display:inline-block;">
                            Set your availability &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Weekly Timetable -->
        <div class="dashboard-card full-width" id="section-timetable">
            <div class="card-header">
                <h3>My Weekly Timetable</h3>
                <span class="badge badge-warning">{{ $classesPerWeek }} Slot(s)</span>
            </div>
            <div class="card-body">
                <div class="timetable-container">
                    @if($weeklySchedule->isNotEmpty())
                        @php
                            $timeSlots = [
                                '08:00' => '09:30',
                                '09:40' => '11:10',
                                '11:20' => '12:50',
                                '13:50' => '15:20',
                                '15:30' => '17:00',
                            ];
                        @endphp
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
                                @foreach($timeSlots as $startStr => $endStr)
                                    <tr>
                                        <td class="time-col">{{ $startStr }} – {{ $endStr }}</td>
                                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                                            <td>
                                                @php
                                                    $daySlots = $weeklySchedule->filter(
                                                        fn($s) => $s->day_of_week === $day
                                                               && substr($s->start_time, 0, 5) === $startStr
                                                    );
                                                @endphp
                                                @foreach($daySlots as $slot)
                                                    <div class="slot">
                                                        <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                                        <div class="room-name">{{ $slot->room->room_number ?? 'TBA' }}</div>
                                                        <div style="font-size:0.7rem;color:#6b7280">Sem {{ $slot->timetable->semester ?? '' }}</div>
                                                    </div>
                                                @endforeach
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
                        <div class="info-label">Full Name</div>
                        <div class="info-value">{{ $teacher->name ?? Auth::user()->username }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Employee ID</div>
                        <div class="info-value">{{ $employeeId }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $teacher->email ?? Auth::user()->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value">{{ $department }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div class="info-value">Professor</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status {{ ($teacher->status ?? '') === 'active' ? 'status-active' : 'status-inactive' }}">
                                {{ ucfirst($teacher->status ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
