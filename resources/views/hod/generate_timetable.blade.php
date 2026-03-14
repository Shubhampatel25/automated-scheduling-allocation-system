@extends('layouts.dashboard')

@section('title', 'Generate Timetable')
@section('role-label', 'Head of Department')
@section('page-title', 'Generate Timetable')

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
.form-card h3 { font-size:1.1rem; font-weight:600; color:#1e293b; margin:0 0 8px; }
.form-card .subtitle { font-size:.88rem; color:#64748b; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:24px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:.85rem; font-weight:600; color:#374151; }
.form-group select, .form-group input { padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; background:#fff; color:#1e293b; outline:none; }
.form-group select:focus, .form-group input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.form-actions { display:flex; gap:12px; }
.btn-primary { padding:11px 28px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer; }
.btn-primary:hover { background:#4f46e5; }
.info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:16px 20px; margin-bottom:20px; }
.info-box p { margin:0; font-size:.88rem; color:#1d4ed8; }
.info-box strong { display:block; margin-bottom:4px; color:#1e40af; }
.data-table { width:100%; border-collapse:collapse; font-size:.9rem; }
.data-table th { text-align:left; padding:10px 14px; background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
.data-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
.data-table tbody tr:hover { background:#f8fafc; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.status-active   { background:#dcfce7; color:#16a34a; }
.status-draft    { background:#fef9c3; color:#854d0e; }
.status-archived { background:#f1f5f9; color:#64748b; }
.btn-action { padding:5px 12px; border:none; border-radius:6px; font-size:.8rem; font-weight:600; cursor:pointer; }
.btn-activate { background:#dcfce7; color:#16a34a; }
.btn-activate:hover { background:#bbf7d0; }
.btn-delete { background:#fee2e2; color:#dc2626; }
.btn-delete:hover { background:#fca5a5; }
.empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:2.5rem; margin-bottom:10px; }
@media(max-width:768px){ .form-grid{ grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <h2>&#128197; Generate Timetable</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

<!-- Info Box -->
<div class="info-box">
    <p>
        <strong>How it works</strong>
        The system will automatically assign course sections to rooms and time slots based on teacher availability,
        room capacity, and conflict-free scheduling. Make sure course sections and teacher assignments exist for
        the selected term before generating.
    </p>
</div>

<!-- Generate Form -->
<div class="form-card">
    <h3>New Timetable Generation</h3>
    <p class="subtitle">Select the term, year, and optionally a semester to generate the schedule.</p>

    <form method="POST" action="{{ route('hod.timetable.generate') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label for="term">Term *</label>
                <select name="term" id="term" required>
                    <option value="">-- Select Term --</option>
                    <option value="Fall"   {{ old('term') == 'Fall'   ? 'selected' : '' }}>Fall</option>
                    <option value="Winter" {{ old('term') == 'Winter' ? 'selected' : '' }}>Winter</option>
                    <option value="Summer" {{ old('term') == 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="year">Year *</label>
                <input type="number" name="year" id="year"
                       min="2024" max="2030"
                       value="{{ old('year', now()->year) }}"
                       placeholder="e.g. {{ now()->year }}" required>
            </div>

            <div class="form-group">
                <label for="semester">Semester (optional)</label>
                <select name="semester" id="semester">
                    <option value="">-- All Semesters --</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ old('semester') == $i ? 'selected' : '' }}>
                            Semester {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">&#9889; Generate Timetable</button>
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
                    <th>Term / Year</th>
                    <th>Semester</th>
                    <th>Slots</th>
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
                        <td><strong>{{ $tt->term }} {{ $tt->year }}</strong></td>
                        <td>{{ $tt->semester ? 'Sem ' . $tt->semester : 'All' }}</td>
                        <td>{{ $tt->slot_count ?? 0 }}</td>
                        <td>
                            <span class="status-badge status-{{ $tt->status }}">
                                {{ ucfirst($tt->status) }}
                            </span>
                        </td>
                        <td>{{ $tt->generatedByUser->username ?? 'N/A' }}</td>
                        <td>{{ $tt->generated_at ? $tt->generated_at->format('d M Y, H:i') : 'N/A' }}</td>
                        <td style="display:flex;gap:6px;">
                            @if($tt->status === 'draft')
                                <form method="POST" action="{{ route('hod.timetable.activate', $tt->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-action btn-activate">Activate</button>
                                </form>
                                <form method="POST" action="{{ route('hod.timetable.delete', $tt->id) }}"
                                      onsubmit="return confirm('Delete this timetable?')">
                                    @csrf
                                    <button type="submit" class="btn-action btn-delete">Delete</button>
                                </form>
                            @elseif($tt->status === 'active')
                                <a href="{{ route('hod.view-timetable', ['timetable_id' => $tt->id]) }}"
                                   class="btn-action btn-activate" style="text-decoration:none;">View</a>
                            @else
                                <span style="color:#94a3b8;font-size:.82rem;">Archived</span>
                            @endif
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

@endsection
