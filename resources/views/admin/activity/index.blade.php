@extends('layouts.dashboard')

@section('title', 'Activity Logs')
@section('role-label', 'Admin Panel')
@section('page-title', 'Activity Logs')

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

.activity-dot-sm {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6366f1;
    display: inline-block;
    margin-right: 6px;
    flex-shrink: 0;
}
.entity-badge {
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 500;
    background: #ede9fe;
    color: #6d28d9;
}
.log-detail {
    font-size: 0.78rem;
    color: #6b7280;
    max-width: 260px;
    white-space: normal;
}
</style>
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Activity Logs</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Activity Logs
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card" style="margin-bottom:20px">
    <div class="card-body" style="padding:18px 20px">
        <form method="GET" action="{{ route('admin.activity') }}">
            <div class="filter-bar">
                @if($entityTypes->isNotEmpty())
                <div class="field-group">
                    <label>Entity Type</label>
                    <select name="entity_type">
                        <option value="">All Types</option>
                        @foreach($entityTypes as $type)
                            <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="field-group">
                    <label>Search</label>
                    <div class="search-wrap" style="width:240px">
                        <span class="si">&#128269;</span>
                        <input type="text" name="search" placeholder="Action, entity, details..." value="{{ request('search') }}">
                    </div>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="{{ route('admin.activity') }}" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Log List</h3>
        <span style="font-size:0.82rem;color:#6b7280">{{ $logs->total() }} records found</span>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Entity Type</th>
                    <th>Entity ID</th>
                    <th>Details</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $i => $log)
                <tr>
                    <td>{{ $logs->firstItem() + $i }}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center">
                            <span class="activity-dot-sm"></span>
                            {{ $log->user->username ?? 'System' }}
                        </span>
                    </td>
                    <td style="font-size:0.82rem;color:#6b7280">
                        {{ $log->user ? ucfirst($log->user->role) : '—' }}
                    </td>
                    <td style="font-size:0.85rem">{{ $log->action ?? '—' }}</td>
                    <td>
                        @if($log->entity_type)
                            <span class="entity-badge">{{ ucfirst($log->entity_type) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:0.82rem;color:#6b7280">{{ $log->entity_id ?? '—' }}</td>
                    <td class="log-detail">{{ $log->details ?? '—' }}</td>
                    <td style="white-space:nowrap;font-size:0.82rem;color:#6b7280">
                        {{ $log->created_at ? $log->created_at->format('d M Y') : '—' }}<br>
                        <span style="font-size:0.75rem">{{ $log->created_at ? $log->created_at->format('H:i') : '' }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:#9ca3af">
                        No activity logs found{{ request()->hasAny(['search','entity_type']) ? ' for the selected filters' : '' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
