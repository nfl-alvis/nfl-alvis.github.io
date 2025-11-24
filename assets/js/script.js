// ========================================
// PUSAKARASA - MAIN JAVASCRIPT
// ========================================

document.addEventListener('DOMContentLoaded', function() {
  // Initialize all features
  initHamburgerMenu();
  initFilterButtons();
  initSearchBar();
  initFavoriteButtons();
  initDetailButtons();
  initSmoothScroll();
  initLoadingAnimation();
});

// ========================================
// HAMBURGER MENU
// ========================================
function initHamburgerMenu() {
  const hamburger = document.getElementById('hamburgerBtn');
  const navMenu = document.getElementById('navMenu');
  
  if (!hamburger || !navMenu) return;
  
  hamburger.addEventListener('click', function() {
    const isActive = navMenu.classList.toggle('active');
    hamburger.classList.toggle('active');
    hamburger.setAttribute('aria-expanded', isActive);
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = isActive ? 'hidden' : '';
  });
  
  // Close menu when clicking outside
  document.addEventListener('click', function(e) {
    if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
      navMenu.classList.remove('active');
      hamburger.classList.remove('active');
      hamburger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }
  });
  
  // Close menu on window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
      navMenu.classList.remove('active');
      hamburger.classList.remove('active');
      hamburger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }
  });
}

// ========================================
// FILTER BUTTONS
// ========================================
function initFilterButtons() {
  const filterButtons = document.querySelectorAll('.btn-tertiary[data-filter]');
  const foodCards = document.querySelectorAll('.food-card[data-category]');
  
  if (!filterButtons.length || !foodCards.length) return;
  
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      const filter = this.getAttribute('data-filter');
      
      // Update active state
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      
      // Filter cards with animation
      foodCards.forEach(card => {
        const category = card.getAttribute('data-category');
        
        if (filter === 'all' || category === filter) {
          card.style.display = 'flex';
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'scale(1)';
          }, 10);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'scale(0.9)';
          setTimeout(() => {
            card.style.display = 'none';
          }, 300);
        }
      });
      
      // Show feedback
      showFilterFeedback(filter, foodCards);
    });
  });
}

function showFilterFeedback(filter, cards) {
  const visibleCards = Array.from(cards).filter(card => {
    return filter === 'all' || card.getAttribute('data-category') === filter;
  });
  
  console.log(`Menampilkan ${visibleCards.length} item untuk filter: ${filter}`);
}

// ========================================
// SEARCH BAR
// ========================================
function initSearchBar() {
  const searchForm = document.getElementById('searchForm');
  const searchInput = document.getElementById('searchInput');
  const foodCards = document.querySelectorAll('.food-card');
  
  if (!searchForm || !searchInput) return;
  
  // Real-time search
  searchInput.addEventListener('input', debounce(function() {
    performSearch(this.value, foodCards);
  }, 300));
  
  // Form submit
  searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    performSearch(searchInput.value, foodCards);
  });
}

function performSearch(query, cards) {
  const searchTerm = query.toLowerCase().trim();
  let visibleCount = 0;
  
  cards.forEach(card => {
    const title = card.querySelector('.food-title')?.textContent.toLowerCase() || '';
    const desc = card.querySelector('.food-desc')?.textContent.toLowerCase() || '';
    const tags = card.querySelector('.food-tag')?.textContent.toLowerCase() || '';
    const region = card.querySelector('.image-tags span:first-child')?.textContent.toLowerCase() || '';
    
    const isMatch = !searchTerm || 
                    title.includes(searchTerm) || 
                    desc.includes(searchTerm) || 
                    tags.includes(searchTerm) ||
                    region.includes(searchTerm);
    
    if (isMatch) {
      card.style.display = 'flex';
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'scale(1)';
      }, 10);
      visibleCount++;
    } else {
      card.style.opacity = '0';
      card.style.transform = 'scale(0.9)';
      setTimeout(() => {
        card.style.display = 'none';
      }, 300);
    }
  });
  
  // Show no results message
  if (visibleCount === 0 && searchTerm) {
    showNoResultsMessage(searchTerm);
  } else {
    removeNoResultsMessage();
  }
}

function showNoResultsMessage(query) {
  removeNoResultsMessage();
  
  const cardsContainer = document.querySelector('.cards-container');
  if (!cardsContainer) return;
  
  const message = document.createElement('div');
  message.className = 'no-results-message';
  message.innerHTML = `
    <p>Tidak ada hasil untuk "<strong>${query}</strong>"</p>
    <p>Coba kata kunci lain atau jelajahi katalog lengkap kami</p>
  `;
  
  message.style.cssText = `
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
  `;
  
  cardsContainer.appendChild(message);
}

function removeNoResultsMessage() {
  const message = document.querySelector('.no-results-message');
  if (message) message.remove();
}

// ========================================
// FAVORITE BUTTONS
// ========================================
function initFavoriteButtons() {
  const favButtons = document.querySelectorAll('.fav-btn');
  
  // Load favorites from localStorage
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
  
  favButtons.forEach(button => {
    const id = button.getAttribute('data-id');
    
    // Set initial state
    if (id && favorites.includes(id)) {
      button.classList.add('active');
    }
    
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleFavorite(this, id);
    });
  });
}

function toggleFavorite(button, id) {
  let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
  
  if (button.classList.contains('active')) {
    // Remove from favorites
    button.classList.remove('active');
    favorites = favorites.filter(fav => fav !== id);
    showToast('Dihapus dari favorit');
  } else {
    // Add to favorites
    button.classList.add('active');
    if (id && !favorites.includes(id)) {
      favorites.push(id);
    }
    showToast('Ditambahkan ke favorit ❤️');
  }
  
  localStorage.setItem('favorites', JSON.stringify(favorites));
}

// ========================================
// DETAIL BUTTONS
// ========================================
function initDetailButtons() {
  const detailButtons = document.querySelectorAll('.detail-btn');
  
  detailButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const card = this.closest('.food-card');
      const title = card.querySelector('.food-title')?.textContent;
      
      // In production, this would navigate to detail page
      console.log('Opening detail for:', id, title);
      showToast(`Membuka detail ${title || 'kuliner'}...`);
      
      // Simulate navigation
      setTimeout(() => {
        // window.location.href = `detail.html?id=${id}`;
      }, 500);
    });
  });
}

// ========================================
// SMOOTH SCROLL
// ========================================
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#') return;
      
      e.preventDefault();
      const target = document.querySelector(href);
      
      if (target) {
        const headerOffset = 80;
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
      }
    });
  });
}

// ========================================
// LOADING ANIMATION
// ========================================
function initLoadingAnimation() {
  const cards = document.querySelectorAll('.food-card');
  
  // Fade in cards on load
  cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, index * 50);
  });
  
  // Lazy load images
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          const src = img.getAttribute('src');
          
          if (src) {
            img.classList.add('loaded');
          }
          
          observer.unobserve(img);
        }
      });
    });
    
    document.querySelectorAll('.card-image img').forEach(img => {
      imageObserver.observe(img);
    });
  }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
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

function showToast(message, duration = 2000) {
  // Remove existing toast
  const existingToast = document.querySelector('.toast');
  if (existingToast) existingToast.remove();
  
  // Create toast
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    z-index: 10000;
    animation: slideUp 0.3s ease;
    backdrop-filter: blur(10px);
  `;
  
  document.body.appendChild(toast);
  
  // Add animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translate(-50%, 20px);
      }
      to {
        opacity: 1;
        transform: translate(-50%, 0);
      }
    }
  `;
  document.head.appendChild(style);
  
  // Remove after duration
  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ========================================
// ANALYTICS (Optional)
// ========================================
function trackEvent(category, action, label) {
  // In production, integrate with analytics service
  console.log('Event tracked:', { category, action, label });
}

// Track page view
trackEvent('Page', 'View', 'Homepage');

// Export functions for use in other scripts if needed
window.PusakaRasa = {
  showToast,
  trackEvent,
  toggleFavorite
};