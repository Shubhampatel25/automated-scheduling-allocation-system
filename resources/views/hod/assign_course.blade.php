@extends('layouts.dashboard')

@section('title', 'Assign Course')
@section('role-label', 'Head of Department')
@section('page-title', 'Assign Course')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }
.form-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:28px; margin-bottom:28px; }
.form-card h3 { font-size:1.1rem; font-weight:600; color:#1e293b; margin:0 0 20px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:16px; align-items:end; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:.85rem; font-weight:600; color:#374151; }
.form-group select { padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; background:#fff; color:#1e293b; outline:none; }
.form-group select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.btn-primary { padding:10px 22px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.9rem; font-weight:600; cursor:pointer; white-space:nowrap; }
.btn-primary:hover { background:#4f46e5; }
.btn-danger-sm { padding:5px 12px; background:#fee2e2; color:#dc2626; border:none; border-radius:6px; font-size:.8rem; font-weight:600; cursor:pointer; }
.btn-danger-sm:hover { background:#fca5a5; }
.data-table { width:100%; border-collapse:collapse; font-size:.9rem; }
.data-table th { text-align:left; padding:10px 14px; background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
.data-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
.data-table tbody tr:hover { background:#f8fafc; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab { background:#fef3c7; color:#92400e; }
.empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:2.5rem; margin-bottom:10px; }
@media(max-width:768px){ .form-grid{ grid-template-columns:1fr; } }
</style>

<!-- Header -->
<div class="page-header">
    <h2>&#128221; Assign Course to Teacher</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

<!-- Assignment Form -->
<div class="form-card">
    <h3>New Assignment</h3>
    <form method="POST" action="{{ route('hod.assign-course.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label for="course_section_id">Course Section</label>
                <select name="course_section_id" id="course_section_id" required>
                    <option value="">-- Select Section --</option>
                    @foreach($courseSections as $section)
                        <option value="{{ $section->id }}" {{ old('course_section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->course->name ?? 'N/A' }}
                            (Sec {{ $section->section_number }}
                            @if($section->term) · {{ $section->term }} {{ $section->year }} @endif)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="teacher_id">Teacher</label>
                <select name="teacher_id" id="teacher_id" required>
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="component">Component</label>
                <select name="component" id="component" required>
                    <option value="">-- Select --</option>
                    <option value="theory" {{ old('component') == 'theory' ? 'selected' : '' }}>Theory</option>
                    <option value="lab"    {{ old('component') == 'lab'    ? 'selected' : '' }}>Lab</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Assign</button>
        </div>
    </form>
</div>

<!-- Existing Assignments -->
<div class="form-card">
    <h3>Current Assignments ({{ $assignments->count() }})</h3>

    @if($assignments->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Teacher</th>
                    <th>Component</th>
                    <th>Assigned Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignments as $index => $assignment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $assignment->courseSection->course->name ?? 'N/A' }}</td>
                        <td>Section {{ $assignment->courseSection->section_number ?? 'N/A' }}</td>
                        <td>{{ $assignment->teacher->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $assignment->component }}">
                                {{ ucfirst($assignment->component) }}
                            </span>
                        </td>
                        <td>{{ $assignment->assigned_date ? \Carbon\Carbon::parse($assignment->assigned_date)->format('d M Y') : 'N/A' }}</td>
                        <td>
                            <form method="POST" action="{{ route('hod.assign-course.destroy', $assignment->id) }}"
                                  onsubmit="return confirm('Remove this assignment?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-icon">&#128221;</div>
            <p>No course assignments yet. Use the form above to assign courses.</p>
        </div>
    @endif
</div>

@endsection
