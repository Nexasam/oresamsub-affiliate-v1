# Namecheap production deployment

Pushing to `main` triggers `.github/workflows/deploy-namecheap.yml`. The workflow
builds the Laravel application on GitHub Actions and synchronizes the resulting
production package to:

```text
/home/emiprbyj/affiliate.emiplug.com
```

The domain document root must remain:

```text
/home/emiprbyj/affiliate.emiplug.com/public
```

## One-time SSH setup

Generate a dedicated deployment key locally. Do not add a passphrase because
GitHub Actions must use it unattended:

```bash
ssh-keygen -t ed25519 -C "github-actions-namecheap" -f namecheap_deploy
```

Add the contents of `namecheap_deploy.pub` to the Namecheap account's
`~/.ssh/authorized_keys`. This can be done through cPanel's **SSH Access →
Manage SSH Keys** interface by importing and authorizing the public key.

Test the key before enabling automatic deployments:

```bash
ssh -i namecheap_deploy -p 21098 emiprbyj@YOUR_NAMECHEAP_HOST
```

## GitHub production secrets

In the GitHub repository, open **Settings → Environments** and create an
environment named `production`. Add these environment secrets:

| Secret | Value |
| --- | --- |
| `NAMECHEAP_HOST` | The external Namecheap SSH hostname from cPanel or the welcome email |
| `NAMECHEAP_SSH_PRIVATE_KEY` | The complete contents of the private `namecheap_deploy` file |

Never store the server password, private key, or production `.env` in Git.

Optionally restrict the `production` environment so that only the `main` branch
can deploy.

## First deployment

Before the first automated run, create the production `.env` at:

```text
/home/emiprbyj/affiliate.emiplug.com/.env
```

The deployment deliberately preserves `.env` and `storage/`. It uploads the
Composer `vendor/` directory and compiled `public/build/` assets, so Composer
and Node.js are not required on Namecheap.

After adding the secrets, run **Actions → Deploy to Namecheap → Run workflow**.
Subsequent pushes to `main` deploy automatically.

## Scheduler limitation

Namecheap shared hosting restricts cron frequency. Configure the closest
permitted cPanel cron interval:

```cron
*/5 * * * * cd /home/emiprbyj/affiliate.emiplug.com && php artisan schedule:run >/dev/null 2>&1
```

Commands scheduled every 30 seconds or every minute will not achieve their
intended frequency on this shared-hosting plan. Move those workloads to an
external scheduler or a server that supports persistent workers if that timing
is operationally required.
