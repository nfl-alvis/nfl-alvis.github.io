document.addEventListener('DOMContentLoaded', function () {
  initHamburgerMenu();
  initNavIndicator();
  initProfileMenu();
  initFavoriteButtons();
  initStarRatingInputs();
  initFavoritesPage();
  initFlashBanner();
});

function initHamburgerMenu() {
  const hamburger = document.getElementById('hamburgerBtn');
  const navMenu = document.getElementById('navMenu');

  if (!hamburger || !navMenu) {
    return;
  }

  hamburger.addEventListener('click', function (event) {
    event.stopPropagation();
    const isOpen = navMenu.classList.toggle('show');
    hamburger.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  document.addEventListener('click', function (event) {
    if (!navMenu.contains(event.target) && !hamburger.contains(event.target)) {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });
}

function initNavIndicator() {
  const navLinks = document.querySelectorAll('nav ul li a');
  const indicator = document.querySelector('.nav-indicator');
  const menu = document.querySelector('nav ul');

  if (!indicator || !navLinks.length || !menu || window.innerWidth <= 600) {
    return;
  }

  const currentPage = window.location.pathname.split('/').pop() || 'index.php';

  navLinks.forEach(function (link) {
    const href = link.getAttribute('href') || '';
    const linkPage = href.split('/').pop().split('?')[0];

    if (linkPage === currentPage || link.classList.contains('active')) {
      setIndicator(link);
    }

    link.addEventListener('mouseenter', function () {
      setIndicator(link);
    });
  });

  menu.addEventListener('mouseleave', function () {
    const activeLink = document.querySelector('nav ul li a.active');
    if (activeLink) {
      setIndicator(activeLink);
    } else {
      indicator.style.width = '0';
    }
  });

  function setIndicator(element) {
    const linkRect = element.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    indicator.style.left = linkRect.left - menuRect.left + 'px';
    indicator.style.width = linkRect.width + 'px';
  }
}

function initProfileMenu() {
  const menu = document.getElementById('profileMenu');
  const button = document.getElementById('profileMenuButton');
  const dropdown = document.getElementById('profileDropdown');

  if (!menu || !button || !dropdown) {
    return;
  }

  button.addEventListener('click', function (event) {
    event.stopPropagation();
    const isOpen = dropdown.classList.toggle('show');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('click', function (event) {
    if (!menu.contains(event.target)) {
      dropdown.classList.remove('show');
      button.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      dropdown.classList.remove('show');
      button.setAttribute('aria-expanded', 'false');
    }
  });
}

function initFavoriteButtons() {
  const favButtons = document.querySelectorAll('.fav-btn');

  if (!favButtons.length) {
    return;
  }

  const favorites = getFavorites();

  favButtons.forEach(function (button) {
    const card = button.closest('.food-card');
    const itemId = button.dataset.id || card?.dataset.favoriteId || '';

    if (!itemId) {
      return;
    }

    button.dataset.id = itemId;
    button.setAttribute('aria-pressed', favorites.includes(itemId) ? 'true' : 'false');

    if (favorites.includes(itemId)) {
      button.classList.add('active');
    }

    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();

      const nextFavorites = getFavorites();
      const currentIndex = nextFavorites.indexOf(itemId);
      const willBeActive = !button.classList.contains('active');

      button.classList.toggle('active', willBeActive);
      button.setAttribute('aria-pressed', willBeActive ? 'true' : 'false');

      if (willBeActive && currentIndex === -1) {
        nextFavorites.push(itemId);
      }

      if (!willBeActive && currentIndex >= 0) {
        nextFavorites.splice(currentIndex, 1);
      }

      localStorage.setItem('pusakarasa_favorites', JSON.stringify(nextFavorites));
      window.dispatchEvent(new CustomEvent('pusakarasa:favorites-updated', { detail: nextFavorites }));
    });
  });
}

function initStarRatingInputs() {
  const groups = document.querySelectorAll('.star-rating-field');

  groups.forEach(function (group) {
    const options = Array.from(group.querySelectorAll('.star-rating-option'));
    const inputs = Array.from(group.querySelectorAll('input[type="radio"]'));

    if (!options.length || !inputs.length) {
      return;
    }

    function paintStars(value) {
      options.forEach(function (option) {
        const starValue = Number(option.dataset.value || '0');
        option.classList.toggle('is-active', starValue <= value);
      });
      group.dataset.selected = String(value);
    }

    options.forEach(function (option) {
      const input = option.querySelector('input[type="radio"]');
      const value = Number(option.dataset.value || input?.value || '0');

      option.addEventListener('mouseenter', function () {
        paintStars(value);
      });

      option.addEventListener('click', function () {
        if (!input) {
          return;
        }

        input.checked = true;
        paintStars(value);
      });
    });

    group.addEventListener('mouseleave', function () {
      const checked = inputs.find(function (input) {
        return input.checked;
      });
      paintStars(Number(checked?.value || '0'));
    });

    const checked = inputs.find(function (input) {
      return input.checked;
    });
    paintStars(Number(checked?.value || '0'));
  });
}

function initFavoritesPage() {
  const favoritesGrid = document.getElementById('favoritesGrid');
  const favoritesEmpty = document.getElementById('favoritesEmpty');
  const favoriteCount = document.getElementById('favoriteCount');

  if (!favoritesGrid) {
    return;
  }

  const cards = Array.from(favoritesGrid.querySelectorAll('.food-card'));

  function syncFavoritesView() {
    const favorites = getFavorites();
    let visibleCount = 0;

    cards.forEach(function (card) {
      const itemId = card.dataset.favoriteId || '';
      const isVisible = favorites.includes(itemId);
      card.style.display = isVisible ? '' : 'none';

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (favoriteCount) {
      favoriteCount.textContent = String(visibleCount);
    }

    if (favoritesEmpty) {
      favoritesEmpty.classList.toggle('show', visibleCount === 0);
    }
  }

  syncFavoritesView();
  window.addEventListener('pusakarasa:favorites-updated', syncFavoritesView);
}

function initFlashBanner() {
  const banner = document.querySelector('.flash-banner');

  if (!banner) {
    return;
  }

  window.setTimeout(function () {
    banner.classList.add('is-hidden');
  }, 3200);
}

function getFavorites() {
  try {
    return JSON.parse(localStorage.getItem('pusakarasa_favorites') || '[]');
  } catch (error) {
    return [];
  }
}
