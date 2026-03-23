@extends('layouts.dashboard')

@section('title', 'Manage Departments')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Departments')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Department</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Department
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Department</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Department List</h3>
        <div class="table-toolbar">
            <div class="rows-label">
                Rows per page
                <select><option>10</option><option>20</option><option>50</option></select>
            </div>
            <form method="GET" action="{{ route('admin.departments.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" onkeyup="debounceSearch()">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="data-table" id="deptTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Department Name</th>
                    <th>Reg. Fee</th>
                    <th>Teachers</th>
                    <th>Courses</th>
                    <th>Students</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr>
                    <td>{{ $dept->code }}</td>
                    <td>{{ $dept->name }}</td>
                    <td>
                        @if($dept->registration_fee !== null)
                            <span style="font-weight:600;color:#065f46;">${{ number_format($dept->registration_fee, 2) }}</span>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    <td>{{ $dept->teachers_count }}</td>
                    <td>{{ $dept->courses_count }}</td>
                    <td>{{ $dept->students_count }}</td>
                    <td>
                        <button class="link-edit" onclick="editDept({{ $dept->id }}, '{{ $dept->code }}', '{{ addslashes($dept->name) }}', '{{ addslashes($dept->description ?? '') }}', '{{ $dept->registration_fee ?? '' }}')">Edit</button>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.departments.destroy', $dept->id) }}" style="display:inline" onsubmit="return confirm('Delete this department? This may affect teachers, courses and students linked to it.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $departments->links() }}</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3>Manage Department</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="deptForm" action="{{ route('admin.departments.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Department Code</label>
                <input type="text" name="code" id="fCode" value="{{ old('code') }}" placeholder="e.g. CS, ICT, PSW" required>
            </div>
            <div class="field-group">
                <label>Department Name</label>
                <input type="text" name="name" id="fName" value="{{ old('name') }}" placeholder="Full department name" required>
            </div>
            <div class="field-group">
                <label>Description <span style="color:#9ca3af;font-weight:400">(optional)</span></label>
                <textarea name="description" id="fDesc" placeholder="Brief description">{{ old('description') }}</textarea>
            </div>
            <div class="field-group">
                <label>Registration Fee <span style="color:#9ca3af;font-weight:400">(optional — $ per semester)</span></label>
                <input type="number" name="registration_fee" id="fFee" value="{{ old('registration_fee') }}" placeholder="e.g. 500.00" step="0.01" min="0">
            </div>
            <button type="submit" class="btn-submit">+ Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.departments.store') }}";

function openModal() {
    document.getElementById('deptForm').action  = storeUrl;
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('deptForm').reset();
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editDept(id, code, name, desc, fee) {
    document.getElementById('deptForm').action  = `/admin/departments/${id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('fCode').value      = code;
    document.getElementById('fName').value      = name;
    document.getElementById('fDesc').value      = desc;
    document.getElementById('fFee').value       = fee;
    document.getElementById('modalBackdrop').classList.add('show');
}

function debounceSearch() {
    clearTimeout(window._st);
    window._st = setTimeout(() => document.getElementById('searchForm').submit(), 400);
}

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-open modal if coming from dashboard quick action or validation errors
@if($errors->any())
document.addEventListener('DOMContentLoaded', () => openModal());
@elseif(request('add') == '1')
document.addEventListener('DOMContentLoaded', () => openModal());
@endif
</script>
@endpush
