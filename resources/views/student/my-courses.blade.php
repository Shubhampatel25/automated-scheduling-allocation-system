@extends('layouts.dashboard')

@section('title', 'My Courses')
@section('role-label', 'Student Panel')
@section('page-title', 'My Courses')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    .btn-drop { background:#ef4444;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:0.8rem; }
    .btn-drop:hover { background:#dc2626; }
    .badge-dept { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .hist-tab { padding:10px 20px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:0.875rem;font-weight:600;color:#6b7280;margin-bottom:-2px; }
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

{{-- Currently Enrolled --}}
<div class="dashboard-card">
    <div class="card-header">
        <h3>My Courses &mdash; Semester {{ $semester }}</h3>
        <span class="badge badge-primary">{{ $enrolledCourses->count() }} Enrolled</span>
    </div>
    <div class="card-body">
        @if($enrolledCourses->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Department</th>
                        <th>Credits</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrolledCourses as $course)
                    <tr>
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $course->name ?? 'N/A' }}</td>
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

{{-- Course History --}}
@if($completedHistory->isNotEmpty())
<div class="dashboard-card" style="margin-top:24px;">
    <div class="card-header">
        <h3>&#127891; Course History</h3>
        <span class="badge badge-primary" style="background:#d1fae5;color:#065f46;">
            {{ $completedHistory->flatten()->count() }} Completed
        </span>
    </div>
    <div class="card-body" style="padding:0">

        <div style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;padding:0 20px;">
            @foreach($completedHistory as $sem => $courses)
                <button onclick="showHistorySem({{ $sem }}, this)"
                    class="hist-tab {{ $loop->first ? 'hist-tab-active' : '' }}"
                    style="border-bottom-color:{{ $loop->first ? '#4f46e5' : 'transparent' }};color:{{ $loop->first ? '#4f46e5' : '#6b7280' }};">
                    Semester {{ $sem }}
                    <span style="background:{{ $loop->first ? '#e0e7ff' : '#f3f4f6' }};color:{{ $loop->first ? '#3730a3' : '#6b7280' }};border-radius:10px;padding:1px 7px;font-size:0.72rem;margin-left:4px;">{{ $courses->count() }}</span>
                </button>
            @endforeach
        </div>

        @foreach($completedHistory as $sem => $courses)
        <div id="hist-sem-{{ $sem }}" class="hist-panel" style="{{ $loop->first ? '' : 'display:none' }}">
            <table class="data-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Teacher</th>
                        <th>Section</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                    <tr>
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $course->name ?? 'N/A' }}</td>
                        <td><span class="badge-credits">{{ $course->credits ?? 0 }} cr</span></td>
                        <td>{{ $course->teacherName ?? 'TBA' }}</td>
                        <td>
                            @if($course->sectionInfo)
                                Sec {{ $course->sectionInfo->section_number }} &bull; {{ $course->sectionInfo->term }} {{ $course->sectionInfo->year }}
                            @else N/A @endif
                        </td>
                        <td><span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;">&#10003; Completed</span></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f9fafb;">
                        <td colspan="2" style="padding:10px 16px;font-weight:600;color:#374151;">Total Credits</td>
                        <td style="padding:10px 16px;font-weight:700;color:#4f46e5;">{{ $courses->sum('credits') }} cr</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endforeach

    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function showHistorySem(sem, btn) {
    document.querySelectorAll('.hist-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.hist-tab').forEach(t => {
        t.style.borderBottomColor = 'transparent';
        t.style.color = '#6b7280';
        const b = t.querySelector('span');
        if (b) { b.style.background = '#f3f4f6'; b.style.color = '#6b7280'; }
    });
    document.getElementById('hist-sem-' + sem).style.display = '';
    btn.style.borderBottomColor = '#4f46e5';
    btn.style.color = '#4f46e5';
    const b = btn.querySelector('span');
    if (b) { b.style.background = '#e0e7ff'; b.style.color = '#3730a3'; }
}
</script>
@endpush
