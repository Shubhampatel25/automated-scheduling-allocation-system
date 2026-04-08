@extends('layouts.dashboard')

@section('title', 'My Timetable')
@section('role-label', 'Student Panel')
@section('page-title', 'My Timetable')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@section('content')
<div class="dashboard-card">
    <div class="card-header">
        <h3>My Weekly Timetable &mdash; Semester {{ $semester }}</h3>
        <span class="badge badge-warning">Week View</span>
    </div>
    <div class="card-body">
        <div class="timetable-container">
            @if($weeklySchedule->count() > 0)
                @php
                    // Build time periods dynamically from actual DB slot times — no hardcoding
                    $periods = $weeklySchedule
                        ->map(fn($s) => [
                            'start' => substr($s->start_time, 0, 5),
                            'end'   => substr($s->end_time,   0, 5),
                            'key'   => substr($s->start_time, 0, 5),
                        ])
                        ->unique('key')
                        ->sortBy('start')
                        ->values();

                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                @endphp
                <table class="timetable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            @foreach($days as $day)<th>{{ $day }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                        <tr>
                            <td class="time-col">{{ $period['start'] }} – {{ $period['end'] }}</td>
                            @foreach($days as $day)
                                @php
                                    $slot = $weeklySchedule->first(
                                        fn($s) => $s->day_of_week === $day
                                               && substr($s->start_time, 0, 5) === $period['start']
                                    );
                                @endphp
                                <td>
                                    @if($slot)
                                        @php
                                            // Mark as retake if this course is in the student's net-failed list.
                                            // $retakeCourseIds is [] for new students — no badge shown.
                                            $isRetake = !empty($retakeCourseIds)
                                                && in_array($slot->courseSection?->course?->id, $retakeCourseIds);
                                        @endphp
                                        <div class="slot" style="{{ $isRetake ? 'border-left:3px solid #dc2626;padding-left:6px;' : '' }}">
                                            <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                            @if($isRetake)
                                                <div style="font-size:0.62rem;color:#dc2626;font-weight:700;letter-spacing:.04em;margin-bottom:1px;">&#8635; RETAKE</div>
                                            @endif
                                            <div class="room-name">&#127968; {{ $slot->room->room_number ?? '' }}</div>
                                            <div class="teacher-name">{{ $slot->teacher->name ?? '' }}</div>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-icon">&#128197;</div>
                    <p>No timetable generated yet. <a href="{{ route('student.register-courses') }}" style="color:#4f46e5">Enroll in courses first &rarr;</a></p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
