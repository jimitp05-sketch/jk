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
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Title</label>
            <input type="text" id="st-hero-title" placeholder="When Seconds Define Survival.">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Tagline</label>
            <textarea id="st-hero-tagline" placeholder="Gujarat's frontline of critical care..."
                style="min-height:80px;"></textarea>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Hero Empathy Message</label>
            <input type="text" id="st-hero-empathy"
                placeholder="We know you're scared. You're in the right place.">
        </div>
        <div
            style="margin: 24px 0 12px; border-top: 1px solid var(--border-subtle); padding-top: 20px;">
            <h3 style="margin-bottom:12px;">👤 Admin Login Credentials</h3>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Login Username</label>
                    <input type="text" id="st-admin-user" placeholder="admin" />
                </div>
                <div class="editor-field">
                    <label>Current/New Password</label>
                    <input type="password" id="st-admin-pass" placeholder="••••••••" />
                </div>
            </div>
            <p style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">💡 Changing these will
                require you to log in again with the new details.</p>
        </div>
        <div class="editor-actions" style="margin-top:16px;">
            <button class="btn-publish" onclick="saveSettings()">💾 Save Settings to Server</button>
            <button class="btn-save-draft" onclick="loadCurrentSettings()">↺ Reload Current</button>
        </div>
        <div id="settings-status" style="margin-top:12px;font-size:0.85rem;display:none;"></div>
    </div>
</div>
