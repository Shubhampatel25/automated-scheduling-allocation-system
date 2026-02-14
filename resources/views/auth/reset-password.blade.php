<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Automated Scheduling System</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Automated Class Scheduling System</h1>
            <p>Create a new password for your account. Make sure it's at least 8 characters long and something you can remember.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Reset Password</h2>
                <p>Enter your new password below</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', request()->query('email')) }}" required placeholder="Enter your email">
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter new password (min 8 characters)">
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Confirm your new password">
                </div>

                <button type="submit" class="login-btn">Reset Password</button>
            </form>

            <div class="back-to-login">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
