<!-- SUBSCRIBERS -->
<div class="admin-panel" id="panel-subscribers">
    <div class="panel-header">
        <h1>Newsletter Subscribers</h1>
        <p>Manage email subscribers who signed up for the ICU Family Guide.</p>
    </div>
    <div class="stats-row" style="margin-bottom:20px;">
        <div class="admin-stat">
            <div class="admin-stat-num" id="sub-total">—</div>
            <div class="admin-stat-label">Total Subscribers</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="sub-new-week">—</div>
            <div class="admin-stat-label">New This Week</div>
        </div>
    </div>
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">All Subscribers</div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" id="sub-search" placeholder="Search email…" oninput="filterSubscribers()"
                    style="padding:6px 10px;border-radius:6px;border:1px solid var(--border);background:var(--bg-section);color:var(--text);font-size:0.85rem;" />
                <button class="action-btn action-btn-edit" onclick="exportSubscribersCSV()">📥 Export CSV</button>
            </div>
        </div>
        <table>
            <thead>
                <tr><th>Email</th><th>Name</th><th>Source</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody id="sub-table-body">
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>
