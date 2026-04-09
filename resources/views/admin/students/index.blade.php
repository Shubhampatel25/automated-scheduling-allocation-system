@extends('layouts.dashboard')

@section('title', 'Manage Students')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Students')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 16px;
    padding: 12px 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.filter-bar label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    margin-right: 4px;
}
.filter-bar select {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #374151;
    background: #fff;
    cursor: pointer;
}
.filter-bar select:focus {
    outline: none;
    border-color: #4f46e5;
}
.btn-clear-filters {
    padding: 6px 14px;
    background: none;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.82rem;
    color: #6b7280;
    cursor: pointer;
}
.btn-clear-filters:hover { background: #f3f4f6; }
.filter-badge {
    display: none;
    font-size: 0.72rem;
    background: #4f46e5;
    color: #fff;
    border-radius: 10px;
    padding: 2px 8px;
    font-weight: 600;
    margin-left: 4px;
}
.modal-sem-tab {
    padding: 8px 16px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: -2px;
    white-space: nowrap;
}
.modal-sem-tab.modal-sem-active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
    font-weight: 600;
}
</style>
@endpush

@section('content')
@if(session('success'))
<div style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #a7f3d0;">
    &#10003; {{ session('success') }}
</div>
@endif
<div class="manage-header">
    <div class="manage-title">
        <h2>Students</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Students
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Student</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Student List</h3>
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.students.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" oninput="filterTable('studentTable')" autocomplete="off">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">

        {{-- Multi-filter bar --}}
        <div class="filter-bar">
            <div>
                <label for="filterDept">Department</label>
                <select id="filterDept" onchange="applyFilters()">
                    <option value="">All Departments</option>
                    @foreach($departments->sortBy('name') as $dept)
                        <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filterSem">Semester</label>
                <select id="filterSem" onchange="applyFilters()">
                    <option value="">All Semesters</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}">Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="filterResult">Result Status</label>
                <select id="filterResult" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="pass">Pass</option>
                    <option value="fail">Fail</option>
                    <option value="enrolled">Enrolled (in progress)</option>
                    <option value="none">No Courses Yet</option>
                </select>
            </div>
            <div>
                <label for="filterTerm">Reg. Term</label>
                <select id="filterTerm" onchange="applyFilters()">
                    <option value="">All Terms</option>
                    @php
                        $allTerms = $students->map(function($s) {
                            $sec = $s->studentCourseRegistrations->where('status','enrolled')
                                ->sortByDesc(fn($r) => $r->courseSection->year ?? 0)->first()?->courseSection;
                            return $sec ? trim(($sec->term ?? '') . ' ' . ($sec->year ?? '')) : null;
                        })->filter()->unique()->sort()->values();
                    @endphp
                    @foreach($allTerms as $term)
                        <option value="{{ strtolower($term) }}">{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-clear-filters" onclick="clearFilters()">&#10005; Clear Filters</button>
            <span class="filter-badge" id="filterCount"></span>
        </div>

        <table class="data-table" id="studentTable">
            <thead>
                <tr>
                    <th>Roll ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>Reg. Term</th>
                    <th>Enrolled Courses</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $activeRegs    = $student->studentCourseRegistrations->whereIn('status', ['enrolled', 'completed']);
                    $enrolledRegs  = $activeRegs->where('status', 'enrolled');
                    $completedRegs = $activeRegs->where('status', 'completed');
                    $resultFlags   = $completedRegs->pluck('result')->filter()->unique()->implode(' ');
                    if ($enrolledRegs->isNotEmpty()) $resultFlags = trim($resultFlags . ' enrolled');
                    if (!$resultFlags) $resultFlags = 'none';
                    // Get term/year from currently enrolled sections (latest year first)
                    $currentSection = $enrolledRegs->sortByDesc(fn($r) => $r->courseSection->year ?? 0)->first()?->courseSection;
                    $regTerm = $currentSection ? trim(($currentSection->term ?? '') . ' ' . ($currentSection->year ?? '')) : '';
                @endphp
                <tr data-dept="{{ strtolower($student->department->name ?? '') }}"
                    data-semester="{{ $student->semester }}"
                    data-result="{{ $resultFlags }}"
                    data-term="{{ strtolower($regTerm) }}">
                    <td>{{ $student->roll_no }}</td>
                    <td>{{ $student->name }}</td>
                    <td style="color:#4f46e5;font-size:0.83rem;">{{ $student->email ?? 'N/A' }}</td>
                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                    <td>{{ $student->semester }}</td>
                    <td>
                        @if($regTerm)
                            <span style="font-size:0.75rem;background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:8px;font-weight:600;white-space:nowrap;">
                                {{ $regTerm }}
                            </span>
                        @else
                            <span style="color:#9ca3af;font-size:0.8rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($activeRegs->count() > 0)
                            <button class="link-edit" style="color:#4f46e5;font-weight:500"
                                onclick="viewCourses('{{ addslashes($student->name) }}', {{ $activeRegs->values()->toJson() }})">
                                {{ $enrolledRegs->count() }} enrolled
                                @if($activeRegs->where('status','completed')->count() > 0)
                                    &bull; {{ $activeRegs->where('status','completed')->count() }} completed
                                @endif
                            </button>
                        @else
                            <span style="color:#9ca3af">None</span>
                        @endif
                    </td>
                    <td>
                        <button class="link-edit" onclick="editStudent({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $student->roll_no }}', '{{ $student->department_id }}', '{{ $student->semester }}')">Edit</button>
                        <span class="sep"> | </span>
                        <a href="{{ route('admin.students.timetable', $student->id) }}"
                           style="color:#6366f1;font-size:0.82rem;font-weight:600;text-decoration:none;white-space:nowrap;"
                           title="View timetable for {{ $student->name }}">&#128197; Timetable</a>
                        <span class="sep"> | </span>
                        <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}" style="display:inline" onsubmit="return confirm('Delete this student?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-del">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:24px;color:#9ca3af">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="noResultsMsg" style="display:none;text-align:center;padding:24px;color:#9ca3af;font-size:0.9rem;">No students match the selected filters.</div>
    </div>
</div>

<!-- Mark Complete Modal -->
<div class="modal-backdrop" id="completeModalBackdrop">
    <div class="modal-card" style="max-width:440px">
        <div class="modal-top">
            <h3>Mark Course Complete</h3>
            <button class="modal-close-btn" onclick="closeCompleteModal()">&times;</button>
        </div>
        <form method="POST" id="completeForm" action="">
            @csrf
            <div style="padding:20px 24px;">
                <p style="margin:0 0 4px;font-size:0.875rem;color:#6b7280;">Student</p>
                <p style="margin:0 0 16px;font-size:0.95rem;font-weight:600;color:#111827;" id="completeStudentName"></p>
                <p style="margin:0 0 4px;font-size:0.875rem;color:#6b7280;">Course</p>
                <p style="margin:0 0 20px;font-size:0.95rem;font-weight:600;color:#111827;" id="completeCourseName"></p>
                <div class="field-group">
                    <label>Result</label>
                    <div style="display:flex;gap:24px;margin-top:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                            <input type="radio" name="result" value="pass" required
                                style="accent-color:#16a34a;width:16px;height:16px;">
                            <span style="color:#16a34a;font-weight:600;font-size:0.95rem;">&#10003; Pass</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                            <input type="radio" name="result" value="fail" required
                                style="accent-color:#dc2626;width:16px;height:16px;">
                            <span style="color:#dc2626;font-weight:600;font-size:0.95rem;">&#10007; Fail</span>
                        </label>
                    </div>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="closeCompleteModal()"
                        style="padding:8px 18px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;font-size:0.875rem;">
                        Cancel
                    </button>
                    <button type="submit"
                        style="padding:8px 18px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.875rem;font-weight:600;">
                        Save & Complete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Courses Detail Modal -->
<div class="modal-backdrop" id="coursesModalBackdrop">
    <div class="modal-card" style="max-width:820px">
        <div class="modal-top">
            <h3 id="coursesModalTitle">Student Courses</h3>
            <button class="modal-close-btn" onclick="closeCoursesModal()">&times;</button>
        </div>
        <div id="coursesSuccessMsg" style="display:none;background:#d1fae5;color:#065f46;padding:10px 16px;font-size:0.875rem;border-bottom:1px solid #a7f3d0;"></div>

        {{-- Semester filter tabs --}}
        <div id="coursesFilterTabs" style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;padding:0 16px;background:#fff;flex-wrap:wrap;"></div>

        <div class="card-body" id="coursesModalBody" style="padding:0">
            <table class="data-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Semester</th>
                        <th>Section</th>
                        <th>Registered At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="coursesModalRows"></tbody>
            </table>
            <div id="coursesEmptyMsg" style="display:none;text-align:center;padding:24px;color:#9ca3af;">No courses found for this filter.</div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Add New Student</h3>
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
                <input type="text" name="name" id="fName" value="{{ old('name') }}" placeholder="Full name" required oninput="previewEmail()">
            </div>
            <div class="field-group">
                <label>Roll No</label>
                <input type="text" name="roll_no" id="fRoll" value="{{ old('roll_no') }}" placeholder="e.g. CS-2401" required oninput="previewEmail()">
                <div id="emailPreview" style="margin-top:5px;font-size:0.78rem;color:#6b7280;display:none;">
                    &#9993; Email will be: <span id="emailPreviewVal" style="color:#4f46e5;font-weight:600;"></span>
                </div>
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
const storeUrl        = "{{ route('admin.students.store') }}";
const completeBaseUrl = "{{ url('admin/registrations') }}";
const csrfToken       = "{{ csrf_token() }}";

function previewEmail() {
    const name    = document.getElementById('fName').value.trim();
    const rollNo  = document.getElementById('fRoll').value.trim();
    const preview = document.getElementById('emailPreview');
    const val     = document.getElementById('emailPreviewVal');
    if (name && rollNo) {
        const firstName = name.split(' ')[0].toLowerCase().replace(/\s/g, '');
        const roll      = rollNo.toLowerCase().replace(/[\s\/]/g, '');
        val.textContent = firstName + roll + '@student.edu';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

function openModal() {
    document.getElementById('studentForm').action = storeUrl;
    document.getElementById('formMethod').value   = 'POST';
    document.getElementById('studentForm').reset();
    document.getElementById('emailPreview').style.display = 'none';
    document.getElementById('modalTitle').textContent = 'Add New Student';
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
    document.getElementById('modalTitle').textContent = 'Edit Student';
    document.getElementById('modalBackdrop').classList.add('show');
}

// ── Filter persistence via sessionStorage ────────────────────────────────
// Filters are saved every time they change and restored when the page loads
// (e.g. after browser Back). Cleared only when user explicitly clicks Clear.
const FILTER_KEY = 'adminStudentFilters_v1';

function saveFilters() {
    try {
        sessionStorage.setItem(FILTER_KEY, JSON.stringify({
            search: document.getElementById('searchInput').value,
            dept:   document.getElementById('filterDept').value,
            sem:    document.getElementById('filterSem').value,
            result: document.getElementById('filterResult').value,
            term:   document.getElementById('filterTerm').value,
        }));
    } catch(e) {}
}

function restoreFilters() {
    try {
        const saved = sessionStorage.getItem(FILTER_KEY);
        if (!saved) return;
        const s = JSON.parse(saved);
        document.getElementById('searchInput').value  = s.search || '';
        document.getElementById('filterDept').value   = s.dept   || '';
        document.getElementById('filterSem').value    = s.sem    || '';
        document.getElementById('filterResult').value = s.result || '';
        document.getElementById('filterTerm').value   = s.term   || '';
    } catch(e) {}
}

function filterTable() { applyFilters(); }

function applyFilters() {
    const query  = document.getElementById('searchInput').value.toLowerCase().trim();
    const dept   = document.getElementById('filterDept').value.toLowerCase();
    const sem    = document.getElementById('filterSem').value;
    const result = document.getElementById('filterResult').value;
    const term   = document.getElementById('filterTerm').value.toLowerCase();

    let visibleCount = 0;
    document.querySelectorAll('#studentTable tbody tr').forEach(row => {
        const matchSearch = !query  || row.textContent.toLowerCase().includes(query);
        const matchDept   = !dept   || row.dataset.dept === dept;
        const matchSem    = !sem    || row.dataset.semester === sem;
        const matchResult = !result || row.dataset.result.split(' ').includes(result);
        const matchTerm   = !term   || row.dataset.term === term;

        const show = matchSearch && matchDept && matchSem && matchResult && matchTerm;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    document.getElementById('noResultsMsg').style.display = visibleCount === 0 ? '' : 'none';

    const active = [dept, sem, result, term, query].filter(Boolean).length;
    const badge  = document.getElementById('filterCount');
    badge.textContent = active + ' filter' + (active > 1 ? 's' : '') + ' active';
    badge.style.display = active > 0 ? 'inline' : 'none';

    saveFilters(); // persist state after every filter change
}

function clearFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('filterDept').value   = '';
    document.getElementById('filterSem').value    = '';
    document.getElementById('filterResult').value = '';
    document.getElementById('filterTerm').value   = '';
    try { sessionStorage.removeItem(FILTER_KEY); } catch(e) {}
    applyFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    restoreFilters(); // put saved values back into the inputs
    applyFilters();   // then apply them so the table reflects the restored state
});

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('coursesModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeCoursesModal();
});

let _allModalRegs = [];

function viewCourses(name, regs) {
    _allModalRegs = regs;
    document.getElementById('coursesModalTitle').textContent = name + ' — Courses';
    document.getElementById('coursesSuccessMsg').style.display = 'none';

    // Build semester filter tabs
    const sems = [...new Set(regs.map(r => {
        const c = (r.course_section || {}).course || {};
        return c.semester || 0;
    }))].sort((a,b) => a - b);

    const tabBar = document.getElementById('coursesFilterTabs');
    tabBar.innerHTML = '';

    // "All" tab
    const allTab = document.createElement('button');
    allTab.textContent = 'All';
    allTab.dataset.sem = 'all';
    allTab.className = 'modal-sem-tab modal-sem-active';
    allTab.onclick = () => applyCoursesFilter('all', allTab);
    tabBar.appendChild(allTab);

    sems.forEach(sem => {
        const btn = document.createElement('button');
        btn.textContent = sem ? 'Semester ' + sem : 'No Semester';
        btn.dataset.sem = sem;
        btn.className = 'modal-sem-tab';
        btn.onclick = () => applyCoursesFilter(sem, btn);
        tabBar.appendChild(btn);
    });

    tabBar.style.display = sems.length > 0 ? 'flex' : 'none';

    renderCourseRows(regs, name);
    document.getElementById('coursesModalBackdrop').classList.add('show');
}

function applyCoursesFilter(sem, btn) {
    document.querySelectorAll('.modal-sem-tab').forEach(t => t.classList.remove('modal-sem-active'));
    btn.classList.add('modal-sem-active');

    const filtered = sem === 'all'
        ? _allModalRegs
        : _allModalRegs.filter(r => {
            const c = (r.course_section || {}).course || {};
            return String(c.semester || 0) === String(sem);
          });

    renderCourseRows(filtered, document.getElementById('coursesModalTitle').textContent.split(' — ')[0]);
}

function renderCourseRows(regs, name) {
    const tbody = document.getElementById('coursesModalRows');
    const emptyMsg = document.getElementById('coursesEmptyMsg');
    tbody.innerHTML = '';

    if (regs.length === 0) {
        emptyMsg.style.display = '';
        return;
    }
    emptyMsg.style.display = 'none';

    regs.forEach(reg => {
        const section    = reg.course_section || {};
        const course     = section.course || {};
        const regDate    = reg.registered_at ? reg.registered_at.slice(0, 10) : 'N/A';
        const status     = reg.status || 'enrolled';
        const isEnrolled = status === 'enrolled';
        const semLabel   = course.semester ? 'Sem ' + course.semester : '—';

        const result = reg.result || null;
        let statusBadge;
        if (isEnrolled) {
            statusBadge = `<span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600;">Enrolled</span>`;
        } else if (result === 'pass') {
            statusBadge = `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600;">&#10003; Pass</span>`;
        } else if (result === 'fail') {
            statusBadge = `<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600;">&#10007; Fail</span>`;
        } else {
            statusBadge = `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600;">&#10003; Completed</span>`;
        }

        const actionBtn = isEnrolled
            ? `<button type="button"
                    style="background:#16a34a;color:#fff;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;font-size:0.78rem;"
                    onclick="openCompleteModal(${reg.id}, '${(course.name||'').replace(/'/g,"\\'")}', '${name.replace(/'/g,"\\'")}')">
                    Mark Complete
               </button>`
            : `<span style="color:#9ca3af;font-size:0.8rem;">—</span>`;

        tbody.innerHTML += `<tr>
            <td><strong>${course.code || 'N/A'}</strong></td>
            <td>${course.name || 'N/A'}</td>
            <td><span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:0.75rem;">${semLabel}</span></td>
            <td>Sec ${section.section_number || '?'} &bull; ${section.term || ''} ${section.year || ''}</td>
            <td>${regDate}</td>
            <td>${statusBadge}</td>
            <td>${actionBtn}</td>
        </tr>`;
    });
}

function closeCoursesModal() {
    document.getElementById('coursesModalBackdrop').classList.remove('show');
}

document.getElementById('completeModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeCompleteModal();
});

function openCompleteModal(regId, courseName, studentName) {
    document.getElementById('completeStudentName').textContent = studentName;
    document.getElementById('completeCourseName').textContent  = courseName;
    document.getElementById('completeForm').action = `${completeBaseUrl}/${regId}/complete`;
    document.querySelectorAll('#completeForm input[name="result"]').forEach(r => r.checked = false);
    document.getElementById('completeModalBackdrop').classList.add('show');
}

function closeCompleteModal() {
    document.getElementById('completeModalBackdrop').classList.remove('show');
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
