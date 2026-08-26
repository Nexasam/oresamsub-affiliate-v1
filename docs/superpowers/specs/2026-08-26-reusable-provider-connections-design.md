# Reusable Provider Connections Design

## Objective

Make provider onboarding simple for parent administrators while keeping technical integration configuration reusable and controlled by the platform.

The hierarchy is:

```text
Provider adapter -> Provider connection -> Parent provider connection
```

An adapter defines the integration flow. A connection represents a specific provider website using that flow. A parent connection contains only that parent's provider choice, encrypted credentials, and operational preferences.

## Responsibilities

### Provider adapters

Platform-admin managed reusable integration definitions. An adapter contains:

- name, slug, internal adapter key and status;
- supported products and HTTP methods;
- credential-field definitions, including labels, type, required status and secret status;
- endpoints and service-specific configuration;
- request parameters and headers;
- network mappings;
- customer-validation flows;
- success conditions and response paths;
- timeout and expected HTTP status rules.

Adapters contain no parent credentials.

### Provider connections

Platform-admin managed provider catalogue entries such as Gongoz, PaulTechs or a specific MSORG-powered website.

A connection belongs to one adapter. Selecting an adapter prefills the complete adapter definition. The platform admin may override any copied value for that provider. The stored connection configuration is a snapshot, so later adapter edits do not silently change existing providers. A platform admin may explicitly apply a newer adapter version while preserving or reviewing connection overrides.

Connections contain provider identity and non-secret operational information, including provider name, slug, website/base URL, support/documentation URLs and active status. Connections contain no parent credentials.

### Parent provider connections

A parent chooses an adapter first and then either:

1. selects an existing active connection under that adapter; or
2. proposes an unlisted provider website that uses the selected adapter.

For an existing connection, the parent supplies only the credential values required by the connection, an optional local display name, and primary/backup preference.

For an unlisted provider, the parent also supplies the proposed provider name, website/base URL, documentation URL and optional notes. This creates a pending discovery request linked to the pending parent connection. It does not immediately create an active shared connection.

Parent credentials remain encrypted in `parent_provider_connections` and are never copied into an adapter, shared connection, audit record or normal response.

## Approval workflow

The platform-admin approval queue distinguishes:

- **Existing connection request**: parent selected an approved connection.
- **New connection requested from adapter**: parent selected an adapter but proposed an unlisted provider.

The reviewer can see the adapter, selected/proposed connection identity, website, API/base URL, documentation, supported services, parent notes, and a supplied/missing indicator for every expected credential. Raw secret values are never displayed.

The platform may perform a server-side connection test. The UI receives only a sanitized result such as HTTP status, success/failure, response message and redacted diagnostic response.

Approving an existing connection activates the parent connection.

Approving a discovery request executes one database transaction that:

1. creates or reuses a matching provider connection under the selected adapter;
2. snapshots the adapter configuration into the connection;
3. applies reviewed platform overrides;
4. attaches the pending parent connection to it;
5. preserves encrypted credentials on the parent connection only;
6. marks the parent connection approved and records the reviewer/audit event.

Rejecting a request records a reason and creates no active shared connection.

Approved discovered connections become selectable by future parents, preventing duplicate catalogue entries.

## Runtime resolution

```text
Product plan route
-> parent provider connection
-> provider connection configuration snapshot
-> adapter/internal client
-> request execution
```

Runtime request configuration comes from the provider connection. Runtime credentials and parent-specific values come from the parent connection. The two are merged in memory only for request execution.

Existing transaction route snapshots and approval requirements remain intact.

## Backward compatibility

Existing records must be migrated without changing live purchasing behaviour:

- current `provider_connections` rows receive or retain a compatible adapter association;
- their existing technical definitions are retained as connection snapshots;
- current `parent_provider_connections.settings` values are migrated to their shared connection only when they are truly provider-wide;
- encrypted credentials remain on parent connections;
- legacy OresamSub processing remains controlled by its existing feature flags;
- the configurable provider client temporarily supports both the old and new configuration locations during rollout.

The new parent form must not expose the old advanced mapping editor. Platform-admin screens retain the advanced adapter and connection editors.

## Validation and security

- Adapter, connection and parent ownership must be validated on every request.
- An existing connection must belong to the selected adapter.
- Provider identity should be deduplicated by normalized host plus adapter, with an explicit platform override for legitimate exceptions.
- Credential fields must be validated from the selected connection/adapter definition.
- Secret values use password inputs, encrypted casts, hidden model fields and redacted logs.
- Leaving a credential input blank during editing preserves its existing encrypted value.
- Adapter or connection deactivation prevents new selections but does not erase historical configuration.
- Parent connection changes continue to require reapproval.

## Interface changes

### Parent admin

The connection form becomes a short wizard:

1. Choose adapter.
2. Choose an existing connection or “My provider is not listed”.
3. Enter credentials and parent-specific preferences.
4. Add provider identity details only when proposing an unlisted provider.
5. Review and submit for approval.

### Platform admin

- Adapter catalogue: complete reusable configuration editor.
- Connection catalogue: choose adapter, prefill configuration, override provider-specific values and manage versions/status.
- Approval queue: review existing selections and adapter-based discovery requests, inspect non-secret details, run sanitized tests, approve or reject.

## Testing requirements

- Adapter configuration prefills new connections and can be overridden.
- Editing an adapter does not silently mutate existing connection snapshots.
- Parents see only active adapters and matching active connections.
- Parents cannot submit a connection belonging to another adapter.
- Existing-connection approval activates only the owning parent's record.
- Discovery approval creates/reuses one shared connection and attaches the parent atomically.
- Concurrent discovery approvals cannot create duplicate connections.
- Rejection creates no active shared connection.
- Credentials are encrypted and absent from pages, JSON, audits and logs.
- Credential presence indicators and sanitized connection tests work.
- Existing provider transactions continue to execute during the compatibility rollout.
