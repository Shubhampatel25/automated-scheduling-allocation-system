<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_code',
        'expires_at',
        'attempts',
        'last_sent_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_sent_at' => 'datetime',
        'used_at'      => 'datetime',
    ];

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= 5;
    }

    /**
     * Check OTP using a constant-time hash comparison.
     */
    public function checkOtp(string $plain): bool
    {
        return Hash::check($plain, $this->otp_code);
    }

    /**
     * How many seconds remain before another send is allowed (60 s cooldown).
     */
    public function secondsUntilResend(): int
    {
        if ($this->last_sent_at === null) {
            return 0;
        }
        // Use absolute diff: last_sent_at is always in the past
        $elapsed = (int) $this->last_sent_at->diffInSeconds(now());
        return max(0, 60 - $elapsed);
    }
}
