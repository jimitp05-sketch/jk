<!-- PULSE: INSTITUTE RECOGNITIONS PANEL -->
<div class="admin-panel" id="panel-pulse-institutes">
    <div class="panel-header">
        <div>
            <h1>🏛️ Institute Recognitions</h1>
            <p>Manage the "Peer & Institutional Recognition" cards on the Pulse page. Add, edit, or delete recognition cards from medical institutions.</p>
        </div>
    </div>

    <!-- Add / Edit Form -->
    <div class="editor-card" style="margin-top:0;">
        <h3>+ Add / Edit Recognition</h3>
        <input type="hidden" id="inst-edit-id" value="" />
        <div class="editor-grid">
            <div class="editor-field">
                <label>Institution Name *</label>
                <input type="text" id="inst-name" placeholder="e.g. Apollo Hospitals, Ahmedabad" />
            </div>
            <div class="editor-field">
                <label>Icon (emoji)</label>
                <input type="text" id="inst-icon" placeholder="e.g. 🏥" maxlength="4" />
            </div>
        </div>
        <div class="editor-field">
            <label>Recognition Text *</label>
            <textarea id="inst-body" rows="3" placeholder="What the institution said about Dr. Kothari..."></textarea>
        </div>
        <div class="editor-field">
            <label>Source / Department</label>
            <input type="text" id="inst-source" placeholder="e.g. Department of Critical Care Medicine, Apollo Hospitals" />
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="saveInstitute()">Save Recognition</button>
            <button class="btn-save-draft" onclick="clearInstituteForm()">Clear</button>
        </div>
    </div>

    <!-- List -->
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Recognitions (<span id="inst-count">0</span>)</div>
            <button class="action-btn action-btn-edit" onclick="loadInstitutes()">Refresh</button>
        </div>
        <div id="inst-list"></div>
    </div>
</div>
