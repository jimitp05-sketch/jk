<!-- HERO & CONTENT CONTROLS -->
<div class="admin-panel" id="panel-hero">
    <div class="panel-header">
        <div>
            <h1>🏠 Hero &amp; Content Controls</h1>
            <p>Edit the homepage hero, ticker, speed dial buttons, and live stats.</p>
        </div>
    </div>
    <div class="editor-card" style="margin-top:0;">
        <h3>🦸 Hero Section</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>Doctor Name (Site Title)</label><input type="text"
                    id="hc-site-name" placeholder="Dr. Jay Kothari" /></div>
            <div class="editor-field"><label>Hero Badge Text</label><input type="text"
                    id="hc-hero-badge" placeholder="Gujarat's Leading Critical Care Specialist" /></div>
        </div>
        <div class="editor-field"><label>Hero Headline</label><input type="text" id="hc-hero-title"
                placeholder="When Seconds Define Survival." /></div>
        <div class="editor-field"><label>Hero Tagline</label><textarea id="hc-hero-tagline" rows="2"
                placeholder="Gujarat's frontline of critical care..."></textarea></div>
        <div class="editor-field"><label>Empathy Message</label><input type="text" id="hc-hero-empathy"
                placeholder="We know you're scared. You're in the right place." /></div>
    </div>
    <div class="editor-card">
        <h3>📊 Homepage Stats Band</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>Stat 1 — Number</label><input type="text" id="hc-stat1-num"
                    placeholder="500+" /></div>
            <div class="editor-field"><label>Stat 1 — Label</label><input type="text" id="hc-stat1-lbl"
                    placeholder="Lives Saved" /></div>
            <div class="editor-field"><label>Stat 2 — Number</label><input type="text" id="hc-stat2-num"
                    placeholder="15+" /></div>
            <div class="editor-field"><label>Stat 2 — Label</label><input type="text" id="hc-stat2-lbl"
                    placeholder="Years Experience" /></div>
            <div class="editor-field"><label>Stat 3 — Number</label><input type="text" id="hc-stat3-num"
                    placeholder="24/7" /></div>
            <div class="editor-field"><label>Stat 3 — Label</label><input type="text" id="hc-stat3-lbl"
                    placeholder="ICU Coverage" /></div>
            <div class="editor-field"><label>Stat 4 — Number</label><input type="text" id="hc-stat4-num"
                    placeholder="98%" /></div>
            <div class="editor-field"><label>Stat 4 — Label</label><input type="text" id="hc-stat4-lbl"
                    placeholder="Patient Satisfaction" /></div>
        </div>
    </div>
    <div class="editor-card">
        <h3>📢 Announcement Ticker</h3>
        <div class="editor-field"><label>Ticker Text</label><input type="text" id="hc-ticker"
                placeholder="🏆 Apollo Hospitals Ahmedabad ICU · Now accepting OPD consultations" />
        </div>
        <label
            style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.84rem;color:var(--ad-text-muted);"><input
                type="checkbox" id="hc-ticker-on" style="width:auto;accent-color:var(--ad-indigo);" />
            Show ticker on homepage</label>
    </div>
    <div class="editor-card">
        <h3>📲 Speed Dial / Contact Buttons</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>WhatsApp Number (no +)</label><input type="text"
                    id="hc-wa-number" placeholder="919999999999" /></div>
            <div class="editor-field"><label>WhatsApp Pre-filled Message</label><input type="text"
                    id="hc-wa-msg" placeholder="Hello Dr. Jay Kothari, I would like a consultation" />
            </div>
            <div class="editor-field"><label>Emergency ICU Phone</label><input type="text"
                    id="hc-icu-phone" placeholder="18605001066" /></div>
            <div class="editor-field"><label>OPD Booking Link</label><input type="text" id="hc-opd-link"
                    placeholder="https://booking.apollohospitals.com/..." /></div>
        </div>
    </div>
    <div class="editor-actions" style="margin-top:4px;">
        <button class="btn-publish" onclick="saveHeroContent()">💾 Save to Server</button>
        <button class="btn-save-draft" onclick="loadHeroContent()">↺ Reload</button>
    </div>
    <div id="hero-status" style="margin-top:12px;display:none;"></div>
</div>
