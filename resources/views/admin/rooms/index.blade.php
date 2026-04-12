@extends('layouts.dashboard')

@section('title', 'Manage Rooms')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Rooms')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Rooms</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Rooms
        </div>
    </div>
    <button class="btn-add" onclick="openModal()">+ Add Room</button>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h3>Room List</h3>
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.rooms.index') }}" id="searchForm" style="display:contents">
            <div class="search-wrap">
                <span class="si">&#128269;</span>
                <input type="text" name="search" id="searchInput" placeholder="Search all records..." value="{{ request('search') }}" oninput="filterTable('roomTable')" autocomplete="off">
            </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px;padding:12px 16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
            <div><label style="font-size:0.78rem;font-weight:600;color:#6b7280;margin-right:4px;">Type</label>
                <select id="fType" onchange="applyFilters()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:0.85rem;color:#374151;background:#fff;cursor:pointer;">
                    <option value="">All Types</option>
                    <option value="classroom">Classroom</option>
                    <option value="lab">Lab</option>
                    <option value="seminar_hall">Seminar Hall</option>
                </select>
            </div>
            <div><label style="font-size:0.78rem;font-weight:600;color:#6b7280;margin-right:4px;">Status</label>
                <select id="fStatus" onchange="applyFilters()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:0.85rem;color:#374151;background:#fff;cursor:pointer;">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <button onclick="clearFilters()" style="padding:6px 14px;background:none;border:1px solid #d1d5db;border-radius:6px;font-size:0.82rem;color:#6b7280;cursor:pointer;">&#10005; Clear</button>
            <span id="filterCount" style="display:none;font-size:0.72rem;background:#4f46e5;color:#fff;border-radius:10px;padding:2px 8px;font-weight:600;"></span>
        </div>
        <table class="data-table" id="roomTable">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Building</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Equipment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr data-type="{{ $room->type }}" data-status="{{ $room->status }}">
                    <td>{{ $room->room_number }}</td>
                    <td>{{ $room->building }}</td>
                    <td>
                        <span class="room-type-badge type-{{ $room->type }}">
                            {{ ucfirst(str_replace('_', ' ', $room->type)) }}
                        </span>
                    </td>
                    <td>{{ $room->capacity }}</td>
                    <td>{{ $room->equipment ?? '—' }}</td>
                    <td>
                        <span class="status {{ $room->status === 'available' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($room->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-tbl-edit" onclick="editRoom({{ $room->id }}, '{{ $room->room_number }}', '{{ addslashes($room->building) }}', '{{ $room->type }}', '{{ $room->capacity }}', '{{ addslashes($room->equipment ?? '') }}', '{{ $room->status }}')">&#9998; Edit</button>
                            <form method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" style="display:contents" onsubmit="return confirm('Delete this room?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-tbl-del">&#128465; Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">No rooms found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="noResults" style="display:none;text-align:center;padding:24px;color:#9ca3af;font-size:0.9rem;">No rooms match the selected filters.</div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Add New Room</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="roomForm" action="{{ route('admin.rooms.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Room Number</label>
                <input type="text" name="room_number" id="fRoom" value="{{ old('room_number') }}" placeholder="e.g. R101" required>
            </div>
            <div class="field-group">
                <label>Building</label>
                <input type="text" name="building" id="fBuilding" value="{{ old('building') }}" placeholder="e.g. Block A" required>
            </div>
            <div class="field-group">
                <label>Room Type</label>
                <select name="type" id="fType" required>
                    <option value="">Select Type</option>
                    <option value="classroom"    {{ old('type') === 'classroom'    ? 'selected' : '' }}>Classroom</option>
                    <option value="lab"          {{ old('type') === 'lab'          ? 'selected' : '' }}>Lab</option>
                    <option value="seminar_hall" {{ old('type') === 'seminar_hall' ? 'selected' : '' }}>Seminar Hall</option>
                </select>
            </div>
            <div class="field-group">
                <label>Capacity</label>
                <select name="capacity" id="fCapacity" required>
                    <option value="">Select Capacity</option>
                    @foreach([20, 30, 40, 50, 60, 80, 100, 150, 200] as $cap)
                        <option value="{{ $cap }}" {{ old('capacity') == $cap ? 'selected' : '' }}>{{ $cap }} seats</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label>Equipment <span style="color:#9ca3af;font-weight:400">(optional)</span></label>
                <input type="text" name="equipment" id="fEquipment" value="{{ old('equipment') }}" placeholder="e.g. Projector, AC">
            </div>
            <div class="field-group">
                <label>Status</label>
                <select name="status" id="fStatus" required>
                    <option value="available"    {{ old('status', 'available') === 'available'    ? 'selected' : '' }}>Available</option>
                    <option value="unavailable"  {{ old('status') === 'unavailable'  ? 'selected' : '' }}>Unavailable</option>
                    <option value="maintenance"  {{ old('status') === 'maintenance'  ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">+ Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.rooms.store') }}";

function openModal() {
    document.getElementById('roomForm').action  = storeUrl;
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('roomForm').reset();
    document.getElementById('modalTitle').textContent = 'Add New Room';
    document.getElementById('modalBackdrop').classList.add('show');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editRoom(id, roomNo, building, type, capacity, equipment, status) {
    document.getElementById('roomForm').action   = `/admin/rooms/${id}`;
    document.getElementById('formMethod').value  = 'PUT';
    document.getElementById('fRoom').value       = roomNo;
    document.getElementById('fBuilding').value   = building;
    document.getElementById('fType').value       = type;
    document.getElementById('fCapacity').value   = capacity;
    document.getElementById('fEquipment').value  = equipment;
    document.getElementById('fStatus').value     = status;
    document.getElementById('modalTitle').textContent = 'Edit Room';
    document.getElementById('modalBackdrop').classList.add('show');
}

function filterTable() { applyFilters(); }
function applyFilters() {
    const query  = document.getElementById('searchInput').value.toLowerCase().trim();
    const type   = document.getElementById('fType').value;
    const status = document.getElementById('fStatus').value;
    let count = 0;
    document.querySelectorAll('#roomTable tbody tr').forEach(row => {
        const ok = (!query  || row.textContent.toLowerCase().includes(query))
                && (!type   || row.dataset.type === type)
                && (!status || row.dataset.status === status);
        row.style.display = ok ? '' : 'none';
        if (ok) count++;
    });
    document.getElementById('noResults').style.display = count === 0 ? '' : 'none';
    const active = [type, status, query].filter(Boolean).length;
    const badge  = document.getElementById('filterCount');
    badge.textContent = active + ' filter' + (active > 1 ? 's' : '') + ' active';
    badge.style.display = active > 0 ? 'inline' : 'none';
}
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('fType').value = '';
    document.getElementById('fStatus').value = '';
    applyFilters();
}
document.addEventListener('DOMContentLoaded', applyFilters);

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalBackdrop').classList.add('show');
});
@elseif(request('add') == '1')
document.addEventListener('DOMContentLoaded', () => openModal());
@endif
</script>
@endpush
