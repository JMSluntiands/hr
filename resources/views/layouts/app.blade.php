<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Luntian HR')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/luntian-favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-inter bg-slate-50 text-slate-800 min-h-screen">
    <header class="bg-[#1e1e2d] text-white px-4 py-3 flex items-center justify-between">
        <span class="font-semibold">@yield('header', 'Luntian HR')</span>
        <div class="flex items-center gap-4 text-sm">
            <span>{{ session('name') }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[#E9A319] hover:underline">Logout</button>
            </form>
        </div>
    </header>
    <main class="p-4 md:p-6 max-w-7xl mx-auto">
        @yield('content')
    </main>
</body>
</html>
