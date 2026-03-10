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
        $students    = Student::with('feePayments')->where('status', 'active')->get();
        $generated   = 0;

        foreach ($students as $student) {
            $hasFee = $student->feePayments
                ->where('semester', $student->semester)
                ->where('year', $currentYear)
                ->isNotEmpty();

            if (!$hasFee) {
                // Calculate fee from enrolled courses
                $totalFee = StudentCourseRegistration::where('student_id', $student->id)
                    ->where('status', 'enrolled')
                    ->with('courseSection.course')
                    ->get()
                    ->sum(fn($reg) => $reg->courseSection->course->fee ?? 0);

                DB::table('fee_payments')->insert([
                    'student_id' => $student->id,
                    'semester'   => $student->semester,
                    'year'       => $currentYear,
                    'amount'     => $totalFee,
                    'status'     => 'pending',
                    'paid_at'    => null,
                    'created_at' => DB::raw('NOW()'),
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

        DB::table('fee_payments')->insert([
            'student_id' => $request->student_id,
            'semester'   => $request->semester,
            'year'       => $request->year,
            'amount'     => $request->amount,
            'status'     => $request->status,
            'paid_at'    => $request->status === 'paid' ? DB::raw('NOW()') : null,
            'created_at' => DB::raw('NOW()'),
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
     */
    public function studentPay(Request $request, FeePayment $feePayment)
    {
        // Ensure the student can only pay their own fee record
        $student = Student::where('user_id', Auth::id())->first();

        if (!$student || $feePayment->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payment_type' => 'required|in:full,partial',
            'paid_amount'  => 'required_if:payment_type,partial|nullable|numeric|min:1|max:' . $feePayment->amount,
        ]);

        if ($request->payment_type === 'full') {
            DB::table('fee_payments')
                ->where('id', $feePayment->id)
                ->update([
                    'status'      => 'paid',
                    'paid_amount' => $feePayment->amount,
                    'paid_at'     => DB::raw('NOW()'),
                ]);

            return redirect()->route('student.dashboard')
                ->with('success', 'Full payment recorded! You can now register for courses.');
        }

        DB::table('fee_payments')
            ->where('id', $feePayment->id)
            ->update([
                'status'      => 'partial',
                'paid_amount' => $request->paid_amount,
                'paid_at'     => DB::raw('NOW()'),
            ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Partial payment of $' . number_format($request->paid_amount, 2) . ' recorded. Full payment is required to register for courses.');
    }
}
