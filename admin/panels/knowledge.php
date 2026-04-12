<!-- KNOWLEDGE HUB -->
<div class="admin-panel" id="panel-knowledge">
    <div class="panel-header">
        <h1>Knowledge Hub</h1>
        <p>Manage published articles and pillars.</p>
    </div>
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Published Articles (10)</div><button
                class="action-btn action-btn-edit" onclick="switchPanel('editor')">+ New
                Article</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Pillar</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="knowledge-table"></tbody>
        </table>
    </div>
</div>
