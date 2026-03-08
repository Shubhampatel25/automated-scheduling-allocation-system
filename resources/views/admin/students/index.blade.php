@extends('layouts.dashboard')

@section('title', 'Manage Students')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Students')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Student</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Student
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Student</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Student List</h3>
        <div class="table-toolbar">
            <div class="rows-label">
                Rows per page
                <select><option>10</option><option>20</option><option>50</option></select>
            </div>
            <form method="GET" action="{{ route('admin.students.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" onkeyup="debounceSearch()">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="data-table" id="studentTable">
            <thead>
                <tr>
                    <th>Roll ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>Enrolled Courses</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $enrolledRegs = $student->studentCourseRegistrations->where('status', 'enrolled');
                @endphp
                <tr>
                    <td>{{ $student->roll_no }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                    <td>{{ $student->semester }}</td>
                    <td>
                        @if($enrolledRegs->count() > 0)
                            <button class="link-edit" style="color:#4f46e5;font-weight:500"
                                onclick="viewCourses({{ $student->id }}, '{{ addslashes($student->name) }}', {{ $enrolledRegs->toJson() }})">
                                {{ $enrolledRegs->count() }} course{{ $enrolledRegs->count() != 1 ? 's' : '' }}
                            </button>
                        @else
                            <span style="color:#9ca3af">None</span>
                        @endif
                    </td>
                    <td>
                        <button class="link-edit" onclick="editStudent({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $student->roll_no }}', '{{ $student->department_id }}', '{{ $student->semester }}')">Edit</button>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}" style="display:inline" onsubmit="return confirm('Delete this student?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#9ca3af">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $students->links() }}</div>
    </div>
</div>

<!-- Courses Detail Modal -->
<div class="modal-backdrop" id="coursesModalBackdrop">
    <div class="modal-card" style="max-width:680px">
        <div class="modal-top">
            <h3 id="coursesModalTitle">Enrolled Courses</h3>
            <button class="modal-close-btn" onclick="closeCoursesModal()">&times;</button>
        </div>
        <div class="card-body" id="coursesModalBody" style="padding:0">
            <table class="data-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody id="coursesModalRows"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3>Manage Student</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="studentForm" action="{{ route('admin.students.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Name</label>
                <input type="text" name="name" id="fName" value="{{ old('name') }}" placeholder="Full name" required>
            </div>
            <div class="field-group">
                <label>Roll No</label>
                <input type="text" name="roll_no" id="fRoll" value="{{ old('roll_no') }}" placeholder="e.g. CS-2401" required>
            </div>
            <div class="field-group">
                <label>Department</label>
                <select name="department_id" id="fDept" required>
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label>Semester</label>
                <select name="semester" id="fSem" required>
                    <option value="">Select Semester</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ old('semester') == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn-submit">+ Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.students.store') }}";

function openModal() {
    document.getElementById('studentForm').action = storeUrl;
    document.getElementById('formMethod').value   = 'POST';
    document.getElementById('studentForm').reset();
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editStudent(id, name, roll, deptId, sem) {
    document.getElementById('studentForm').action = `/admin/students/${id}`;
    document.getElementById('formMethod').value   = 'PUT';
    document.getElementById('fName').value        = name;
    document.getElementById('fRoll').value        = roll;
    document.getElementById('fDept').value        = deptId;
    document.getElementById('fSem').value         = sem;
    document.getElementById('modalBackdrop').classList.add('show');
}

function debounceSearch() {
    clearTimeout(window._st);
    window._st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
}

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('coursesModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeCoursesModal();
});

function viewCourses(id, name, regs) {
    document.getElementById('coursesModalTitle').textContent = name + ' — Enrolled Courses';
    const tbody = document.getElementById('coursesModalRows');
    tbody.innerHTML = '';
    regs.forEach(reg => {
        const section = reg.course_section || {};
        const course  = section.course || {};
        const regDate = reg.registered_at ? reg.registered_at.slice(0, 10) : 'N/A';
        tbody.innerHTML += `<tr>
            <td>${course.code || 'N/A'}</td>
            <td>${course.name || 'N/A'}</td>
            <td>Sec ${section.section_number || '?'} &bull; ${section.term || ''} ${section.year || ''}</td>
            <td>${regDate}</td>
        </tr>`;
    });
    document.getElementById('coursesModalBackdrop').classList.add('show');
}

function closeCoursesModal() {
    document.getElementById('coursesModalBackdrop').classList.remove('show');
}

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalBackdrop').classList.add('show');
});
@elseif(request('add') == '1')
document.addEventListener('DOMContentLoaded', () => openModal());
@endif
</script>
@endpush
