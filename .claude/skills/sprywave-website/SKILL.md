---
name: sprywave-website
description: Use when modifying, extending or reviewing the Sprywave Technologies static website (sprywave.com): adding pages, services, products or blog posts, editing copy or legal pages, styling, animations, nav/footer, testing or deploying.
---

# Sprywave website: how to change it safely

The Sprywave corporate site is a **plain static site**: hand-written HTML with inline styles, one shared stylesheet and one shared script, plus a single `contact.php` form handler. No framework, no build step, no dependencies. The old WordPress site is retired. Never copy markup, plugins or animations from it.

Before any change: ask for (or locate) the current project folder, and work on the files there. Never rebuild a page from memory.

## 1. Project structure

```
sprywave-website/
  index.html                     Home
  products.html                  Products (anchors: #spryconnect #sprydesk #geopulse #misa #farmconnect #spryerp #unityledger)
  software-development.html      Services (5 pages)
  virtual-teams.html
  call-center-solutions.html
  erp-solutions.html
  consulting-services.html
  about.html                     About Us (+ #how-we-work section)
  contact.html                   Contact form + details
  contact.php                    Form handler (PHPMailer over SMTP), the only server-side file
  blog.html                      Blog index
  blog-<slug>.html               Blog posts
  privacy-policy.html            Privacy Policy (Act 843)
  terms-of-use.html              Terms of Use (website only)
  css/animations.css             Shared motion, header sizing, mobile menu, hover states
  js/site.js                     Shared behaviour: sticky header, mobile menu, dropdowns, scroll reveal, back-to-top
  images/                        All images (flat folder, kebab-case names)
  README.md
```

Every page's `<head>` contains, in this order: charset, viewport, `<title>`, meta description, favicon, the `js` class script, the Google Fonts link for Manrope, a page `<style>` block (base + dropdown + page-specific background-image classes), then `css/animations.css?v=N` and `js/site.js?v=N` (defer). **Bump `?v=N` on every page whenever either shared file changes** so browsers don't serve stale copies.

Page body structure: `<body><div style="width: 100%; …">` → `<header>` (nav) → `<section>`s → `<footer>` → `</div>`. `site.js` relies on this structure (`body > div > header`, `body > div > section`), so keep it.

## 2. Brand and design tokens

Use these exact values. They come from the Sprywave design system.

| Token | Value | Use |
|---|---|---|
| blue (brand) | `#1a6dff` | Icons, accents. Not for small text or button fills |
| blue-strong | `#1557d6` | Primary buttons, blue cards, links/eyebrows on light grounds |
| blue-hover | `#1048b3` | Primary button hover |
| blue-bright | `#4d8dff` | Blue text and accents on black |
| blue-tint | `#eef4ff` | Alternating light section background |
| card-tint | `#f4f7fc` / `#e3edff` | Quiet cards, FAQ rows |
| black | `#000000` | Header, hero, footer, dark cards, CTA panels |
| raised dark | `#14161c`, border `#2a2e38` | Panels on black |
| ink | `#0b0d12` | Text on light |
| ink-muted | `#4a5160` | Body/secondary text on light |
| ink-inverse-muted | `#a9afbb` | Secondary text on black |
| border | `#d0d6e2` | Hairlines on light |

- **Font**: Manrope (Google Fonts, weights 300/400/600/700), fallback `"Plus Jakarta Sans", system-ui, sans-serif`.
- **Type scale**: hero h1 52–56px (300 weight on the home hero, 600 elsewhere); section h2 40px/48px 700; card h3 17–22px 600–700; lead 16–17px/26–28px; body 15px/24px; eyebrow 12px 700 uppercase, letter-spacing .14em. Never set readable text below 13px.
- **Spacing**: sections `padding: 96px 16px`; content `max-width: 1120px; margin: 0 auto`; card padding 24–32px; grid gaps 16–20px.
- **Shape**: cards radius 4–6px, photos 8px, CTA panels 12px, buttons and inputs `999px` (pills).
- **Shadow**: light cards `0 6px 24px rgba(11, 13, 18, 0.10)`.
- **Section rhythm**: alternate black, white and `#eef4ff`. Never put two `#eef4ff` sections next to each other.
- **Contrast**: white text only on `#1557d6` or black, never on `#1a6dff`. Blue text on black uses `#4d8dff`.

## 3. Reusable patterns (copy from existing pages)

Copy the closest existing section and edit its content. Don't invent new styles. The source for each pattern:

- **Primary button**: `<a href="…" style="display: inline-flex; align-items: center; min-height: 48px; padding: 0 28px; border-radius: 999px; background: #1557d6; color: #ffffff; text-decoration: none; font-size: 12px; font-weight: 700; letter-spacing: 0.06em">LABEL</a>`. Outline versions use `border: 1.5px solid #ffffff` (on dark) or `#0b0d12` (on light). Labels are UPPERCASE, two words or fewer.
- **Hero (split, text + photo)**: any service page.
- **White feature cards with icon**: index.html "Why Choose".
- **Alternating blue/black cards**: industry and benefit grids (erp-solutions.html, about.html).
- **Numbered process steps on dark**: call-center-solutions.html, erp-solutions.html.
- **Photo + content split**: most pages. Alternate the photo side between sections.
- **Product section**: products.html. Shows status tag, category, lead, "The problem it solves" and "Key capabilities".
- **FAQ accordion**: virtual-teams.html, using native `<details class="faq">`. No JS needed.
- **CTA banner with photo**: bottom of every page. The background photo is set by a `.sw-cta-*` class in the page `<style>` block, never in an inline `style` attribute.
- **Icons**: inline stroke SVG (`stroke="#1a6dff"`, width 1.3–1.5, `aria-hidden="true"`). No emoji, no icon fonts. Don't redraw other companies' logos.

## 4. Header and footer: keep every page in sync

The header (nav) and footer are **duplicated in every HTML file**. Any change to them must be applied to **all** pages. Make the change in one page, then propagate it with a script or careful find-and-replace, and verify by grepping.

Nav order: logo (→ index.html) · Products ▾ · Services ▾ · How we work (→ about.html#how-we-work) · About · Blog · TALK TO US (→ contact.html).
- Products ▾: All products, SpryConnect, SpryDesk, GeoPulse Cloud, MISA ERP, FarmConnect, SpryERP · Soon, UnityLedger · Soon (each → products.html#anchor).
- Services ▾: Software Development, Virtual Teams, Call Center Solutions, ERP Solutions, Consulting Services.
- The current page's nav item or dropdown entry is coloured `#4d8dff` and has `aria-current="page"`.
- Footer: newsletter form ("By subscribing, you agree to our Privacy Policy" links to privacy-policy.html), Quick Links (Home, About, Blog, Contact Us, Privacy Policy → privacy-policy.html, Terms of Use → terms-of-use.html, Cookies Settings), Services list, `©<current year> Sprywave Technologies. All rights reserved.`
- Use the name "Terms of Use", never "Terms of Service", for the website terms.

## 5. Motion and interaction rules

All motion lives in `css/animations.css` and `js/site.js`. Pages do **not** need extra markup for most effects:
- **Scroll reveal** is applied automatically by `site.js` to h2s, intro paragraphs, eyebrows, grid children, cards and large photos. The first section (hero) gets a load-in animation instead.
- **Card hover lift**: add `class="card"` to any card element (div/a/article with a background or shadow and radius 4–8px). Don't add it to wide panels, forms or CTA banners. Add `data-static` to any boxed element that must never lift (e.g. legal page TOC and reading card).
- **Buttons**: pill buttons get hover effects automatically through attribute selectors on their inline styles. Keep the exact inline style strings from section 3, or hover won't apply.
- **Mobile menu**: generated by `site.js` below 960px wide from the second child of `header nav`. Keep the nav markup shape (logo link, then one `div` of links).
- Don't add animation libraries (AOS, GSAP, jQuery). Extend the two shared files instead.
- Everything must respect `prefers-reduced-motion` (no movement, keep colour/shadow feedback), and content must stay visible if JS fails. Hide things only under `.js` selectors.

## 6. Common tasks

### Add a service page
1. Copy the most similar service page and rename it in kebab-case.
2. Update `<title>`, meta description, hero, sections, CTA background class and image paths.
3. Mark the page active in its own nav (Services colour + dropdown entry).
4. Add the page to the Services dropdown **and** footer Services list on **every** page.
5. If the service maps to a Sprywave product, add the "Our own … platform" product box above the CTA (see call-center-solutions.html).

### Add or update a product
1. Edit `products.html`: add a `<section id="slug">` following the existing product pattern (alternate white/black backgrounds), plus a jump chip in the hero.
2. Add it to the Products dropdown on every page and, if live, to the "Our Platforms" cards on index.html.
3. Status tags: `LIVE` (solid blue) or `IN DEVELOPMENT` (outline). Coming-soon products go in the "On the roadmap" section with a "Get notified at launch" button.
4. External product links open in a new tab (`target="_blank" rel="noopener"`) and use the product's real domain. Avoid raw cloud-run or test URLs.

### Add a blog post
1. Copy an existing `blog-*.html`. Name it `blog-<keyword-slug>.html`.
2. Update title, meta description, tag, headline, excerpt, author, date, reading time (≈ words ÷ 220), featured image, body and the related-product box.
3. Add a card to `blog.html`. The newest post becomes the featured one, and the previous featured post moves into the grid.
4. Update the "More from the blog" cards on the other posts if relevant.
5. Remove the "DRAFT — REVIEW BEFORE PUBLISHING" label only after a human at Sprywave has reviewed the post.

### Update the Privacy Policy or Terms of Use
1. Layout (keep it): black header with "← Back to Home", eyebrow, h1, then the "Last updated" line; below it, an "On this page" table of contents next to a white reading card with numbered `<section id="…">` blocks; a contact block at the end, with a link across to the other legal page. The TOC `<nav>` and the reading `<article>` carry `data-static` so they don't lift on hover.
2. Date line format: **`Last updated: 14 October 2026`** (full day month year, label "Last updated", never "Effective date"). When the wording changes materially, set it to the actual date of the change. Never backdate. Each page's "Changes to…" section refers to "the "Last updated" date at the top of this page", so keep the two consistent.
3. Scope: the Privacy Policy covers website, enquiry, newsletter and client-relationship data. Sprywave is the **data controller** there and the **data processor** for client data inside its platforms (SpryConnect, SpryDesk, GeoPulse Cloud, MISA ERP), which have their own product privacy notices. The Terms of Use cover the website only; services and products are governed by separate agreements.
4. Keep it aligned with Ghana's Data Protection Act, 2012 (Act 843). Don't add a Data Protection Commission registration number unless Sprywave provides a real one (it was removed for now).
5. Retention periods currently stated: up to 24 months for enquiries that don't lead to a contract, up to 90 days for server/security logs, newsletter until unsubscribe, contract records as long as tax law requires. Change them only if Sprywave's actual practice changes.
6. Any new legal wording is a draft until Sprywave confirms it. Flag substantive legal changes for human or legal review rather than presenting them as final, and never invent registrations, certifications or legal obligations.
7. If a new data use is introduced (e.g. analytics, a new form), update the Privacy Policy sections on what is collected, why, cookies and retention in the same change.

### Change colours, type or spacing site-wide
These values are inline on every page, so use a scripted find-and-replace across all `*.html` files, then spot-check pages. Header sizing and all motion live in `css/animations.css`.

## 7. Content and copy rules

- Voice: Sprywave is "we", the reader is "you / your business". Title Case headlines, no emoji, no trailing colons on titles.
- Be concrete and Ghana-relevant (MTN, Telecel, AT networks; mobile money; Act 843 for data protection). Avoid filler like "cutting-edge" and "seamless" where a specific benefit can be stated.
- **Never invent** statistics, client names, testimonials, certifications or partnerships. Use `[PLACEHOLDER]` text when facts are missing and ask.
- Third-party platforms (Salesforce, Zendesk, HubSpot and so on) appear only as plain-text integrations or advisory options, never as "our tools" or with their logos, unless Sprywave has a formal partnership.
- Each product description must say who it's for (e.g. MISA ERP = schools; SpryERP = growing businesses) to avoid confusing the two.
- Contact details: +233 030 25 51 083 (`tel:+233302551083`), info@sprywave.com, Accra, Ghana. Use the same details on the legal pages. (GeoPulse's own policies use enquiry@sprywave.com; if Sprywave standardises on one address for data protection requests, update both sites.)
- Footer year must be the current year.

## 8. Accessibility checklist

- Real `<a href>` and `<button>` elements, never clickable divs. Icon-only buttons have `aria-label`.
- Every `<img>` has meaningful `alt`, or `alt=""` if decorative. Decorative SVGs have `aria-hidden="true"`.
- Form inputs have `<label for>`. Required fields are marked with *.
- Touch targets are at least 44px tall. Text contrast is at least 4.5:1 (see section 2).

## 9. Test before handing over

```bash
cd sprywave-website && python3 -m http.server 8080   # run from INSIDE the project folder, then hard-refresh
```
Check at 1440px, about 1100px and 390px wide:
- Nav fits on one line on desktop. The mobile menu opens, submenus expand, and Escape closes it.
- No horizontal scroll on mobile (`document.documentElement.scrollWidth === innerWidth`).
- Cards lift on hover. Sections reveal on scroll. With OS "Reduce motion" on, nothing moves but everything is visible.
- No console errors.
- No broken internal links or images:
```bash
grep -oh 'href="[^"#:]*\.html' *.html | sort -u | sed 's/href="//' | while read f; do [ -f "$f" ] || echo "MISSING PAGE $f"; done
grep -oh 'images/[^")]*' *.html | sort -u | while read f; do [ -f "$f" ] || echo "MISSING IMAGE $f"; done
```

## 10. Deployment (Spry Deploy) and redirects

**How changes go live.** The site is deployed with **Spry Deploy**, an in-house Node.js web UI that runs locally. Select the SpryWave project and click **Deploy**: it `rsync`s the local project folder straight into the web root on the VPS (Nginx, with Cloudflare in front). Files are live within seconds.

- There are **no release folders and no rollback**. Whatever is in the local folder at deploy time is what the public sees. Use Git as the safety net: **commit before every deploy**, so a bad deploy can be undone by checking out the previous commit and deploying again.
- Before deploying: run the link and image checks in section 9; bump `?v=N` on every page if `css/animations.css` or `js/site.js` changed; check the changed pages locally at desktop and mobile widths.
- After deploying: hard-refresh the live site and spot-check the changed pages. If an old version still shows, purge the Cloudflare cache.
- Never deploy internal files. `.claude/`, `CLAUDE.md`, `README.md`, `.git/`, `.env` and any credentials must be excluded from the sync (or blocked by Nginx), so they're never publicly downloadable.

**Contact form.** `contact.php` is the only server-side file. It handles contact form submissions using a vendored copy of PHPMailer, sending over SMTP.
- It is currently configured with the **Mailtrap sandbox**, so messages are captured for testing and **not delivered**. Switching to production SMTP is an outstanding task.
- Keep SMTP credentials out of the HTML and out of Git (environment variables or a config file outside the web root, or at least blocked by Nginx). Never print credentials in chat or commit messages.
- When changing the form fields in `contact.html`, update `contact.php` to match, and update the Privacy Policy if new kinds of data are collected.

**Redirects.** Old WordPress URLs (`/about-us/`, `/contact-us/`, `/services/...`, `/privacy-policy/`, `/terms-of-service/` and similar) are 301-redirected to the new `.html` pages in the Nginx server block for sprywave.com, with a catch-all that sends `/slug/` to `/slug.html` when that page exists. WordPress system paths (`/wp-admin`, `/wp-login.php`, `/wp-content` and so on) return 410.
- If a page is **renamed or removed**, add a `location ~ ^/old-path/?$ { return 301 /new-page.html; }` rule so existing links and search rankings carry over. Never just delete a published page without a redirect.
- Test any Nginx change with `sudo nginx -t` before `sudo systemctl reload nginx`, then check with `curl -sI https://sprywave.com/old-path/`.

**Outstanding items:** switch `contact.php` from Mailtrap to production SMTP; wire up the newsletter forms; replace screenshot-cropped images in `images/` with full-resolution originals (same file names); replace `images/sw-logo.png` with the real logo SVG; add real social links; review the blog drafts; connect "Cookies Settings" or remove it if no consent tool is used; add `sitemap.xml` and `robots.txt` and submit the sitemap in Google Search Console.
