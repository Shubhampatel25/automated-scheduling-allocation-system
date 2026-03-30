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
    .badge-dept    { background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-credits { background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:0.75rem; }
    .badge-retake  { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;padding:2px 9px;border-radius:12px;font-size:0.72rem;font-weight:700;letter-spacing:.02em; }
    .badge-regular { background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:2px 9px;border-radius:12px;font-size:0.72rem;font-weight:600; }
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

@endsection
