<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user is blacklisted
if ($user && $user['status'] === 'blacklisted') {
    session_destroy();
    header("Location: login.php?error=blacklisted");
    exit;
}

// Set avatar and name
$user_avatar = $_SESSION['avatar_path'] ?? ($user['avatar_path'] ?: "images/avatar/test.png");
$user_name = $_SESSION['name'] ?? ($user['name'] ?: "Guest");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ AI - FacilityBot</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="chatbot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="booking_facilities.php">Booking Facilities</a>
        <a href="booking_list.php">Booking List</a>
        <a href="feedback.php">Feedback</a>
        <a href="faqai.php" class="active">FAQ AI</a>
        <a href="#" id="contactSidebarLink">Contact Us</a>
    </div>

    <!-- Main Area -->
    <div class="main-area">

        <!-- Top Navbar -->
        <div class="topnav">
            <div class="nav-search">
                <h2 style="color: #ff7300; margin: 0; font-weight: 600;">FAQ AI Assistant</h2>
            </div>

            <div class="nav-right">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($user_avatar) ?>" class="avatar" alt="Avatar">
                    <span><?= htmlspecialchars($user_name) ?></span>
                </div>
                
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>

        <!-- Main Content - Chatbot -->
        <div class="main-content" style="padding: 0; height: calc(100vh - 70px); overflow: hidden;">
            <div class="chatbot-wrapper">
<div class="ai-shell">
    <div class="left">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <i class="fas fa-robot"></i>
                    <span>FacilityBot</span>
                </div>
            </div>
            <div class="sidebar-top">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input id="chatSearch" placeholder="Search conversations..." />
                </div>
                <button id="newChatBtn" class="new-chat-btn">
                    <i class="fas fa-plus"></i>
                    <span>New Chat</span>
                </button>
                <button id="clearAllChatsBtn" class="clear-all-btn">
                    <i class="fas fa-trash-alt"></i>
                    <span>Clear All Chats</span>
                </button>
            </div>
            <div id="chatList" class="chat-list"></div>
        </div>
    </div>

    <div class="right">
        <div class="chat-window">
            <div class="chat-window-header">
                <div class="header-left">
                    <div class="bot-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="header-info">
                        <div id="chatTitle">Facility Assistant</div>
                        <div class="bot-status">
                            <span class="status-dot"></span>
                            <span>Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="messages" class="messages"></div>

            <div class="chat-input-container">
                <div class="chat-input">
                    <input id="userInput" placeholder="Ask me anything about facilities, bookings, events..." />
                    <button id="sendBtn" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="input-footer">
                    <span class="powered-by">Powered by Google Gemma 2 27B</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Confirm Action</h3>
        </div>
        <div class="modal-body">
            <p id="modalMessage">Are you sure?</p>
        </div>
        <div class="modal-footer">
            <button id="modalNoBtn" class="modal-btn modal-btn-no">
                <i class="fas fa-times"></i>
                No
            </button>
            <button id="modalYesBtn" class="modal-btn modal-btn-yes">
                <i class="fas fa-check"></i>
                Yes
            </button>
        </div>
    </div>
</div>

<!-- Booking Confirmation Modal -->
<div id="bookingConfirmModal" class="booking-modal">
    <div class="booking-modal-content">
        <div class="booking-modal-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h2 class="booking-modal-title">CONFIRM BOOKING</h2>
        <div class="booking-details-box">
            <div class="booking-detail-row">
                <i class="fas fa-building"></i>
                <span class="booking-label">Facility:</span>
                <span class="booking-value" id="bookingFacility"></span>
            </div>
            <div class="booking-detail-row">
                <i class="fas fa-map-marker-alt"></i>
                <span class="booking-label">Court:</span>
                <span class="booking-value" id="bookingCourt"></span>
            </div>
            <div class="booking-detail-row">
                <i class="fas fa-calendar-alt"></i>
                <span class="booking-label">Date:</span>
                <span class="booking-value" id="bookingDate"></span>
            </div>
            <div class="booking-detail-row">
                <i class="fas fa-clock"></i>
                <span class="booking-label">Time:</span>
                <span class="booking-value" id="bookingTime"></span>
            </div>
        </div>
        <div class="booking-modal-actions">
            <button class="booking-btn booking-btn-cancel" id="bookingCancelBtn">CANCEL</button>
            <button class="booking-btn booking-btn-confirm" id="bookingConfirmBtn">CONFIRM</button>
        </div>
    </div>
</div>

<script>
// Simple chat frontend: stores chats in localStorage and sends user messages to AI.php
const STORAGE_KEY = 'ai_chats_v1';
let chats = [];
let currentChatId = null;
let modalCallback = null;

function uid(){ return 'c' + Date.now() + Math.random().toString(36).slice(2,8); }

// Modal Functions
function showModal(title, message, onYes) {
    const modal = document.getElementById('confirmModal');
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    modalCallback = onYes;
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
    modalCallback = null;
}

function loadChats(){
    try{ chats = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }catch(e){ chats = []; }
    renderChatList();
    
    // Only create a new chat if there are NO chats at all
    if (chats.length === 0) {
        createNewChat();
    } else {
        // Check if there's an empty chat, select it
        const emptyChat = chats.find(c => !c.messages || c.messages.length === 0);
        if (emptyChat) {
            selectChat(emptyChat.id);
        } else {
            // If all chats have messages, create a new one
            createNewChat();
        }
    }
}

function saveChats(){ localStorage.setItem(STORAGE_KEY, JSON.stringify(chats)); }

function generateChatTitle(firstMessage) {
    // Generate a smart title from the first message
    if (!firstMessage) return 'New Chat';
    
    let title = firstMessage.trim();
    
    // Remove question marks and clean up
    title = title.replace(/\?+$/g, '').trim();
    
    // Extract key topic based on common patterns
    const patterns = [
        // "What/How/When/Where/Who/Why is/are X" -> "X"
        /^(?:what|how|when|where|who|why|can|could|would|will|should)\s+(?:is|are|was|were|do|does|did|can|could|would|will|should|has|have|had)\s+(.+)/i,
        // "Show me X" -> "X"
        /^(?:show|give|tell|find|get|list|display)\s+(?:me|us|all)?\s*(.+)/i,
        // "I want/need X" -> "X"
        /^(?:i|we)\s+(?:want|need|would like)\s+(?:to\s+)?(.+)/i,
        // Generic question words at start -> rest of sentence
        /^(?:what|how|when|where|who|why|which|can|could|would|is|are|do|does)\s+(.+)/i,
    ];
    
    for (const pattern of patterns) {
        const match = title.match(pattern);
        if (match && match[1]) {
            title = match[1].trim();
            break;
        }
    }
    
    // Clean up common words at the end
    title = title.replace(/\s+(?:please|now|today|currently)$/i, '');
    
    // Capitalize first letter of each major word
    title = title.split(' ').map((word, index) => {
        // Don't capitalize small words unless they're first
        if (index > 0 && ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with'].includes(word.toLowerCase())) {
            return word.toLowerCase();
        }
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
    
    // Limit length
    if (title.length > 40) {
        title = title.substring(0, 40).trim() + '...';
    }
    
    return title || 'New Chat';
}

function renderChatList(filter=''){
    const list = document.getElementById('chatList');
    list.innerHTML = '';
    const filtered = chats.filter(c => !filter || (c.title && c.title.toLowerCase().includes(filter)) || (c.last && c.last.toLowerCase().includes(filter)) );
    
    if (filtered.length === 0 && filter) {
        list.innerHTML = '<div style="text-align:center;color:#999;padding:20px;font-size:0.9em;">No conversations found</div>';
        return;
    }
    
    filtered.forEach(c => {
        const item = document.createElement('div');
        item.className = 'chat-item';
        if(c.id === currentChatId) item.classList.add('active');
        item.dataset.id = c.id;
        item.innerHTML = `
            <div class="chat-item-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="chat-item-inner">
                <div class="chat-item-title" data-id="${c.id}">${escapeHtml(c.title || 'Chat')}</div>
                <div class="chat-item-last">${escapeHtml(c.last || 'No messages yet')}</div>
            </div>
            <div class="chat-actions">
                <button class="chat-edit" title="Edit title">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="chat-delete" title="Delete">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        
        item.addEventListener('click', (e)=>{
            if(e.target.closest('.chat-delete') || e.target.closest('.chat-edit')) return;
            selectChat(c.id);
        });
        
        // Edit button handler
        const editBtn = item.querySelector('.chat-edit');
        editBtn.addEventListener('click', (e)=>{ 
            e.stopPropagation(); 
            startEditingTitle(c.id);
        });
        
        // Delete button handler
        const del = item.querySelector('.chat-delete');
        del.addEventListener('click', (e)=>{ 
            e.stopPropagation(); 
            showModal(
                'Delete conversation ?',
                'This conversation will be permanently deleted.',
                () => deleteChat(c.id)
            );
        });
        
        list.appendChild(item);
    });
}

function startEditingTitle(chatId) {
    const chat = chats.find(c => c.id === chatId);
    if (!chat) return;
    
    const titleElement = document.querySelector(`.chat-item-title[data-id="${chatId}"]`);
    if (!titleElement) return;
    
    const currentTitle = chat.title;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'chat-title-edit';
    input.value = currentTitle;
    
    titleElement.replaceWith(input);
    input.focus();
    input.select();
    
    function saveTitle() {
        const newTitle = input.value.trim();
        if (newTitle && newTitle !== currentTitle) {
            chat.title = newTitle;
            saveChats();
            
            // Update header if this is the current chat
            if (currentChatId === chatId) {
                document.getElementById('chatTitle').textContent = newTitle;
            }
        }
        renderChatList(document.getElementById('chatSearch').value.toLowerCase());
    }
    
    input.addEventListener('blur', saveTitle);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveTitle();
        } else if (e.key === 'Escape') {
            renderChatList(document.getElementById('chatSearch').value.toLowerCase());
        }
    });
}

function selectChat(id){
    currentChatId = id;
    const chat = chats.find(x=>x.id===id);
    if(!chat) return;
    document.getElementById('chatTitle').textContent = chat.title || 'Facility Assistant';
    renderMessages(chat.messages || []);
    renderChatList(document.getElementById('chatSearch').value.toLowerCase());
}

function renderMessages(messages){
    const box = document.getElementById('messages');
    box.innerHTML = '';
    
    if(messages.length === 0){
        box.classList.add('empty-chat');
        box.innerHTML = `
            <div class="welcome-message">
                <div class="welcome-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>Welcome to FacilityBot!</h3>
                <p>I can help you with:</p>
                <div class="suggestions">
                    <div class="suggestion-item" onclick="document.getElementById('userInput').value='What venues are available?'; document.getElementById('sendBtn').click();">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Available Venues</span>
                    </div>
                    <div class="suggestion-item" onclick="document.getElementById('userInput').value='Show my bookings'; document.getElementById('sendBtn').click();">
                        <i class="fas fa-calendar-check"></i>
                        <span>My Bookings</span>
                    </div>
                    <div class="suggestion-item" onclick="document.getElementById('userInput').value='Upcoming events'; document.getElementById('sendBtn').click();">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Upcoming Events</span>
                    </div>
                    <div class="suggestion-item" onclick="document.getElementById('userInput').value='Latest announcements'; document.getElementById('sendBtn').click();">
                        <i class="fas fa-bullhorn"></i>
                        <span>Announcements</span>
                    </div>
                </div>
            </div>
        `;
    } else {
        box.classList.remove('empty-chat');
        messages.forEach((m, index) => {
            const el = document.createElement('div');
            el.className = 'msg ' + (m.role==='user' ? 'user' : 'assistant');
            el.dataset.index = index;
            
            if(m.role === 'assistant'){
                const hasVenues = m.venues && m.venues.length > 0;
                const hasBookings = m.bookings && m.bookings.length > 0;
                
                // Determine PRIMARY intent from query_type (most reliable)
                let showVenueViewer = false;
                let showBookingViewer = false;
                
                if (m.query_type) {
                    // Use query_type as primary indicator
                    // ONLY show dropdowns for 'venues' and 'bookings' types
                    if (m.query_type === 'venues') {
                        showVenueViewer = hasVenues;
                    } else if (m.query_type === 'bookings') {
                        showBookingViewer = hasBookings;
                    }
                    // For 'event', 'announcement', 'feedback', 'general' - show NO dropdowns
                } else {
                    // Fallback: keyword-based detection (STRICT - prioritize bookings over venues)
                    const question = (m.original_question || '').toLowerCase();
                    
                    // Booking keywords take PRIORITY
                    const bookingKeywords = ['booking', 'book', 'reservation', 'reserve', 'booked', 'history', 'appointment', 'my booking', 'scheduled'];
                    const askedAboutBookings = bookingKeywords.some(kw => question.includes(kw));
                    
                    if (askedAboutBookings && hasBookings) {
                        showBookingViewer = true;
                    } else if (hasVenues) {
                        // Only show venues if NOT asking about bookings
                        const venueOnlyKeywords = ['venue', 'facility', 'facilities', 'place', 'court', 'location', 'available', 'gym', 'pool'];
                        const askedAboutVenues = venueOnlyKeywords.some(kw => question.includes(kw));
                        showVenueViewer = askedAboutVenues;
                    }
                }
                
                el.innerHTML = `
                    <div class="msg-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="msg-content">
                        <div class="msg-text">${escapeHtml(m.text)}</div>
                        ${showVenueViewer ? `
                        <div class="venue-viewer-container" id="venue-container-${index}">
                            <button class="view-facilities-btn" onclick="toggleVenueViewer(${index})">
                                <i class="fas fa-building"></i> View Facilities
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="venue-viewer" id="venue-viewer-${index}" style="display:none;">
                                <div class="venue-carousel">
                                    <button class="carousel-btn prev" onclick="scrollVenueCarousel(${index}, -1)">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="venue-cards" id="venue-cards-${index}">
                                        ${m.venues.map(v => `
                                            <div class="venue-card-item">
                                                <div class="venue-img-container">
                                                    ${v.image ? `<img src="${escapeHtml(v.image)}" alt="${escapeHtml(v.name)}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'200\\' height=\\'150\\'%3E%3Crect fill=\\'%23f0f0f0\\' width=\\'200\\' height=\\'150\\'/%3E%3Ctext fill=\\'%23999\\' x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\' font-family=\\'Arial\\' font-size=\\'14\\'%3ENo Image%3C/text%3E%3C/svg%3E'">` : `<div class="no-image"><i class="fas fa-building"></i></div>`}
                                                </div>
                                                <div class="venue-card-body">
                                                    <h4>${escapeHtml(v.name)}${v.court ? ' - ' + escapeHtml(v.court) : ''}</h4>
                                                    <div class="venue-detail-item">
                                                        <i class="fas fa-users"></i>
                                                        <span>Capacity: ${escapeHtml(v.capacity)}</span>
                                                    </div>
                                                    <div class="venue-detail-item">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span>${escapeHtml(v.location)}</span>
                                                    </div>
                                                    ${v.description ? `<p class="venue-description">${escapeHtml(v.description)}</p>` : ''}
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                    <button class="carousel-btn next" onclick="scrollVenueCarousel(${index}, 1)">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                        ${showBookingViewer ? `
                        <div class="venue-viewer-container" id="booking-container-${index}">
                            <button class="view-facilities-btn" onclick="toggleBookingViewer(${index})">
                                <i class="fas fa-calendar-check"></i> View Bookings
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="venue-viewer" id="booking-viewer-${index}" style="display:none;">
                                <div class="venue-carousel">
                                    <button class="carousel-btn prev" onclick="scrollBookingCarousel(${index}, -1)">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="venue-cards" id="booking-cards-${index}">
                                        ${m.bookings.map(b => `
                                            <div class="venue-card-item booking-card-item">
                                                <div class="venue-img-container">
                                                    ${b.image ? `<img src="${escapeHtml(b.image)}" alt="${escapeHtml(b.venue_name)}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'200\\' height=\\'150\\'%3E%3Crect fill=\\'%23f0f0f0\\' width=\\'200\\' height=\\'150\\'/%3E%3Ctext fill=\\'%23999\\' x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\' font-family=\\'Arial\\' font-size=\\'14\\'%3ENo Image%3C/text%3E%3C/svg%3E'">` : `<div class="no-image"><i class="fas fa-calendar"></i></div>`}
                                                </div>
                                                <div class="venue-card-body">
                                                    <h4>${escapeHtml(b.venue_name)}${b.court ? ' - ' + escapeHtml(b.court) : ''}</h4>
                                                    <div class="venue-detail-item">
                                                        <i class="fas fa-calendar-day"></i>
                                                        <span>${escapeHtml(b.booking_date)}</span>
                                                    </div>
                                                    <div class="venue-detail-item">
                                                        <i class="fas fa-clock"></i>
                                                        <span>${escapeHtml(b.booking_time)}</span>
                                                    </div>
                                                    <div class="venue-detail-item">
                                                        <i class="fas fa-info-circle"></i>
                                                        <span class="booking-status status-${escapeHtml(b.status)}">${b.status === 'approved' ? 'BOOKED' : escapeHtml(b.status).toUpperCase()}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                    <button class="carousel-btn next" onclick="scrollBookingCarousel(${index}, 1)">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                        <div class="msg-time">${formatTime(m.ts)}</div>
                    </div>
                `;
            } else {
                el.innerHTML = `
                    <div class="msg-content">
                        <div class="msg-text" data-index="${index}">${escapeHtml(m.text)}</div>
                        <div class="msg-footer">
                            <div class="msg-time">${formatTime(m.ts)}</div>
                            <button class="msg-edit-btn" data-index="${index}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <div class="msg-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                `;
                
                // Add edit handler after element is created
                setTimeout(() => {
                    const editBtn = el.querySelector('.msg-edit-btn');
                    if(editBtn) {
                        editBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            startEditingMessage(index);
                        });
                    }
                }, 0);
            }
            box.appendChild(el);
        });
    }
    
    box.scrollTop = box.scrollHeight;
}

function addMessageToChat(role, text){
    if(!currentChatId){ createNewChat(); }
    const chat = chats.find(c=>c.id===currentChatId);
    const msg = { role, text, ts: Date.now() };
    chat.messages = chat.messages || [];
    chat.messages.push(msg);
    chat.last = text.slice(0,60);
    
    // Update title dynamically based on first user message
    if (role === 'user' && chat.messages.filter(m => m.role === 'user').length === 1) {
        chat.title = generateChatTitle(text);
        // Immediately update the header title as well
        document.getElementById('chatTitle').textContent = chat.title;
    }
    
    saveChats();
    renderChatList(document.getElementById('chatSearch').value.toLowerCase());
    renderMessages(chat.messages);
}

function createNewChat(){
    // Check if current chat is empty - if so, don't create new chat
    if (currentChatId) {
        const currentChat = chats.find(c => c.id === currentChatId);
        if (currentChat && (!currentChat.messages || currentChat.messages.length === 0)) {
            // Current chat is empty, don't create a new one
            return;
        }
    }
    
    const id = uid();
    const chat = { id, title: 'New Chat', messages: [] };
    chats.unshift(chat);
    saveChats();
    renderChatList();
    selectChat(id);
}

function deleteChat(id){
    chats = chats.filter(c=>c.id!==id);
    saveChats();
    renderChatList(document.getElementById('chatSearch').value.toLowerCase());
    if(currentChatId===id){ 
        currentChatId = null; 
        if(chats.length) selectChat(chats[0].id); 
        else { 
            document.getElementById('messages').innerHTML=''; 
            document.getElementById('chatTitle').textContent='Facility Assistant'; 
            createNewChat();
        } 
    }
    closeModal();
}

function clearAllChats(){
    chats = [];
    currentChatId = null;
    saveChats();
    renderChatList();
    document.getElementById('messages').innerHTML='';
    document.getElementById('chatTitle').textContent='Facility Assistant';
    createNewChat();
    closeModal();
}

function startEditingMessage(messageIndex) {
    const chat = chats.find(c => c.id === currentChatId);
    if (!chat || !chat.messages[messageIndex]) return;
    
    const message = chat.messages[messageIndex];
    if (message.role !== 'user') return; // Only allow editing user messages
    
    const msgTextElement = document.querySelector(`.msg-text[data-index="${messageIndex}"]`);
    if (!msgTextElement) return;
    
    const currentText = message.text;
    const textarea = document.createElement('textarea');
    textarea.className = 'msg-edit-textarea';
    textarea.value = currentText;
    
    // Replace the message text with textarea
    const msgContent = msgTextElement.closest('.msg-content');
    const msgFooter = msgContent.querySelector('.msg-footer');
    
    msgTextElement.style.display = 'none';
    msgFooter.style.display = 'none'; // Hide the footer (time + edit button)
    
    msgContent.insertBefore(textarea, msgTextElement);
    
    textarea.focus();
    textarea.select();
    
    // Auto-resize textarea
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
    
    // Add save and cancel buttons
    const btnContainer = document.createElement('div');
    btnContainer.className = 'msg-edit-actions';
    btnContainer.innerHTML = `
        <button class="msg-save-btn" title="Save">
            <i class="fas fa-check"></i> Save
        </button>
        <button class="msg-cancel-btn" title="Cancel">
            <i class="fas fa-times"></i> Cancel
        </button>
    `;
    
    msgContent.insertBefore(btnContainer, msgTextElement);
    
    const saveBtn = btnContainer.querySelector('.msg-save-btn');
    const cancelBtn = btnContainer.querySelector('.msg-cancel-btn');
    
    function cancelEdit() {
        textarea.remove();
        btnContainer.remove();
        msgTextElement.style.display = 'block';
        msgFooter.style.display = 'flex'; // Show footer again
    }
    
    function saveEdit() {
        const newText = textarea.value.trim();
        if (!newText) {
            cancelEdit();
            return;
        }
        
        if (newText === currentText) {
            cancelEdit();
            return;
        }
        
        // Update the message
        message.text = newText;
        
        // Remove all messages after this one (they'll be regenerated)
        chat.messages = chat.messages.slice(0, messageIndex + 1);
        
        saveChats();
        renderMessages(chat.messages);
        
        // Regenerate response
        regenerateResponse(newText);
    }
    
    saveBtn.addEventListener('click', saveEdit);
    cancelBtn.addEventListener('click', cancelEdit);
    
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            saveEdit();
        } else if (e.key === 'Escape') {
            cancelEdit();
        }
    });
}

function regenerateResponse(userMessage) {
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Show typing indicator
    addMessageToChat('assistant', '<div class="typing-indicator"><span></span><span></span><span></span></div>');
    
    // Call backend
    fetch('AI.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ 
        message: userMessage,
        user_id: '<?= $user_id ?>'
    }) })
        .then(r => r.json())
        .then(data => {
            const chat = chats.find(c=>c.id===currentChatId);
            if(chat){
                // Remove typing indicator
                const last = chat.messages[chat.messages.length-1];
                if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
                
                const reply = data.response || (data.error || 'No response');
                
                // Store venues data if available
                const messageData = { 
                    role:'assistant', 
                    text: reply, 
                    ts: Date.now(),
                    query_type: data.query_type,
                    original_question: text
                };
                
                if(data.data && data.data.venues && data.data.venues.length > 0) {
                    messageData.venues = data.data.venues;
                }
                
if(data.data && data.data.bookings && data.data.bookings.length > 0) {
                    messageData.bookings = data.data.bookings;
                }
                
                chat.messages.push(messageData);
                chat.last = reply.slice(0,60);
                saveChats();
                renderMessages(chat.messages);
                renderChatList(document.getElementById('chatSearch').value.toLowerCase());
            }
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        })
        .catch(err => {
            console.error(err);
            const chat = chats.find(c=>c.id===currentChatId);
            if(chat){
                const last = chat.messages[chat.messages.length-1];
                if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
                chat.messages.push({ role:'assistant', text: '❌ Sorry, I encountered an error. Please try again.', ts: Date.now() });
                saveChats(); 
                renderMessages(chat.messages);
            }
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
}

function sendUserMessage(){
    const input = document.getElementById('userInput');
    const text = input.value.trim();
    if(!text) return;
    
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    addMessageToChat('user', text);
    input.value = '';
    
    // show typing indicator
    addMessageToChat('assistant', '<div class="typing-indicator"><span></span><span></span><span></span></div>');
    
    // call backend
    const chat = chats.find(c=>c.id===currentChatId);
    const conversationHistory = chat ? chat.messages.slice(-10).map(m => ({
        role: m.role,
        text: m.text,
        incomplete_booking: m.incomplete_booking
    })) : [];
    
    fetch('AI.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ 
        message: text,
        user_id: '<?= $user_id ?>',
        conversation_history: conversationHistory
    }) })
        .then(r => {
            if (!r.ok) {
                return r.json().then(err => { throw new Error(err.error || 'Server error'); });
            }
            return r.json();
        })
        .then(data => {
            const chat = chats.find(c=>c.id===currentChatId);
            if(chat){
                // remove typing indicator
                const last = chat.messages[chat.messages.length-1];
                if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
                
                // Check if this is a booking request that needs confirmation
                if(data.query_type === 'create_booking_ready' && data.booking_data) {
                    // Show confirmation modal instead of creating booking
                    showBookingConfirmation(data.booking_data);
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                    return;
                }
                
                let reply = data.response || (data.error || 'No response');
                
                // REMOVE ALL ASTERISKS from response
                reply = reply.replace(/\*/g, '');
                
                // Store message data
                const messageData = { 
                    role:'assistant', 
                    text: reply, 
                    ts: Date.now(),
                    query_type: data.query_type,
                    original_question: text,
                    incomplete_booking: data.incomplete_booking
                };
                
                
if(data.data && data.data.bookings && data.data.bookings.length > 0) {
                    messageData.bookings = data.data.bookings;
                }
                if(data.data && data.data.venues && data.data.venues.length > 0) {
                    messageData.venues = data.data.venues;
                }
                
                chat.messages.push(messageData);
                chat.last = reply.slice(0,60);
                saveChats();
                renderMessages(chat.messages);
                renderChatList(document.getElementById('chatSearch').value.toLowerCase());
            }
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        })
        .catch(err => {
            console.error('Error details:', err);
            const chat = chats.find(c=>c.id===currentChatId);
            if(chat){
                const last = chat.messages[chat.messages.length-1];
                if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
                chat.messages.push({ role:'assistant', text: '❌ Sorry, I encountered an error: ' + err.message, ts: Date.now() });
                saveChats(); 
                renderMessages(chat.messages);
            }
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
}

function toggleVenueViewer(index) {
    const viewer = document.getElementById(`venue-viewer-${index}`);
    const btn = viewer.previousElementSibling;
    const icon = btn.querySelector('.fa-chevron-down, .fa-chevron-up');
    
    if (viewer.style.display === 'none') {
        viewer.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        viewer.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function scrollVenueCarousel(index, direction) {
    const cards = document.getElementById(`venue-cards-${index}`);
    const cardWidth = cards.querySelector('.venue-card-item').offsetWidth + 16; // card width + gap
    cards.scrollBy({
        left: direction * cardWidth,
        behavior: 'smooth'
    });
}

function toggleBookingViewer(index) {
    const viewer = document.getElementById(`booking-viewer-${index}`);
    const btn = viewer.previousElementSibling;
    const icon = btn.querySelector('.fa-chevron-down, .fa-chevron-up');
    
    if (viewer.style.display === 'none') {
        viewer.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        viewer.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function scrollBookingCarousel(index, direction) {
    const cards = document.getElementById(`booking-cards-${index}`);
    const cardWidth = cards.querySelector('.venue-card-item').offsetWidth + 16;
    cards.scrollBy({
        left: direction * cardWidth,
        behavior: 'smooth'
    });
}

function escapeHtml(s){ 
    if(!s && s !== 0) return '';
    // Convert to string if not already
    s = String(s);
    // Don't escape if it contains typing indicator
    if(s.includes('typing-indicator')) return s;
    return s.replace(/[&<>"']/g, (m)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); 
}

function formatTime(ts){ 
    const d = new Date(ts); 
    const now = new Date();
    const diff = now - d;
    
    if(diff < 60000) return 'Just now';
    if(diff < 3600000) return Math.floor(diff/60000) + 'm ago';
    if(diff < 86400000) return d.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'});
    return d.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
}

let pendingBookingData = null;

function showBookingConfirmation(bookingData) {
    pendingBookingData = bookingData;
    
    // Format date
    const dateObj = new Date(bookingData.date);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = dateObj.toLocaleDateString('en-US', options);
    
    // Format time
    const startTime = new Date(`2000-01-01 ${bookingData.start_time}`);
    const endTime = new Date(`2000-01-01 ${bookingData.end_time}`);
    const formattedTime = startTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) + 
                          ' - ' + 
                          endTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    
    // Populate modal
    document.getElementById('bookingFacility').textContent = bookingData.venue_name;
    document.getElementById('bookingCourt').textContent = bookingData.court || 'N/A';
    document.getElementById('bookingDate').textContent = formattedDate;
    document.getElementById('bookingTime').textContent = formattedTime;
    
    // Show modal
    document.getElementById('bookingConfirmModal').style.display = 'flex';
}

function closeBookingModal() {
    document.getElementById('bookingConfirmModal').style.display = 'none';
    pendingBookingData = null;
}

function confirmBooking() {
    if (!pendingBookingData) return;
    
    // ✅ CLOSE MODAL IMMEDIATELY (FIXED - moved to top)
    closeBookingModal();
    
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Show typing indicator
    addMessageToChat('assistant', '<div class="typing-indicator"><span></span><span></span><span></span></div>');
    
    // Send confirmation to backend with _CONFIRMED flag
    fetch('AI.php', { 
        method:'POST', 
        headers:{'Content-Type':'application/json'}, 
        body: JSON.stringify({ 
            message: '_BOOKING_CONFIRMED',
            user_id: '<?= $user_id ?>',
            booking_data: pendingBookingData
        }) 
    })
    .then(r => r.json())
    .then(data => {
        const chat = chats.find(c=>c.id===currentChatId);
        if(chat){
            // Remove typing indicator
            const last = chat.messages[chat.messages.length-1];
            if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
            
            const reply = data.response || (data.error || 'No response');
            
            chat.messages.push({ 
                role:'assistant', 
                text: reply, 
                ts: Date.now(),
                query_type: data.query_type
            });
            chat.last = reply.slice(0,60);
            saveChats();
            renderMessages(chat.messages);
            renderChatList(document.getElementById('chatSearch').value.toLowerCase());
        }
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    })
    .catch(err => {
        console.error(err);
        const chat = chats.find(c=>c.id===currentChatId);
        if(chat){
            const last = chat.messages[chat.messages.length-1];
            if(last && last.role==='assistant' && last.text.includes('typing-indicator')) chat.messages.pop();
            chat.messages.push({ role:'assistant', text: '❌ Sorry, booking failed. Please try again.', ts: Date.now() });
            saveChats();
            renderMessages(chat.messages);
        }
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}

// UI bindings
document.addEventListener('DOMContentLoaded', ()=>{
    document.getElementById('sendBtn').addEventListener('click', sendUserMessage);
    document.getElementById('userInput').addEventListener('keydown', (e)=>{ 
        if(e.key==='Enter' && !e.shiftKey) {
            e.preventDefault();
            sendUserMessage(); 
        }
    });
    document.getElementById('newChatBtn').addEventListener('click', createNewChat);
    document.getElementById('clearAllChatsBtn').addEventListener('click', ()=>{
        showModal(
            'Clear all conversations ?',
            'All your conversations will be permanently deleted. This action cannot be undone.',
            clearAllChats
        );
    });
    document.getElementById('chatSearch').addEventListener('input', (e)=> renderChatList(e.target.value.toLowerCase()));
    
    // Modal event listeners
    document.getElementById('modalYesBtn').addEventListener('click', ()=>{
        if(modalCallback) modalCallback();
    });
    document.getElementById('modalNoBtn').addEventListener('click', closeModal);
    
    // Click outside modal to close
    document.getElementById('confirmModal').addEventListener('click', (e)=>{
        if(e.target.id === 'confirmModal') closeModal();
    });
    
    // Booking modal event listeners
    document.getElementById('bookingConfirmBtn').addEventListener('click', confirmBooking);
    document.getElementById('bookingCancelBtn').addEventListener('click', closeBookingModal);
    document.getElementById('bookingConfirmModal').addEventListener('click', (e)=>{
        if(e.target.id === 'bookingConfirmModal') closeBookingModal();
    });

    // initial load - always starts fresh
    loadChats();
});
</script>

            </div><!-- End chatbot-wrapper -->
        </div><!-- End main-content -->
    </div><!-- End main-area -->
</div><!-- End dashboard-container -->

<!-- Contact Us Panel (same as dashboard.php) -->
<section id="contactPanel" class="contact-panel">
    <div class="contact-popout">
        <button class="close-btn" id="closeContactPanel">&times;</button>
        <div class="contact-left">
            <img src="images/tarumt-logo.png" alt="Logo" class="contact-logo-large">
            <h3>Student Sport Club</h3>
            <p>Your campus facility booking system</p>
        </div>
        <div class="contact-right">
            <a href="mailto:sabah@tarc.edu.my" class="info-row">
                <i class="fa-solid fa-envelope"></i> <span>sabah@tarc.edu.my</span>
            </a>
            <a href="https://www.facebook.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-facebook"></i> <span>TAR UMT Sabah Branch</span>
            </a>
            <a href="https://www.instagram.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-instagram"></i> <span>tarumtsabah</span>
            </a>
            <a href="https://x.com/TARUMT_Sabah" class="info-row" target="_blank" rel="noopener">
                <i class="fa-brands fa-x-twitter"></i> <span>@TARUMT_Sabah</span>
            </a>
            <a href="#" class="info-row">
                <i class="fa-brands fa-whatsapp"></i> <span>(6)011-1082 5619</span>
            </a>
            <a href="https://maps.app.goo.gl/4K9jrb9DcXMdu68JA" class="info-row" target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot"></i> <span>Jalan Alamesra, Alamesra, 88450 Kota Kinabalu, Sabah.</span>
            </a>
        </div>
    </div>
</section>

<script>
// Contact Panel Script (same as dashboard.php)
document.addEventListener("DOMContentLoaded", () => {
    const contactPanel = document.getElementById('contactPanel');
    const contactSidebarLink = document.getElementById('contactSidebarLink');
    const closeBtn = document.getElementById('closeContactPanel');

    if (contactSidebarLink) {
        contactSidebarLink.addEventListener('click', (e) => {
            e.preventDefault();
            contactPanel.classList.add('open');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            contactPanel.classList.remove('open');
        });
    }

    contactPanel.addEventListener('click', (e) => {
        if (!e.target.closest('.contact-popout')) {
            contactPanel.classList.remove('open');
        }
    });
});

const card = document.querySelector('.contact-popout');
if (card) {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((centerY - y) / centerY) * 6;
        const rotateY = ((x - centerX) / centerX) * 6;

        card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.03)`;
        card.classList.add('hovering');
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = `rotateX(0deg) rotateY(0deg) scale(1)`;
        card.classList.remove('hovering');
    });
}
</script>

</body>
</html>