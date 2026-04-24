<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel — Dr. Jay Kothari</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="admin.css" />
</head>
<body>

    <!-- TOAST CONTAINER -->
    <div id="toast-container"></div>

    <!-- === LOGIN === -->
    <div class="login-screen" id="login-screen">
        <div class="login-card">
            <div class="login-logo">Dr. Jay <span>Kothari</span></div>
            <div class="login-label">Admin Panel · Restricted Access</div>
            <div class="login-field">
                <label for="admin-user">Username</label>
                <input type="text" id="admin-user" placeholder="admin" autocomplete="username" />
            </div>
            <div class="login-field">
                <label for="admin-pass">Password</label>
                <input type="password" id="admin-pass" placeholder="••••••••" autocomplete="current-password" />
            </div>
            <button class="login-btn" id="login-btn" onclick="doLogin()">Sign In →</button>
            <div class="login-error" id="login-error"></div>
        </div>
    </div>

    <!-- === ADMIN LAYOUT === -->
    <div class="admin-layout" id="admin-layout" style="display:none;">
        <!-- TOP BAR -->
        <div class="admin-top">
            <div class="admin-top-logo">Dr. Jay <span>Kothari</span> <span
                    style="color:var(--ad-text-muted);font-weight:400;font-size:0.78rem;margin-left:8px;">Admin
                    Panel</span></div>
            <div class="admin-top-right">
                <span class="api-status ok" id="api-status-pill" title="API connection status">API Live</span>
                <a href="index.html" class="admin-view-site" target="_blank">View Site ↗</a>
                <span class="admin-logout" onclick="doLogout()">Sign Out</span>
            </div>
        </div>

        <div class="admin-body">
            <!-- SIDEBAR -->
            <div class="admin-sidebar">
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Overview</div>
                    <div class="admin-nav-link active" data-panel="dashboard" onclick="switchPanel('dashboard')"><span
                            class="nav-icon">📊</span>Dashboard</div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Consultations</div>
                    <div class="admin-nav-link" data-panel="calendar" onclick="switchPanel('calendar')"><span
                            class="nav-icon">🗓️</span>Booking Calendar</div>
                    <div class="admin-nav-link" data-panel="requests" onclick="switchPanel('requests')"><span
                            class="nav-icon">📋</span>Pending Requests <span class="admin-badge"
                            id="badge-requests">0</span></div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Content</div>
                    <div class="admin-nav-link" data-panel="knowledge" onclick="switchPanel('knowledge')"><span
                            class="nav-icon">📚</span>Knowledge Hub</div>
                    <div class="admin-nav-link" data-panel="editor" onclick="switchPanel('editor')"><span
                            class="nav-icon">✏️</span>New Article</div>
                    <div class="admin-nav-link" data-panel="myths" onclick="switchPanel('myths')"><span
                            class="nav-icon">🃏</span>Myth Buster Cards</div>
                    <div class="admin-nav-link" data-panel="quizeditor" onclick="switchPanel('quizeditor')"><span
                            class="nav-icon">🧠</span>Quiz Questions</div>
                    <div class="admin-nav-link" data-panel="research" onclick="switchPanel('research')"><span
                            class="nav-icon">🔬</span>Research Papers</div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Community</div>
                    <div class="admin-nav-link" data-panel="reviews" onclick="switchPanel('reviews')"><span
                            class="nav-icon">⭐</span>Review Approvals <span class="admin-badge"
                            id="badge-reviews">0</span></div>
                    <div class="admin-nav-link" data-panel="photos" onclick="switchPanel('photos')"><span
                            class="nav-icon">🖼️</span>Photo Wall <span class="admin-badge" id="badge-photos">0</span>
                    </div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Memories</div>
                    <div class="admin-nav-link" data-panel="diya" onclick="switchPanel('diya')"><span
                            class="nav-icon">🪔</span>Diya Prayer Wall <span class="admin-badge"
                            id="badge-diyas">0</span></div>
                    <div class="admin-nav-link" data-panel="memories" onclick="switchPanel('memories')"><span
                            class="nav-icon">💝</span>Memories — ICU to Home <span class="admin-badge"
                            id="badge-memories">0</span></div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Growth</div>
                    <div class="admin-nav-link" data-panel="subscribers" onclick="switchPanel('subscribers')"><span class="nav-icon">📧</span>Subscribers</div>
                </div>
                <div class="admin-nav-group">
                    <div class="admin-nav-label">Site Controls</div>
                    <div class="admin-nav-link" data-panel="faq" onclick="switchPanel('faq')"><span
                            class="nav-icon">❓</span>FAQ Manager</div>
                    <div class="admin-nav-link" data-panel="hero" onclick="switchPanel('hero')"><span
                            class="nav-icon">🏠</span>Hero &amp; Content</div>
                    <div class="admin-nav-link" data-panel="images" onclick="switchPanel('images')"><span
                            class="nav-icon">🖼️</span>Site Images</div>
                    <div class="admin-nav-link" data-panel="settings" onclick="switchPanel('settings')"><span
                            class="nav-icon">⚙️</span>Settings &amp; Credentials</div>
                    <div class="admin-nav-link" data-panel="export" onclick="switchPanel('export')"><span
                            class="nav-icon">📦</span>Export / Import</div>
                    <div class="admin-nav-link" data-panel="apihealth" onclick="switchPanel('apihealth')"><span
                            class="nav-icon">📶</span>API Diagnostics</div>
                </div>
            </div>

            <!-- MAIN CONTENT AREA -->
            <div class="admin-main">
                <?php
                $panels = [
                    'dashboard', 'calendar', 'requests', 'knowledge', 'editor',
                    'reviews', 'photos', 'images', 'settings', 'myths',
                    'quizeditor', 'research', 'hero', 'faq', 'export', 'api_health',
                    'diya', 'memories', 'subscribers'
                ];
                foreach ($panels as $panel) {
                    $file = __DIR__ . "/admin/panels/{$panel}.php";
                    if (file_exists($file)) {
                        include $file;
                    } else {
                        echo "<!-- Panel not found: $panel -->";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="admin.js"></script>
</body>
</html>
