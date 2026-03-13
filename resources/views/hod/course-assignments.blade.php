@extends('layouts.dashboard')

@section('title', 'Course Assignments')
@section('role-label', 'Head of Department')
@section('page-title', 'Course Assignments')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('hod.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Department</div>
    <a href="#section-faculty" class="nav-link">
        <span class="icon">&#128100;</span> Faculty Members
    </a>
    <a href="{{ route('hod.courses') }}" class="nav-link">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="{{ route('hod.assignments') }}" class="nav-link active">
        <span class="icon">&#128221;</span> Course Assignments
    </a>
    <a href="#section-conflicts" class="nav-link">
        <span class="icon">&#9888;</span> Conflicts
    </a>

    <div class="nav-section-title">Scheduling</div>
    <a href="#" class="nav-link">
        <span class="icon">&#128197;</span> Generate Timetable
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> Department Timetable
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128203;</span> Approve Schedule
    </a>

    <div class="nav-section-title">Reports</div>
    <a href="#section-workload" class="nav-link">
        <span class="icon">&#128202;</span> Faculty Workload
    </a>
    <a href="#" class="nav-link">
        <span class="icon">&#128196;</span> Department Report
    </a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
.status-assigned {
    background: #d1fae5;
    color: #065f46;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}
</style>
@endpush

@section('content')
    <div class="manage-header">
        <div class="manage-title">
            <h2>Course Assignments</h2>
            <div class="breadcrumb-nav">
                <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Course Assignments
            </div>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon blue">&#128221;</div>
            <div class="stat-details">
                <h3>{{ $assignments->total() }}</h3>
                <p>Total Assignments</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount }}</h3>
                <p>Teachers Assigned</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount }}</h3>
                <p>Courses Covered</p>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h3>Course Assignments</h3>
            <div class="table-toolbar">
                <div class="rows-label">
                    Showing {{ $assignments->count() }} of {{ $assignments->total() }}
                </div>
                <form method="GET" action="{{ route('hod.assignments') }}" id="searchForm" style="display:contents">
                    <div class="search-wrap">
                        <span class="si">&#128269;</span>
                        <input type="text" name="search" id="searchInput" placeholder="Search course or teacher..." value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)






                     <tr>
    <td><strong>{{ $assignment->courseSection->course->name ?? 'N/A' }}</strong></td>
    <td>N/A</td>
    <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
    <td>
        <span class="status-assigned">Assigned</span>
    </td>
</tr>





                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:32px;color:#9ca3af;">
                                @if(request('search'))
                                    No assignments found matching "{{ request('search') }}"
                                @else
                                    No course assignments in your department yet
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($assignments->hasPages())
                <div style="margin-top:16px">{{ $assignments->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
var searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            document.getElementById('searchForm').submit();
        }, 400);
    });
}
</script>
@endpush