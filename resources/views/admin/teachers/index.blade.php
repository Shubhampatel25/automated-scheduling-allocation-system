@extends('layouts.dashboard')

@section('title', 'Manage Teachers')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Teachers')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Teacher</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Teacher
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Teacher</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Teacher List</h3>
        <div class="table-toolbar" style="margin-bottom:0">
            <div class="rows-label">
                Rows per page
                <select onchange="changeRows(this.value)">
                    <option>10</option><option>20</option><option>50</option>
                </select>
            </div>
            <form method="GET" action="{{ route('admin.teachers.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" onkeyup="debounceSearch()">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="data-table" id="teacherTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                <tr>
                    <td>{{ $teacher->employee_id }}</td>
                    <td>{{ $teacher->name }}</td>
                    <td>{{ $teacher->department->name ?? 'N/A' }}</td>
                    <td>{{ $teacher->email }}</td>
                    <td>
                        <span class="status {{ $teacher->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($teacher->status) }}
                        </span>
                    </td>
                    <td>
                        <button class="link-edit" onclick="editTeacher({{ $teacher->id }}, '{{ addslashes($teacher->name) }}', '{{ $teacher->email }}', '{{ $teacher->department_id }}', '{{ $teacher->status }}')">Edit</button>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.teachers.destroy', $teacher->id) }}" style="display:inline" onsubmit="return confirm('Delete this teacher?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#9ca3af">No teachers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $teachers->links() }}</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3>Manage Teacher</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="teacherForm" action="{{ route('admin.teachers.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Name</label>
                <input type="text" name="name" id="fName" value="{{ old('name') }}" placeholder="Full name" required>
            </div>
            <div class="field-group">
                <label>Email</label>
                <input type="email" name="email" id="fEmail" value="{{ old('email') }}" placeholder="Email address" required>
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
                <label>Status</label>
                <select name="status" id="fStatus" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
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
const storeUrl = "{{ route('admin.teachers.store') }}";

function openModal() {
    document.getElementById('teacherForm').action = storeUrl;
    document.getElementById('formMethod').value   = 'POST';
    document.getElementById('teacherForm').reset();
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editTeacher(id, name, email, deptId, status) {
    document.getElementById('teacherForm').action = `/admin/teachers/${id}`;
    document.getElementById('formMethod').value   = 'PUT';
    document.getElementById('fName').value        = name;
    document.getElementById('fEmail').value       = email;
    document.getElementById('fDept').value        = deptId;
    document.getElementById('fStatus').value      = status;
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
