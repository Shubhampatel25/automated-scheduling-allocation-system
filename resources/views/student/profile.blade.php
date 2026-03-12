@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('role-label', 'Student Panel')
@section('page-title', 'My Profile')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@section('content')
<div class="dashboard-card">
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
            <div class="info-item">
                <div class="info-label">Fee Status</div>
                <div class="info-value">
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
@endsection
