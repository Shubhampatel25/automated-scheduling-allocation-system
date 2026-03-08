@extends('layouts.dashboard')

@section('title', 'Manage Courses')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Courses')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Courses</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Courses
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Course</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Courses List</h3>
        <div class="table-toolbar">
            <div class="rows-label">
                Rows per page
                <select><option>10</option><option>20</option><option>50</option></select>
            </div>
            <form method="GET" action="{{ route('admin.courses.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" onkeyup="debounceSearch()">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="data-table" id="courseTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Department</th>
                    <th>Credit</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td>{{ $course->code }}</td>
                    <td>{{ $course->name }}</td>
                    <td>{{ $course->department->name ?? 'N/A' }}</td>
                    <td>{{ $course->credits }}</td>
                    <td>
                        <span class="type-badge type-{{ $course->type }}">
                            {{ ucfirst($course->type) }}
                        </span>
                    </td>
                    <td>
                        <span class="status {{ $course->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($course->status) }}
                        </span>
                    </td>
                    <td>
                        <button class="link-edit" onclick="editCourse({{ $course->id }}, '{{ $course->code }}', '{{ addslashes($course->name) }}', '{{ $course->department_id }}', '{{ $course->credits }}', '{{ $course->type }}', '{{ $course->status }}')">Edit</button>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}" style="display:inline" onsubmit="return confirm('Delete this course?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">No courses found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $courses->links() }}</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3>Manage Course</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="courseForm" action="{{ route('admin.courses.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Course Code</label>
                <input type="text" name="code" id="fCode" value="{{ old('code') }}" placeholder="e.g. CS401" required>
            </div>
            <div class="field-group">
                <label>Course Name</label>
                <input type="text" name="name" id="fName" value="{{ old('name') }}" placeholder="Course title" required>
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
                <label>Credit Hours</label>
                <select name="credits" id="fCredits" required>
                    <option value="">Select Credits</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ old('credits') == $i ? 'selected' : '' }}>{{ $i }} Credit{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
            <div class="field-group">
                <label>Course Type</label>
                <select name="type" id="fType" required>
                    <option value="">Select Type</option>
                    <option value="theory"  {{ old('type') === 'theory'  ? 'selected' : '' }}>Theory</option>
                    <option value="lab"     {{ old('type') === 'lab'     ? 'selected' : '' }}>Lab</option>
                    <option value="hybrid"  {{ old('type') === 'hybrid'  ? 'selected' : '' }}>Lecture + Lab</option>
                </select>
            </div>
            <div class="field-group">
                <label>Status</label>
                <select name="status" id="fStatus" required>
                    <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">+ Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.courses.store') }}";

function openModal() {
    document.getElementById('courseForm').action = storeUrl;
    document.getElementById('formMethod').value  = 'POST';
    document.getElementById('courseForm').reset();
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editCourse(id, code, name, deptId, credits, type, status) {
    document.getElementById('courseForm').action = `/admin/courses/${id}`;
    document.getElementById('formMethod').value  = 'PUT';
    document.getElementById('fCode').value       = code;
    document.getElementById('fName').value       = name;
    document.getElementById('fDept').value       = deptId;
    document.getElementById('fCredits').value    = credits;
    document.getElementById('fType').value       = type;
    document.getElementById('fStatus').value     = status;
    document.getElementById('modalBackdrop').classList.add('show');
}

function debounceSearch() {
    clearTimeout(window._st);
    window._st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
}

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalBackdrop').classList.add('show');
});
@elseif(request('add') == '1')
document.addEventListener('DOMContentLoaded', () => openModal());
@endif
</script>
@endpush
