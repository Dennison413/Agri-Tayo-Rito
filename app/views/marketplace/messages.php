<?php
// app/views/marketplace/messages.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../helpers/csrf.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'User';

if (!$isLoggedIn) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// TODO: Fetch conversations from database
// For now, using mock data for UI demonstration
$conversations = [
    [
        'id' => 1,
        'user_name' => 'Mang Juan\'s Farm',
        'user_avatar' => '🧑‍🌾',
        'last_message' => 'Yes, the tomatoes are still available!',
        'timestamp' => '2 min ago',
        'unread_count' => 2,
        'is_online' => true
    ],
    [
        'id' => 2,
        'user_name' => 'Anna\'s Garden',
        'user_avatar' => '👩‍🌾',
        'last_message' => 'I can deliver tomorrow morning',
        'timestamp' => '15 min ago',
        'unread_count' => 0,
        'is_online' => true
    ],
    [
        'id' => 3,
        'user_name' => 'Pedro\'s Produce',
        'user_avatar' => '🧑‍🌾',
        'last_message' => 'Thank you for your order!',
        'timestamp' => '1 hour ago',
        'unread_count' => 0,
        'is_online' => false
    ],
    [
        'id' => 4,
        'user_name' => 'Rosa\'s Organic Farm',
        'user_avatar' => '👩‍🌾',
        'last_message' => 'The vegetables are freshly harvested',
        'timestamp' => '3 hours ago',
        'unread_count' => 1,
        'is_online' => false
    ],
];

$csrfToken = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Messages - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/messages.css">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <style>
        /* ==========================================
   MESSAGES PAGE - MATCHING MARKETPLACE DESIGN
   ========================================== */

/* ==================== CONTAINER ==================== */
.messages-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

/* ==================== HEADER SECTION ==================== */
.messages-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 20px;
}

.header-content {
    flex: 1;
}

.page-title {
    font-size: 1.8rem;
    color: #2d5016;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
}

.title-icon {
    font-size: 2rem;
}

.page-subtitle {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

/* ==================== HEADER ACTIONS ==================== */
.header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #2d5016;
    color: #2d5016;
}

.action-btn span:first-child {
    font-size: 1.1rem;
}

/* ==================== SEARCH BAR ==================== */
.search-bar {
    position: relative;
    margin-bottom: 15px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-input {
    width: 100%;
    padding: 15px 45px 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: #2d5016;
    box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
}

.clear-search {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: #e0e0e0;
    border: none;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.clear-search:hover {
    background: #2d5016;
    color: white;
}

/* ==================== FILTER OPTIONS ==================== */
.filter-options {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
    animation: slideDown 0.3s ease;
}

.filter-chip {
    padding: 8px 18px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    color: #666;
}

.filter-chip:hover {
    border-color: #2d5016;
    color: #2d5016;
}

.filter-chip.active {
    background: #2d5016;
    color: white;
    border-color: #2d5016;
}

/* ==================== CONVERSATIONS LIST ==================== */
.conversations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ==================== CONVERSATION CARD ==================== */
.conversation-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.conversation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
}

.conversation-card.unread {
    background: #f8fdf4;
    border-left: 4px solid #2d5016;
}

/* ==================== AVATAR ==================== */
.conversation-avatar {
    position: relative;
    flex-shrink: 0;
}

.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2d5016, #4a7c25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 15px;
    height: 15px;
    background: #28a745;
    border: 3px solid white;
    border-radius: 50%;
}

/* ==================== CONVERSATION CONTENT ==================== */
.conversation-content {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.user-name {
    font-size: 1rem;
    font-weight: 700;
    color: #2d5016;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.timestamp {
    font-size: 0.75rem;
    color: #999;
    white-space: nowrap;
    margin-left: 10px;
}

.conversation-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.last-message {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.conversation-card.unread .last-message {
    color: #2d5016;
    font-weight: 600;
}

.unread-badge {
    background: #2d5016;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}

/* ==================== EMPTY STATE ==================== */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    max-width: 500px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.empty-title {
    font-size: 1.5rem;
    color: #2d5016;
    margin-bottom: 10px;
    font-weight: 700;
}

.empty-message {
    font-size: 0.95rem;
    color: #666;
    margin-bottom: 25px;
    line-height: 1.6;
}

.browse-btn {
    display: inline-block;
    padding: 12px 30px;
    background: #2d5016;
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.browse-btn:hover {
    background: #1f3810;
    transform: scale(1.05);
}

/* ==========================================
   TABLET RESPONSIVE (768px - 1199px)
   ========================================== */
@media (max-width: 1200px) {
    .messages-container {
        max-width: 900px;
    }
}

@media (max-width: 768px) {
    .messages-container {
        padding: 15px;
    }

    .messages-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
        margin-bottom: 15px;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .title-icon {
        font-size: 1.5rem;
    }

    .header-actions {
        width: 100%;
    }

    .action-btn {
        flex: 1;
        justify-content: center;
    }

    .avatar-circle {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }

    .online-indicator {
        width: 12px;
        height: 12px;
    }

    .user-name {
        font-size: 0.95rem;
    }

    .last-message {
        font-size: 0.85rem;
    }
}

/* ==========================================
   MOBILE RESPONSIVE (480px and below)
   ========================================== */
@media (max-width: 480px) {
    .messages-container {
        padding: 10px;
        margin-bottom: 70px; /* Space for bottom nav */
    }

    .page-title {
        font-size: 1.3rem;
    }

    .page-subtitle {
        font-size: 0.8rem;
    }

    .action-btn {
        padding: 8px 15px;
        font-size: 0.8rem;
    }

    .conversation-card {
        padding: 12px;
        gap: 12px;
    }

    .avatar-circle {
        width: 45px;
        height: 45px;
        font-size: 1.3rem;
    }

    .user-name {
        font-size: 0.9rem;
    }

    .timestamp {
        font-size: 0.7rem;
    }

    .last-message {
        font-size: 0.8rem;
    }

    .unread-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
    }

    .empty-state {
        padding: 60px 15px;
    }

    .empty-icon {
        font-size: 4rem;
    }

    .empty-title {
        font-size: 1.2rem;
    }

    .empty-message {
        font-size: 0.85rem;
    }
}

/* ==========================================
   VERY SMALL MOBILE (360px and below)
   ========================================== */
@media (max-width: 360px) {
    .messages-container {
        padding: 8px;
    }

    .page-title {
        font-size: 1.1rem;
    }

    .action-btn {
        padding: 6px 12px;
        font-size: 0.75rem;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
}

@media (max-height: 500px) and (orientation: landscape) {
    .messages-container {
        padding: 10px;
    }

    .messages-header {
        margin-bottom: 10px;
    }

    .page-title {
        font-size: 1.2rem;
    }

    .conversation-card {
        padding: 10px;
    }

    .empty-state {
        padding: 40px 20px;
    }
}
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <?php include __DIR__ . '/marketnav.php'; ?>
    
    <main class="main-content">
        <div class="messages-container">
            <!-- Messages Header -->
            <div class="messages-header">
                <div class="header-content">
                    <h1 class="page-title">
                        <span class="title-icon">💬</span>
                        Messages
                    </h1>
                    <p class="page-subtitle">Chat with sellers and buyers</p>
                </div>
                <div class="header-actions">
                    <button class="action-btn search-btn" onclick="toggleSearch()">
                        <span>🔍</span>
                        <span>Search</span>
                    </button>
                    <button class="action-btn filter-btn" onclick="toggleFilter()">
                        <span>🔽</span>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <!-- Search Bar (Hidden by default) -->
            <div class="search-bar" id="searchBar" style="display: none;">
                <input type="text" 
                       class="search-input" 
                       id="messageSearch" 
                       placeholder="Search conversations..."
                       onkeyup="searchConversations()">
                <button class="clear-search" onclick="clearSearch()">✕</button>
            </div>

            <!-- Filter Options (Hidden by default) -->
            <div class="filter-options" id="filterOptions" style="display: none;">
                <button class="filter-chip active" data-filter="all">All</button>
                <button class="filter-chip" data-filter="unread">Unread</button>
                <button class="filter-chip" data-filter="online">Online</button>
                <button class="filter-chip" data-filter="archived">Archived</button>
            </div>

            <!-- Conversations List -->
            <div class="conversations-list">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💬</div>
                        <h3 class="empty-title">No Messages Yet</h3>
                        <p class="empty-message">Start a conversation by messaging a seller!</p>
                        <a href="<?php echo BASE_URL; ?>marketplace" class="browse-btn">
                            Browse Products
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="<?php echo BASE_URL; ?>marketplace/chat?conversation=<?php echo $conv['id']; ?>" 
                           class="conversation-card <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                            <div class="conversation-avatar">
                                <div class="avatar-circle">
                                    <?php echo $conv['user_avatar']; ?>
                                </div>
                                <?php if ($conv['is_online']): ?>
                                    <span class="online-indicator"></span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-content">
                                <div class="conversation-header">
                                    <h3 class="user-name"><?php echo htmlspecialchars($conv['user_name']); ?></h3>
                                    <span class="timestamp"><?php echo $conv['timestamp']; ?></span>
                                </div>
                                <div class="conversation-footer">
                                    <p class="last-message">
                                        <?php echo htmlspecialchars($conv['last_message']); ?>
                                    </p>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty Search Results -->
            <div class="empty-state" id="emptySearch" style="display: none;">
                <div class="empty-icon">🔍</div>
                <h3 class="empty-title">No Results Found</h3>
                <p class="empty-message">Try searching with different keywords</p>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
    <script>
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            const isVisible = searchBar.style.display !== 'none';
            searchBar.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                document.getElementById('messageSearch').focus();
            }
        }

        function toggleFilter() {
            const filterOptions = document.getElementById('filterOptions');
            const isVisible = filterOptions.style.display !== 'none';
            filterOptions.style.display = isVisible ? 'none' : 'flex';
        }

        function clearSearch() {
            document.getElementById('messageSearch').value = '';
            searchConversations();
        }

        function searchConversations() {
            const searchTerm = document.getElementById('messageSearch').value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-card');
            let visibleCount = 0;

            conversations.forEach(conv => {
                const userName = conv.querySelector('.user-name').textContent.toLowerCase();
                const lastMessage = conv.querySelector('.last-message').textContent.toLowerCase();
                
                if (userName.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                    conv.style.display = 'flex';
                    visibleCount++;
                } else {
                    conv.style.display = 'none';
                }
            });

            // Show empty state if no results
            document.getElementById('emptySearch').style.display = 
                visibleCount === 0 && searchTerm ? 'block' : 'none';
            document.querySelector('.conversations-list').style.display = 
                visibleCount === 0 && searchTerm ? 'none' : 'block';
        }

        // Filter functionality
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                const conversations = document.querySelectorAll('.conversation-card');

                conversations.forEach(conv => {
                    let shouldShow = true;

                    if (filter === 'unread') {
                        shouldShow = conv.classList.contains('unread');
                    } else if (filter === 'online') {
                        shouldShow = conv.querySelector('.online-indicator') !== null;
                    } else if (filter === 'archived') {
                        shouldShow = false; // TODO: Implement archived filter
                    }

                    conv.style.display = shouldShow ? 'flex' : 'none';
                });
            });
        });
    </script>
</body>
</html>