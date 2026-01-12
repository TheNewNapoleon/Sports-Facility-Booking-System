<?php
session_start();
// Optional: show logged-in user name if available
$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name'], ENT_QUOTES) : null;
$avatar = isset($_SESSION['avatar_path']) ? htmlspecialchars($_SESSION['avatar_path'], ENT_QUOTES) : 'images/avatar/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TARUMT Sabah Sport Facilities</title>
  <link rel="stylesheet" href="css/homepage.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="description" content="Book sports facilities at TARUMT Sabah quickly and easily">
</head>
<body>

  <header>
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
      <div class="nav-inner">
        <a class="site-brand" href="index.php">
          <img src="images/logo1.png" alt="TARUMT logo" class="brand-img">
          <div class="brand-text">
            <div class="logo-title">TARUMT Sports Club</div>
            <small class="logo-sub">Sabah, Malaysia</small>
          </div>
        </a>

        <button class="mobile-menu-btn" aria-expanded="false" aria-controls="main-nav" id="menuBtn">
          <i class="fas fa-bars" aria-hidden="true"></i>
          <span class="sr-only">Toggle menu</span>
        </button>

        <div id="main-nav" class="nav-menu" role="menubar">
          <ul class="nav-links">
            <li><a class="nav-link active" href="#home">Home</a></li>
            <li><a class="nav-link" href="#facilities">Facilities</a></li>
            <li><a class="nav-link" href="booking_facilities.php">Booking</a></li>
            <li><a class="nav-link" href="#contact">Contact</a></li>
          </ul>

          <div class="nav-actions">
            <?php if($userName): ?>
              <div class="user-menu">
                <img src="<?= $avatar ?>" alt="User avatar" class="avatar">
                <div class="user-info">
                  <a href="profile.php" class="nav-link user-name">Hi, <?= $userName ?></a>
                  <a href="logout.php" class="btn-login">Logout</a>
                </div>
              </div>
            <?php else: ?>
              <a class="btn-login" href="Login.php">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <section id="home" class="hero" aria-labelledby="hero-title">
      
      <div class="hero-container">
        <div class="hero-content">
          <span class="hero-badge">Next-Gen Experience</span>
          <h1 id="hero-title" class="hero-title">
            Book TARUMT Sabah 
            <span class="hero-title-gradient">Sport Facilities</span>
          </h1>
          <p class="hero-description">Easily reserve courts, gym time, or pools — manage your bookings on campus with just a few clicks.</p>
          <div class="cta-buttons">
            <a class="btn-primary" href="booking_facilities.php">
              Book Now <i class="fas fa-arrow-right"></i>
            </a>
            <a class="btn-secondary" href="#facilities">View Facilities</a>
          </div>
        </div>
      </div>
    </section>

    <section id="facilities" class="features" aria-labelledby="facilities-heading">
      <div class="section-header">
        <h2 id="facilities-heading">Our Main Facilities</h2>
        <p class="section-subtitle">Experience world-class sports facilities on campus</p>
      </div>
      
      <div class="cards">
        <article class="card" data-facility="basketball">
          <i class="fas fa-basketball-ball" aria-hidden="true"></i>
          <h3>Basketball</h3>
          <p>Play matches or practice your shots with friends anytime.</p>
        </article>

        <article class="card" data-facility="futsal">
          <i class="fas fa-futbol"></i>
          <h3>Futsal</h3>
          <p>5-a-side football for students and staff.</p>
        </article>

        <article class="card" data-facility="gym">
          <i class="fas fa-dumbbell"></i>
          <h3>Gym</h3>
          <p>Modern equipment for strength and cardio training.</p>
        </article>

        <article class="card" data-facility="badminton">
          <i class="fas fa-medal"></i>
          <h3>Badminton</h3>
          <p>Courts available for practice and friendly matches.</p>
        </article>

        <article class="card" data-facility="swimming">
          <i class="fas fa-swimmer"></i>
          <h3>Swimming Pool</h3>
          <p>Open for laps, training, and recreational use.</p>
        </article>

        <article class="card" data-facility="pingpong">
          <i class="fas fa-table-tennis-paddle-ball"></i>
          <h3>Ping Pong</h3>
          <p>Equipment available for student use.</p>
        </article>
      </div>
    </section>

    <section class="facility-showcase" aria-label="Facility showcase">
      <h2 class="showcase-title">Facilities Showcase</h2>
      <p class="showcase-subtitle">Take a visual tour of our amazing facilities</p>
      
      <div class="slider" id="slider">
        <div class="slide-track" id="slideTrack">
          <div class="slide">
            <img src="images/fac1.jpg" alt="Basketball court" loading="lazy">
            <div class="slide-overlay">
              <span>Basketball Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac2.jpg" alt="Futsal court" loading="lazy">
            <div class="slide-overlay">
              <span>Futsal Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac3.jpeg" alt="Gym equipment" loading="lazy">
            <div class="slide-overlay">
              <span>Gym Equipment</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac4.jpg" alt="Badminton court" loading="lazy">
            <div class="slide-overlay">
              <span>Badminton Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac5.jpg" alt="Swimming pool" loading="lazy">
            <div class="slide-overlay">
              <span>Swimming Pool</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac6.jpeg" alt="Ping pong tables" loading="lazy">
            <div class="slide-overlay">
              <span>Ping Pong Tables</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac7.jpg" alt="Volleyball court" loading="lazy">
            <div class="slide-overlay">
              <span>Volleyball Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac8.png" alt="Practice area" loading="lazy">
            <div class="slide-overlay">
              <span>Practice Area</span>
            </div>
          </div>
          
          <!-- Duplicate for smooth loop -->
          <div class="slide">
            <img src="images/fac1.jpg" alt="Basketball court" loading="lazy">
            <div class="slide-overlay">
              <span>Basketball Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac2.jpg" alt="Futsal court" loading="lazy">
            <div class="slide-overlay">
              <span>Futsal Court</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac3.jpeg" alt="Gym equipment" loading="lazy">
            <div class="slide-overlay">
              <span>Gym Equipment</span>
            </div>
          </div>
          <div class="slide">
            <img src="images/fac4.jpg" alt="Badminton court" loading="lazy">
            <div class="slide-overlay">
              <span>Badminton Court</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="contact" class="contact-section">
      <h2 class="contact-title">Get In Touch</h2>
      <p class="contact-subtitle">Have questions? We're here to help!</p>
      
      <div class="contact-card-horizontal">
        <div class="contact-left">
          <img src="images/logo1.png" alt="TARUMT Logo" class="contact-logo-large">
          <h3>TARUMT Sports Club</h3>
          <p>Your campus facility booking system</p>
          <div class="contact-stats">
            <div class="stat-item">
              <span class="stat-number">24/7</span>
              <span class="stat-label">Support</span>
            </div>
          </div>
        </div>
        
        <div class="contact-right">
          <a href="mailto:sabah@tarc.edu.my" class="info-row">
            <i class="fas fa-envelope"></i>
            <span>sabah@tarc.edu.my</span>
          </a>
          <a href="https://www.facebook.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
            <i class="fab fa-facebook"></i>
            <span>TAR UMT Sabah Branch</span>
          </a>
          <a href="https://www.instagram.com/tarumtsabah" class="info-row" target="_blank" rel="noopener">
            <i class="fab fa-instagram"></i>
            <span>tarumtsabah</span>
          </a>
          <a href="https://x.com/TARUMT_Sabah" class="info-row" target="_blank" rel="noopener">
            <i class="fab fa-x-twitter"></i>
            <span>@TARUMT_Sabah</span>
          </a>
          <a href="tel:+60111082519" class="info-row">
            <i class="fab fa-whatsapp"></i>
            <span>(6)011-1082 5619</span>
          </a>
          <a href="https://maps.app.goo.gl/4K9jrb9DcXMdu68JA" class="info-row" target="_blank" rel="noopener">
            <i class="fas fa-location-dot"></i>
            <span>Jalan Alamesra, Alamesra, 88450 Kota Kinabalu, Sabah.</span>
          </a>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-left">
          <div class="footer-copyright">&copy; <?= date('Y') ?> TARUMT Sabah Sport Club. All rights reserved.</div>
        </div>
      </div>
    </div>
  </footer>

  <script>
/**
 * TARUMT Sabah Sport Facilities - Homepage JavaScript
 * Enhanced interactions and animations
 */

(function() {
  'use strict';

  // ============================================
  // INITIALIZATION
  // ============================================
  document.addEventListener('DOMContentLoaded', function() {
    initNavbar();
    initMobileMenu();
    initCardReveal();
    initSlider();
    initSmoothScroll();
    initActiveNavLinks();
    initLazyLoading();
    initDisableSelectionDrag();
  });

  // ============================================
  // NAVBAR FUNCTIONALITY
  // ============================================
  function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    let lastScroll = 0;
    let ticking = false;

    function updateNavbar() {
      const currentScroll = window.scrollY;

      // Add solid background when scrolled more than 50px
      if (currentScroll > 50) {
        navbar.classList.add('solid');
      } else {
        navbar.classList.remove('solid');
      }

      lastScroll = currentScroll;
      ticking = false;
    }

    function requestTick() {
      if (!ticking) {
        window.requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }

    // Initial check
    updateNavbar();

    // Optimized scroll listener
    window.addEventListener('scroll', requestTick, { passive: true });
  }

  // ============================================
  // MOBILE MENU
  // ============================================
  function initMobileMenu() {
    const menuBtn = document.getElementById('menuBtn');
    const mainNav = document.getElementById('main-nav');
    
    if (!menuBtn || !mainNav) return;

    const navLinks = mainNav.querySelectorAll('a');

    function toggleMenu(open) {
      menuBtn.setAttribute('aria-expanded', String(open));
      mainNav.classList.toggle('show', open);
      document.body.style.overflow = open ? 'hidden' : '';

      // Toggle icon
      const icon = menuBtn.querySelector('i');
      if (open) {
        icon.classList.replace('fa-bars', 'fa-xmark');
      } else {
        icon.classList.replace('fa-xmark', 'fa-bars');
      }
    }

    // Click handler
    menuBtn.addEventListener('click', function() {
      const isOpen = menuBtn.getAttribute('aria-expanded') === 'true';
      toggleMenu(!isOpen);
    });

    // Close menu when clicking on a link
    navLinks.forEach(function(link) {
      link.addEventListener('click', function() {
        toggleMenu(false);
      });
    });

    // Keyboard support
    menuBtn.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const isOpen = menuBtn.getAttribute('aria-expanded') === 'true';
        toggleMenu(!isOpen);
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        toggleMenu(false);
      }
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      const isClickInsideMenu = mainNav.contains(e.target);
      const isClickOnButton = menuBtn.contains(e.target);
      const isOpen = menuBtn.getAttribute('aria-expanded') === 'true';

      if (isOpen && !isClickInsideMenu && !isClickOnButton) {
        toggleMenu(false);
      }
    });
  }

  // ============================================
  // CARD REVEAL ANIMATION
  // ============================================
  function initCardReveal() {
    const cards = document.querySelectorAll('.card');
    if (cards.length === 0) return;

    const observerOptions = {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    // Staggered reveal with CSS custom property
    cards.forEach(function(card, index) {
      card.style.setProperty('--delay', (index * 110) + 'ms');
      observer.observe(card);
    });
  }

  // ============================================
  // SLIDER/CAROUSEL
  // ============================================
  function initSlider() {
    const track = document.getElementById('slideTrack');
    if (!track) return;

    let position = 0;
    let isPaused = false;
    let animationId;
    const speed = 0.5; // pixels per frame

    function animate() {
      if (!isPaused) {
        position -= speed;

        // Reset when first half has passed
        if (Math.abs(position) >= track.scrollWidth / 2) {
          position = 0;
        }

        track.style.transform = 'translateX(' + position + 'px)';
      }

      animationId = requestAnimationFrame(animate);
    }

    // Pause on hover
    track.addEventListener('mouseenter', function() {
      isPaused = true;
    });

    track.addEventListener('mouseleave', function() {
      isPaused = false;
    });

    // Start animation
    animationId = requestAnimationFrame(animate);

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
      cancelAnimationFrame(animationId);
    });
  }

  // ============================================
  // SMOOTH SCROLL
  // ============================================
  function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(function(link) {
      link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        
        // Skip empty hash or just "#"
        if (!href || href === '#') {
          e.preventDefault();
          return;
        }

        const target = document.querySelector(href);
        
        if (target) {
          e.preventDefault();
          
          const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 60;
          const targetPosition = target.getBoundingClientRect().top + window.scrollY - navbarHeight;

          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });

          // Update URL without jumping
          if (history.pushState) {
            history.pushState(null, null, href);
          }
        }
      });
    });
  }

  // ============================================
  // ACTIVE NAV LINKS
  // ============================================
  function initActiveNavLinks() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    
    if (sections.length === 0 || navLinks.length === 0) return;

    let ticking = false;

    function updateActiveLink() {
      const scrollY = window.scrollY + 120;

      sections.forEach(function(section) {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');

        if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
          navLinks.forEach(function(link) {
            link.classList.remove('active');
            
            const href = link.getAttribute('href');
            if (href === '#' + sectionId) {
              link.classList.add('active');
            }
          });
        }
      });

      ticking = false;
    }

    function requestTick() {
      if (!ticking) {
        window.requestAnimationFrame(updateActiveLink);
        ticking = true;
      }
    }

    // Initial check
    updateActiveLink();

    // Optimized scroll listener
    window.addEventListener('scroll', requestTick, { passive: true });
  }

  // ============================================
  // LAZY LOADING IMAGES
  // ============================================
  function initLazyLoading() {
    if (!('IntersectionObserver' in window)) {
      return; // Skip if not supported
    }

    const images = document.querySelectorAll('img[loading="lazy"]');
    if (images.length === 0) return;

    const imageObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          const img = entry.target;
          
          // Add fade-in effect
          img.style.opacity = '0';
          img.style.transition = 'opacity 0.3s ease';

          img.addEventListener('load', function() {
            img.style.opacity = '1';
          });

          // If image is already loaded
          if (img.complete) {
            img.style.opacity = '1';
          }

          imageObserver.unobserve(img);
        }
      });
    }, {
      rootMargin: '50px 0px'
    });

    images.forEach(function(img) {
      imageObserver.observe(img);
    });
  }

  // ============================================
  // UTILITY FUNCTIONS
  // ============================================
  
  // Scroll to top function (for footer link)
  window.scrollToTop = function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };

  // Debounce function for performance
  window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction() {
      const context = this;
      const args = arguments;
      
      const later = function() {
        timeout = null;
        func.apply(context, args);
      };
      
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  // Throttle function for scroll events
  window.throttle = function(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(function() {
          inThrottle = false;
        }, limit);
      }
    };
  };

  // ============================================
  // DISABLE SELECTION & IMAGE DRAG
  // ============================================
  function initDisableSelectionDrag() {
    // Apply 'no-select' class so CSS rules take effect site-wide
    if (document.body) document.body.classList.add('no-select');

    // Make images non-draggable and prevent dragstart events
    document.querySelectorAll('img').forEach(function(img) {
      try {
        img.setAttribute('draggable', 'false');
      } catch (e) {
        // ignore
      }
      img.addEventListener('dragstart', function(e) { e.preventDefault(); });
    });

    // Fallback: block dragstart at document level for images
    document.addEventListener('dragstart', function(e) {
      if (e.target && e.target.tagName === 'IMG') {
        e.preventDefault();
      }
    }, { passive: false });
  }

  // ============================================
  // PERFORMANCE MONITORING (Development only)
  // ============================================
  if (window.performance && console.log) {
    window.addEventListener('load', function() {
      setTimeout(function() {
        const perfData = window.performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        const connectTime = perfData.responseEnd - perfData.requestStart;
        const renderTime = perfData.domComplete - perfData.domLoading;

        console.log('%c⚡ Performance Metrics', 'color: #ff7b00; font-weight: bold; font-size: 14px;');
        console.log('Page Load Time: ' + pageLoadTime + 'ms');
        console.log('Connect Time: ' + connectTime + 'ms');
        console.log('Render Time: ' + renderTime + 'ms');
      }, 0);
    });
  }

})();
  </script>

</body>
</html>