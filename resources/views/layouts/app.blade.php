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
            @include('partials.footer')
        </main>
    </div>

    <!-- Mobile Sidebar Overlay (Blur) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>
</body>
</html>
