@extends('layouts.dashboard')

@section('title', 'Register Courses')
@section('role-label', 'Student Panel')
@section('page-title', 'Register Courses')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    .btn-register      { background:#4f46e5;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:0.8rem; }
    .btn-register:hover { background:#4338ca; }
    .btn-register.advisory { background:#d97706; }
    .btn-register.advisory:hover { background:#b45309; }
    .btn-blocked       { background:#e5e7eb;color:#9ca3af;border:none;padding:6px 16px;border-radius:6px;font-size:0.8rem;cursor:not-allowed; }
    .blocked-row td    { background:#fafafa; }
    .advisory-row td   { background:#fffbeb; }
    .badge-dept        { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits     { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .capacity-bar      { height:6px;background:#e5e7eb;border-radius:4px;min-width:80px; }
    .capacity-fill     { height:6px;border-radius:4px;background:#4f46e5; }
    .capacity-fill.near-full { background:#f59e0b; }
    .section-search    { padding:7px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:0.875rem;width:240px; }
    .fee-banner        { padding:14px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:10px; }
    .fee-banner.paid   { background:#d1fae5;color:#065f46;border:1px solid #a7f3d0; }
    .fee-banner.unpaid { background:#fef3c7;color:#92400e;border:1px solid #fde68a; }

    /* Prerequisite badges */
    .badge-prereq-met       { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-required  { background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-advisory  { background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .badge-prereq-none      { background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:12px;font-size:0.72rem; }
    .conflict-badge         { background:#fff7ed;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.72rem;font-weight:600; }
    .block-reason           { font-size:0.73rem;color:#9ca3af;margin-top:3px;max-width:190px; }
    .advisory-note          { font-size:0.72rem;color:#92400e;margin-top:3px;max-width:190px; }
</style>
@endpush

@section('content')

@if(session('semester_advanced'))
<div style="background:#ede9fe;color:#4c1d95;padding:16px 20px;border-radius:10px;margin-bottom:18px;border:2px solid #c4b5fd;display:flex;align-items:flex-start;gap:12px;">
    <span style="font-size:1.4rem;flex-shrink:0;">&#127881;</span>
    <div>
        <strong style="font-size:1rem;">Congratulations! You have been advanced to Semester {{ session('semester_advanced') }}.</strong>
        <div style="margin-top:4px;font-size:0.85rem;color:#5b21b6;">
            You passed your previous semester requirements. You can now register for Semester {{ session('semester_advanced') }} courses below.
            If you have any failed courses from the previous semester, you can retake them in parallel.
        </div>
    </div>
</div>
@endif

@if(session('success'))
<div style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #a7f3d0;">
    &#10003; {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;color:#991b1b;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #fca5a5;">
    &#9888; {{ session('error') }}
</div>
@endif

<div class="dashboard-card">
    <div class="card-header">
        <h3>Register for Courses &mdash; Semester {{ $semester }}</h3>
        <span class="badge badge-primary">{{ $upcomingTerm }} {{ $upcomingYear }} &nbsp;&bull;&nbsp; {{ $availableSections->count() }} Available</span>
    </div>
    <div class="card-body">

        @if(!$feePaid)
            <div class="fee-banner unpaid">
                <span style="font-size:1.2rem">&#9888;</span>
                <div>
                    <strong>Fee Payment Required</strong> &mdash; You must complete your fee payment for
                    Semester {{ $semester }} before registering for courses.
                    <a href="{{ route('student.fee-payment') }}" style="color:#92400e;text-decoration:underline;margin-left:6px;">Go to Fee Payment &rarr;</a>
                </div>
            </div>
        @else
            <div class="fee-banner paid">
                <span style="font-size:1.2rem">&#10003;</span>
                <div><strong>Fees Paid</strong> &mdash; Your fee payment for Semester {{ $semester }} has been confirmed. You may register for courses below.</div>
            </div>
        @endif

        @if($feePaid && $availableSections->count() > 0)

            {{-- Legend --}}
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px;font-size:0.78rem;color:#6b7280;align-items:center;padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
                <strong style="color:#374151;">Legend:</strong>
                <span><span class="badge-prereq-met">&#10003; MET</span> Prerequisite passed</span>
                <span><span class="badge-prereq-required">&#10007; REQUIRED</span> Mandatory prerequisite missing &mdash; blocked</span>
                <span><span class="badge-prereq-advisory">&#9888; ADVISORY</span> Recommended prerequisite not yet passed &mdash; can still enroll</span>
                <span><span class="conflict-badge">&#128337; CONFLICT</span> Schedule overlap &mdash; blocked</span>
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
                    @foreach($availableSections as $section)
                        @php
                            $course      = $section->course;
                            $teacher     = $section->assignments->first()?->teacher;
                            $filled      = $section->enrolled_students;
                            $max         = $section->max_students;
                            $pct         = $max > 0 ? round($filled / $max * 100) : 0;
                            $barClass    = $pct >= 90 ? 'near-full' : '';
                            $secStatus   = $sectionStatuses[$section->id] ?? ['blocked' => false, 'advisory' => null, 'reason' => null];
                            $prereqCode  = $course->prerequisite_course_code;
                            $isMandatory = $course->prerequisite_mandatory ?? false;
                            $prereqMet   = $prereqCode && in_array($prereqCode, $passedCourseCodes);

                            // Row highlight class
                            $rowClass = '';
                            if ($secStatus['blocked'])            $rowClass = 'blocked-row';
                            elseif ($secStatus['advisory'])        $rowClass = 'advisory-row';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                            <td>{{ $course->name ?? 'N/A' }}</td>
                            <td><span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span></td>
                            <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                            <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                            <td>{{ $teacher ? $teacher->name : 'TBA' }}</td>
                            <td>
                                @if(!$prereqCode)
                                    <span class="badge-prereq-none">None</span>
                                @elseif($prereqMet)
                                    <span class="badge-prereq-met">&#10003; {{ $prereqCode }}</span>
                                @elseif($isMandatory)
                                    <span class="badge-prereq-required">&#10007; {{ $prereqCode }}</span>
                                    <div style="font-size:0.7rem;color:#991b1b;margin-top:2px;">Required</div>
                                @else
                                    <span class="badge-prereq-advisory">&#9888; {{ $prereqCode }}</span>
                                    <div style="font-size:0.7rem;color:#92400e;margin-top:2px;">Advisory</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                                <div class="capacity-bar">
                                    <div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if($secStatus['blocked'])
                                    {{-- Hard block: mandatory prereq missing or schedule conflict --}}
                                    <button type="button" class="btn-blocked" disabled>Blocked</button>
                                    <div class="block-reason">{{ $secStatus['reason'] }}</div>

                                @elseif($secStatus['advisory'])
                                    {{-- Advisory: non-mandatory prereq not yet passed — allow with warning --}}
                                    <form method="POST" action="{{ route('student.courses.register') }}"
                                          onsubmit="return confirmAdvisory('{{ addslashes($course->name) }}', '{{ $prereqCode }}')">
                                        @csrf
                                        <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                        <button type="submit" class="btn-register advisory">Enroll</button>
                                    </form>
                                    <div class="advisory-note">&#9888; {{ $prereqCode }} not yet passed</div>

                                @else
                                    {{-- Available: enroll normally --}}
                                    <form method="POST" action="{{ route('student.courses.register') }}">
                                        @csrf
                                        <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                        <button type="submit" class="btn-register">Enroll</button>
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
                <p>No available courses to register for at the moment.</p>
            </div>
        @endif

    </div>
</div>

{{-- ── Retake / Backlog Courses Section ── --}}
@if(isset($retakeSections) && $retakeSections->isNotEmpty())
<div class="dashboard-card" style="margin-top:24px;border:2px solid #fca5a5;">
    <div class="card-header" style="background:#fff1f2;">
        <h3 style="color:#991b1b;">&#8635; Backlog / Retake Registration</h3>
        <span style="font-size:0.82rem;color:#991b1b;font-weight:600;">{{ $retakeSections->count() }} section(s) available</span>
    </div>
    <div class="card-body">
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.875rem;color:#92400e;">
            &#9888; These are courses you previously failed (backlogs). You can retake them
            <strong>in parallel with your current semester courses</strong>.
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
                    <th>Fee Status</th>
                    <th>Seats</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retakeSections as $section)
                    @php
                        $course       = $section->course;
                        $teacher      = $section->assignments->first()?->teacher;
                        $filled       = $section->enrolled_students;
                        $max          = $section->max_students;
                        $pct          = $max > 0 ? round($filled / $max * 100) : 0;
                        $barClass     = $pct >= 90 ? 'near-full' : '';
                        $suppPaid     = in_array($course->id, $supplementalPaidCourseIds ?? []);
                    @endphp
                    <tr style="{{ !$suppPaid ? 'background:#fafafa;' : '' }}">
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>
                            {{ $course->name ?? 'N/A' }}
                            <span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:8px;font-size:0.7rem;font-weight:700;margin-left:4px;">RETAKE</span>
                        </td>
                        <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                        <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                        <td>{{ $teacher ? $teacher->name : 'TBA' }}</td>
                        <td>
                            @if($suppPaid)
                                <span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.73rem;font-weight:700;">&#10003; Paid</span>
                            @else
                                <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:0.73rem;font-weight:700;">&#9888; Fee Required</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                            <div class="capacity-bar">
                                <div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </td>
                        <td>
                            @if($suppPaid)
                                <form method="POST" action="{{ route('student.courses.register') }}">
                                    @csrf
                                    <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                    <button type="submit" class="btn-register" style="background:#dc2626;">&#8635; Re-Enroll</button>
                                </form>
                            @else
                                <button type="button" class="btn-blocked" disabled>Locked</button>
                                <div class="block-reason" style="color:#dc2626;">Pay supplemental fee first</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function filterRegTable() {
    const val  = document.getElementById('regSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#regTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
}

function confirmAdvisory(courseName, prereqCode) {
    return confirm(
        'Advisory Notice\n\n' +
        'You have not yet passed the recommended prerequisite "' + prereqCode + '" for ' + courseName + '.\n\n' +
        'You are still allowed to enroll and progress to this semester. You can take both this course and your retake in parallel.\n\n' +
        'Do you want to proceed with enrollment?'
    );
}
</script>
@endpush
