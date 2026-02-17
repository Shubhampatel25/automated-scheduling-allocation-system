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
    <a href="#section-departments" class="nav-link">
        <span class="icon">&#127979;</span> Departments
    </a>
    <a href="#section-teachers" class="nav-link">
        <span class="icon">&#128100;</span> Teachers
    </a>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="#section-rooms" class="nav-link">
        <span class="icon">&#127970;</span> Rooms
    </a>
    <a href="#section-students" class="nav-link">
        <span class="icon">&#128101;</span> Students
    </a>

    <div class="nav-section-title">Scheduling</div>
    <a href="#section-timetables" class="nav-link">
        <span class="icon">&#128203;</span> View Timetables
    </a>
    <a href="#section-conflicts" class="nav-link">
        <span class="icon">&#9888;</span> Conflicts
    </a>

    <div class="nav-section-title">System</div>
    <a href="#section-activity" class="nav-link">
        <span class="icon">&#128196;</span> Activity Logs
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#9881;</span> Settings
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Manage the entire scheduling system from here. Monitor departments, courses, and timetables.</p>
        </div>
        <a href="#section-timetables" class="banner-btn">View Timetables</a>
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
        <a href="#section-departments" class="action-btn">
            <div class="action-icon">&#127979;</div>
            Add Department
        </a>
        <a href="#section-teachers" class="action-btn">
            <div class="action-icon">&#128100;</div>
            Add Teacher
        </a>
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            Add Course
        </a>
        <a href="#section-rooms" class="action-btn">
            <div class="action-icon">&#127970;</div>
            Add Room
        </a>
        <a href="#section-students" class="action-btn">
            <div class="action-icon">&#128101;</div>
            Add Student
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Recent Teachers -->
        <div class="dashboard-card" id="section-teachers">
            <div class="card-header">
                <h3>Recent Teachers</h3>
                <span class="badge badge-primary">View All</span>
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
                <span class="badge badge-success">View All</span>
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

        <!-- Recent Rooms -->
        <div class="dashboard-card" id="section-rooms">
            <div class="card-header">
                <h3>Recent Rooms</h3>
                <span class="badge badge-warning">View All</span>
            </div>
            <div class="card-body">
                @if(isset($recentRooms) && count($recentRooms) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room No.</th>
                                <th>Building</th>
                                <th>Capacity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRooms as $room)
                                <tr>
                                    <td>{{ $room->room_number ?? 'N/A' }}</td>
                                    <td>{{ $room->building ?? 'N/A' }}</td>
                                    <td>{{ $room->capacity ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status {{ ($room->status ?? '') === 'active' ? 'status-active' : 'status-inactive' }}">
                                            {{ ucfirst($room->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#127970;</div>
                        <p>No rooms added yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Students -->
        <div class="dashboard-card" id="section-students">
            <div class="card-header">
                <h3>Recent Students</h3>
                <span class="badge badge-primary">View All</span>
            </div>
            <div class="card-body">
                @if(isset($recentStudents) && count($recentStudents) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentStudents as $student)
                                <tr>
                                    <td>{{ $student->name ?? 'N/A' }}</td>
                                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128101;</div>
                        <p>No students added yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Departments -->
        <div class="dashboard-card full-width" id="section-departments">
            <div class="card-header">
                <h3>Departments</h3>
                <span class="badge badge-primary">View All</span>
            </div>
            <div class="card-body">
                @if(isset($recentDepartments) && count($recentDepartments) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>HOD</th>
                                <th>Teachers</th>
                                <th>Courses</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentDepartments as $department)
                                <tr>
                                    <td>{{ $department->name ?? 'N/A' }}</td>
                                    <td>{{ $department->hod->teacher->name ?? 'Not Assigned' }}</td>
                                    <td>{{ $department->teachers_count ?? 0 }}</td>
                                    <td>{{ $department->courses_count ?? 0 }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#127979;</div>
                        <p>No departments added yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- View Timetables -->
        <div class="dashboard-card full-width" id="section-timetables">
            <div class="card-header">
                <h3>Generated Timetables</h3>
                <span class="badge badge-success">View All</span>
            </div>
            <div class="card-body">
                @if(isset($timetables) && count($timetables) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Term</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Generated By</th>
                                <th>Conflicts</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timetables as $timetable)
                                <tr>
                                    <td>{{ $timetable->department->name ?? 'N/A' }}</td>
                                    <td>{{ $timetable->term ?? 'N/A' }}</td>
                                    <td>{{ $timetable->year ?? 'N/A' }}</td>
                                    <td>{{ $timetable->semester ?? 'N/A' }}</td>
                                    <td>{{ $timetable->generatedByUser->username ?? 'N/A' }}</td>
                                    <td>{{ $timetable->conflicts_count ?? 0 }}</td>
                                    <td>
                                        <span class="status {{ ($timetable->status ?? '') === 'published' ? 'status-published' : 'status-pending' }}">
                                            {{ ucfirst($timetable->status ?? 'Draft') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128203;</div>
                        <p>No timetables generated yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Conflicts -->
        <div class="dashboard-card full-width" id="section-conflicts">
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
                                <th>Department</th>
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
                                    <td>{{ $conflict->timetable->department->name ?? 'N/A' }}</td>
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

        <!-- Recent Activity -->
        <div class="dashboard-card full-width" id="section-activity">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <span class="badge badge-warning">View All</span>
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
