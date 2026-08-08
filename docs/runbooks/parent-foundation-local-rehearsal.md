# Parent foundation local rehearsal

> **Local development only.** This is not a production runbook, does not authorize a production deployment, and must not be run against a production database.

This records the parent/provider foundation rehearsal performed on 2026-08-08 against the database configured by the local `.env`. Confirm the target before every run:

```bash
php artisan about --only=environment
```

## Prerequisites and backup

Stop if the configured database is not a disposable/local database. Before any migration or seed, create a transactional schema/data backup outside the repository. This command refuses non-local application environments and non-loopback database hosts, reads credentials through Laravel without printing them, and creates a run-specific dump:

```bash
REHEARSAL_BACKUP="/private/tmp/oresamsub-parent-foundation-pre-rehearsal-$(date +%Y%m%d-%H%M%S).sql"
export REHEARSAL_BACKUP
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c=config("database.connections.mysql"); if(!app()->environment("local") || !in_array($c["host"],["127.0.0.1","localhost","::1"],true)){fwrite(STDERR,"Refusing non-local backup.\n"); exit(2);} $cmd=["/Applications/XAMPP/xamppfiles/bin/mysqldump","--host=".$c["host"],"--port=".$c["port"],"--user=".$c["username"],"--single-transaction","--triggers",$c["database"]]; $spec=[0=>["file","/dev/null","r"],1=>["file",getenv("REHEARSAL_BACKUP"),"w"],2=>["pipe","w"]]; $p=proc_open($cmd,$spec,$pipes,null,array_merge($_ENV,["MYSQL_PWD"=>(string)$c["password"]])); $error=stream_get_contents($pipes[2]); fclose($pipes[2]); $code=proc_close($p); if($code!==0){fwrite(STDERR,$error); exit($code);}'
test -s "$REHEARSAL_BACKUP"
shasum -a 256 "$REHEARSAL_BACKUP"
```

Record the path and checksum before continuing. The 2026-08-08 rehearsal backup was:

```text
/private/tmp/oresamsub-parent-foundation-pre-rehearsal-20260808.sql
size: 374 KB
SHA-256: 2c5749d99f0607e58e75eeb76bea6b582092f10c78a4e121bba9e4c134cfb430
```

It was created with the local XAMPP `mysqldump`, using the Laravel MySQL connection values, `--single-transaction`, and `--triggers`. Stored routines were excluded because this local MariaDB installation reports an out-of-date `mysql.proc` schema; the parent foundation does not add or change stored routines.

Verify the backup before continuing:

```bash
test -s /private/tmp/oresamsub-parent-foundation-pre-rehearsal-20260808.sql
shasum -a 256 /private/tmp/oresamsub-parent-foundation-pre-rehearsal-20260808.sql
```

## Acceptance commands

Run the focused checks and inspect the generated MySQL SQL before mutation:

```bash
php artisan test tests/Feature/MultiParent tests/Feature/PlatformAdmin
vendor/bin/pint --test
git diff --check
php artisan migrate --pretend
```

Then use this exact required order against the confirmed local database:

```bash
php artisan migrate
php artisan db:seed --class=OresamsubParentSeeder
php artisan multi-parent:backfill-oresamsub-foundation --dry-run
php artisan multi-parent:backfill-oresamsub-foundation --commit
php artisan multi-parent:backfill-oresamsub-foundation --commit
```

As an additional idempotency check, run the seeder a second time and confirm it creates no duplicate foundation records and does not print or rotate a temporary password:

```bash
php artisan db:seed --class=OresamsubParentSeeder
```

The 2026-08-08 dry-run and first commit both reported the same work, proving that the dry-run rolled its writes back:

| Entity | Dry-run | First commit | Second commit |
| --- | ---: | ---: | ---: |
| parents | 0 | 0 | 0 |
| affiliates | 4 | 4 | 0 |
| customer_plans | 0 | 0 | 0 |
| plans | 147 | 147 | 0 |
| prices | 882 | 882 | 0 |
| routes | 147 | 147 | 0 |
| transactions | 14 | 14 | 0 |
| audits | 1194 | 1194 | 0 |

The second commit reported zero mutations in every category. The second seeder run completed without creating another parent admin or printing/changing a temporary password.

## Read-only invariants

Run these queries with a read-only MySQL client after the second commit:

```sql
SELECT COUNT(*) AS oresamsub_parents
FROM parent_businesses WHERE slug = 'oresamsub';

SELECT COUNT(*) AS oresamsub_providers
FROM provider_connections WHERE slug = 'oresamsub';

SELECT COUNT(*) AS oresamsub_parent_provider_connections
FROM parent_provider_connections ppc
JOIN parent_businesses pb ON pb.id = ppc.parent_business_id
JOIN provider_connections pc ON pc.id = ppc.provider_connection_id
WHERE pb.slug = 'oresamsub' AND pc.slug = 'oresamsub';

SELECT COUNT(*) AS oresamsub_reseller_levels
FROM parent_reseller_levels prl
JOIN parent_businesses pb ON pb.id = prl.parent_business_id
WHERE pb.slug = 'oresamsub' AND prl.position BETWEEN 1 AND 6;

SELECT COUNT(*) AS affiliates_missing_ownership
FROM affiliates
WHERE parent_business_id IS NULL OR parent_reseller_level_id IS NULL;

SELECT COUNT(*) AS plans_missing_oresamsub_ownership_or_primary_route
FROM product_plans pp
LEFT JOIN parent_businesses pb ON pb.id = pp.parent_business_id
LEFT JOIN product_plan_provider_routes r
  ON r.product_plan_id = pp.id AND r.priority = 1 AND r.active = 1
WHERE pb.slug IS NULL OR pb.slug <> 'oresamsub' OR r.id IS NULL;

SELECT COUNT(*) AS transactions_missing_parent_when_source_plan_exists
FROM transactions t
JOIN affiliate_product_plans app ON app.id = t.affiliate_product_plan_id
JOIN product_plans pp ON pp.id = app.product_plan_id
WHERE t.parent_business_id IS NULL;

SELECT COUNT(*) AS active_customer_levels_above_6
FROM affiliate_user_plans aup
WHERE aup.visibility = 1 AND CAST(aup.plan_level AS UNSIGNED) > 6;

SELECT COUNT(*) AS cross_parent_prices
FROM product_plan_parent_prices p
JOIN product_plans pp ON pp.id = p.product_plan_id
JOIN parent_reseller_levels l ON l.id = p.parent_reseller_level_id
WHERE p.parent_business_id <> pp.parent_business_id
   OR p.parent_business_id <> l.parent_business_id;

SELECT COUNT(*) AS cross_parent_routes
FROM product_plan_provider_routes r
JOIN product_plans pp ON pp.id = r.product_plan_id
JOIN parent_provider_connections ppc ON ppc.id = r.parent_provider_connection_id
WHERE r.parent_business_id <> pp.parent_business_id
   OR r.parent_business_id <> ppc.parent_business_id;
```

Expected and observed counts were `1, 1, 1, 6, 0, 0, 0, 0, 0, 0`, in query order. Also verify the Laravel flags without writing:

```bash
php artisan tinker --execute="dump(config('parent_businesses.features'));"
```

All three flags must remain `false`: `ownership_reads`, `normalized_pricing`, and `provider_routing`.

## Rollback

Do not use migration rollback alone after a committed backfill: it cannot reconstruct the previous ownership data safely. Restore the pre-rehearsal dump instead.

1. Stop application/worker writes to the local database and copy the backup to a second safe location.
2. Set the exact verified artifact, verify its checksum, and export it for the guarded restore command:

```bash
REHEARSAL_BACKUP=/private/tmp/oresamsub-parent-foundation-pre-rehearsal-20260808.sql
test -s "$REHEARSAL_BACKUP"
shasum -a 256 "$REHEARSAL_BACKUP"
export REHEARSAL_BACKUP
```

3. First test the configured credentials and local-only guard without changing data:

```bash
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c=config("database.connections.mysql"); if(!app()->environment("local") || !in_array($c["host"],["127.0.0.1","localhost","::1"],true)){fwrite(STDERR,"Refusing non-local restore.\n"); exit(2);} DB::select("SELECT 1"); echo "Verified local target: ".$c["host"]."/".$c["database"].PHP_EOL;'
```

4. After visually confirming the printed local target, recreate only that configured local database and restore the verified dump. This is destructive to the named local database:

```bash
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c=config("database.connections.mysql"); $file=getenv("REHEARSAL_BACKUP"); if(!app()->environment("local") || !in_array($c["host"],["127.0.0.1","localhost","::1"],true) || !$file || !is_file($file) || filesize($file)===0 || !preg_match("/^[A-Za-z0-9_]+$/",$c["database"])){fwrite(STDERR,"Refusing unsafe restore.\n"); exit(2);} $base=["/Applications/XAMPP/xamppfiles/bin/mysql","--host=".$c["host"],"--port=".$c["port"],"--user=".$c["username"]]; $env=array_merge($_ENV,["MYSQL_PWD"=>(string)$c["password"]]); $run=function(array $cmd,array $spec)use($env){$p=proc_open($cmd,$spec,$pipes,null,$env); $error=stream_get_contents($pipes[2]); fclose($pipes[2]); $code=proc_close($p); if($code!==0){fwrite(STDERR,$error); exit($code);}}; $run([...$base,"--execute=DROP DATABASE `".$c["database"]."`; CREATE DATABASE `".$c["database"]."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"],[0=>["file","/dev/null","r"],1=>["file","php://stdout","w"],2=>["pipe","w"]]); $run([...$base,$c["database"]],[0=>["file",$file,"r"],1=>["file","php://stdout","w"],2=>["pipe","w"]]);'
```

5. Run `php artisan migrate:status` and application smoke checks. Keep the backup until the restored local state is verified.
