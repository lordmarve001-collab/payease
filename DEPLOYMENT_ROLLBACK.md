# PayEase Deployment & Rollback Runbook

## CI/CD Pipeline

Deployment is handled by `deploy.sh`. It uses a zero-downtime symlink swap strategy.

### Deployment Steps

1. Push to `main` branch triggers GitHub Actions / GitLab CI
2. Pipeline runs: lint → tests → build assets → code quality
3. If all checks pass, `deploy.sh` runs on production:
   - Clones fresh release into `releases/<timestamp>/`
   - Symlinks shared `.env`, `storage`, `public/uploads`
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci && npm run build`
   - `php artisan config:cache && route:cache && view:cache && event:cache`
   - `php artisan migrate --force`
   - Swaps `current` symlink to new release
   - `php artisan horizon:terminate`
   - Reloads PHP-FPM
   - Prunes old releases (keeps 5)

### Manual Deployment

```bash
ssh deploy@your-server
cd /var/www/payease
sudo -u deploy bash deploy.sh
```

---

## Rollback Procedure

### Rolling Back a Release

1. SSH into the production server:
   ```bash
   ssh deploy@your-server
   ```

2. List available releases:
   ```bash
   ls -lt /var/www/payease/releases/
   ```

3. Rollback to a specific release:
   ```bash
   cd /var/www/payease
   ln -sfn releases/20250101120000 current
   ```

4. Run any DOWN migrations (if applicable):
   ```bash
   cd current
   php artisan migrate:rollback --step=1 --force
   ```

5. Restart Horizon:
   ```bash
   php artisan horizon:terminate
   ```

6. Reload PHP-FPM:
   ```bash
   sudo systemctl reload php8.3-fpm
   ```

7. Verify health:
   ```bash
   curl https://yourdomain.com/up
   curl https://yourdomain.com/api/health
   ```

### Rolling Back the Database

If a migration causes data loss:

1. Restore the latest backup:
   ```bash
   php artisan payease:restore-database --file=s3://backups/payease_backup_20250101_020000.sql.gz
   ```

2. Point the `current` symlink to the release that matches that backup.

3. Verify the application is functional.

### Emergency Fix

When a hotfix must bypass CI:

1. Create a hotfix branch, commit the fix.
2. Push and create a PR; get it reviewed and merged.
3. The CI pipeline will deploy automatically.
4. If CI is broken, run `deploy.sh` manually from the target branch.

---

## Monitoring & Alerts

- **Health endpoint**: `GET /api/health` — monitored via UptimeRobot/Pingdom every minute
- **Sentry**: All errors ≥ Warning level trigger Slack notifications
- **Horizon**: Supervisor auto-restarts workers; health check via `php artisan horizon:status`
- **Queue failures**: `php artisan queue:failed` alerts via Sentry
- **Backup**: `payease:backup-database` runs daily at 03:00 UTC; failure sends email alert

---

## Post-Deployment Verification Checklist

- [ ] `curl https://yourdomain.com/up` returns `{"status": "healthy"}`
- [ ] `curl https://yourdomain.com/api/health` shows all green checks
- [ ] `php artisan horizon:status` reports "Horizon is running"
- [ ] `php artisan queue:failed` is empty
- [ ] Sentry dashboard shows no new errors
- [ ] Logs: `tail -f /var/log/payease/*.log` shows no anomalies
- [ ] Smoke test: register a user, fund wallet, view balance, initiate payout
