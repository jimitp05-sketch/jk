<!-- REVIEW APPROVALS -->
<div class="admin-panel" id="panel-reviews">
    <div class="panel-header">
        <h1>Review Approvals</h1>
        <p>Approve or reject community-submitted reviews.</p>
    </div>

    <div id="reviews-bulk-bar" style="display:none;background:var(--bg-section);border:1px solid var(--border);border-radius:8px;padding:10px 16px;margin-bottom:12px;align-items:center;gap:12px;flex-wrap:wrap;">
        <span id="reviews-bulk-count" style="font-weight:600;color:var(--text);">0 selected</span>
        <button class="action-btn action-btn-edit" onclick="bulkModerate('reviews','approved')">✅ Approve Selected</button>
        <button class="action-btn action-btn-reject" onclick="bulkModerate('reviews','rejected')">❌ Reject Selected</button>
        <button class="action-btn action-btn-reject" onclick="bulkModerate('reviews','deleted')" style="margin-left:auto;">🗑 Delete Selected</button>
    </div>

    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Submitted Reviews <span class="admin-badge" id="badge-reviews">0</span></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:32px;"><input type="checkbox" id="reviews-select-all" onchange="toggleSelectAll('reviews',this.checked)" /></th>
                    <th>Name / Platform</th>
                    <th>Review</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reviews-table-body">
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:28px;">No reviews yet.</td></tr>
            </tbody>
        </table>
    </div>
</div>
