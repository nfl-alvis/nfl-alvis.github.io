/* ========================================
   PUSAKARASA - MAIN JAVASCRIPT (SIMPLIFIED)
   Hanya 3 fungsi: Hamburger, Favorite, Nav Indicator
   ======================================== */

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
  initHamburgerMenu();
  initNavIndicator();
  initFavoriteButtons();
});

// ========== HAMBURGER MENU ==========
function initHamburgerMenu() {
  const hamburger = document.getElementById('hamburgerBtn');
  const navMenu = document.getElementById('navMenu');
  
  if (!hamburger || !navMenu) return;
  
  // Toggle menu
  hamburger.addEventListener('click', function(e) {
    e.stopPropagation();
    const isOpen = navMenu.classList.toggle('show');
    hamburger.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });
  
  // Close on outside click
  document.addEventListener('click', function(e) {
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });
  
  // Close on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && navMenu.classList.contains('show')) {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });
  
  // Close on window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth > 600) {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });
  
  // Close on nav link click
  navMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', function() {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    });
  });
}

// ========== NAV INDICATOR ==========
function initNavIndicator() {
  const navLinks = document.querySelectorAll('nav ul li a');
  const indicator = document.querySelector('.nav-indicator');
  
  if (!indicator || !navLinks.length) return;
  
  // Set active link based on current page
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  
  navLinks.forEach(link => {
    const linkPage = link.getAttribute('href').split('/').pop();
    
    if (linkPage === currentPage) {
      setIndicator(link);
    }
    
    // Hover effect
    link.addEventListener('mouseenter', function() {
      setIndicator(this);
    });
  });
  
  // Reset on mouse leave
  document.querySelector('nav ul').addEventListener('mouseleave', function() {
    const activeLink = document.querySelector('nav ul li a[href$="' + currentPage + '"]');
    if (activeLink) {
      setIndicator(activeLink);
    } else {
      indicator.style.width = '0';
    }
  });
  
  function setIndicator(element) {
    const linkRect = element.getBoundingClientRect();
    const navRect = element.closest('nav ul').getBoundingClientRect();
    
    indicator.style.left = (linkRect.left - navRect.left) + 'px';
    indicator.style.width = linkRect.width + 'px';
  }
}

// ========== FAVORITE BUTTONS ==========
function initFavoriteButtons() {
  const favButtons = document.querySelectorAll('.fav-btn');
  
  if (!favButtons.length) return;
  
  // Load favorites from localStorage
  let favorites = getFavorites();
  
  favButtons.forEach(button => {
    const card = button.closest('.food-card');
    const title = card?.querySelector('.food-title')?.textContent || '';
    const itemId = generateId(title);
    
    button.setAttribute('data-id', itemId);
    
    // Set initial state
    if (favorites.includes(itemId)) {
      button.classList.add('active');
    }
    
    // Click handler
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      
      const isActive = this.classList.toggle('active');
      
      if (isActive) {
        addToFavorites(itemId);
      } else {
        removeFromFavorites(itemId);
      }
    });
  });
  
  function getFavorites() {
    try {
      return JSON.parse(localStorage.getItem('pusakarasa_favorites') || '[]');
    } catch (e) {
      return [];
    }
  }
  
  function addToFavorites(id) {
    let favorites = getFavorites();
    if (!favorites.includes(id)) {
      favorites.push(id);
      localStorage.setItem('pusakarasa_favorites', JSON.stringify(favorites));
    }
  }
  
  function removeFromFavorites(id) {
    let favorites = getFavorites();
    favorites = favorites.filter(fav => fav !== id);
    localStorage.setItem('pusakarasa_favorites', JSON.stringify(favorites));
  }
  
  function generateId(text) {
    return text.toLowerCase().replace(/\s+/g, '-');
  }
}