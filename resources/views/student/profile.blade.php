@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('role-label', 'Student Panel')
@section('page-title', 'My Profile')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    /* ── Result badges ── */
    .rb-pass         { background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700; }
    .rb-retake-pass  { background:#ccfbf1;color:#0f766e;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700;border:1px solid #99f6e4; }
    .rb-fail         { background:#fee2e2;color:#991b1b;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700; }
    .rb-backlog      { background:#fff7ed;color:#c2410c;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700;border:1px solid #fed7aa; }
    .rb-na           { background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:600; }

    /* ── Filter buttons ── */
    .filter-btn {
        padding:6px 18px;border:1px solid #d1d5db;border-radius:20px;background:#fff;
        cursor:pointer;font-size:0.82rem;font-weight:500;color:#374151;transition:all .15s;
    }
    .filter-btn.active { background:#4f46e5;color:#fff;border-color:#4f46e5;font-weight:600; }
    .filter-btn:hover:not(.active) { background:#f3f4f6; }

    .filter-sem-btn {
        padding:5px 14px;border:1px solid #d1d5db;border-radius:20px;background:#fff;
        cursor:pointer;font-size:0.8rem;font-weight:500;color:#374151;transition:all .15s;
    }
    .filter-sem-btn.active { background:#6366f1;color:#fff;border-color:#6366f1;font-weight:600; }
    .filter-sem-btn:hover:not(.active) { background:#f3f4f6; }

    /* ── Prerequisite alert ── */
    .prereq-alert { background:#fff7ed;border:2px solid #f97316;border-radius:10px;padding:16px 20px;margin-bottom:20px; }
    .prereq-alert-title { font-size:1rem;font-weight:800;color:#c2410c;margin:0 0 10px;display:flex;align-items:center;gap:8px; }
    .prereq-item { display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid #fed7aa;font-size:0.875rem; }
    .prereq-item:last-child { border-bottom:none; }

    /* ── Profile info grid ── */
    .profile-info-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:20px 32px; }
    .profile-info-item { display:flex;flex-direction:column;gap:4px; }
    .profile-info-label { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af; }
    .profile-info-value { font-size:0.95rem;font-weight:600;color:#111827; }

    /* ── Semester sections ── */
    .sem-section { margin-bottom:28px; }
    .sem-section-header {
        display:flex;align-items:center;gap:10px;
        padding:10px 16px;
        background:linear-gradient(90deg,#f0f4ff 0%,#fafafa 100%);
        border-left:4px solid #6366f1;
        border-radius:0 8px 8px 0;
        margin-bottom:12px;
        font-weight:800;font-size:0.95rem;color:#1e1b4b;
    }
    .sem-section-header .sem-stats { font-size:0.78rem;font-weight:500;color:#6b7280;margin-left:auto;display:flex;gap:14px; }
    .sem-stats .s-pass { color:#065f46;font-weight:700; }
    .sem-stats .s-fail { color:#991b1b;font-weight:700; }
    .sem-stats .s-backlog { color:#c2410c;font-weight:700; }

    /* ── Result sub-blocks ── */
    .result-block { margin-bottom:14px; }
    .result-block-header {
        display:flex;align-items:center;gap:8px;
        padding:7px 14px;border-radius:8px 8px 0 0;
        font-size:0.8rem;font-weight:700;
    }
    .result-block-header.pass-header  { background:#f0fdf4;color:#065f46;border:1px solid #d1fae5;border-bottom:none; }
    .result-block-header.fail-header  { background:#fff1f2;color:#991b1b;border:1px solid #fee2e2;border-bottom:none; }
    .result-block table.data-table    { border-radius:0 0 8px 8px;border-top:none; }

    /* ── Table cell alignment ── */
    .results-tbl th, .results-tbl td { vertical-align:middle; }
    .results-tbl td:nth-child(1) { font-weight:600; }
    .results-tbl td:nth-child(3),
    .results-tbl td:nth-child(5),
    .results-tbl td:nth-child(6) { text-align:center; }
    .results-tbl th:nth-child(3),
    .results-tbl th:nth-child(5),
    .results-tbl th:nth-child(6) { text-align:center; }
</style>
@endpush

@section('content')

{{-- ── Prerequisite Alert ── --}}
@if($prerequisiteAlerts->isNotEmpty())
<div class="prereq-alert">
    <div class="prereq-alert-title">
        <span style="font-size:1.3rem;">&#9888;</span>
        <strong>YOU HAVE NOT PASSED REQUIRED PREREQUISITE COURSES FOR NEXT SEMESTER REGISTRATION</strong>
    </div>
    <p style="margin:0 0 10px;font-size:0.875rem;color:#7c2d12;">
        The following Semester {{ is_numeric($semester) ? $semester + 1 : '' }} courses require prerequisites you have not yet passed:
    </p>
    @foreach($prerequisiteAlerts as $alert)
    <div class="prereq-item">
        <span style="color:#f97316;font-size:1rem;margin-top:1px;">&#10007;</span>
        <div>
            <strong style="color:#111827;">{{ $alert['course'] }}</strong>
            <span style="color:#6b7280;font-size:0.8rem;"> ({{ $alert['course_code'] }})</span>
            <span style="color:#c2410c;font-weight:700;"> &mdash; requires: {{ $alert['requires'] }}</span>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Profile Info ── --}}
<div class="dashboard-card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3>My Profile</h3>
    </div>
    <div class="card-body">
        <div class="profile-info-grid">
            <div class="profile-info-item">
                <div class="profile-info-label">Name</div>
                <div class="profile-info-value">{{ Auth::user()->username }}</div>
            </div>
            <div class="profile-info-item">
                <div class="profile-info-label">Email</div>
                <div class="profile-info-value">{{ Auth::user()->email }}</div>
            </div>
            <div class="profile-info-item">
                <div class="profile-info-label">Department</div>
                <div class="profile-info-value">{{ $department }}</div>
            </div>
            <div class="profile-info-item">
                <div class="profile-info-label">Current Semester</div>
                <div class="profile-info-value">{{ $semester }}</div>
            </div>
            <div class="profile-info-item">
                <div class="profile-info-label">Enrolled Credits</div>
                <div class="profile-info-value">{{ $totalCredits }}</div>
            </div>
            <div class="profile-info-item">
                <div class="profile-info-label">Fee Status</div>
                <div class="profile-info-value">
                    @if($feeRecord)
                        @php
                            $statusColors = [
                                'paid'    => 'background:#d1fae5;color:#065f46',
                                'pending' => 'background:#fef3c7;color:#92400e',
                                'overdue' => 'background:#fee2e2;color:#991b1b',
                                'partial' => 'background:#dbeafe;color:#1e40af',
                            ];
                            $sc = $statusColors[$feeRecord->status] ?? 'background:#f3f4f6;color:#6b7280';
                        @endphp
                        <span style="{{ $sc }};padding:3px 12px;border-radius:12px;font-size:0.8rem;font-weight:600;">
                            {{ ucfirst($feeRecord->status) }}
                        </span>
                    @else
                        <span style="background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:12px;font-size:0.8rem;font-weight:600;">No Record</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Course Results History ── --}}
<div class="dashboard-card">
    @php
        $passCount         = $completedResults->where('result', 'pass')->count();
        $failCount         = $completedResults->where('result', 'fail')->count();
        $backlogCount      = $completedResults->where('isBacklogCleared', true)->count();
        $activeFailCount   = $failCount - $backlogCount;
        $retakePassCount   = $completedResults->where('isRetakePass', true)->count();
        $totalDone         = $completedResults->count();

        // Group by course semester for the grouped display
        $bySemester = $completedResults
            ->groupBy(fn($r) => $r->courseSection?->course?->semester ?? 0)
            ->sortKeys();

        // Unique semesters for the filter
        $semesterOptions = $bySemester->keys()->filter()->values();
    @endphp

    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <h3>My Course Results</h3>
        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;font-size:0.8rem;">
            <span style="color:#374151;font-weight:600;">{{ $totalDone }} total</span>
            <span style="color:#065f46;font-weight:700;">&#10003; {{ $passCount }} passed</span>
            @if($retakePassCount > 0)
                <span style="color:#0f766e;font-weight:700;">&#8635; {{ $retakePassCount }} retake pass</span>
            @endif
            <span style="color:#991b1b;font-weight:700;">&#10007; {{ $activeFailCount }} failed</span>
            @if($backlogCount > 0)
                <span style="color:#c2410c;font-weight:700;">&#128218; {{ $backlogCount }} backlog cleared</span>
            @endif
        </div>
    </div>

    <div class="card-body">

        {{-- ── Semester filter ── --}}
        @if($semesterOptions->count() > 0)
        <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:0.75rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-right:4px;">Semester:</span>
            <button class="filter-sem-btn active" onclick="filterBySemester('all', this)">All Semesters</button>
            @foreach($semesterOptions as $sem)
                <button class="filter-sem-btn" onclick="filterBySemester('{{ $sem }}', this)">Semester {{ $sem }}</button>
            @endforeach
        </div>
        @endif

        {{-- ── Result filter ── --}}
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:0.75rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-right:4px;">Result:</span>
            <button class="filter-btn filter-btn-result active" onclick="filterResults('all', this)">All ({{ $totalDone }})</button>
            <button class="filter-btn filter-btn-result" onclick="filterResults('pass', this)">&#10003; Pass ({{ $passCount }})</button>
            <button class="filter-btn filter-btn-result" onclick="filterResults('fail', this)">&#10007; Fail ({{ $activeFailCount }})</button>
            @if($backlogCount > 0)
            <button class="filter-btn filter-btn-result" onclick="filterResults('backlog', this)" style="border-color:#fed7aa;color:#c2410c;">
                &#128218; Backlog Cleared ({{ $backlogCount }})
            </button>
            @endif
        </div>

        @if($completedResults->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">&#128203;</div>
                <p>No completed course results yet.</p>
            </div>
        @else

        {{-- ── Results grouped by semester ── --}}
        <div id="resultsContainer">
        @foreach($bySemester as $semKey => $semRegs)
            @php
                $semPasses = $semRegs->filter(fn($r) => $r->result === 'pass')->values();
                $semFails  = $semRegs->filter(fn($r) => $r->result === 'fail')->values();
                $semPassCount    = $semPasses->count();
                $semFailCount    = $semFails->count();
                $semBacklogCount = $semFails->where('isBacklogCleared', true)->count();
                $semActiveFailCount = $semFailCount - $semBacklogCount;
            @endphp

            <div class="sem-section" data-sem="{{ $semKey }}">
                <div class="sem-section-header">
                    <span>Semester {{ $semKey > 0 ? $semKey : '—' }}</span>
                    <span style="font-size:0.75rem;color:#6b7280;font-weight:400;">&bull; {{ $semRegs->count() }} subjects</span>
                    <div class="sem-stats">
                        @if($semPassCount > 0)
                            <span class="s-pass">&#10003; {{ $semPassCount }} passed</span>
                        @endif
                        @if($semActiveFailCount > 0)
                            <span class="s-fail">&#10007; {{ $semActiveFailCount }} failed</span>
                        @endif
                        @if($semBacklogCount > 0)
                            <span class="s-backlog">&#128218; {{ $semBacklogCount }} backlog cleared</span>
                        @endif
                    </div>
                </div>

                {{-- Passed block --}}
                @if($semPasses->isNotEmpty())
                <div class="result-block" data-block="pass">
                    <div class="result-block-header pass-header">
                        &#10003; Passed Subjects ({{ $semPassCount }})
                    </div>
                    <table class="data-table results-tbl" style="border-radius:0 0 8px 8px;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th style="text-align:center;">Sem</th>
                                <th>Section</th>
                                <th style="text-align:center;">Term</th>
                                <th style="text-align:center;">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($semPasses as $reg)
                            @php
                                $course  = $reg->courseSection?->course;
                                $section = $reg->courseSection;
                                $dtype   = $reg->isRetakePass ? 'retake-pass' : 'pass';
                            @endphp
                            <tr data-type="{{ $dtype }}" data-semester="{{ $semKey }}">
                                <td><strong>{{ $course?->code ?? 'N/A' }}</strong></td>
                                <td>
                                    {{ $course?->name ?? 'N/A' }}
                                    @if($reg->isRetakePass)
                                        <span style="margin-left:6px;font-size:0.72rem;background:#ccfbf1;color:#0f766e;padding:1px 7px;border-radius:10px;font-weight:700;">&#8635; Retake</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($course?->semester)
                                        <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:0.75rem;">
                                            {{ $course->semester }}
                                        </span>
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
                                <td>
                                    @if($section)
                                        Sec {{ $section->section_number }}
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
                                <td style="text-align:center;font-size:0.85rem;color:#6b7280;">
                                    @if($section) {{ $section->term }} {{ $section->year }}
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($reg->isRetakePass)
                                        <span class="rb-retake-pass">&#8635; Retake Pass</span>
                                    @else
                                        <span class="rb-pass">&#10003; Pass</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Failed / Backlog block --}}
                @if($semFails->isNotEmpty())
                <div class="result-block" data-block="fail" style="{{ $semPasses->isNotEmpty() ? 'margin-top:10px;' : '' }}">
                    <div class="result-block-header fail-header">
                        &#10007; Failed / Backlogs ({{ $semFailCount }})
                        @if($semBacklogCount > 0)
                            &nbsp;<span style="font-size:0.73rem;font-weight:600;color:#c2410c;">&mdash; {{ $semBacklogCount }} backlog cleared</span>
                        @endif
                    </div>
                    <table class="data-table results-tbl" style="border-radius:0 0 8px 8px;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th style="text-align:center;">Sem</th>
                                <th>Section</th>
                                <th style="text-align:center;">Term</th>
                                <th style="text-align:center;">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($semFails as $reg)
                            @php
                                $course  = $reg->courseSection?->course;
                                $section = $reg->courseSection;
                                $dtype   = $reg->isBacklogCleared ? 'backlog-cleared' : 'fail-active';
                            @endphp
                            <tr data-type="{{ $dtype }}" data-semester="{{ $semKey }}"
                                @if($reg->isBacklogCleared) style="opacity:0.75;" @endif>
                                <td><strong>{{ $course?->code ?? 'N/A' }}</strong></td>
                                <td>
                                    @if($reg->isBacklogCleared)
                                        <span style="text-decoration:line-through;color:#9ca3af;">{{ $course?->name ?? 'N/A' }}</span>
                                    @else
                                        {{ $course?->name ?? 'N/A' }}
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($course?->semester)
                                        <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:8px;font-size:0.75rem;">
                                            {{ $course->semester }}
                                        </span>
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
                                <td>
                                    @if($section) Sec {{ $section->section_number }}
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
                                <td style="text-align:center;font-size:0.85rem;color:#6b7280;">
                                    @if($section) {{ $section->term }} {{ $section->year }}
                                    @else <span style="color:#9ca3af">—</span> @endif
                                </td>
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
                @endif

            </div>{{-- /sem-section --}}
        @endforeach
        </div>{{-- /resultsContainer --}}

        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
let activeSemester = 'all';
let activeResult   = 'all';

function applyFilters() {
    document.querySelectorAll('.sem-section').forEach(function(section) {
        var sem = section.dataset.sem;
        var semVisible = activeSemester === 'all' || activeSemester === sem;
        section.style.display = semVisible ? '' : 'none';

        if (!semVisible) return;

        // Within visible semester, filter result-blocks and rows
        section.querySelectorAll('.result-block').forEach(function(block) {
            var blockType = block.dataset.block; // 'pass' or 'fail'
            var blockVisible = true;

            if (activeResult === 'pass') {
                blockVisible = (blockType === 'pass');
            } else if (activeResult === 'fail') {
                blockVisible = (blockType === 'fail');
                // Show only active fail rows (not backlog-cleared)
                if (blockVisible) {
                    block.querySelectorAll('tr[data-type]').forEach(function(row) {
                        row.style.display = (row.dataset.type === 'fail-active') ? '' : 'none';
                    });
                }
            } else if (activeResult === 'backlog') {
                blockVisible = (blockType === 'fail');
                // Show only backlog-cleared rows
                if (blockVisible) {
                    block.querySelectorAll('tr[data-type]').forEach(function(row) {
                        row.style.display = (row.dataset.type === 'backlog-cleared') ? '' : 'none';
                    });
                }
            } else {
                // 'all' — reset all row visibility
                block.querySelectorAll('tr[data-type]').forEach(function(row) {
                    row.style.display = '';
                });
            }

            block.style.display = blockVisible ? '' : 'none';

            // Hide block if all its data rows are hidden
            if (blockVisible) {
                var visibleRows = block.querySelectorAll('tr[data-type]:not([style*="none"])').length;
                if (visibleRows === 0) block.style.display = 'none';
            }
        });
    });
}

function filterResults(type, btn) {
    document.querySelectorAll('.filter-btn-result').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    activeResult = type;
    applyFilters();
}

function filterBySemester(sem, btn) {
    document.querySelectorAll('.filter-sem-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    activeSemester = String(sem);
    applyFilters();
}
</script>
@endpush
