/* ========================================
   PUSAKARASA - Simple Carousel Navigation
   Swipe-only navigation (No buttons)
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {
  const container = document.querySelector('.contact-container');
  const cards = document.querySelectorAll('.card');
  
  if (!container || !cards.length) return;

  let currentIndex = 0;
  const totalCards = cards.length;

  // Check if we're in mobile view
  function isMobileView() {
    return window.innerWidth <= 1024;
  }

  // Update carousel positions
  function updateCarousel() {
    if (!isMobileView()) {
      // Desktop mode - remove all classes
      cards.forEach(card => {
        card.classList.remove('active', 'left', 'right', 'hidden');
      });
      return;
    }

    // Mobile mode - carousel effect
    cards.forEach((card, index) => {
      // Remove all classes first
      card.classList.remove('active', 'left', 'right', 'hidden');
      
      if (index === currentIndex) {
        // Active card (center)
        card.classList.add('active');
      } else if (index === currentIndex - 1 || 
                 (currentIndex === 0 && index === totalCards - 1)) {
        // Left card
        card.classList.add('left');
      } else if (index === currentIndex + 1 || 
                 (currentIndex === totalCards - 1 && index === 0)) {
        // Right card
        card.classList.add('right');
      } else {
        // Hidden cards
        card.classList.add('hidden');
      }
    });

    updateIndicators();
  }

  // Go to specific index
  function goToIndex(index) {
    if (index < 0 || index >= totalCards) return;
    currentIndex = index;
    updateCarousel();
  }

  // Go to previous card
  function goToPrev() {
    currentIndex--;
    if (currentIndex < 0) {
      currentIndex = totalCards - 1;
    }
    updateCarousel();
  }

  // Go to next card
  function goToNext() {
    currentIndex++;
    if (currentIndex >= totalCards) {
      currentIndex = 0;
    }
    updateCarousel();
  }

  // Touch swipe support
  let touchStartX = 0;
  let touchEndX = 0;
  const swipeThreshold = 50;

  container.addEventListener('touchstart', function(e) {
    if (!isMobileView()) return;
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });

  container.addEventListener('touchend', function(e) {
    if (!isMobileView()) return;
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
  }, { passive: true });

  function handleSwipe() {
    const swipeDistance = touchStartX - touchEndX;
    
    if (Math.abs(swipeDistance) > swipeThreshold) {
      if (swipeDistance > 0) {
        // Swipe left (next)
        goToNext();
      } else {
        // Swipe right (prev)
        goToPrev();
      }
    }
  }

  // Keyboard navigation (optional)
  document.addEventListener('keydown', function(e) {
    if (!isMobileView()) return;
    
    if (e.key === 'ArrowLeft') {
      goToPrev();
    } else if (e.key === 'ArrowRight') {
      goToNext();
    }
  });

  // Create indicators
  function createIndicators() {
    const indicatorsContainer = document.createElement('div');
    indicatorsContainer.className = 'carousel-indicators';
    
    for (let i = 0; i < totalCards; i++) {
      const indicator = document.createElement('button');
      indicator.className = 'indicator';
      indicator.setAttribute('data-index', i);
      indicator.setAttribute('aria-label', `Go to card ${i + 1}`);
      indicator.addEventListener('click', function() {
        if (!isMobileView()) return;
        goToIndex(i);
      });
      indicatorsContainer.appendChild(indicator);
    }
    
    container.parentElement.appendChild(indicatorsContainer);
  }

  // Update indicators
  function updateIndicators() {
    const indicators = document.querySelectorAll('.indicator');
    indicators.forEach((indicator, index) => {
      if (index === currentIndex) {
        indicator.classList.add('active');
      } else {
        indicator.classList.remove('active');
      }
    });
  }

  // Handle window resize
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      updateCarousel();
    }, 250);
  });

  // Initialize
  createIndicators();
  updateCarousel();
});