<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Automated Scheduling System</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Automated Class Scheduling System</h1>
            <p>Forgot your password? No worries! Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Forgot Password</h2>
                <p>Enter your email to receive a reset link</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your registered email">
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="login-btn">Send Reset Link</button>
            </form>

            <div class="back-to-login">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
