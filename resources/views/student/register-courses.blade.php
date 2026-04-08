@extends('layouts.dashboard')

@section('title', 'Register Courses')
@section('role-label', 'Student Panel')
@section('page-title', 'Register Courses')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    /* ── Action buttons ── */
    .btn-register       { background:#4f46e5;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:0.8rem; }
    .btn-register:hover { background:#4338ca; }
    .btn-register.advisory { background:#d97706; }
    .btn-register.advisory:hover { background:#b45309; }
    .btn-blocked        { background:#e5e7eb;color:#9ca3af;border:none;padding:6px 16px;border-radius:6px;font-size:0.8rem;cursor:not-allowed; }

    /* ── Row highlights ── */
    .blocked-row td  { background:#fafafa; }
    .advisory-row td { background:#fffbeb; }

    /* ── Badges ── */
    .badge-dept    { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-prereq-met      { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-required { background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-advisory { background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-none     { background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:12px;font-size:0.72rem; }
    .conflict-badge        { background:#fff7ed;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }

    /* ── Capacity bar ── */
    .capacity-bar  { height:6px;background:#e5e7eb;border-radius:4px;min-width:80px; }
    .capacity-fill { height:6px;border-radius:4px;background:#4f46e5; }
    .capacity-fill.near-full { background:#f59e0b; }

    /* ── Search ── */
    .section-search { padding:7px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:0.875rem;width:240px; }

    /* ── Fee banner ── */
    .fee-banner       { padding:14px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:10px; }
    .fee-banner.paid  { background:#d1fae5;color:#065f46;border:1px solid #a7f3d0; }
    .fee-banner.unpaid { background:#fef3c7;color:#92400e;border:1px solid #fde68a; }

    /* ── Inline reason text ── */
    .block-reason  { font-size:0.73rem;color:#9ca3af;margin-top:3px;max-width:190px; }
    .advisory-note { font-size:0.72rem;color:#92400e;margin-top:3px;max-width:190px; }

    /* ── Blocked-reason chip in Blocked section ── */
    .reason-chip { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:0.72rem;font-weight:700; }
    .reason-chip.prereq   { background:#fee2e2;color:#991b1b; }
    .reason-chip.conflict { background:#fff7ed;color:#92400e; }

    /* ── Payment status summary ── */
    .pay-status-bar { display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:11px 16px;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:18px;background:#fafafa;font-size:0.82rem; }
    .pay-status-bar .pstat { display:flex;flex-direction:column;gap:1px; }
    .pay-status-bar .pstat-label { font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af; }
    .pay-status-bar .pstat-val   { font-size:0.88rem;font-weight:700;color:#111827; }
</style>
@endpush

@section('content')

{{-- ── Semester-advance banner ── --}}
@if(session('semester_advanced'))
<div style="background:#ede9fe;color:#4c1d95;padding:16px 20px;border-radius:10px;margin-bottom:18px;border:2px solid #c4b5fd;display:flex;align-items:flex-start;gap:12px;">
    <span style="font-size:1.4rem;flex-shrink:0;">&#127881;</span>
    <div>
        <strong style="font-size:1rem;">Congratulations! You have been advanced to Semester {{ session('semester_advanced') }}.</strong>
        <div style="margin-top:4px;font-size:0.85rem;color:#5b21b6;">
            You passed your previous semester requirements. Register for Semester {{ session('semester_advanced') }} courses below.
            Any failed courses from the previous semester appear in the Retake / Backlog section.
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     SECTION 1 — Payment Status Summary
══════════════════════════════════════════════════════ --}}
<div class="pay-status-bar">
    <div class="pstat">
        <div class="pstat-label">Payment Status</div>
        <div class="pstat-val">
            @if($feeRecord)
                @php $st = $feeRecord->status; @endphp
                <span style="
                    padding:3px 12px;border-radius:20px;font-size:0.78rem;
                    {{ $st === 'paid'    ? 'background:#d1fae5;color:#065f46;'
                     : ($st === 'partial' ? 'background:#dbeafe;color:#1e40af;'
                     : 'background:#fef3c7;color:#92400e;') }}
                ">
                    {{ $st === 'paid' ? '✓ Paid' : ($st === 'partial' ? '⬤ Partial' : '⚠ Pending') }}
                </span>
            @else
                <span style="background:#f3f4f6;color:#6b7280;padding:3px 12px;border-radius:20px;font-size:0.78rem;">No Record</span>
            @endif
        </div>
    </div>
    @if($feeRecord)
    <div class="pstat" style="border-left:1px solid #e5e7eb;padding-left:16px;">
        <div class="pstat-label">Total Fee</div>
        <div class="pstat-val">${{ number_format($feeRecord->amount ?? 0, 2) }}</div>
    </div>
    <div class="pstat" style="border-left:1px solid #e5e7eb;padding-left:16px;">
        <div class="pstat-label">Amount Paid</div>
        <div class="pstat-val" style="{{ $feeRecord->paid_amount > 0 ? 'color:#2563eb;' : '' }}">${{ number_format($feeRecord->paid_amount ?? 0, 2) }}</div>
    </div>
    @php $remaining = max(0, ($feeRecord->amount ?? 0) - ($feeRecord->paid_amount ?? 0)); @endphp
    @if($remaining > 0)
    <div class="pstat" style="border-left:1px solid #e5e7eb;padding-left:16px;">
        <div class="pstat-label">Balance Due</div>
        <div class="pstat-val" style="color:#dc2626;">${{ number_format($remaining, 2) }}</div>
    </div>
    @endif
    @endif
    <div style="margin-left:auto;">
        <a href="{{ route('student.fee-payment') }}" style="color:#4f46e5;font-size:0.82rem;font-weight:600;text-decoration:none;">
            &#128197; View Full Invoice &rarr;
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 2 — Current Semester Courses (Regular)
══════════════════════════════════════════════════════ --}}
<div class="dashboard-card">
    <div class="card-header">
        <h3>Current Semester Courses &mdash; Semester {{ $semester }}</h3>
        <span class="badge badge-primary">{{ $upcomingTerm }} {{ $upcomingYear }} &nbsp;&bull;&nbsp; {{ $regularSections->count() }} Available</span>
    </div>
    <div class="card-body">

        @if(!$feePaid)
            <div class="fee-banner unpaid">
                <span style="font-size:1.2rem">&#9888;</span>
                <div>
                    <strong>Fee Payment Required</strong> &mdash; Complete your Semester {{ $semester }} fee payment before registering for courses.
                    <a href="{{ route('student.fee-payment') }}" style="color:#92400e;text-decoration:underline;margin-left:6px;">Go to Fee Payment &rarr;</a>
                </div>
            </div>
        @else
            <div class="fee-banner paid">
                <span style="font-size:1.2rem">&#10003;</span>
                <div><strong>Fee Paid</strong> &mdash; Your Semester {{ $semester }} fee is confirmed. Register for courses below.</div>
            </div>
        @endif

        @if($feePaid && $regularSections->count() > 0)

            {{-- Legend --}}
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px;font-size:0.78rem;color:#6b7280;align-items:center;padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
                <strong style="color:#374151;">Legend:</strong>
                <span><span class="badge-prereq-met">&#10003; MET</span> Prerequisite passed</span>
                <span><span class="badge-prereq-required">&#10007; REQUIRED</span> Mandatory prerequisite missing</span>
                <span><span class="badge-prereq-advisory">&#9888; ADVISORY</span> Recommended prerequisite not yet passed &mdash; can still enrol</span>
                <span><span class="conflict-badge">&#128337; CONFLICT</span> Schedule overlap</span>
                <span><span class="badge-prereq-none">None</span> No prerequisite</span>
            </div>

            <div style="margin-bottom:14px;">
                <input type="text" class="section-search" id="regSearch" placeholder="Search course name or code..." onkeyup="filterRegTable()">
            </div>
            <div style="font-size:0.78rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">
                Regular Courses &mdash; {{ $upcomingTerm }} {{ $upcomingYear }}
            </div>

            <table class="data-table" id="regTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Department</th>
                        <th>Credits</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Prerequisite</th>
                        <th>Seats</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($regularSections as $section)
                        @php
                            $course     = $section->course;
                            $teacher    = $section->assignments->first()?->teacher ?? null;
                            $teacherName = $teacher?->name ?? ($courseTeacherMap[$course->id ?? 0] ?? 'TBA');
                            $filled     = $section->actual_enrolled ?? $section->enrolled_students;
                            $max        = $section->max_students;
                            $pct        = $max > 0 ? round($filled / $max * 100) : 0;
                            $barClass   = $pct >= 90 ? 'near-full' : '';
                            $secStatus  = $sectionStatuses[$section->id] ?? ['blocked' => false, 'advisory' => null, 'reason' => null];
                            $prereqCode = $course->prerequisite_course_code;
                            $isMand     = $course->prerequisite_mandatory ?? false;
                            $prereqMet  = $prereqCode && in_array($prereqCode, $passedCourseCodes);
                            $rowClass   = $secStatus['advisory'] ? 'advisory-row' : '';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                            <td>{{ $course->name ?? 'N/A' }}</td>
                            <td><span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span></td>
                            <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                            <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                            <td>{{ $teacherName }}</td>
                            <td>
                                @if(!$prereqCode)
                                    <span class="badge-prereq-none">None</span>
                                @elseif($prereqMet)
                                    <span class="badge-prereq-met">&#10003; {{ $prereqCode }}</span>
                                @elseif($isMand)
                                    <span class="badge-prereq-required">&#10007; {{ $prereqCode }}</span>
                                    <div style="font-size:0.7rem;color:#991b1b;margin-top:2px;">Required</div>
                                @else
                                    <span class="badge-prereq-advisory">&#9888; {{ $prereqCode }}</span>
                                    <div style="font-size:0.7rem;color:#92400e;margin-top:2px;">Advisory</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                                <div class="capacity-bar"><div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div></div>
                            </td>
                            <td>
                                @if($secStatus['advisory'])
                                    <form method="POST" action="{{ route('student.courses.register') }}"
                                          onsubmit="return confirmAdvisory('{{ addslashes($course->name) }}', '{{ $prereqCode }}')">
                                        @csrf
                                        <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                        <button type="submit" class="btn-register advisory">Enrol</button>
                                    </form>
                                    <div class="advisory-note">&#9888; {{ $prereqCode }} not yet passed</div>
                                @else
                                    <form method="POST" action="{{ route('student.courses.register') }}">
                                        @csrf
                                        <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                        <button type="submit" class="btn-register">Enrol</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($feePaid)
            <div class="empty-state">
                <div class="empty-icon">&#128218;</div>
                <p>No available courses for Semester {{ $semester }} right now.</p>
            </div>
        @endif

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 3 — Retake / Backlog Courses
══════════════════════════════════════════════════════ --}}
@if($retakeSections->isNotEmpty())
<div class="dashboard-card" style="margin-top:24px;border:2px solid #fca5a5;">
    <div class="card-header" style="background:#fff1f2;">
        <h3 style="color:#991b1b;">&#8635; Retake / Backlog Registration</h3>
        <span style="font-size:0.82rem;color:#991b1b;font-weight:600;">{{ $retakeSections->count() }} section(s) available</span>
    </div>
    <div class="card-body">
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.875rem;color:#92400e;">
            &#9888; These are courses you previously failed. You can retake them <strong>in parallel</strong> with your current semester.
            Courses marked <span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:6px;font-size:0.75rem;font-weight:700;">FEE REQUIRED</span>
            need a supplemental fee paid first on the
            <a href="{{ route('student.fee-payment') }}" style="color:#c2410c;font-weight:700;">Fee Payment page</a>.
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Credits</th>
                    <th>Section</th>
                    <th>Teacher</th>
                    <th>Retake Fee</th>
                    <th>Seats</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retakeSections as $section)
                    @php
                        $course    = $section->course;
                        $teacher   = $section->assignments->first()?->teacher ?? null;
                        $teacherName = $teacher?->name ?? ($courseTeacherMap[$course->id ?? 0] ?? 'TBA');
                        $filled    = $section->enrolled_students;
                        $max       = $section->max_students;
                        $pct       = $max > 0 ? round($filled / $max * 100) : 0;
                        $barClass  = $pct >= 90 ? 'near-full' : '';
                        $rStatus   = $retakeStatuses[$section->id] ?? ['supp_paid' => false, 'blocked' => true, 'block_reason' => 'Retake fee not paid', 'block_reason_key' => 'retake_fee_not_paid'];
                        $suppPaid  = $rStatus['supp_paid'];
                        $rtBlocked = $rStatus['blocked'];
                    @endphp
                    <tr style="{{ $rtBlocked ? 'background:#fafafa;' : '' }}">
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>
                            {{ $course->name ?? 'N/A' }}
                            <span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:8px;font-size:0.7rem;font-weight:700;margin-left:4px;">RETAKE</span>
                        </td>
                        <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                        <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                        <td>{{ $teacherName }}</td>
                        <td>
                            @if($suppPaid)
                                <span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.73rem;font-weight:700;">&#10003; Paid</span>
                            @else
                                <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:0.73rem;font-weight:700;">&#9888; Fee Required</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                            <div class="capacity-bar"><div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div></div>
                        </td>
                        <td>
                            @if(!$rtBlocked)
                                <form method="POST" action="{{ route('student.courses.register') }}">
                                    @csrf
                                    <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                    <button type="submit" class="btn-register" style="background:#dc2626;">&#8635; Re-Enrol</button>
                                </form>
                            @else
                                <button type="button" class="btn-blocked" disabled>Locked</button>
                                <div class="block-reason" style="color:#dc2626;">
                                    @if($rStatus['block_reason_key'] === 'timetable_conflict')
                                        &#128337; {{ $rStatus['block_reason'] }}
                                    @else
                                        Pay supplemental fee first
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     SECTION 4 — Blocked Courses (with reasons)
══════════════════════════════════════════════════════ --}}
@if($blockedSections->isNotEmpty())
<div class="dashboard-card" style="margin-top:24px;border:1.5px solid #e5e7eb;">
    <div class="card-header" style="background:#f9fafb;">
        <h3 style="color:#374151;">&#128683; Blocked Courses</h3>
        <span style="font-size:0.82rem;color:#6b7280;font-weight:600;">{{ $blockedSections->count() }} course(s) currently unavailable</span>
    </div>
    <div class="card-body">
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:0.8rem;color:#6b7280;">
            &#8505;&nbsp; The courses below belong to your semester but cannot be registered right now. Each row shows the exact reason.
            Once the blocking condition is resolved (e.g. prerequisite passed, schedule conflict fixed), the course will appear in the
            <strong>Current Semester Courses</strong> section above.
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Credits</th>
                    <th>Section</th>
                    <th>Teacher</th>
                    <th>Block Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach($blockedSections as $section)
                    @php
                        $course      = $section->course;
                        $teacher     = $section->assignments->first()?->teacher ?? null;
                        $teacherName = $teacher?->name ?? ($courseTeacherMap[$course->id ?? 0] ?? 'TBA');
                        $bStatus   = $sectionStatuses[$section->id] ?? ['reason' => 'Unavailable', 'reason_type' => ''];
                        $reasonKey = $bStatus['reason_type'] ?? '';
                    @endphp
                    <tr class="blocked-row">
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td style="color:#9ca3af;">{{ $course->name ?? 'N/A' }}</td>
                        <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                        <td style="color:#9ca3af;">Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                        <td style="color:#9ca3af;">{{ $teacherName }}</td>
                        <td>
                            @if($reasonKey === 'prerequisite_fail')
                                <span class="reason-chip prereq">&#10007; Prerequisite</span>
                                <div style="font-size:0.71rem;color:#991b1b;margin-top:3px;">{{ $bStatus['reason'] }}</div>
                            @elseif($reasonKey === 'timetable_conflict')
                                <span class="reason-chip conflict">&#128337; Conflict</span>
                                <div style="font-size:0.71rem;color:#92400e;margin-top:3px;">{{ $bStatus['reason'] }}</div>
                            @else
                                <span class="reason-chip prereq">&#9888; Blocked</span>
                                <div style="font-size:0.71rem;color:#6b7280;margin-top:3px;">{{ $bStatus['reason'] }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     SECTION 5 — Previous Results (compact summary)
══════════════════════════════════════════════════════ --}}
@php
    $prevResultsCount = \App\Models\StudentCourseRegistration::where('student_id', optional(auth()->user())->id ? \App\Models\Student::where('user_id', auth()->id())->value('id') : 0)
        ->where('status', 'completed')->count();
@endphp
@if($prevResultsCount > 0)
<div style="margin-top:24px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px 18px;font-size:0.82rem;color:#6b7280;display:flex;align-items:center;gap:10px;">
    <span style="font-size:1.1rem;">&#128203;</span>
    <span>You have <strong style="color:#374151;">{{ $prevResultsCount }}</strong> completed course record(s) in your academic history.</span>
    <a href="{{ route('student.profile') }}" style="color:#4f46e5;font-weight:600;text-decoration:none;margin-left:auto;">View Full Results &rarr;</a>
</div>
@endif

@endsection

@push('scripts')
<script>
function filterRegTable() {
    const val  = document.getElementById('regSearch').value.toLowerCase();
    document.querySelectorAll('#regTable tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
}

function confirmAdvisory(courseName, prereqCode) {
    return confirm(
        'Advisory Notice\n\n' +
        'You have not yet passed the recommended prerequisite "' + prereqCode + '" for ' + courseName + '.\n\n' +
        'You are still allowed to enrol and progress to this semester. You can take both this course and your retake in parallel.\n\n' +
        'Do you want to proceed with enrolment?'
    );
}
</script>
@endpush
