<!DOCTYPE html>
<html lang="en" style="scroll-behavior: smooth;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Academic Scheduling System</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Academic Scheduler</h2>
            <p>@yield('role-label', 'Dashboard')</p>
        </div>

        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>

            <div class="user-info">
                <div>
                    <div class="user-name">{{ Auth::user()->username }}</div>
                    <div class="user-role">{{ ucfirst(Auth::user()->role) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success js-alert" style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:20px;border:1px solid #a7f3d0;display:flex;align-items:center;gap:10px;font-size:0.9rem;">
                    <span style="font-size:1.1rem;flex-shrink:0;font-weight:700;">&#10003;</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger js-alert" style="background:#fee2e2;color:#991b1b;padding:12px 18px;border-radius:8px;margin-bottom:20px;border:1px solid #fca5a5;display:flex;align-items:center;gap:10px;font-size:0.9rem;">
                    <span style="font-size:1.1rem;flex-shrink:0;">&#9888;</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning js-alert" style="background:#fef3c7;color:#92400e;padding:12px 18px;border-radius:8px;margin-bottom:20px;border:1px solid #fde68a;display:flex;align-items:center;gap:10px;font-size:0.9rem;">
                    <span style="font-size:1.1rem;flex-shrink:0;">&#9888;</span>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')
    <script>
    // Auto-dismiss flash alerts after 5 s
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-alert').forEach(function (el) {
            setTimeout(function () {
                el.classList.add('alert-dismissing');
                setTimeout(function () { el.remove(); }, 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>
