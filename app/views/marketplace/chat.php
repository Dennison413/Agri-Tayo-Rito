<?php
// app/views/marketplace/chat.php
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

$conversationId = $_GET['conversation'] ?? null;

// TODO: Fetch conversation details and messages from database
// For now, using mock data for UI demonstration
$otherUser = [
    'id' => 5,
    'name' => 'Mang Juan\'s Farm',
    'avatar' => '🧑‍🌾',
    'is_online' => true,
    'last_seen' => 'Active now'
];

$messages = [
    [
        'id' => 1,
        'sender_id' => 5,
        'message' => 'Hello! Are you interested in our fresh tomatoes?',
        'timestamp' => '10:30 AM',
        'is_me' => false,
        'is_read' => true
    ],
    [
        'id' => 2,
        'sender_id' => $userId,
        'message' => 'Yes! How much per kilo?',
        'timestamp' => '10:32 AM',
        'is_me' => true,
        'is_read' => true
    ],
    [
        'id' => 3,
        'sender_id' => 5,
        'message' => 'It\'s ₱80 per kilo. All fresh from our farm this morning!',
        'timestamp' => '10:33 AM',
        'is_me' => false,
        'is_read' => true
    ],
    [
        'id' => 4,
        'sender_id' => $userId,
        'message' => 'Great! Do you have at least 5 kilos available?',
        'timestamp' => '10:35 AM',
        'is_me' => true,
        'is_read' => true
    ],
    [
        'id' => 5,
        'sender_id' => 5,
        'message' => 'Yes, the tomatoes are still available! I have 10 kilos ready.',
        'timestamp' => '10:37 AM',
        'is_me' => false,
        'is_read' => false
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
    <title>Chat - <?php echo htmlspecialchars($otherUser['name']); ?> - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/chat.css">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <style>
.chat-container {
    max-width: 1000px;
    margin: 0 auto;
    height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* ==================== CHAT HEADER ==================== */
.chat-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: white;
    border-bottom: 2px solid #f0f0f0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.back-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f5f5f5;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1.3rem;
    color: #2d5016;
}

.back-btn:hover {
    background: #2d5016;
    color: white;
}

.chat-user-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-wrapper {
    position: relative;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2d5016, #4a7c25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #28a745;
    border: 2px solid white;
    border-radius: 50%;
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d5016;
    margin: 0 0 2px 0;
}

.user-status {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
}

.chat-actions {
    display: flex;
    gap: 8px;
}

.chat-actions .action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f5f5f5;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1.3rem;
}

.chat-actions .action-btn:hover {
    background: #2d5016;
    color: white;
}

/* ==================== CHAT MENU ==================== */
.chat-menu {
    position: absolute;
    top: 70px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    padding: 10px;
    min-width: 200px;
    z-index: 1000;
    animation: slideDown 0.2s ease;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 15px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9rem;
    text-align: left;
    color: #333;
}

.menu-item:hover {
    background: #f5f5f5;
}

.menu-item.danger {
    color: #dc3545;
}

.menu-item.danger:hover {
    background: #fee;
}

.menu-item span {
    font-size: 1.2rem;
}

/* ==================== MESSAGES AREA ==================== */
.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: linear-gradient(135deg, #f8fdf4 0%, #ffffff 100%);
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Custom scrollbar */
.messages-area::-webkit-scrollbar {
    width: 8px;
}

.messages-area::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.messages-area::-webkit-scrollbar-thumb {
    background: #2d5016;
    border-radius: 4px;
}

.messages-area::-webkit-scrollbar-thumb:hover {
    background: #1f3810;
}

/* ==================== DATE DIVIDER ==================== */
.date-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px 0;
}

.date-divider span {
    background: white;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 0.75rem;
    color: #666;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ==================== MESSAGE WRAPPER ==================== */
.message-wrapper {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    max-width: 75%;
    animation: messageSlide 0.3s ease;
}

@keyframes messageSlide {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-wrapper.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-wrapper.received {
    align-self: flex-start;
}

.message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2d5016, #4a7c25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

/* ==================== MESSAGE BUBBLE ==================== */
.message-bubble {
    background: white;
    padding: 12px 16px;
    border-radius: 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    max-width: 100%;
    word-wrap: break-word;
}

.message-wrapper.sent .message-bubble {
    background: #2d5016;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-wrapper.received .message-bubble {
    background: white;
    border-bottom-left-radius: 4px;
}

.message-text {
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

.message-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    margin-top: 5px;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.message-status {
    font-size: 0.8rem;
    color: #28a745;
}

.message-wrapper.sent .message-meta {
    justify-content: flex-end;
}

.message-wrapper.received .message-meta {
    justify-content: flex-start;
}

/* ==================== TYPING INDICATOR ==================== */
.typing-indicator {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    max-width: 75%;
    animation: messageSlide 0.3s ease;
}

.typing-bubble {
    background: white;
    padding: 12px 16px;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    gap: 5px;
    align-items: center;
}

.typing-bubble span {
    width: 8px;
    height: 8px;
    background: #2d5016;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-bubble span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-bubble span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.5;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

/* ==================== MESSAGE INPUT ==================== */
.message-input-container {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    padding: 15px 20px;
    background: white;
    border-top: 2px solid #f0f0f0;
}

.attach-btn,
.emoji-btn,
.send-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.attach-btn,
.emoji-btn {
    background: #f5f5f5;
    color: #666;
}

.attach-btn:hover,
.emoji-btn:hover {
    background: #e0e0e0;
    transform: scale(1.1);
}

.send-btn {
    background: #2d5016;
    color: white;
}

.send-btn:hover {
    background: #1f3810;
    transform: scale(1.1);
}

.input-wrapper {
    flex: 1;
}

.message-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 22px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: none;
    min-height: 42px;
    max-height: 120px;
    overflow-y: auto;
    transition: all 0.3s;
}

.message-input:focus {
    outline: none;
    border-color: #2d5016;
    box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
}

/* ==================== EMOJI PICKER ==================== */
.emoji-picker {
    position: absolute;
    bottom: 80px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    padding: 15px;
    z-index: 1000;
    animation: slideUp 0.2s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.emoji-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    max-width: 300px;
}

.emoji {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    font-size: 1.5rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
}

.emoji:hover {
    background: #f5f5f5;
    transform: scale(1.2);
}

/* ==========================================
   TABLET RESPONSIVE (768px - 1199px)
   ========================================== */
@media (max-width: 1200px) {
    .chat-container {
        max-width: 900px;
    }
}

@media (max-width: 768px) {
    .chat-container {
        height: calc(100vh - 60px);
        border-radius: 0;
    }

    .chat-header {
        padding: 12px 15px;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        font-size: 1.5rem;
    }

    .user-name {
        font-size: 1rem;
    }

    .user-status {
        font-size: 0.75rem;
    }

    .messages-area {
        padding: 15px;
    }

    .message-wrapper {
        max-width: 85%;
    }

    .message-bubble {
        padding: 10px 14px;
    }

    .message-text {
        font-size: 0.9rem;
    }

    .message-input-container {
        padding: 12px 15px;
    }

    .emoji-picker {
        right: 15px;
    }

    .emoji-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

/* ==========================================
   MOBILE RESPONSIVE (480px and below)
   ========================================== */
@media (max-width: 480px) {
    .chat-container {
        height: calc(100vh - 130px); /* Account for bottom nav */
        margin-bottom: 70px;
    }

    .chat-header {
        padding: 10px 12px;
    }

    .back-btn {
        width: 35px;
        height: 35px;
        font-size: 1.1rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.3rem;
    }

    .online-dot {
        width: 10px;
        height: 10px;
    }

    .user-name {
        font-size: 0.95rem;
    }

    .user-status {
        font-size: 0.7rem;
    }

    .chat-actions .action-btn {
        width: 35px;
        height: 35px;
        font-size: 1.1rem;
    }

    .messages-area {
        padding: 12px;
        gap: 12px;
    }

    .message-wrapper {
        max-width: 90%;
    }

    .message-avatar {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }

    .message-bubble {
        padding: 10px 12px;
        font-size: 0.85rem;
    }

    .message-time {
        font-size: 0.65rem;
    }

    .message-input-container {
        padding: 10px 12px;
        gap: 8px;
    }

    .attach-btn,
    .emoji-btn,
    .send-btn {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
    }

    .message-input {
        padding: 10px 12px;
        font-size: 0.9rem;
        min-height: 38px;
    }

    .emoji-picker {
        bottom: 70px;
        right: 12px;
        padding: 12px;
    }

    .emoji-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .emoji {
        width: 35px;
        height: 35px;
        font-size: 1.3rem;
    }

    .chat-menu {
        right: 12px;
        min-width: 180px;
    }

    .menu-item {
        padding: 10px 12px;
        font-size: 0.85rem;
    }
}

/* ==========================================
   VERY SMALL MOBILE (360px and below)
   ========================================== */
@media (max-width: 360px) {
    .chat-header {
        padding: 8px 10px;
    }

    .user-avatar {
        width: 35px;
        height: 35px;
        font-size: 1.2rem;
    }

    .user-name {
        font-size: 0.9rem;
    }

    .message-bubble {
        padding: 8px 10px;
        font-size: 0.8rem;
    }

    .message-input {
        font-size: 0.85rem;
        padding: 8px 10px;
    }

    .emoji-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-height: 500px) and (orientation: landscape) {
    .chat-container {
        height: calc(100vh - 40px);
    }

    .chat-header {
        padding: 8px 15px;
    }

    .user-avatar {
        width: 35px;
        height: 35px;
    }

    .messages-area {
        padding: 10px;
    }

    .message-input-container {
        padding: 10px 15px;
    }

    .message-input {
        max-height: 80px;
    }

    .emoji-picker {
        max-height: 200px;
        overflow-y: auto;
    }
}
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <?php include __DIR__ . '/marketnav.php'; ?>
    
    <main class="main-content">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <button class="back-btn" onclick="goBack()">
                    <span><</span>
                </button>
                <div class="chat-user-info">
                    <div class="user-avatar-wrapper">
                        <div class="user-avatar">
                            <?php echo $otherUser['avatar']; ?>
                        </div>
                        <?php if ($otherUser['is_online']): ?>
                            <span class="online-dot"></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <h2 class="user-name"><?php echo htmlspecialchars($otherUser['name']); ?></h2>
                        <p class="user-status">
                            <?php echo $otherUser['is_online'] ? 'Active now' : $otherUser['last_seen']; ?>
                        </p>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="action-btn" onclick="viewProfile()" title="View Profile">
                        <span>👤</span>
                    </button>
                    <button class="action-btn" onclick="toggleChatMenu()" title="More options">
                        <span>⋮</span>
                    </button>
                </div>
            </div>

            <!-- Chat Menu (Hidden by default) -->
            <div class="chat-menu" id="chatMenu" style="display: none;">
                <button class="menu-item" onclick="viewShop()">
                    <span>🏪</span> View Shop
                </button>
                <button class="menu-item" onclick="reportConversation()">
                    <span>⚠️</span> Report
                </button>
                <button class="menu-item danger" onclick="blockUser()">
                    <span>🚫</span> Block User
                </button>
            </div>

            <!-- Messages Area -->
            <div class="messages-area" id="messagesArea">
                <div class="date-divider">
                    <span>Today</span>
                </div>

                <?php foreach ($messages as $msg): ?>
                    <div class="message-wrapper <?php echo $msg['is_me'] ? 'sent' : 'received'; ?>">
                        <?php if (!$msg['is_me']): ?>
                            <div class="message-avatar">
                                <?php echo $otherUser['avatar']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="message-bubble">
                            <p class="message-text"><?php echo htmlspecialchars($msg['message']); ?></p>
                            <div class="message-meta">
                                <span class="message-time"><?php echo $msg['timestamp']; ?></span>
                                <?php if ($msg['is_me']): ?>
                                    <span class="message-status">
                                        <?php echo $msg['is_read'] ? '✓✓' : '✓'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Typing Indicator (Hidden by default) -->
                <div class="typing-indicator" id="typingIndicator" style="display: none;">
                    <div class="message-avatar">
                        <?php echo $otherUser['avatar']; ?>
                    </div>
                    <div class="typing-bubble">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="message-input-container">
                <button class="attach-btn" onclick="attachFile()" title="Attach file">
                    <span>📎</span>
                </button>
                <div class="input-wrapper">
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type a message..."
                        rows="1"
                        onkeydown="handleKeyPress(event)"
                        oninput="autoResize(this)"></textarea>
                </div>
                <button class="emoji-btn" onclick="toggleEmojiPicker()" title="Add emoji">
                    <span>😊</span>
                </button>
                <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Send message">
                    <span>➤</span>
                </button>
            </div>

            <!-- Emoji Picker (Hidden by default) -->
            <div class="emoji-picker" id="emojiPicker" style="display: none;">
                <div class="emoji-grid">
                    <button class="emoji" onclick="insertEmoji('😊')">😊</button>
                    <button class="emoji" onclick="insertEmoji('😂')">😂</button>
                    <button class="emoji" onclick="insertEmoji('❤️')">❤️</button>
                    <button class="emoji" onclick="insertEmoji('👍')">👍</button>
                    <button class="emoji" onclick="insertEmoji('🙏')">🙏</button>
                    <button class="emoji" onclick="insertEmoji('👏')">👏</button>
                    <button class="emoji" onclick="insertEmoji('🔥')">🔥</button>
                    <button class="emoji" onclick="insertEmoji('✨')">✨</button>
                    <button class="emoji" onclick="insertEmoji('🌟')">🌟</button>
                    <button class="emoji" onclick="insertEmoji('🎉')">🎉</button>
                    <button class="emoji" onclick="insertEmoji('🥳')">🥳</button>
                    <button class="emoji" onclick="insertEmoji('😍')">😍</button>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
    <script>
        // Auto-scroll to bottom on load
        window.addEventListener('load', function() {
            scrollToBottom();
        });

        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        function goBack() {
            window.location.href = '<?php echo BASE_URL; ?>marketplace/messages';
        }

        function toggleChatMenu() {
            const menu = document.getElementById('chatMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        function viewProfile() {
            // TODO: Navigate to user profile
            alert('View profile functionality coming soon!');
        }

        function viewShop() {
            // TODO: Navigate to shop
            alert('View shop functionality coming soon!');
        }

        function reportConversation() {
            if (confirm('Are you sure you want to report this conversation?')) {
                alert('Report functionality coming soon!');
            }
        }

        function blockUser() {
            if (confirm('Are you sure you want to block this user? You will no longer receive messages from them.')) {
                alert('Block functionality coming soon!');
            }
        }

        function attachFile() {
            alert('File attachment coming soon!');
        }

        function toggleEmojiPicker() {
            const picker = document.getElementById('emojiPicker');
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        }

        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
            toggleEmojiPicker();
        }

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (message === '') return;

            // TODO: Send message via AJAX/WebSocket
            console.log('Sending message:', message);

            // Add message to UI (temporary - will be replaced with real-time messaging)
            addMessageToUI(message, true);

            // Clear input
            input.value = '';
            input.style.height = 'auto';

            // Show typing indicator for demo
            setTimeout(() => {
                showTypingIndicator();
                setTimeout(() => {
                    hideTypingIndicator();
                    // Simulate response
                    addMessageToUI('Thank you for your message! I will get back to you shortly.', false);
                }, 2000);
            }, 1000);
        }

        function addMessageToUI(text, isSent) {
            const messagesArea = document.getElementById('messagesArea');
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

            const messageHTML = `
                <div class="message-wrapper ${isSent ? 'sent' : 'received'}">
                    ${!isSent ? '<div class="message-avatar"><?php echo $otherUser['avatar']; ?></div>' : ''}
                    <div class="message-bubble">
                        <p class="message-text">${escapeHtml(text)}</p>
                        <div class="message-meta">
                            <span class="message-time">${timeStr}</span>
                            ${isSent ? '<span class="message-status">✓</span>' : ''}
                        </div>
                    </div>
                </div>
            `;

            const typingIndicator = document.getElementById('typingIndicator');
            messagesArea.insertBefore(createElementFromHTML(messageHTML), typingIndicator);
            scrollToBottom();
        }

        function createElementFromHTML(htmlString) {
            const div = document.createElement('div');
            div.innerHTML = htmlString.trim();
            return div.firstChild;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'flex';
            scrollToBottom();
        }

        function hideTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'none';
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('chatMenu');
            const emojiPicker = document.getElementById('emojiPicker');
            
            if (!event.target.closest('.chat-actions') && !event.target.closest('.chat-menu')) {
                menu.style.display = 'none';
            }
            
            if (!event.target.closest('.emoji-btn') && !event.target.closest('.emoji-picker')) {
                emojiPicker.style.display = 'none';
            }
        });

        // TODO: Initialize WebSocket connection for real-time messaging
        // See implementation guide below
    </script>
</body>
</html>