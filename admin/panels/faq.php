<!-- FAQ EDITOR -->
<div class="admin-panel" id="panel-faq">
    <div class="panel-header">
        <div>
            <h1>❓ FAQ Manager</h1>
            <p>Add, edit, or remove frequently asked questions shown on the homepage.</p>
        </div>
        <span class="stat-pill" id="faq-count">0 questions</span>
    </div>

    <div class="editor-card" style="margin-top:0;">
        <h3 id="faq-form-title">➕ Add New FAQ</h3>
        <input type="hidden" id="faq-edit-id" value="" />
        <div class="editor-field">
            <label>Question</label>
            <input type="text" id="faq-question" placeholder="What does a Critical Care Specialist do?" />
        </div>
        <div class="editor-field">
            <label>Answer</label>
            <textarea id="faq-answer" rows="4" placeholder="A critical care specialist manages life-threatening conditions in the ICU..."></textarea>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="saveFaqItem()">💾 Save FAQ</button>
            <button class="btn-save-draft" onclick="clearFaqForm()">↺ Clear</button>
        </div>
    </div>

    <div class="editor-card">
        <h3>📋 Current FAQs <small style="font-weight:400;color:var(--ad-text-muted);">(drag to reorder coming soon)</small></h3>
        <div id="faq-table-body" style="border:1px solid var(--ad-border);border-radius:var(--ad-radius);">
            <div style="text-align:center;padding:40px;color:var(--ad-text-muted);">Loading...</div>
        </div>
    </div>
</div>
