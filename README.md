# Sprywave Technologies website (static export)

Plain HTML/CSS with a little vanilla JavaScript. No build step, no dependencies.

## Run it locally

Open `index.html` in a browser, or serve the folder (recommended, so links behave like the live site):

```bash
cd sprywave-website
python3 -m http.server 8080
# then visit http://localhost:8080
```

## Pages

| File | Page |
|---|---|
| index.html | Home |
| products.html | Products (SpryConnect, SpryDesk, GeoPulse Cloud, MISA ERP, FarmConnect, SpryERP, UnityLedger) |
| software-development.html, virtual-teams.html, call-center-solutions.html, erp-solutions.html, consulting-services.html | Services |
| about.html | About Us (includes the How We Work section) |
| contact.html | Contact Us |
| blog.html + blog-*.html | Blog index and four draft articles |
| privacy-policy.html, terms-of-use.html | Legal pages |

## Before going live

- **Contact form** (`contact.html` + `contact.php`): wired up and protected by Cloudflare Turnstile. See [DEPLOY.md](DEPLOY.md) for the server setup and deploy steps.
- **Newsletter forms** in every footer are not wired to anything yet.
- **Images** were cropped from screenshots of the current site. Replace files in `images/` with the original, full-resolution photos (keep the same file names) and swap `images/sw-logo.png` for the real logo, ideally as SVG.
- **Blog posts** are marked "Draft — review before publishing". Review, then remove that label.
- **Social links** in the footer point to `#`. Add your real profile URLs.
- Fonts load from Google Fonts (Manrope).

## Motion & interaction

All pages share `css/animations.css` and `js/site.js` (no libraries):
sticky header that shrinks on scroll, mobile menu (below 960px), animated dropdowns,
scroll-reveal for headings, cards and photos, hover lift on cards, button and arrow micro-interactions,
photo hover zoom, smooth scrolling and a back-to-top button.
Everything is switched off automatically for visitors who enable "reduce motion".
Content stays visible if JavaScript is disabled.
