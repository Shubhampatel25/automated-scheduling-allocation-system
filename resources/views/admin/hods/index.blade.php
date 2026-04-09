@extends('layouts.dashboard')

@section('title', 'Manage HOD')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage HOD')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>HOD Assignments</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / HOD Assignments
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add HOD</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>HOD List</h3>
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.hods.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" oninput="filterTable('hodTable')" autocomplete="off">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="data-table" id="hodTable">
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
                @forelse($hods as $hod)
                <tr>
                    <td>{{ $hod->teacher->employee_id ?? 'N/A' }}</td>
                    <td>{{ $hod->teacher->name ?? 'N/A' }}</td>
                    <td>{{ $hod->department->name ?? 'N/A' }}</td>
                    <td>{{ $hod->teacher->email ?? 'N/A' }}</td>
                    <td>
                        <span class="status {{ $hod->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($hod->status) }}
                        </span>
                    </td>
                    <td>
                        <button class="link-edit" onclick="editHod({{ $hod->id }}, '{{ addslashes($hod->teacher->name ?? '') }}', '{{ $hod->teacher->email ?? '' }}', '{{ $hod->department_id }}', '{{ $hod->status }}')">Edit</button>
                        <span class="sep"> | </span>
                        <a href="{{ route('admin.hods.timetable', $hod->id) }}"
                           style="color:#6366f1;font-size:0.82rem;font-weight:600;text-decoration:none;white-space:nowrap;"
                           title="View department timetable">&#128197; Timetable</a>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.hods.destroy', $hod->id) }}" style="display:inline" onsubmit="return confirm('Remove this HOD?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#9ca3af">No HODs found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $hods->links() }}</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Assign HOD</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="hodForm" action="{{ route('admin.hods.store') }}">
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
                        <option value="{{ $dept->id }}"
                            data-assigned="{{ in_array($dept->id, $assignedDeptIds) ? '1' : '0' }}"
                            {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}{{ in_array($dept->id, $assignedDeptIds) ? ' (HOD assigned)' : '' }}
                        </option>
                    @endforeach
                </select>
                <p id="fDeptNote" style="display:none;margin:4px 0 0;font-size:.78rem;color:#ef4444;">This department already has an HOD assigned.</p>
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
const storeUrl = "{{ route('admin.hods.store') }}";

function filterDeptOptions(currentDeptId) {
    // currentDeptId: the HOD's own dept (always visible), or null for Add mode
    const sel  = document.getElementById('fDept');
    const note = document.getElementById('fDeptNote');
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return; // keep the blank placeholder
        const assigned = opt.dataset.assigned === '1';
        const isOwn    = opt.value == currentDeptId;
        opt.hidden    = assigned && !isOwn;
        opt.disabled  = assigned && !isOwn;
    });
    note.style.display = 'none';
    sel.addEventListener('change', function() {
        const chosen = this.options[this.selectedIndex];
        note.style.display = (chosen && chosen.dataset.assigned === '1' && chosen.value != currentDeptId) ? '' : 'none';
    });
}

function openModal() {
    document.getElementById('hodForm').action = storeUrl;
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('hodForm').reset();
    filterDeptOptions(null);
    document.getElementById('modalTitle').textContent = 'Assign HOD';
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editHod(id, name, email, deptId, status) {
    document.getElementById('hodForm').action   = `/admin/hods/${id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('fName').value      = name;
    document.getElementById('fEmail').value     = email;
    document.getElementById('fDept').value      = deptId;
    document.getElementById('fStatus').value    = status;
    filterDeptOptions(deptId);
    document.getElementById('modalTitle').textContent = 'Edit HOD Assignment';
    document.getElementById('modalBackdrop').classList.add('show');
}

function filterTable(tableId) {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

function debounceSearch() {
    clearTimeout(window._st);
    window._st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('searchInput').value) filterTable('hodTable');
});

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalBackdrop').classList.add('show');
});
@endif
</script>
@endpush
