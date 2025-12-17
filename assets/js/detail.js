/* ========================================
   PUSAKARASA - DETAIL PAGE JAVASCRIPT
   Functionality untuk halaman detail makanan
   ======================================== */

// ========== INITIALIZATION START ==========
document.addEventListener('DOMContentLoaded', function() {
  // Initialize favorite button
  initFavoriteButton();
  
  // Initialize action buttons
  initActionButtons();
  
  // Initialize review form
  initReviewForm();
  
  // Load existing reviews
  loadReviews();
});
// ========== INITIALIZATION END ==========

// ========== FAVORITE BUTTON START ==========
function initFavoriteButton() {
  const favoriteBtn = document.querySelector('.btn-favorite');
  
  if (!favoriteBtn) return;
  
  // Check if item is already in favorites
  const itemTitle = document.querySelector('.hero h2')?.textContent || '';
  const itemId = generateId(itemTitle);
  
  const favorites = getFavorites();
  if (favorites.includes(itemId)) {
    favoriteBtn.classList.add('active');
    favoriteBtn.querySelector('i').classList.remove('fa-regular');
    favoriteBtn.querySelector('i').classList.add('fa-solid');
  }
  
  // Toggle favorite on click
  favoriteBtn.addEventListener('click', function() {
    const icon = this.querySelector('i');
    const isActive = this.classList.toggle('active');
    
    if (isActive) {
      icon.classList.remove('fa-regular');
      icon.classList.add('fa-solid');
      addToFavorites(itemId);
    } else {
      icon.classList.remove('fa-solid');
      icon.classList.add('fa-regular');
      removeFromFavorites(itemId);
    }
  });
}

// Helper functions untuk favorites
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
// ========== FAVORITE BUTTON END ==========

// ========== ACTION BUTTONS START ==========
function initActionButtons() {
  // WhatsApp button
  const whatsappBtn = document.querySelector('.btn-whatsapp');
  if (whatsappBtn) {
    whatsappBtn.addEventListener('click', function() {
      const itemName = document.querySelector('.hero h2')?.textContent || 'produk';
      const message = `Halo, saya tertarik dengan ${itemName}`;
      window.open(`https://wa.me/6281234567890?text=${encodeURIComponent(message)}`, '_blank');
    });
  }
  
  // Instagram button
  const instagramBtn = document.querySelector('.btn-instagram');
  if (instagramBtn) {
    instagramBtn.addEventListener('click', function() {
      window.open('https://instagram.com/pusakarasa', '_blank');
    });
  }
  
  // Location button
  const locationBtn = document.querySelector('.btn-location');
  if (locationBtn) {
    locationBtn.addEventListener('click', function() {
      // Bisa diganti dengan Google Maps link atau lokasi spesifik
      window.open('https://maps.google.com', '_blank');
    });
  }
}
// ========== ACTION BUTTONS END ==========