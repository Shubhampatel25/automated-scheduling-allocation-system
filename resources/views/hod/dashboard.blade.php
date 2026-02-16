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
    <a href="#section-faculty" class="nav-link">
        <span class="icon">&#128100;</span> Faculty Members
    </a>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="#section-conflicts" class="nav-link">
        <span class="icon">&#9888;</span> Conflicts
    </a>

    <div class="nav-section-title">Scheduling</div>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> Department Timetable
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Manage your department's courses, faculty, and scheduling from this panel.</p>
        </div>
        <a href="#section-timetable" class="banner-btn">View Timetable</a>
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
        <a href="#section-faculty" class="action-btn">
            <div class="action-icon">&#128100;</div>
            View Faculty
        </a>
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            View Courses
        </a>
        <a href="#section-timetable" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="#section-conflicts" class="action-btn">
            <div class="action-icon">&#9888;</div>
            View Conflicts
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Faculty Members -->
        <div class="dashboard-card" id="section-faculty">
            <div class="card-header">
                <h3>Faculty Members</h3>
                <a href="#section-faculty" class="badge badge-primary">View All</a>
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
        <div class="dashboard-card" id="section-courses">
            <div class="card-header">
                <h3>Department Courses</h3>
                <a href="#section-courses" class="badge badge-success">View All</a>
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
                                    @php
                                        $assignment = $course->sections->flatMap(fn($s) => $s->assignments)->first();
                                    @endphp
                                    <td>{{ $assignment?->teacher?->name ?? 'Unassigned' }}</td>
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
        <div class="dashboard-card full-width" id="section-conflicts">
            <div class="card-header">
                <h3>Schedule Conflicts</h3>
                <a href="#section-conflicts" class="badge badge-danger">{{ $conflictCount ?? 0 }} Issues</a>
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
                                        @php $ctype = $conflict->conflict_type ?? ''; @endphp
                                        <span class="badge {{ str_contains($ctype, 'room') ? 'badge-danger' : (str_contains($ctype, 'teacher') ? 'badge-warning' : 'badge-primary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $ctype)) }}
                                        </span>
                                    </td>
                                    <td>{{ $conflict->description ?? 'N/A' }}</td>
                                    <td>{{ $conflict->slot1->day_of_week ?? 'N/A' }}</td>
                                    <td>{{ $conflict->slot1 ? $conflict->slot1->start_time . ' - ' . $conflict->slot1->end_time : 'N/A' }}</td>
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
        <div class="dashboard-card full-width" id="section-timetable">
            <div class="card-header">
                <h3>Department Timetable</h3>
                <a href="#section-timetable" class="badge badge-warning">Full View</a>
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
                                @foreach(['08:00 - 09:30', '09:30 - 11:00', '11:00 - 12:30', '13:00 - 14:30', '14:30 - 16:00'] as $time)
                                    <tr>
                                        <td class="time-col">{{ $time }}</td>
                                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                            <td>
                                                @php
                                                    [$startStr] = explode(' - ', $time);
                                                    $slot = collect($timetableSlots)->first(fn($s) => ($s->day_of_week ?? '') === $day && substr($s->start_time, 0, 5) === $startStr);
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
                            <p>No timetable generated yet for this department</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
