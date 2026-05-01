<!-- SOCIAL MEDIA LINKS -->
<div class="admin-panel" id="panel-social">
    <div class="panel-header">
        <h1>🔗 Social Media Profiles</h1>
        <p>Add your social media profile URLs. These appear on the Pulse page as linked profile cards, and can be used to embed recent posts.</p>
    </div>
    <div class="editor-card">
        <h3>📱 Social Profile Links</h3>
        <p style="font-size:0.8rem;color:var(--ad-text-muted);margin-bottom:16px;">Enter the full URL for each platform. Leave blank to hide that platform.</p>
        <div class="editor-grid">
            <div class="editor-field">
                <label>🔵 LinkedIn Profile / Company Page</label>
                <input type="url" id="social-linkedin" placeholder="https://www.linkedin.com/in/dr-jay-kothari" />
            </div>
            <div class="editor-field">
                <label>📘 Facebook Page</label>
                <input type="url" id="social-facebook" placeholder="https://www.facebook.com/drjaykothari" />
            </div>
            <div class="editor-field">
                <label>📸 Instagram Profile</label>
                <input type="url" id="social-instagram" placeholder="https://www.instagram.com/drjaykothari" />
            </div>
            <div class="editor-field">
                <label>𝕏 X / Twitter Profile</label>
                <input type="url" id="social-twitter" placeholder="https://x.com/drjaykothari" />
            </div>
            <div class="editor-field">
                <label>▶️ YouTube Channel</label>
                <input type="url" id="social-youtube" placeholder="https://www.youtube.com/@drjaykothari" />
            </div>
            <div class="editor-field">
                <label>🌐 Google Business Profile</label>
                <input type="url" id="social-google" placeholder="https://g.page/..." />
            </div>
        </div>
    </div>
    <div class="editor-card">
        <h3>📌 Pinned Post Embeds</h3>
        <p style="font-size:0.8rem;color:var(--ad-text-muted);margin-bottom:16px;">
            Paste embed URLs to feature specific posts on the Pulse page. Up to 6 pinned posts.
            <br /><strong>How to get embed URLs:</strong>
        </p>
        <ul style="font-size:0.78rem;color:var(--ad-text-muted);margin:0 0 16px 16px;line-height:1.7;">
            <li><strong>LinkedIn:</strong> Click "..." on a post → "Copy link to post" → paste the URL</li>
            <li><strong>Facebook:</strong> Click "..." on a post → "Embed" → copy the post URL</li>
            <li><strong>Instagram:</strong> Click "..." on a post → "Copy link" → paste the URL</li>
            <li><strong>X/Twitter:</strong> Click share → "Copy link to post" → paste the URL</li>
            <li><strong>YouTube:</strong> Click "Share" → copy the URL</li>
        </ul>
        <div id="pinned-posts-list">
            <div class="pinned-post-row" data-index="0">
                <div class="editor-grid" style="align-items:end;">
                    <div class="editor-field" style="flex:2;">
                        <label>Post URL</label>
                        <input type="url" class="pinned-post-url" placeholder="https://www.linkedin.com/posts/..." />
                    </div>
                    <div class="editor-field" style="flex:1;">
                        <label>Platform</label>
                        <select class="pinned-post-platform">
                            <option value="linkedin">LinkedIn</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">X / Twitter</option>
                            <option value="youtube">YouTube</option>
                        </select>
                    </div>
                    <div class="editor-field" style="flex:1.5;">
                        <label>Caption (optional)</label>
                        <input type="text" class="pinned-post-caption" placeholder="Brief description..." />
                    </div>
                </div>
            </div>
        </div>
        <button class="btn-save-draft" onclick="addPinnedPostRow()" style="margin-top:12px;">+ Add Another Post</button>
    </div>
    <div class="editor-card">
        <h3>🔌 Facebook Page Plugin (Auto-Feed)</h3>
        <p style="font-size:0.8rem;color:var(--ad-text-muted);margin-bottom:16px;">
            If you have a Facebook Page, you can embed a live feed of your posts directly on the Pulse page. No API key required.
            <br />Enter your Facebook Page URL (e.g., <code>https://www.facebook.com/drjaykothari</code>).
        </p>
        <div class="editor-field">
            <label>Facebook Page URL for Auto-Feed</label>
            <input type="url" id="social-fb-feed-url" placeholder="https://www.facebook.com/drjaykothari" />
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.84rem;color:var(--ad-text-muted);margin-top:8px;">
            <input type="checkbox" id="social-fb-feed-on" style="width:auto;accent-color:var(--ad-indigo);" />
            Show Facebook feed on Pulse page
        </label>
    </div>
    <div class="editor-actions" style="margin-top:4px;">
        <button class="btn-publish" onclick="saveSocialSettings()">💾 Save Social Settings</button>
        <button class="btn-save-draft" onclick="loadSocialSettings()">↺ Reload</button>
    </div>
    <div id="social-status" style="margin-top:12px;display:none;"></div>
</div>
