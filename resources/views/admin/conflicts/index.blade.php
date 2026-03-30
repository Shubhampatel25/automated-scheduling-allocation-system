@extends('layouts.dashboard')

@section('title', 'Conflicts')
@section('role-label', 'Admin Panel')
@section('page-title', 'Conflicts')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
.filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
    margin-bottom: 0;
}
.filter-bar .field-group {
    margin-bottom: 0;
    min-width: 160px;
}
.filter-bar .field-group select,
.filter-bar .field-group input {
    padding: 8px 12px;
    font-size: 0.85rem;
}
.btn-filter {
    padding: 9px 18px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    font-weight: 500;
    font-family: inherit;
}
.btn-filter:hover { background: #4f46e5; }
.btn-reset {
    padding: 9px 14px;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-reset:hover { background: #e5e7eb; }
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.summary-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    text-align: center;
}
.summary-card h3 { font-size: 1.6rem; font-weight: 700; color: #1f2937; margin: 0 0 4px; }
.summary-card p  { font-size: 0.8rem; color: #6b7280; margin: 0; }
.summary-card.red h3   { color: #ef4444; }
.summary-card.green h3 { color: #10b981; }

.badge-danger  { background: #fee2e2; color: #b91c1c; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.badge-warning { background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.badge-primary { background: #ede9fe; color: #6d28d9; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.status-unresolved { background: #fee2e2; color: #b91c1c; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.status-resolved   { background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
</style>
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Conflicts</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Conflicts
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card red">
        <h3>{{ $unresolvedCount }}</h3>
        <p>Unresolved</p>
    </div>
    <div class="summary-card green">
        <h3>{{ $resolvedCount }}</h3>
        <p>Resolved</p>
    </div>
    <div class="summary-card">
        <h3>{{ $unresolvedCount + $resolvedCount }}</h3>
        <p>Total Conflicts</p>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card" style="margin-bottom:20px">
    <div class="card-body" style="padding:18px 20px">
        <form method="GET" action="{{ route('admin.conflicts') }}">
            <div class="filter-bar">
                <div class="field-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                        <option value="resolved"   {{ request('status') === 'resolved'   ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Conflict Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="room_conflict"    {{ request('type') === 'room_conflict'    ? 'selected' : '' }}>Room Conflict</option>
                        <option value="teacher_conflict" {{ request('type') === 'teacher_conflict' ? 'selected' : '' }}>Teacher Conflict</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Search Description</label>
                    <div class="search-wrap" style="width:220px">
                        <span class="si">&#128269;</span>
                        <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="{{ route('admin.conflicts') }}" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Conflicts Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Conflict List</h3>
        <span style="font-size:0.82rem;color:#6b7280">{{ $conflicts->total() }} records found</span>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Room</th>
                    <th>Day &amp; Time</th>
                    <th>Detected</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conflicts as $i => $conflict)
                @php
                    $ctype = $conflict->conflict_type ?? '';
                    $slot  = $conflict->slot1;
                @endphp
                <tr>
                    <td>{{ $conflicts->firstItem() + $i }}</td>
                    <td>
                        @if(str_contains($ctype, 'room'))
                            <span class="badge-danger">{{ ucfirst(str_replace('_', ' ', $ctype)) }}</span>
                        @elseif(str_contains($ctype, 'teacher'))
                            <span class="badge-warning">{{ ucfirst(str_replace('_', ' ', $ctype)) }}</span>
                        @else
                            <span class="badge-primary">{{ ucfirst(str_replace('_', ' ', $ctype)) }}</span>
                        @endif
                    </td>
                    <td style="max-width:220px;white-space:normal;font-size:0.82rem">{{ $conflict->description ?? '—' }}</td>
                    <td>{{ $conflict->timetable->department->name ?? '—' }}</td>
                    <td>{{ $slot->courseSection->course->name ?? '—' }}</td>
                    <td>{{ $slot->teacher->name ?? '—' }}</td>
                    <td>{{ $slot->room->room_number ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        @if($slot)
                            {{ $slot->day_of_week }}<br>
                            <span style="color:#6b7280;font-size:0.78rem">{{ $slot->start_time }} – {{ $slot->end_time }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="white-space:nowrap;font-size:0.82rem">
                        {{ $conflict->detected_at ? $conflict->detected_at->format('d M Y') : '—' }}
                    </td>
                    <td>
                        <span class="status-{{ $conflict->status ?? 'unresolved' }}">
                            {{ ucfirst($conflict->status ?? 'Unresolved') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:32px;color:#9ca3af">
                        No conflicts found{{ request()->hasAny(['status','type','search']) ? ' for the selected filters' : '' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $conflicts->links() }}</div>
    </div>
</div>
@endsection
