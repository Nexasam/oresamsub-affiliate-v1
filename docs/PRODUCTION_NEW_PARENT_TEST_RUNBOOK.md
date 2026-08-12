# Production New-Parent Test Runbook

This runbook prepares the existing `emiplug.name.ng` OresamSub legacy affiliate as a completely fresh test affiliate belonging to a new parent business.

## Critical warning

The execution command permanently deletes the existing affiliate row and every tenant-scoped record discovered for that affiliate, including customers, transactions, wallet logs, plans, settings and funding configuration.

- Take a complete production database backup first.
- Run the preview command before execution.
- Keep all new purchase and funding feature flags disabled until configuration and testing are complete.
- Do not run this procedure for a live affiliate containing data that must be retained.
- Do not run `migrate:rollback` to recover from a migration failure.

## 1. Deploy and confirm the reset command

Deploy the current application code, including:

- `app/Console/Commands/ResetAffiliateForParentTest.php`
- `app/Services/MultiParent/ResetAffiliateTenantService.php`
- `config/parent_businesses.php`

Clear cached application state and confirm that the command is registered:

```bash
php artisan optimize:clear
php artisan list | grep reset-test-affiliate
```

Expected command:

```text
multi-parent:reset-test-affiliate
```

## 2. Keep new production flows disabled

Configure production `.env` with the new flows disabled:

```env
PARENT_OWNERSHIP_READS=false
PARENT_NORMALIZED_PRICING=false
PARENT_PROVIDER_ROUTING=false
PARENT_MANAGED_PURCHASES_ENABLED=false
MULTI_PARENT_FUNDING_ENABLED=false
```

Do not enable the purchasing flags until the new parent has an approved connection, mapped plans, valid pricing and a funded settlement wallet.

## 3. Complete the database migrations

Back up the production database, then run:

```bash
php artisan down
php artisan optimize:clear
php artisan migrate --force
php artisan up
```

Stop if any migration fails. Do not continue to the backfill or affiliate reset.

## 4. Configure the OresamSub foundation credentials

Add production values to `.env`:

```env
ORESAMSUB_PARENT_ADMIN_NAME="OresamSub Parent Admin"
ORESAMSUB_PARENT_ADMIN_EMAIL="parent-admin@oresamsub.local"
ORESAMSUB_PARENT_ADMIN_PASSWORD="REPLACE_WITH_A_STRONG_PASSWORD"
```

Then clear cached configuration:

```bash
php artisan config:clear
```

## 5. Seed the OresamSub parent foundation

```bash
php artisan db:seed --class=OresamsubParentSeeder --force
```

The seeder is idempotent. It does not rotate the password of an existing OresamSub parent administrator.

## 6. Preview the OresamSub legacy backfill

The legacy backfill is a separate operational command; migrations do not automatically perform it.

```bash
php artisan multi-parent:backfill-oresamsub-foundation --dry-run
```

Review all reported counts and errors. The report covers affiliates, customer plans, product plans, prices, routes, transactions and audit entries.

If the command reports an exception, stop. Do not run the commit command until the reported data problem has been corrected.

## 7. Commit the OresamSub legacy backfill

After a successful dry run:

```bash
php artisan multi-parent:backfill-oresamsub-foundation --commit
```

This assigns unowned legacy affiliates and product plans to OresamSub and creates the normalized pricing, routing and transaction snapshots required by the multi-parent foundation.

Run this backfill before resetting `emiplug.name.ng`.

## 8. Configure the new parent administrator password

Add a strong password to production `.env`:

```env
TEST_PARENT_ADMIN_PASSWORD="REPLACE_WITH_A_STRONG_PASSWORD"
```

The password must contain at least 12 characters, uppercase and lowercase letters, and a number.

Reload uncached configuration:

```bash
php artisan config:clear
```

Do not pass the password as a command argument because command-line arguments may be retained in shell history or process logs.

## 9. Preview the Emiplug tenant deletion

This command does not modify the database:

```bash
php artisan multi-parent:reset-test-affiliate \
  --domain=emiplug.name.ng
```

Save and review the displayed table counts. Confirm that the command resolves exactly the expected OresamSub legacy affiliate.

The command refuses to execute when:

- the domain matches zero or multiple affiliates;
- the affiliate does not belong to the OresamSub parent;
- the confirmation domain differs from the target domain;
- the new parent slug or administrator email already exists;
- required new-parent details are invalid.

## 10. Execute the destructive reset

Replace the administrator email and names if required:

```bash
php artisan multi-parent:reset-test-affiliate \
  --domain=emiplug.name.ng \
  --confirm-domain=emiplug.name.ng \
  --parent-name="Emiplug Parent" \
  --parent-slug=emiplug-parent \
  --parent-admin-name="Emiplug Parent Administrator" \
  --parent-admin-email=parent@emiplug.com \
  --affiliate-name="Emiplug" \
  --affiliate-slug=emiplug \
  --execute
```

Expected completion message:

```text
Fresh affiliate [new ID] created for parent Emiplug Parent.
```

The command creates:

- a new parent business;
- the first parent administrator;
- six active parent reseller levels;
- a new affiliate row for `emiplug.name.ng`;
- an active `parent_managed` and `multi_parent` processing profile.

The old affiliate ID and its tenant-scoped records are removed.

## 11. Rebuild production caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Confirm that production does not contain `public/hot`. Production must use the compiled `public/build` assets rather than a local Vite development server.

## 12. Log in to the new parent workspace

Open:

```text
https://YOUR_PLATFORM_DOMAIN/parent-admin/login
```

Use:

```text
Email: parent@emiplug.com
Password: the TEST_PARENT_ADMIN_PASSWORD value
```

The administrator is required to change the initial password.

## 13. Configure the new parent and affiliate

The recreated affiliate is intentionally empty. Complete the following configuration before enabling purchases:

1. Create or select an approved provider adapter.
2. Add the parent provider connection and credentials.
3. Approve the connection as platform administrator.
4. Add the parent product plans.
5. Map provider plan IDs and primary routes.
6. Configure parent acquisition pricing for the reseller levels.
7. Generate the affiliate categories and product plans.
8. Configure affiliate customer profit levels.
9. Create and fund the affiliate settlement wallet.
10. Configure one low-value Data plan for the first controlled test.
11. Create one test customer account on `emiplug.name.ng`.

## 14. Enable the controlled Data test

Only after the configuration checklist is complete, update production `.env`:

```env
PARENT_PROVIDER_ROUTING=true
PARENT_MANAGED_PURCHASES_ENABLED=true
```

Then rebuild the configuration cache:

```bash
php artisan config:cache
```

In the platform-admin rollout workspace, enable routing only for:

- the new Emiplug affiliate;
- the `data` service.

Do not enable a parent-wide rollout during the first production test.

Existing OresamSub affiliates must remain configured as:

```text
affiliate_managed + legacy_oresamsub
```

## 15. First purchase verification

For the first live Data transaction:

1. Use one phone number only.
2. Use the customer's main wallet.
3. Purchase the lowest-value configured plan.
4. Confirm the customer wallet debit.
5. Confirm the affiliate settlement reservation and capture.
6. Confirm the provider reference and routing snapshots on the transaction.
7. Confirm parent and affiliate profit snapshots.
8. Confirm that a provider timeout remains pending for reconciliation rather than being immediately refunded.
9. Confirm that a conclusive failure releases the settlement reserve and refunds the customer once.

## Emergency switch-off

If the controlled purchase flow misbehaves, immediately set:

```env
PARENT_MANAGED_PURCHASES_ENABLED=false
PARENT_PROVIDER_ROUTING=false
```

Then run:

```bash
php artisan config:cache
```

This prevents subsequent purchases from entering the new parent-managed route. Transactions already marked `reconciliation_required` must still be reviewed; switching off the flow does not automatically settle pending transactions.
