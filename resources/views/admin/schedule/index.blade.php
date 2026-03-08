@extends('layouts.dashboard')

@section('title', 'Schedule View')
@section('role-label', 'Admin Panel')
@section('page-title', 'Schedule View')

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
    margin-bottom: 20px;
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
.status-active    { background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.status-draft     { background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.status-published { background: #dbeafe; color: #1d4ed8; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.status-inactive  { background: #f3f4f6; color: #6b7280; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
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
</style>
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Schedule View</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Schedule View
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <h3>{{ $timetables->total() }}</h3>
        <p>Total Timetables</p>
    </div>
    <div class="summary-card">
        <h3>{{ $timetables->getCollection()->where('status', 'active')->count() }}</h3>
        <p>Active</p>
    </div>
    <div class="summary-card">
        <h3>{{ $timetables->getCollection()->where('status', 'draft')->count() }}</h3>
        <p>Draft</p>
    </div>
    <div class="summary-card">
        <h3>{{ $timetables->getCollection()->sum('slot_count') }}</h3>
        <p>Slots (This Page)</p>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card" style="margin-bottom:20px">
    <div class="card-body" style="padding:18px 20px">
        <form method="GET" action="{{ route('admin.schedule') }}">
            <div class="filter-bar">
                <div class="field-group">
                    <label>Department</label>
                    <select name="department_id">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Year</label>
                    <input type="number" name="year" placeholder="e.g. 2025" value="{{ request('year') }}" min="2020" max="2099">
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="{{ route('admin.schedule') }}" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Timetable Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3>Timetable List</h3>
        <span style="font-size:0.82rem;color:#6b7280">{{ $timetables->total() }} records found</span>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Slots</th>
                    <th>Conflicts</th>
                    <th>Generated By</th>
                    <th>Generated At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($timetables as $i => $tt)
                <tr>
                    <td>{{ $timetables->firstItem() + $i }}</td>
                    <td>{{ $tt->department->name ?? 'N/A' }}</td>
                    <td>{{ $tt->term ?? '—' }}</td>
                    <td>{{ $tt->year ?? '—' }}</td>
                    <td>{{ $tt->semester ?? '—' }}</td>
                    <td>{{ $tt->slot_count }}</td>
                    <td>
                        @if($tt->conflicts_count > 0)
                            <span style="color:#ef4444;font-weight:600">{{ $tt->conflicts_count }}</span>
                        @else
                            <span style="color:#10b981">0</span>
                        @endif
                    </td>
                    <td>{{ $tt->generatedByUser->username ?? '—' }}</td>
                    <td>{{ $tt->generated_at ? $tt->generated_at->format('d M Y, H:i') : '—' }}</td>
                    <td>
                        @php $s = $tt->status ?? 'draft'; @endphp
                        <span class="status-{{ $s }}">{{ ucfirst($s) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:32px;color:#9ca3af">
                        No timetables found{{ request()->hasAny(['department_id','status','year']) ? ' for the selected filters' : '' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px">{{ $timetables->links() }}</div>
    </div>
</div>
@endsection
