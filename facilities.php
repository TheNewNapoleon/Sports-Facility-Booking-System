<?php
// Include database connection
require_once 'db.php';

// Fetch all facilities from database
$query = "SELECT venue_id, name, capacity, location, shared_group, description, image FROM venues ORDER BY venue_id";
$result = mysqli_query($conn, $query);

$facilities = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $facilities[] = $row;
    }
}

// Convert to JSON for JavaScript
$facilitiesJSON = json_encode($facilities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities Showcase</title>
    <link rel="stylesheet" href="css/facility.css">
    <link rel="stylesheet" href="css/homepage.css">
    <style>
        /* Intro Screen Styles */
        #intro-screen {
            position: fixed;
            top: 0; 
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffb579;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 999999;
            animation: introFadeOut 0.6s ease 1.4s forwards;
        }

        .intro-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 6px solid #ff7b00;
            box-shadow: 
                0 0 15px #ff7b00,
                0 0 30px #ff7b00,
                inset 0 0 15px #ff7b00;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: ringPulse 1s ease-in-out infinite;
            overflow: hidden;
            background: white;
        }

        .intro-logo-img {
            width: 70%;
            height: auto;
            object-fit: contain;
            animation: logoFade 0.6s ease-out forwards 0.2s;
            opacity: 0;
            border-radius: 50px;
        }

        .intro-logo-text {
            color: #fff;
            font-size: 40px;
            font-weight: 900;
            letter-spacing: 2px;
            margin-top: 22px;
            opacity: 0;
            animation: textReveal 0.5s ease-out forwards 0.4s;
        }

        @keyframes introFadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @keyframes ringPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes logoFade {
            to { opacity: 1; }
        }

        @keyframes textReveal {
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Intro Screen -->
    <div id="intro-screen">
        <div class="intro-circle">
            <img src="/images/tarumt-logo.png" class="intro-logo-img" alt="TARUMT Logo">
        </div>
        <h1 class="intro-logo-text">TARUMT Sport Facilities</h1>
    </div>

    <!-- Dynamic Background -->
    <div class="background-layer" id="backgroundLayer">
        <div class="bg-gradient"></div>
        <div class="grid-overlay"></div>
        <img id="backgroundImage" src="" alt="">
    </div>
    <div class="background-overlay"></div>

    <div class="container">
        <!-- Left Side - Image Only -->
        <div class="left-section">
            <div class="image-container">
                <img id="mainImage" class="main-image" src="" alt="">
            </div>
        </div>

        <!-- Right Side - Tabbed Interface -->
        <div class="right-section">
            <!-- Back Button with Home Icon -->
            <a href="index.php" class="back-btn" title="Back to Home">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </a>
            
            <!-- Header -->
            <div class="right-header">
                <h1>FACILITIES</h1>
                <p class="subtitle">Explore Our Premium Venues</p>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="#detailsTab">Facility Details</button>
            </div>

            <!-- Tab Content Wrapper -->
            <div class="tab-content-wrapper">
                <!-- Details Tab -->
                <div class="tab-content active details-content" id="detailsTab">
                    <?php if (empty($facilities)): ?>
                        <div class="no-facilities">
                            <h3>No Facilities Available</h3>
                            <p>Please add facilities to the database.</p>
                        </div>
                    <?php else: ?>
                        <!-- Title -->
                        <div class="title-container">
                            <h2 class="facility-title" id="facilityTitle">Select a facility</h2>
                        </div>

                        <!-- Description -->
                        <div class="description-container">
                            <p class="facility-description" id="facilityDescription">
                                Use the controls below to navigate through our facilities or let them auto-slide every 3 seconds.
                            </p>
                        </div>

                        <!-- Info Grid -->
                        <div class="info-grid-container">
                            <div class="info-grid" id="infoGrid"></div>
                        </div>

                        <!-- Controls moved to bottom -->
                        <div class="bottom-controls">
                            <button class="nav-btn" id="prevBtn" aria-label="Previous">‹</button>
                            <button class="play-pause-btn" id="playPauseBtn" aria-label="Play/Pause">
                                <span id="playPauseIcon">❚❚</span>
                            </button>
                            <button class="nav-btn" id="nextBtn" aria-label="Next">›</button>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons" style="display: none;">
                            <button class="action-btn btn-primary">Book Now</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
// Intro screen overflow control
document.body.style.overflow = "hidden";

setTimeout(() => {
    document.body.style.overflow = "auto";
}, 2000); // matches animation timing (1.4s delay + 0.6s fadeout)

document.addEventListener('DOMContentLoaded', () => {
  // Get facilities data from PHP
  const facilities = <?php echo $facilitiesJSON; ?>;
  
  console.log('Facilities loaded:', facilities);
  
  if (!facilities || facilities.length === 0) {
    console.warn('No facilities found in database');
    return;
  }

  // Elements
  const mainImage = document.getElementById('mainImage');
  const backgroundImage = document.getElementById('backgroundImage');
  const playPauseBtn = document.getElementById('playPauseBtn');
  const playPauseIcon = document.getElementById('playPauseIcon');
  const facilityTitle = document.getElementById('facilityTitle');
  const facilityDescription = document.getElementById('facilityDescription');
  const infoGrid = document.getElementById('infoGrid');

  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  // State
  let currentIndex = 0;
  let isPlaying = true;
  let autoPlayInterval = null;
  const AUTOPLAY_MS = 3000;

  // Helpers with animations
  function setMainAndBg(src, alt='') {
    if (mainImage) { 
      mainImage.classList.add('slide-out-left');
      setTimeout(() => {
        mainImage.src = src; 
        mainImage.alt = alt;
        mainImage.classList.remove('slide-out-left');
        mainImage.classList.add('slide-in-right');
        setTimeout(() => mainImage.classList.remove('slide-in-right'), 800);
      }, 400);
    }
    if (backgroundImage) { 
      backgroundImage.classList.add('zoom-blur');
      setTimeout(() => {
        backgroundImage.src = src;
        backgroundImage.classList.remove('zoom-blur');
      }, 500);
    }
  }

  function renderInfo(fac) {
    if (!infoGrid) return;
    infoGrid.classList.add('fade-down');
    setTimeout(() => {
      infoGrid.innerHTML = `
        <div class="info-item">
          <div class="info-label">Venue ID</div>
          <div class="info-value">${fac.venue_id}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Capacity</div>
          <div class="info-value">${fac.capacity}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Location</div>
          <div class="info-value">${fac.location || 'N/A'}</div>
        </div>
        <div class="info-item">
          <div class="info-label">Group</div>
          <div class="info-value">${fac.shared_group || 'N/A'}</div>
        </div>
      `;
      infoGrid.classList.remove('fade-down');
    }, 250);
  }

  // Update UI for current facility with animations
  function updateDisplay() {
    const fac = facilities[currentIndex];
    if (!fac) return;
    
    const imgSrc = fac.image || 'https://via.placeholder.com/1200x800?text=No+Image';
    setMainAndBg(imgSrc, fac.name);
    
    if (facilityTitle) {
      facilityTitle.classList.add('text-slide');
      setTimeout(() => {
        facilityTitle.textContent = fac.name;
        facilityTitle.classList.remove('text-slide');
      }, 300);
    }
    
    if (facilityDescription) {
      facilityDescription.classList.add('text-slide');
      setTimeout(() => {
        facilityDescription.textContent = fac.description || 'No description available.';
        facilityDescription.classList.remove('text-slide');
      }, 300);
    }
    
    renderInfo(fac);
  }

  // Navigation
  function goTo(index) {
    currentIndex = ((index % facilities.length) + facilities.length) % facilities.length;
    updateDisplay();
  }
  function next() { goTo(currentIndex + 1); }
  function prev() { goTo(currentIndex - 1); }

  // Autoplay
  function startAutoplay() {
    stopAutoplay();
    isPlaying = true;
    if (playPauseIcon) playPauseIcon.textContent = '❚❚';
    if (playPauseBtn) playPauseBtn.classList.remove('paused');
    autoPlayInterval = setInterval(() => {
      next();
    }, AUTOPLAY_MS);
  }
  
  function stopAutoplay() {
    isPlaying = false;
    if (playPauseIcon) playPauseIcon.textContent = '▶';
    if (playPauseBtn) playPauseBtn.classList.add('paused');
    if (autoPlayInterval) { 
      clearInterval(autoPlayInterval); 
      autoPlayInterval = null; 
    }
  }
  
  function togglePlay() { 
    if (autoPlayInterval) stopAutoplay(); 
    else startAutoplay(); 
  }

  // Tab switching
  function switchToTab(selector) {
    console.log('Switching to tab:', selector);
    tabButtons.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));

    const btn = Array.from(tabButtons).find(b => b.dataset.tab === selector);
    if (btn) {
      btn.classList.add('active');
      console.log('Tab button found and activated');
    } else {
      console.error('Tab button not found for selector:', selector);
    }

    const target = document.querySelector(selector);
    if (target) {
      target.classList.add('active');
      console.log('Tab content found and activated');
    } else {
      console.error('Tab content not found for selector:', selector);
    }

    // Autoplay only on details tab
    if (selector === '#detailsTab') startAutoplay(); 
    else stopAutoplay();
  }

  // Event listeners
  if (prevBtn) prevBtn.addEventListener('click', () => { prev(); stopAutoplay(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { next(); stopAutoplay(); });
  if (playPauseBtn) playPauseBtn.addEventListener('click', () => togglePlay());

  // Keyboard
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { prev(); stopAutoplay(); }
    if (e.key === 'ArrowRight') { next(); stopAutoplay(); }
    if (e.key === ' ') { e.preventDefault(); togglePlay(); }
  });

  // Tab buttons
  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      const selector = button.dataset.tab;
      switchToTab(selector);
    });
  });

  // Initialize
  function init() {
    updateDisplay();
    const startBtn = Array.from(tabButtons).find(b => b.classList.contains('active'));
    const startSelector = startBtn ? startBtn.dataset.tab : '#detailsTab';
    switchToTab(startSelector);
  }

  init();
});
</script>

</body>
</html>