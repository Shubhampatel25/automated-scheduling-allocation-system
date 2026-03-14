<div class="nav-section-title">Main</div>
<a href="{{ route('hod.dashboard') }}" class="nav-link {{ request()->routeIs('hod.dashboard') ? 'active' : '' }}">
    <span class="icon">&#9776;</span> Dashboard
</a>

<div class="nav-section-title">Department</div>
<a href="{{ route('hod.dashboard') }}#section-faculty" class="nav-link">
    <span class="icon">&#128100;</span> Faculty Members
</a>
<a href="{{ route('hod.dashboard') }}#section-courses" class="nav-link">
    <span class="icon">&#128218;</span> Courses
</a>
<a href="{{ route('hod.dashboard') }}#section-assignments" class="nav-link">
    <span class="icon">&#128221;</span> Course Assignments
</a>
<a href="{{ route('hod.assign-course') }}" class="nav-link {{ request()->routeIs('hod.assign-course') ? 'active' : '' }}">
    <span class="icon">&#128196;</span> Assign Course
</a>
<a href="{{ route('hod.dashboard') }}#section-conflicts" class="nav-link">
    <span class="icon">&#9888;</span> Conflicts
</a>

<div class="nav-section-title">Scheduling</div>
<a href="{{ route('hod.generate-timetable') }}" class="nav-link {{ request()->routeIs('hod.generate-timetable') ? 'active' : '' }}">
    <span class="icon">&#128197;</span> Generate Timetable
</a>
<a href="{{ route('hod.view-timetable') }}" class="nav-link {{ request()->routeIs('hod.view-timetable') ? 'active' : '' }}">
    <span class="icon">&#128197;</span> View Timetable
</a>
<a href="{{ route('hod.dashboard') }}#section-timetable" class="nav-link">
    <span class="icon">&#128197;</span> Department Timetable
</a>
<a href="{{ route('hod.approve-schedule') }}" class="nav-link {{ request()->routeIs('hod.approve-schedule') ? 'active' : '' }}">
    <span class="icon">&#128203;</span> Approve Schedule
</a>

<div class="nav-section-title">Reports</div>
<a href="{{ route('hod.faculty-workload') }}" class="nav-link {{ request()->routeIs('hod.faculty-workload') ? 'active' : '' }}">
    <span class="icon">&#128202;</span> Faculty Workload
</a>
<a href="#" class="nav-link">
    <span class="icon">&#128196;</span> Department Report
</a>