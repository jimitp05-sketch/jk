<!-- SITE IMAGES -->
<div class="admin-panel" id="panel-images">
    <div class="panel-header">
        <h1>🖼️ Site Images</h1>
        <p>Manage the primary images used across the website. Replacing an image here will update it
            everywhere instantly. Supported formats: JPG, PNG, WebP.</p>
    </div>

    <div class="stats-row">
        <div class="admin-stat">
            <div class="admin-stat-num">5</div>
            <div class="admin-stat-label">Core Site Images</div>
        </div>
    </div>

    <div class="editor-grid">
        <div class="editor-card">
            <h3>🏠 Hero Doctor Image</h3>
            <div class="photo-review-img" style="height: 200px; margin-bottom: 12px;">
                <img src="img-hero-doctor.png" id="prev-img-hero"
                    style="max-height: 100%; object-fit: contain;" />
            </div>
            <div class="editor-field">
                <label>Replace Image</label>
                <input type="file" id="upload-hero" accept="image/*"
                    onchange="previewImage(this, 'prev-img-hero')" />
            </div>
            <button class="btn-publish" onclick="uploadSiteImage('hero', 'upload-hero')">Upload &
                Replace</button>
        </div>

        <div class="editor-card">
            <h3>🫀 ECMO Machine</h3>
            <div class="photo-review-img" style="height: 200px; margin-bottom: 12px;">
                <img src="img-ecmo.png" id="prev-img-ecmo"
                    style="max-height: 100%; object-fit: contain;" />
            </div>
            <div class="editor-field">
                <label>Replace Image</label>
                <input type="file" id="upload-ecmo" accept="image/*"
                    onchange="previewImage(this, 'prev-img-ecmo')" />
            </div>
            <button class="btn-publish" onclick="uploadSiteImage('ecmo', 'upload-ecmo')">Upload &
                Replace</button>
        </div>

        <div class="editor-card">
            <h3>👨‍👩‍👧‍👦 Critical Care Team</h3>
            <div class="photo-review-img" style="height: 200px; margin-bottom: 12px;">
                <img src="img-team.png" id="prev-img-team"
                    style="max-height: 100%; object-fit: contain;" />
            </div>
            <div class="editor-field">
                <label>Replace Image</label>
                <input type="file" id="upload-team" accept="image/*"
                    onchange="previewImage(this, 'prev-img-team')" />
            </div>
            <button class="btn-publish" onclick="uploadSiteImage('team', 'upload-team')">Upload &
                Replace</button>
        </div>

        <div class="editor-card">
            <h3>📚 Knowledge Hub</h3>
            <div class="photo-review-img" style="height: 200px; margin-bottom: 12px;">
                <img src="img-knowledge.png" id="prev-img-knowledge"
                    style="max-height: 100%; object-fit: contain;" />
            </div>
            <div class="editor-field">
                <label>Replace Image</label>
                <input type="file" id="upload-knowledge" accept="image/*"
                    onchange="previewImage(this, 'prev-img-knowledge')" />
            </div>
            <button class="btn-publish"
                onclick="uploadSiteImage('knowledge', 'upload-knowledge')">Upload & Replace</button>
        </div>
    </div>
</div>
