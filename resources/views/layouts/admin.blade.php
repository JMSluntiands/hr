<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('/') }}">
    <title>@yield('title', 'Admin - Luntian HR')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/luntian-favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.sidebar-styles')
    @stack('head')
</head>
<body class="font-inter bg-gradient-to-br from-slate-100 via-slate-50 to-amber-50/30 min-h-screen">
    @include('partials.admin-sidebar')
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    @stack('modals')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('assets/js/sidebar-dropdown.js') }}"></script>
    @stack('scripts')
</body>
</html>
