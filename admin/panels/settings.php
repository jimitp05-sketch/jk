<!-- SITE SETTINGS -->
<div class="admin-panel" id="panel-settings">
    <div class="panel-header">
        <h1>⚙️ Site Settings</h1>
        <p>Update contact numbers, WhatsApp details, and site name. Changes are saved to the server and
            reflected on the live website immediately.</p>
    </div>
    <div class="editor-card">
        <h3>📲 Contact & WhatsApp</h3>
        <div class="editor-grid">
            <div class="editor-field">
                <label>WhatsApp Number <span style="font-weight:400;color:var(--text-muted);">(with
                        country code, no +)</span></label>
                <input type="text" id="st-wa-number" placeholder="919999999999" />
            </div>
            <div class="editor-field">
                <label>ICU Emergency Phone</label>
                <input type="text" id="st-icu-phone" placeholder="18605001066" />
            </div>
        </div>
        <div class="editor-field">
            <label>WhatsApp Pre-filled Message</label>
            <input type="text" id="st-wa-message"
                placeholder="Hello, I would like to consult Dr. Jay Kothari" />
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Site Name / Doctor Name</label>
            <input type="text" id="st-site-name" placeholder="Dr. Jay Kothari">
        </div>
        <div class="form-group" style="grid-column: 1 / -1; margin-top:20px;">
            <h3 style="margin-bottom:12px;">🏠 Homepage Hero & Content</h3>
            <label>Hero Badge Text</label>
            <input type="text" id="st-hero-badge" placeholder="🔵 Apollo Hospitals · Gujarat's #1 Critical Care Unit">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Title (Main Heading)</label>
            <input type="text" id="st-hero-title" placeholder="Your Family Deserves The Best ICU Doctor in Gujarat.">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Description (Subtext)</label>
            <textarea id="st-hero-desc" placeholder="We know you're terrified right now..." style="min-height:80px;"></textarea>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Doctor Image URL</label>
            <input type="text" id="st-hero-img" placeholder="img-hero-doctor.png">
        </div>
        
        <div class="editor-grid" style="margin-top:12px;">
            <div class="editor-field">
                <label>Stat 1 Value & Label</label>
                <input type="text" id="st-hero-stat1-val" placeholder="30" style="margin-bottom:4px;" />
                <input type="text" id="st-hero-stat1-lbl" placeholder="Years of Practice" />
            </div>
            <div class="editor-field">
                <label>Stat 2 Value & Label</label>
                <input type="text" id="st-hero-stat2-val" placeholder="10000" style="margin-bottom:4px;" />
                <input type="text" id="st-hero-stat2-lbl" placeholder="Lives Touched" />
            </div>
            <div class="editor-field">
                <label>Stat 3 Value & Label</label>
                <input type="text" id="st-hero-stat3-val" placeholder="10" style="margin-bottom:4px;" />
                <input type="text" id="st-hero-stat3-lbl" placeholder="ECMO Docs in Gujarat" />
            </div>
        </div>
        <div style="margin: 24px 0 12px; border-top: 1px solid var(--border-subtle); padding-top: 20px;">
            <h3 style="margin-bottom:4px;">🔐 Admin Login Credentials</h3>
            <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:16px;">Change your username or password. You must enter your current password to verify your identity.</p>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>New Username <span style="font-weight:400;color:var(--text-muted);">(leave blank to keep same)</span></label>
                    <input type="text" id="cred-new-user" placeholder="admin" autocomplete="off" />
                </div>
                <div class="editor-field">
                    <label>Current Password <span style="color:#ef4444;">*</span></label>
                    <input type="password" id="cred-current-pass" placeholder="Enter current password" autocomplete="off" />
                </div>
            </div>
            <div class="editor-grid" style="margin-top:8px;">
                <div class="editor-field">
                    <label>New Password <span style="font-weight:400;color:var(--text-muted);">(min 6 chars, leave blank to keep same)</span></label>
                    <input type="password" id="cred-new-pass" placeholder="New password" autocomplete="new-password" />
                </div>
                <div class="editor-field">
                    <label>Confirm New Password</label>
                    <input type="password" id="cred-confirm-pass" placeholder="Re-enter new password" autocomplete="new-password" />
                </div>
            </div>
            <div class="editor-actions" style="margin-top:12px;">
                <button class="btn-publish" onclick="changeAdminCredentials()" style="background:linear-gradient(135deg,#ef4444,#dc2626);">🔑 Update Credentials</button>
            </div>
            <p style="font-size:0.72rem;color:var(--text-muted);margin-top:8px;">Changing credentials will log you out and require you to log in again with the new details.</p>
        </div>
        <div class="editor-actions" style="margin-top:16px;">
            <button class="btn-publish" onclick="saveSettings()">💾 Save Settings to Server</button>
            <button class="btn-save-draft" onclick="loadCurrentSettings()">↺ Reload Current</button>
        </div>
        <div id="settings-status" style="margin-top:12px;font-size:0.85rem;display:none;"></div>
    </div>
</div>
