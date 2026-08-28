# ResellGrid Landing Page Design

## Status

Documented for later implementation. The visual companion and image-generation concept pass were intentionally skipped to preserve Codex usage. The page will be designed directly in the existing application and refined using browser screenshots.

## Objective

Create a premium, conversion-focused landing page for ResellGrid that immediately captures the attention of Nigerian VTU business owners and clearly positions the platform as infrastructure for business expansion—not another retail VTU website competing primarily on price.

The landing page must explain two connected opportunities:

1. A parent VTU business can use ResellGrid to launch and manage affiliate websites under its existing business.
2. An affiliate receives a branded, operational storefront while still benefiting from the parent business's plans, provider routing and commercial structure.

## Initial Domain Scope

The new landing page must display only when the exact request host is:

```text
affiliate.emiplug.com
```

All existing affiliate domains must continue using their current configurable landing pages. The implementation must branch by request host and must not replace or modify the existing affiliate landing-page behavior.

This domain restriction is an initial rollout measure and should be easy to expand later through configuration.

## Audience

### Primary audience

Existing VTU website owners who already operate a platform and want to grow through owned affiliate websites without rebuilding their provider infrastructure.

### Secondary audience

Entrepreneurs who want an affiliate VTU business with branding, customer management, pricing flexibility, funding and reporting already provided.

## Positioning

ResellGrid should be presented as:

- The infrastructure behind scalable VTU distribution.
- A growth system for parent businesses and their affiliates.
- A software manufacturer and orchestration layer, rather than another price-competing VTU retailer.
- A controlled ecosystem combining provider integrations, product plans, pricing, funding, wallets, reporting and operational safeguards.

The core message should communicate that a business owner can turn one working VTU operation into a network of independently branded affiliate businesses while retaining operational and commercial control.

## Visual Direction

The page should feel like premium financial and infrastructure technology adapted to the Nigerian VTU market.

- Deep ink or near-black foundation.
- Electric blue and restrained cyan accents.
- Subtle grid geometry that supports the ResellGrid name.
- Crisp, confident typography with excellent mobile readability.
- Real product-interface imagery instead of generic stock photography.
- Strong whitespace, clear hierarchy and varied section rhythm.
- Restrained motion used to reveal relationships and product capability.
- Mobile-first implementation with an excellent small-screen first viewport.
- No repetitive template-style card grids or excessive decorative badges.

The first viewport must immediately communicate the offer, show credible product evidence and provide one clear conversion action.

## Navigation

The initial navigation should remain compact:

- Product
- For Parents
- For Affiliates
- Why ResellGrid
- About
- Support
- Talk to us

On mobile, navigation should collapse into a polished accessible menu. The primary action remains visible or easily reachable.

## Page Structure

### 1. Hero

The hero must make the commercial opportunity understandable within seconds.

Recommended message direction:

```text
Turn one VTU business into a network of growing affiliate brands.
```

Supporting copy should explain that ResellGrid connects affiliate websites to a parent business's products, provider routes, pricing and settlement infrastructure while giving each affiliate its own branded customer experience.

Primary action:

```text
Talk to us on WhatsApp
```

Secondary action:

```text
See how it works
```

The hero should include a carefully composed product visual using real ResellGrid interfaces, potentially combining:

- Parent business workspace.
- Affiliate admin workspace.
- V2 customer mobile experience.

The hero must not make unsupported numerical claims or invent customer logos, revenue figures or testimonials.

### 2. The Growth Model

Visually explain:

```text
Provider connections → Parent business → Affiliate websites → Affiliate customers
```

This section should clarify that the parent keeps commercial control while affiliates operate their own customer-facing businesses.

### 3. Built for Parent Businesses

Explain the parent's capabilities and benefits:

- Create and manage approved affiliates.
- Assign affiliates to as many as six reseller levels.
- Own parent-scoped product plans and acquisition pricing.
- Import product plans through an intelligent spreadsheet workflow.
- Connect approved providers through reusable adapters.
- Route individual plans to the appropriate provider connection.
- Maintain primary routing while preparing alternative connections.
- Control parent defaults and plan-specific pricing overrides.
- Establish affiliate settlement wallets.
- Enable funding providers and bank configurations.
- View transactions, realised profit and financial activity.
- Monitor grouped plan-health and provider-failure alerts.
- Disable an unhealthy plan across all affiliates immediately without destroying affiliate pricing preferences.
- Review and reconcile uncertain transactions.
- Use onboarding checklists and scoped administration.

The business benefit should remain more prominent than the technical implementation.

### 4. Built for Affiliates

Explain the affiliate capabilities and benefits:

- Receive a branded storefront connected to an established parent business.
- Configure customer profit levels within parent-approved limits.
- Offer up to six customer pricing levels.
- Manage customers, transactions and customer availability.
- Use the V1 interface or the modern V2 PWA-style customer interface.
- Configure affiliate-managed funding credentials where enabled.
- Allow customers to generate virtual accounts.
- Fund the business settlement wallet through supported parent funding infrastructure.
- See settlement credits, reservations, captures, releases and funding history.
- Track realised affiliate profit.
- Control local plan availability while respecting parent availability.
- Use onboarding guidance without needing to understand provider integration internals.

### 5. Customer Experience Showcase

Use real snapshots of both customer interfaces:

- V1 customer experience.
- V2 mobile-first/PWA-style customer experience.

The showcase should cover representative screens such as:

- Dashboard.
- Buy data.
- Airtime.
- Cable.
- Electricity.
- Virtual accounts/funding.
- Transactions and receipt details.
- Pricing.

The screenshots should use safe demonstration data. Customer details, tokens, balances, phone numbers and production references must be anonymised.

V1 and V2 should be presented as interface choices sharing the same underlying business logic and data, not as two separate products.

### 6. One Operational System

Group the broader platform capabilities into a coherent operational story:

- Multi-parent tenancy and strict ownership scoping.
- Provider adapters and reusable connection definitions.
- Parent-specific encrypted credentials.
- Product-specific request, response and validation flows.
- Parent product catalogue and affiliate inheritance.
- Unified pricing resolution.
- Parent and affiliate profit protection.
- Parent-managed purchase settlement.
- Funding-provider catalogue and signed webhook processing.
- Idempotent wallet credits and exactly-once financial handling.
- Reservation, capture, release and reconciliation workflow.
- Provider request/response diagnostics with redaction.
- Plan-health monitoring and route switching support.
- Approval workflows for sensitive connection and ownership changes.
- Audit trails, onboarding checklists and scoped dashboards.

### 7. How It Works

Recommended journey:

1. ResellGrid onboards and verifies the parent business.
2. The parent configures providers, plans, prices and funding.
3. An affiliate is submitted and approved.
4. The affiliate inherits the parent's eligible catalogue and configures its customer business.
5. Customers fund wallets and purchase services through the affiliate storefront.
6. ResellGrid records settlement, routing, profit and operational activity.

This section should make onboarding feel manageable rather than technical or overwhelming.

### 8. Why ResellGrid

Differentiate ResellGrid without unverifiable competitor attacks:

- Built for parent-to-affiliate expansion rather than only direct VTU retailing.
- Provider-independent architecture instead of one permanently hard-coded upstream.
- Reusable adapter catalogue that reduces repeated integration work.
- Parent-scoped plans, prices, funding, reporting and administration.
- Real operational safeguards around money movement and uncertain provider responses.
- V1 compatibility alongside a modern V2 experience.
- Controlled migration and rollout designed to preserve existing businesses.
- A system where parents, affiliates and customers each have the correct level of control.

Avoid claims such as "the only platform" or "guaranteed revenue" unless they can later be proven.

### 9. About ResellGrid

Present ResellGrid as a Nigerian-built infrastructure product focused on helping digital-service businesses distribute products through independently branded affiliate networks.

The About section should communicate:

- Practical experience with real VTU operations.
- Understanding of providers, wallets, pricing and reseller relationships.
- Commitment to making sophisticated infrastructure manageable for non-technical business owners.
- A long-term vision extending beyond a single provider or product category.

### 10. Support and Onboarding

The initial support flow is WhatsApp-first.

Primary WhatsApp message:

```text
Hello ResellGrid, I run a VTU business and would like to learn how to launch affiliate websites under my platform.
```

The WhatsApp number is not yet supplied. It should be configured rather than embedded directly in the Blade template, for example:

```text
RESELLGRID_WHATSAPP_NUMBER=2348012345678
```

The configuration should generate a `wa.me` link with a URL-encoded message.

Support presentation should set expectations around:

- Initial business discussion.
- Parent requirements review.
- Provider and catalogue assessment.
- Guided configuration.
- Affiliate pilot setup.
- Controlled launch and post-launch support.

### 11. Final Conversion Section

End with one decisive message inviting an existing VTU owner to discuss expanding through affiliate websites.

Primary action:

```text
Talk to ResellGrid on WhatsApp
```

Avoid displaying pricing until the commercial model and packages have been deliberately finalised.

### 12. Footer

Include:

- ResellGrid identity and concise positioning.
- Product anchors.
- Parent and affiliate anchors.
- About and support anchors.
- Privacy and terms placeholders only when matching routes exist.
- Copyright year generated dynamically.

## Content and Claims Policy

- Use concrete product capabilities already implemented in the codebase.
- Do not invent customers, partner logos, testimonials or transaction volume.
- Do not expose provider credentials, real phone numbers or production transaction data in screenshots.
- Avoid absolute superiority claims. Explain the structural advantages clearly instead.
- Keep language accessible to business owners while retaining enough technical credibility for experienced operators.

## Implementation Direction

- Use the existing Laravel/Blade stack for the marketing page.
- Keep the new page isolated in dedicated view/style/script assets.
- Use normal Blade for content and lightweight JavaScript or Alpine only for navigation, screenshot switching, accordions or subtle interactions.
- Avoid adding a heavy frontend dependency for a mostly static page.
- Route by the exact request host before the existing affiliate landing-page data flow runs.
- Preserve existing authentication routes and affiliate landing pages.
- Make the permitted marketing host configurable after the initial exact-host rollout if useful.
- Use responsive, accessible HTML with keyboard navigation, visible focus states and reduced-motion support.
- Optimise screenshots and visual assets as WebP/AVIF where practical.
- Include appropriate SEO title, description, Open Graph metadata and canonical host handling.

## Product Screenshot Plan

Before implementation:

1. Capture desktop and mobile views of the V1 and V2 customer interfaces.
2. Replace or mask production/customer data.
3. Select a small number of strong images rather than showing every page at once.
4. Use a consistent browser/device frame treatment.
5. Confirm that the images remain legible on mobile.

If clean screenshots cannot be captured from seeded/local data, create a safe local demonstration affiliate/customer dataset before capture.

## Quality and Verification

Implementation is not complete until the following are verified:

- `affiliate.emiplug.com` receives the new page.
- Existing affiliate domains still receive their original landing pages.
- Desktop, tablet and mobile layouts have been visually reviewed.
- Navigation, anchors, mobile menu and WhatsApp actions work.
- V1/V2 screenshots load and remain readable.
- No sensitive production data appears in page source or assets.
- Page remains usable with JavaScript disabled except optional enhancements.
- Core Web Vitals are protected through optimised assets and minimal JavaScript.
- Accessibility basics pass: semantic landmarks, heading order, alt text, focus visibility, contrast and reduced motion.
- Browser screenshots are reviewed and the page is refined before production deployment.

## Deferred Decisions

- Final WhatsApp number.
- Final ResellGrid logo/wordmark asset.
- Whether the marketing host later moves from `affiliate.emiplug.com` to a dedicated ResellGrid domain.
- Final package/pricing presentation.
- Verified testimonials, customer logos or commercial metrics.
- Whether to add a contact form after the WhatsApp-first launch.

## Resume Point

When work resumes:

1. Supply the WhatsApp number.
2. Confirm or create the ResellGrid logo.
3. Capture safe V1/V2 product screenshots.
4. Inspect the exact root-route branch and add the host-specific rendering test first.
5. Build the page section by section.
6. Perform desktop/mobile browser QA and refine the implementation.

