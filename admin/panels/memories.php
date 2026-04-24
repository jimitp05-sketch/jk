<!-- MEMORIES MANAGEMENT PANEL -->
<div class="admin-panel" id="panel-memories" style="display:none;">

<style>
.admin-subtabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.subtab-btn { padding:10px 20px; border:1px solid var(--ad-border); background:var(--ad-surface); color:var(--ad-text-muted); border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:600; transition:var(--ad-transition); font-family:inherit; }
.subtab-btn.active { background:var(--ad-indigo); color:#fff; border-color:var(--ad-indigo); }
.subtab-btn:hover:not(.active) { background:var(--ad-surface-hover); color:var(--ad-text); }
</style>

    <div class="panel-header">
        <div>
            <h1>💝 Memories — ICU to Home</h1>
            <p>Manage healing stories, gratitude notes, and photo memories. User submissions need approval before appearing on the website.</p>
        </div>
    </div>

    <!-- Sub-tabs -->
    <div class="admin-subtabs">
        <button class="subtab-btn active" onclick="switchMemoryTab('stories')">📖 Healing Stories <span class="admin-badge" id="badge-mem-stories" style="display:inline;margin-left:6px;">0</span></button>
        <button class="subtab-btn" onclick="switchMemoryTab('notes')">💌 Gratitude Notes <span class="admin-badge" id="badge-mem-notes" style="display:inline;margin-left:6px;">0</span></button>
        <button class="subtab-btn" onclick="switchMemoryTab('photos')">📸 Photo Memories <span class="admin-badge" id="badge-mem-photos" style="display:inline;margin-left:6px;">0</span></button>
    </div>

    <!-- ======== STORIES TAB ======== -->
    <div class="memory-tab" id="mem-tab-stories">

        <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat">
                <div class="admin-stat-num" id="stories-total">0</div>
                <div class="admin-stat-label">Total</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="stories-pending">0</div>
                <div class="admin-stat-label">Pending</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="stories-approved">0</div>
                <div class="admin-stat-label">Approved</div>
            </div>
        </div>

        <div class="editor-card">
            <h3>➕ Add Healing Story</h3>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Patient Name</label>
                    <input type="text" id="mem-story-patient" placeholder="Patient name" />
                </div>
                <div class="editor-field">
                    <label>Family Name</label>
                    <input type="text" id="mem-story-family" placeholder="e.g., Mehta Family" />
                </div>
            </div>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Relationship</label>
                    <select id="mem-story-relationship">
                        <option value="">Select...</option>
                        <option>Son</option>
                        <option>Daughter</option>
                        <option>Wife</option>
                        <option>Husband</option>
                        <option>Parent</option>
                        <option>Sibling</option>
                        <option>Friend</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="editor-field">
                    <label>Duration</label>
                    <input type="text" id="mem-story-duration" placeholder="e.g., 28 Days in ICU" />
                </div>
            </div>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Tag</label>
                    <select id="mem-story-tag">
                        <option value="">Select tag...</option>
                        <option>ICU Recovery</option>
                        <option>ECMO Survivor</option>
                        <option>Sepsis Recovery</option>
                        <option>Ventilator Recovery</option>
                        <option>CRRT Success</option>
                        <option>Multi-Organ</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="editor-field">
                    <label>Title</label>
                    <input type="text" id="mem-story-title" placeholder="Story title" />
                </div>
            </div>
            <div class="editor-field">
                <label>Story</label>
                <textarea id="mem-story-text" rows="6" placeholder="The family's ICU journey..."></textarea>
            </div>
            <div class="editor-field">
                <label>Highlight Quote</label>
                <input type="text" id="mem-story-quote" placeholder="One powerful line from the story" />
            </div>
            <input type="hidden" id="mem-story-edit-id" value="" />
            <div class="editor-actions">
                <button class="btn-publish" onclick="saveStory()">💾 Save Story</button>
                <button class="btn-save-draft" onclick="clearStoryForm()">Clear</button>
            </div>
        </div>

        <div id="memories-bulk-bar" style="display:none;background:var(--bg-section);border:1px solid var(--border);border-radius:8px;padding:10px 16px;margin-bottom:12px;align-items:center;gap:12px;flex-wrap:wrap;">
            <span id="memories-bulk-count" style="font-weight:600;color:var(--text);">0 selected</span>
            <button class="action-btn action-btn-edit" onclick="bulkModerate('memories','approved')">✅ Approve Selected</button>
            <button class="action-btn action-btn-reject" onclick="bulkModerate('memories','rejected')">❌ Reject Selected</button>
            <button class="action-btn action-btn-reject" onclick="bulkModerate('memories','deleted')" style="margin-left:auto;">🗑 Delete Selected</button>
        </div>
        <div class="admin-table-wrap" style="margin-top:24px;">
            <div class="table-header">
                <div class="table-title">All Healing Stories</div>
                <button class="action-btn action-btn-edit" onclick="adminLoadStories()">↻ Refresh</button>
            </div>
            <table id="stories-table">
                <thead>
                    <tr>
                        <th style="width:32px;"><input type="checkbox" id="memories-select-all" onchange="toggleSelectAll('memories',this.checked)" /></th>
                        <th>Title</th>
                        <th>Family</th>
                        <th>Tag</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="stories-tbody">
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--ad-text-muted);padding:28px;">Loading stories…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======== NOTES TAB ======== -->
    <div class="memory-tab" id="mem-tab-notes" style="display:none;">

        <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat">
                <div class="admin-stat-num" id="notes-total">0</div>
                <div class="admin-stat-label">Total</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="notes-pending">0</div>
                <div class="admin-stat-label">Pending</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="notes-approved">0</div>
                <div class="admin-stat-label">Approved</div>
            </div>
        </div>

        <div class="editor-card">
            <h3>➕ Add Gratitude Note</h3>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Name</label>
                    <input type="text" id="mem-note-name" placeholder="e.g., Sharma Family" />
                </div>
                <div class="editor-field">
                    <label>Relationship</label>
                    <input type="text" id="mem-note-relationship" placeholder="e.g., Son, Daughter" />
                </div>
            </div>
            <div class="editor-field">
                <label>Note</label>
                <textarea id="mem-note-text" rows="4" placeholder="Write the gratitude note..."></textarea>
            </div>
            <div class="editor-actions">
                <button class="btn-publish" onclick="saveNote()">💾 Save Note</button>
                <button class="btn-save-draft" onclick="clearNoteForm()">Clear</button>
            </div>
        </div>

        <div class="admin-table-wrap" style="margin-top:24px;">
            <div class="table-header">
                <div class="table-title">All Gratitude Notes</div>
                <button class="action-btn action-btn-edit" onclick="adminLoadNotes()">↻ Refresh</button>
            </div>
            <table id="notes-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Note</th>
                        <th>Relationship</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="notes-tbody">
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--ad-text-muted);padding:28px;">Loading notes…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======== PHOTOS TAB ======== -->
    <div class="memory-tab" id="mem-tab-photos" style="display:none;">

        <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
            <div class="admin-stat">
                <div class="admin-stat-num" id="photos-total">0</div>
                <div class="admin-stat-label">Total</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="photos-pending">0</div>
                <div class="admin-stat-label">Pending</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-num" id="photos-approved">0</div>
                <div class="admin-stat-label">Approved</div>
            </div>
        </div>

        <div class="editor-card">
            <h3>➕ Upload Photo Memory</h3>
            <div class="editor-grid">
                <div class="editor-field">
                    <label>Uploaded By</label>
                    <input type="text" id="mem-photo-by" placeholder="Name" />
                </div>
                <div class="editor-field">
                    <label>Label</label>
                    <select id="mem-photo-label">
                        <option value="">Select...</option>
                        <option>Team</option>
                        <option>Clinical</option>
                        <option>Celebration</option>
                        <option>Recovery</option>
                        <option>Discharge</option>
                        <option>Conference</option>
                        <option>Other</option>
                    </select>
                </div>
            </div>
            <div class="editor-field">
                <label>Caption</label>
                <input type="text" id="mem-photo-caption" placeholder="Photo caption" />
            </div>
            <div class="editor-field">
                <label>Photo</label>
                <input type="file" id="mem-photo-file" accept="image/*" onchange="previewMemoryPhoto(this)" />
            </div>
            <div id="mem-photo-preview" style="display:none;margin-bottom:12px;">
                <img id="mem-photo-preview-img" style="max-height:150px;border-radius:8px;border:1px solid var(--ad-border);" />
            </div>
            <div class="editor-actions">
                <button class="btn-publish" onclick="uploadMemoryPhoto()">Upload Photo 📸</button>
                <button class="btn-save-draft" onclick="clearPhotoForm()">Clear</button>
            </div>
        </div>

        <div class="admin-table-wrap" style="margin-top:24px;">
            <div class="table-header">
                <div class="table-title">All Photo Memories</div>
                <button class="action-btn action-btn-edit" onclick="adminLoadMemoryPhotos()">↻ Refresh</button>
            </div>
            <div id="photos-grid" style="padding:16px;"></div>
        </div>
    </div>

</div>
