/* Sprywave — shared interactions: sticky header, mobile menu, dropdowns,
   scroll reveal, card hover, back-to-top. No dependencies. */
(function () {
  'use strict';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var root = document.body.firstElementChild;           // page wrapper div
  var header = root && root.querySelector(':scope > header');
  var nav = header && header.querySelector('nav');
  var sections = root ? Array.prototype.slice.call(root.querySelectorAll(':scope > section, :scope > article')) : [];

  /* ---------- Sticky header shrink ---------- */
  function onScroll() {
    var y = window.scrollY || document.documentElement.scrollTop;
    if (header) header.classList.toggle('is-scrolled', y > 24);
    if (toTop) toTop.classList.toggle('show', y > 700);
  }

  /* ---------- Mobile menu ---------- */
  if (nav) {
    var links = nav.children[1];
    if (links) {
      links.classList.add('nav-links');
      links.id = 'site-nav-links';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'nav-toggle';
      btn.setAttribute('aria-controls', 'site-nav-links');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Open menu');
      btn.innerHTML = '<span class="bar" aria-hidden="true"></span>';
      nav.appendChild(btn);
      var isMobile = function () { return window.matchMedia('(max-width: 960px)').matches; };
      var setOpen = function (open) {
        header.classList.toggle('nav-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('menu-locked', open);
      };
      btn.addEventListener('click', function () { setOpen(!header.classList.contains('nav-open')); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
      links.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var dd = a.parentElement && a.parentElement.classList.contains('sw-dd') ? a.parentElement : null;
        if (dd && isMobile()) {           // first tap on a dropdown title opens its submenu
          e.preventDefault();
          dd.classList.toggle('open');
          return;
        }
        if (isMobile()) setOpen(false);
      });
      window.addEventListener('resize', function () { if (!isMobile()) setOpen(false); });
    }
  }

  /* Desktop dropdowns: also open on click/keyboard for touch laptops */
  Array.prototype.forEach.call(document.querySelectorAll('.sw-dd > a'), function (a) {
    a.setAttribute('aria-expanded', 'false');
    var dd = a.parentElement;
    dd.addEventListener('mouseenter', function () { a.setAttribute('aria-expanded', 'true'); });
    dd.addEventListener('mouseleave', function () { a.setAttribute('aria-expanded', 'false'); dd.classList.remove('open'); });
  });
  document.addEventListener('click', function (e) {
    Array.prototype.forEach.call(document.querySelectorAll('.sw-dd.open'), function (dd) {
      if (!dd.contains(e.target) && !(window.matchMedia('(max-width: 960px)').matches)) dd.classList.remove('open');
    });
  });

  /* ---------- Back to top ---------- */
  var toTop = document.createElement('button');
  toTop.type = 'button';
  toTop.className = 'to-top';
  toTop.setAttribute('aria-label', 'Back to top');
  toTop.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
  toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });
  document.body.appendChild(toTop);

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Card detection: hover lift ---------- */
  function isCard(el) {
    if (el.hasAttribute('data-static') || el.querySelector('input, textarea, select') || el.closest('[hidden]')) return false;
    if (el.tagName === 'IMG' || el.tagName === 'BUTTON' || el.tagName === 'SUMMARY' || el.tagName === 'DETAILS' || el.tagName === 'FORM') return false;
    var cs = getComputedStyle(el);
    var r = parseFloat(cs.borderTopLeftRadius) || 0;
    if (r < 3 || r > 40) return false;                                  // skip pills and square blocks
    var bg = cs.backgroundColor;
    var hasBg = bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent';
    var hasShadow = cs.boxShadow && cs.boxShadow !== 'none';
    if (!hasBg && !hasShadow) return false;
    var p = el.parentElement && getComputedStyle(el.parentElement);
    if (!p || (p.display !== 'grid' && !(p.display === 'flex' && p.flexWrap === 'wrap'))) return false;
    return el.getBoundingClientRect().width < 760;
  }

  /* ---------- Scroll reveal ---------- */
  var revealEls = [];
  function mark(el, delay, variant) {
    if (!el || el.classList.contains('reveal') || el.closest('[hidden]')) return;
    el.classList.add('reveal');
    if (variant) el.classList.add(variant);
    if (delay) el.style.setProperty('--d', delay + 'ms');
    revealEls.push(el);
  }

  sections.forEach(function (sec, si) {
    var isHero = si === 0;
    if (isHero) {
      var hi = 0;
      Array.prototype.forEach.call(sec.querySelectorAll('h1, h1 ~ p, h1 ~ div, h1 ~ a, h1 ~ nav, p:first-child'), function (el) {
        if (el.closest('.hero-in')) return;
        el.classList.add('hero-in'); el.style.setProperty('--d', (hi++ * 110) + 'ms');
      });
      Array.prototype.forEach.call(sec.querySelectorAll('img'), function (img) {
        img.classList.add('hero-in-img'); img.style.setProperty('--d', '250ms');
      });
      return;
    }
    // headings, eyebrows and intro paragraphs
    Array.prototype.forEach.call(sec.querySelectorAll('h2, h2 + p, p[style*="letter-spacing: 0.14em"]'), function (el) {
      if (el.closest('.lift')) return;
      mark(el, 0);
    });
    // cards, staggered within their row
    var groups = new Map();
    Array.prototype.forEach.call(sec.querySelectorAll('div, a, article, li'), function (el) {
      if (!(el.classList.contains('card') || isCard(el)) || el.closest('.lift')) return;
      el.classList.add('lift');
      var par = el.parentElement;
      var i = groups.get(par) || 0; groups.set(par, i + 1);
      mark(el, Math.min(i * 90, 450));
    });
    // feature rows without backgrounds (icon + title + text) in grids
    Array.prototype.forEach.call(sec.querySelectorAll('div[style*="display: grid"] > div, ul > li'), function (el) {
      if (el.classList.contains('reveal') || el.closest('.lift') || el.closest('.reveal')) return;
      var par = el.parentElement;
      var i = groups.get(par) || 0; groups.set(par, i + 1);
      mark(el, Math.min(i * 80, 400));
    });
    // large photos: gentle side-slide + hover zoom
    Array.prototype.forEach.call(sec.querySelectorAll('img'), function (img) {
      var w = img.getBoundingClientRect().width;
      if (w < 240 || img.closest('.lift') || img.closest('a')) return;
      var wrap = img.parentElement;
      if (wrap && wrap.children.length === 1 && wrap.tagName === 'DIV') {
        wrap.classList.add('zoom-wrap');
        var rect = wrap.getBoundingClientRect(), mid = window.innerWidth / 2;
        mark(wrap, 100, rect.left + rect.width / 2 < mid ? 'reveal-left' : 'reveal-right');
      } else {
        mark(img, 100, 'reveal-fade');
      }
    });
    // anything else sizeable that is still unmarked (e.g. CTA panels, asides)
    Array.prototype.forEach.call(sec.querySelectorAll('[class*="sw-cta"], aside, form'), function (el) {
      if (!el.closest('.reveal')) mark(el, 0);
    });
  });

  if (reduce || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  }
})();
