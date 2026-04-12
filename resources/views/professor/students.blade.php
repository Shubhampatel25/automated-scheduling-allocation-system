@extends('layouts.dashboard')

@section('title', 'My Students')
@section('role-label', 'Professor Panel')
@section('page-title', 'My Students')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('professor.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Teaching</div>
    <a href="{{ route('professor.dashboard') }}#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="{{ route('professor.dashboard') }}#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="{{ route('professor.dashboard') }}#section-today" class="nav-link">
        <span class="icon">&#9200;</span> Today's Schedule
    </a>
    <a href="{{ route('professor.students') }}" class="nav-link active">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="{{ route('professor.availability') }}" class="nav-link">
        <span class="icon">&#128336;</span> Set Availability
    </a>

    <div class="nav-section-title">Account</div>
    <a href="{{ route('professor.dashboard') }}#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
/* ── Filter bar ─────────────────────────────── */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    padding: 12px 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
}
.filter-bar label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    margin-right: 4px;
    white-space: nowrap;
}
.filter-bar select {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #374151;
    background: #fff;
    cursor: pointer;
    max-width: 200px;
}
.filter-bar select:focus { outline: none; border-color: #6366f1; }
.btn-clear-filters {
    padding: 6px 14px;
    background: none;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.82rem;
    color: #6b7280;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
}
.btn-clear-filters:hover { background: #f3f4f6; }
.filter-badge {
    display: none;
    font-size: 0.72rem;
    background: #6366f1;
    color: #fff;
    border-radius: 10px;
    padding: 2px 8px;
    font-weight: 600;
}

/* ── Student cell ───────────────────────────── */
.student-cell { display: flex; flex-direction: column; gap: 1px; }
.student-cell .s-name { font-weight: 600; color: #111827; font-size: 0.88rem; }
.student-cell .s-roll { font-size: 0.74rem; color: #6b7280; }
.student-cell .s-email { font-size: 0.74rem; color: #6366f1; }

/* ── Sem cell ───────────────────────────────── */
.sem-cell { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
.sem-badge {
    font-size: 0.78rem; font-weight: 700;
    background: #f3f4f6; color: #374151;
    padding: 2px 8px; border-radius: 6px;
}

/* ── Course tags ────────────────────────────── */
.course-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.course-tag {
    font-size: 0.72rem; font-weight: 600;
    background: #eef2ff; color: #4f46e5;
    padding: 2px 8px; border-radius: 6px;
    white-space: nowrap;
}

/* ── Schedule button ────────────────────────── */
.btn-schedule {
    padding: 5px 14px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    font-family: inherit;
}
.btn-schedule:hover { background: #4f46e5; }

/* ── No-results ─────────────────────────────── */
#noResultsMsg { display:none; text-align:center; padding:24px; color:#9ca3af; font-size:0.9rem; }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="manage-header">
        <div class="manage-title">
            <h2>My Students</h2>
            <div class="breadcrumb-nav">
                <a href="{{ route('professor.dashboard') }}">Dashboard</a> &rsaquo; My Students
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <!-- Toolbar: count only -->
        <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;font-size:0.88rem;color:#6b7280;">
            Showing <strong>{{ $myStudents->count() }}</strong>
            of <strong>{{ $myStudents->total() }}</strong> students
        </div>

        <!-- Filter bar -->
        <div style="padding:12px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="filter-bar">
                {{-- Semester --}}
                <div>
                    <label for="filterSem">Semester</label>
                    <select id="filterSem" onchange="applyServerFilter()">
                        <option value="">All Semesters</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ $filterSem == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Course --}}
                <div>
                    <label for="filterCourse">Course</label>
                    <select id="filterCourse" onchange="applyServerFilter()">
                        <option value="">All Courses</option>
                        @foreach($myCourses as $course)
                            <option value="{{ $course->id }}" {{ $filterCourse == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus" onchange="applyServerFilter()">
                        <option value="">All Statuses</option>
                        <option value="active"   {{ $filterStatus === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $filterStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                @php
                    $activeFilters = collect([$filterSem, $filterCourse, $filterStatus])->filter()->count();
                @endphp
                <a href="{{ route('professor.students') }}" class="btn-clear-filters">&#10005; Clear Filters</a>
                @if($activeFilters > 0)
                    <span class="filter-badge" style="display:inline;">
                        {{ $activeFilters }} filter{{ $activeFilters > 1 ? 's' : '' }} active
                    </span>
                @endif
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table class="data-table" id="studentTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Enrolled Courses</th>
                        <th>Status</th>
                        <th>Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myStudents as $i => $student)
                        @php
                            $regs = $student->studentCourseRegistrations;
                        @endphp
                        <tr>
                            <td>{{ $myStudents->firstItem() + $i }}</td>
                            <td>
                                <div class="student-cell">
                                    <span class="s-name">{{ $student->name }}</span>
                                    <span class="s-roll">{{ $student->roll_no ?? '—' }}</span>
                                    @if($student->email)
                                        <span class="s-email">{{ $student->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $student->department->name ?? '—' }}</td>
                            <td>
                                <div class="sem-cell">
                                    <span class="sem-badge">Sem {{ $student->semester ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($regs->isNotEmpty())
                                    <div class="course-tags">
                                        @foreach($regs->take(3) as $reg)
                                            @if($reg->courseSection?->course)
                                                <span class="course-tag">{{ $reg->courseSection->course->name }}</span>
                                            @endif
                                        @endforeach
                                        @if($regs->count() > 3)
                                            <span class="course-tag" style="background:#f3f4f6;color:#6b7280;">
                                                +{{ $regs->count() - 3 }} more
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#9ca3af;font-size:0.82rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="status {{ $student->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ ucfirst($student->status ?? 'active') }}
                                </span>
                            </td>
                            <td>
                                <button class="btn-schedule"
                                        onclick="openTimetableModal(
                                            {{ $student->id }},
                                            '{{ addslashes($student->name) }}',
                                            'Semester {{ $student->semester }}',
                                            {{ (int)($student->semester ?? 0) }}
                                        )">
                                    &#128197; Schedule
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                                @if($filterSem || $filterCourse || $filterStatus)
                                    No students match the selected filters.
                                @else
                                    No students enrolled in your courses yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($myStudents->hasPages())
            <div style="padding:16px 20px;border-top:1px solid #f3f4f6;">
                {{ $myStudents->links() }}
            </div>
        @endif
    </div>

    {{-- Timetable modal (reuses shared partial, pointed at professor slots endpoint) --}}
    @include('partials.timetable-modal', ['slotRouteBase' => url('professor/students')])
@endsection

@push('scripts')
<script>
function applyServerFilter() {
    const params = new URLSearchParams(window.location.search);

    const sem    = document.getElementById('filterSem').value;
    const course = document.getElementById('filterCourse').value;
    const status = document.getElementById('filterStatus').value;

    sem    ? params.set('semester',  sem)    : params.delete('semester');
    course ? params.set('course_id', course) : params.delete('course_id');
    status ? params.set('status',    status) : params.delete('status');
    params.delete('page');

    window.location.search = params.toString();
}
</script>
@endpush
