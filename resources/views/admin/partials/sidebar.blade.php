<div class="nav-section-title">Main</div>
<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <span class="icon">&#9776;</span> Dashboard
</a>

<div class="nav-section-title">Management</div>
<a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
    <span class="icon">&#127979;</span> Manage Departments
</a>
<a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
    <span class="icon">&#128100;</span> Manage Teachers
</a>
<a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
    <span class="icon">&#128101;</span> Manage Students
</a>
<a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
    <span class="icon">&#128218;</span> Manage Courses
</a>
<a href="{{ route('admin.hods.index') }}" class="nav-link {{ request()->routeIs('admin.hods.*') ? 'active' : '' }}">
    <span class="icon">&#127979;</span> HOD Assignments
</a>
<a href="{{ route('admin.rooms.index') }}" class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}">
    <span class="icon">&#127970;</span> Manage Rooms
</a>
<a href="{{ route('admin.fee-payments.index') }}" class="nav-link {{ request()->routeIs('admin.fee-payments.*') ? 'active' : '' }}">
    <span class="icon">&#128176;</span> Fee Payments
</a>

<div class="nav-section-title">Scheduling</div>
<a href="{{ route('admin.schedule') }}" class="nav-link {{ request()->routeIs('admin.schedule') ? 'active' : '' }}">
    <span class="icon">&#128203;</span> Schedule View
</a>
<a href="{{ route('admin.conflicts') }}" class="nav-link {{ request()->routeIs('admin.conflicts') ? 'active' : '' }}">
    <span class="icon">&#9888;</span> Conflicts
</a>

<div class="nav-section-title">System</div>
<a href="{{ route('admin.excel-import.index') }}" class="nav-link {{ request()->routeIs('admin.excel-import.*') ? 'active' : '' }}">
    <span class="icon">&#128196;</span> Excel Import
</a>
<a href="{{ route('admin.activity') }}" class="nav-link {{ request()->routeIs('admin.activity') ? 'active' : '' }}">
    <span class="icon">&#128196;</span> Activity Logs
</a>
