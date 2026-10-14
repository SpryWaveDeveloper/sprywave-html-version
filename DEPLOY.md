# Deploying the website

Runbook for pushing the whole Sprywave site — all HTML pages, `css/`, `js/`,
`images/`, and the contact form backend (`contact.php`) — live on the VPS
(Nginx + PHP-FPM, self-hosted, behind Cloudflare). Steps 1–2 cover the site
in general; steps 3–7 are specific to the contact form and Turnstile. Follow
it in order the first time; later deploys only need steps 1, 2 and 10.

## 0. Before you start

- SSH access to the VPS.
- The Turnstile **secret key** from the Cloudflare dashboard, for site key
  `0x4AAAAAAFWbZEdtpNRkQa4b`. Have it ready to paste directly into the server
  config in step 5 — don't put it in a commit, a chat, or any file that gets
  uploaded.
- Confirm in the Cloudflare Turnstile dashboard that the widget's allowed
  domains include `sprywave.com` and `www.sprywave.com` (or whatever
  `TURNSTILE_HOSTNAMES` you plan to set in step 5).
- SMTP credentials for sending mail (Mailtrap for testing, or a production
  provider) — see `EMAIL-SETUP.md`. Same rule: real values go directly into
  the server config in step 5, never in chat or a committed file.

## 1. Upload the whole site

```bash
rsync -avz --delete \
  *.html css/ js/ images/ contact.php vendor/ \
  you@your-vps:/var/www/sprywave/
```

`vendor/phpmailer/` holds the three PHPMailer source files `contact.php`
requires for SMTP sending — it's committed to the repo (not installed via
Composer), so it's just another directory to upload like `css/` or `images/`.

(Adjust the remote path to the actual web root; it's whatever directory
already holds `index.html` on the server. `--delete` removes files on the
server that no longer exist locally — drop it if you'd rather add/update
only.)

## 2. Bump cache-busting versions if CSS/JS changed

If this deploy includes changes to `css/animations.css` or `js/site.js`,
confirm every page's `?v=N` query string was bumped before step 1 (per the
site's own convention — see `README.md`), otherwise visitors behind
Cloudflare or their own browser cache may keep serving the old files.

## 3. Confirm Nginx hands `.php` requests to PHP-FPM

```bash
ssh you@your-vps
sudo nginx -T | grep -A5 'location ~ \\.php'
```

You should see a block like:

```nginx
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.x-fpm.sock;
}
```

If it's missing, `contact.php` will be served as plain text instead of
executed — stop and fix the Nginx server block before continuing.

## 4. Find the PHP-FPM pool config

```bash
php -v                                   # confirm the PHP version
find /etc/php -name "*.conf" -path "*pool.d*"
```

Typically `/etc/php/8.x/fpm/pool.d/www.conf`.

## 5. Set the environment variables

Edit that pool file and add:

```ini
env[TURNSTILE_SECRET_KEY] = <paste the real secret key here, directly on the server>
; only needed if the site is served from a domain other than these two:
; env[TURNSTILE_HOSTNAMES] = sprywave.com,www.sprywave.com

env[SMTP_HOST] = <value — see EMAIL-SETUP.md>
env[SMTP_PORT] = <value>
env[SMTP_USER] = <value>
env[SMTP_PASS] = <value>
env[SMTP_ENCRYPTION] = <value>
env[SMTP_FROM] = <value>
env[SMTP_FROM_NAME] = <value>
```

Save, then restart PHP-FPM so it picks up the new environment:

```bash
sudo systemctl restart php8.x-fpm
```

## 6. Verify the PHP runtime prerequisites

```bash
php -i | grep allow_url_fopen      # must say "On" — contact.php needs it to call Cloudflare's siteverify endpoint
curl -sI https://challenges.cloudflare.com/turnstile/v0/siteverify | head -1   # confirm outbound HTTPS isn't blocked
```

## 7. Verify SMTP connectivity

```bash
curl -v telnet://$SMTP_HOST:$SMTP_PORT   # confirm the server can even reach the SMTP host/port (Ctrl+C once connected)
```

A hang or "connection refused" here usually means an outbound firewall rule
is blocking that port — fix that before testing the form itself. Actual
send/auth success is easiest to confirm via step 9's live form test, since
`contact.php` will report a clear 502 + on-page error if PHPMailer can't
connect or authenticate.

## 8. Purge the Cloudflare cache

```bash
# Cloudflare dashboard → Caching → Configuration → Purge Everything
# (or Purge by URL for just the pages that changed, e.g. /contact.html,
# plus any page whose css/js version was bumped in step 2)
```

Needed so visitors don't get stale cached pages or a copy of `contact.html`
missing the Turnstile widget.

## 9. End-to-end test on the live domain

1. Visit `https://sprywave.com/contact.html` in a real browser (not
   localhost — Turnstile's hostname check will reject anything else).
2. Fill the form, complete the Turnstile challenge, submit.
3. Confirm the on-page thank-you panel appears, **and** check the inbox the
   `SMTP_*` env vars actually point at (the Mailtrap sandbox inbox during
   testing, or the production provider's dashboard/the real mailbox once
   switched over) — don't assume delivery from the thank-you panel alone.
4. Submit again without refreshing the widget (e.g. via browser dev tools,
   replaying the same request) and confirm it's rejected — Turnstile tokens
   are single-use, so a replay must fail.

## 10. Routine redeploys

Once the server is set up, later changes only need step 1 (`rsync` the whole
site), step 2 if CSS/JS changed, and step 8 (cache purge). The environment
variables from step 5 persist across file redeploys and don't need to be
re-set unless the PHP-FPM pool config itself changes.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| "Verification failed. Please try again." on every submit | `TURNSTILE_SECRET_KEY` not set, wrong value, or PHP-FPM wasn't restarted after step 5 |
| Same error, but only when testing locally/off-domain | Expected — Turnstile's `hostname` check only passes on an allowed domain |
| Form submits, thank-you page shows, but no email ever appears anywhere | `SMTP_*` env vars not set/wrong, or PHP-FPM wasn't restarted after step 5 — check step 7 |
| 502 + on-page error after submitting | PHPMailer couldn't connect or authenticate to the SMTP host — recheck the `SMTP_*` values and step 7's connectivity test |
| `contact.php` downloads as a file instead of running | Nginx isn't routing `.php` to PHP-FPM — see step 3 |
| Site shows old content/styling after deploy | Cache not purged (step 8) or `?v=N` not bumped (step 2) |
