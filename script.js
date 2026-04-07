/* ================================================
   AI APOLLO 2.0 — MASTER SCRIPT (PREMIUM EDITION)
   web-animation + frontend-design skills applied
   ================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('navbar');

    // ── 1. SCROLL PROGRESS BAR ───────────────────────
    const progressBar = document.createElement('div');
    progressBar.id = 'scroll-progress';
    progressBar.style.cssText = `
    position:fixed;top:0;left:0;height:3px;width:0%;z-index:9999;
    background:linear-gradient(90deg,#2B3280,#0078D4,#38ABFF);
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

    // ── 3. SCROLL REVEAL (web-animation skill) ───────
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05, rootMargin: '60px 0px 60px 0px' });

    document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

    // Fallback for file:// protocol
    setTimeout(() => {
        document.querySelectorAll('.reveal:not(.visible)').forEach(el => el.classList.add('visible'));
    }, 700);

    // ── 4. COUNTER ANIMATION (web-animation skill) ───
    function animateCounter(el) {
        const target = parseInt(el.dataset.target, 10);
        const suffix = el.dataset.suffix || '';
        if (isNaN(target)) return;
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                el.textContent = target.toLocaleString() + suffix;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(current).toLocaleString() + suffix;
            }
        }, 16);
    }

    // For data-count (original) + data-target (new)
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

    document.querySelectorAll('[data-count], [data-target]').forEach(el => counterObs.observe(el));

    // ── 5. HERO WORD STAGGER ─────────────────────────
    // Split hero h1 into animated words
    const heroH1 = document.querySelector('.hero h1');
    if (heroH1) {
        const html = heroH1.innerHTML;
        // Preserve <br> and <span class="glow-text">
        // Only animate top-level text nodes
        const nodes = Array.from(heroH1.childNodes);
        let delay = 0.3;
        heroH1.innerHTML = '';
        nodes.forEach(node => {
            if (node.nodeType === 3) { // text node
                node.textContent.split(' ').filter(w => w.trim()).forEach(word => {
                    const span = document.createElement('span');
                    span.className = 'hero-word';
                    span.textContent = word + ' ';
                    span.style.animationDelay = delay + 's';
                    delay += 0.12;
                    heroH1.appendChild(span);
                });
            } else {
                // Wrap the element itself
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

    // ── 6. ECG SVG — inject into hero ────────────────
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

    // ── 7. CARD 3D TILT (subtle, performance-safe) ───
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!prefersReduced) {
        document.querySelectorAll('.glass-card').forEach(card => {
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

        // Subtle parallax on hero image
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

    // ── 9. FAQ ACCORDION (smooth height) ─────────────
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

    // ── 10. RESEARCH FILTER ──────────────────────────
    const filterBtns = document.querySelectorAll('.filter-btn');
    const researchItems = document.querySelectorAll('.research-item[data-topic]');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const topic = btn.dataset.filter;
            researchItems.forEach(item => {
                if (topic === 'all' || item.dataset.topic === topic) item.removeAttribute('hidden');
                else item.setAttribute('hidden', '');
            });
        });
    });

    // ── 11. ACTIVE NAV HIGHLIGHT as you scroll ───────
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

    // ── 12. SECTION MOUSE-FOLLOW RADIAL GRADIENT ─────
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

    // ── ACCESSIBILITY ─────────────────────────────────
    if (prefersReduced) {
        document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
    }
});

/* ═══════════════════════════════════════════════════
   PHASE 1 GLOBALS
   Emergency Strip · WhatsApp Float · Dark Mode · Page Transitions
   ═══════════════════════════════════════════════════ */

// ── SITE SETTINGS — loaded from /api/settings.php ─────────────────────────
let WA_NUM = '919999999999';
let WA_MSG = 'Hello%2C%20I%20would%20like%20to%20consult%20Dr.%20Jay%20Kothari';
let ICU_PHONE = '18605001066';

function applySettings(s) {
    if (s.wa_number) WA_NUM = s.wa_number;
    if (s.wa_message) WA_MSG = s.wa_message;
    if (s.icu_phone) ICU_PHONE = s.icu_phone;

    // Patch speed dial and any other contact links
    const emgCalls = document.querySelectorAll('.sd-emergency, .icu-link-phone');
    const waChats = document.querySelectorAll('.sd-whatsapp, .wa-link-btn');

    emgCalls.forEach(el => {
        el.href = `tel:${ICU_PHONE}`;
    });
    waChats.forEach(el => {
        el.href = `https://wa.me/${WA_NUM}?text=${encodeURIComponent(WA_MSG)}`;
    });

    // Patch site name
    if (s.site_name) {
        document.querySelectorAll('.nav-logo, .footer-doctor-name').forEach(el => {
            if (el.classList.contains('nav-logo')) {
                const parts = s.site_name.split(' ');
                if (parts.length > 2) {
                    el.innerHTML = `${parts[0]} ${parts[1]} <span class="glow-text">${parts.slice(2).join(' ')}</span>`;
                } else if (parts.length === 2) {
                    el.innerHTML = `${parts[0]} <span class="glow-text">${parts[1]}</span>`;
                } else {
                    el.textContent = s.site_name;
                }
            } else {
                el.textContent = s.site_name;
            }
        });
    }

    // Patch hero
    if (s.hero_title) {
        const h1 = document.querySelector('.hero h1');
        if (h1) h1.innerHTML = s.hero_title.replace('Seconds', '<span class="glow-text">Seconds</span>');
    }
    if (s.hero_tagline) {
        const p = document.querySelector('.hero-tagline');
        if (p) p.textContent = s.hero_tagline;
    }
    if (s.hero_empathy) {
        const p = document.querySelector('.hero-empathy');
        if (p) p.textContent = s.hero_empathy;
    }
}

(function loadSettings() {
    fetch('/api/settings.php')
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(s => {
            applySettings(s);
        })
        .catch(() => { }); // defaults are hardcoded in variables
})();

// ── UNIFIED SPEED DIAL FAB ────────────────────────────────
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
        <a href="https://wa.me/${WA_NUM}?text=${WA_MSG}"
           class="sd-btn sd-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
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

    document.getElementById('sd-main-btn').addEventListener('click', e => {
        e.stopPropagation();
        fab.classList.toggle('sd-open');
    });

    document.addEventListener('click', e => {
        if (!fab.contains(e.target)) fab.classList.remove('sd-open');
    });
})();



// ── PAGE FADE TRANSITIONS ―――――――――――――――――――――――――――――
(function () {
    document.body.classList.add('page-fade-in');
    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('http') ||
            href.startsWith('tel:') || href.startsWith('mailto:') ||
            link.hasAttribute('target')) return;
        link.addEventListener('click', e => {
            e.preventDefault();
            document.body.classList.add('page-fade-out');
            setTimeout(() => { window.location.href = href; }, 280);
        });
    });
})();

// ── DARK / LIGHT MODE TOGGLE ―――――――――――――――――――――――――
(function () {
    const saved = localStorage.getItem('apollo_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    const nav = document.querySelector('.nav-content');
    if (!nav) return;
    const btn = document.createElement('button');
    btn.id = 'theme-toggle';
    btn.className = 'theme-toggle-btn';
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

// ── FOOTER ENHANCEMENTS ――――――――――――――――――――――――――――――
(function () {
    document.querySelectorAll('.footer-year').forEach(el => { el.textContent = new Date().getFullYear(); });
    document.querySelectorAll('.footer-links').forEach(nav => {
        if (nav.querySelector('.footer-linkedin')) return;
        [
            { text: 'LinkedIn', href: 'https://www.linkedin.com/in/dr-jay-kothari', cls: 'footer-linkedin', target: '_blank' },
            { text: 'Find Us on Maps', href: 'https://maps.google.com/?q=Apollo+Hospitals+International+Ahmedabad', target: '_blank' },
            { text: 'Sitemap', href: 'sitemap.xml' }
        ].forEach(({ text, href, cls, target }) => {
            const a = document.createElement('a');
            a.href = href; a.textContent = text;
            if (cls) a.className = cls;
            if (target) { a.target = target; a.rel = 'noopener'; }
            nav.appendChild(a);
        });
    });
})();

