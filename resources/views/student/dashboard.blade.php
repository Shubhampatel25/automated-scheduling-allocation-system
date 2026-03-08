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
    <a href="#section-register" class="nav-link">
        <span class="icon">&#43;</span> Register Courses
    </a>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#section-today" class="nav-link">
        <span class="icon">&#128336;</span> Today's Classes
    </a>

    <div class="nav-section-title">Account</div>
    <a href="#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@push('styles')
<style>
    .register-table th, .register-table td { padding: 10px 14px; font-size: 0.875rem; }
    .register-table { width: 100%; border-collapse: collapse; }
    .register-table thead th { background: #f3f4f6; color: #374151; font-weight: 600; }
    .register-table tbody tr:hover { background: #f9fafb; }
    .register-table td { border-bottom: 1px solid #f0f0f0; }
    .btn-register { background: #4f46e5; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
    .btn-register:hover { background: #4338ca; }
    .btn-drop { background: #ef4444; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
    .btn-drop:hover { background: #dc2626; }
    .badge-dept { background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; }
    .badge-credits { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; }
    .capacity-bar { height: 6px; background: #e5e7eb; border-radius: 4px; min-width: 80px; }
    .capacity-fill { height: 6px; border-radius: 4px; background: #4f46e5; }
    .capacity-fill.near-full { background: #f59e0b; }
    .section-search { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; width: 220px; }
    .filter-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
</style>
@endpush

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Register for courses, view your schedule, and manage your academic information.</p>
        </div>
        <a href="#section-register" class="banner-btn">Register Courses</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount }}</h3>
                <p>Enrolled Courses</p>
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
            <div class="stat-icon purple">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount }}</h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $totalCredits }}</h3>
                <p>Total Credits</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#section-register" class="action-btn">
            <div class="action-icon">&#43;</div>
            Register Courses
        </a>
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            My Courses
        </a>
        <a href="#section-timetable" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="#section-today" class="action-btn">
            <div class="action-icon">&#128336;</div>
            Today's Classes
        </a>
        <a href="#section-profile" class="action-btn">
            <div class="action-icon">&#128100;</div>
            My Profile
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- =================== REGISTER COURSES =================== -->
        <div class="dashboard-card full-width" id="section-register">
            <div class="card-header">
                <h3>Register for Courses</h3>
                <span class="badge badge-primary">{{ $availableSections->count() }} Available</span>
            </div>
            <div class="card-body">
                @if($availableSections->count() > 0)
                    <div class="filter-row">
                        <input type="text" class="section-search" id="regSearch" placeholder="Search course name or code..." onkeyup="filterRegTable()">
                    </div>
                    <table class="register-table data-table" id="regTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Section</th>
                                <th>Teacher</th>
                                <th>Seats</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableSections as $section)
                                @php
                                    $course     = $section->course;
                                    $assignment = $section->assignments->first();
                                    $teacher    = $assignment ? $assignment->teacher : null;
                                    $filled     = $section->enrolled_students;
                                    $max        = $section->max_students;
                                    $pct        = $max > 0 ? round($filled / $max * 100) : 0;
                                    $barClass   = $pct >= 90 ? 'near-full' : '';
                                @endphp
                                <tr>
                                    <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span>
                                    </td>
                                    <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                                    <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                                    <td>{{ $teacher ? $teacher->name : 'TBA' }}</td>
                                    <td>
                                        <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('student.courses.register') }}">
                                            @csrf
                                            <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                            <button type="submit" class="btn-register">Enroll</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No available courses to register for at the moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- =================== ENROLLED COURSES =================== -->
        <div class="dashboard-card full-width" id="section-courses">
            <div class="card-header">
                <h3>My Enrolled Courses</h3>
                <span class="badge badge-primary">{{ $courseCount }} Enrolled</span>
            </div>
            <div class="card-body">
                @if($enrolledCourses->count() > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Section</th>
                                <th>Teacher</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolledCourses as $course)
                                <tr>
                                    <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td><span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span></td>
                                    <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                                    <td>
                                        @if($course->sectionInfo)
                                            Sec {{ $course->sectionInfo->section_number }} &bull; {{ $course->sectionInfo->term }} {{ $course->sectionInfo->year }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $course->teacherName ?? 'TBA' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('student.courses.drop', $course->registrationId) }}"
                                              onsubmit="return confirm('Drop {{ addslashes($course->name) }}?')">
                                            @csrf
                                            <button type="submit" class="btn-drop">Drop</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses enrolled yet. Use the <a href="#section-register" style="color:#4f46e5">Register Courses</a> section above to enroll.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- =================== TODAY'S CLASSES =================== -->
        <div class="dashboard-card" id="section-today">
            <div class="card-header">
                <h3>Today's Classes</h3>
                <span class="badge badge-success">{{ now()->format('l') }}</span>
            </div>
            <div class="card-body">
                @if($todaySchedule->count() > 0)
                    <ul class="activity-list">
                        @foreach($todaySchedule as $slot)
                            <li class="activity-item">
                                <div class="activity-dot blue"></div>
                                <div class="activity-content">
                                    <h4>{{ $slot->courseSection->course->name ?? 'N/A' }}</h4>
                                    <p>
                                        {{ substr($slot->start_time, 0, 5) }} &ndash; {{ substr($slot->end_time, 0, 5) }}
                                        &bull; Room: {{ $slot->room->room_number ?? 'TBA' }}
                                        &bull; {{ $slot->teacher->name ?? 'TBA' }}
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

        <!-- =================== PROFILE =================== -->
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
                        <div class="info-value">{{ $department }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Semester</div>
                        <div class="info-value">{{ $semester }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Enrolled Credits</div>
                        <div class="info-value">{{ $totalCredits }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== WEEKLY TIMETABLE =================== -->
        <div class="dashboard-card full-width" id="section-timetable">
            <div class="card-header">
                <h3>My Weekly Timetable</h3>
                <span class="badge badge-warning">Week View</span>
            </div>
            <div class="card-body">
                <div class="timetable-container">
                    @if($weeklySchedule->count() > 0)
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
                                                    $slot = $weeklySchedule->first(fn($s) => ($s->day_of_week ?? '') === $day && substr($s->start_time, 0, 5) === $startStr);
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
                            <p>No timetable generated yet. Enroll in courses first.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
function filterRegTable() {
    const val = document.getElementById('regSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#regTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
    });
}
</script>
@endpush
