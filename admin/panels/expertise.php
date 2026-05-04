<!-- EXPERTISE MANAGEMENT PANEL -->
<div class="admin-panel" id="panel-expertise">
    <div class="panel-header">
        <div>
            <h1>⚕️ Expertise Cards</h1>
            <p>Manage the clinical specialisation cards shown in the "Expertise" section on the homepage. Add, edit, reorder, or remove specialties.</p>
        </div>
    </div>

    <div class="editor-card" style="margin-top:0;">
        <h3>+ Add / Edit Expertise Card</h3>
        <input type="hidden" id="exp-edit-id" value="" />
        <div class="editor-grid">
            <div class="editor-field">
                <label>Speciality Title *</label>
                <input type="text" id="exp-title" placeholder="e.g. ECMO — Extracorporeal Membrane Oxygenation" />
            </div>
            <div class="editor-field">
                <label>Plain English Label</label>
                <input type="text" id="exp-plain" placeholder="e.g. A machine that takes over for your heart and lungs" />
            </div>
        </div>
        <div class="editor-field">
            <label>Description *</label>
            <textarea id="exp-desc" rows="3" placeholder="Clinical description of this speciality..."></textarea>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="saveExpertise()">Save Card</button>
            <button class="btn-save-draft" onclick="clearExpertiseForm()">Clear</button>
        </div>
    </div>

    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Expertise Cards (<span id="exp-count">0</span>)</div>
            <button class="action-btn action-btn-edit" onclick="loadExpertise()">Refresh</button>
        </div>
        <div id="exp-list"></div>
    </div>
</div>
