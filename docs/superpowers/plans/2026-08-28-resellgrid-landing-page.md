# ResellGrid Landing Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Launch a premium, conversion-focused ResellGrid marketing page exclusively on `affiliate.emiplug.com` without altering any existing affiliate landing page.

**Architecture:** The existing root route will short-circuit on a configurable marketing host before running legacy affiliate landing queries. A dedicated Blade view will own semantic content, while isolated CSS and lightweight JavaScript provide the responsive visual system, accessible navigation, interface showcase, and progressive reveal behavior. WhatsApp details remain environment-configurable.

**Tech Stack:** Laravel 11, Blade, plain CSS, lightweight browser JavaScript, PHPUnit feature tests.

**Spec:** `docs/superpowers/specs/2026-08-28-resellgrid-landing-page-design.md`

## Global Constraints

- Render the new page only for the exact configured host, defaulting to `affiliate.emiplug.com`.
- Preserve existing affiliate landing-page behavior and data queries unchanged for every other host.
- Do not invent testimonials, customer logos, revenue figures, or unsupported market claims.
- Use a deep-ink, electric-blue and restrained-cyan visual system with mobile-first responsive behavior.
- Keep WhatsApp destination and initial message in configuration; never hard-code a private number in Blade.
- Use anonymised product visuals and no production customer details, tokens, balances, phone numbers, or transaction references.
- Use no new frontend framework or runtime dependency.

---

### Task 1: Marketing Host Isolation and Configuration

**Files:**
- Create: `config/resellgrid.php`
- Modify: `routes/web.php:483`
- Create: `tests/Feature/ResellGridLandingPageTest.php`

**Interfaces:**
- Consumes: Laravel request host and `RESELLGRID_MARKETING_HOST`, `RESELLGRID_WHATSAPP_NUMBER` environment values.
- Produces: `config('resellgrid.marketing_host')`, `config('resellgrid.whatsapp.number')`, `config('resellgrid.whatsapp.message')`, and a host-isolated `resellgrid.landing` response.

- [ ] Write a feature test asserting `affiliate.emiplug.com` receives the ResellGrid view marker and a different host does not.
- [ ] Run `php artisan test tests/Feature/ResellGridLandingPageTest.php` and confirm the marketing-host assertion fails.
- [ ] Add `config/resellgrid.php` and short-circuit the existing `/` closure before legacy queries when the exact host matches.
- [ ] Run the focused test and confirm host isolation passes.

### Task 2: Semantic Landing Page and Conversion Flow

**Files:**
- Create: `resources/views/resellgrid/landing.blade.php`
- Modify: `tests/Feature/ResellGridLandingPageTest.php`

**Interfaces:**
- Consumes: ResellGrid config values from Task 1.
- Produces: semantic sections with IDs `product`, `parents`, `affiliates`, `why-resellgrid`, `about`, and `support`; encoded WhatsApp CTA when configured; safe in-page support fallback otherwise.

- [ ] Add failing assertions for the hero message, parent and affiliate sections, operational features, About section, WhatsApp copy, and absence of unsupported claims.
- [ ] Run the focused feature test and confirm the content assertions fail.
- [ ] Build the Blade view with navigation, hero, growth model, parent capabilities, affiliate capabilities, shared customer experience, operations, onboarding journey, differentiation, About, support, final CTA, and footer.
- [ ] Generate the WhatsApp link server-side using digits-only number normalization and `rawurlencode`; fall back to `#support` when no number is configured.
- [ ] Run the focused feature test and confirm all content and CTA assertions pass.

### Task 3: Premium Responsive Visual System

**Files:**
- Create: `public/assets/resellgrid/landing.css`
- Create: `public/assets/resellgrid/resellgrid-mark.svg`
- Modify: `resources/views/resellgrid/landing.blade.php`

**Interfaces:**
- Consumes: semantic classes and sections from Task 2.
- Produces: scoped design tokens, responsive page layout, accessible focus states, reduced-motion support, and an original code-native ResellGrid mark.

- [ ] Add asset assertions to the feature test and confirm they fail.
- [ ] Implement the deep-ink visual system, grid texture, typography scale, hero composition, non-repetitive section rhythm, responsive cards/tables, mobile navigation presentation, and visible keyboard focus.
- [ ] Add the SVG brand mark and complete favicon/meta/theme-color declarations without changing affiliate-wide assets.
- [ ] Run the feature test and confirm asset assertions pass.

### Task 4: Interactive Product Evidence

**Files:**
- Create: `public/assets/resellgrid/landing.js`
- Modify: `resources/views/resellgrid/landing.blade.php`
- Modify: `tests/Feature/ResellGridLandingPageTest.php`

**Interfaces:**
- Consumes: `[data-menu-toggle]`, `[data-interface-tab]`, `[data-interface-panel]`, and `[data-reveal]` hooks in the Blade view.
- Produces: accessible mobile menu, V1/V2 interface switching, progressive reveal with reduced-motion fallback, and no dependency on Alpine or jQuery.

- [ ] Add failing assertions for tab ARIA relationships, safe demonstration data, and the JavaScript asset.
- [ ] Implement a product-interface composition showing parent workspace, affiliate workspace, V1 compatibility and V2 mobile experience using anonymised UI data.
- [ ] Implement keyboard-accessible interface tabs, menu state, escape-to-close, and IntersectionObserver reveal behavior.
- [ ] Run the focused feature test and confirm it passes.

### Task 5: Regression and Browser Launch Verification

**Files:**
- Modify only if QA exposes a defect: `resources/views/resellgrid/landing.blade.php`, `public/assets/resellgrid/landing.css`, `public/assets/resellgrid/landing.js`

**Interfaces:**
- Consumes: completed landing implementation.
- Produces: verified desktop/mobile rendering and documented production activation values.

- [ ] Run `php artisan test tests/Feature/ResellGridLandingPageTest.php`.
- [ ] Run relevant existing landing/auth route tests discovered by `php artisan test --filter=Landing` and record any unrelated legacy failures separately.
- [ ] Start the local application and inspect the marketing host at desktop and mobile widths with Playwright because no Browser plugin is available in this session.
- [ ] Inspect the latest screenshots visually, fix overflow, contrast, menu, CTA, tab, typography, and first-viewport issues, then recapture.
- [ ] Run `php artisan route:list --path=/` and `php artisan view:cache` to detect route or Blade compilation regressions.
- [ ] Document deployment values: `RESELLGRID_MARKETING_HOST=affiliate.emiplug.com` and `RESELLGRID_WHATSAPP_NUMBER=<international digits>`; clear and rebuild configuration/view caches after deployment.
