<div class="nav-section-title">Main</div>
<a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
    <span class="icon">&#9776;</span> Dashboard
</a>

<div class="nav-section-title">Academics</div>
<a href="{{ route('student.register-courses') }}" class="nav-link {{ request()->routeIs('student.register-courses') ? 'active' : '' }}">
    <span class="icon">&#43;</span> Register Courses
</a>
<a href="{{ route('student.my-courses') }}" class="nav-link {{ request()->routeIs('student.my-courses') ? 'active' : '' }}">
    <span class="icon">&#128218;</span> My Courses
</a>
<a href="{{ route('student.timetable') }}" class="nav-link {{ request()->routeIs('student.timetable') ? 'active' : '' }}">
    <span class="icon">&#128197;</span> My Timetable
</a>
<a href="{{ route('student.today') }}" class="nav-link {{ request()->routeIs('student.today') ? 'active' : '' }}">
    <span class="icon">&#128336;</span> Today's Classes
</a>

<div class="nav-section-title">Finance</div>
<a href="{{ route('student.fee-payment') }}" class="nav-link {{ request()->routeIs('student.fee-payment') ? 'active' : '' }}">
    <span class="icon">&#128178;</span> Fee Payment
</a>

<div class="nav-section-title">Account</div>
<a href="{{ route('student.profile') }}" class="nav-link {{ request()->routeIs('student.profile') ? 'active' : '' }}">
    <span class="icon">&#128100;</span> My Profile
</a>
