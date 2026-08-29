<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#06101f">
    <meta name="description" content="ResellGrid helps VTU businesses launch and manage branded affiliate websites with shared plans, provider routing, funding, settlement and reporting.">
    <title>ResellGrid — Grow one VTU business into an affiliate network</title>
    <link rel="icon" href="/assets/resellgrid/resellgrid-mark.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/resellgrid/landing.css?v={{ filemtime(public_path('assets/resellgrid/landing.css')) }}">
</head>
<body data-resellgrid-landing>
<a class="skip-link" href="#main">Skip to content</a>
<div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>

<header class="site-header" data-header>
    <div class="shell nav-wrap">
        <a class="brand" href="#top" aria-label="ResellGrid home">
            <img src="/assets/resellgrid/resellgrid-mark.svg" alt="" width="34" height="34">
            <span>ResellGrid</span>
        </a>
        <button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="site-nav">
            <span class="sr-only">Open navigation</span><span></span><span></span>
        </button>
        <nav class="site-nav" id="site-nav" data-menu aria-label="Primary navigation">
            <a href="#product">Product</a>
            <a href="#parents">For Parents</a>
            <a href="#affiliates">For Affiliates</a>
            <a href="#why-resellgrid">Why ResellGrid</a>
            <a href="#about">About</a>
            <a href="#support">Support</a>
            <a class="button button-small" href="{{ $whatsappUrl }}" @if($whatsappConfigured) target="_blank" rel="noopener" @endif>Talk to us</a>
        </nav>
    </div>
</header>

<main id="main">
    <section class="hero" id="top">
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="shell hero-layout">
            <div class="hero-copy" data-reveal>
                <h1>Turn one VTU business into a network of growing affiliate brands.</h1>
                <p>Connect every affiliate website to your products, provider routes, pricing and settlement infrastructure—while each affiliate runs a branded customer experience of their own.</p>
                <div class="hero-actions">
                    <a class="button" href="{{ $whatsappUrl }}" @if($whatsappConfigured) target="_blank" rel="noopener" @endif>Talk to us on WhatsApp <span aria-hidden="true">↗</span></a>
                    <a class="text-link" href="#growth-model">See how it works <span aria-hidden="true">↓</span></a>
                </div>
                <div class="hero-proof" aria-label="Platform highlights">
                    <span>Parent-controlled growth</span><span>Provider-independent</span><span>Built for real operations</span>
                </div>
            </div>

            <div class="hero-product" data-reveal data-parallax>
                <div class="workspace-frame parent-frame">
                    <div class="frame-top"><span class="mini-mark"></span><b>Parent workspace</b><small>Live operations</small></div>
                    <div class="workspace-content">
                        <div class="metric"><small>Affiliate network</small><strong>12 businesses</strong><span>Scoped under one parent</span></div>
                        <div class="route-line"><span>MTN 1GB</span><i></i><b>Primary route</b></div>
                        <div class="route-line"><span>GOTV Jinja</span><i></i><b>Healthy</b></div>
                        <div class="profit-line"><span>Realised profit</span><strong>Tracked per transaction</strong></div>
                    </div>
                </div>
                <div class="phone-frame" aria-label="V2 customer mobile interface preview">
                    <div class="phone-status"><span>9:41</span><span>● ● ●</span></div>
                    <div class="phone-brand"><span class="mini-mark"></span><b>My business</b><span>•••</span></div>
                    <div class="balance-card"><small>Available balance</small><strong>₦18,420.00</strong><span>Fund wallet →</span></div>
                    <p class="phone-label">What would you like to buy?</p>
                    <div class="services"><span>Data</span><span>Airtime</span><span>Cable</span><span>Power</span></div>
                    <div class="phone-activity"><b>Recent activity</b><span>MTN Data <strong>Successful</strong></span><span>Airtime <strong>Successful</strong></span></div>
                </div>
                <div class="flow-note"><span></span>Pricing, routing and settlement stay connected</div>
            </div>
        </div>
    </section>

    <section class="growth" id="growth-model">
        <div class="shell" data-reveal>
            <div class="section-intro split-intro">
                <h2>Your working business becomes the engine for many.</h2>
                <p>ResellGrid turns the systems you already rely on into a controlled distribution network—without forcing every affiliate to rebuild provider integrations from scratch.</p>
            </div>
            @include('resellgrid.partials.network-map')
        </div>
    </section>

    <section class="audience-section" id="parents">
        <div class="shell audience-layout">
            <div class="sticky-copy" data-reveal>
                <span class="section-number">01 / Built for parent businesses</span>
                <h2>Grow distribution. Keep commercial control.</h2>
                <p>Give affiliates room to operate while you retain control over the catalogue, acquisition pricing, provider routes and settlement that protect your business.</p>
                <a class="text-link" href="{{ $whatsappUrl }}">Discuss your parent setup <span aria-hidden="true">↗</span></a>
            </div>
            <div class="capability-list" data-reveal>
                <article><span>01</span><div><h3>Build your affiliate network</h3><p>Submit and manage approved affiliates, assign up to six reseller levels, and keep every business correctly scoped.</p></div></article>
                <article><span>02</span><div><h3>Own plans and acquisition pricing</h3><p>Import plans, set service defaults or plan overrides, protect margins and control availability across every child website.</p></div></article>
                <article><span>03</span><div><h3>Route with confidence</h3><p>Connect approved providers through reusable adapters, map plans to the right route, and respond quickly to grouped health alerts.</p></div></article>
                <article><span>04</span><div><h3>See the money clearly</h3><p>Track settlement wallets, reservations, captures, releases, transactions and realised profit from one parent workspace.</p></div></article>
            </div>
        </div>
    </section>

    <section class="audience-section affiliate-band" id="affiliates">
        <div class="shell audience-layout reverse">
            <div class="sticky-copy" data-reveal>
                <span class="section-number">02 / Built for affiliates</span>
                <h2>A real branded business, without rebuilding the machinery.</h2>
                <p>Start with your parent’s eligible catalogue and focus on customers, pricing, funding and growth—not the complexity underneath provider integrations.</p>
            </div>
            <div class="affiliate-console" data-reveal>
                <div class="console-head"><div><small>Affiliate workspace</small><strong>Business overview</strong></div><span>Operational</span></div>
                <div class="console-stats"><div><small>Settlement</small><b>Available</b></div><div><small>Customer levels</small><b>Up to 6</b></div><div><small>Profit</small><b>Visible</b></div></div>
                <ul class="check-list">
                    <li><span>✓</span>Branded customer storefront with V1 and V2 choices</li>
                    <li><span>✓</span>Customer profit levels within parent-approved limits</li>
                    <li><span>✓</span>Virtual accounts, funding and settlement activity</li>
                    <li><span>✓</span>Customers, transactions and local plan availability</li>
                    <li><span>✓</span>Clear onboarding without provider-integration complexity</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="experience" id="product">
        <div class="shell">
            <div class="section-intro centered" data-reveal>
                <span class="section-number">ONE ENGINE · TWO EXPERIENCES</span>
                <h2>Let each business choose the customer experience that fits.</h2>
                <p>V1 compatibility and the modern V2 mobile experience share the same customers, pricing, wallets and transaction logic.</p>
            </div>
            <div class="interface-switcher" data-reveal>
                <div class="interface-tabs" role="tablist" aria-label="Customer interface versions">
                    <button id="tab-v2" role="tab" aria-selected="true" aria-controls="panel-v2" data-interface-tab="v2">V2 · Mobile experience</button>
                    <button id="tab-v1" role="tab" aria-selected="false" aria-controls="panel-v1" data-interface-tab="v1" tabindex="-1">V1 · Classic experience</button>
                </div>
                <div id="panel-v2" role="tabpanel" aria-labelledby="tab-v2" data-interface-panel="v2" class="interface-panel active">
                    <div class="showcase-copy"><h3>Feels like a modern mobile app.</h3><p>A compact dashboard, quick service access, funding accounts, transaction drawers and installable PWA support.</p><ul><li>Data, airtime, cable and electricity</li><li>Funding and virtual accounts</li><li>Pricing, history and receipts</li></ul></div>
                    <div class="showcase-screen modern-screen"><aside><span class="mini-mark"></span><b>Home</b><span>Buy services</span><span>Fund wallet</span><span>Transactions</span></aside><div><div class="screen-nav"><b>Good morning, Demo User</b><span>◎</span></div><div class="screen-balance"><small>Wallet balance</small><strong>₦8,750.00</strong></div><div class="screen-services"><span>Data</span><span>Airtime</span><span>Cable</span><span>Electricity</span></div><div class="screen-table"><b>Recent transactions</b><span>MTN 1GB <i>Successful</i></span><span>Wallet funding <i>Successful</i></span></div></div></div>
                </div>
                <div id="panel-v1" role="tabpanel" aria-labelledby="tab-v1" data-interface-panel="v1" class="interface-panel" hidden>
                    <div class="showcase-copy"><h3>Keep the familiar interface.</h3><p>Businesses that prefer the established V1 experience can retain it while benefiting from the same multi-parent infrastructure.</p><ul><li>Proven service purchase flows</li><li>Existing customer familiarity</li><li>Shared logic, no duplicated operations</li></ul></div>
                    <div class="showcase-screen classic-screen"><div class="classic-nav"><b>Business Portal</b><span>Dashboard　Services　Transactions</span></div><div class="classic-body"><h4>Welcome back, Demo User</h4><div class="classic-cards"><span>Wallet balance<br><b>₦8,750.00</b></span><span>Transactions<br><b>24</b></span><span>Funding<br><b>Active</b></span></div><div class="classic-table"><b>Recent transactions</b><span>Service　 Amount　 Status</span><span>MTN Data　 ₦650　 Successful</span></div></div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="operations">
        <div class="shell operations-layout">
            <div class="operations-title" data-reveal><span class="section-number">THE INFRASTRUCTURE LAYER</span><h2>One operational system.</h2><p>Everything needed to keep providers, products, money and ownership aligned as the network grows.</p></div>
            <div class="operations-grid" data-reveal>
                <article><span>⌁</span><h3>Provider orchestration</h3><p>Reusable adapters, encrypted parent credentials, product-specific mappings and plan-level routes.</p></article>
                <article><span>₦</span><h3>Financial integrity</h3><p>Unified pricing, profit protection, settlement reservations, captures, releases and exactly-once credits.</p></article>
                <article><span>◎</span><h3>Operational safeguards</h3><p>Redacted diagnostics, reconciliation, health alerts, approvals, audit trails and controlled rollout.</p></article>
                <article><span>↳</span><h3>Strict ownership</h3><p>Parent-scoped plans, funding, reports and administration with the correct control at every level.</p></article>
            </div>
            @include('resellgrid.partials.settlement-flow')
        </div>
    </section>

    <section class="journey">
        <div class="shell">
            <div class="section-intro split-intro" data-reveal><h2>From one business to a working network.</h2><p>Guided onboarding keeps a powerful system manageable for non-technical owners and their affiliates.</p></div>
            <ol class="journey-list" data-reveal>
                <li><span>01</span><div><h3>Onboard the parent</h3><p>ResellGrid verifies the business and prepares its controlled workspace.</p></div></li>
                <li><span>02</span><div><h3>Configure the engine</h3><p>Add provider connections, product plans, prices and funding.</p></div></li>
                <li><span>03</span><div><h3>Approve an affiliate</h3><p>Attach the branded website and assign its reseller level.</p></div></li>
                <li><span>04</span><div><h3>Open for business</h3><p>Customers fund wallets and buy while routing, settlement and profit are recorded.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="why" id="why-resellgrid">
        <div class="shell why-layout">
            <div data-reveal><span class="section-number">WHY RESELLGRID</span><h2>Designed for expansion—not another race to the lowest retail price.</h2></div>
            <div class="why-points" data-reveal>
                <p><strong>Parent-to-affiliate by design.</strong> The ownership, pricing and operational model is built around distribution networks.</p>
                <p><strong>Provider-independent.</strong> Plans can route through approved connections instead of one permanently hard-coded upstream.</p>
                <p><strong>Safe money movement.</strong> Reservations, reconciliation and idempotent funding protect more than a simple API relay can.</p>
                <p><strong>Built to migrate responsibly.</strong> V1 compatibility and controlled rollout help preserve businesses already running.</p>
            </div>
        </div>
    </section>

    <section class="about" id="about">
        <div class="shell about-layout" data-reveal>
            <span class="giant-mark">RG</span>
            <div><span class="section-number">ABOUT RESELLGRID</span><h2>Nigerian-built infrastructure for the businesses behind digital services.</h2><p>ResellGrid comes from practical experience with VTU providers, wallets, pricing, reseller relationships and the operational realities that appear after a platform begins to grow.</p><p>Our purpose is to make sophisticated distribution infrastructure manageable for business owners—across many providers, affiliates and, eventually, product categories beyond VTU.</p></div>
        </div>
    </section>

    <section class="support" id="support">
        <div class="shell support-card" data-reveal>
            <div><span class="section-number">READY TO BUILD YOUR NETWORK?</span><h2>Your next affiliate should strengthen your business—not create more complexity.</h2><p>Tell us how your current VTU platform works. We’ll show you what parent and affiliate onboarding can look like with ResellGrid.</p></div>
            <div class="support-action"><a class="button button-light" href="{{ $whatsappUrl }}" @if($whatsappConfigured) target="_blank" rel="noopener" @endif>Start on WhatsApp <span aria-hidden="true">↗</span></a>@unless($whatsappConfigured)<small>WhatsApp support is being connected. Use this page again once the support number is active.</small>@endunless</div>
        </div>
    </section>
</main>

<footer>
    <div class="shell footer-layout"><a class="brand" href="#top"><img src="/assets/resellgrid/resellgrid-mark.svg" alt="" width="30" height="30"><span>ResellGrid</span></a><p>Infrastructure for scalable VTU distribution.</p><span>© {{ date('Y') }} ResellGrid</span></div>
</footer>

<script src="/assets/resellgrid/landing.js?v={{ filemtime(public_path('assets/resellgrid/landing.js')) }}" defer></script>
</body>
</html>
