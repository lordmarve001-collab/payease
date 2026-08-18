# PayEase Go-Live Checklist

## Pre-Launch

### Infrastructure
- [ ] Production server provisioned (Ubuntu 22.04+, 4 vCPU, 8 GB RAM minimum)
- [ ] MySQL 8.0+ installed and tuned (`innodb_buffer_pool_size` = 70% RAM)
- [ ] Redis 7+ installed with password and persistence (AOF)
- [ ] Nginx installed with SSL (Let's Encrypt via Certbot)
- [ ] PHP 8.3+ installed with extensions: bcmath, ctype, curl, dom, gd, intl, json, mbstring, openssl, pdo_mysql, tokenizer, xml, redis, pcntl, posix
- [ ] Supervisor installed for Horizon queue workers
- [ ] S3-compatible bucket created for backups and uploads
- [ ] Firewall rules configured (22, 80, 443 only)

### Application
- [ ] `.env.production` created with all credentials from vault
- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_KEY` generated via `php artisan key:generate`
- [ ] Run zero-downtime deployment script: `deploy/deploy.sh`
- [ ] Database migrations have been tested and verified
- [ ] Storage link created: `php artisan storage:link`
- [ ] Supervisor configs installed from `deploy/supervisor/`
- [ ] Horizon config published and `deploy/supervisor/payease-horizon.conf` installed
- [ ] Scheduler supervisor config `deploy/supervisor/payease-scheduler.conf` installed
- [ ] Sentry DSN configured and test event sent
- [ ] Health endpoint (`/api/health`) verified working
- [ ] CORS configured for frontend domain

### External Services
- [ ] Monnify: API keys configured and connection tested
- [ ] Termii: API key configured and SMS tested
- [ ] Youverify: API key configured and BVN/NIN test passed
- [ ] Prembly: API key configured and identity check tested
- [ ] Africa's Talking: API key configured and SMS tested
- [ ] Mailgun/SES: SMTP credentials configured and email tested
- [ ] Sentry: Error and performance monitoring verified

### Security
- [ ] HTTPS only (no HTTP access)
- [ ] Rate limiting configured on sensitive endpoints
- [ ] `php artisan optimize` run with cache cleared
- [ ] Failed jobs notification setup
- [ ] Load testing completed (100+ concurrent users)
- [ ] Penetration test / vulnerability scan passed

## Rollback

- [ ] `deploy/rollback.sh` tested in staging

## Launch Day

- [ ] DNS A record updated to production server IP
- [ ] SSL certificate active and auto-renewal configured
- [ ] Supervisor started: `supervisorctl start payease-horizon`
- [ ] Queue worker verified: `php artisan horizon:status`
- [ ] Cron installed: `* * * * * php /var/www/payease/current/artisan schedule:run >> /dev/null 2>&1`
- [ ] First database backup triggered and uploaded to S3
- [ ] Monitoring dashboards reviewed (no errors)

## Post-Launch

- [ ] Real user monitoring (RUM) active in Sentry
- [ ] Backup schedule confirmed working
- [ ] Error alerts reaching on-call channel
- [ ] Traffic monitoring with alerts configured
- [ ] First week: daily health check reviews
