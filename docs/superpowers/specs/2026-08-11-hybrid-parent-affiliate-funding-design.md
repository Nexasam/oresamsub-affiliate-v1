# Hybrid Parent and Affiliate Funding Design

## Objective

Add multi-parent funding configuration without changing the existing OresamSub generation or webhook flow until explicitly enabled.

## Ownership

- Platform administrators approve the global provider catalogue. Initial providers are Xixapay and SecurewaveNG.
- A parent enables catalogue providers, stores optional parent-managed credentials, and may stop new virtual-account generation.
- Each affiliate uses either `parent_managed` or `affiliate_managed` configuration per provider.
- Mode changes are pending until the parent approves them. The last approved mode remains effective while a request is pending.
- Parents maintain each provider's reusable bank catalogue. Every bank has its own flat or capped-percentage charge, activation state and new-generation state.
- Affiliate-managed configurations inherit bank identities from the parent catalogue but own their bank activation and charge settings.
- Parent-managed configurations use the parent's credentials, webhook secret and bank charge settings.

## Runtime safeguards

- Disabling configuration blocks new virtual-account generation only. Existing virtual accounts remain valid.
- Every new virtual account snapshots its parent, provider and affiliate configuration.
- Webhooks resolve against the account snapshot, not the current active configuration.
- Provider event IDs are unique per provider and wallet crediting must be idempotent.
- Parent-managed and affiliate-managed webhook endpoints use stable opaque keys and encrypted signing secrets.
- Secrets are encrypted, hidden from serialization, masked in forms and excluded from logs.

## Backward compatibility

- All schema changes are additive and nullable where they touch legacy records.
- Existing OresamSub funding tables, routes, virtual accounts and webhooks remain intact.
- `MULTI_PARENT_FUNDING_ENABLED` defaults to `false`; the legacy flow remains authoritative until controlled rollout.
