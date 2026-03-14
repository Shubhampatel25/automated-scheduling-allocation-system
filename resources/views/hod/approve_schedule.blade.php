@extends('layouts.dashboard')

@section('title', 'Approve Schedule')
@section('role-label', 'Head of Department')
@section('page-title', 'Approve Schedule')

@section('sidebar-nav')
    @include('hod.partials.sidebar')
@endsection

@section('content')

<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h2 { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.back-link { color:#6366f1; text-decoration:none; font-size:.9rem; }
.back-link:hover { text-decoration:underline; }
.summary-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:28px; }
.stat-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:20px; display:flex; align-items:center; gap:14px; }
.stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.icon-yellow { background:#fefce8; }
.icon-green  { background:#f0fdf4; }
.icon-gray   { background:#f8fafc; }
.stat-card h3 { font-size:1.5rem; font-weight:700; color:#1e293b; margin:0; }
.stat-card p  { font-size:.8rem; color:#64748b; margin:0; }
.section-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:24px; margin-bottom:24px; }
.section-card h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 16px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
.tt-card { border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px; display:flex; align-items:flex-start; gap:20px; flex-wrap:wrap; }
.tt-card:last-child { margin-bottom:0; }
.tt-info { flex:1; min-width:200px; }
.tt-title { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:4px; }
.tt-meta { font-size:.82rem; color:#64748b; display:flex; gap:16px; flex-wrap:wrap; }
.tt-meta span { display:flex; align-items:center; gap:4px; }
.status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
.status-active   { background:#dcfce7; color:#16a34a; }
.status-draft    { background:#fef9c3; color:#854d0e; }
.status-archived { background:#f1f5f9; color:#64748b; }
.conflict-badge  { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; background:#fee2e2; color:#dc2626; }
.tt-actions { display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
.btn-activate { padding:9px 20px; background:#22c55e; color:#fff; border:none; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; }
.btn-activate:hover { background:#16a34a; }
.btn-view { padding:9px 20px; background:#eff6ff; color:#1d4ed8; border:none; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-view:hover { background:#dbeafe; }
.btn-delete { padding:9px 20px; background:#fee2e2; color:#dc2626; border:none; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; }
.btn-delete:hover { background:#fca5a5; }
.active-timetable { border:2px solid #22c55e; }
.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state .empty-icon { font-size:3rem; margin-bottom:12px; }
.info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:14px 18px; margin-bottom:24px; font-size:.88rem; color:#1d4ed8; }
.info-box strong { font-weight:700; }
</style>

<div class="page-header">
    <h2>&#128203; Approve Schedule</h2>
    <a href="{{ route('hod.dashboard') }}" class="back-link">&#8592; Back to Dashboard</a>
</div>

@php
    $draftCount    = $timetables->where('status', 'draft')->count();
    $activeCount   = $timetables->where('status', 'active')->count();
    $archivedCount = $timetables->where('status', 'archived')->count();
@endphp

<!-- Stats -->
<div class="summary-row">
    <div class="stat-card">
        <div class="stat-icon icon-yellow">&#128221;</div>
        <div>
            <h3>{{ $draftCount }}</h3>
            <p>Pending Approval</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">&#9989;</div>
        <div>
            <h3>{{ $activeCount }}</h3>
            <p>Active Timetables</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-gray">&#128196;</div>
        <div>
            <h3>{{ $archivedCount }}</h3>
            <p>Archived</p>
        </div>
    </div>
</div>

@if($draftCount > 0)
    <div class="info-box">
        <strong>Action Required:</strong> {{ $draftCount }} timetable(s) are awaiting approval. Review the details and click "Activate" to make a timetable live.
    </div>
@endif

@if($timetables->count() > 0)

    <!-- Draft Timetables -->
    @if($draftCount > 0)
        <div class="section-card">
            <h3>&#128221; Pending Approval ({{ $draftCount }})</h3>
            @foreach($timetables->where('status', 'draft') as $tt)
                <div class="tt-card">
                    <div class="tt-info">
                        <div class="tt-title">
                            {{ $tt->term }} {{ $tt->year }}
                            @if($tt->semester) &mdash; Semester {{ $tt->semester }} @endif
                        </div>
                        <div class="tt-meta" style="margin-top:6px;">
                            <span>&#128197; {{ $tt->slot_count ?? 0 }} slots</span>
                            @if(($tt->conflict_count ?? 0) > 0)
                                <span><span class="conflict-badge">&#9888; {{ $tt->conflict_count }} conflicts</span></span>
                            @else
                                <span style="color:#16a34a;">&#9989; No conflicts</span>
                            @endif
                            <span>&#128100; By {{ $tt->generatedByUser->username ?? 'N/A' }}</span>
                            <span>&#128336; {{ $tt->generated_at ? $tt->generated_at->format('d M Y, H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="tt-actions">
                        <a href="{{ route('hod.view-timetable', ['timetable_id' => $tt->id]) }}" class="btn-view">
                            &#128065; View
                        </a>
                        <form method="POST" action="{{ route('hod.timetable.activate', $tt->id) }}">
                            @csrf
                            <button type="submit" class="btn-activate"
                                    onclick="return confirm('Activate this timetable? Any current active timetable for the same term/year will be archived.')">
                                &#9989; Activate
                            </button>
                        </form>
                        <form method="POST" action="{{ route('hod.timetable.delete', $tt->id) }}"
                              onsubmit="return confirm('Permanently delete this draft timetable?')">
                            @csrf
                            <button type="submit" class="btn-delete">&#128465; Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Active Timetables -->
    @if($activeCount > 0)
        <div class="section-card">
            <h3>&#9989; Active Timetables ({{ $activeCount }})</h3>
            @foreach($timetables->where('status', 'active') as $tt)
                <div class="tt-card active-timetable">
                    <div class="tt-info">
                        <div class="tt-title">
                            {{ $tt->term }} {{ $tt->year }}
                            @if($tt->semester) &mdash; Semester {{ $tt->semester }} @endif
                            <span class="status-badge status-active" style="margin-left:8px;">Active</span>
                        </div>
                        <div class="tt-meta" style="margin-top:6px;">
                            <span>&#128197; {{ $tt->slot_count ?? 0 }} slots</span>
                            @if(($tt->conflict_count ?? 0) > 0)
                                <span><span class="conflict-badge">&#9888; {{ $tt->conflict_count }} conflicts</span></span>
                            @else
                                <span style="color:#16a34a;">&#9989; No conflicts</span>
                            @endif
                            <span>&#128100; By {{ $tt->generatedByUser->username ?? 'N/A' }}</span>
                            <span>&#128336; {{ $tt->generated_at ? $tt->generated_at->format('d M Y') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="tt-actions">
                        <a href="{{ route('hod.view-timetable', ['timetable_id' => $tt->id]) }}" class="btn-view">
                            &#128065; View
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Archived Timetables -->
    @if($archivedCount > 0)
        <div class="section-card">
            <h3>&#128196; Archived Timetables ({{ $archivedCount }})</h3>
            @foreach($timetables->where('status', 'archived') as $tt)
                <div class="tt-card" style="opacity:.7;">
                    <div class="tt-info">
                        <div class="tt-title">
                            {{ $tt->term }} {{ $tt->year }}
                            @if($tt->semester) &mdash; Semester {{ $tt->semester }} @endif
                            <span class="status-badge status-archived" style="margin-left:8px;">Archived</span>
                        </div>
                        <div class="tt-meta" style="margin-top:6px;">
                            <span>&#128197; {{ $tt->slot_count ?? 0 }} slots</span>
                            <span>&#128336; {{ $tt->generated_at ? $tt->generated_at->format('d M Y') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="tt-actions">
                        <a href="{{ route('hod.view-timetable', ['timetable_id' => $tt->id]) }}" class="btn-view">
                            &#128065; View
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@else
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);">
        <div class="empty-state">
            <div class="empty-icon">&#128203;</div>
            <p>No timetables to review yet.</p>
            <a href="{{ route('hod.generate-timetable') }}"
               style="display:inline-block;margin-top:12px;padding:10px 22px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">
                Generate Timetable
            </a>
        </div>
    </div>
@endif

@endsection
