@extends('layouts.dashboard')

@section('title', 'Generate Timetable')
@section('role-label', 'Head of Department')
@section('page-title', 'Generate Timetable')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }
.form-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:28px; margin-bottom:28px; }
.form-card h3 { font-size:1.1rem; font-weight:600; color:#1e293b; margin:0 0 8px; }
.form-card .subtitle { font-size:.88rem; color:#64748b; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
.form-row { display:grid; gap:20px; margin-bottom:20px; }
.form-row-3 { grid-template-columns: repeat(3, 1fr); }
.form-row-2 { grid-template-columns: repeat(2, 1fr); }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:.85rem; font-weight:600; color:#374151; }
.form-group select, .form-group input {
    padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;
    font-size:.9rem; background:#fff; color:#1e293b; outline:none;
}
.form-group select:focus, .form-group input:focus {
    border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15);
}
.form-actions { display:flex; gap:12px; padding-top:4px; }
.btn-primary { padding:11px 28px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer; }
.btn-primary:hover { background:#4f46e5; }
.btn-regen { background:#0ea5e9; }
.btn-regen:hover { background:#0284c7; }
.info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:16px 20px; margin-bottom:20px; }
.info-box p { margin:0; font-size:.88rem; color:#1d4ed8; }
.info-box strong { display:block; margin-bottom:4px; color:#1e40af; }
.alert { padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:.9rem; }
.alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.alert-warning { background:#fef9c3; color:#854d0e; border:1px solid #fde68a; }
.data-table { width:100%; border-collapse:collapse; font-size:.9rem; }
.data-table th { text-align:left; padding:10px 14px; background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
.data-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.data-table tbody tr:hover { background:#f8fafc; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.status-active   { background:#dcfce7; color:#16a34a; }
.status-draft    { background:#fef9c3; color:#854d0e; }
.status-archived { background:#f1f5f9; color:#64748b; }
.empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:2.5rem; margin-bottom:10px; }
.actions-cell { display:flex; gap:6px; align-items:center; }
@media(max-width:768px){
    .form-row-3, .form-row-2 { grid-template-columns:1fr; }
}
</style>

<div class="page-header">
    <h2>&#128197; Generate Timetable</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="alert alert-success">&#10003; {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">&#9888; {{ session('error') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">&#9888; {{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $err)
            <div>&#9888; {{ $err }}</div>
        @endforeach
    </div>
@endif

<!-- Info Box -->
<div class="info-box">
    <p>
        <strong>How it works</strong>
        Select semester, term, year, department, and optionally a specific course. The system will
        automatically create course sections, assign teachers (round-robin if unassigned), allocate
        rooms by type and capacity, and detect conflicts.
    </p>
</div>

<!-- Generate Form -->
<div class="form-card">
    <h3>&#9889; Generate Time-Table</h3>
    <p class="subtitle">Choose the parameters below then click Generate.</p>

    <form method="POST" action="{{ route('hod.timetable.generate') }}">
        @csrf

        {{-- Row 1: Semester | Term | Year --}}
        <div class="form-row form-row-3">
            <div class="form-group">
                <label for="semester">Semester</label>
                <select name="semester" id="semester" required>
                    <option value="">-- Select Semester --</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ old('semester') == $i ? 'selected' : '' }}>
                            Semester {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="form-group">
                <label for="term">Term</label>
                <select name="term" id="term" required>
                    <option value="">-- Select Term --</option>
                    <option value="Fall"   {{ old('term') == 'Fall'   ? 'selected' : '' }}>Fall</option>
                    <option value="Winter" {{ old('term') == 'Winter' ? 'selected' : '' }}>Winter</option>
                    <option value="Summer" {{ old('term') == 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="year">Year</label>
                <select name="year" id="year" required>
                    <option value="">-- Select Year --</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Row 2: Department --}}
        <div class="form-row" style="grid-template-columns:1fr;">
            <div class="form-group">
                <label for="department_id">Department</label>
                <select name="department_id" id="department_id" required>
                    <option value="">-- Select Semester First --</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="action" value="generate"   class="btn-primary">&#9889; Generate Timetable</button>
            <button type="submit" name="action" value="regenerate" class="btn-primary btn-regen">&#8635; Regenerate Timetable</button>
        </div>
    </form>
</div>

<!-- Existing Timetables -->
<div class="form-card">
    <h3>Generated Timetables ({{ $existingTimetables->count() }})</h3>

    @if($existingTimetables->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department</th>
                    <th>Term / Year</th>
                    <th>Semester</th>
                    <th>Slots</th>
                    <th>Conflicts</th>
                    <th>Status</th>
                    <th>Generated By</th>
                    <th>Generated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($existingTimetables as $index => $tt)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $tt->department->code ?? $tt->department->name ?? 'N/A' }}</td>
                        <td><strong>{{ $tt->term }} {{ $tt->year }}</strong></td>
                        <td>{{ $tt->semester ? 'Sem ' . $tt->semester : 'All' }}</td>
                        <td>{{ $tt->slot_count ?? 0 }}</td>
                        <td>
                            @if(($tt->conflicts_count ?? 0) > 0)
                                <span style="color:#dc2626;font-weight:600;">{{ $tt->conflicts_count }}</span>
                            @else
                                <span style="color:#16a34a;">None</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge status-{{ $tt->status }}">
                                {{ ucfirst($tt->status) }}
                            </span>
                        </td>
                        <td>{{ $tt->generatedByUser->username ?? 'N/A' }}</td>
                        <td>{{ $tt->generated_at ? $tt->generated_at->format('d M Y, H:i') : 'N/A' }}</td>
                        <td>
                            <div class="actions-cell">
                                @if($tt->status === 'draft')
                                    <form method="POST" action="{{ route('hod.timetable.activate', $tt->id) }}">
                                        @csrf
                                        <button type="submit" class="btn-tbl-activate">Activate</button>
                                    </form>
                                    <form method="POST" action="{{ route('hod.timetable.delete', $tt->id) }}"
                                          onsubmit="return confirm('Delete this timetable?')">
                                        @csrf
                                        <button type="submit" class="btn-tbl-del">&#128465; Delete</button>
                                    </form>
                                @elseif($tt->status === 'active')
                                    <form method="POST" action="{{ route('hod.timetable.deactivate', $tt->id) }}"
                                          onsubmit="return confirm('Deactivate this timetable? It will move back to draft.')">
                                        @csrf
                                        <button type="submit" class="btn-tbl-warn">Deactivate</button>
                                    </form>
                                @endif
                                @if($tt->status === 'active' || ($tt->status === 'draft' && ($tt->slot_count ?? 0) > 0))
                                    <button class="btn-tbl-view"
                                            onclick="openTimetableModal({{ $tt->id }}, '{{ addslashes($tt->department->name ?? 'N/A') }}', '{{ $tt->term }} {{ $tt->year }}', {{ $tt->semester ?? 0 }})">
                                        &#128065; View
                                    </button>
                                @endif
                                @if($tt->status === 'archived')
                                    <span style="color:#94a3b8;font-size:.82rem;">Archived</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128197;</div>
            <p>No timetables generated yet. Use the form above to create one.</p>
        </div>
    @endif
</div>

<script>
const semesterSelect   = document.getElementById('semester');
const departmentSelect = document.getElementById('department_id');

semesterSelect.addEventListener('change', function () {
    const semester = this.value;
    departmentSelect.innerHTML = '<option value="">-- Loading... --</option>';

    if (!semester) {
        departmentSelect.innerHTML = '<option value="">-- Select Semester First --</option>';
        return;
    }

    fetch(`{{ route('hod.departments-by-semester') }}?semester=${semester}`)
        .then(res => res.json())
        .then(depts => {
            departmentSelect.innerHTML = '<option value="">-- Select Department --</option>';
            depts.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = `${d.code} \u2013 ${d.name}`;
                departmentSelect.appendChild(opt);
            });
        })
        .catch(() => {
            departmentSelect.innerHTML = '<option value="">-- Failed to load --</option>';
        });
});
</script>

@include('partials.timetable-modal', ['slotRouteBase' => url('hod/timetable')])
@endsection
