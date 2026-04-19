<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AfterReload')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />
    <link rel="icon" href="{{ asset('branding/minilogoNoBG.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('branding/minilogoNoBG.png') }}" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="{{ asset('branding/minilogoWithBg.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-[#121212] text-slate-100 flex font-sans overflow-x-hidden">
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        @include('partials.topbar')

        <main class="flex-1 overflow-y-auto">
            @yield('content')
        </main>
    </div>

    <!-- Mobile Sidebar Overlay (Blur) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('main-sidebar');
            const toggle = document.getElementById('sidebar-toggle');
            const close = document.getElementById('sidebar-close');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            if (toggle && sidebar && overlay) {
                toggle.addEventListener('click', openSidebar);
                overlay.addEventListener('click', closeSidebar);
                if (close) {
                    close.addEventListener('click', closeSidebar);
                }
            }
        });
    </script>
</body>
</html>
