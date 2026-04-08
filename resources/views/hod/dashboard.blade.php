@extends('layouts.dashboard')

@section('title', 'HOD Dashboard')
@section('role-label', 'Head of Department')
@section('page-title', 'HOD Dashboard')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
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
        <a href="{{ route('hod.assign-course') }}" class="action-btn">
            <div class="action-icon">&#128221;</div>
            Assign Course
        </a>
        <a href="{{ route('hod.generate-timetable') }}" class="action-btn">
            <div class="action-icon">&#9881;</div>
            Generate Timetable
        </a>
        <a href="{{ route('hod.view-timetable') }}" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="{{ route('hod.faculty-workload') }}" class="action-btn">
            <div class="action-icon">&#128202;</div>
            Faculty Workload
        </a>
        <a href="{{ route('hod.approve-schedule') }}" class="action-btn">
            <div class="action-icon">&#128203;</div>
            Approve Schedule
        </a>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Faculty Members -->
        <div class="dashboard-card" id="section-faculty">
            <div class="card-header">
                <h3>Faculty Members</h3>
                <a href="{{ route('hod.faculty-members') }}" class="badge badge-primary" style="text-decoration:none;">View All &rarr;</a>
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
                <a href="{{ route('hod.courses') }}" class="badge badge-success" style="text-decoration:none;">View All &rarr;</a>
            </div>
            <div class="card-body">
                @if(isset($departmentCourses) && count($departmentCourses) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Term / Year</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentCourses as $course)
                                @php
                                    $latestSec  = $course->sections->sortByDesc('year')->first();
                                    $assignment = $course->sections->flatMap(fn($s) => $s->assignments)->first();
                                @endphp
                                <tr>
                                    <td>{{ $course->code ?? 'N/A' }}</td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($latestSec)
                                            <span style="font-size:0.75rem;background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:8px;font-weight:600;">
                                                {{ $latestSec->term }} {{ $latestSec->year }}
                                            </span>
                                        @else
                                            <span style="color:#9ca3af;">—</span>
                                        @endif
                                    </td>
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

        <!-- Course Assignments -->
        <div class="dashboard-card full-width" id="section-assignments">
            <div class="card-header">
                <h3>Course Assignments</h3>
                <span class="badge badge-primary">{{ $assignmentCount ?? 0 }} Assigned</span>
            </div>
            <div class="card-body">
                @if(isset($courseAssignments) && count($courseAssignments) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Term / Year</th>
                                <th>Teacher</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseAssignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->courseSection->course->name ?? 'N/A' }}</td>
                                    <td>{{ $assignment->courseSection->section_name ?? 'N/A' }}</td>
                                    <td>
                                        @if($assignment->courseSection->term)
                                            <span style="font-size:0.75rem;background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:8px;font-weight:600;">
                                                {{ $assignment->courseSection->term }} {{ $assignment->courseSection->year }}
                                            </span>
                                        @else
                                            <span style="color:#9ca3af;">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status status-active">Assigned</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128221;</div>
                        <p>No course assignments found</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Faculty Workload -->
        <div class="dashboard-card full-width" id="section-workload">
            <div class="card-header">
                <h3>Faculty Workload</h3>
                <span class="badge badge-warning">This Semester</span>
            </div>
            <div class="card-body">
                @if(isset($facultyWorkload) && count($facultyWorkload) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Courses Assigned</th>
                                <th>Classes / Week</th>
                                <th>Hours / Week</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facultyWorkload as $faculty)
                                <tr>
                                    <td>{{ $faculty->name ?? 'N/A' }}</td>
                                    <td>{{ $faculty->courses_count ?? 0 }}</td>
                                    <td>{{ $faculty->classes_per_week ?? 0 }}</td>
                                    <td>{{ $faculty->hours_per_week ?? 0 }}</td>
                                    <td><span class="status status-active">Active</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128202;</div>
                        <p>No workload data available</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Schedule Conflicts -->
        <div class="dashboard-card full-width" id="section-conflicts">
            <div class="card-header">
                <h3>Schedule Conflicts</h3>
                <a href="{{ route('hod.conflicts') }}" class="badge badge-danger" style="text-decoration:none;">
                    {{ ($conflictCount ?? 0) > 0 ? ($conflictCount . ' Unresolved') : 'No Issues' }}
                </a>
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
                                        <span class="status {{ ($conflict->status ?? '') === 'resolved' ? 'status-resolved' : 'status-unresolved' }}">
                                            {{ ($conflict->status ?? '') === 'resolved' ? '&#10003; Resolved' : '&#9888; Unresolved' }}
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
                <a href="{{ route('hod.view-timetable') }}" class="badge badge-warning" style="text-decoration:none;">Full View &rarr;</a>
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
