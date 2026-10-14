# Fixing contact form email delivery

PHP's built-in `mail()` function only confirms the message was handed to the
server's local mail system — it does **not** confirm actual delivery.
Testing on the live site showed the form submitted successfully (Turnstile
passed, thank-you page showed) while `contact.php` still used `mail()`, but
no email ever arrived at `info@sprywave.com`. The fix: `contact.php` now
sends through an authenticated SMTP provider instead of `mail()`.

`contact.php` now sends through [PHPMailer](https://github.com/PHPMailer/PHPMailer)
over authenticated SMTP instead. PHPMailer's three source files are vendored
directly into the repo at `vendor/phpmailer/` (pinned to release `v7.1.1`,
downloaded straight from the official GitHub repo) — no Composer, no build
step, no change to the one-click deploy flow. They just need to be included
in the upload like any other file (see `DEPLOY.md`).

## 1. Pick an SMTP-capable mail provider

For testing right now, a **Mailtrap sandbox inbox** works well — it captures
mail instead of delivering it anywhere real, so nothing reaches
`info@sprywave.com` (or anyone else) while testing. For production, swap to:

| Option | Why |
|---|---|
| **A transactional provider** (Resend, Postmark, Mailgun, SendGrid, Amazon SES) | Built specifically for app-generated mail — best deliverability, free tiers available, dashboards show delivery/bounce status |
| **Google Workspace / Microsoft 365**, if `info@sprywave.com` is already hosted there | No new signup, but requires an app-specific password and sometimes has worse deliverability for automated mail than a dedicated provider |

## 2. Get these values from the provider's dashboard

For a Mailtrap sandbox inbox (Inboxes → your inbox → SMTP Settings):

| Env var | Mailtrap value |
|---|---|
| `SMTP_HOST` | `sandbox.smtp.mailtrap.io` |
| `SMTP_PORT` | `2525` |
| `SMTP_USER` | from the Mailtrap inbox's SMTP settings |
| `SMTP_PASS` | from the Mailtrap inbox's SMTP settings |
| `SMTP_ENCRYPTION` | `tls` |
| `SMTP_FROM` | `no-reply@sprywave.com` (Mailtrap sandbox accepts any from-address since nothing is actually delivered externally) |
| `SMTP_FROM_NAME` | `Sprywave Website` |

For a production provider, the same six env vars apply — just use that
provider's host/port/credentials instead, and the registered "from" address
it has verified for you (see step 3).

## 3. Authorize the sending domain (production only — skip for Mailtrap testing)

Once you move off Mailtrap to a real provider, it will ask you to add **SPF
and DKIM DNS records** for `sprywave.com`. Since Cloudflare sits in front of
the site, this is a DNS change in the Cloudflare dashboard — the provider's
setup page gives the exact TXT/CNAME records to add. This step is what stops
messages being silently dropped as unauthenticated spam, which is the likely
cause of the missing emails under the old `mail()`-based setup.

## 4. Set the six values as environment variables on the server

Same pattern as `TURNSTILE_SECRET_KEY` (see `DEPLOY.md`) — in the PHP-FPM
pool config (e.g. `/etc/php/8.x/fpm/pool.d/www.conf`), add:

```ini
env[SMTP_HOST] = <value>
env[SMTP_PORT] = <value>
env[SMTP_USER] = <value>
env[SMTP_PASS] = <value>
env[SMTP_ENCRYPTION] = <value>
env[SMTP_FROM] = <value>
env[SMTP_FROM_NAME] = <value>
```

Then:

```bash
sudo systemctl restart php8.x-fpm
```

Type real values directly into that server file — never in chat, a commit,
or any file that gets uploaded through the normal deploy.

If any of these aren't set, `contact.php` falls back to placeholder literals
that will make PHPMailer fail loudly (a 502 response with an on-page error),
rather than silently pretending to succeed the way `mail()` did.

## 5. Deploy and test

`contact.php` and the new `vendor/phpmailer/` directory both need to be part
of the upload — see the updated `DEPLOY.md`. Once deployed with the env vars
set, submit the live form and check the Mailtrap inbox (or the real
provider's dashboard) for the message — not `info@sprywave.com`, until
you've switched to a production provider.
