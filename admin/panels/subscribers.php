<!-- SUBSCRIBERS -->
<div class="admin-panel" id="panel-subscribers">
    <div class="panel-header">
        <h1>Newsletter Subscribers</h1>
        <p>Manage email subscribers who signed up for the ICU Family Guide.</p>
    </div>
    <div class="editor-card" style="margin-bottom:24px;">
  <h3>📧 Send Newsletter</h3>
  <p style="font-size:0.84rem;color:var(--ad-text-muted);margin-bottom:16px;">Compose and send an email to all subscribers. Use plain text or basic HTML.</p>
  <div class="editor-field">
    <label>Subject Line</label>
    <input type="text" id="nl-subject" placeholder="e.g. Monthly ICU Insights from Dr. Jay Kothari" />
  </div>
  <div class="editor-field">
    <label>Message Body (HTML allowed)</label>
    <textarea id="nl-body" rows="8" placeholder="Dear Subscriber,&#10;&#10;Write your newsletter here..."></textarea>
  </div>
  <div class="editor-field">
    <label>Preview Text <span style="color:var(--ad-text-muted);font-weight:400;">(shown in email client preview)</span></label>
    <input type="text" id="nl-preview" placeholder="e.g. This month: ECMO updates and new ICU protocols" />
  </div>
  <div class="editor-actions">
    <button class="btn-publish" onclick="sendNewsletter()">📤 Send to All Subscribers</button>
    <span id="nl-status" style="font-size:0.84rem;color:var(--ad-text-muted);margin-left:12px;"></span>
  </div>
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
