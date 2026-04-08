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
        <h2>Teachers</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Teachers
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Teacher</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Teacher List</h3>
        <div class="table-toolbar" style="margin-bottom:0">
            <form method="GET" action="{{ route('admin.teachers.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" oninput="filterTable('teacherTable')" autocomplete="off">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px;padding:12px 16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
            <div><label style="font-size:0.78rem;font-weight:600;color:#6b7280;margin-right:4px;">Department</label>
                <select id="fDept" onchange="applyFilters()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:0.85rem;color:#374151;background:#fff;cursor:pointer;">
                    <option value="">All Departments</option>
                    @foreach($departments->sortBy('name') as $dept)
                        <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label style="font-size:0.78rem;font-weight:600;color:#6b7280;margin-right:4px;">Status</label>
                <select id="fStatus" onchange="applyFilters()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:0.85rem;color:#374151;background:#fff;cursor:pointer;">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button onclick="clearFilters()" style="padding:6px 14px;background:none;border:1px solid #d1d5db;border-radius:6px;font-size:0.82rem;color:#6b7280;cursor:pointer;">&#10005; Clear</button>
            <span id="filterCount" style="display:none;font-size:0.72rem;background:#4f46e5;color:#fff;border-radius:10px;padding:2px 8px;font-weight:600;"></span>
        </div>
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
                <tr data-dept="{{ strtolower($teacher->department->name ?? '') }}" data-status="{{ $teacher->status }}">
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
        <div id="noResults" style="display:none;text-align:center;padding:24px;color:#9ca3af;font-size:0.9rem;">No teachers match the selected filters.</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Add New Teacher</h3>
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
    document.getElementById('modalTitle').textContent = 'Add New Teacher';
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
    document.getElementById('modalTitle').textContent = 'Edit Teacher';
    document.getElementById('modalBackdrop').classList.add('show');
}

function filterTable() { applyFilters(); }
function applyFilters() {
    const query  = document.getElementById('searchInput').value.toLowerCase().trim();
    const dept   = document.getElementById('fDept').value.toLowerCase();
    const status = document.getElementById('fStatus').value;
    let count = 0;
    document.querySelectorAll('#teacherTable tbody tr').forEach(row => {
        const ok = (!query  || row.textContent.toLowerCase().includes(query))
                && (!dept   || row.dataset.dept === dept)
                && (!status || row.dataset.status === status);
        row.style.display = ok ? '' : 'none';
        if (ok) count++;
    });
    document.getElementById('noResults').style.display = count === 0 ? '' : 'none';
    const active = [dept, status, query].filter(Boolean).length;
    const badge  = document.getElementById('filterCount');
    badge.textContent = active + ' filter' + (active > 1 ? 's' : '') + ' active';
    badge.style.display = active > 0 ? 'inline' : 'none';
}
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('fDept').value = '';
    document.getElementById('fStatus').value = '';
    applyFilters();
}
document.addEventListener('DOMContentLoaded', applyFilters);

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
