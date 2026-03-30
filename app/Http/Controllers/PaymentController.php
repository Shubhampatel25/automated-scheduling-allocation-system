<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FeePayment;
use App\Models\Student;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends Controller
{
    /**
     * Create a Stripe Checkout Session for a student fee payment.
     * Handles both full and partial payments.
     */
    public function createCheckoutSession(Request $request, FeePayment $feePayment)
    {
        $student = Student::where('user_id', Auth::id())->first();

        if (!$student || $feePayment->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        if ($feePayment->status === 'paid') {
            return redirect()->route('student.fee-payment')
                ->with('error', 'This fee has already been paid.');
        }

        $request->validate([
            'payment_type' => 'required|in:full,partial',
            'paid_amount'  => 'required_if:payment_type,partial|nullable|numeric|min:1',
        ]);

        $paymentType = $request->payment_type;
        $amount      = $paymentType === 'full'
            ? (float) $feePayment->amount
            : (float) $request->paid_amount;

        $remaining = (float) $feePayment->amount - (float) ($feePayment->paid_amount ?? 0);
        if ($paymentType === 'partial' && $amount >= $remaining) {
            return redirect()->route('student.fee-payment')
                ->with('error', 'Amount entered covers the full balance. Please use the "Pay in Full" button instead.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'cad',
                        'product_data' => [
                            'name' => 'Semester ' . $feePayment->semester . ' Tuition Fee',
                        ],
                        'unit_amount' => (int) round($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => route('student.fees.stripe.success', $feePayment->id)
                    . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('student.fee-payment'),
                'metadata'    => [
                    'fee_payment_id' => $feePayment->id,
                    'payment_type'   => $paymentType,
                    'paid_amount'    => $amount,
                ],
            ]);

            return redirect($session->url, 303);

        } catch (\Exception $e) {
            return redirect()->route('student.fee-payment')
                ->with('error', 'Unable to initiate payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe redirect after successful payment.
     * Verifies the session with Stripe before marking the fee as paid.
     */
    public function success(Request $request, FeePayment $feePayment)
    {
        $student = Student::where('user_id', Auth::id())->first();

        if (!$student || $feePayment->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        if (!$request->session_id) {
            return redirect()->route('student.fee-payment')
                ->with('error', 'Invalid payment session.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::retrieve($request->session_id);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('student.fee-payment')
                    ->with('error', 'Payment was not completed. Please try again.');
            }

            $paymentType = $session->metadata->payment_type;
            $paidAmount  = (float) $session->metadata->paid_amount;

        } catch (\Exception $e) {
            return redirect()->route('student.fee-payment')
                ->with('error', 'Could not verify payment with Stripe: ' . $e->getMessage());
        }

        // Prevent double-processing if already paid
        if ($feePayment->status === 'paid') {
            return redirect()->route('student.dashboard')
                ->with('success', 'Your fee has already been recorded as paid.');
        }

        $totalAmount    = (float) $feePayment->amount;
        $alreadyPaid    = (float) ($feePayment->paid_amount ?? 0);

        if ($paymentType === 'full') {
            DB::table('fee_payments')
                ->where('id', $feePayment->id)
                ->update([
                    'status'      => 'paid',
                    'paid_amount' => $totalAmount,
                    'paid_at'     => DB::raw('NOW()'),
                ]);

            return redirect()->route('student.dashboard')
                ->with('success', 'Payment of $' . number_format($totalAmount, 2) . ' received via Stripe! You can now register for courses.');
        }

        // Partial payment — ADD to any previous payments
        $newTotal = $alreadyPaid + $paidAmount;

        if ($newTotal >= $totalAmount) {
            // Accumulated payments now cover the full amount — mark fully paid
            DB::table('fee_payments')
                ->where('id', $feePayment->id)
                ->update([
                    'status'      => 'paid',
                    'paid_amount' => $totalAmount,
                    'paid_at'     => DB::raw('NOW()'),
                ]);

            return redirect()->route('student.dashboard')
                ->with('success', 'Payment complete! Total $' . number_format($totalAmount, 2) . ' received. You can now register for courses.');
        }

        DB::table('fee_payments')
            ->where('id', $feePayment->id)
            ->update([
                'status'      => 'partial',
                'paid_amount' => $newTotal,
                'paid_at'     => DB::raw('NOW()'),
            ]);

        $remaining = $totalAmount - $newTotal;
        return redirect()->route('student.fee-payment')
            ->with('success', 'Partial payment of $' . number_format($paidAmount, 2) . ' received. Remaining balance: $' . number_format($remaining, 2) . '. Full payment is required to register for courses.');
    }
}
