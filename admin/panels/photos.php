<!-- PHOTO WALL -->
<div class="admin-panel" id="panel-photos">
    <div class="panel-header">
        <h1>🖼️ Photo Wall</h1>
        <p>Upload and manage photos displayed on the public <a href="reviews.html#photo-wall" target="_blank" rel="noopener">Pulse Photo Wall</a>. Supports direct file upload or URL.</p>
    </div>
    <div class="editor-card">
        <h3>+ Add Photo</h3>
        <div class="editor-grid">
            <div class="editor-field">
                <label>Upload Photo File</label>
                <input type="file" id="photo-file" accept="image/*" onchange="previewPhotoUpload(this)" style="color:var(--ad-text);" />
                <div id="photo-file-preview" style="margin-top:8px;display:none;">
                    <img id="photo-file-img" style="max-height:120px;border-radius:8px;object-fit:cover;" />
                </div>
            </div>
            <div class="editor-field">
                <label>— OR — Photo URL</label>
                <input type="text" id="photo-url" placeholder="https://… or img-team.png" />
            </div>
        </div>
        <div class="editor-grid">
            <div class="editor-field">
                <label>Caption *</label>
                <input type="text" id="photo-caption" placeholder="e.g. ICU Team, Apollo Ahmedabad" />
            </div>
            <div class="editor-field">
                <label>Category Label</label>
                <select id="photo-label" style="width:100%;padding:10px;border-radius:8px;border:1px solid var(--ad-border);background:var(--ad-bg);color:var(--ad-text);">
                    <option value="Clinical">Clinical</option>
                    <option value="Team">Team</option>
                    <option value="Conference">Conference</option>
                    <option value="Academic">Academic</option>
                    <option value="Rounds">Rounds</option>
                    <option value="Equipment">Equipment</option>
                    <option value="General">General</option>
                </select>
            </div>
        </div>
        <div class="editor-actions">
            <button class="btn-publish" onclick="addPhotoWithUpload()">Add to Photo Wall</button>
            <button class="btn-save-draft" onclick="clearPhotoForm()">Clear</button>
        </div>
    </div>
    <div class="photo-review-grid" id="photo-grid"></div>
</div>
