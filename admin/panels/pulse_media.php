<!-- PULSE: MEDIA MENTIONS PANEL -->
<div class="admin-panel" id="panel-pulse-media">
    <div class="panel-header">
        <div>
            <h1>📰 Media Mentions</h1>
            <p>Manage press coverage, news articles, and media mentions of Dr. Kothari's work. These appear in a dedicated section on the Pulse page.</p>
        </div>
    </div>

    <!-- Add / Edit Form -->
    <div class="editor-card" style="margin-top:0;">
        <h3>+ Add / Edit Media Mention</h3>
        <input type="hidden" id="media-edit-id" value="" />
        <div class="editor-grid">
            <div class="editor-field">
                <label>Headline / Title *</label>
                <input type="text" id="media-title" placeholder="e.g. Apollo Hospitals pioneers ECMO treatment in Gujarat" />
            </div>
            <div class="editor-field">
                <label>Publication Name *</label>
                <input type="text" id="media-pub" placeholder="e.g. Times of India, Gujarat Samachar" />
            </div>
        </div>
        <div class="editor-grid">
            <div class="editor-field">
                <label>Date Published</label>
                <input type="date" id="media-date" />
            </div>
            <div class="editor-field">
                <label>Article URL (optional)</label>
                <input type="url" id="media-url" placeholder="https://..." />
            </div>
        </div>
        <div class="editor-field">
            <label>Excerpt / Summary</label>
            <textarea id="media-excerpt" rows="3" placeholder="Brief summary of what was covered..."></textarea>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="saveMediaMention()">Save Media Mention</button>
            <button class="btn-save-draft" onclick="clearMediaForm()">Clear</button>
        </div>
    </div>

    <!-- List -->
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Media Mentions (<span id="media-count">0</span>)</div>
            <button class="action-btn action-btn-edit" onclick="loadMediaMentions()">Refresh</button>
        </div>
        <div id="media-list"></div>
    </div>
</div>
