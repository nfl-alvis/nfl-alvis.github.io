/* ========================================
   PUSAKARASA - MAIN JAVASCRIPT
   Unified script untuk semua halaman
   ======================================== */

// ========== INITIALIZATION START ==========
document.addEventListener('DOMContentLoaded', function() {
  initHamburgerMenu();
  initNavIndicator();
  initFilterButtons();
  initSearchBar();
  initFavoriteButtons();
  initDetailButtons();
  initSmoothScroll();
  initCardAnimations();
  
  console.log('✅ PusakaRasa initialized successfully');
});
// ========== INITIALIZATION END ==========

// ========== HAMBURGER MENU START ==========
function initHamburgerMenu() {
  const hamburger = document.getElementById('hamburgerBtn');
  const navMenu = document.getElementById('navMenu');
  
  if (!hamburger || !navMenu) return;
  
  // Toggle menu
  hamburger.addEventListener('click', function(e) {
    e.stopPropagation();
    const isOpen = navMenu.classList.toggle('show');
    hamburger.classList.toggle('active', isOpen);
    hamburger.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });
  
  // Close on outside click
  document.addEventListener('click', function(e) {
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      closeMenu();
    }
  });
  
  // Close on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && navMenu.classList.contains('show')) {
      closeMenu();
    }
  });
  
  // Close on window resize (mobile to desktop)
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      if (window.innerWidth > 600) {
        closeMenu();
      }
    }, 250);
  });
  
  // Close on nav link click
  navMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', function() {
      closeMenu();
    });
  });
  
  function closeMenu() {
    navMenu.classList.remove('show');
    hamburger.classList.remove('active');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }
}
// ========== HAMBURGER MENU END ==========

// ========== NAV INDICATOR START ==========
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
// ========== NAV INDICATOR END ==========

// ========== FILTER BUTTONS START ==========
function initFilterButtons() {
  const filterButtons = document.querySelectorAll('.btn-tertiary');
  const foodCards = document.querySelectorAll('.food-card');
  
  if (!filterButtons.length || !foodCards.length) return;
  
  filterButtons.forEach((button, index) => {
    button.addEventListener('click', function() {
      const filterText = this.textContent.trim().toLowerCase();
      
      // Update active state
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      
      // Filter cards
      let visibleCount = 0;
      
      foodCards.forEach((card, cardIndex) => {
        const tags = card.querySelectorAll('.image-tags span');
        let shouldShow = false;
        
        if (filterText === 'semua') {
          shouldShow = true;
        } else {
          tags.forEach(tag => {
            if (tag.textContent.toLowerCase().includes(filterText)) {
              shouldShow = true;
            }
          });
        }
        
        if (shouldShow) {
          card.style.display = 'flex';
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
          }, cardIndex * 30);
          visibleCount++;
        } else {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px) scale(0.95)';
          setTimeout(() => {
            card.style.display = 'none';
          }, 300);
        }
      });
      
      console.log(`Filter: ${filterText} - Showing ${visibleCount} items`);
    });
  });
  
  // Set default active
  if (filterButtons[0]) {
    filterButtons[0].classList.add('active');
  }
}
// ========== FILTER BUTTONS END ==========

// ========== SEARCH BAR START ==========
function initSearchBar() {
  const searchBar = document.querySelector('.search-bar');
  const searchButton = document.querySelector('.search-button');
  const foodCards = document.querySelectorAll('.food-card');
  
  if (!searchBar || !foodCards.length) return;
  
  let searchTimeout;
  
  // Real-time search with debounce
  searchBar.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      performSearch(this.value);
    }, 300);
  });
  
  // Search on button click
  if (searchButton) {
    searchButton.addEventListener('click', function(e) {
      e.preventDefault();
      performSearch(searchBar.value);
    });
  }
  
  // Search on Enter key
  searchBar.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      performSearch(this.value);
    }
  });
  
  function performSearch(query) {
    const searchTerm = query.toLowerCase().trim();
    let visibleCount = 0;
    
    foodCards.forEach((card, index) => {
      const title = card.querySelector('.food-title')?.textContent.toLowerCase() || '';
      const desc = card.querySelector('.food-desc')?.textContent.toLowerCase() || '';
      const tags = Array.from(card.querySelectorAll('.image-tags span'))
        .map(tag => tag.textContent.toLowerCase())
        .join(' ');
      const hashtag = card.querySelector('.food-tag')?.textContent.toLowerCase() || '';
      
      const isMatch = !searchTerm || 
                      title.includes(searchTerm) || 
                      desc.includes(searchTerm) || 
                      tags.includes(searchTerm) ||
                      hashtag.includes(searchTerm);
      
      if (isMatch) {
        card.style.display = 'flex';
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 30);
        visibleCount++;
      } else {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        setTimeout(() => {
          card.style.display = 'none';
        }, 300);
      }
    });
    
    // Show/hide no results message
    if (visibleCount === 0 && searchTerm) {
      showNoResults(searchTerm);
    } else {
      hideNoResults();
    }
    
    console.log(`Search: "${searchTerm}" - Found ${visibleCount} items`);
  }
  
  function showNoResults(query) {
    hideNoResults();
    
    const container = document.querySelector('.cards-container');
    if (!container) return;
    
    const message = document.createElement('div');
    message.className = 'no-results';
    message.innerHTML = `
      <p style="font-size: 18px; color: var(--color-text-secondary); margin-bottom: 8px;">
        Tidak ada hasil untuk "<strong>${escapeHtml(query)}</strong>"
      </p>
      <p style="font-size: 14px; color: var(--color-text-light);">
        Coba kata kunci lain atau lihat katalog lengkap
      </p>
    `;
    message.style.cssText = `
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
    `;
    
    container.appendChild(message);
  }
  
  function hideNoResults() {
    const message = document.querySelector('.no-results');
    if (message) message.remove();
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}
// ========== SEARCH BAR END ==========

// ========== FAVORITE BUTTONS START ==========
function initFavoriteButtons() {
  const favButtons = document.querySelectorAll('.fav-btn');
  
  if (!favButtons.length) return;
  
  // Load favorites from localStorage
  let favorites = getFavorites();
  
  favButtons.forEach((button, index) => {
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
        addToFavorites(itemId, title);
        showToast(`❤️ ${title} ditambahkan ke favorit`);
      } else {
        removeFromFavorites(itemId);
        showToast(`${title} dihapus dari favorit`);
      }
    });
  });
  
  function getFavorites() {
    try {
      return JSON.parse(localStorage.getItem('pusakarasa_favorites') || '[]');
    } catch (e) {
      console.error('Error loading favorites:', e);
      return [];
    }
  }
  
  function addToFavorites(id, title) {
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
// ========== FAVORITE BUTTONS END ==========

// ========== DETAIL BUTTONS START ==========
function initDetailButtons() {
  const detailButtons = document.querySelectorAll('.detail-btn');
  
  if (!detailButtons.length) return;
  
  detailButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      
      const card = this.closest('.food-card');
      const title = card?.querySelector('.food-title')?.textContent || '';
      const id = title.toLowerCase().replace(/\s+/g, '-');
      
      console.log(`Opening detail for: ${title} (${id})`);
      
      // TODO: Navigate to detail page
      // window.location.href = `detail.html?id=${id}`;
      
      showToast(`Membuka detail ${title}...`);
    });
  });
}
// ========== DETAIL BUTTONS END ==========

// ========== SMOOTH SCROLL START ==========
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      
      if (href === '#' || href === '#favorit') {
        e.preventDefault();
        return;
      }
      
      const target = document.querySelector(href);
      
      if (target) {
        e.preventDefault();
        const headerHeight = 80;
        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;
        
        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth'
        });
      }
    });
  });
}
// ========== SMOOTH SCROLL END ==========

// ========== CARD ANIMATIONS START ==========
function initCardAnimations() {
  const cards = document.querySelectorAll('.food-card');
  
  if (!cards.length) return;
  
  // Initial fade-in animation
  cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, index * 50);
  });
  
  // Intersection Observer for lazy loading
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.classList.add('loaded');
          imageObserver.unobserve(img);
        }
      });
    }, {
      rootMargin: '50px'
    });
    
    cards.forEach(card => {
      const img = card.querySelector('.card-image img');
      if (img) imageObserver.observe(img);
    });
  }
}
// ========== CARD ANIMATIONS END ==========

// ========== UTILITY FUNCTIONS START ==========
function showToast(message, duration = 2500) {
  // Remove existing toast
  const existingToast = document.querySelector('.pusakarasa-toast');
  if (existingToast) existingToast.remove();
  
  // Create toast element
  const toast = document.createElement('div');
  toast.className = 'pusakarasa-toast';
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    animation: toastSlideUp 0.3s ease;
    max-width: 90%;
    text-align: center;
  `;
  
  // Add animation keyframes
  if (!document.querySelector('#toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
      @keyframes toastSlideUp {
        from {
          opacity: 0;
          transform: translate(-50%, 20px);
        }
        to {
          opacity: 1;
          transform: translate(-50%, 0);
        }
      }
      @keyframes toastFadeOut {
        to {
          opacity: 0;
          transform: translate(-50%, -10px);
        }
      }
    `;
    document.head.appendChild(style);
  }
  
  document.body.appendChild(toast);
  
  // Remove after duration
  setTimeout(() => {
    toast.style.animation = 'toastFadeOut 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func.apply(this, args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function trackEvent(category, action, label) {
  // TODO: Integrate with analytics service (Google Analytics, etc.)
  console.log('📊 Event:', { category, action, label });
}
// ========== UTILITY FUNCTIONS END ==========

// ========== GLOBAL API START ==========
// Export functions for external use
window.PusakaRasa = {
  showToast,
  trackEvent,
  version: '1.0.0'
};
// ========== GLOBAL API END ==========

// Track page load
trackEvent('Page', 'Load', document.title);