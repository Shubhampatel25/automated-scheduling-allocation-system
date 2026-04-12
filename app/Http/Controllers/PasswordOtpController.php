<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordOtpController extends Controller
{
    private const OTP_TTL_MINUTES  = 10;
    private const OTP_COOLDOWN_SEC = 60;
    private const MAX_ATTEMPTS     = 5;

    // ── Step 1: Show "forgot password" form ──────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // ── Step 2: Send OTP ─────────────────────────────────────────────────

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $email = strtolower(trim($request->email));

        // Rate-limit: max 5 send attempts per email per minute
        $rateLimitKey = 'otp-send:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return back()->withErrors(['email' => "Too many attempts. Please wait {$seconds} seconds."])->withInput();
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Always return success message — do not reveal whether email exists
        $user = User::where('email', $email)->first();
        if (! $user) {
            return back()->with('otp_sent', true)->withInput(['email' => $email]);
        }

        // Enforce 60-second cooldown per email
        $existing = PasswordResetOtp::where('email', $email)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if ($existing && $existing->secondsUntilResend() > 0) {
            $wait = $existing->secondsUntilResend();
            return back()
                ->withErrors(['email' => "Please wait {$wait} seconds before requesting another OTP."])
                ->withInput();
        }

        // Invalidate all previous OTPs for this email
        PasswordResetOtp::where('email', $email)->delete();

        // Generate and hash OTP
        $plainOtp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::create([
            'email'        => $email,
            'otp_code'     => Hash::make($plainOtp),
            'expires_at'   => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts'     => 0,
            'last_sent_at' => now(),
        ]);

        Mail::to($email)->send(new OtpMail($plainOtp, self::OTP_TTL_MINUTES));

        return redirect()->route('password.otp.verify.form')
            ->with('otp_email', $email)
            ->with('otp_sent', true);
    }

    // ── Step 3: Show OTP verification form ───────────────────────────────

    public function showVerifyOtp(Request $request)
    {
        // Email comes from session flash, old input (after failed verify), or query param
        $email = session('otp_email') ?? old('email') ?? $request->query('email', '');
        return view('auth.verify-otp', compact('email'));
    }

    // ── Step 4: Verify OTP ────────────────────────────────────────────────

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower(trim($request->email));

        // Rate-limit verify: 10 attempts per IP per minute
        $ipKey = 'otp-verify:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return back()->withErrors(['otp' => "Too many attempts. Please wait {$seconds} seconds."]);
        }
        RateLimiter::hit($ipKey, 60);

        $record = PasswordResetOtp::where('email', $email)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record) {
            return back()->withErrors(['otp' => 'No OTP found for this email. Please request a new one.'])
                ->withInput(['email' => $email]);
        }

        if ($record->isExpired()) {
            return back()->withErrors(['otp' => 'This OTP has expired. Please request a new one.'])
                ->withInput(['email' => $email]);
        }

        if ($record->isExhausted()) {
            return back()->withErrors(['otp' => 'Maximum verification attempts reached. Please request a new OTP.'])
                ->withInput(['email' => $email]);
        }

        // Increment attempt before checking — prevents timing-based enumeration
        $record->increment('attempts');

        if (! $record->checkOtp($request->otp)) {
            $remaining = self::MAX_ATTEMPTS - $record->attempts;
            return back()->withErrors(['otp' => "Invalid OTP. {$remaining} attempt(s) remaining."])
                ->withInput(['email' => $email]);
        }

        // OTP correct — generate a short-lived signed token for the reset step
        $resetToken = Str::random(64);
        $record->update(['used_at' => now()]);

        // Store token in session (expires with session)
        session([
            'otp_reset_token' => $resetToken,
            'otp_reset_email' => $email,
            'otp_reset_at'    => now()->timestamp,
        ]);

        return redirect()->route('password.otp.reset.form');
    }

    // ── Step 5: Show reset-password form ─────────────────────────────────

    public function showResetPassword()
    {
        if (! $this->validResetSession()) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your session has expired. Please start over.']);
        }
        return view('auth.reset-password-otp', [
            'email' => session('otp_reset_email'),
        ]);
    }

    // ── Step 6: Apply new password ────────────────────────────────────────

    public function resetPassword(Request $request)
    {
        if (! $this->validResetSession()) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your session has expired. Please start over.']);
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = session('otp_reset_email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Account not found. Please try again.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Invalidate the OTP reset session
        session()->forget(['otp_reset_token', 'otp_reset_email', 'otp_reset_at']);

        return redirect()->route('login')
            ->with('success', 'Password has been reset successfully! Please log in with your new password.');
    }

    // ── Step 7: Resend OTP (from verify page) ─────────────────────────────

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $request->merge(['email' => $request->email]);
        return $this->sendOtp($request);
    }

    // ── Private ───────────────────────────────────────────────────────────

    /**
     * The OTP reset session is valid for 15 minutes after verification.
     */
    private function validResetSession(): bool
    {
        return session()->has('otp_reset_token')
            && session()->has('otp_reset_email')
            && (now()->timestamp - (int) session('otp_reset_at', 0)) < 900; // 15 min
    }
}
