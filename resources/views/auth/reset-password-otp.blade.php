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
            <p>Choose a strong new password. Your password must be at least 8 characters long.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Set New Password</h2>
                <p>{{ $email }}</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.otp.reset') }}">
                @csrf

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password"
                           required autofocus minlength="8"
                           placeholder="Minimum 8 characters">
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation"
                           name="password_confirmation"
                           required minlength="8"
                           placeholder="Re-enter your new password">
                </div>

                <button type="submit" class="login-btn">Reset Password</button>
            </form>

            <div class="back-to-login">
                <a href="{{ route('login') }}">&#8592; Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
