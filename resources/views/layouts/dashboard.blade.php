<!DOCTYPE html>
<html lang="en" style="scroll-behavior: smooth;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Scheduling System</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Scheduling System</h2>
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
                <div class="alert alert-success" style="background:#efe;color:#2d7d46;padding:12px 18px;border-radius:8px;margin-bottom:20px;border:1px solid #b7e4c7;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" style="background:#fee;color:#c33;padding:12px 18px;border-radius:8px;margin-bottom:20px;border:1px solid #fcc;">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
