<!-- CALENDAR -->
<div class="admin-panel" id="panel-calendar">
    <div class="panel-header">
        <h1>Booking Calendar</h1>
        <p>View scheduled OPD consultations by date.</p>
    </div>
    <div class="admin-calendar-grid">
        <div class="admin-cal-card">
            <div
                style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 id="admin-cal-month" style="margin:0;">April 2026</h3>
                <div style="display:flex;gap:6px;">
                    <button class="cal-nav-btn" onclick="adminCalNav(-1)">‹</button>
                    <button class="cal-nav-btn" onclick="adminCalNav(1)">›</button>
                </div>
            </div>
            <div class="mini-cal-grid" id="admin-cal-grid"></div>
        </div>
        <div class="admin-cal-card">
            <h3 id="selected-day-label">Select a day to see bookings</h3>
            <div id="day-booking-list">
                <div class="empty-bookings">Click a date on the calendar</div>
            </div>
        </div>
    </div>
</div>
