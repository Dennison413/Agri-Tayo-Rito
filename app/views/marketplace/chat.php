<?php
// chat.php
session_start();
require_once __DIR__ . '/../../config/config.php';

$threadId = $_GET['thread_id'] ?? 1;
$userRole = $_SESSION['user_role'] ?? 'buyer';
$userId = $_SESSION['user_id'] ?? null;

// Mock conversation data - replace with database queries
$otherUser = [
    'name' => 'Fresh Farm Store',
    'avatar' => BASE_URL . 'images/avatars/shop1.jpg',
    'is_online' => true,
    'last_seen' => 'Active now'
];

// Mock messages - replace with database queries
$messages = [
    [
        'id' => 1,
        'sender_id' => 2,
        'sender_name' => 'Fresh Farm Store',
        'message' => 'Hello! Thank you for your interest in our products. How can I help you today?',
        'timestamp' => '10:30 AM',
        'is_mine' => false,
        'type' => 'text'
    ],
    [
        'id' => 2,
        'sender_id' => $userId,
        'sender_name' => 'You',
        'message' => 'Hi! I would like to know more about your organic vegetables.',
        'timestamp' => '10:32 AM',
        'is_mine' => true,
        'type' => 'text'
    ],
    [
        'id' => 3,
        'sender_id' => 2,
        'sender_name' => 'Fresh Farm Store',
        'message' => 'Great! Here are some photos of our fresh harvest today:',
        'timestamp' => '10:33 AM',
        'is_mine' => false,
        'type' => 'text'
    ],
    [
        'id' => 4,
        'sender_id' => 2,
        'sender_name' => 'Fresh Farm Store',
        'image_url' => BASE_URL . 'images/products/vegetables.jpg',
        'timestamp' => '10:33 AM',
        'is_mine' => false,
        'type' => 'image'
    ],
    [
        'id' => 5,
        'sender_id' => $userId,
        'sender_name' => 'You',
        'message' => 'Those look amazing! Do you have a price list?',
        'timestamp' => '10:35 AM',
        'is_mine' => true,
        'type' => 'text'
    ],
    [
        'id' => 6,
        'sender_id' => 2,
        'sender_name' => 'Fresh Farm Store',
        'file_name' => 'Price_List_2024.pdf',
        'file_size' => '245 KB',
        'timestamp' => '10:36 AM',
        'is_mine' => false,
        'type' => 'file'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo htmlspecialchars($otherUser['name']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <style>
        .chat-container {
            max-width: 1000px;
            margin: 0 auto;
            height: calc(100vh - 145px);
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: #2d5016;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .chat-avatar-wrapper {
            position: relative;
        }

        .chat-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .chat-online-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #28a745;
            border: 2px solid #2d5016;
            border-radius: 50%;
        }

        .chat-user-details h3 {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .chat-user-details p {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
        }

        .chat-action-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .chat-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message-group {
            margin-bottom: 20px;
        }

        .message-bubble {
            max-width: 70%;
            margin-bottom: 8px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-bubble.mine {
            margin-left: auto;
        }

        .message-content {
            background: white;
            padding: 12px 15px;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
        }

        .message-bubble.mine .message-content {
            background: #2d5016;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-bubble:not(.mine) .message-content {
            border-bottom-left-radius: 4px;
        }

        .message-image {
            max-width: 100%;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        .message-file {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .message-bubble.mine .message-file {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .message-file:hover {
            transform: translateX(5px);
        }

        .file-icon {
            width: 40px;
            height: 40px;
            background: #2d5016;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .message-bubble.mine .file-icon {
            background: rgba(255, 255, 255, 0.3);
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .file-size {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .message-timestamp {
            font-size: 0.7rem;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }

        .message-bubble.mine .message-timestamp {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-input-area {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .attachment-btn {
            background: #f0f7ed;
            border: none;
            color: #2d5016;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .attachment-btn:hover {
            background: #2d5016;
            color: white;
        }

        .chat-input-container {
            flex: 1;
            background: #f8f9fa;
            border-radius: 25px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            border: none;
            background: none;
            outline: none;
            font-size: 0.95rem;
            resize: none;
            max-height: 100px;
            font-family: inherit;
        }

        .emoji-btn {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .emoji-btn:hover {
            transform: scale(1.2);
        }

        .send-btn {
            background: #2d5016;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .send-btn:hover {
            background: #1f3810;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .file-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f0f7ed;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .file-preview-icon {
            width: 35px;
            height: 35px;
            background: #2d5016;
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .file-preview-info {
            flex: 1;
        }

        .file-preview-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #2d5016;
        }

        .file-preview-size {
            font-size: 0.75rem;
            color: #666;
        }

        .remove-file-btn {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 5px;
        }

        .typing-indicator {
            display: none;
            padding: 10px;
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
        }

        .typing-indicator.show {
            display: block;
        }

        /* Modal for image preview */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .image-modal.show {
            display: flex;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 130px);
                border-radius: 0;
            }

            .message-bubble {
                max-width: 85%;
            }

            .chat-header {
                padding: 12px 15px;
            }

            .chat-avatar {
                width: 40px;
                height: 40px;
            }

            .chat-user-details h3 {
                font-size: 0.9rem;
            }

            .chat-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/marketnav.php'; ?>

    <div class="main-content">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <button class="back-btn" onclick="window.location.href='<?php echo BASE_URL; ?>marketplace/messages'">
                    ←
                </button>
                <div class="chat-user-info">
                    <div class="chat-avatar-wrapper">
                        <img src="<?php echo $otherUser['avatar']; ?>" 
                             alt="<?php echo htmlspecialchars($otherUser['name']); ?>" 
                             class="chat-avatar">
                        <?php if ($otherUser['is_online']): ?>
                            <div class="chat-online-indicator"></div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-user-details">
                        <h3><?php echo htmlspecialchars($otherUser['name']); ?></h3>
                        <p><?php echo $otherUser['last_seen']; ?></p>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn" title="Call">📞</button>
                    <button class="chat-action-btn" title="Video Call">📹</button>
                    <button class="chat-action-btn" title="More Options">⋮</button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages" id="chatMessages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-group">
                        <div class="message-bubble <?php echo $msg['is_mine'] ? 'mine' : ''; ?>">
                            <?php if ($msg['type'] === 'text'): ?>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                            <?php elseif ($msg['type'] === 'image'): ?>
                                <div class="message-content">
                                    <img src="<?php echo $msg['image_url']; ?>" 
                                         alt="Shared image" 
                                         class="message-image"
                                         onclick="openImageModal(this.src)">
                                </div>
                            <?php elseif ($msg['type'] === 'file'): ?>
                                <div class="message-file">
                                    <div class="file-icon">📄</div>
                                    <div class="file-info">
                                        <div class="file-name"><?php echo htmlspecialchars($msg['file_name']); ?></div>
                                        <div class="file-size"><?php echo $msg['file_size']; ?></div>
                                    </div>
                                    <div style="font-size: 1.2rem;">⬇️</div>
                                </div>
                            <?php endif; ?>
                            <div class="message-timestamp"><?php echo $msg['timestamp']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="typing-indicator" id="typingIndicator">
                    <?php echo htmlspecialchars($otherUser['name']); ?> is typing...
                </div>
            </div>

            <!-- Input Area -->
            <div class="chat-input-area">
                <div id="filePreview"></div>
                <div class="chat-input-wrapper">
                    <input type="file" id="fileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx" multiple>
                    <button class="attachment-btn" onclick="document.getElementById('fileInput').click()">
                        📎
                    </button>
                    <div class="chat-input-container">
                        <textarea class="chat-input" 
                                  id="messageInput" 
                                  placeholder="Type a message..." 
                                  rows="1"></textarea>
                        <button class="emoji-btn" onclick="insertEmoji()">😊</button>
                    </div>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        ➤
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="image-modal" id="imageModal" onclick="closeImageModal()">
        <button class="close-modal">×</button>
        <img src="" alt="Preview" id="modalImage">
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const chatMessages = document.getElementById('chatMessages');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        let selectedFiles = [];

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            sendBtn.disabled = !this.value.trim() && selectedFiles.length === 0;
        });

        // Send message on Enter (Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // File selection
        fileInput.addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            displayFilePreview();
        });

        function displayFilePreview() {
            filePreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const preview = document.createElement('div');
                preview.className = 'file-preview';
                
                const icon = file.type.startsWith('image/') ? '🖼️' : '📄';
                const size = (file.size / 1024).toFixed(2) + ' KB';
                
                preview.innerHTML = `
                    <div class="file-preview-icon">${icon}</div>
                    <div class="file-preview-info">
                        <div class="file-preview-name">${file.name}</div>
                        <div class="file-preview-size">${size}</div>
                    </div>
                    <button class="remove-file-btn" onclick="removeFile(${index})">×</button>
                `;
                
                filePreview.appendChild(preview);
            });
            
            sendBtn.disabled = !messageInput.value.trim() && selectedFiles.length === 0;
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displayFilePreview();
            
            if (selectedFiles.length === 0) {
                fileInput.value = '';
            }
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            
            if (!message && selectedFiles.length === 0) return;
            
            // Create message bubble
            const messageGroup = document.createElement('div');
            messageGroup.className = 'message-group';
            
            const messageBubble = document.createElement('div');
            messageBubble.className = 'message-bubble mine';
            
            if (message) {
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                messageContent.textContent = message;
                messageBubble.appendChild(messageContent);
            }
            
            // Handle files
            if (selectedFiles.length > 0) {
                selectedFiles.forEach(file => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'message-file';
                    
                    const icon = file.type.startsWith('image/') ? '🖼️' : '📄';
                    const size = (file.size / 1024).toFixed(2) + ' KB';
                    
                    fileDiv.innerHTML = `
                        <div class="file-icon">${icon}</div>
                        <div class="file-info">
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${size}</div>
                        </div>
                    `;
                    
                    messageBubble.appendChild(fileDiv);
                });
            }
            
            const timestamp = document.createElement('div');
            timestamp.className = 'message-timestamp';
            timestamp.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            messageBubble.appendChild(timestamp);
            
            messageGroup.appendChild(messageBubble);
            chatMessages.insertBefore(messageGroup, document.getElementById('typingIndicator'));
            
            // Clear input
            messageInput.value = '';
            messageInput.style.height = 'auto';
            selectedFiles = [];
            fileInput.value = '';
            filePreview.innerHTML = '';
            sendBtn.disabled = true;
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // TODO: Send message via WebSocket/AJAX
            // Example: socket.emit('message', { message, files });
        }

        function insertEmoji() {
            messageInput.value += '😊';
            messageInput.focus();
            messageInput.dispatchEvent(new Event('input'));
        }

        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = src;
            modal.classList.add('show');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('show');
        }

        // Scroll to bottom on load
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // WebSocket connection example (requires Socket.IO or similar)
        /*
        const socket = io('YOUR_WEBSOCKET_SERVER');
        
        socket.on('connect', () => {
            console.log('Connected to chat server');
        });
        
        socket.on('message', (data) => {
            // Add received message to chat
            addReceivedMessage(data);
        });
        
        socket.on('typing', (data) => {
            // Show typing indicator
            document.getElementById('typingIndicator').classList.add('show');
        });
        
        socket.on('stop_typing', () => {
            document.getElementById('typingIndicator').classList.remove('show');
        });
        
        // Emit typing event
        let typingTimer;
        messageInput.addEventListener('input', () => {
            socket.emit('typing');
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                socket.emit('stop_typing');
            }, 1000);
        });
        */
    </script>
</body>
</html>