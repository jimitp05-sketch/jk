<!-- RESEARCH PAPERS EDITOR -->
<div class="admin-panel" id="panel-research">
    <div class="panel-header">
        <h1>🔬 Research Papers</h1>
        <p>Add, edit or remove papers shown on the Research page.</p>
    </div>
    <div class="editor-card">
        <h3>➕ Add / Edit Paper</h3>
        <input type="hidden" id="rp-edit-id" value="" />
        <div class="editor-grid">
            <div class="editor-field"><label>Paper Title</label><input type="text" id="rp-title"
                    placeholder="PROSEVA Trial: Prone Positioning in Severe ARDS" /></div>
            <div class="editor-field"><label>Journal</label><input type="text" id="rp-journal"
                    placeholder="New England Journal of Medicine" /></div>
        </div>
        <div class="editor-grid">
            <div class="editor-field"><label>Year</label><input type="number" id="rp-year"
                    placeholder="2013" min="1990" max="2030" /></div>
            <div class="editor-field"><label>Topic Tag</label>
                <select id="rp-topic">
                    <option>ARDS & Ventilation</option>
                    <option>Sepsis & Infection</option>
                    <option>ECMO</option>
                    <option>Renal Support</option>
                    <option>Nutrition & Metabolism</option>
                    <option>Delirium & Sedation</option>
                    <option>Other</option>
                </select>
            </div>
        </div>
        <div class="editor-field"><label>DOI / URL</label><input type="text" id="rp-doi"
                placeholder="https://doi.org/…" /></div>
        <div class="editor-field"><label>Clinical Takeaway</label>
            <textarea id="rp-takeaway" rows="2" placeholder="Summary of the study findings…"></textarea>
        </div>
        <div class="editor-field"><label>Dr. Kothari's Practice (Bedside Context)</label>
            <textarea id="rp-practice" rows="2"
                placeholder="How you apply this evidence in your ICU…"></textarea>
        </div>
        <div class="editor-actions" style="margin-top:8px;">
            <button class="btn-publish" onclick="saveResearchPaper()">💾 Save Paper to Server</button>
            <button class="btn-save-draft" onclick="clearResearchForm()">Clear</button>
        </div>
    </div>
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Papers</div><span id="rp-count"
                style="font-size:0.8rem;color:var(--text-muted);"></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Journal</th>
                    <th>Year</th>
                    <th>Topic</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rp-table"></tbody>
        </table>
    </div>
</div>
