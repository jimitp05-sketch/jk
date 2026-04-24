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

    <!-- Date Filter -->
    <div class="editor-card" style="margin-top:16px;padding:16px 20px;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <label style="font-weight:600;color:var(--ad-text-dim);font-size:0.85rem;">Filter by Date:</label>
            <input type="date" id="admin-diya-date-filter" style="padding:6px 10px;border-radius:8px;border:1px solid var(--ad-border);background:var(--ad-bg);color:var(--ad-text);font-size:0.85rem;" />
            <button class="btn-save-draft" onclick="adminFilterDiyasByDate()" style="padding:6px 14px;font-size:0.82rem;">Apply</button>
            <button class="btn-save-draft" onclick="adminResetDiyaFilter()" style="padding:6px 14px;font-size:0.82rem;">Show All</button>
        </div>
    </div>

    <!-- Diya Table -->
    <div id="diya-bulk-bar" style="display:none;background:var(--bg-section);border:1px solid var(--border);border-radius:8px;padding:10px 16px;margin-bottom:12px;align-items:center;gap:12px;flex-wrap:wrap;">
        <span id="diya-bulk-count" style="font-weight:600;color:var(--text);">0 selected</span>
        <button class="action-btn action-btn-edit" onclick="bulkModerate('diya','approved')">✅ Approve Selected</button>
        <button class="action-btn action-btn-reject" onclick="bulkModerate('diya','deleted')" style="margin-left:auto;">🗑 Delete Selected</button>
    </div>
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">All Diyas</div>
            <button class="action-btn action-btn-edit" onclick="adminLoadDiyas()">↻ Refresh</button>
        </div>
        <table id="diya-table">
            <thead>
                <tr>
                    <th style="width:32px;"><input type="checkbox" id="diya-select-all" onchange="toggleSelectAll('diya',this.checked)" /></th>
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
                    <td colspan="7" style="text-align:center;color:var(--ad-text-muted);padding:28px;">Loading diyas…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Diya Quotes Management -->
    <div class="editor-card" style="margin-top:28px;">
        <h3>💬 Diya Quotes</h3>
        <p style="color:var(--ad-text-dim);font-size:0.85rem;margin-bottom:16px;">Manage inspirational quotes shown on the Diya Memories calendar view.</p>
        <div class="editor-grid">
            <div class="editor-field" style="grid-column:1/-1;">
                <label>Quote Text</label>
                <textarea id="admin-quote-text" rows="2" placeholder="Even a single flame can hold back the darkness..."></textarea>
            </div>
            <div class="editor-field">
                <label>Author</label>
                <input type="text" id="admin-quote-author" placeholder="e.g., Ancient Proverb" />
            </div>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="adminAddQuote()">Add Quote</button>
            <button class="btn-save-draft" onclick="adminClearQuoteForm()">Clear</button>
        </div>
    </div>

    <div class="admin-table-wrap" style="margin-top:16px;">
        <div class="table-header">
            <div class="table-title">All Quotes (<span id="quote-count">0</span>)</div>
            <button class="action-btn action-btn-edit" onclick="adminLoadQuotes()">↻ Refresh</button>
        </div>
        <table id="quote-table">
            <thead>
                <tr>
                    <th>Quote</th>
                    <th>Author</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="quote-tbody">
                <tr>
                    <td colspan="3" style="text-align:center;color:var(--ad-text-muted);padding:28px;">Loading quotes…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
