<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    @include('partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.topbar')

        <main id="app-main" class="flex-1 overflow-y-auto transition-opacity duration-150">
            @yield('content')
        </main>
    </div>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pendingLobbyLeaveKey = 'afterreload.pendingLobbyLeave';

            const flushPendingLobbyLeave = async () => {
                const raw = window.localStorage.getItem(pendingLobbyLeaveKey);
                if (!raw) {
                    return;
                }

                let pendingLeave = null;
                try {
                    pendingLeave = JSON.parse(raw);
                } catch (_error) {
                    window.localStorage.removeItem(pendingLobbyLeaveKey);
                    return;
                }

                if (!pendingLeave?.url || !pendingLeave?.csrf || !pendingLeave?.timestamp) {
                    window.localStorage.removeItem(pendingLobbyLeaveKey);
                    return;
                }

                if ((Date.now() - Number(pendingLeave.timestamp)) > 86400000) {
                    window.localStorage.removeItem(pendingLobbyLeaveKey);
                    return;
                }

                window.localStorage.removeItem(pendingLobbyLeaveKey);

                try {
                    await fetch(pendingLeave.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': pendingLeave.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({}),
                        keepalive: true,
                    });
                } catch (_error) {}
            };

            flushPendingLobbyLeave();

            const sidebar = document.getElementById('main-sidebar');
            const toggle = document.getElementById('sidebar-toggle');
            const close = document.getElementById('sidebar-close');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar?.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar?.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
            }

            if (toggle && sidebar && overlay) {
                toggle.addEventListener('click', openSidebar);
                overlay.addEventListener('click', closeSidebar);
                if (close) {
                    close.addEventListener('click', closeSidebar);
                }
            }

            const mainContent = document.getElementById('app-main');
            const spaSelector = 'a[data-spa-link]';
            let activeRequestId = 0;

            const isSpaUrl = (url) => {
                const pathname = new URL(url, window.location.origin).pathname;
                return Array.from(document.querySelectorAll(spaSelector)).some((link) => {
                    const linkPath = new URL(link.href, window.location.origin).pathname;
                    return pathname === linkPath || (linkPath !== '/' && pathname.startsWith(linkPath));
                });
            };

            const rehydrateScripts = (container) => {
                container.querySelectorAll('script').forEach((oldScript) => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach((attr) => newScript.setAttribute(attr.name, attr.value));
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode?.replaceChild(newScript, oldScript);
                });
            };

            function updateActiveLinks(url) {
                const currentPath = new URL(url, window.location.origin).pathname;

                document.querySelectorAll(spaSelector).forEach((link) => {
                    const linkPath = new URL(link.href, window.location.origin).pathname;
                    const isActive = currentPath === linkPath || (linkPath !== '/' && currentPath.startsWith(linkPath));
                    const isAdminLink = linkPath.startsWith('/admin');
                    const img = link.querySelector('img');
                    const svg = link.querySelector('svg');

                    if (isActive) {
                        link.classList.add('bg-[#5b7cff]', 'text-white');
                        link.classList.remove('text-slate-400', 'hover:bg-[#222222]', 'hover:text-white', 'text-[#5b7cff]', 'hover:bg-[#5b7cff]');
                        img?.classList.add('brightness-0', 'invert');
                        svg?.classList.remove('text-slate-400');
                        svg?.classList.add('text-white');
                    } else {
                        link.classList.remove('bg-[#5b7cff]', 'text-white');
                        img?.classList.remove('brightness-0', 'invert');
                        svg?.classList.remove('text-white');

                        if (isAdminLink) {
                            link.classList.add('text-[#5b7cff]', 'hover:bg-[#5b7cff]', 'hover:text-white');
                        } else {
                            link.classList.add('text-slate-400', 'hover:bg-[#222222]', 'hover:text-white');
                            svg?.classList.add('text-slate-400');
                        }
                    }
                });
            }

            async function loadPage(url, pushState = true) {
                if (!mainContent || !isSpaUrl(url)) {
                    window.location.href = url;
                    return;
                }

                const requestId = ++activeRequestId;
                window.dispatchEvent(new CustomEvent('spa:before-navigate', { detail: { url } }));
                mainContent.style.opacity = '0.45';

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-SPA-REQUEST': 'true',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(`spa-request-failed:${response.status}`);
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextMain = doc.getElementById('app-main');

                    if (!nextMain) {
                        throw new Error('spa-main-not-found');
                    }

                    if (requestId !== activeRequestId) {
                        return;
                    }

                    mainContent.innerHTML = nextMain.innerHTML;
                    document.title = doc.title || document.title;

                    if (pushState) {
                        history.pushState({ url }, '', url);
                    }

                    updateActiveLinks(url);
                    rehydrateScripts(mainContent);
                    mainContent.scrollTo({ top: 0, behavior: 'auto' });
                    window.scrollTo({ top: 0, behavior: 'auto' });

                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                } catch (error) {
                    console.error('SPA Navigation Error:', error);
                    window.location.href = url;
                    return;
                } finally {
                    if (requestId === activeRequestId) {
                        mainContent.style.opacity = '1';
                    }
                }
            }

            window.loadPage = loadPage;
            updateActiveLinks(window.location.href);

            document.addEventListener('click', function(e) {
                const link = e.target.closest(spaSelector);
                if (!link) return;
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (link.target && link.target !== '_self') return;

                const url = new URL(link.href, window.location.origin);
                if (url.origin !== window.location.origin) return;
                if (url.pathname === window.location.pathname && url.hash) return;

                e.preventDefault();
                loadPage(link.href);
            });

            window.addEventListener('popstate', function() {
                if (isSpaUrl(window.location.href)) {
                    loadPage(window.location.href, false);
                } else {
                    window.location.reload();
                }
            });
        });
    </script>
</body>
</html>
