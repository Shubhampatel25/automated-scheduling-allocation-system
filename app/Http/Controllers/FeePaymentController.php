<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\Department;
use App\Models\StudentCourseRegistration;

class FeePaymentController extends Controller
{
    public function index(Request $request)
    {
        $search       = $request->get('search');
        $statusFilter = $request->get('status');
        $currentYear  = now()->year;

        // All students with their current-semester fee payment (if any)
        $allStudents = Student::with(['department', 'feePayments' => function ($q) use ($currentYear) {
                $q->where('year', $currentYear)->orderBy('semester');
            }])
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('roll_no', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Existing payment records for the records tab
        $feePayments = FeePayment::with(['student.department'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('roll_no', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $students    = Student::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.fee-payments.index', compact(
            'feePayments', 'allStudents', 'students', 'departments', 'currentYear'
        ));
    }

    /**
     * Return the calculated total fee for a student based on their enrolled courses.
     */
    public function getStudentFee(Student $student)
    {
        $totalFee = StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->with('courseSection.course')
            ->get()
            ->sum(fn($reg) => $reg->courseSection->course->fee ?? 0);

        return response()->json([
            'total_fee' => $totalFee,
            'semester'  => $student->semester,
        ]);
    }

    /**
     * Auto-generate pending fee records for all students who don't have one for the current semester/year.
     */
    public function generatePending()
    {
        $currentYear = now()->year;
        $students    = Student::with(['feePayments', 'department'])->where('status', 'active')->get();
        $generated   = 0;

        foreach ($students as $student) {
            $hasFee = $student->feePayments
                ->where('semester', $student->semester)
                ->where('year', $currentYear)
                ->isNotEmpty();

            if (!$hasFee) {
                // Use department registration fee if set, otherwise fall back to enrolled course fees
                $deptFee = $student->department->registration_fee ?? null;

                $totalFee = $deptFee !== null
                    ? (float) $deptFee
                    : StudentCourseRegistration::where('student_id', $student->id)
                        ->where('status', 'enrolled')
                        ->with('courseSection.course')
                        ->get()
                        ->sum(fn($reg) => $reg->courseSection->course->fee ?? 0);

                DB::table('fee_payments')->insert([
                    'student_id'  => $student->id,
                    'semester'    => $student->semester,
                    'year'        => $currentYear,
                    'amount'      => $totalFee,
                    'type'        => 'regular',
                    'paid_amount' => 0,
                    'status'      => 'pending',
                    'paid_at'     => null,
                    'created_at'  => DB::raw('NOW()'),
                ]);
                $generated++;
            }
        }

        return redirect()->route('admin.fee-payments.index')
            ->with('success', "Generated {$generated} pending fee record(s) for the current semester.");
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester'   => 'required|integer|between:1,8',
            'year'       => 'required|integer|min:2020|max:2099',
            'amount'     => 'required|numeric|min:0',
            'status'     => 'required|in:paid,pending,overdue,partial',
        ]);

        $duplicate = DB::table('fee_payments')
            ->where('student_id', $request->student_id)
            ->where('semester',   $request->semester)
            ->where('year',       $request->year)
            ->where('type',       'regular')
            ->exists();

        if ($duplicate) {
            return redirect()->route('admin.fee-payments.index')
                ->with('error', 'A regular fee record already exists for this student, semester, and year.');
        }

        DB::table('fee_payments')->insert([
            'student_id'  => $request->student_id,
            'semester'    => $request->semester,
            'year'        => $request->year,
            'amount'      => $request->amount,
            'type'        => 'regular',
            'paid_amount' => 0,
            'status'      => $request->status,
            'paid_at'     => $request->status === 'paid' ? DB::raw('NOW()') : null,
            'created_at'  => DB::raw('NOW()'),
        ]);

        return redirect()->route('admin.fee-payments.index')
            ->with('success', 'Fee payment record created successfully.');
    }

    public function update(Request $request, FeePayment $feePayment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester'   => 'required|integer|between:1,8',
            'year'       => 'required|integer|min:2020|max:2099',
            'amount'     => 'required|numeric|min:0',
            'status'     => 'required|in:paid,pending,overdue,partial',
        ]);

        $paidAt = null;
        if ($request->status === 'paid') {
            $paidAt = $feePayment->paid_at ? $feePayment->paid_at : DB::raw('NOW()');
        }

        DB::table('fee_payments')
            ->where('id', $feePayment->id)
            ->update([
                'student_id' => $request->student_id,
                'semester'   => $request->semester,
                'year'       => $request->year,
                'amount'     => $request->amount,
                'status'     => $request->status,
                'paid_at'    => $paidAt,
            ]);

        return redirect()->route('admin.fee-payments.index')
            ->with('success', 'Fee payment record updated successfully.');
    }

    public function destroy(FeePayment $feePayment)
    {
        $feePayment->delete();

        return redirect()->route('admin.fee-payments.index')
            ->with('success', 'Fee payment record deleted successfully.');
    }

    /**
     * Student self-payment: full or partial.
     *
     * Partial payments accumulate on top of any amount already paid so that
     * a student who paid $100 on a $300 fee and then pays $150 more ends up
     * with paid_amount = $250, not just $150.
     */
    public function studentPay(Request $request, FeePayment $feePayment)
    {
        // Ensure the student can only pay their own fee record
        $student = Student::where('user_id', Auth::id())->first();

        if (!$student || $feePayment->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        // Pre-check (fast path — avoids acquiring lock for already-paid records)
        if ($feePayment->status === 'paid') {
            return redirect()->route('student.fee-payment')
                ->with('error', 'This fee has already been paid in full.');
        }

        $totalAmount = (float) $feePayment->amount;
        $remaining   = $totalAmount - (float) ($feePayment->paid_amount ?? 0);

        $request->validate([
            'payment_type' => 'required|in:full,partial',
            'paid_amount'  => 'required_if:payment_type,partial|nullable|numeric|min:1|max:' . $remaining,
        ]);

        $redirectResponse = null;

        DB::transaction(function () use ($feePayment, $request, $totalAmount, &$redirectResponse) {
            // Re-read under a row lock so concurrent requests cannot both see the
            // same paid_amount and both add their portion on top of a stale value.
            $locked = FeePayment::lockForUpdate()->find($feePayment->id);

            if (!$locked || $locked->status === 'paid') {
                $redirectResponse = redirect()->route('student.fee-payment')
                    ->with('error', 'This fee has already been paid in full.');
                return;
            }

            if ($request->payment_type === 'full') {
                DB::table('fee_payments')
                    ->where('id', $locked->id)
                    ->update([
                        'status'      => 'paid',
                        'paid_amount' => $totalAmount,
                        'paid_at'     => DB::raw('NOW()'),
                    ]);

                $redirectResponse = redirect()->route('student.dashboard')
                    ->with('success', 'Full payment recorded! You can now register for courses.');
                return;
            }

            // Accumulate partial payment on top of what has already been paid
            $alreadyPaid  = (float) ($locked->paid_amount ?? 0);
            $newPaidTotal = $alreadyPaid + (float) $request->paid_amount;

            if ($newPaidTotal >= $totalAmount) {
                DB::table('fee_payments')
                    ->where('id', $locked->id)
                    ->update([
                        'status'      => 'paid',
                        'paid_amount' => $totalAmount,
                        'paid_at'     => DB::raw('NOW()'),
                    ]);

                $redirectResponse = redirect()->route('student.dashboard')
                    ->with('success', 'Payment complete! Full amount of $' . number_format($totalAmount, 2) . ' received. You can now register for courses.');
                return;
            }

            DB::table('fee_payments')
                ->where('id', $locked->id)
                ->update([
                    'status'      => 'partial',
                    'paid_amount' => $newPaidTotal,
                    'paid_at'     => DB::raw('NOW()'),
                ]);

            $newRemaining     = $totalAmount - $newPaidTotal;
            $redirectResponse = redirect()->route('student.fee-payment')
                ->with('success', 'Partial payment of $' . number_format((float) $request->paid_amount, 2)
                    . ' recorded. Remaining balance: $' . number_format($newRemaining, 2)
                    . '. Full payment is required to register for courses.');
        });

        return $redirectResponse;
    }
}
