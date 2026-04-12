<!-- PHOTO WALL -->
<div class="admin-panel" id="panel-photos">
    <div class="panel-header">
        <h1>Photo Wall</h1>
        <p>Approve submitted photos for the Pulse Photo Wall.</p>
    </div>
    <div class="editor-card">
        <h3>➕ Add Photo to Wall</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>Photo URL / Filename</label><input type="text"
                    id="photo-url" placeholder="img-team.png or https://…" /></div>
            <div class="editor-field"><label>Caption</label><input type="text" id="photo-caption"
                    placeholder="e.g. ICU Team, Apollo Ahmedabad" /></div>
        </div>
        <div class="editor-field"><label>Label Tag</label><input type="text" id="photo-label"
                placeholder="Team / Clinical / Conference / Education…" /></div>
        <button class="btn-publish" onclick="addPhoto()">Add to Photo Wall</button>
    </div>
    <div class="photo-review-grid" id="photo-grid"></div>
</div>
