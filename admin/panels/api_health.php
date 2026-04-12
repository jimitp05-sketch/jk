<!-- API DIAGNOSTICS -->
<div class="admin-panel" id="panel-apihealth">
    <div class="panel-header">
        <h1>📶 API Diagnostics</h1>
        <p>Live endpoint checks for all backend PHP files.</p>
    </div>
    <div id="api-check-results" style="display:grid;gap:14px;"></div>
    <div class="editor-card" style="margin-top:24px;">
        <h3>🔧 Manual API Tester</h3>
        <div class="editor-grid">
            <div class="editor-field"><label>Endpoint</label>
                <select id="test-endpoint">
                    <option value="./api/content.php">content.php (GET all)</option>
                    <option value="./api/content.php?type=myth_busters">myth_busters</option>
                    <option value="./api/content.php?type=quiz_questions">quiz_questions</option>
                    <option value="./api/content.php?type=research_papers">research_papers</option>
                    <option value="./api/content.php?type=knowledge_articles">knowledge_articles
                    </option>
                    <option value="./api/settings.php">settings.php</option>
                </select>
            </div>
            <div class="editor-field"><label>Method</label><select id="test-method">
                    <option>GET</option>
                    <option>POST</option>
                </select></div>
        </div>
        <div class="editor-field"><label>POST Body (JSON)</label><textarea id="test-body" rows="3"
                placeholder='{"admin_pass":"apollo2024","type":"myth_busters","items":[]}'></textarea>
        </div>
        <div class="editor-actions"><button class="btn-publish" onclick="runManualTest()">▶ Run
                Test</button></div>
        <pre id="test-result"
            style="margin-top:14px;background:rgba(0,0,0,0.4);border:1px solid var(--ad-border);border-radius:8px;padding:14px;font-size:0.76rem;color:var(--ad-teal);overflow-x:auto;white-space:pre-wrap;display:none;"></pre>
    </div>
</div>
