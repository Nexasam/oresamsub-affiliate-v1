# Platform Parent Business Workspace Design

## Goal

Give authenticated platform administrators a frontend for creating and viewing parent businesses before those businesses configure provider connections or manage affiliates.

## Scope

Add a platform-admin workspace at `/admin/parent-businesses`. The workspace lists existing parent businesses and contains one combined creation form.

The form captures:

- business name;
- unique slug;
- contact email and phone;
- business status;
- first parent administrator name;
- unique parent-administrator login email;
- temporary password;
- administrator status; and
- whether the administrator must change the password at first login.

Provider credentials, endpoints, mappings, affiliates, and product plans are outside this form.

## Creation Flow

The server validates the complete payload and uses a database transaction to create:

1. the parent business;
2. its first parent administrator; and
3. six active reseller levels named Basic, Bronze, Silver, Gold, Diamond, and Platinum at positions one through six.

Any failure rolls back every record. Duplicate business slugs and parent-admin emails are rejected. Password values are hashed through the existing model cast and never returned by JSON responses.

## Interface

Add “Parent businesses” to the platform sidebar. The workspace shows summary cards and a responsive business list containing business identity, status, contact details, first administrator, affiliate count, provider-connection count, and reseller-level count.

Creation uses the existing platform-admin Blade and Alpine patterns. A successful submission closes or resets the form and refreshes the list. Validation failures remain visible without exposing passwords.

## Authorization and Isolation

Only the authenticated `platform_admin` guard may load data or create parent businesses. Parent administrators and guests cannot access these routes. This workspace creates platform-owned tenant records; it does not impersonate or sign into the new parent.

## Testing

Feature tests cover:

- guest authentication protection;
- rendering and listing existing parents;
- atomic creation of the business, first administrator, and six levels;
- password hashing and response redaction;
- duplicate slug and email validation;
- invalid input producing no partial records; and
- sidebar visibility.

