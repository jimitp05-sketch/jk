<!-- QUIZ QUESTION EDITOR -->
<div class="admin-panel" id="panel-quizeditor">
    <div class="panel-header">
        <h1>🧠 Quiz Questions</h1>
        <p>Manage the 50-question bank. 10 random questions are picked each quiz attempt.</p>
    </div>
    <div class="editor-card">
        <h3>➕ Add / Edit Question</h3>
        <input type="hidden" id="q-edit-id" value="" />
        <div class="editor-field"><label>Question</label><input type="text" id="q-question"
                placeholder="What does ECMO stand for?" /></div>
        <div class="editor-grid">
            <div class="editor-field"><label>Option A</label><input type="text" id="q-a"
                    placeholder="Option A" /></div>
            <div class="editor-field"><label>Option B</label><input type="text" id="q-b"
                    placeholder="Option B" /></div>
            <div class="editor-field"><label>Option C</label><input type="text" id="q-c"
                    placeholder="Option C (optional)" /></div>
            <div class="editor-field"><label>Option D</label><input type="text" id="q-d"
                    placeholder="Option D (optional)" /></div>
        </div>
        <div class="editor-grid">
            <div class="editor-field"><label>Correct Answer (0=A, 1=B, 2=C, 3=D)</label>
                <select id="q-correct">
                    <option value="0">A</option>
                    <option value="1">B</option>
                    <option value="2">C</option>
                    <option value="3">D</option>
                </select>
            </div>
        </div>
        <div class="editor-field"><label>Explanation</label><textarea id="q-explanation" rows="2"
                placeholder="Why this answer is correct…"></textarea></div>
        <div class="editor-actions" style="margin-top:8px;">
            <button class="btn-publish" onclick="saveQuizQuestion()">💾 Save Question</button>
            <button class="btn-save-draft" onclick="clearQuizForm()">Clear</button>
            <button class="btn-save-draft" onclick="resetQuizToDefault()" style="color:#C0392B;">↺ Reset
                to 50 Defaults</button>
        </div>
    </div>
    <div class="admin-table-wrap" style="margin-top:24px;">
        <div class="table-header">
            <div class="table-title">Question Bank</div><span id="quiz-q-count"
                style="font-size:0.8rem;color:var(--text-muted);"></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Question</th>
                    <th>Correct</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="quiz-q-table"></tbody>
        </table>
    </div>
</div>
