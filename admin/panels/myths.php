<!-- MYTH BUSTER EDITOR -->
<div class="admin-panel" id="panel-myths">
    <div class="panel-header">
        <h1>🃏 Myth Buster Cards</h1>
        <p>Add, edit or remove flip-cards shown in the ICU Intel → Myth Busters tab.</p>
    </div>
    <div class="editor-card">
        <h3>➕ Add / Edit Myth Card</h3>
        <input type="hidden" id="myth-edit-id" value="" />
        <div class="editor-grid">
            <div class="editor-field"><label>Myth Statement (front)</label><textarea id="myth-statement"
                    rows="3" placeholder='"Patients on ventilators feel nothing."'></textarea></div>
            <div class="editor-field"><label>Fact (back)</label><textarea id="myth-fact" rows="3"
                    placeholder="Evidence-based explanation of the truth…"></textarea></div>
        </div>
        <div class="editor-field"><label>Source / Citation</label><input type="text" id="myth-source"
                placeholder="e.g. SSC Guidelines 2021" /></div>
        <div class="editor-actions" style="margin-top:8px;">
            <button class="btn-publish" onclick="saveMythCard()">💾 Save Card</button>
            <button class="btn-save-draft" onclick="clearMythForm()">Clear</button>
        </div>
    </div>
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Myth Cards</div><span id="myth-count"
                style="font-size:0.8rem;color:var(--text-muted);"></span>
        </div>
        <div id="myth-table-body" style="padding:0 4px;"></div>
    </div>
    <p style="font-size:0.78rem;color:var(--text-muted);margin-top:12px;">💡 After saving, the Myth
        Busters tab on quiz.html will use these custom cards instead of the defaults.</p>
</div>
