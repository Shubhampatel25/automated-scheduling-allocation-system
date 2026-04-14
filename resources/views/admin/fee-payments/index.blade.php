@extends('layouts.dashboard')

@section('title', 'Manage Fee Payments')
@section('role-label', 'Admin Panel')
@section('page-title', 'Manage Fee Payments')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/manage.css') }}">
<style>
    /* status-pending overrides global to match fee context (amber-brown vs orange) */
    .status-pending { background: #fef3c7; color: #92400e; padding: 2px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; }
    .tab-bar { display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid #e5e7eb; }
    .tab-btn { padding: 10px 24px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #6b7280; margin-bottom: -2px; }
    .tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>
@endpush

@section('content')
<div class="manage-header">
    <div class="manage-title">
        <h2>Fee Payments</h2>
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> / Manage Fee Payments
        </div>
    </div>
    <div class="action-btns">
        <form method="POST" action="{{ route('admin.fee-payments.generate') }}" onsubmit="return confirm('Generate pending fee records for all students who don\'t have one yet for the current semester?')">
            @csrf
            <button type="submit" class="btn-add" style="background:#0891b2;">&#9881; Generate Pending Records</button>
        </form>
        <button class="btn-add" onclick="openModal()">+ Add Payment</button>
    </div>
</div>

@if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:8px;border:1px solid #a7f3d0;">
        <span>&#10003;</span> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;display:flex;align-items:center;gap:8px;border:1px solid #fca5a5;">
        <span>&#9888;</span> {{ session('error') }}
    </div>
@endif

<!-- Summary Totals -->
@if($summaryTotals)
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px;">
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:18px 20px;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px;">Total Records</div>
        <div style="font-size:1.5rem;font-weight:800;color:#1e293b;">{{ number_format($summaryTotals->total_records) }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:18px 20px;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px;">Total Fee Amount</div>
        <div style="font-size:1.5rem;font-weight:800;color:#1e293b;">${{ number_format($summaryTotals->total_amount ?? 0, 2) }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:18px 20px;border-left:4px solid #16a34a;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#16a34a;margin-bottom:6px;">Total Collected</div>
        <div style="font-size:1.5rem;font-weight:800;color:#16a34a;">${{ number_format($summaryTotals->total_paid ?? 0, 2) }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:18px 20px;border-left:4px solid #dc2626;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#dc2626;margin-bottom:6px;">Outstanding Balance</div>
        <div style="font-size:1.5rem;font-weight:800;color:#dc2626;">${{ number_format($summaryTotals->total_outstanding ?? 0, 2) }}</div>
    </div>
</div>
@endif

<!-- Tab Bar -->
<div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('students', this)">All Students Overview</button>
    <button class="tab-btn" onclick="switchTab('records', this)">Payment Records</button>
</div>

<!-- ===== TAB 1: ALL STUDENTS ===== -->
<div id="tab-students" class="tab-content active">
    <div class="dashboard-card" style="margin-top:0;border-radius:0 12px 12px 12px;">
        <div class="card-header">
            <h3>Students &mdash; Fee Status ({{ $currentYear }})</h3>
            <form method="GET" action="{{ route('admin.fee-payments.index') }}" id="searchFormStudents" style="display:contents">
                <div class="search-wrap">
                    <span class="si">&#128269;</span>
                    <input type="text" name="search" placeholder="Search by name or roll no..." value="{{ request('search') }}" onkeyup="debounceSearch('searchFormStudents')">
                </div>
            </form>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Department</th>
                        <th>Current Semester</th>
                        <th>Total Fee</th>
                        <th>Paid</th>
                        <th>Fee Status (Sem {{ $currentYear }})</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allStudents as $student)
                        @php
                            $semPayment = $student->feePayments->where('semester', $student->semester)->first();
                            $statusLabel = $semPayment ? $semPayment->status : null;
                        @endphp
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->roll_no }}</td>
                            <td>{{ $student->department->name ?? 'N/A' }}</td>
                            <td>Semester {{ $student->semester }}</td>
                            <td>
                                @if($semPayment)
                                    @if(($semPayment->amount ?? 0) > 0)
                                        <strong>${{ number_format($semPayment->amount, 2) }}</strong>
                                    @else
                                        <span style="color:#f59e0b;font-weight:600;font-size:.82rem;">&#9888; Not set</span>
                                    @endif
                                @else
                                    <span style="color:#94a3b8;font-size:.82rem;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($semPayment)
                                    ${{ number_format($semPayment->paid_amount ?? 0, 2) }}
                                @else
                                    <span style="color:#94a3b8;font-size:.82rem;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($semPayment)
                                    <span class="status-{{ $semPayment->status }}">{{ ucfirst($semPayment->status) }}</span>
                                    @if($semPayment->status === 'paid' && $semPayment->paid_at)
                                        <small style="color:#6b7280;display:block;margin-top:2px;">{{ \Carbon\Carbon::parse($semPayment->paid_at)->format('M d, Y') }}</small>
                                    @endif
                                @else
                                    <span class="status-none">No Record</span>
                                @endif
                            </td>
                            <td>
                                @if($semPayment)
                                    <div class="action-btns">
                                        <button class="btn-tbl-edit"
                                            onclick="editPayment({{ $semPayment->id }}, {{ $student->id }}, {{ $student->semester }}, {{ $semPayment->year }}, '{{ $semPayment->amount }}', '{{ $semPayment->status }}')">
                                            &#9998; Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.fee-payments.destroy', $semPayment->id) }}" style="display:contents" onsubmit="return confirm('Delete payment record for {{ addslashes($student->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-tbl-del">&#128465; Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <button class="btn-tbl-edit"
                                        onclick="openAddForStudent({{ $student->id }}, {{ $student->semester }})">
                                        + Add Payment
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:24px;color:#9ca3af">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div style="margin-top:16px">{{ $allStudents->links() }}</div>
        </div>
    </div>
</div>

<!-- ===== TAB 2: PAYMENT RECORDS ===== -->
<div id="tab-records" class="tab-content">
    <div class="dashboard-card" style="margin-top:0;border-radius:0 12px 12px 12px;">
        <div class="card-header">
            <h3>All Payment Records</h3>
            <div class="table-toolbar">
                <div class="rows-label">
                    Type
                    <select id="typeFilterSelect" onchange="applyRecordFilters()">
                        <option value="" {{ !$typeFilter ? 'selected' : '' }}>All Types</option>
                        <option value="regular"      {{ $typeFilter == 'regular'      ? 'selected' : '' }}>&#128203; Regular (Tuition)</option>
                        <option value="supplemental" {{ $typeFilter == 'supplemental' ? 'selected' : '' }}>&#8635; Retake Fee</option>
                    </select>
                </div>
                <div class="rows-label">
                    Status
                    <select id="statusFilterSelect" onchange="applyRecordFilters()">
                        <option value="" {{ !$statusFilter ? 'selected' : '' }}>All</option>
                        <option value="paid"    {{ $statusFilter == 'paid'    ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="overdue" {{ $statusFilter == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="partial" {{ $statusFilter == 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
                </div>
                <form method="GET" action="{{ route('admin.fee-payments.index') }}" id="searchFormRecords" style="display:contents">
                    <input type="hidden" name="tab" value="records">
                    <input type="hidden" name="type"   value="{{ $typeFilter }}">
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                    <div class="search-wrap">
                        <span class="si">&#128269;</span>
                        <input type="text" name="search" placeholder="Search by student name or roll no..." value="{{ request('search') }}" onkeyup="debounceSearch('searchFormRecords')">
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Year</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Paid Amount</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feePayments as $payment)
                    @php $outstanding = $payment->status !== 'paid' ? max(0, $payment->amount - ($payment->paid_amount ?? 0)) : 0; @endphp
                    <tr>
                        <td>{{ $payment->student->name ?? 'N/A' }}</td>
                        <td>{{ $payment->student->roll_no ?? 'N/A' }}</td>
                        <td>{{ $payment->student->department->name ?? 'N/A' }}</td>
                        <td>Sem {{ $payment->semester }}</td>
                        <td>{{ $payment->year }}</td>
                        <td>
                            @if($payment->type === 'supplemental')
                                <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:.75rem;font-weight:600;background:#fef3c7;color:#92400e;">&#8635; Retake</span>
                            @else
                                <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:.75rem;font-weight:600;background:#ede9fe;color:#6d28d9;">&#128203; Regular</span>
                            @endif
                        </td>
                        <td>${{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->paid_amount !== null)
                                ${{ number_format($payment->paid_amount, 2) }}
                                @if($payment->status === 'partial')
                                    <small style="color:#6b7280;display:block">of ${{ number_format($payment->amount, 2) }}</small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($outstanding > 0)
                                <span style="color:#dc2626;font-weight:600">${{ number_format($outstanding, 2) }}</span>
                            @else
                                <span style="color:#16a34a;font-weight:600">$0.00</span>
                            @endif
                        </td>
                        <td><span class="status-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></td>
                        <td>{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('Y-m-d') : '-' }}</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-tbl-edit" onclick="editPayment({{ $payment->id }}, {{ $payment->student_id }}, {{ $payment->semester }}, {{ $payment->year }}, '{{ $payment->amount }}', '{{ $payment->status }}')">&#9998; Edit</button>
                                <form method="POST" action="{{ route('admin.fee-payments.destroy', $payment->id) }}" style="display:contents" onsubmit="return confirm('Delete this payment record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-tbl-del">&#128465; Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" style="text-align:center;padding:24px;color:#9ca3af">No fee payment records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>{{-- /.table-scroll --}}
            <div style="margin-top:16px">{{ $feePayments->links() }}</div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-card">
        <div class="modal-top">
            <h3 id="modalTitle">Add Fee Payment</h3>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>

        @if($errors->any())
        <div class="err-box">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" id="feeForm" action="{{ route('admin.fee-payments.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="field-group">
                <label>Student</label>
                <select name="student_id" id="fStudent" required>
                    <option value="">Select Student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->name }} ({{ $student->roll_no }}) &mdash; {{ $student->department->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label>Semester</label>
                <select name="semester" id="fSemester" required>
                    <option value="">Select Semester</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ old('semester') == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="field-group">
                <label>Year</label>
                <input type="number" name="year" id="fYear" value="{{ old('year', $currentYear) }}" min="2020" max="2099" required>
            </div>
            <div class="field-group">
                <label>Amount ($)</label>
                <input type="number" name="amount" id="fAmount" value="{{ old('amount') }}" step="0.01" min="0" placeholder="e.g. 5000.00" required>
            </div>
            <div class="field-group">
                <label>Status</label>
                <select name="status" id="fStatus" required>
                    <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid"    {{ old('status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                    <option value="overdue" {{ old('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="partial" {{ old('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">+ Save</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storeUrl = "{{ route('admin.fee-payments.store') }}";

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

// Auto-open the correct tab if redirected from records filter/search
@if(request('tab') === 'records')
document.addEventListener('DOMContentLoaded', () => {
    switchTab('records', document.querySelectorAll('.tab-btn')[1]);
});
@endif

function openModal() {
    document.getElementById('feeForm').action = storeUrl;
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('feeForm').reset();
    document.getElementById('fYear').value = {{ $currentYear }};
    document.getElementById('modalTitle').textContent = 'Add Fee Payment';
    document.getElementById('modalBackdrop').classList.add('show');
}

function openAddForStudent(studentId, semester) {
    openModal();
    document.getElementById('fStudent').value  = studentId;
    document.getElementById('fSemester').value = semester;
    fetchStudentFee(studentId);
}

function fetchStudentFee(studentId) {
    if (!studentId) return;
    fetch(`/admin/fee-payments/student-fee/${studentId}`)
        .then(r => r.json())
        .then(data => {
            if (data.total_fee > 0) {
                document.getElementById('fAmount').value = data.total_fee;
            }
            if (data.semester) {
                document.getElementById('fSemester').value = data.semester;
            }
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('fStudent').addEventListener('change', function() {
        fetchStudentFee(this.value);
    });
});

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
}

function editPayment(id, studentId, semester, year, amount, status) {
    document.getElementById('feeForm').action   = `/admin/fee-payments/${id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('fStudent').value   = studentId;
    document.getElementById('fSemester').value  = semester;
    document.getElementById('fYear').value      = year;
    document.getElementById('fAmount').value    = amount;
    document.getElementById('fStatus').value    = status;
    document.getElementById('modalTitle').textContent = 'Edit Fee Payment';
    document.getElementById('modalBackdrop').classList.add('show');
}

function applyRecordFilters() {
    const type   = document.getElementById('typeFilterSelect').value;
    const status = document.getElementById('statusFilterSelect').value;
    const search = document.querySelector('#searchFormRecords input[name="search"]')?.value ?? '';
    const params = new URLSearchParams({ tab: 'records' });
    if (search) params.set('search', search);
    if (type)   params.set('type', type);
    if (status) params.set('status', status);
    location.href = '{{ route('admin.fee-payments.index') }}?' + params.toString();
}

function debounceSearch(formId) {
    clearTimeout(window._st);
    window._st = setTimeout(() => document.getElementById(formId).submit(), 400);
}

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('modalBackdrop').classList.add('show');
});
@endif
</script>
@endpush
