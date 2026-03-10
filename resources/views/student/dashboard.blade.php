@extends('layouts.dashboard')

@section('title', 'Student Dashboard')
@section('role-label', 'Student Panel')
@section('page-title', 'Student Dashboard')

@section('sidebar-nav')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('student.dashboard') }}" class="nav-link active">
        <span class="icon">&#9776;</span> Dashboard
    </a>

    <div class="nav-section-title">Academics</div>
    <a href="#section-register" class="nav-link">
        <span class="icon">&#43;</span> Register Courses
    </a>
    <a href="#section-courses" class="nav-link">
        <span class="icon">&#128218;</span> My Courses
    </a>
    <a href="#section-timetable" class="nav-link">
        <span class="icon">&#128197;</span> My Timetable
    </a>
    <a href="#section-today" class="nav-link">
        <span class="icon">&#128336;</span> Today's Classes
    </a>

    <div class="nav-section-title">Finance</div>
    <a href="#section-fees" class="nav-link">
        <span class="icon">&#128178;</span> Fee Payment
    </a>

    <div class="nav-section-title">Account</div>
    <a href="#section-profile" class="nav-link">
        <span class="icon">&#128100;</span> My Profile
    </a>
@endsection

@push('styles')
<style>
    .register-table th, .register-table td { padding: 10px 14px; font-size: 0.875rem; }
    .register-table { width: 100%; border-collapse: collapse; }
    .register-table thead th { background: #f3f4f6; color: #374151; font-weight: 600; }
    .register-table tbody tr:hover { background: #f9fafb; }
    .register-table td { border-bottom: 1px solid #f0f0f0; }
    .btn-register { background: #4f46e5; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
    .btn-register:hover { background: #4338ca; }
    .btn-drop { background: #ef4444; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
    .btn-drop:hover { background: #dc2626; }
    .badge-dept { background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; }
    .badge-credits { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; }
    .capacity-bar { height: 6px; background: #e5e7eb; border-radius: 4px; min-width: 80px; }
    .capacity-fill { height: 6px; border-radius: 4px; background: #4f46e5; }
    .capacity-fill.near-full { background: #f59e0b; }
    .section-search { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; width: 220px; }
    .filter-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
    .fee-banner { padding: 14px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
    .fee-banner.paid { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .fee-banner.unpaid { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .fee-banner.partial { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
    .fee-card-body { display: flex; flex-direction: column; gap: 18px; }
    .fee-info-row { display: flex; flex-wrap: wrap; gap: 24px; }
    .fee-info-item { flex: 1; min-width: 140px; }
    .fee-info-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .fee-info-value { font-size: 1.1rem; font-weight: 600; color: #111827; }
    .fee-status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
    .fee-status-badge.paid { background: #d1fae5; color: #065f46; }
    .fee-status-badge.pending { background: #fef3c7; color: #92400e; }
    .fee-status-badge.overdue { background: #fee2e2; color: #991b1b; }
    .fee-status-badge.partial { background: #dbeafe; color: #1e40af; }
    .fee-status-badge.none { background: #f3f4f6; color: #6b7280; }
    .fee-pay-section { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
    .btn-pay-full { background: #16a34a; color: #fff; border: none; padding: 9px 20px; border-radius: 7px; cursor: pointer; font-size: 0.88rem; font-weight: 600; }
    .btn-pay-full:hover { background: #15803d; }
    .partial-pay-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .partial-amount-input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 0.88rem; width: 140px; }
    .btn-pay-partial { background: #2563eb; color: #fff; border: none; padding: 9px 18px; border-radius: 7px; cursor: pointer; font-size: 0.88rem; font-weight: 600; }
    .btn-pay-partial:hover { background: #1d4ed8; }
</style>
@endpush

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h2>Welcome, {{ Auth::user()->username }}!</h2>
            <p>Register for courses, view your schedule, and manage your academic information.</p>
        </div>
        <a href="#section-register" class="banner-btn">Register Courses</a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#128218;</div>
            <div class="stat-details">
                <h3>{{ $courseCount }}</h3>
                <p>Enrolled Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128197;</div>
            <div class="stat-details">
                <h3>{{ $classesPerWeek }}</h3>
                <p>Classes / Week</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">&#128100;</div>
            <div class="stat-details">
                <h3>{{ $teacherCount }}</h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#128336;</div>
            <div class="stat-details">
                <h3>{{ $totalCredits }}</h3>
                <p>Total Credits</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#section-register" class="action-btn">
            <div class="action-icon">&#43;</div>
            Register Courses
        </a>
        <a href="#section-courses" class="action-btn">
            <div class="action-icon">&#128218;</div>
            My Courses
        </a>
        <a href="#section-timetable" class="action-btn">
            <div class="action-icon">&#128197;</div>
            View Timetable
        </a>
        <a href="#section-today" class="action-btn">
            <div class="action-icon">&#128336;</div>
            Today's Classes
        </a>
        <a href="#section-fees" class="action-btn">
            <div class="action-icon">&#128178;</div>
            Fee Payment
        </a>
        <a href="#section-profile" class="action-btn">
            <div class="action-icon">&#128100;</div>
            My Profile
        </a>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;border:1px solid #a7f3d0;">
        &#10003; {{ session('success') }}
    </div>
    @endif

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- =================== FEE PAYMENT =================== -->
        <div class="dashboard-card full-width" id="section-fees">
            <div class="card-header">
                <h3>Fee Payment &mdash; Semester {{ $semester }}</h3>
                @if($feeRecord)
                    <span class="fee-status-badge {{ $feeRecord->status }}">{{ ucfirst($feeRecord->status) }}</span>
                @else
                    <span class="fee-status-badge none">No Record</span>
                @endif
            </div>
            <div class="card-body">
                @if(!$feeRecord)
                    <div class="fee-banner unpaid">
                        <span style="font-size:1.2rem">&#9888;</span>
                        <div>No fee record found for Semester {{ $semester }}. Please contact the administration office.</div>
                    </div>
                @elseif($feeRecord->status === 'paid')
                    <div class="fee-banner paid">
                        <span style="font-size:1.2rem">&#10003;</span>
                        <div>
                            <strong>Fully Paid</strong> &mdash; Your fee of ${{ number_format($feeRecord->amount, 2) }} has been received.
                            @if($feeRecord->paid_at) Paid on {{ \Carbon\Carbon::parse($feeRecord->paid_at)->format('M d, Y') }}. @endif
                        </div>
                    </div>
                    @if($enrolledCourses->count() > 0)
                    <table class="data-table" style="margin-top:14px;">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Code</th>
                                <th>Credits</th>
                                <th style="text-align:right">Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolledCourses as $ec)
                            <tr>
                                <td>{{ $ec->name }}</td>
                                <td>{{ $ec->code }}</td>
                                <td>{{ $ec->credits }} cr</td>
                                <td style="text-align:right">${{ number_format($ec->fee ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr style="font-weight:600;background:#f9fafb;">
                                <td colspan="3" style="text-align:right">Total Paid</td>
                                <td style="text-align:right;color:#065f46;">${{ number_format($feeRecord->amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @endif
                @else
                    <div class="fee-card-body">
                        <div class="fee-info-row">
                            <div class="fee-info-item">
                                <div class="fee-info-label">Total Fee Due</div>
                                <div class="fee-info-value">${{ number_format($feeRecord->amount, 2) }}</div>
                            </div>
                            @if($feeRecord->paid_amount)
                            <div class="fee-info-item">
                                <div class="fee-info-label">Amount Paid</div>
                                <div class="fee-info-value" style="color:#2563eb;">${{ number_format($feeRecord->paid_amount, 2) }}</div>
                            </div>
                            <div class="fee-info-item">
                                <div class="fee-info-label">Balance Remaining</div>
                                <div class="fee-info-value" style="color:#dc2626;">${{ number_format($feeRecord->amount - $feeRecord->paid_amount, 2) }}</div>
                            </div>
                            @endif
                            <div class="fee-info-item">
                                <div class="fee-info-label">Status</div>
                                <div class="fee-info-value">
                                    <span class="fee-status-badge {{ $feeRecord->status }}">{{ ucfirst($feeRecord->status) }}</span>
                                </div>
                            </div>
                        </div>

                        @if($feeRecord->status === 'partial')
                        <div class="fee-banner partial">
                            <span style="font-size:1.2rem">&#8505;</span>
                            <div><strong>Partial Payment Recorded</strong> &mdash; Full payment is required to register for courses. Please pay the remaining balance below.</div>
                        </div>
                        @elseif($feeRecord->status === 'overdue')
                        <div class="fee-banner unpaid" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5;">
                            <span style="font-size:1.2rem">&#9888;</span>
                            <div><strong>Fee Overdue</strong> &mdash; Please settle your outstanding fee immediately to avoid enrollment restrictions.</div>
                        </div>
                        @else
                        <div class="fee-banner unpaid">
                            <span style="font-size:1.2rem">&#9888;</span>
                            <div><strong>Payment Pending</strong> &mdash; Pay your fee to unlock course registration for Semester {{ $semester }}.</div>
                        </div>
                        @endif

                        @if($enrolledCourses->count() > 0)
                        <div>
                            <div style="font-size:0.8rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Fee Breakdown</div>
                            <table class="data-table" style="font-size:0.875rem;">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Code</th>
                                        <th>Credits</th>
                                        <th style="text-align:right">Fee</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enrolledCourses as $ec)
                                    <tr>
                                        <td>{{ $ec->name }}</td>
                                        <td>{{ $ec->code }}</td>
                                        <td>{{ $ec->credits }} cr</td>
                                        <td style="text-align:right">${{ number_format($ec->fee ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr style="font-weight:600;background:#f9fafb;">
                                        <td colspan="3" style="text-align:right">Total Due</td>
                                        <td style="text-align:right;color:#dc2626;">${{ number_format($feeRecord->amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <div class="fee-pay-section">
                            <!-- Pay in Full -->
                            <form method="POST" action="{{ route('student.fees.pay', $feeRecord->id) }}"
                                  onsubmit="return confirm('Confirm full payment of ${{ number_format($feeRecord->amount, 2) }}?')">
                                @csrf
                                <input type="hidden" name="payment_type" value="full">
                                <button type="submit" class="btn-pay-full">&#10003; Pay in Full (${{ number_format($feeRecord->amount, 2) }})</button>
                            </form>

                            <!-- Pay Partially -->
                            <form method="POST" action="{{ route('student.fees.pay', $feeRecord->id) }}"
                                  class="partial-pay-form"
                                  onsubmit="return validatePartialPay(this, {{ $feeRecord->amount }})">
                                @csrf
                                <input type="hidden" name="payment_type" value="partial">
                                <input type="number" name="paid_amount" class="partial-amount-input"
                                       placeholder="Enter amount" step="0.01" min="1" max="{{ $feeRecord->amount }}">
                                <button type="submit" class="btn-pay-partial">Pay Partially</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- =================== REGISTER COURSES =================== -->
        <div class="dashboard-card full-width" id="section-register">
            <div class="card-header">
                <h3>Register for Courses &mdash; Semester {{ $semester }}</h3>
                <span class="badge badge-primary">{{ $availableSections->count() }} Available</span>
            </div>
            <div class="card-body">
                @if(!$feePaid)
                    <div class="fee-banner unpaid">
                        <span style="font-size:1.2rem">&#9888;</span>
                        <div>
                            <strong>Fee Payment Required</strong> &mdash; You must complete your fee payment for Semester {{ $semester }} before you can register for courses. Please contact the administration office.
                        </div>
                    </div>
                @else
                    <div class="fee-banner paid">
                        <span style="font-size:1.2rem">&#10003;</span>
                        <div>
                            <strong>Fees Paid</strong> &mdash; Your fee payment for Semester {{ $semester }} has been confirmed. You may register for courses below.
                        </div>
                    </div>
                @endif

                @if($feePaid && $availableSections->count() > 0)
                    <div class="filter-row">
                        <input type="text" class="section-search" id="regSearch" placeholder="Search course name or code..." onkeyup="filterRegTable()">
                    </div>
                    <table class="register-table data-table" id="regTable">
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
                                    $course     = $section->course;
                                    $assignment = $section->assignments->first();
                                    $teacher    = $assignment ? $assignment->teacher : null;
                                    $filled     = $section->enrolled_students;
                                    $max        = $section->max_students;
                                    $pct        = $max > 0 ? round($filled / $max * 100) : 0;
                                    $barClass   = $pct >= 90 ? 'near-full' : '';
                                @endphp
                                <tr>
                                    <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                                    <td>{{ $course->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge-dept">{{ $course->department->name ?? 'N/A' }}</span>
                                    </td>
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

        <!-- =================== ENROLLED COURSES =================== -->
        <div class="dashboard-card full-width" id="section-courses">
            <div class="card-header">
                <h3>My Courses</h3>
                <span class="badge badge-primary">{{ $courseCount }} Enrolled</span>
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
                                <th>Status</th>
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
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $course->teacherName ?? 'TBA' }}</td>
                                    <td>
                                        @if($course->registrationStatus === 'completed')
                                            <span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;">&#10003; Completed</span>
                                        @else
                                            <span style="background:#e0e7ff;color:#3730a3;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;">Enrolled</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($course->registrationStatus === 'completed')
                                            <span style="color:#9ca3af;font-size:0.8rem;">—</span>
                                        @else
                                            <form method="POST" action="{{ route('student.courses.drop', $course->registrationId) }}"
                                                  onsubmit="return confirm('Drop {{ addslashes($course->name) }}?')">
                                                @csrf
                                                <button type="submit" class="btn-drop">Drop</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">&#128218;</div>
                        <p>No courses enrolled yet. Use the <a href="#section-register" style="color:#4f46e5">Register Courses</a> section above to enroll.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- =================== TODAY'S CLASSES =================== -->
        <div class="dashboard-card" id="section-today">
            <div class="card-header">
                <h3>Today's Classes</h3>
                <span class="badge badge-success">{{ now()->format('l') }}</span>
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
                        <p>No classes scheduled for today</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- =================== PROFILE =================== -->
        <div class="dashboard-card" id="section-profile">
            <div class="card-header">
                <h3>My Profile</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ Auth::user()->username }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value">{{ $department }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Semester</div>
                        <div class="info-value">{{ $semester }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Enrolled Credits</div>
                        <div class="info-value">{{ $totalCredits }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== WEEKLY TIMETABLE =================== -->
        <div class="dashboard-card full-width" id="section-timetable">
            <div class="card-header">
                <h3>My Weekly Timetable</h3>
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
                            <p>No timetable generated yet. Enroll in courses first.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
function filterRegTable() {
    const val = document.getElementById('regSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#regTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
    });
}

function validatePartialPay(form, totalFee) {
    const input = form.querySelector('[name="paid_amount"]');
    const amount = parseFloat(input.value);
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount.');
        return false;
    }
    if (amount >= totalFee) {
        alert('For full payment, please use the "Pay in Full" button.');
        return false;
    }
    return confirm('Confirm partial payment of $' + amount.toFixed(2) + '? Note: Full payment is required to register for courses.');
}
</script>
@endpush
