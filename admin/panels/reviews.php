<!-- REVIEWS APPROVAL -->
<div class="admin-panel" id="panel-reviews">
    <div class="panel-header">
        <h1>Review Approvals</h1>
        <p>Approve or reject submitted community reviews before they appear on the Pulse page.</p>
    </div>
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Pending Reviews</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Name / Platform</th>
                    <th>Review Text</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reviews-table">
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No
                        pending reviews. <a href="reviews.html" target="_blank"
                            style="color:var(--accent-secondary);">View Pulse page ↗</a></td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Demo submit form to test workflow -->
    <div class="editor-card" style="margin-top:24px;">
        <h3>➕ Submit Test Review (Simulate incoming request)</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>Name</label><input type="text" id="rv-name"
                    placeholder="Dr. A. Sharma" /></div>
            <div class="editor-field"><label>Platform</label>
                <select id="rv-platform">
                    <option value="linkedin">LinkedIn</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="twitter">X / Twitter</option>
                    <option value="others">Other</option>
                </select>
            </div>
        </div>
        <div class="editor-field"><label>Review Text</label><textarea id="rv-text"
                style="min-height:80px;" placeholder="Write the review content…"></textarea></div>
        <button class="btn-publish" onclick="submitTestReview()" style="margin-top:4px;">Submit for
            Approval</button>
    </div>
</div>
