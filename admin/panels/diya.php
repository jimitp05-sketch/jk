<!-- DIYA MANAGEMENT PANEL -->
<div class="admin-panel" id="panel-diya" style="display:none;">
    <div class="panel-header">
        <div>
            <h1>🪔 Diya Prayer Wall</h1>
            <p>Manage the Light a Diya prayer wall. Diyas are auto-approved. You can delete inappropriate ones.</p>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
        <div class="admin-stat">
            <div class="admin-stat-num" id="diya-total">0</div>
            <div class="admin-stat-label">Total Diyas</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="diya-approved">0</div>
            <div class="admin-stat-label">Approved</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="diya-pending">0</div>
            <div class="admin-stat-label">Pending</div>
        </div>
    </div>

    <!-- Add Diya Form -->
    <div class="editor-card">
        <h3>➕ Add Diya Manually</h3>
        <div class="editor-grid">
            <div class="editor-field">
                <label>This diya is for</label>
                <input type="text" id="admin-diya-name" placeholder="e.g., For Papa" />
            </div>
            <div class="editor-field">
                <label>Lit by</label>
                <input type="text" id="admin-diya-litby" placeholder="e.g., A loving daughter" />
            </div>
        </div>
        <div class="editor-field">
            <label>Prayer / Wish</label>
            <textarea id="admin-diya-prayer" rows="3" placeholder="Write the prayer message..."></textarea>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="adminAddDiya()">Add Diya 🪔</button>
            <button class="btn-save-draft" onclick="adminClearDiyaForm()">Clear</button>
        </div>
    </div>

    <!-- Diya Table -->
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Diyas</div>
            <button class="action-btn action-btn-edit" onclick="adminLoadDiyas()">↻ Refresh</button>
        </div>
        <table id="diya-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Prayer</th>
                    <th>Lit By</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="diya-tbody">
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--ad-text-muted);padding:28px;">Loading diyas…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
