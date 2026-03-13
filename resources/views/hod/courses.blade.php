@extends('layouts.dashboard')

@section('title', 'Department Courses')
@section('role-label', 'Head of Department')
@section('page-title', 'Department Courses')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('hod.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Department</div>
    <a href="#section-faculty" class="nav-link">
        <span class="icon">&#128100;</span> Faculty Members
    </a>
    <a href="{{ route('hod.courses') }}" class="nav-link active">
        <span class="icon">&#128218;</span> Courses
    </a>
    <a href="{{ route('hod.assignments') }}" class="nav-link">
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
.assigned-teacher {
    color: #059669;
    font-weight: 500;
}
.unassigned {
    color: #dc2626;
    font-style: italic;
}
</style>
@endpush

@section('content')
    <div class="manage-header">
        <div class="manage-title">
            <h2>Department Courses</h2>
            <div class="breadcrumb-nav">
                <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Department Courses
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courses->total() }}</h3>
                <p>Total Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#10003;</div>
            <div class="stat-details">
                <h3>{{ $assignedCount }}</h3>
                <p>Assigned Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#9888;</div>
            <div class="stat-details">
                <h3>{{ $unassignedCount }}</h3>
                <p>Unassigned Courses</p>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h3>Department Courses</h3>
            <div class="table-toolbar">
                <div class="rows-label">
                    Showing {{ $courses->count() }} of {{ $courses->total() }}
                </div>
                <div class="rows-label" style="margin-left: 20px;">
                    Filter
                    <select id="statusFilter">
                        <option value="">All Courses</option>
                        <option value="assigned" @if(request('status') == 'assigned') selected @endif>Assigned Only</option>
                        <option value="unassigned" @if(request('status') == 'unassigned') selected @endif>Unassigned Only</option>
                    </select>
                </div>
                <form method="GET" action="{{ route('hod.courses') }}" id="searchForm" style="display:contents">
                    <div class="search-wrap">
                        <span class="si">&#128269;</span>
                        <input type="text" name="search" id="searchInput" placeholder="Search by code or name..." value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>CODE</th>
                        <th>COURSE NAME</th>
                        <th>ASSIGNED TO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        @php
                            $firstSection = $course->sections->first();
                            $assignment = $firstSection ? $firstSection->assignments->first() : null;
                            $teacher = $assignment ? $assignment->teacher : null;
                        @endphp
                        <tr>
                            <td><strong>{{ $course->code }}</strong></td>
                            <td>{{ $course->name }}</td>
                            <td>
                                @if($teacher)
                                    <span class="assigned-teacher">{{ $teacher->name }}</span>
                                @else
                                    <span class="unassigned">Unassigned</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;padding:32px;color:#9ca3af;">
                                @if(request('search'))
                                    No courses found matching "{{ request('search') }}"
                                @elseif(request('status'))
                                    No {{ request('status') }} courses found
                                @else
                                    No courses available in your department
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($courses->hasPages())
                <div style="margin-top:16px">{{ $courses->links() }}</div>
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

var filterSelect = document.getElementById('statusFilter');
if (filterSelect) {
    filterSelect.addEventListener('change', function() {
        var url = '{{ route("hod.courses") }}';
        var status = this.value;
        if (status) {
            window.location.href = url + '?status=' + status;
        } else {
            window.location.href = url;
        }
    });
}
</script>
@endpush