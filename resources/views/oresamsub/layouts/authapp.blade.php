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
        .auth-page { min-height: 100dvh; display: grid; place-items: center; padding: max(20px, env(safe-area-inset-top)) 16px max(24px, env(safe-area-inset-bottom)); color: #0f172a; background: #f8fafc; position: relative; overflow: hidden; }
        .auth-page::before { content: ''; position: fixed; inset: 0; pointer-events: none; background: radial-gradient(circle at 8% 8%, color-mix(in srgb, var(--auth-primary) 13%, transparent), transparent 32%), radial-gradient(circle at 92% 90%, color-mix(in srgb, var(--auth-secondary) 12%, transparent), transparent 30%); }
        .auth-wrap { position: relative; width: 100%; max-width: 460px; }
        .auth-card { width: 100%; border: 1px solid #e2e8f0; border-radius: 24px; padding: 24px 20px; background: rgba(255,255,255,.96); box-shadow: 0 24px 70px rgba(15,23,42,.10); }
        .auth-card--wide { max-width: 680px; }
        .auth-wrap:has(.auth-card--wide) { max-width: 680px; }
        .auth-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
        .auth-logo { width: 48px; height: 48px; flex: none; border-radius: 15px; object-fit: cover; box-shadow: 0 8px 22px rgba(15,23,42,.13); }
        .auth-logo--fallback { display: grid; place-items: center; color: white; font-weight: 800; font-size: 19px; background: linear-gradient(135deg,var(--auth-primary),var(--auth-secondary)); }
        .auth-business-name { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; font-weight: 750; color: #0f172a; }
        .auth-business-label { margin: 3px 0 0; font-size: 12px; color: #64748b; }
        .auth-heading { margin-bottom: 24px; }
        .auth-heading h1 { margin: 0; font-size: clamp(25px, 7vw, 32px); line-height: 1.15; letter-spacing: -.035em; font-weight: 800; color: #0f172a; }
        .auth-heading p { margin: 9px 0 0; color: #64748b; font-size: 14px; line-height: 1.6; }
        .auth-grid { display: grid; gap: 16px; }
        .auth-grid--two { grid-template-columns: 1fr; }
        .auth-field label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 650; color: #334155; }
        .auth-input { width: 100%; min-height: 49px; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 13px; background: #fff; color: #0f172a; font: inherit; font-size: 15px; outline: none; transition: border-color .18s, box-shadow .18s; }
        .auth-input:focus { border-color: var(--auth-primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--auth-primary) 14%, transparent); }
        .auth-input-wrap { position: relative; }
        .auth-input-wrap .auth-input { padding-right: 72px; }
        .auth-reveal { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); border: 0; border-radius: 9px; padding: 7px 8px; background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 650; cursor: pointer; }
        .auth-error { margin: 6px 0 0; color: #dc2626; font-size: 12px; }
        .auth-alert { margin-bottom: 18px; padding: 12px 14px; border-radius: 12px; font-size: 13px; line-height: 1.5; }
        .auth-alert--success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .auth-alert--error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .auth-button { display: inline-flex; width: 100%; min-height: 50px; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 13px; padding: 12px 18px; color: white; background: var(--auth-primary); font: inherit; font-size: 14px; font-weight: 750; cursor: pointer; box-shadow: 0 10px 24px color-mix(in srgb, var(--auth-primary) 24%, transparent); transition: transform .18s, opacity .18s; }
        .auth-button:hover { transform: translateY(-1px); }
        .auth-button:disabled { cursor: wait; opacity: .65; transform: none; }
        .auth-link { color: var(--auth-primary); font-weight: 650; text-decoration: none; }
        .auth-link:hover { text-decoration: underline; }
        .auth-footer { margin: 22px 0 0; text-align: center; color: #64748b; font-size: 13px; }
        @media (min-width: 640px) { .auth-page { padding: 36px 24px; } .auth-card { padding: 32px; border-radius: 28px; } .auth-grid--two { grid-template-columns: repeat(2,minmax(0,1fr)); } .auth-span-two { grid-column: span 2; } }
        @media (prefers-reduced-motion: reduce) { * { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
    <main class="auth-page">
        <div class="auth-wrap">@yield('content')</div>
    </main>
    <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
</body>
</html>
