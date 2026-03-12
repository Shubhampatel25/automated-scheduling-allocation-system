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
                <table class="timetable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['08:00 - 09:30', '09:30 - 11:00', '11:00 - 12:30', '13:00 - 14:30', '14:30 - 16:00'] as $time)
                        <tr>
                            <td class="time-col">{{ $time }}</td>
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                <td>
                                    @php
                                        [$startStr] = explode(' - ', $time);
                                        $slot = $weeklySchedule->first(fn($s) => ($s->day_of_week ?? '') === $day && substr($s->start_time, 0, 5) === $startStr);
                                    @endphp
                                    @if($slot)
                                        <div class="slot">
                                            <div class="course-name">{{ $slot->courseSection->course->name ?? '' }}</div>
                                            <div class="room-name">{{ $slot->room->room_number ?? '' }}</div>
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
