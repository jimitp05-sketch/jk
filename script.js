/* ================================================
   AI APOLLO 2.1 — MASTER SCRIPT (UPGRADED)
   v2.1.1 — 2026-04-08
   ================================================ */

// ── GLOBAL UTILITIES (Available immediately for page-specific scripts) ──

// 1. SCROLL REVEAL OBSERVER
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.01, rootMargin: '0px 0px 50px 0px' });

window.revealNew = function () {
    document.querySelectorAll('.reveal:not(.visible)').forEach(el => revealObs.observe(el));
};

// 2. COUNTER ANIMATION
function animateCountEl(el) {
    const useTarget = el.dataset.target !== undefined;
    const target = parseInt(useTarget ? el.dataset.target : el.dataset.count, 10);
    const suffix = el.dataset.suffix || '';
    if (isNaN(target)) return;
    const step = Math.ceil(target / 80);
    let current = 0;
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString() + suffix;
        if (current >= target) clearInterval(timer);
    }, 20);
}

const counterObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
            entry.target.dataset.counted = '1';
            animateCountEl(entry.target);
        }
    });
}, { threshold: 0.5 });

// ── DOM CONTENT LOADED ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('navbar');

    // ── 1. SCROLL PROGRESS BAR ───────────────────────
    const progressBar = document.createElement('div');
    progressBar.id = 'scroll-progress';
    progressBar.style.cssText = `
    position:fixed;top:0;left:0;height:3px;width:0%;z-index:9999;
    background:linear-gradient(90deg,#ff4b2b,#ff416c);
    transition:width .1s linear;pointer-events:none;
  `;
    document.body.prepend(progressBar);

    // ── 2. SCROLL EFFECTS ────────────────────────────
    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        const total = document.body.scrollHeight - window.innerHeight;
        progressBar.style.width = (scrolled / total * 100) + '%';
        if (scrolled > 50) navbar.classList.add('scrolled');
        else navbar.classList.remove('scrolled');
    }, { passive: true });

    // ── 3. RUN INITIAL REVEAL ────────────────────────
    window.revealNew();
    document.querySelectorAll('[data-count], [data-target]').forEach(el => counterObs.observe(el));

    // Fallback for file:// protocol or slow loads
    setTimeout(() => {
        document.querySelectorAll('.reveal:not(.visible)').forEach(el => el.classList.add('visible'));
    }, 2000);

    // ── 5. HERO WORD STAGGER ─────────────────────────
    const heroH1 = document.querySelector('.hero h1');
    if (heroH1) {
        const nodes = Array.from(heroH1.childNodes);
        let delay = 0.3;
        heroH1.innerHTML = '';
        nodes.forEach(node => {
            if (node.nodeType === 3) {
                node.textContent.split(' ').filter(w => w.trim()).forEach(word => {
                    const span = document.createElement('span');
                    span.className = 'hero-word';
                    span.textContent = word + ' ';
                    span.style.animationDelay = delay + 's';
                    delay += 0.12;
                    heroH1.appendChild(span);
                });
            } else {
                const el = node.cloneNode(true);
                if (el.nodeType === 1) {
                    el.classList.add('hero-word');
                    el.style.animationDelay = delay + 's';
                    delay += 0.12;
                }
                heroH1.appendChild(el);
            }
        });
    }

    // ── 6. ECG SVG ───────────────────────────────────
    const hero = document.querySelector('.hero');
    if (hero) {
        const ecgWrap = document.createElement('div');
        ecgWrap.className = 'hero-ecg';
        const ecgPath = 'M0,25 L40,25 L50,5 L60,45 L70,5 L80,45 L90,15 L100,25 L200,25 L240,25 L250,5 L260,45 L270,5 L280,45 L290,15 L300,25 L400,25 L440,25 L450,5 L460,45 L470,5 L480,45 L490,15 L500,25 L600,25 L640,25 L650,5 L660,45 L670,5 L680,45 L690,15 L700,25 L800,25 L840,25 L850,5 L860,45 L870,5 L880,45 L890,15 L900,25 L1000,25 L1100,25 L1200,25 L1440,25';
        ecgWrap.innerHTML = `
      <svg viewBox="0 0 1440 50" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path class="ecg-line" d="${ecgPath}"/>
      </svg>`;
        hero.appendChild(ecgWrap);
    }

    // ── 7. CARD 3D TILT ──────────────────────────────
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!prefersReduced) {
        document.querySelectorAll('.glass-card, .myth-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const r = card.getBoundingClientRect();
                const rotX = ((e.clientY - r.top) - r.height / 2) / 22;
                const rotY = (r.width / 2 - (e.clientX - r.left)) / 22;
                card.style.transform = `perspective(900px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateY(-6px)`;
                card.style.transition = 'transform 0.08s ease';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                card.style.transition = 'transform 0.4s cubic-bezier(0.22,1,0.36,1)';
            });
        });

        const heroImg = document.querySelector('.hero-image-wrapper');
        document.addEventListener('mousemove', e => {
            if (!heroImg) return;
            const x = (e.clientX / window.innerWidth - 0.5) * 14;
            const y = (e.clientY / window.innerHeight - 0.5) * 8;
            heroImg.style.transform = `translate(${x * 0.5}px, ${y * 0.5}px)`;
            document.querySelectorAll('.hero-float-badge').forEach((b, i) => {
                const d = i === 0 ? 1.5 : 1;
                b.style.transform = `translate(${x * d}px, ${y * d}px)`;
            });
        }, { passive: true });
    }

    // ── 8. MOBILE NAV ────────────────────────────────
    const navToggle = document.getElementById('nav-toggle');
    const navMobile = document.getElementById('nav-mobile');
    if (navToggle && navMobile) {
        navToggle.addEventListener('click', () => {
            const isOpen = navMobile.classList.toggle('open');
            navToggle.classList.toggle('open', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });
        navMobile.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMobile.classList.remove('open');
                navToggle.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });
    }

    // ── 9. FAQ ACCORDION ─────────────────────────────
    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.faq-item');
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item.open').forEach(open => {
                open.classList.remove('open');
                open.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            });
            if (!isOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // ── 11. ACTIVE NAV HIGHLIGHT ─────────────────────
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-links a');
    const sectionObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                navLinks.forEach(a => a.classList.remove('active'));
                const link = document.querySelector(`.nav-links a[href="#${e.target.id}"]`);
                if (link) link.classList.add('active');
            }
        });
    }, { threshold: 0.45 });
    sections.forEach(s => sectionObs.observe(s));

    // ── 12. SECTION MOUSE-FOLLOW GRADIENT ────────────
    if (!prefersReduced) {
        document.querySelectorAll('.section').forEach(sec => {
            sec.addEventListener('mousemove', e => {
                const r = sec.getBoundingClientRect();
                const x = ((e.clientX - r.left) / r.width * 100).toFixed(1);
                const y = ((e.clientY - r.top) / r.height * 100).toFixed(1);
                sec.style.backgroundImage = `radial-gradient(circle at ${x}% ${y}%, rgba(43,50,128,0.03) 0%, transparent 55%)`;
            }, { passive: true });
            sec.addEventListener('mouseleave', () => { sec.style.backgroundImage = ''; });
        });
    }
});

/* ═══════════════════════════════════════════════════
   SITE SETTINGS & GLOBALS
   ═══════════════════════════════════════════════════ */

let WA_NUM = '919999999999';
let WA_MSG = 'Hello%2C%20I%20would%20like%20to%20consult%20Dr.%20Jay%20Kothari';
let ICU_PHONE = '18605001066';

function applySettings(s) {
    if (s.wa_number) WA_NUM = s.wa_number;
    if (s.wa_message) WA_MSG = s.wa_message;
    if (s.icu_phone) ICU_PHONE = s.icu_phone;

    document.querySelectorAll('.sd-emergency, .icu-link-phone').forEach(el => { el.href = `tel:${ICU_PHONE}`; });
    document.querySelectorAll('.sd-whatsapp, .wa-link-btn').forEach(el => { el.href = `https://wa.me/${WA_NUM}?text=${encodeURIComponent(WA_MSG)}`; });

    if (s.site_name) {
        document.querySelectorAll('.nav-logo, .footer-doctor-name').forEach(el => {
            if (el.classList.contains('nav-logo')) {
                const parts = s.site_name.split(' ');
                if (parts.length >= 2) el.innerHTML = `${parts[0]} <span class="glow-text">${parts.slice(1).join(' ')}</span>`;
                else el.textContent = s.site_name;
            } else el.textContent = s.site_name;
        });
    }
}

(function loadSettings() {
    fetch('./api/settings.php')
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(s => applySettings(s))
        .catch(() => { });
})();

// ── UNIFIED SPEED DIAL FAB ─────────────────────────
(function createSpeedDial() {
    const fab = document.createElement('div');
    fab.id = 'speed-dial';
    fab.className = 'sd-wrap';
    fab.innerHTML = `
      <div class="sd-actions">
        <a href="tel:${ICU_PHONE}" class="sd-btn sd-emergency" aria-label="ICU Emergency">
          <span class="sd-icon">🚨</span>
          <span class="sd-label">ICU Emergency</span>
        </a>
        <a href="https://wa.me/${WA_NUM}?text=${WA_MSG}" class="sd-btn sd-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
          <span class="sd-icon">💬</span>
          <span class="sd-label">WhatsApp Chat</span>
        </a>
        <a href="booking.html" class="sd-btn sd-book" aria-label="Book OPD">
          <span class="sd-icon">📅</span>
          <span class="sd-label">Book OPD</span>
        </a>
      </div>
      <button class="sd-main" id="sd-main-btn" aria-label="Open quick actions">
        <span class="sd-main-icon">＋</span>
        <span class="sd-pulse"></span>
      </button>
    `;
    document.body.appendChild(fab);
    setTimeout(() => fab.classList.add('sd-visible'), 800);
    document.getElementById('sd-main-btn').addEventListener('click', e => { e.stopPropagation(); fab.classList.toggle('sd-open'); });
    document.addEventListener('click', e => { if (!fab.contains(e.target)) fab.classList.remove('sd-open'); });
})();

// ── PAGE FADE TRANSITIONS ―――――――――――――――――――――――――――
(function () {
    document.body.classList.add('page-fade-in');
    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('tel:') || href.startsWith('mailto:') || link.hasAttribute('target')) return;
        link.addEventListener('click', e => {
            e.preventDefault();
            document.body.classList.add('page-fade-out');
            setTimeout(() => { window.location.href = href; }, 280);
        });
    });
})();

// ── DARK / LIGHT MODE TOGGLE ――――――――――――――――――――――――
(function () {
    const saved = localStorage.getItem('apollo_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    const nav = document.querySelector('.nav-content');
    if (!nav) return;
    const btn = document.createElement('button');
    btn.id = 'theme-toggle'; btn.className = 'theme-toggle-btn';
    btn.setAttribute('aria-label', 'Toggle dark mode');
    btn.innerHTML = saved === 'dark' ? '☀️' : '🌙';
    btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('apollo_theme', next);
        btn.innerHTML = next === 'dark' ? '☀️' : '🌙';
    });
    const toggle = nav.querySelector('.nav-toggle');
    nav.insertBefore(btn, toggle || null);
})();

// ── FOOTER ENHANCEMENTS ―――――――――――――――――――――――――――――
(function () {
    document.querySelectorAll('.footer-year').forEach(el => { el.textContent = new Date().getFullYear(); });
    document.querySelectorAll('.footer-links').forEach(nav => {
        if (nav.querySelector('.footer-linkedin')) return;
        [
            { text: 'LinkedIn', href: 'https://www.linkedin.com/in/dr-jay-kothari' },
            { text: 'Find Us on Maps', href: 'https://maps.google.com/?q=Apollo+Hospitals+International+Ahmedabad', target: '_blank' }
        ].forEach(({ text, href, target }) => {
            const a = document.createElement('a'); a.href = href; a.textContent = text;
            if (target) { a.target = target; a.rel = 'noopener'; }
            nav.appendChild(a);
        });
    });
})();
