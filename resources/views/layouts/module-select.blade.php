<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Select Module - Luntian')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/luntian-favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianDark: '#1e1e2d',
                        luntianOrange: '#FA9800',
                    }
                }
            }
        };
    </script>
    @stack('head')
</head>
<body class="font-inter min-h-screen flex flex-col bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800]">
    <header class="shrink-0 px-4 py-4 md:px-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/img/luntian-favicon.png') }}" alt="" class="w-9 h-9 rounded-lg shadow-md" onerror="this.style.display='none'">
            <span class="text-white/90 text-sm font-medium">Luntian HR</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span class="text-white/80 hidden sm:inline">{{ session('name') ?? session('email', 'User') }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[#FA9800] hover:text-white font-medium transition-colors">Logout</button>
            </form>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center p-4 md:p-8">
        @yield('content')
    </main>

    <footer class="shrink-0 py-4 text-center text-white/50 text-xs">
        &copy; {{ date('Y') }} Luntian
    </footer>
</body>
</html>
