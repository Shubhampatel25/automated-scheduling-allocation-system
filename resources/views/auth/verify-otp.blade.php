<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Automated Scheduling System</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin: 12px 0 20px; }
        .otp-inputs input {
            width: 44px; height: 52px; text-align: center; font-size: 1.4rem; font-weight: 700;
            border: 2px solid #d1d5db; border-radius: 8px; outline: none;
            color: #1e293b; background: #f9fafb;
        }
        .otp-inputs input:focus { border-color: #4f46e5; background: #fff; }
        .resend-row { text-align: center; font-size: 0.85rem; color: #6b7280; margin-top: 14px; }
        .resend-row a, .resend-row button { color: #4f46e5; font-weight: 600; background: none; border: none; cursor: pointer; font-size: 0.85rem; padding: 0; }
        #countdown { font-weight: 600; color: #4f46e5; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Automated Class Scheduling System</h1>
            <p>Enter the 6-digit OTP sent to your email. The code is valid for 10 minutes.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Verify OTP</h2>
                <p>Check your email for the 6-digit code</p>
            </div>

            @if(session('otp_sent'))
                <div class="alert alert-success">
                    OTP sent successfully to <strong>{{ $email }}</strong>.
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.otp.verify') }}" id="otpForm">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-group">
                    <label style="display:block;text-align:center;margin-bottom:4px;">Enter 6-Digit OTP</label>
                    {{-- Individual digit boxes for UX; assembled into hidden input on submit --}}
                    <div class="otp-inputs" id="digitBoxes">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit"
                                   autocomplete="one-time-code" pattern="[0-9]">
                        @endfor
                    </div>
                    <input type="hidden" name="otp" id="otpHidden">
                    @error('otp')
                        <span class="error-message" style="display:block;text-align:center;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="login-btn">Verify OTP</button>
            </form>

            <div class="resend-row" id="resendRow">
                Didn't receive it?
                <span id="countdownWrap">
                    Resend in <span id="countdown">60</span>s
                </span>
                <span id="resendLinkWrap" style="display:none;">
                    <form method="POST" action="{{ route('password.otp.resend') }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit">Resend OTP</button>
                    </form>
                </span>
            </div>

            <div class="back-to-login">
                <a href="{{ route('password.request') }}">&#8592; Back</a>
            </div>
        </div>
    </div>

    <script>
    // ── OTP digit input UX ──────────────────────────────────────────────
    const digits  = document.querySelectorAll('.otp-digit');
    const hidden  = document.getElementById('otpHidden');
    const form    = document.getElementById('otpForm');

    digits.forEach((input, i) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value && i < digits.length - 1) digits[i + 1].focus();
            syncHidden();
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
        });
        input.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0, 6);
            pasted.split('').forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
            const next = pasted.length < 6 ? digits[pasted.length] : digits[5];
            if (next) next.focus();
            syncHidden();
        });
    });

    function syncHidden() {
        hidden.value = [...digits].map(d => d.value).join('');
    }

    form.addEventListener('submit', () => syncHidden());

    // ── Resend countdown ──────────────────────────────────────────────
    let seconds = 60;
    const cdEl   = document.getElementById('countdown');
    const cdWrap = document.getElementById('countdownWrap');
    const rlWrap = document.getElementById('resendLinkWrap');

    const timer = setInterval(() => {
        seconds--;
        cdEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            cdWrap.style.display = 'none';
            rlWrap.style.display = 'inline';
        }
    }, 1000);
    </script>
</body>
</html>
