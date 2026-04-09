<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
    .wrapper { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .header { background: #4f46e5; padding: 28px 32px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 1.3rem; }
    .body { padding: 32px; }
    .body p { color: #374151; font-size: 0.95rem; line-height: 1.6; margin: 0 0 16px; }
    .otp-box { background: #f0f4ff; border: 2px dashed #6366f1; border-radius: 8px; text-align: center; padding: 20px; margin: 24px 0; }
    .otp-box .otp { font-size: 2.2rem; font-weight: 700; letter-spacing: 0.3em; color: #4338ca; }
    .otp-box .expires { font-size: 0.82rem; color: #6b7280; margin-top: 8px; }
    .footer { background: #f9fafb; padding: 18px 32px; text-align: center; font-size: 0.78rem; color: #9ca3af; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Automated Scheduling System</h1>
    </div>
    <div class="body">
        <p>Hello,</p>
        <p>We received a request to reset your password. Use the OTP below to proceed. Do not share this code with anyone.</p>
        <div class="otp-box">
            <div class="otp">{{ $otp }}</div>
            <div class="expires">This code expires in {{ $expiresInMinutes }} minutes.</div>
        </div>
        <p>If you did not request a password reset, you can safely ignore this email.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Automated Scheduling System &mdash; Do not reply to this email.
    </div>
</div>
</body>
</html>
