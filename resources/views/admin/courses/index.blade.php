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
            <form method="GET" action="{{ route('admin.courses.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" oninput="filterTable('courseTable')" autocomplete="off">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-bar">
            <div>
                <label for="fDeptFilter">Department</label>
                <select id="fDeptFilter" onchange="applyFilters()">
                    <option value="">All Departments</option>
                    @foreach($departments->sortBy('name') as $dept)
                        <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fSemFilter">Semester</label>
                <select id="fSemFilter" onchange="applyFilters()">
                    <option value="">All Semesters</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}">Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="fTypeFilter">Type</label>
                <select id="fTypeFilter" onchange="applyFilters()">
                    <option value="">All Types</option>
                    <option value="theory">Theory</option>
                    <option value="lab">Lab</option>
                    <option value="elective">Elective</option>
                </select>
            </div>
            <div>
                <label for="fStatusFilter">Status</label>
                <select id="fStatusFilter" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button class="btn-clear-filters" onclick="clearFilters()">&#10005; Clear</button>
            <span class="filter-badge" id="filterCount"></span>
        </div>
        <table class="data-table" id="courseTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>Fee</th>
                    <th>Credit</th>
                    <th>Type</th>
                    <th>Prerequisite</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr data-dept="{{ strtolower($course->department->name ?? '') }}" data-semester="{{ $course->semester }}" data-type="{{ $course->type }}" data-status="{{ $course->status }}">
                    <td>{{ $course->code }}</td>
                    <td>{{ $course->name }}</td>
                    <td>{{ $course->department->name ?? 'N/A' }}</td>
                    <td>{{ $course->semester ? 'Sem ' . $course->semester : 'All' }}</td>
                    <td>${{ number_format($course->fee ?? 0, 2) }}</td>
                    <td>{{ $course->credits }}</td>
                    <td>
                        <span class="type-badge type-{{ $course->type }}">
                            {{ ucfirst($course->type) }}
                        </span>
                    </td>
                    <td>
                        @if($course->prerequisite_course_code)
                            <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:.76rem;font-weight:600;background:#ede9fe;color:#6366f1;">
                                {{ $course->prerequisite_course_code }}
                            </span>
                            @if($course->prerequisite_mandatory)
                                <span style="display:inline-block;padding:2px 7px;border-radius:12px;font-size:.72rem;font-weight:600;background:#fee2e2;color:#dc2626;margin-top:2px;">Required</span>
                            @else
                                <span style="display:inline-block;padding:2px 7px;border-radius:12px;font-size:.72rem;font-weight:600;background:#fef3c7;color:#92400e;margin-top:2px;">Optional</span>
                            @endif
                        @else
                            <span style="color:#94a3b8;font-size:.82rem;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="status {{ $course->status === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($course->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-tbl-edit" onclick="editCourse({{ $course->id }}, '{{ $course->code }}', '{{ addslashes($course->name) }}', '{{ $course->department_id }}', '{{ $course->semester }}', '{{ $course->fee ?? 0 }}', '{{ $course->credits }}', '{{ $course->type }}', '{{ $course->status }}', '{{ $course->prerequisite_course_code ?? '' }}', {{ $course->prerequisite_mandatory ? 'true' : 'false' }})">&#9998; Edit</button>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course->id) }}" style="display:contents" onsubmit="return confirm('Delete this course?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-tbl-del">&#128465; Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:24px;color:#9ca3af">No courses found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="noResults" style="display:none;text-align:center;padding:24px;color:#9ca3af;font-size:0.9rem;">No courses match the selected filters.</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Add New Course</h3>
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
                <label>Semester</label>
                <select name="semester" id="fSemester">
                    <option value="">All Semesters (Elective)</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ old('semester') == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="field-group">
                <label>Course Fee ($)</label>
                <input type="number" name="fee" id="fFee" value="{{ old('fee', 0) }}" step="0.01" min="0" placeholder="e.g. 500.00">
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
                    <option value="theory"  {{ old('type') === 'theory'  ? 'selected' : '' }}>Theory (Lecture)</option>
                    <option value="lab"     {{ old('type') === 'lab'     ? 'selected' : '' }}>Lab</option>
                    <option value="hybrid"  {{ old('type') === 'hybrid'  ? 'selected' : '' }}>Hybrid (Lecture + Lab)</option>
                </select>
            </div>
            <div class="field-group">
                <label>Prerequisite Course <span style="color:#94a3b8;font-weight:400;font-size:.82rem;">(optional)</span></label>
                <select name="prerequisite_course_code" id="fPrereq">
                    <option value="">— No Prerequisite —</option>
                    @foreach($allCourseCodes as $pc)
                        <option value="{{ $pc->code }}" {{ old('prerequisite_course_code') === $pc->code ? 'selected' : '' }}>
                            {{ $pc->code }} – {{ $pc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field-group" id="prereqMandatoryRow" style="display:none">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="prerequisite_mandatory" id="fPrereqMandatory" value="1"
                           {{ old('prerequisite_mandatory') ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:#6366f1;">
                    Prerequisite is <strong>mandatory</strong>
                    <span style="color:#64748b;font-size:.8rem;font-weight:400;">(student must pass it before enrolling)</span>
                </label>
            </div>
            <div class="field-group">
                <label>Status</label>
                <select name="status" id="fStatus" required>
                    <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">Add Course</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.courses.store') }}";

function togglePrereqMandatory() {
    const val = document.getElementById('fPrereq').value;
    document.getElementById('prereqMandatoryRow').style.display = val ? '' : 'none';
}

function openModal() {
    document.getElementById('courseForm').action = storeUrl;
    document.getElementById('formMethod').value  = 'POST';
    document.getElementById('courseForm').reset();
    document.getElementById('prereqMandatoryRow').style.display = 'none';
    document.getElementById('modalTitle').textContent = 'Add New Course';
    document.getElementById('submitBtn').textContent  = 'Add Course';
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editCourse(id, code, name, deptId, semester, fee, credits, type, status, prereqCode, prereqMandatory) {
    document.getElementById('courseForm').action        = `/admin/courses/${id}`;
    document.getElementById('formMethod').value         = 'PUT';
    document.getElementById('fCode').value              = code;
    document.getElementById('fName').value              = name;
    document.getElementById('fDept').value              = deptId;
    document.getElementById('fSemester').value          = semester || '';
    document.getElementById('fFee').value               = fee || 0;
    document.getElementById('fCredits').value           = credits;
    document.getElementById('fType').value              = type;
    document.getElementById('fStatus').value            = status;
    document.getElementById('fPrereq').value            = prereqCode || '';
    document.getElementById('fPrereqMandatory').checked = prereqMandatory === true || prereqMandatory === 'true';
    document.getElementById('prereqMandatoryRow').style.display = prereqCode ? '' : 'none';
    document.getElementById('modalTitle').textContent   = 'Edit Course';
    document.getElementById('submitBtn').textContent    = 'Update Course';
    document.getElementById('modalBackdrop').classList.add('show');
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('fPrereq').addEventListener('change', togglePrereqMandatory);
});

function filterTable() { applyFilters(); }
function applyFilters() {
    const query  = document.getElementById('searchInput').value.toLowerCase().trim();
    const dept   = document.getElementById('fDeptFilter').value.toLowerCase();
    const sem    = document.getElementById('fSemFilter').value;
    const type   = document.getElementById('fTypeFilter').value;
    const status = document.getElementById('fStatusFilter').value;
    let count = 0;
    document.querySelectorAll('#courseTable tbody tr').forEach(row => {
        const ok = (!query  || row.textContent.toLowerCase().includes(query))
                && (!dept   || row.dataset.dept === dept)
                && (!sem    || row.dataset.semester === sem)
                && (!type   || row.dataset.type === type)
                && (!status || row.dataset.status === status);
        row.style.display = ok ? '' : 'none';
        if (ok) count++;
    });
    document.getElementById('noResults').style.display = count === 0 ? '' : 'none';
    const active = [dept, sem, type, status, query].filter(Boolean).length;
    const badge  = document.getElementById('filterCount');
    badge.textContent = active + ' filter' + (active > 1 ? 's' : '') + ' active';
    badge.style.display = active > 0 ? 'inline' : 'none';
}
function clearFilters() {
    ['searchInput','fDeptFilter','fSemFilter','fTypeFilter','fStatusFilter'].forEach(id => document.getElementById(id).value = '');
    applyFilters();
}
document.addEventListener('DOMContentLoaded', applyFilters);

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    @php
        $editingCourse = old('code') ? App\Models\Course::where('code', old('code'))->first() : null;
    @endphp
    @if($editingCourse)
        editCourse({{ $editingCourse->id }}, '{{ $editingCourse->code }}', '{{ addslashes($editingCourse->name) }}', '{{ $editingCourse->department_id }}', '{{ $editingCourse->semester }}', '{{ $editingCourse->fee ?? 0 }}', '{{ $editingCourse->credits }}', '{{ $editingCourse->type }}', '{{ $editingCourse->status }}', '{{ $editingCourse->prerequisite_course_code ?? '' }}', {{ $editingCourse->prerequisite_mandatory ? 'true' : 'false' }});
    @else
        document.getElementById('modalBackdrop').classList.add('show');
    @endif
});
@elseif(request('add') == '1')
document.addEventListener('DOMContentLoaded', () => openModal());
@endif
</script>
@endpush
