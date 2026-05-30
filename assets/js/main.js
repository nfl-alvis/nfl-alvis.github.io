document.addEventListener('DOMContentLoaded', function () {
  initHamburgerMenu();
  initNavIndicator();
  initProfileMenu();
  initFavoriteButtons();
  initStarRatingInputs();
  initFavoritesPage();
  initFlashBanner();
  initFileDropStates();
  initPriceFormatInputs();
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

  function syncFavoriteButton(button, isActive) {
    button.classList.toggle('active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

    const icon = button.querySelector('i');
    if (icon) {
      icon.classList.toggle('fa-solid', isActive);
      icon.classList.toggle('fa-regular', !isActive);
    }

    const label = button.querySelector('span');
    if (label && button.classList.contains('dp-fav-btn')) {
      label.textContent = isActive ? 'Tersimpan' : 'Favoritkan';
      button.setAttribute('aria-label', isActive ? 'Hapus dari favorit' : 'Simpan ke favorit');
    }
  }

  favButtons.forEach(function (button) {
    const card = button.closest('.food-card');
    const itemId = button.dataset.id || card?.dataset.favoriteId || '';

    if (!itemId) {
      return;
    }

    button.dataset.id = itemId;
    syncFavoriteButton(button, favorites.includes(itemId));

    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();

      const nextFavorites = getFavorites();
      const currentIndex = nextFavorites.indexOf(itemId);
      const willBeActive = !button.classList.contains('active');

      syncFavoriteButton(button, willBeActive);

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
  const countEls = [
    document.getElementById('favoriteCount'),
    document.getElementById('heroCount'),
    document.getElementById('favCount')
  ].filter(Boolean);
  const storeCount = document.getElementById('storeCount');
  const clearButton = document.getElementById('favClearBtn');
  const sortSelect = document.getElementById('favSortSelect');

  if (!favoritesGrid) {
    return;
  }

  const cards = Array.from(favoritesGrid.querySelectorAll('.food-card'));
  const initialOrder = new Map(cards.map(function (card, index) {
    return [card, index];
  }));

  function sortCards(favorites) {
    if (!sortSelect) {
      return;
    }

    const favoriteOrder = new Map(favorites.map(function (id, index) {
      return [id, index];
    }));

    const sortedCards = cards.slice().sort(function (a, b) {
      const mode = sortSelect.value;

      if (mode === 'price-low') {
        return getCardPrice(a) - getCardPrice(b);
      }

      if (mode === 'rating-high') {
        return getCardRating(b) - getCardRating(a);
      }

      if (mode === 'name-az') {
        return getCardTitle(a).localeCompare(getCardTitle(b), 'id');
      }

      const orderA = favoriteOrder.get(a.dataset.favoriteId || '') ?? initialOrder.get(a) ?? 0;
      const orderB = favoriteOrder.get(b.dataset.favoriteId || '') ?? initialOrder.get(b) ?? 0;
      return orderB - orderA;
    });

    sortedCards.forEach(function (card) {
      favoritesGrid.appendChild(card);
    });

    if (favoritesEmpty) {
      favoritesGrid.appendChild(favoritesEmpty);
    }
  }

  function syncFavoritesView() {
    const favorites = getFavorites();
    const visibleStores = new Set();
    let visibleCount = 0;

    sortCards(favorites);

    cards.forEach(function (card) {
      const itemId = card.dataset.favoriteId || '';
      const isVisible = favorites.includes(itemId);
      const button = card.querySelector('.fav-btn');
      card.style.display = isVisible ? '' : 'none';

      if (button) {
        button.classList.toggle('active', isVisible);
        button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
      }

      if (isVisible) {
        visibleCount += 1;
        const storeName = card.querySelector('.food-store')?.textContent.trim();
        if (storeName) {
          visibleStores.add(storeName);
        }
      }
    });

    countEls.forEach(function (countEl) {
      countEl.textContent = String(visibleCount);
    });

    if (storeCount) {
      storeCount.textContent = String(visibleStores.size);
    }

    if (favoritesEmpty) {
      favoritesEmpty.classList.toggle('show', visibleCount === 0);
    }
  }

  if (clearButton) {
    clearButton.addEventListener('click', function () {
      const cardIds = new Set(cards.map(function (card) {
        return card.dataset.favoriteId || '';
      }).filter(Boolean));
      const remainingFavorites = getFavorites().filter(function (itemId) {
        return !cardIds.has(itemId);
      });

      localStorage.setItem('pusakarasa_favorites', JSON.stringify(remainingFavorites));
      window.dispatchEvent(new CustomEvent('pusakarasa:favorites-updated', { detail: remainingFavorites }));
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', syncFavoritesView);
  }

  syncFavoritesView();
  window.addEventListener('pusakarasa:favorites-updated', syncFavoritesView);
}

function getCardTitle(card) {
  return card.querySelector('.food-title')?.textContent.trim() || '';
}

function getCardRating(card) {
  const ratingText = card.querySelector('.review')?.textContent.trim() || '0';
  return Number.parseFloat(ratingText.replace(',', '.')) || 0;
}

function getCardPrice(card) {
  const priceText = card.querySelector('.food-price')?.textContent || '0';
  return Number(priceText.replace(/[^\d]/g, '')) || 0;
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

function initFileDropStates() {
  const dropZones = document.querySelectorAll('.file-drop, .create-store-file-drop, .photo-drop-zone');

  dropZones.forEach(function (dropZone) {
    const input = dropZone.querySelector('input[type="file"]');
    const hint = dropZone.querySelector('.file-drop-sub, .create-store-file-sub, .photo-drop-sub');
    let fileList = dropZone.querySelector('.file-drop-list');

    if (!input) {
      return;
    }

    const defaultHint = hint?.textContent || '';

    input.addEventListener('change', function () {
      const files = Array.from(input.files || []);
      const hasFile = files.length > 0;

      dropZone.classList.toggle('has-file', hasFile);

      if (hint) {
        hint.textContent = hasFile
          ? (files.length > 1 ? files.length + ' file dipilih' : files[0].name)
          : defaultHint;
      }

      if (!hasFile) {
        fileList?.remove();
        fileList = null;
        return;
      }

      if (files.length < 2) {
        fileList?.remove();
        fileList = null;
        return;
      }

      if (!fileList) {
        fileList = document.createElement('div');
        fileList.className = 'file-drop-list';
        fileList.setAttribute('aria-label', 'Daftar file terpilih');
        dropZone.appendChild(fileList);
      }

      fileList.innerHTML = '';
      files.forEach(function (file) {
        const item = document.createElement('span');
        item.className = 'file-drop-list-item';
        item.textContent = file.name;
        fileList.appendChild(item);
      });
    });
  });
}

function initPriceFormatInputs() {
  const inputs = document.querySelectorAll('input[name="price_display"], [data-price-format]');

  inputs.forEach(function (input) {
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'off');

    function formatValue() {
      const digits = input.value.replace(/\D/g, '');
      input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    input.addEventListener('input', formatValue);
    input.addEventListener('paste', function () {
      window.setTimeout(formatValue, 0);
    });

    formatValue();
  });
}

function getFavorites() {
  try {
    return JSON.parse(localStorage.getItem('pusakarasa_favorites') || '[]');
  } catch (error) {
    return [];
  }
}
