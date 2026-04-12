<!-- EXPORT / IMPORT -->
<div class="admin-panel" id="panel-export">
    <div class="panel-header">
        <div>
            <h1>📦 Export / Import</h1>
            <p>Download CMS content as JSON or upload a backup to restore.</p>
        </div>
    </div>
    <div class="editor-card" style="margin-top:0;">
        <h3>⬇️ Export Content</h3>
        <p style="font-size:0.84rem;color:var(--ad-text-muted);margin-bottom:18px;">Download current
            server data for any section.</p>
        <div class="action-btns">
            <button class="btn-publish" onclick="exportSection('myth_busters')">Myths JSON</button>
            <button class="btn-publish" onclick="exportSection('quiz_questions')">Quiz JSON</button>
            <button class="btn-publish" onclick="exportSection('research_papers')">Research
                JSON</button>
            <button class="btn-publish" onclick="exportSection('knowledge_articles')">Knowledge
                JSON</button>
            <button class="btn-save-draft" onclick="exportAll()">⬇️ Export ALL</button>
        </div>
    </div>
    <div class="editor-card">
        <h3>⬆️ Import / Restore</h3>
        <p style="font-size:0.84rem;color:var(--ad-text-muted);margin-bottom:18px;">Upload a JSON file
            to <strong style="color:var(--ad-red);">overwrite</strong> the selected section.</p>
        <div class="editor-field"><label>Content Type</label>
            <select id="import-type">
                <option value="myth_busters">Myth Buster Cards</option>
                <option value="quiz_questions">Quiz Questions</option>
                <option value="research_papers">Research Papers</option>
                <option value="knowledge_articles">Knowledge Articles</option>
            </select>
        </div>
        <div class="editor-field"><label>Select JSON File</label><input type="file" id="import-file"
                accept=".json" style="color:var(--ad-text);" /></div>
        <button class="btn-publish" style="background:linear-gradient(135deg,#b45309,#92400e);"
            onclick="importContent()">⬆️ Import &amp; Overwrite</button>
        <div id="import-status" style="margin-top:12px;display:none;"></div>
    </div>
    <div class="editor-card">
        <h3>🗂️ Booking Data Export</h3>
        <div class="action-btns">
            <button class="btn-publish" onclick="exportBookingsCSV()">⬇️ CSV</button>
            <button class="btn-save-draft" onclick="exportBookingsJSON()">⬇️ JSON</button>
        </div>
    </div>
</div>
