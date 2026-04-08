@extends('layouts.dashboard')

@section('title', 'Edit Timetable Slot')
@section('role-label', 'Head of Department')
@section('page-title', 'Edit Timetable Slot')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }

.slot-info-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px 24px; margin-bottom:24px; display:flex; gap:28px; flex-wrap:wrap; align-items:flex-start; }
.info-group { display:flex; flex-direction:column; gap:3px; min-width:140px; }
.info-group .lbl { font-size:.74rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.info-group .val { font-size:.95rem; font-weight:700; color:#1e293b; }
.badge-component { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.76rem; font-weight:700; }
.badge-theory { background:#e0f2fe; color:#0369a1; }
.badge-lab    { background:#fef3c7; color:#92400e; }

.form-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:28px 32px; max-width:700px; }
.form-card h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 20px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }

.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media(max-width:580px) { .form-grid { grid-template-columns:1fr; } }

.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:.84rem; font-weight:600; color:#374151; }
.form-group select,
.form-group input { padding:10px 13px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; color:#1e293b; background:#fff; width:100%; box-sizing:border-box; }
.form-group select:focus,
.form-group input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.form-group .hint { font-size:.76rem; color:#94a3b8; }

.error-box { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin-bottom:20px; }
.error-box ul { margin:0; padding-left:18px; }
.error-box li { font-size:.86rem; color:#dc2626; margin-bottom:4px; }
.error-box li:last-child { margin-bottom:0; }

.action-row { display:flex; align-items:center; gap:14px; margin-top:24px; flex-wrap:wrap; }
.btn-save { padding:11px 28px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:.92rem; font-weight:700; cursor:pointer; }
.btn-save:hover { background:#4f46e5; }
.btn-cancel { padding:11px 22px; background:#f1f5f9; color:#374151; border:none; border-radius:8px; font-size:.92rem; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-cancel:hover { background:#e2e8f0; }
.btn-delete { padding:11px 22px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; margin-left:auto; }
.btn-delete:hover { background:#fee2e2; }

.room-tag { display:inline-flex; align-items:center; gap:4px; background:#6366f1; color:#fff; border-radius:4px; padding:3px 8px; font-size:.8rem; font-weight:700; }
.room-tag.lab { background:#d97706; }

.capacity-info { margin-top:8px; padding:10px 14px; background:#f0fdf4; border-radius:6px; border:1px solid #bbf7d0; font-size:.83rem; color:#166534; display:none; }
</style>

<!-- Header -->
<div class="page-header">
    <h2>&#9998; Edit Timetable Slot</h2>
    <a href="{{ route('hod.view-timetable', ['timetable_id' => $slot->timetable_id]) }}" class="back-link">&#8592; Back to Timetable</a>
</div>

<!-- Slot Info (read-only) -->
<div class="slot-info-card">
    <div class="info-group">
        <span class="lbl">Course</span>
        <span class="val">{{ $slot->courseSection?->course?->name ?? 'N/A' }}</span>
    </div>
    <div class="info-group">
        <span class="lbl">Code</span>
        <span class="val">{{ $slot->courseSection?->course?->code ?? '—' }}</span>
    </div>
    <div class="info-group">
        <span class="lbl">Section</span>
        <span class="val">{{ $slot->courseSection?->section_name ?? '—' }}</span>
    </div>
    <div class="info-group">
        <span class="lbl">Teacher</span>
        <span class="val">{{ $slot->teacher?->name ?? '—' }}</span>
    </div>
    <div class="info-group">
        <span class="lbl">Component</span>
        <span class="val">
            <span class="badge-component badge-{{ $slot->component ?? 'theory' }}">
                {{ ucfirst($slot->component ?? 'Theory') }}
            </span>
        </span>
    </div>
    <div class="info-group">
        <span class="lbl">Enrolled</span>
        <span class="val">{{ $slot->courseSection?->enrolled_students ?? 0 }} students</span>
    </div>
    <div class="info-group">
        <span class="lbl">Current Room</span>
        <span class="val">
            <span class="room-tag {{ $slot->component === 'lab' ? 'lab' : '' }}">
                &#127968; {{ $slot->room?->room_number ?? '—' }}
            </span>
        </span>
    </div>
    <div class="info-group">
        <span class="lbl">Current Time</span>
        <span class="val">
            {{ $slot->day_of_week }}, {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}
        </span>
    </div>
</div>

@if($errors->any())
    <div class="error-box">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Edit Form -->
<div class="form-card">
    <h3>&#128197; Reschedule Slot</h3>

    <form method="POST" action="{{ route('hod.timetable.slot.update', $slot->id) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">

            <!-- Day -->
            <div class="form-group">
                <label for="day_of_week">Day</label>
                <select name="day_of_week" id="day_of_week" required>
                    @foreach($days as $day)
                        <option value="{{ $day }}" {{ old('day_of_week', $slot->day_of_week) === $day ? 'selected' : '' }}>
                            {{ $day }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Time Slot -->
            <div class="form-group">
                <label for="time_slot">Time Slot</label>
                <select name="time_slot" id="time_slot" required onchange="syncTimes(this)">
                    @foreach($timeSlots as $ts)
                        @php
                            $label = substr($ts['start'], 0, 5) . ' – ' . substr($ts['end'], 0, 5);
                            $selected = old('start_time', $slot->start_time) === $ts['start'];
                        @endphp
                        <option value="{{ $ts['start'] }}|{{ $ts['end'] }}" {{ $selected ? 'selected' : '' }}
                            data-start="{{ $ts['start'] }}" data-end="{{ $ts['end'] }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <span class="hint">90-minute periods</span>
            </div>

            <!-- Hidden start/end times synced from time_slot select -->
            <input type="hidden" name="start_time" id="start_time"
                value="{{ old('start_time', $slot->start_time) }}">
            <input type="hidden" name="end_time" id="end_time"
                value="{{ old('end_time', $slot->end_time) }}">

            <!-- Room -->
            <div class="form-group" style="grid-column:1/-1;">
                <label for="room_id">Room</label>
                <select name="room_id" id="room_id" required onchange="showRoomInfo(this)">
                    <option value="">-- Select Room --</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                            data-capacity="{{ $room->capacity }}"
                            data-type="{{ $room->type }}"
                            {{ old('room_id', $slot->room_id) == $room->id ? 'selected' : '' }}>
                            {{ $room->room_number }}
                            ({{ ucfirst(str_replace('_', ' ', $room->type)) }},
                            cap: {{ $room->capacity }})
                        </option>
                    @endforeach
                </select>
                <div class="capacity-info" id="capacity-info"></div>
                <span class="hint">
                    @if($slot->component === 'lab')
                        Lab component — only lab rooms shown as compatible.
                    @else
                        Theory component — classrooms and seminar halls are compatible.
                    @endif
                    Capacity must be &ge; {{ $slot->courseSection?->enrolled_students ?? 0 }} enrolled students.
                </span>
            </div>

        </div>

        <div class="action-row">
            <button type="submit" class="btn-save">&#10003; Save Changes</button>
            <a href="{{ route('hod.view-timetable', ['timetable_id' => $slot->timetable_id]) }}" class="btn-cancel">Cancel</a>

            <!-- Delete slot -->
            <form method="POST"
                  action="{{ route('hod.timetable.slot.destroy', $slot->id) }}"
                  style="margin-left:auto;"
                  onsubmit="return confirm('Delete this slot permanently? This cannot be undone.')">
                @csrf
                <button type="submit" class="btn-delete">&#128465; Delete Slot</button>
            </form>
        </div>
    </form>
</div>

<script>
function syncTimes(sel) {
    const opt = sel.options[sel.selectedIndex];
    const parts = opt.value.split('|');
    document.getElementById('start_time').value = parts[0];
    document.getElementById('end_time').value   = parts[1];
}

function showRoomInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const cap  = opt.getAttribute('data-capacity');
    const type = opt.getAttribute('data-type');
    const info = document.getElementById('capacity-info');
    if (!cap) { info.style.display = 'none'; return; }
    info.style.display = 'block';
    info.textContent   = `Capacity: ${cap} · Type: ${type.replace('_', ' ')}`;
}

// Initialise hidden inputs on page load
(function () {
    syncTimes(document.getElementById('time_slot'));
    const roomSel = document.getElementById('room_id');
    if (roomSel.value) showRoomInfo(roomSel);
})();
</script>

@endsection
