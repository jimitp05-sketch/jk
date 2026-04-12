<!-- ARTICLE EDITOR -->
<div class="admin-panel" id="panel-editor">
    <div class="panel-header">
        <h1>Article Editor</h1>
        <p>Create or edit a Knowledge Hub article.</p>
    </div>
    <div class="editor-card">
        <input type="hidden" id="editor-id" value="" />
        <input type="hidden" id="editor-overrides-id" value="" />
        <div class="editor-grid">
            <div class="editor-field">
                <label>Article Title *</label>
                <input type="text" id="editor-title"
                    placeholder="e.g. Understanding Sepsis: What Families Must Know" />
            </div>
            <div class="editor-field">
                <label>Pillar / Topic *</label>
                <select id="editor-pillar">
                    <option>Sepsis & Infection</option>
                    <option>ECMO & Advanced Life Support</option>
                    <option>Organ Failure & CRRT</option>
                    <option>Respiratory & Ventilation</option>
                    <option>Multi-Organ Failure</option>
                    <option>Family Guide to the ICU</option>
                    <option>ICU Delirium & Brain Health</option>
                    <option>Critical Care Nutrition</option>
                    <option>Infection Control & Antimicrobial Stewardship</option>
                    <option>Post-ICU Recovery & PICS</option>
                    <option>Other</option>
                </select>
            </div>
        </div>
        <div class="editor-field">
            <label>Article Subtitle / Description</label>
            <input type="text" id="editor-subtitle"
                placeholder="A short, plain-language description shown in the hub cards…" />
        </div>
        <!-- Hidden textarea — stores generated HTML for knowledge.html -->
        <textarea id="editor-body" style="display:none;"></textarea>

        <!-- SECTION BUILDER -->
        <div class="editor-field">
            <div
                style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <label style="margin:0;">Article Sections</label>
                <button type="button" class="action-btn action-btn-edit" onclick="addSection()">+ Add
                    Section</button>
            </div>
            <div id="sections-builder"></div>
            <p style="font-size:0.75rem;color:var(--text-muted);margin-top:8px;">Each section has a
                heading, plain-text content, an optional highlighted callout, and an optional warning
                note.</p>
        </div>

        <!-- STATS BUILDER -->
        <div class="editor-field">
            <label>Key Stats <span style="font-weight:400;color:var(--text-muted);">(optional — up to 3
                    headline numbers shown in the article)</span></label>
            <div class="stats-builder">
                <div class="stat-input-group">
                    <small>Stat 1 — Number</small><input type="text" id="stat1-num"
                        placeholder="e.g. 11M" />
                    <small>Stat 1 — Label</small><input type="text" id="stat1-lbl"
                        placeholder="e.g. deaths annually worldwide" />
                </div>
                <div class="stat-input-group">
                    <small>Stat 2 — Number</small><input type="text" id="stat2-num"
                        placeholder="e.g. 7%" />
                    <small>Stat 2 — Label</small><input type="text" id="stat2-lbl"
                        placeholder="e.g. mortality increase per hour" />
                </div>
                <div class="stat-input-group">
                    <small>Stat 3 — Number</small><input type="text" id="stat3-num"
                        placeholder="e.g. 1hr" />
                    <small>Stat 3 — Label</small><input type="text" id="stat3-lbl"
                        placeholder="e.g. the critical window" />
                </div>
            </div>
        </div>
        <div class="editor-grid">
            <div class="editor-field">
                <label>Author</label>
                <input type="text" id="editor-author" value="Dr. Jay Kothari" />
            </div>
            <div class="editor-field">
                <label>Status</label>
                <select id="editor-status">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="saveArticle()">💾 Save Article to Server</button>
            <button class="action-btn action-btn-reject" style="margin-left:auto;"
                onclick="clearEditor()">Clear</button>
        </div>
    </div>
    <div id="editor-articles-list" style="margin-top:24px;"></div>
</div>
