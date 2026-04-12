@extends('layouts.dashboard')

@section('title', 'My Courses')
@section('role-label', 'Student Panel')
@section('page-title', 'My Courses')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    /* ── Action buttons ── */
    .btn-drop       { background:#ef4444;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:0.8rem; }
    .btn-drop:hover { background:#dc2626; }

    /* ── Enrollment type badges ── */
    .badge-dept    { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-retake  { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;padding:2px 9px;border-radius:12px;font-size:0.72rem;font-weight:700; }
    .badge-regular { background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:2px 9px;border-radius:12px;font-size:0.72rem;font-weight:600; }

    /* ── Result badges ── */
    .rb-pass        { background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700; }
    .rb-retake-pass { background:#ccfbf1;color:#0f766e;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700;border:1px solid #99f6e4; }
    .rb-fail        { background:#fee2e2;color:#991b1b;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700; }
    .rb-backlog     { background:#fff7ed;color:#c2410c;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700;border:1px solid #fed7aa; }

    /* ── Semester section header ── */
    .sem-section { margin-bottom:24px; }
    .sem-section-header {
        display:flex;align-items:center;gap:10px;
        padding:9px 14px;
        background:linear-gradient(90deg,#f0f4ff 0%,#fafafa 100%);
        border-left:4px solid #6366f1;
        border-radius:0 8px 8px 0;
        margin-bottom:10px;
        font-weight:800;font-size:0.92rem;color:#1e1b4b;
    }
    .sem-stats { font-size:0.75rem;font-weight:500;color:#6b7280;margin-left:auto;display:flex;gap:12px; }
    .sem-stats .s-pass  { color:#065f46;font-weight:700; }
    .sem-stats .s-fail  { color:#991b1b;font-weight:700; }
    .sem-stats .s-bl    { color:#c2410c;font-weight:700; }

    /* ── Filter buttons ── */
    .filter-sem-btn { padding:5px 14px;border:1px solid #d1d5db;border-radius:20px;background:#fff;cursor:pointer;font-size:0.8rem;font-weight:500;color:#374151;transition:all .15s; }
    .filter-sem-btn.active { background:#6366f1;color:#fff;border-color:#6366f1;font-weight:600; }
    .filter-sem-btn:hover:not(.active) { background:#f3f4f6; }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════
     SECTION 1 — Currently Enrolled
══════════════════════════════════════════════════════ --}}
<div class="dashboard-card">
    <div class="card-header">
        <h3>My Courses &mdash; Semester {{ $semester }}</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="badge badge-primary">{{ $enrolledCourses->count() }} Enrolled</span>
            @if($enrolledCourses->where('isRetake', true)->count() > 0)
                <span class="badge-retake">&#8635; {{ $enrolledCourses->where('isRetake', true)->count() }} Retake</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($enrolledCourses->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Credits</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrolledCourses as $course)
                    <tr @if($course->isRetake) style="background:#fffbeb;" @endif>
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $course->name ?? 'N/A' }}</td>
                        <td>
                            @if($course->isRetake)
                                <span class="badge-retake">&#8635; Retake</span>
                            @else
                                <span class="badge-regular">&#10003; Regular</span>
                            @endif
                        </td>
                        <td><span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span></td>
                        <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                        <td>
                            @if($course->sectionInfo)
                                Sec {{ $course->sectionInfo->section_number }} &bull; {{ $course->sectionInfo->term }} {{ $course->sectionInfo->year }}
                            @else N/A @endif
                        </td>
                        <td>{{ $course->teacherName ?? 'TBA' }}</td>
                        <td>
                            <form method="POST" action="{{ route('student.courses.drop', $course->registrationId) }}"
                                  onsubmit="return confirm('Drop {{ addslashes($course->name) }}?')">
                                @csrf
                                <button type="submit" class="btn-drop">Drop</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128218;</div>
                <p>No courses enrolled yet. <a href="{{ route('student.register-courses') }}" style="color:#4f46e5">Register for courses &rarr;</a></p>
            </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 2 — Previous Results (grouped by semester)
══════════════════════════════════════════════════════ --}}
@if($completedHistory->isNotEmpty())
<div class="dashboard-card card-mt">
    @php
        $total       = $historyStats['total']       ?? 0;
        $passed      = $historyStats['passed']      ?? 0;
        $retakePass  = $historyStats['retake_pass'] ?? 0;
        $failed      = $historyStats['failed']      ?? 0;
        $backlogClr  = $historyStats['backlog_clear'] ?? 0;
        $semKeys     = $completedHistory->keys()->filter()->values();
    @endphp

    <div class="card-header" style="flex-wrap:wrap;gap:10px;">
        <h3>Previous Results</h3>
        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;font-size:0.8rem;">
            <span style="color:#374151;font-weight:600;">{{ $total }} total</span>
            @if($passed > 0)      <span style="color:#065f46;font-weight:700;">&#10003; {{ $passed }} passed</span> @endif
            @if($retakePass > 0)  <span style="color:#0f766e;font-weight:700;">&#8635; {{ $retakePass }} retake pass</span> @endif
            @if($failed > 0)      <span style="color:#991b1b;font-weight:700;">&#10007; {{ $failed }} failed</span> @endif
            @if($backlogClr > 0)  <span style="color:#c2410c;font-weight:700;">&#128218; {{ $backlogClr }} backlog cleared</span> @endif
        </div>
    </div>

    <div class="card-body">

        {{-- Semester filter --}}
        @if($semKeys->count() > 1)
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:0.72rem;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-right:4px;">Semester:</span>
            <button class="filter-sem-btn active" onclick="filterBySemester('all', this)">All</button>
            @foreach($semKeys as $sk)
                <button class="filter-sem-btn" onclick="filterBySemester('{{ $sk }}', this)">Sem {{ $sk }}</button>
            @endforeach
        </div>
        @endif

        {{-- Results grouped by semester --}}
        <div id="historyContainer">
        @foreach($completedHistory as $semKey => $semRegs)
            @php
                $semPasses   = $semRegs->filter(fn($r) => $r->result === 'pass')->values();
                $semFails    = $semRegs->filter(fn($r) => $r->result === 'fail')->values();
                $semPassCnt  = $semPasses->count();
                $semFailCnt  = $semFails->count();
                $semBlCnt    = $semFails->where('isBacklogCleared', true)->count();
                $semActFail  = $semFailCnt - $semBlCnt;
            @endphp
            <div class="sem-section" data-sem="{{ $semKey }}">
                <div class="sem-section-header">
                    <span>Semester {{ $semKey > 0 ? $semKey : '—' }}</span>
                    <span style="font-size:0.73rem;font-weight:400;color:#6b7280;">&bull; {{ $semRegs->count() }} subjects</span>
                    <div class="sem-stats">
                        @if($semPassCnt > 0)  <span class="s-pass">&#10003; {{ $semPassCnt }} passed</span> @endif
                        @if($semActFail > 0)  <span class="s-fail">&#10007; {{ $semActFail }} failed</span> @endif
                        @if($semBlCnt > 0)    <span class="s-bl">&#128218; {{ $semBlCnt }} backlog cleared</span> @endif
                    </div>
                </div>

                <table class="data-table" style="margin-bottom:6px;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Sem</th>
                            <th>Section</th>
                            <th>Term</th>
                            <th style="text-align:center;">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Passed rows --}}
                        @foreach($semPasses as $reg)
                        @php $c = $reg->courseSection?->course; $sec = $reg->courseSection; @endphp
                        <tr>
                            <td><strong>{{ $c?->code ?? 'N/A' }}</strong></td>
                            <td>
                                {{ $c?->name ?? 'N/A' }}
                                @if($reg->isRetakePass)
                                    <span style="margin-left:5px;font-size:0.7rem;background:#ccfbf1;color:#0f766e;padding:1px 7px;border-radius:10px;font-weight:700;">&#8635; Retake</span>
                                @endif
                            </td>
                            <td>
                                @if($c?->semester) <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:0.75rem;">{{ $c->semester }}</span>
                                @else <span style="color:#9ca3af">—</span> @endif
                            </td>
                            <td>@if($sec) Sec {{ $sec->section_number }} @else <span style="color:#9ca3af">—</span> @endif</td>
                            <td style="font-size:0.83rem;color:#6b7280;">@if($sec) {{ $sec->term }} {{ $sec->year }} @else <span style="color:#9ca3af">—</span> @endif</td>
                            <td style="text-align:center;">
                                @if($reg->isRetakePass)
                                    <span class="rb-retake-pass">&#8635; Retake Pass</span>
                                @else
                                    <span class="rb-pass">&#10003; Pass</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                        {{-- Failed / Backlog rows --}}
                        @foreach($semFails as $reg)
                        @php $c = $reg->courseSection?->course; $sec = $reg->courseSection; @endphp
                        <tr @if($reg->isBacklogCleared) style="opacity:0.65;" @endif>
                            <td><strong>{{ $c?->code ?? 'N/A' }}</strong></td>
                            <td>
                                @if($reg->isBacklogCleared)
                                    <span style="text-decoration:line-through;color:#9ca3af;">{{ $c?->name ?? 'N/A' }}</span>
                                @else
                                    {{ $c?->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>
                                @if($c?->semester) <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:0.75rem;">{{ $c->semester }}</span>
                                @else <span style="color:#9ca3af">—</span> @endif
                            </td>
                            <td>@if($sec) Sec {{ $sec->section_number }} @else <span style="color:#9ca3af">—</span> @endif</td>
                            <td style="font-size:0.83rem;color:#6b7280;">@if($sec) {{ $sec->term }} {{ $sec->year }} @else <span style="color:#9ca3af">—</span> @endif</td>
                            <td style="text-align:center;">
                                @if($reg->isBacklogCleared)
                                    <span class="rb-backlog">&#10003; Backlog Cleared</span>
                                @else
                                    <span class="rb-fail">&#10007; Fail</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
        </div>

    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function filterBySemester(sem, btn) {
    document.querySelectorAll('.filter-sem-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('#historyContainer .sem-section').forEach(function(section) {
        section.style.display = (sem === 'all' || section.dataset.sem === String(sem)) ? '' : 'none';
    });
}
</script>
@endpush
