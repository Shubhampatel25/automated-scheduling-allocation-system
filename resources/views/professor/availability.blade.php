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
<style>
/* ---- Day-pill toggle buttons ---- */
.day-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 4px;
}
.day-pill input[type="checkbox"] {
    display: none;
}
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
.day-pill span:hover {
    border-color: #a5b4fc;
    color: #4f46e5;
    background: #eef2ff;
}
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
.avail-card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}
.avail-card-body {
    padding: 24px;
}

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
.form-group select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.form-group input[type="time"] { min-width: 140px; }
.form-group input[type="number"] { width: 110px; }

/* ---- Submit button ---- */
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
.btn-remove:hover {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
}
.empty-avail {
    text-align: center;
    padding: 48px 0;
    color: #9ca3af;
}
.empty-avail .ei { font-size: 2.5rem; margin-bottom: 10px; }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="manage-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <h2 style="font-size:1.3rem;font-weight:600;color:#1f2937;margin:0;">My Availability</h2>
            <div style="font-size:0.78rem;color:#9ca3af;margin-top:4px;">
                <a href="{{ route('professor.dashboard') }}" style="color:#6366f1;text-decoration:none;">Dashboard</a>
                &rsaquo; My Availability
            </div>
        </div>
        <span style="font-size:0.85rem;color:#6b7280;">
            {{ $availability->count() }} slot(s) set
        </span>
    </div>

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
                        @php
                            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                            $oldDays = old('days', []);
                        @endphp
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

                {{-- Time + hours row --}}
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
                                    <form method="POST"
                                          action="{{ route('professor.availability.destroy', $slot->id) }}"
                                          onsubmit="return confirm('Remove this availability slot?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-remove">Remove</button>
                                    </form>
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
@endsection
