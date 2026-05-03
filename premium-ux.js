/* ================================================
   PREMIUM UX ENHANCEMENTS v1.0
   Features: Hero Parallax, Timeline Animation,
   WhatsApp FAB, Enhanced Counters, Expertise Tooltips
   ================================================ */

(function () {
    'use strict';

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── 1. HERO PARALLAX ON SCROLL ──────────────────────
    function initHeroParallax() {
        if (prefersReduced) return;
        var heroImg = document.querySelector('.hero-image-wrapper');
        var heroContent = document.querySelector('.hero-content');
        var heroOrb = document.querySelector('.hero-glow-orb');
        var hero = document.querySelector('.hero');
        if (!hero || !heroImg) return;

        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () {
                var scrolled = window.scrollY;
                var heroH = hero.offsetHeight;
                if (scrolled < heroH) {
                    var ratio = scrolled / heroH;
                    heroImg.style.transform = 'translateY(' + (scrolled * 0.12) + 'px)';
                    if (heroContent) {
                        heroContent.style.transform = 'translateY(' + (scrolled * 0.04) + 'px)';
                        heroContent.style.opacity = String(1 - ratio * 0.3);
                    }
                    if (heroOrb) {
                        heroOrb.style.transform = 'translateY(' + (scrolled * -0.08) + 'px) scale(' + (1 + ratio * 0.1) + ')';
                    }
                }
                ticking = false;
            });
        }, { passive: true });
    }

    // ── 2. TIMELINE SCROLL ANIMATION ────────────────────
    function initTimelineAnimation() {
        var items = document.querySelectorAll('.timeline-item');
        if (!items.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

        items.forEach(function (item) {
            item.classList.remove('visible');
            observer.observe(item);
        });
    }

    // ── 3. WHATSAPP FLOATING BUTTON ─────────────────────
    function initWhatsAppFAB() {
        var fab = document.createElement('div');
        fab.className = 'whatsapp-float';
        fab.id = 'whatsapp-float';
        fab.innerHTML =
            '<span class="whatsapp-float-label">Chat with Dr. Kothari\'s Team</span>' +
            '<a href="https://wa.me/919999999999?text=Hello%2C%20I%20need%20to%20consult%20Dr.%20Jay%20Kothari" ' +
            'class="whatsapp-float-btn sd-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">' +
            '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>' +
            '</svg></a>';

        document.body.appendChild(fab);

        var showThreshold = 400;
        var isVisible = false;

        window.addEventListener('scroll', function () {
            var shouldShow = window.scrollY > showThreshold;
            if (shouldShow && !isVisible) {
                fab.classList.add('visible');
                isVisible = true;
            } else if (!shouldShow && isVisible) {
                fab.classList.remove('visible');
                isVisible = false;
            }
        }, { passive: true });
    }

    // ── 4. ENHANCED COUNTER ANIMATION ───────────────────
    function initEnhancedCounters() {
        var counters = document.querySelectorAll('.stat-number');
        if (!counters.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !entry.target.dataset.glowed) {
                    entry.target.dataset.glowed = '1';
                    entry.target.classList.add('counting');
                    setTimeout(function () {
                        entry.target.classList.remove('counting');
                    }, 1600);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(function (el) { observer.observe(el); });
    }

    // ── 5. EXPERTISE TOOLTIPS ───────────────────────────
    function initExpertiseTooltips() {
        var tooltipData = [
            { keyword: 'ECMO', plain: 'A machine that takes over for your heart and lungs when they can\'t work on their own.' },
            { keyword: 'CRRT', plain: 'Gentle, continuous kidney filtering for patients too unstable for regular dialysis.' },
            { keyword: 'Sepsis', plain: 'Fighting a deadly body-wide infection before it shuts down your organs.' },
            { keyword: 'Multi-Organ', plain: 'Managing when multiple organs fail at once — lungs, kidneys, liver.' },
            { keyword: 'Mechanical Ventilation', plain: 'Helping patients breathe with a machine — and safely getting them off it.' },
            { keyword: 'Post-Surgical', plain: 'Intensive monitoring and recovery support after major surgery.' }
        ];

        var cards = document.querySelectorAll('.expertise-card');
        cards.forEach(function (card, index) {
            if (index >= tooltipData.length) return;
            var data = tooltipData[index];

            var tooltip = document.createElement('div');
            tooltip.className = 'expertise-tooltip';
            tooltip.setAttribute('role', 'tooltip');
            tooltip.id = 'tooltip-' + index;
            tooltip.innerHTML = '<span class="tooltip-label">In Plain English</span>' + data.plain;

            card.appendChild(tooltip);
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-describedby', 'tooltip-' + index);
        });
    }

    // ── 6. GA4 EVENT TRACKING ────────────────────────────
    function initAnalyticsTracking() {
        function trackEvent(category, action, label) {
            if (typeof gtag === 'function') {
                gtag('event', action, {
                    event_category: category,
                    event_label: label
                });
            }
            if (window.dataLayer) {
                window.dataLayer.push({
                    event: 'cta_click',
                    cta_category: category,
                    cta_action: action,
                    cta_label: label
                });
            }
        }

        document.querySelectorAll('.sd-whatsapp, .btn-cta-whatsapp, .whatsapp-float-btn').forEach(function (el) {
            el.addEventListener('click', function () {
                trackEvent('Contact', 'whatsapp_click', el.closest('.whatsapp-float') ? 'floating_button' : 'inline');
            });
        });

        document.querySelectorAll('.sd-emergency, .btn-cta-call, [href^="tel:"]').forEach(function (el) {
            el.addEventListener('click', function () {
                trackEvent('Contact', 'phone_click', 'call_now');
            });
        });

        document.querySelectorAll('.sd-book, .btn-cta-opd').forEach(function (el) {
            el.addEventListener('click', function () {
                trackEvent('Booking', 'opd_click', 'book_consultation');
            });
        });

        var nlForm = document.querySelector('.newsletter-form');
        if (nlForm) {
            nlForm.addEventListener('submit', function () {
                trackEvent('Lead', 'guide_download', 'icu_family_guide');
            });
        }

        var scrollDepths = { 25: false, 50: false, 75: false, 100: false };
        window.addEventListener('scroll', function () {
            var percent = Math.round(window.scrollY / (document.body.scrollHeight - window.innerHeight) * 100);
            [25, 50, 75, 100].forEach(function (depth) {
                if (percent >= depth && !scrollDepths[depth]) {
                    scrollDepths[depth] = true;
                    trackEvent('Engagement', 'scroll_depth', depth + '%');
                }
            });
        }, { passive: true });
    }

    // ── INIT ────────────────────────────────────────────
    function init() {
        initHeroParallax();
        initTimelineAnimation();
        // initWhatsAppFAB(); // Disabled – WhatsApp FAB removed from home page
        initEnhancedCounters();
        initExpertiseTooltips();
        initAnalyticsTracking();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
