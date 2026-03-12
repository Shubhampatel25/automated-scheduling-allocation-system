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
        <!-- Toolbar -->
        <div class="table-toolbar" style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
            <div class="rows-label">
                Showing <strong>{{ $myStudents->count() }}</strong>
                of <strong>{{ $myStudents->total() }}</strong> students
            </div>

            <form method="GET" action="{{ route('professor.students') }}" id="searchForm" style="display:contents;">
                <div class="search-wrap">
                    <span class="si">&#128269;</span>
                    <input type="text" name="search" id="searchInput"
                           placeholder="Search name, email, roll no..."
                           value="{{ $search ?? '' }}"
                           onkeyup="debounceSearch()">
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table class="data-table" id="studentTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myStudents as $i => $student)
                        @php
                            $firstReg   = $student->studentCourseRegistrations->first();
                            $courseName = $firstReg?->courseSection?->course?->name ?? '—';
                        @endphp
                        <tr>
                            <td>{{ $myStudents->firstItem() + $i }}</td>
                            <td>{{ $student->roll_no ?? '—' }}</td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->email }}</td>
                            <td>{{ $student->department->name ?? '—' }}</td>
                            <td>{{ $student->semester ?? '—' }}</td>
                            <td>{{ $courseName }}</td>
                            <td>
                                <span class="status {{ $student->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ ucfirst($student->status ?? 'active') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">
                                @if($search ?? false)
                                    No students found matching <strong>"{{ $search }}"</strong>
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
@endsection

@push('scripts')
<script>
    function debounceSearch() {
        clearTimeout(window._st);
        window._st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
    }
</script>
@endpush
