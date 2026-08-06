# Affiliate Network Expansion Platform

## Product vision

The platform helps an established VTU API owner create and manage branded affiliate businesses connected to the owner's existing API.

Each affiliate operates its own customer-facing brand, pricing, wallet and sales channel. The parent remains the upstream supplier, receives the transaction volume and manages the network centrally. OresamSub becomes one supported parent provider rather than the permanent parent of every affiliate.

### Operating hierarchy

```text
Platform administrator
└── Parent business/API owner
    ├── Affiliate business
    │   └── Retail customers
    └── Affiliate business
        └── Retail customers
```

### Core sales promise

> Launch and manage branded affiliate businesses whose transactions flow through the VTU infrastructure you already own.

## Complete capability roadmap

The following 23 capabilities capture the full product opportunity. They are a roadmap, not the scope of the first release.

### 1. One-click affiliate launch

A parent creates an affiliate from a guided form. The system prepares the affiliate's administrator account, branding, website or subdomain, product access, pricing defaults and onboarding checklist. Launching an affiliate should become a configuration task rather than a separate development project.

### 2. Bring-your-own API

An independent parent connects its existing VTU API without replacing its underlying business. A provider adapter handles its authentication, endpoints, request formats, product identifiers, status responses and callbacks. Credentials must be encrypted and never exposed to affiliates.

### 3. Parent-controlled product catalogue

The parent determines which data, airtime, cable, electricity and other supported services its affiliates may sell. Parent changes to availability, wholesale pricing or plan status flow to connected affiliates according to controlled publishing rules.

### 4. Flexible affiliate pricing

The system supports fixed prices, fixed markups, percentage markups, suggested prices and minimum or maximum retail prices. Rules may apply globally, by product or by affiliate while protecting the parent's wholesale price.

### 5. Parent-to-affiliate wallet structure

Each affiliate has a funding relationship with its parent through a prepaid balance, linked upstream wallet or explicitly approved credit limit. The system records funding, deductions, low-balance conditions and reconciliation without allowing an overdraft by accident.

### 6. Central parent dashboard

The parent views affiliate count, activation status, transaction volume, revenue, service performance, wallet position and pending or failed transactions. The dashboard highlights the strongest, inactive and operationally risky affiliates.

### 7. Affiliate dashboard

An affiliate manages only its own customers, transactions, wallet, prices, branding, reports and support information. It cannot view another affiliate or the parent's private API credentials.

### 8. Customer-facing WhatsApp sales bot

Each affiliate can offer a branded WhatsApp purchase journey for products, transaction-status checks, receipts and support escalation. The affiliate's retail price applies while fulfillment continues through the parent API.

### 9. Parent administrative WhatsApp assistant

The parent can request summaries and alerts through WhatsApp, such as daily sales, leading affiliates and pending transactions. High-risk actions such as disabling services or changing prices require secure confirmation.

### 10. Automated branding and provisioning

After licence activation, the platform prepares the affiliate's subdomain, theme, product access, administrator account, parent connection and welcome materials. Provisioning must be repeatable and auditable.

### 11. Custom domains and white labelling

Affiliates can use their own domains, logos, colours, business names and support details. Parent attribution may be visible or hidden according to the commercial package and parent policy.

### 12. Automatic plan synchronization

Where the parent exposes a catalogue endpoint, the platform imports new plans and detects price, name, validity and availability changes. Changes may publish automatically or wait for approval depending on the parent's settings.

### 13. Controlled plan-import template

Parents without catalogue endpoints can upload a defined spreadsheet. Imported plans reference normalized global categories and provider-specific codes. Unknown categories enter a review queue rather than being published incorrectly.

### 14. Transaction reconciliation

Every purchase uses a unique reference and idempotency protection. The system records normalized and sanitized provider responses, processes callbacks, requeries pending transactions, tracks refunds and sends uncertain outcomes to a manual review queue.

### 15. Network and provider monitoring

The platform detects or records downtime, slow responses and elevated failure rates. Parents and affected affiliates receive appropriate warnings, suspension notices and recovery messages.

### 16. Affiliate licence management

The platform administrator manages affiliate purchases, parent ownership, activation, expiration, renewal, suspension, package, domain status and maintenance status. The licence rules state clearly what is included and what requires an upgrade.

### 17. Bulk affiliate packages

Parents may purchase one-, three-, five-, ten- or custom-sized affiliate packages. Packages can differ by domain, support, reporting, WhatsApp automation, customization and renewal terms.

### 18. Commission and profit reporting

Reports distinguish the provider cost, parent wholesale charge, affiliate selling price, affiliate markup, refunds and estimated profit. Incomplete cost data is labelled clearly rather than presented as confirmed profit.

### 19. Tenant isolation and customer ownership

Every parent, affiliate, user, wallet, plan and transaction is scoped to the correct tenant. Authorization policies, database constraints and tests prevent cross-tenant access. Administrative actions are written to an audit log.

### 20. Central updates and maintenance

Affiliates share a managed multi-tenant application rather than separate code copies. Security fixes, provider changes and product improvements can be deployed centrally without removing independent branding.

### 21. Promotional toolkit

The platform can generate affiliate referral links, QR codes, launch flyers, WhatsApp status graphics, promotional captions and price-list assets. These tools help a newly launched affiliate acquire its first customers.

### 22. Parent announcements

The parent publishes price changes, new services, promotions, downtime and training information to all or selected affiliates. Delivery channels may include dashboards, email, WhatsApp, Telegram and push notifications.

### 23. Growth and performance tools

Parents analyze affiliate activity, customer growth, repeat purchases, average transaction value, product mix, funding behaviour and failure rates. The system should turn these metrics into practical actions such as re-engaging an inactive affiliate.

## The version to build first

### MVP objective

Prove that one independent parent API owner will pay to connect at least two affiliates and that real customer purchases can travel safely through the correct parent API.

### MVP scope

1. **Parent business records:** create and manage parent organizations independently of OresamSub.
2. **Provider connection:** store an adapter type, base URL, encrypted credentials and connection status for each parent.
3. **First external adapter:** implement one real parent API after documentation and test credentials are supplied.
4. **Normalized catalogue:** treat OresamSub as the first provider and map both OresamSub and the pilot parent's plans into shared, provider-scoped structures.
5. **Plan import:** support the controlled spreadsheet template first; add automatic API synchronization only if required by the pilot API.
6. **Affiliate linkage:** assign every affiliate to exactly one parent connection in the first release.
7. **Plan inheritance:** show only plans belonging to the affiliate's parent and allow an affiliate retail markup.
8. **Transaction routing:** resolve the affiliate, plan, parent connection and adapter before submitting a purchase.
9. **Normalized outcomes:** represent provider responses consistently as pending, successful or failed while retaining sanitized diagnostic metadata.
10. **Basic reconciliation:** requery pending transactions through scheduled Laravel commands and protect purchases from duplicate submission.
11. **Parent dashboard:** display connected affiliates, transaction totals and transaction status summaries.
12. **Affiliate dashboard:** preserve existing customer, wallet, purchase and transaction functions while strictly scoping them to the affiliate.
13. **Branding and domain:** retain current affiliate domain resolution and allow standard branding configuration.
14. **Licence status:** record whether an affiliate licence is pending, active, suspended or expired.
15. **Tenant security:** add authorization, scoping, audit and automated isolation tests before onboarding external parents.

### Explicitly deferred from the MVP

- Direct multi-provider routing for one affiliate
- Automatic failover between providers
- Customer-facing WhatsApp bot
- Parent administrative WhatsApp assistant
- Telegram bot
- Automated domain and SSL provisioning
- Advanced commissions and profit analytics
- Promotional asset generation
- Mobile application generation
- Complex credit limits
- Fully automatic catalogue matching

These remain planned differentiators and upsells. Deferring them protects the first release from becoming too large to validate.

## MVP success criteria

The MVP is commercially and technically validated when:

- One external parent has supplied documentation and test credentials.
- The parent has paid for or committed a deposit for at least two affiliate licences.
- The parent's plans can be imported and approved without changing application code for each plan.
- Both affiliates display only the correct parent's products and prices.
- Real or provider-approved test transactions complete through the external parent adapter.
- Duplicate submissions do not create duplicate upstream purchases.
- Pending transactions can be reconciled and traced.
- Parent A cannot access OresamSub or another parent's credentials, plans, wallets or transaction data.
- The parent can see its affiliates and their aggregate performance.
- An affiliate can manage its customers without seeing sibling affiliates.
- A new affiliate can be configured without copying the Laravel application or creating a new product-plan table.

## Commercial pilot

The first offer should be a controlled paid pilot:

1. Select one established API owner with at least two affiliates ready.
2. Review its API documentation before quoting the adapter work.
3. Charge separately for custom adapter onboarding and affiliate licences.
4. Import and approve its catalogue.
5. Launch two branded affiliates.
6. Run test transactions and a limited live rollout.
7. Measure launch time, transaction success, support workload and parent satisfaction.
8. Use the verified result as the first case study before scaling acquisition.

## Infrastructure constraint

The initial system can remain on shared hosting if it uses one multi-tenant application and database, scheduled commands are reliable and traffic remains moderate. Affiliates must not receive separate Laravel installations. Migration to a VPS should be triggered by measured resource throttling, failed scheduled work, growing pending queues or concurrency limits—not by affiliate count alone.

## Product guardrails

- Never expose or log provider credentials.
- Never claim a phone number is WhatsApp-enabled without verification.
- Never publish an imported plan that has an ambiguous category or provider code.
- Never report estimated profit as confirmed when provider cost is missing.
- Never promise automatic refunds, synchronization or failover unless the connected API supports them.
- Never allow convenience features to weaken tenant isolation, wallet integrity or transaction traceability.

