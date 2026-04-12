@extends('layouts.dashboard')

@section('title', 'My Availability')
@section('role-label', 'Professor Panel')
@section('page-title', 'My Availability')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('professor.dashboard') }}" class="nav-link">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Teaching</div>
    <a href="{{ route('professor.dashboard') }}#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="{{ route('professor.dashboard') }}#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="{{ route('professor.dashboard') }}#section-today" class="nav-link">
        <span class="icon">&#9200;</span> Today's Schedule
    </a>
    <a href="{{ route('professor.students') }}" class="nav-link">
        <span class="icon">&#128101;</span> My Students
    </a>

    <div class="nav-section-title">Availability</div>
    <a href="{{ route('professor.availability') }}" class="nav-link active">
        <span class="icon">&#128336;</span> Set Availability
    </a>

    <div class="nav-section-title">Account</div>
    <a href="{{ route('professor.dashboard') }}#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
/* ---- Day-pill toggle buttons ---- */
.day-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 4px;
}
.day-pill input[type="checkbox"] { display: none; }
.day-pill span {
    display: inline-block;
    padding: 8px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 999px;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 500;
    color: #6b7280;
    background: #f9fafb;
    user-select: none;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.day-pill span:hover { border-color: #a5b4fc; color: #4f46e5; background: #eef2ff; }
.day-pill input[type="checkbox"]:checked + span {
    background: #6366f1;
    border-color: #6366f1;
    color: #fff;
    box-shadow: 0 2px 6px rgba(99,102,241,.35);
}

/* ---- Availability form card ---- */
.avail-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    margin-bottom: 28px;
    overflow: hidden;
}
.avail-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.avail-card-header h3 { font-size: 1rem; font-weight: 600; color: #1f2937; margin: 0; }
.avail-card-body { padding: 24px; }

/* ---- Form field groups ---- */
.form-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
    align-items: flex-end;
}
.form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-group input,
.form-group select {
    padding: 9px 13px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.88rem;
    color: #1f2937;
    outline: none;
    transition: border-color 0.15s;
}
.form-group input:focus,
.form-group select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
.form-group input[type="time"] { min-width: 140px; }
.form-group input[type="number"] { width: 110px; }

/* ---- Buttons ---- */
.btn-submit-avail {
    padding: 9px 24px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s;
}
.btn-submit-avail:hover { background: #4f46e5; }

/* ---- Availability table ---- */
.avail-table { width: 100%; border-collapse: collapse; }
.avail-table th {
    background: #f9fafb;
    padding: 10px 14px;
    text-align: left;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid #e5e7eb;
}
.avail-table td {
    padding: 12px 14px;
    font-size: 0.88rem;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.avail-table tbody tr:last-child td { border-bottom: none; }
.avail-table tbody tr:hover { background: #fafafa; }

.day-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #eef2ff;
    color: #4f46e5;
}
.btn-remove {
    padding: 5px 14px;
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-remove:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

.btn-edit {
    padding: 5px 14px;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
    margin-right: 6px;
}
.btn-edit:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

.action-cell { display: flex; gap: 6px; align-items: center; }

.empty-avail { text-align: center; padding: 48px 0; color: #9ca3af; }
.empty-avail .ei { font-size: 2.5rem; margin-bottom: 10px; }

/* ---- Edit Modal ---- */
.modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-backdrop.open { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 14px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    padding: 28px 32px;
    position: relative;
}
.modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 22px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}
.modal-close {
    position: absolute;
    top: 16px; right: 20px;
    background: none;
    border: none;
    font-size: 1.3rem;
    color: #9ca3af;
    cursor: pointer;
    line-height: 1;
}
.modal-close:hover { color: #374151; }
.modal-form-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
.modal-form-row .form-group { flex: 1; min-width: 130px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
.btn-cancel-modal {
    padding: 9px 22px;
    background: #f9fafb;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 500;
    cursor: pointer;
}
.btn-cancel-modal:hover { background: #f3f4f6; }
.btn-save-modal {
    padding: 9px 24px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
}
.btn-save-modal:hover { background: #4f46e5; }
</style>
@endpush

@section('content')
    @php
        $currentYear = now()->year;
        $years = [$currentYear - 1, $currentYear, $currentYear + 1];
        $terms = ['Fall', 'Winter', 'Summer'];
        $days  = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

        // Guess sensible defaults for the add form
        $defaultTerm = now()->month <= 4 ? 'Winter' : (now()->month <= 8 ? 'Summer' : 'Fall');
        $defaultYear = $currentYear;
    @endphp

    <!-- Page Header -->
    <div class="manage-header">
        <div class="manage-title">
            <h2>My Availability</h2>
            <div class="breadcrumb-nav">
                <a href="{{ route('professor.dashboard') }}">Dashboard</a> / My Availability
            </div>
        </div>
        <span class="text-muted-cell" style="font-size:0.85rem;">
            {{ $availability->count() }} slot(s) set
        </span>
    </div>

    {{-- Session flashes are handled by the shared layout -- no duplication needed --}}

    <!-- Add Availability Card -->
    <div class="avail-card">
        <div class="avail-card-header">
            <h3>&#10133; Add Availability Slots</h3>
            <span style="font-size:0.8rem;color:#9ca3af;">Select one or multiple days</span>
        </div>
        <div class="avail-card-body">
            <form method="POST" action="{{ route('professor.availability.store') }}">
                @csrf

                {{-- Day multi-select as toggle pills --}}
                <div>
                    <label style="font-size:0.8rem;font-weight:600;color:#374151;display:block;margin-bottom:10px;">
                        Select Day(s) <span style="color:#ef4444;">*</span>
                    </label>
                    <div class="day-picker">
                        @php $oldDays = old('days', []); @endphp
                        @foreach($days as $day)
                            <label class="day-pill">
                                <input type="checkbox"
                                       name="days[]"
                                       value="{{ $day }}"
                                       {{ in_array($day, $oldDays) ? 'checked' : '' }}>
                                <span>{{ substr($day, 0, 3) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('days')
                        <p style="color:#ef4444;font-size:0.8rem;margin-top:6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Time + term + year + hours row --}}
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time <span style="color:#ef4444;">*</span></label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required>
                        @error('start_time')
                            <p style="color:#ef4444;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>End Time <span style="color:#ef4444;">*</span></label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required>
                        @error('end_time')
                            <p style="color:#ef4444;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Term <span style="color:#ef4444;">*</span></label>
                        <select name="term" required style="min-width:110px;">
                            @foreach($terms as $t)
                                <option value="{{ $t }}" {{ old('term', $defaultTerm) === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                        @error('term')
                            <p style="color:#ef4444;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Year <span style="color:#ef4444;">*</span></label>
                        <select name="year" required style="min-width:100px;">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ old('year', $defaultYear) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                        @error('year')
                            <p style="color:#ef4444;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Max Hours / Week</label>
                        <input type="number" name="max_hours_per_week"
                               value="{{ old('max_hours_per_week', 20) }}"
                               min="1" max="40">
                        @error('max_hours_per_week')
                            <p style="color:#ef4444;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label style="visibility:hidden;">Submit</label>
                        <button type="submit" class="btn-submit-avail">
                            &#43; Add Slots
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Availability Card -->
    <div class="avail-card">
        <div class="avail-card-header">
            <h3>&#128336; Current Availability</h3>
            <span class="badge badge-success" style="background:#ecfdf5;color:#065f46;padding:4px 12px;border-radius:999px;font-size:0.78rem;font-weight:600;">
                {{ $availability->count() }} Slot(s)
            </span>
        </div>
        <div class="avail-card-body" style="padding:0;">
            @if($availability->isNotEmpty())
                <table class="avail-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Max Hrs / Week</th>
                            <th>Term</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($availability as $i => $slot)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><span class="day-badge">{{ $slot->day_of_week }}</span></td>
                                <td>{{ substr($slot->start_time, 0, 5) }}</td>
                                <td>{{ substr($slot->end_time, 0, 5) }}</td>
                                <td>{{ $slot->max_hours_per_week ?? '—' }}</td>
                                <td>{{ ucfirst($slot->term ?? '—') }}</td>
                                <td>{{ $slot->year ?? '—' }}</td>
                                <td>
                                    <div class="action-cell">
                                        {{-- Edit button --}}
                                        <button type="button"
                                                class="btn-edit"
                                                onclick="openEditModal(
                                                    {{ $slot->id }},
                                                    '{{ $slot->day_of_week }}',
                                                    '{{ substr($slot->start_time, 0, 5) }}',
                                                    '{{ substr($slot->end_time, 0, 5) }}',
                                                    '{{ $slot->term }}',
                                                    {{ $slot->year }},
                                                    {{ $slot->max_hours_per_week ?? 20 }}
                                                )">
                                            Edit
                                        </button>

                                        {{-- Remove button --}}
                                        <form method="POST"
                                              action="{{ route('professor.availability.destroy', $slot->id) }}"
                                              onsubmit="return confirm('Remove this availability slot?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-remove">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-avail">
                    <div class="ei">&#128336;</div>
                    <p>No availability slots set yet.</p>
                    <p style="font-size:0.82rem;">Use the form above to add your availability.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ── Edit Modal ── -->
    <div class="modal-backdrop" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeEditModal()" title="Close">&times;</button>
            <div class="modal-title">&#9998; Edit Availability Slot</div>

            <form method="POST" id="editForm" action="">
                @csrf
                @method('PUT')

                <div class="modal-form-row">
                    <div class="form-group" style="flex:1;min-width:140px;">
                        <label>Day <span style="color:#ef4444;">*</span></label>
                        <select name="day_of_week" id="edit_day" required style="width:100%;">
                            @foreach($days as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;min-width:120px;">
                        <label>Term <span style="color:#ef4444;">*</span></label>
                        <select name="term" id="edit_term" required style="width:100%;">
                            @foreach($terms as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;min-width:100px;">
                        <label>Year <span style="color:#ef4444;">*</span></label>
                        <select name="year" id="edit_year" required style="width:100%;">
                            @foreach($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Start Time <span style="color:#ef4444;">*</span></label>
                        <input type="time" name="start_time" id="edit_start" required style="width:100%;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Time <span style="color:#ef4444;">*</span></label>
                        <input type="time" name="end_time" id="edit_end" required style="width:100%;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Max Hrs / Week</label>
                        <input type="number" name="max_hours_per_week" id="edit_max_hours"
                               min="1" max="40" style="width:100%;">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save-modal">&#10003; Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openEditModal(id, day, startTime, endTime, term, year, maxHours) {
    document.getElementById('editForm').action =
        '{{ url("professor/availability") }}/' + id;

    document.getElementById('edit_day').value       = day;
    document.getElementById('edit_term').value      = term;
    document.getElementById('edit_year').value      = year;
    document.getElementById('edit_start').value     = startTime;
    document.getElementById('edit_end').value       = endTime;
    document.getElementById('edit_max_hours').value = maxHours;

    document.getElementById('editModal').classList.add('open');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
}

// Close on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endpush
