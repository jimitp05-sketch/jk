<!-- AUDIT TRAIL PANEL -->
<div class="admin-panel" id="panel-audit">
    <div class="panel-header">
        <div>
            <h1>📋 Audit Trail</h1>
            <p>Recent admin actions — what was changed and when.</p>
        </div>
    </div>
    <div class="editor-card" style="margin-top:0;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <button class="action-btn action-btn-edit" onclick="loadAuditLog()">↻ Refresh</button>
            <select id="audit-filter" style="padding:6px 12px;border-radius:8px;border:1px solid var(--ad-border);background:var(--ad-bg);color:var(--ad-text);font-size:0.84rem;" onchange="filterAuditLog()">
                <option value="">All Sections</option>
                <option value="Knowledge">Knowledge</option>
                <option value="Quiz">Quiz</option>
                <option value="Research">Research</option>
                <option value="Myths">Myths</option>
                <option value="FAQ">FAQ</option>
                <option value="Reviews">Reviews</option>
                <option value="Memories">Memories</option>
                <option value="Diya">Diya</option>
                <option value="Settings">Settings</option>
                <option value="Images">Images</option>
            </select>
        </div>
        <div id="audit-table-wrap">
            <p style="color:var(--ad-text-muted);font-size:0.9rem;">Loading audit log...</p>
        </div>
    </div>
</div>
