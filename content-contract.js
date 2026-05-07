(function () {
    function text(value, fallback = '') {
        if (value === null || value === undefined) return fallback;
        return String(value);
    }

    function esc(value) {
        const d = document.createElement('div');
        d.textContent = text(value);
        return d.innerHTML;
    }

    function normalizeResearchPaper(item = {}) {
        return {
            id: item.id || Date.now(),
            title: text(item.title),
            journal: text(item.journal || item.publication),
            year: text(item.year),
            topic: text(item.topic || item.category),
            authors: text(item.authors),
            doi: text(item.doi || item.url),
            url: text(item.url || item.doi),
            takeaway: text(item.takeaway || item.summary),
            practice: text(item.practice || item.position),
            status: text(item.status || 'approved'),
        };
    }

    function normalizePhotoWallItem(item = {}) {
        return {
            id: item.id || item.timestamp || Date.now(),
            url: text(item.url || item.src || item.photo_data),
            src: text(item.src || item.url || item.photo_data),
            caption: text(item.caption || item.title),
            label: text(item.label || item.category || 'General'),
            status: text(item.status || 'approved'),
            added: text(item.added || item.created_at || item.date),
            source: text(item.source),
        };
    }

    function normalizeInstituteRecognition(item = {}) {
        return {
            id: item.id || Date.now(),
            name: text(item.name || item.institution),
            icon: text(item.icon),
            body: text(item.body || item.text || item.description),
            source: text(item.source || item.department),
        };
    }

    function normalizeMediaMention(item = {}) {
        return {
            id: item.id || Date.now(),
            title: text(item.title || item.headline),
            publication: text(item.publication || item.pub || item.source),
            date: text(item.date || item.published_at),
            url: text(item.url || item.link),
            excerpt: text(item.excerpt || item.summary || item.body),
        };
    }

    function normalizeMemoryPhoto(item = {}) {
        const raw = text(item.photo_data || item.url || item.src);
        return {
            ...item,
            photo_data: raw,
            preview_src: raw,
            caption: text(item.caption),
            label: text(item.label),
            uploaded_by: text(item.uploaded_by || item.name),
            status: text(item.status || 'pending'),
        };
    }

    window.ContentContract = {
        esc,
        text,
        normalizeResearchPaper,
        normalizePhotoWallItem,
        normalizeInstituteRecognition,
        normalizeMediaMention,
        normalizeMemoryPhoto,
    };
})();
