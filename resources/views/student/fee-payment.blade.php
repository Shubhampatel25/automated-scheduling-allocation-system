@extends('layouts.dashboard')

@section('title', 'Fee Payment')
@section('role-label', 'Student Panel')
@section('page-title', 'Fee Payment')

@section('sidebar-nav')
    @include('student.partials.sidebar')
@endsection

@push('styles')
<style>
    .fee-banner { padding:14px 18px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:10px; }
    .fee-banner.paid    { background:#d1fae5;color:#065f46;border:1px solid #a7f3d0; }
    .fee-banner.unpaid  { background:#fef3c7;color:#92400e;border:1px solid #fde68a; }
    .fee-banner.partial { background:#dbeafe;color:#1e40af;border:1px solid #93c5fd; }
    .fee-card-body { display:flex;flex-direction:column;gap:18px; }
    .fee-info-row { display:flex;flex-wrap:wrap;gap:24px; }
    .fee-info-item { flex:1;min-width:140px; }
    .fee-info-label { font-size:0.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px; }
    .fee-info-value { font-size:1.1rem;font-weight:600;color:#111827; }
    .fee-status-badge { display:inline-block;padding:3px 12px;border-radius:12px;font-size:0.8rem;font-weight:600; }
    .fee-status-badge.paid    { background:#d1fae5;color:#065f46; }
    .fee-status-badge.pending { background:#fef3c7;color:#92400e; }
    .fee-status-badge.overdue { background:#fee2e2;color:#991b1b; }
    .fee-status-badge.partial { background:#dbeafe;color:#1e40af; }
    .fee-status-badge.none    { background:#f3f4f6;color:#6b7280; }
    .fee-pay-section { display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start; }
    .btn-pay-full { background:#16a34a;color:#fff;border:none;padding:9px 20px;border-radius:7px;cursor:pointer;font-size:0.88rem;font-weight:600; }
    .btn-pay-full:hover { background:#15803d; }
    .partial-pay-form { display:flex;gap:8px;align-items:center;flex-wrap:wrap; }
    .partial-amount-input { padding:8px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:0.88rem;width:140px; }
    .btn-pay-partial { background:#2563eb;color:#fff;border:none;padding:9px 18px;border-radius:7px;cursor:pointer;font-size:0.88rem;font-weight:600; }
    .btn-pay-partial:hover { background:#1d4ed8; }
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
            @if($deptFee !== null)
            <table class="data-table" style="margin-top:14px;">
                <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Semester {{ $semester }} Registration Fee</td>
                        <td style="text-align:right;color:#065f46;font-weight:600;">${{ number_format($feeRecord->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @elseif($enrolledCourses->count() > 0)
            <table class="data-table" style="margin-top:14px;">
                <thead>
                    <tr><th>Course</th><th>Code</th><th>Credits</th><th style="text-align:right">Fee</th></tr>
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
                    <div><strong>Partial Payment Recorded</strong> &mdash; Full payment is required to register for courses.</div>
                </div>
                @elseif($feeRecord->status === 'overdue')
                <div class="fee-banner unpaid" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5;">
                    <span style="font-size:1.2rem">&#9888;</span>
                    <div><strong>Fee Overdue</strong> &mdash; Please settle your outstanding fee immediately.</div>
                </div>
                @else
                <div class="fee-banner unpaid">
                    <span style="font-size:1.2rem">&#9888;</span>
                    <div><strong>Payment Pending</strong> &mdash; Pay your fee to unlock course registration for Semester {{ $semester }}.</div>
                </div>
                @endif

                @if($deptFee !== null)
                <div>
                    <div style="font-size:0.8rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Fee Details</div>
                    <table class="data-table" style="font-size:0.875rem;">
                        <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Semester {{ $semester }} Registration Fee</td>
                                <td style="text-align:right;font-weight:600;color:#dc2626;">${{ number_format($feeRecord->amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @elseif($enrolledCourses->count() > 0)
                <div>
                    <div style="font-size:0.8rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Fee Breakdown</div>
                    <table class="data-table" style="font-size:0.875rem;">
                        <thead>
                            <tr><th>Course</th><th>Code</th><th>Credits</th><th style="text-align:right">Fee</th></tr>
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
                    <form method="POST" action="{{ route('student.fees.stripe.checkout', $feeRecord->id) }}">
                        @csrf
                        <input type="hidden" name="payment_type" value="full">
                        <button type="submit" class="btn-pay-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:4px"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            Pay in Full via Stripe (${{ number_format($feeRecord->amount, 2) }})
                        </button>
                    </form>

                    <form method="POST" action="{{ route('student.fees.stripe.checkout', $feeRecord->id) }}"
                          class="partial-pay-form"
                          onsubmit="return validatePartialPay(this, {{ $feeRecord->amount }})">
                        @csrf
                        <input type="hidden" name="payment_type" value="partial">
                        <input type="number" name="paid_amount" class="partial-amount-input"
                               placeholder="Enter amount" step="0.01" min="1" max="{{ $feeRecord->amount - 0.01 }}">
                        <button type="submit" class="btn-pay-partial">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:4px"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            Pay Partially via Stripe
                        </button>
                    </form>
                </div>

                <p style="font-size:0.78rem;color:#6b7280;margin-top:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280" style="vertical-align:middle;margin-right:3px"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                    Payments are securely processed by <strong>Stripe</strong>. Your card details are never stored on our servers.
                </p>
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
function validatePartialPay(form, totalFee) {
    const input  = form.querySelector('[name="paid_amount"]');
    const amount = parseFloat(input.value);
    if (!amount || amount <= 0) { alert('Please enter a valid amount.'); return false; }
    if (amount >= totalFee) { alert('For full payment, please use the "Pay in Full" button.'); return false; }
    return confirm('Confirm partial payment of $' + amount.toFixed(2) + '? Full payment is required to register for courses.');
}
</script>
@endpush
