# Namecheap production deployment

Namecheap shared hosting pulls updates from GitHub instead of accepting inbound
SSH from GitHub-hosted runners.

```text
Push main to GitHub
→ GitHub Actions validates the build and tests
→ Namecheap cron pulls main within five minutes
→ Laravel migrations and caches are finalized
```

The application path is:

```text
/home/emiprbyj/affiliate.emiplug.com
```

The domain document root must remain:

```text
/home/emiprbyj/affiliate.emiplug.com/public
```

## Production safety

The pull deployment preserves `.env`, `storage/`, `.well-known`, and untracked
uploads. It only permits fast-forward updates and uses `flock` to prevent two
deployments from running at once.

If `composer.json` or `composer.lock` changes, the deployment requires Composer
and stops before changing production when Composer is unavailable.

Never commit private keys, passwords, or production `.env` files.

## Cron setup

After pulling the script onto Namecheap, make it executable:

```bash
chmod 750 /home/emiprbyj/affiliate.emiplug.com/scripts/deploy-namecheap.sh
```

Add this cPanel cron job:

```cron
*/5 * * * * /bin/bash /home/emiprbyj/affiliate.emiplug.com/scripts/deploy-namecheap.sh >> /home/emiprbyj/affiliate.emiplug.com/storage/logs/deploy.log 2>&1
```

The application scheduler remains a separate cron job:

```cron
*/5 * * * * cd /home/emiprbyj/affiliate.emiplug.com && php artisan schedule:run >/dev/null 2>&1
```

Namecheap shared hosting does not permit the scheduler frequency required by
commands configured for every 30 seconds or every minute. Those workloads need
an external scheduler or a server with persistent worker support when exact
timing is required.
