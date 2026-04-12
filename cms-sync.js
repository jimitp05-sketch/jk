/**
 * AI Apollo CMS Hydration Engine v1.0
 * Handles dynamic content injection with FOUC prevention and security guards.
 */

(function () {
    const CMS_CONFIG = {
        apiEndpoint: './api/settings.php?action=get_content',
        loadingClass: 'cms-loading',
        fadeDuration: 300
    };

    // 1. Initial UI Guard: Hide marked elements to prevent flicker
    const style = document.createElement('style');
    style.innerHTML = `[data-cms-key] { opacity: 0; transition: opacity ${CMS_CONFIG.fadeDuration}ms ease; }`;
    document.head.appendChild(style);

    async function hydrate() {
        try {
            const response = await fetch(CMS_CONFIG.apiEndpoint);
            if (!response.ok) throw new Error('CMS API unavailable');

            const result = await response.json();
            const content = result.site_content || {};

            // 2. Map through elements
            const elements = document.querySelectorAll('[data-cms-key]');
            elements.forEach(el => {
                const key = el.getAttribute('data-cms-key');
                if (content[key]) {
                    const value = content[key];

                    // Handle different element types
                    if (el.tagName === 'IMG') {
                        el.src = sanitizeSource(value);
                    } else if (el.tagName === 'A') {
                        el.href = sanitizeSource(value);
                    } else {
                        // Default to textContent for security (XSS prevention)
                        el.textContent = value;
                    }

                    // Special case: if it's a stat number, update the data-target attribute too
                    if (el.classList.contains('stat-number')) {
                        el.setAttribute('data-target', parseFloat(value) || 0);
                    }
                }

                // 3. Reveal element
                el.style.opacity = '1';
            });

        } catch (error) {
            console.warn('CMS Hydration failed, falling back to static HTML:', error);
            // On failure, reveal all elements anyway to show static content
            document.querySelectorAll('[data-cms-key]').forEach(el => el.style.opacity = '1');
        }
    }

    function sanitizeSource(url) {
        // Basic check to prevent javascript: or other malicious protocols
        if (url.trim().toLowerCase().startsWith('javascript:')) return '#';
        return url;
    }

    // Run on interactive to start hydration as soon as DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hydrate);
    } else {
        hydrate();
    }
})();
