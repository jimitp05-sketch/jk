<!-- DASHBOARD -->
<div class="admin-panel active" id="panel-dashboard">
    <div class="panel-header">
        <h1>Dashboard</h1>
        <p>Live overview of consultations, content, and community.</p>
    </div>

    <div class="stats-row">
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-bookings">—</div>
            <div class="admin-stat-label">Total Bookings</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-books">—</div>
            <div class="admin-stat-label">Pending Requests</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-articles">—</div>
            <div class="admin-stat-label">Published Articles</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-reviews">—</div>
            <div class="admin-stat-label">Pending Approvals</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-subscribers">—</div>
            <div class="admin-stat-label">Subscribers</div>
        </div>
    </div>

    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">Quick Actions</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;padding:16px;">
            <button class="action-btn action-btn-edit" onclick="bulkApproveAll('peer_recognitions')">✅ Approve All Reviews</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('subscribers')">📧 View Subscribers</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('seohealth')">🔍 SEO Health Check</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('requests')">📋 View Bookings</button>
        </div>
    </div>

    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">SEO Health Summary</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('seohealth')">Full Report →</button>
        </div>
        <div id="dash-seo-summary" style="padding:16px;color:var(--text-muted);font-size:0.85rem;">Loading…</div>
    </div>

    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">Top Articles by Views</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('knowledge')">View All →</button>
        </div>
        <table>
            <thead>
                <tr><th>Article</th><th>Pillar</th><th>Views</th></tr>
            </thead>
            <tbody id="dash-top-articles">
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px;">Loading…</td></tr>
            </tbody>
        </table>
    </div>

    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Recent Booking Requests</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('requests')">View All</button>
        </div>
        <table>
            <thead>
                <tr><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr>
            </thead>
            <tbody id="dashboard-recent-bookings">
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No booking requests yet.</td></tr>
            </tbody>
        </table>
    </div>
</div>
