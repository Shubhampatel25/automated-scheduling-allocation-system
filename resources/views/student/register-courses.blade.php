@extends('layouts.dashboard')

@section('title', 'Register Courses')
@section('role-label', 'Student Panel')
@section('page-title', 'Register Courses')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    .btn-register { background:#4f46e5;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:0.8rem; }
    .btn-register:hover { background:#4338ca; }
    .badge-dept { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .capacity-bar { height:6px;background:#e5e7eb;border-radius:4px;min-width:80px; }
    .capacity-fill { height:6px;border-radius:4px;background:#4f46e5; }
    .capacity-fill.near-full { background:#f59e0b; }
    .section-search { padding:7px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:0.875rem;width:240px; }
    .fee-banner { padding:14px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:10px; }
    .fee-banner.paid { background:#d1fae5;color:#065f46;border:1px solid #a7f3d0; }
    .fee-banner.unpaid { background:#fef3c7;color:#92400e;border:1px solid #fde68a; }
</style>
@endpush

@section('content')

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
            <div style="margin-bottom:14px;">
                <input type="text" class="section-search" id="regSearch" placeholder="Search course name or code..." onkeyup="filterRegTable()">
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
                        <th>Seats</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($availableSections as $section)
                        @php
                            $course   = $section->course;
                            $teacher  = $section->assignments->first()?->teacher;
                            $filled   = $section->enrolled_students;
                            $max      = $section->max_students;
                            $pct      = $max > 0 ? round($filled / $max * 100) : 0;
                            $barClass = $pct >= 90 ? 'near-full' : '';
                        @endphp
                        <tr>
                            <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                            <td>{{ $course->name ?? 'N/A' }}</td>
                            <td><span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span></td>
                            <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                            <td>Sec {{ $section->section_number }} &bull; {{ $section->term }} {{ $section->year }}</td>
                            <td>{{ $teacher ? $teacher->name : 'TBA' }}</td>
                            <td>
                                <div style="font-size:0.78rem;color:#6b7280;margin-bottom:3px">{{ $filled }}/{{ $max }}</div>
                                <div class="capacity-bar">
                                    <div class="capacity-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                                </div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('student.courses.register') }}">
                                    @csrf
                                    <input type="hidden" name="course_section_id" value="{{ $section->id }}">
                                    <button type="submit" class="btn-register">Enroll</button>
                                </form>
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
</script>
@endpush
