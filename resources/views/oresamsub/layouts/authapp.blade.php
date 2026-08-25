@php
    $affiliate = session('affiliate');
    $authSiteLogo = $site_logo ?? null;
    $favicon = $affiliate?->logo
        ? asset($affiliate->logo)
        : ($authSiteLogo
            ? asset('assets/landing_page_assets/img/site_logo/'.$authSiteLogo)
            : asset('assets/logo_imgs/favicon/android-chrome-192x192.png'));
    $primary = session('user_dashboard_primary_color', '#2563eb');
    $secondary = session('user_dashboard_secondary_color', '#14b8a6');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $primary }}">
    <title>@yield('title', $affiliate?->name ?? 'Customer account')</title>
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <style>
        :root { --auth-primary: {{ $primary }}; --auth-secondary: {{ $secondary }}; }
        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        body { margin: 0; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .auth-page { min-height: 100dvh; display: grid; place-items: center; padding: max(72px, env(safe-area-inset-top)) 16px max(30px, env(safe-area-inset-bottom)); color: #0f172a; background: #f8fafc; position: relative; overflow: hidden; transition: background .2s,color .2s; }
        .auth-page::before { content: ''; position: fixed; inset: 0; pointer-events: none; background: radial-gradient(circle at 5% 5%, color-mix(in srgb, var(--auth-primary) 15%, transparent), transparent 31%), radial-gradient(circle at 95% 94%, color-mix(in srgb, var(--auth-secondary) 14%, transparent), transparent 29%), linear-gradient(to right,rgba(15,23,42,.025) 1px,transparent 1px),linear-gradient(to bottom,rgba(15,23,42,.025) 1px,transparent 1px); background-size: auto,auto,32px 32px,32px 32px; }
        .auth-wrap { position: relative; z-index: 1; width: 100%; max-width: 480px; }
        .auth-card { width: 100%; border: 1px solid rgba(226,232,240,.9); border-radius: 28px; padding: 24px 20px; background: rgba(255,255,255,.96); box-shadow: 0 28px 80px rgba(15,23,42,.12); backdrop-filter: blur(18px); }
        .auth-card--wide { max-width: 680px; }
        .auth-wrap:has(.auth-card--wide) { max-width: 680px; }
        .auth-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .auth-logo { width: 50px; height: 50px; flex: none; border-radius: 16px; object-fit: cover; box-shadow: 0 9px 24px rgba(15,23,42,.14); border: 1px solid #e2e8f0; }
        .auth-logo--fallback { display: grid; place-items: center; color: white; font-weight: 800; font-size: 19px; background: linear-gradient(135deg,var(--auth-primary),var(--auth-secondary)); }
        .auth-business-name { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; font-weight: 750; color: #0f172a; }
        .auth-business-label { margin: 3px 0 0; font-size: 12px; color: #64748b; }
        .auth-heading { margin-bottom: 12px; }
        .auth-heading h1 { margin: 0; font-size: clamp(25px, 7vw, 32px); line-height: 1.15; letter-spacing: -.035em; font-weight: 800; color: #0f172a; }
        .auth-heading p { margin: 9px 0 0; color: #64748b; font-size: 14px; line-height: 1.6; }
        .auth-trust { display: flex; align-items: center; gap: 7px; margin: 0 0 25px; color: #64748b; font-size: 12px; font-weight: 650; }
        .auth-trust svg { width: 16px; height: 16px; fill: var(--auth-primary); }
        .auth-grid { display: grid; gap: 16px; }
        .auth-grid--two { grid-template-columns: 1fr; }
        .auth-field label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 650; color: #334155; }
        .auth-input { width: 100%; min-height: 52px; padding: 13px 15px; border: 1px solid #cbd5e1; border-radius: 16px; background: #fff; color: #0f172a; font: inherit; font-size: 15px; outline: none; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: border-color .18s, box-shadow .18s,background .18s; }
        .auth-input:focus { border-color: var(--auth-primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--auth-primary) 14%, transparent); }
        .auth-input-wrap { position: relative; }
        .auth-input-wrap .auth-input { padding-right: 72px; }
        .auth-reveal { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); border: 0; border-radius: 11px; padding: 8px 10px; background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 700; cursor: pointer; }
        .auth-error { margin: 6px 0 0; color: #dc2626; font-size: 12px; }
        .auth-alert { margin-bottom: 18px; padding: 12px 14px; border-radius: 12px; font-size: 13px; line-height: 1.5; }
        .auth-alert--success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .auth-alert--error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .auth-button { display: inline-flex; width: 100%; min-height: 52px; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 16px; padding: 12px 18px; color: white; background: linear-gradient(135deg,var(--auth-primary),var(--auth-secondary)); font: inherit; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 14px 30px color-mix(in srgb, var(--auth-primary) 26%, transparent); transition: transform .18s, opacity .18s,box-shadow .18s; }
        .auth-button:hover { transform: translateY(-1px); }
        .auth-button:disabled { cursor: wait; opacity: .65; transform: none; }
        .auth-link { color: var(--auth-primary); font-weight: 650; text-decoration: none; }
        .auth-link:hover { text-decoration: underline; }
        .auth-footer { margin: 22px 0 0; text-align: center; color: #64748b; font-size: 13px; }
        .auth-theme-toggle { position: fixed; z-index: 20; top: 16px; right: 16px; display: grid; place-items: center; width: 44px; height: 44px; padding: 0; border: 1px solid #e2e8f0; border-radius: 16px; background: rgba(255,255,255,.9); color: #475569; cursor: pointer; box-shadow: 0 4px 16px rgba(15,23,42,.07); backdrop-filter: blur(12px); }
        .auth-theme-toggle svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        html.dark .auth-page { color: #f8fafc; background: #070b12; }
        html.dark .auth-page::before { background: radial-gradient(circle at 5% 5%, color-mix(in srgb, var(--auth-primary) 16%, transparent), transparent 31%),radial-gradient(circle at 95% 94%, color-mix(in srgb, var(--auth-secondary) 14%, transparent), transparent 29%),linear-gradient(to right,rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(to bottom,rgba(255,255,255,.035) 1px,transparent 1px); background-size: auto,auto,32px 32px,32px 32px; }
        html.dark .auth-card { border-color: #1e293b; background: rgba(13,21,34,.96); box-shadow: 0 30px 90px rgba(0,0,0,.36); }
        html.dark .auth-logo { border-color: #334155; }
        html.dark .auth-business-name,html.dark .auth-heading h1 { color: #fff; }
        html.dark .auth-business-label,html.dark .auth-heading p,html.dark .auth-trust,html.dark .auth-footer { color: #94a3b8; }
        html.dark .auth-field label { color: #cbd5e1; }
        html.dark .auth-input { border-color: #334155; background: rgba(2,6,23,.55); color: #fff; }
        html.dark .auth-reveal { background: #1e293b; color: #cbd5e1; }
        html.dark .auth-theme-toggle { border-color: #1e293b; background: rgba(15,23,42,.9); color: #cbd5e1; }
        @media (min-width: 640px) { .auth-page { padding: 76px 24px 44px; } .auth-card { padding: 34px; } .auth-grid--two { grid-template-columns: repeat(2,minmax(0,1fr)); } .auth-span-two { grid-column: span 2; } .auth-theme-toggle { top: 24px; right: 24px; } }
        @media (prefers-reduced-motion: reduce) { * { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
    <button data-testid="auth-theme-toggle" class="auth-theme-toggle" type="button" aria-label="Toggle colour theme" onclick="toggleAuthTheme()">
        <svg class="auth-theme-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
        <svg class="auth-theme-sun" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
    </button>
    <main class="auth-page">
        <div class="auth-wrap">@yield('content')</div>
    </main>
    <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
    <script>
        function syncAuthThemeIcon() {
            const dark = document.documentElement.classList.contains('dark');
            document.querySelector('.auth-theme-moon').style.display = dark ? 'none' : 'block';
            document.querySelector('.auth-theme-sun').style.display = dark ? 'block' : 'none';
        }
        function toggleAuthTheme() {
            const dark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', dark);
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            syncAuthThemeIcon();
        }
        syncAuthThemeIcon();
    </script>
</body>
</html>
