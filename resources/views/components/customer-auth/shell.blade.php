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

    @if(session('success') || session('status'))
        <div class="auth-alert auth-alert--success" role="status">{{ session('success') ?? session('status') }}</div>
    @endif
    @if(session('failure') || session('error'))
        <div class="auth-alert auth-alert--error" role="alert">{{ session('failure') ?? session('error') }}</div>
    @endif

    {{ $slot }}
</section>
