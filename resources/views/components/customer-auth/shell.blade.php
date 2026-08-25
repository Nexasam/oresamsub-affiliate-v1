@props([
    'title',
    'description' => null,
    'affiliate' => null,
    'siteLogo' => null,
    'wide' => false,
])

@php
    $businessName = $affiliate?->name ?? 'ResellGrid';
    $logoUrl = $siteLogo
        ? asset('assets/landing_page_assets/img/site_logo/'.$siteLogo)
        : ($affiliate?->logo ? asset($affiliate->logo) : null);
@endphp

<section data-testid="customer-auth-shell" class="auth-card {{ $wide ? 'auth-card--wide' : '' }}">
    <div class="auth-brand">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $businessName }} logo" class="auth-logo">
        @else
            <div class="auth-logo auth-logo--fallback" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($businessName, 0, 1)) }}
            </div>
        @endif
        <div class="min-w-0">
            <p class="auth-business-name">{{ $businessName }}</p>
            <p class="auth-business-label">Secure customer account</p>
        </div>
    </div>

    <header class="auth-heading">
        <h1>{{ $title }}</h1>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </header>

    <div data-testid="auth-trust-message" class="auth-trust">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.8 7 10 4.1-1.2 7-5.4 7-10V6l-7-3Zm3.2 6.8-4.1 4.1-2.3-2.3 1.1-1.1 1.2 1.2 3-3 1.1 1.1Z"/></svg>
        Protected and private
    </div>

    @if(session('success') || session('status'))
        <div class="auth-alert auth-alert--success" role="status">{{ session('success') ?? session('status') }}</div>
    @endif
    @if(session('failure') || session('error'))
        <div class="auth-alert auth-alert--error" role="alert">{{ session('failure') ?? session('error') }}</div>
    @endif

    {{ $slot }}
</section>
