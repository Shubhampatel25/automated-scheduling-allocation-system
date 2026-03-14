@extends('layouts.dashboard')

@section('title', "Today's Classes")
@section('role-label', 'Student Panel')
@section('page-title', "Today's Classes")

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@section('content')
<div class="dashboard-card">
    <div class="card-header">
        <h3>Today's Classes</h3>
        <span class="badge badge-success">{{ $today }}</span>
    </div>
    <div class="card-body">
        @if($todaySchedule->count() > 0)
            <ul class="activity-list">
                @foreach($todaySchedule as $slot)
                    <li class="activity-item">
                        <div class="activity-dot blue"></div>
                        <div class="activity-content">
                            <h4>{{ $slot->courseSection->course->name ?? 'N/A' }}</h4>
                            <p>
                                {{ substr($slot->start_time, 0, 5) }} &ndash; {{ substr($slot->end_time, 0, 5) }}
                                &bull; Room: {{ $slot->room->room_number ?? 'TBA' }}
                                &bull; {{ $slot->teacher->name ?? 'TBA' }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty-state">
                <div class="empty-icon">&#128197;</div>
                <p>No classes scheduled for today ({{ $today }}).</p>
            </div>
        @endif
    </div>
</div>
@endsection
