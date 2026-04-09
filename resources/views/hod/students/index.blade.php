@extends('layouts.dashboard')

@section('title', 'Department Students')
@section('role-label', 'Head of Department')
@section('page-title', 'Department Students')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
.filter-bar {
    display:flex; flex-wrap:wrap; gap:10px; align-items:center;
    margin-bottom:16px; padding:12px 16px;
    background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;
}
.filter-bar label { font-size:.78rem; font-weight:600; color:#6b7280; margin-right:4px; }
.filter-bar select {
    padding:6px 10px; border:1px solid #d1d5db; border-radius:6px;
    font-size:.85rem; color:#374151; background:#fff; cursor:pointer;
}
.filter-bar select:focus { outline:none; border-color:#6366f1; }
.btn-clear-filters {
    padding:6px 14px; background:none; border:1px solid #d1d5db;
    border-radius:6px; font-size:.82rem; color:#6b7280; cursor:pointer;
}
.btn-clear-filters:hover { background:#f3f4f6; }
.filter-badge {
    display:none; font-size:.72rem; background:#6366f1; color:#fff;
    border-radius:10px; padding:2px 8px; font-weight:600; margin-left:4px;
}
.modal-sem-tab {
    padding:8px 16px; background:none; border:none;
    border-bottom:3px solid transparent; cursor:pointer;
    font-size:.82rem; font-weight:500; color:#6b7280;
    margin-bottom:-2px; white-space:nowrap;
}
.modal-sem-tab.modal-sem-active { color:#6366f1; border-bottom-color:#6366f1; font-weight:600; }
</style>
@endpush

@section('content')

<div class="manage-header">
    <div class="manage-title">
        <h2>Department Students</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('hod.dashboard') }}">Dashboard</a> / Students
        </div>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Student List</h3>
        <div class="table-toolbar">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" id="searchInput" placeholder="Search name, roll no, email..."
                       oninput="applyFilters()" autocomplete="off">
            </div>
        </div>
    </div>
    <div class="card-body">

        {{-- Filter bar --}}
        <div class="filter-bar">
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
                    $currentSection = $enrolledRegs->sortByDesc(fn($r) => $r->courseSection->year ?? 0)->first()?->courseSection;
                    $regTerm = $currentSection ? trim(($currentSection->term ?? '') . ' ' . ($currentSection->year ?? '')) : '';
                @endphp
                <tr data-semester="{{ $student->semester }}"
                    data-result="{{ $resultFlags }}"
                    data-term="{{ strtolower($regTerm) }}">
                    <td>{{ $student->roll_no }}</td>
                    <td style="font-weight:600;color:#1e293b;">{{ $student->name }}</td>
                    <td style="color:#6366f1;font-size:.83rem;">{{ $student->email ?? 'N/A' }}</td>
                    <td>
                        <span style="background:#ede9fe;color:#6366f1;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:600;">
                            Sem {{ $student->semester }}
                        </span>
                    </td>
                    <td>
                        @if($regTerm)
                            <span style="font-size:.75rem;background:#ede9fe;color:#5b21b6;padding:2px 7px;border-radius:8px;font-weight:600;white-space:nowrap;">
                                {{ $regTerm }}
                            </span>
                        @else
                            <span style="color:#9ca3af;font-size:.8rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($activeRegs->count() > 0)
                            <button class="link-edit" style="color:#6366f1;font-weight:500"
                                onclick="viewCourses('{{ addslashes($student->name) }}', {{ $activeRegs->values()->toJson() }})">
                                {{ $enrolledRegs->count() }} enrolled
                                @if($completedRegs->count() > 0)
                                    &bull; {{ $completedRegs->count() }} completed
                                @endif
                            </button>
                        @else
                            <span style="color:#9ca3af">None</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('hod.students.timetable', $student->id) }}"
                           style="color:#6366f1;font-size:.82rem;font-weight:600;text-decoration:none;white-space:nowrap;"
                           title="View timetable for {{ $student->name }}">&#128197; Timetable</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">No students found in your department.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="noResultsMsg" style="display:none;text-align:center;padding:24px;color:#9ca3af;font-size:.9rem;">No students match the selected filters.</div>
    </div>
</div>

{{-- Courses Detail Modal (read-only) --}}
<div class="modal-backdrop" id="coursesModalBackdrop">
    <div class="modal-card" style="max-width:820px">
        <div class="modal-top">
            <h3 id="coursesModalTitle">Student Courses</h3>
            <button class="modal-close-btn" onclick="closeCoursesModal()">&times;</button>
        </div>
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
                    </tr>
                </thead>
                <tbody id="coursesModalRows"></tbody>
            </table>
            <div id="coursesEmptyMsg" style="display:none;text-align:center;padding:24px;color:#9ca3af;">No courses found for this filter.</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Courses modal ─────────────────────────────────────────────────────────
let _allModalRegs = [];

function viewCourses(name, regs) {
    _allModalRegs = regs;
    document.getElementById('coursesModalTitle').textContent = name + ' — Courses';

    const sems = [...new Set(regs.map(r => {
        const c = (r.course_section || {}).course || {};
        return c.semester || 0;
    }))].sort((a,b) => a - b);

    const tabBar = document.getElementById('coursesFilterTabs');
    tabBar.innerHTML = '';

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
    renderCourseRows(regs);
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
    renderCourseRows(filtered);
}

function renderCourseRows(regs) {
    const tbody    = document.getElementById('coursesModalRows');
    const emptyMsg = document.getElementById('coursesEmptyMsg');
    tbody.innerHTML = '';

    if (regs.length === 0) { emptyMsg.style.display = ''; return; }
    emptyMsg.style.display = 'none';

    regs.forEach(reg => {
        const section  = reg.course_section || {};
        const course   = section.course || {};
        const regDate  = reg.registered_at ? reg.registered_at.slice(0, 10) : 'N/A';
        const status   = reg.status || 'enrolled';
        const semLabel = course.semester ? 'Sem ' + course.semester : '—';
        const result   = reg.result || null;

        let statusBadge;
        if (status === 'enrolled') {
            statusBadge = `<span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:600;">Enrolled</span>`;
        } else if (result === 'pass') {
            statusBadge = `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:600;">&#10003; Pass</span>`;
        } else if (result === 'fail') {
            statusBadge = `<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:600;">&#10007; Fail</span>`;
        } else {
            statusBadge = `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:600;">&#10003; Completed</span>`;
        }

        tbody.innerHTML += `<tr>
            <td><strong>${course.code || 'N/A'}</strong></td>
            <td>${course.name || 'N/A'}</td>
            <td><span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:.75rem;">${semLabel}</span></td>
            <td>${section.section_name || section.name || '—'}</td>
            <td style="font-size:.8rem;color:#6b7280;">${regDate}</td>
            <td>${statusBadge}</td>
        </tr>`;
    });
}

function closeCoursesModal() {
    document.getElementById('coursesModalBackdrop').classList.remove('show');
}

document.getElementById('coursesModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeCoursesModal();
});

// ── Filters + sessionStorage persistence ─────────────────────────────────
const FILTER_KEY = 'hodStudentFilters_v2';

function saveFilters() {
    try {
        sessionStorage.setItem(FILTER_KEY, JSON.stringify({
            search: document.getElementById('searchInput').value,
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
        document.getElementById('filterSem').value    = s.sem    || '';
        document.getElementById('filterResult').value = s.result || '';
        document.getElementById('filterTerm').value   = s.term   || '';
    } catch(e) {}
}

function applyFilters() {
    const query  = document.getElementById('searchInput').value.toLowerCase().trim();
    const sem    = document.getElementById('filterSem').value;
    const result = document.getElementById('filterResult').value;
    const term   = document.getElementById('filterTerm').value.toLowerCase();

    let visibleCount = 0;
    document.querySelectorAll('#studentTable tbody tr').forEach(row => {
        const matchSearch = !query  || row.textContent.toLowerCase().includes(query);
        const matchSem    = !sem    || row.dataset.semester === sem;
        const matchResult = !result || row.dataset.result.split(' ').includes(result);
        const matchTerm   = !term   || row.dataset.term === term;
        const show = matchSearch && matchSem && matchResult && matchTerm;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    document.getElementById('noResultsMsg').style.display = visibleCount === 0 ? '' : 'none';

    const active = [sem, result, term, query].filter(Boolean).length;
    const badge  = document.getElementById('filterCount');
    badge.textContent = active + ' filter' + (active > 1 ? 's' : '') + ' active';
    badge.style.display = active > 0 ? 'inline' : 'none';

    saveFilters();
}

function clearFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('filterSem').value    = '';
    document.getElementById('filterResult').value = '';
    document.getElementById('filterTerm').value   = '';
    try { sessionStorage.removeItem(FILTER_KEY); } catch(e) {}
    applyFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    restoreFilters();
    applyFilters();
});
</script>
@endpush
