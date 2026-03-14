<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AfterReload')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700" rel="stylesheet" />
    <link rel="icon" href="{{ asset('branding/minilogoNoBG.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('branding/minilogoNoBG.png') }}" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="{{ asset('branding/minilogoWithBg.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
    @include('partials.navbar')
    <main class="flex-1">
        @yield('content')
    </main>
    @include('partials.footer')
</body>
</html>
