<!-- DASHBOARD -->
<div class="admin-panel active" id="panel-dashboard">
    <div class="panel-header">
        <h1>Dashboard</h1>
        <p>Overview of consultations, content, and community activity.</p>
    </div>
    <div class="stats-row">
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-bookings">0</div>
            <div class="admin-stat-label">Total Bookings</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-books">0</div>
            <div class="admin-stat-label">Pending Requests</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-articles">10</div>
            <div class="admin-stat-label">Published Articles</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-reviews">0</div>
            <div class="admin-stat-label">Pending Approvals</div>
        </div>
    </div>
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Recent Booking Requests</div><button
                class="action-btn action-btn-edit" onclick="switchPanel('requests')">View All</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="dashboard-recent-bookings">
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No
                        booking requests yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
