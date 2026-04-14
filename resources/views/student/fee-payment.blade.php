@extends('layouts.dashboard')

@section('title', 'Fee Payment')
@section('role-label', 'Student Panel')
@section('page-title', 'Fee Payment')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
/* ── Invoice Header ── */
.invoice-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
    border-radius: 14px;
    padding: 24px 28px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    color: #fff;
    box-shadow: 0 4px 16px rgba(29,78,216,0.18);
}
.invoice-institution { display: flex; flex-direction: column; gap: 3px; }
.invoice-institution h2 { margin: 0; font-size: 1.1rem; font-weight: 800; letter-spacing: -0.02em; }
.invoice-institution p { margin: 0; font-size: 0.78rem; opacity: 0.75; }
.invoice-meta-right { text-align: right; }
.invoice-title {
    font-size: 1.4rem;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.9);
    margin-bottom: 8px;
}
.invoice-meta-table { font-size: 0.8rem; opacity: 0.85; }
.invoice-meta-table td { padding: 2px 0 2px 14px; }
.invoice-meta-table td:first-child { font-weight: 700; padding-left: 0; text-align: right; }
@media(max-width:600px){
    .invoice-header { flex-direction: column; }
    .invoice-meta-right { text-align: left; }
    .invoice-meta-table td:first-child { text-align: left; }
}

/* ── Student Info Strip ── */
.student-info-strip {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    margin-bottom: 20px;
}
@media(max-width:640px){ .student-info-strip { grid-template-columns: 1fr 1fr; } }
.si-cell { padding: 6px 12px; border-right: 1px solid #f3f4f6; }
.si-cell:first-child { padding-left: 0; }
.si-cell:last-child { border-right: none; }
.si-lbl { font-size: 0.67rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; margin-bottom: 3px; }
.si-val { font-size: 0.875rem; font-weight: 700; color: #111827; }

/* ── Status Badges ── */
.sbadge { display:inline-flex;align-items:center;gap:5px;padding:4px 14px;border-radius:20px;font-size:0.78rem;font-weight:700; }
.sbadge.paid    { background:#d1fae5;color:#065f46; }
.sbadge.pending { background:#fef3c7;color:#92400e; }
.sbadge.overdue { background:#fee2e2;color:#991b1b; }
.sbadge.partial { background:#dbeafe;color:#1e40af; }

/* ── Summary Stat Cards ── */
.fee-summary-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
    margin-bottom:20px;
}
@media(max-width:640px){ .fee-summary-grid{ grid-template-columns:1fr 1fr; } }
.fee-sum-card {
    border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;
    display:flex;flex-direction:column;gap:5px;background:#fff;
}
.fee-sum-label { font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af; }
.fee-sum-value { font-size:1.3rem;font-weight:800;color:#111827;line-height:1.2; }
.fee-sum-value.green  { color:#059669; }
.fee-sum-value.blue   { color:#2563eb; }
.fee-sum-value.red    { color:#dc2626; }

/* ── Progress Bar ── */
.progress-wrap { margin-bottom:20px; }
.progress-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;font-size:0.82rem; }
.progress-track { height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden; }
.progress-fill  { height:10px;border-radius:999px;background:linear-gradient(90deg,#4f46e5,#7c3aed);transition:width .4s; }
.progress-fill.full { background:linear-gradient(90deg,#059669,#10b981); }

/* ── Alert Banners ── */
.fee-alert { display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border-radius:10px;margin-bottom:18px;font-size:0.875rem; }
.fee-alert.success { background:#d1fae5;color:#065f46;border:1px solid #a7f3d0; }
.fee-alert.warning { background:#fef3c7;color:#92400e;border:1px solid #fde68a; }
.fee-alert.danger  { background:#fee2e2;color:#991b1b;border:1px solid #fca5a5; }
.fee-alert.info    { background:#dbeafe;color:#1e40af;border:1px solid #93c5fd; }
.fee-alert-icon    { font-size:1.1rem;flex-shrink:0;margin-top:1px; }

/* ── Fee Breakdown Table ── */
.breakdown-table { width:100%;border-collapse:collapse;font-size:0.875rem;margin-bottom:0; }
.breakdown-table thead th {
    background:#f9fafb;padding:9px 14px;text-align:left;
    font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    color:#6b7280;border-bottom:1px solid #e5e7eb;
}
.breakdown-table thead th:last-child { text-align:right; }
.breakdown-table tbody td { padding:10px 14px;border-bottom:1px solid #f3f4f6;color:#374151; }
.breakdown-table tbody td:last-child { text-align:right;font-weight:600; }
.breakdown-table tfoot td { padding:10px 14px;font-weight:700;background:#f9fafb;color:#111827;border-top:2px solid #e5e7eb; }
.breakdown-table tfoot td:last-child { text-align:right; }

/* ── Payment Cards ── */
.pay-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:4px; }
@media(max-width:600px){ .pay-grid{ grid-template-columns:1fr; } }
.pay-card {
    border:1.5px solid #e5e7eb;border-radius:12px;padding:18px 20px;
    background:#fafafa;display:flex;flex-direction:column;gap:10px;
}
.pay-card-title { font-size:0.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#374151; }
.pay-card-desc  { font-size:0.82rem;color:#6b7280;line-height:1.5;margin:0; }
.btn-full {
    width:100%;display:flex;align-items:center;justify-content:center;gap:8px;
    background:#059669;color:#fff;border:none;border-radius:8px;
    padding:11px;font-size:0.9rem;font-weight:700;cursor:pointer;transition:background .15s;
}
.btn-full:hover { background:#047857; }
.partial-row { display:flex;gap:8px; }
.partial-input {
    flex:1;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:8px;
    font-size:0.875rem;outline:none;min-width:0;
}
.partial-input:focus { border-color:#4f46e5; }
.btn-partial {
    display:flex;align-items:center;gap:6px;
    background:#2563eb;color:#fff;border:none;border-radius:8px;
    padding:10px 16px;font-size:0.85rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s;
}
.btn-partial:hover { background:#1d4ed8; }
.stripe-note { font-size:0.73rem;color:#9ca3af;display:flex;align-items:center;gap:5px; }

/* ── Section heading ── */
.sec-heading { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;margin-bottom:10px; }

/* ── Retake table ── */
.retake-paid-row td { background:#f0fdf4; }
.btn-retake {
    background:#7c3aed;color:#fff;border:none;border-radius:6px;
    padding:6px 14px;font-size:0.8rem;font-weight:600;cursor:pointer;transition:background .15s;
}
.btn-retake:hover { background:#6d28d9; }

/* ── Help Box ── */
.help-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px 20px;
    margin-top: 20px;
    font-size: 0.82rem;
    color: #6b7280;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
</style>
@endpush

@section('content')

{{-- Flash Messages --}}
@if(session('success'))
<div class="fee-alert success" style="margin-bottom:16px;">
    <span class="fee-alert-icon">&#10003;</span>
    <div>{{ session('success') }}</div>
</div>
@endif
@if(session('error'))
<div class="fee-alert danger" style="margin-bottom:16px;">
    <span class="fee-alert-icon">&#9888;</span>
    <div>{{ session('error') }}</div>
</div>
@endif

{{-- ── Invoice Header ── --}}
<div class="invoice-header">
    <div class="invoice-institution">
        <h2>&#127979; Confederation College</h2>
        <p>Office of Student Financial Services</p>
        <p style="margin-top:8px;font-size:0.75rem;opacity:0.65;">For fee inquiries contact: finance@college.edu</p>
    </div>
    <div class="invoice-meta-right">
        <div class="invoice-title">Tuition Invoice</div>
        <table class="invoice-meta-table">
            <tr>
                <td>Invoice Ref:</td>
                <td>FEE-{{ now()->year }}-{{ str_pad($feeRecord?->id ?? 0, 4, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td>Issued:</td>
                <td>{{ now()->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td>Academic Year:</td>
                <td>{{ now()->year }}&ndash;{{ now()->year + 1 }}</td>
            </tr>
            <tr>
                <td>Status:</td>
                <td>
                    @if($feeRecord)
                        @php $st = $feeRecord->status; @endphp
                        <span class="sbadge {{ $st }}">
                            {{ $st === 'paid' ? '✓' : ($st === 'overdue' ? '⚠' : '•') }} {{ ucfirst($st) }}
                        </span>
                    @else
                        <span class="sbadge pending">• Pending</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Student Info Strip ── --}}
<div class="student-info-strip">
    <div class="si-cell">
        <div class="si-lbl">Student Name</div>
        <div class="si-val">{{ $studentRecord?->name ?? Auth::user()->username }}</div>
    </div>
    <div class="si-cell">
        <div class="si-lbl">Student ID</div>
        <div class="si-val">{{ $studentRecord?->roll_no ?? Auth::user()->username }}</div>
    </div>
    <div class="si-cell">
        <div class="si-lbl">Program</div>
        <div class="si-val">{{ $studentRecord?->department?->name ?? 'N/A' }}</div>
    </div>
    <div class="si-cell">
        <div class="si-lbl">Semester</div>
        <div class="si-val">Semester {{ $semester }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════
     SEMESTER FEE CARD
══════════════════════════════════════ --}}
<div class="dashboard-card" style="margin-bottom:24px;">
    <div class="card-header">
        <div>
            <h3 style="margin:0;">Semester {{ $semester }} &mdash; Tuition Fee</h3>
            <p style="margin:4px 0 0;font-size:0.82rem;color:#6b7280;">Academic registration fee for the current semester</p>
        </div>
        @if($feeRecord)
            @php $statusIcon = $feeRecord->status === 'paid' ? '✓' : ($feeRecord->status === 'overdue' ? '⚠' : '•'); @endphp
            <span class="sbadge {{ $feeRecord->status }}">{{ $statusIcon }} {{ ucfirst($feeRecord->status) }}</span>
        @else
            <span class="sbadge" style="background:#f3f4f6;color:#6b7280;">No Record</span>
        @endif
    </div>

    <div class="card-body">

    @if(!$feeRecord)
        <div class="fee-alert warning">
            <span class="fee-alert-icon">&#9888;</span>
            <div>No fee record found for Semester {{ $semester }}. Please visit the administration office.</div>
        </div>
    @else
        @php
            $paidAmt   = (float)($feeRecord->paid_amount ?? 0);
            $totalAmt  = (float)$feeRecord->amount;
            $remaining = max(0, $totalAmt - $paidAmt);
            $pct       = $totalAmt > 0 ? min(100, round($paidAmt / $totalAmt * 100)) : 0;
            $isPaid    = $feeRecord->status === 'paid';
        @endphp

        {{-- Zero-amount guard: admin hasn't set the fee yet --}}
        @if($totalAmt == 0)
        <div class="fee-alert warning">
            <span class="fee-alert-icon">&#9888;</span>
            <div>
                <strong>Fee amount not set.</strong> &mdash;
                The administration has not yet configured your Semester {{ $semester }} tuition fee.
                Please visit the Student Finance Office or email <strong>finance@college.edu</strong> for assistance.
            </div>
        </div>
        @else

        {{-- Summary stat cards --}}
        <div class="fee-summary-grid">
            <div class="fee-sum-card">
                <div class="fee-sum-label">Total Fee</div>
                <div class="fee-sum-value">${{ number_format($totalAmt, 2) }}</div>
            </div>
            <div class="fee-sum-card">
                <div class="fee-sum-label">Amount Paid</div>
                <div class="fee-sum-value {{ $paidAmt > 0 ? 'blue' : '' }}">${{ number_format($paidAmt, 2) }}</div>
            </div>
            <div class="fee-sum-card">
                <div class="fee-sum-label">{{ $isPaid ? 'Status' : 'Balance Due' }}</div>
                @if($isPaid)
                    <div style="margin-top:4px;"><span class="sbadge paid">&#10003; Fully Paid</span></div>
                @else
                    <div class="fee-sum-value red">${{ number_format($remaining, 2) }}</div>
                @endif
            </div>
        </div>

        {{-- Payment progress bar --}}
        <div class="progress-wrap">
            <div class="progress-header">
                <span style="font-weight:600;color:#374151;font-size:0.85rem;">
                    @if($isPaid) Payment Complete @elseif($paidAmt > 0) Partially Paid @else Unpaid @endif
                </span>
                <span style="font-weight:700;color:{{ $isPaid ? '#059669' : '#374151' }};font-size:0.85rem;">
                    {{ $pct }}% paid
                </span>
            </div>
            <div class="progress-track">
                <div class="progress-fill {{ $isPaid ? 'full' : '' }}" style="width:{{ $pct }}%"></div>
            </div>
            @if(!$isPaid && $paidAmt > 0)
            <div style="margin-top:5px;font-size:0.76rem;color:#6b7280;">
                ${{ number_format($paidAmt, 2) }} paid &mdash; ${{ number_format($remaining, 2) }} remaining
            </div>
            @endif
        </div>

        {{-- Status banner --}}
        @if($isPaid)
        <div class="fee-alert success">
            <span class="fee-alert-icon">&#10003;</span>
            <div>
                <strong>Payment Complete</strong> &mdash;
                Your Semester {{ $semester }} fee of <strong>${{ number_format($totalAmt, 2) }}</strong> has been fully received.
                @if($feeRecord->paid_at)
                    Confirmed on <strong>{{ \Carbon\Carbon::parse($feeRecord->paid_at)->format('M d, Y') }}</strong>.
                @endif
                You are cleared to register for courses.
            </div>
        </div>
        @elseif($feeRecord->status === 'partial')
        <div class="fee-alert info">
            <span class="fee-alert-icon">&#8505;</span>
            <div><strong>Partial Payment Received</strong> &mdash; <strong>${{ number_format($remaining, 2) }}</strong> still outstanding. Full payment is required to unlock course registration.</div>
        </div>
        @elseif($feeRecord->status === 'overdue')
        <div class="fee-alert danger">
            <span class="fee-alert-icon">&#9888;</span>
            <div><strong>Fee Overdue</strong> &mdash; Please settle your outstanding balance of <strong>${{ number_format($remaining, 2) }}</strong> immediately to avoid academic hold.</div>
        </div>
        @else
        <div class="fee-alert warning">
            <span class="fee-alert-icon">&#9888;</span>
            <div><strong>Payment Pending</strong> &mdash; Pay your Semester {{ $semester }} fee to unlock course registration.</div>
        </div>
        @endif

        {{-- Fee breakdown --}}
        @if($deptFee !== null || $enrolledCourses->count() > 0)
        <div class="sec-heading">Fee Breakdown</div>
        <div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:22px;">
            @if($deptFee !== null)
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Semester {{ $semester }} Registration &amp; Tuition Fee
                            <div style="font-size:0.73rem;color:#9ca3af;margin-top:2px;">Academic Year {{ now()->year }}&ndash;{{ now()->year + 1 }}</div>
                        </td>
                        <td style="color:{{ $isPaid ? '#059669' : '#dc2626' }};">${{ number_format($totalAmt, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>{{ $isPaid ? 'Total Paid' : 'Total Due' }}</td>
                        <td style="color:{{ $isPaid ? '#059669' : '#dc2626' }};">${{ number_format($totalAmt, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Credits</th>
                        <th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrolledCourses as $ec)
                    <tr>
                        <td>{{ $ec->name }}</td>
                        <td><strong>{{ $ec->code }}</strong></td>
                        <td>{{ $ec->credits }} cr</td>
                        <td>${{ number_format($ec->fee ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">{{ $isPaid ? 'Total Paid' : 'Total Due' }}</td>
                        <td style="color:{{ $isPaid ? '#059669' : '#dc2626' }};">${{ number_format($totalAmt, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
        @endif

        {{-- Payment actions (only shown when there is something to pay) --}}
        @if(!$isPaid && $remaining > 0)
        <div class="sec-heading">Make a Payment</div>
        <div class="pay-grid">

            {{-- Pay in Full --}}
            <div class="pay-card">
                <div class="pay-card-title">&#9654; Pay in Full</div>
                <p class="pay-card-desc">
                    Clear the remaining <strong>${{ number_format($remaining, 2) }}</strong> in one payment
                    to immediately unlock course registration.
                </p>
                <form method="POST" action="{{ route('student.fees.stripe.checkout', $feeRecord->id) }}">
                    @csrf
                    <input type="hidden" name="payment_type" value="full">
                    <button type="submit" class="btn-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                        Pay ${{ number_format($remaining, 2) }} via Stripe
                    </button>
                </form>
                <div class="stripe-note">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#9ca3af"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                    Secured by Stripe &mdash; card details never stored
                </div>
            </div>

            {{-- Pay Partially --}}
            <div class="pay-card">
                <div class="pay-card-title">&#9654; Pay Partially</div>
                <p class="pay-card-desc">
                    Make a partial payment now. <strong>Full payment</strong> is required
                    before course registration is unlocked.
                </p>
                <form method="POST" action="{{ route('student.fees.stripe.checkout', $feeRecord->id) }}"
                      onsubmit="return confirmPartial(this, {{ $remaining }})">
                    @csrf
                    <input type="hidden" name="payment_type" value="partial">
                    <div class="partial-row">
                        <input type="number" name="paid_amount" class="partial-input"
                               placeholder="Amount (e.g. 500.00)"
                               step="0.01" min="1" max="{{ max(0, $remaining - 0.01) }}">
                        <button type="submit" class="btn-partial">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            Pay via Stripe
                        </button>
                    </div>
                </form>
                <div class="stripe-note">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#9ca3af"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                    Secured by Stripe &mdash; card details never stored
                </div>
            </div>

        </div>
        @endif

        {{-- Receipt section when paid --}}
        @if($isPaid && $feeRecord->paid_at)
        <div style="margin-top:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;">
            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#059669;margin-bottom:10px;">&#10003; Payment Receipt</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;">
                <div style="padding-right:16px;border-right:1px solid #bbf7d0;">
                    <div style="font-size:0.68rem;color:#6b7280;text-transform:uppercase;font-weight:600;margin-bottom:3px;">Date Paid</div>
                    <div style="font-weight:700;color:#111827;font-size:0.88rem;">{{ \Carbon\Carbon::parse($feeRecord->paid_at)->format('M d, Y') }}</div>
                </div>
                <div style="padding:0 16px;border-right:1px solid #bbf7d0;">
                    <div style="font-size:0.68rem;color:#6b7280;text-transform:uppercase;font-weight:600;margin-bottom:3px;">Amount Paid</div>
                    <div style="font-weight:700;color:#059669;font-size:0.88rem;">${{ number_format($paidAmt, 2) }}</div>
                </div>
                <div style="padding-left:16px;">
                    <div style="font-size:0.68rem;color:#6b7280;text-transform:uppercase;font-weight:600;margin-bottom:3px;">Reference</div>
                    <div style="font-weight:700;color:#111827;font-size:0.88rem;">FEE-{{ now()->year }}-{{ str_pad($feeRecord->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>
        @endif

        @endif {{-- end $totalAmt == 0 guard --}}

    @endif
    </div>
</div>

{{-- ══════════════════════════════════════
     COURSE RETAKE FEES CARD
══════════════════════════════════════ --}}
@if(isset($supplementalFees) && $supplementalFees->isNotEmpty())
@php
    $pendingRetakes = $supplementalFees->where('status', '!=', 'paid');
    $paidRetakes    = $supplementalFees->where('status', 'paid');
@endphp
<div class="dashboard-card" style="margin-bottom:20px;">
    <div class="card-header">
        <div>
            <h3 style="margin:0;">&#8635; Course Retake Fees</h3>
            <p style="margin:4px 0 0;font-size:0.82rem;color:#6b7280;">
                Pay the retake fee for each failed course to unlock re-registration
            </p>
        </div>
        <div style="display:flex;gap:8px;">
            @if($pendingRetakes->isNotEmpty())
                <span class="sbadge pending">{{ $pendingRetakes->count() }} Pending</span>
            @endif
            @if($paidRetakes->isNotEmpty())
                <span class="sbadge paid">{{ $paidRetakes->count() }} Paid</span>
            @endif
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Code</th>
                    <th>Semester</th>
                    <th>Retake Fee</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplementalFees as $sf)
                @php $sfPaid = $sf->status === 'paid'; @endphp
                <tr class="{{ $sfPaid ? 'retake-paid-row' : '' }}">
                    <td><strong>{{ $sf->course?->name ?? 'N/A' }}</strong></td>
                    <td>
                        <span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:6px;font-size:0.78rem;font-weight:600;">
                            {{ $sf->course?->code ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($sf->course?->semester)
                            <span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:8px;font-size:0.75rem;">
                                Sem {{ $sf->course->semester }}
                            </span>
                        @else
                            <span style="color:#9ca3af">—</span>
                        @endif
                    </td>
                    <td style="font-weight:700;color:{{ $sfPaid ? '#059669' : '#dc2626' }};">
                        ${{ number_format($sf->amount, 2) }}
                    </td>
                    <td>
                        <span class="sbadge {{ $sf->status }}">{{ $sfPaid ? '✓' : '•' }} {{ ucfirst($sf->status) }}</span>
                    </td>
                    <td>
                        @if(!$sfPaid)
                            <form method="POST" action="{{ route('student.fees.stripe.checkout', $sf->id) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="payment_type" value="full">
                                <button type="submit" class="btn-retake">
                                    Pay ${{ number_format($sf->amount, 2) }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('student.register-courses') }}"
                               style="color:#059669;font-weight:700;font-size:0.85rem;text-decoration:none;">
                                &#10003; Paid &mdash; Re-Register &rarr;
                            </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:12px 18px;font-size:0.8rem;color:#6b7280;border-top:1px solid #f3f4f6;background:#fafafa;border-radius:0 0 12px 12px;">
            &#8505;&nbsp; After paying the retake fee, visit
            <a href="{{ route('student.register-courses') }}" style="color:#4f46e5;font-weight:600;">Register Courses</a>
            to re-enrol in the failed course.
        </div>
    </div>
</div>
@endif

{{-- Help Box --}}
<div class="help-box">
    <span style="font-size:1.1rem;">&#8505;</span>
    <div>
        <strong style="color:#374151;">Need help with your fee payment?</strong><br>
        Visit the Student Finance Office or email <strong>finance@college.edu</strong>.
        Office hours: Monday&ndash;Friday, 9:00 AM &ndash; 4:00 PM.
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmPartial(form, remaining) {
    const input  = form.querySelector('[name="paid_amount"]');
    const amount = parseFloat(input.value);
    if (!amount || amount <= 0) {
        alert('Please enter a valid payment amount.');
        return false;
    }
    if (amount >= remaining) {
        alert('For the full balance, please use the "Pay in Full" button.');
        return false;
    }
    return confirm(
        'Confirm partial payment of $' + amount.toFixed(2) + '?\n\n' +
        'Reminder: full payment of $' + remaining.toFixed(2) + ' is needed to unlock course registration.'
    );
}
</script>
@endpush
