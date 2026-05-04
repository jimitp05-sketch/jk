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
    <div class="editor-card" style="margin-top:28px;">
  <h3>🚫 Block / Unblock Dates</h3>
  <p style="font-size:0.84rem;color:var(--ad-text-muted);margin-bottom:16px;">Block specific dates so patients cannot book on those days.</p>
  <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div class="editor-field" style="margin:0;">
      <label>Date to Block</label>
      <input type="date" id="block-date-input" style="padding:8px 12px;border-radius:8px;border:1px solid var(--ad-border);background:var(--ad-bg);color:var(--ad-text);" />
    </div>
    <div class="editor-field" style="margin:0;">
      <label>Reason (optional)</label>
      <input type="text" id="block-date-reason" placeholder="e.g. Conference, Holiday" style="padding:8px 12px;border-radius:8px;border:1px solid var(--ad-border);background:var(--ad-bg);color:var(--ad-text);width:200px;" />
    </div>
    <button class="btn-publish" onclick="addBlockedDate()" style="margin-bottom:0;">Block Date</button>
  </div>
  <div id="blocked-dates-list" style="margin-top:16px;"></div>
</div>
</div>
