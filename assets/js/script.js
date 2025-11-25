// ========================================
// PUSAKARASA - UNIVERSAL JAVASCRIPT
// File ini berfungsi untuk semua halaman
// ========================================

// Inisialisasi saat halaman selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
  initHamburgerMenu(); // Aktifkan menu hamburger (semua halaman)
  initFilterButtons(); // Aktifkan tombol filter (index.html, katalog.html)
  initSearchBar(); // Aktifkan pencarian (index.html, katalog.html)
  initFavoriteButtons(); // Aktifkan tombol favorit (index.html, katalog.html)
  initDetailButtons(); // Aktifkan tombol detail (index.html, katalog.html)
  initSmoothScroll(); // Aktifkan smooth scroll (semua halaman)
  initCardAnimation(); // Aktifkan animasi card (index.html, katalog.html)
  initNavIndicator(); // Aktifkan indikator navigasi aktif (semua halaman)
});

// ========================================
// HAMBURGER MENU (Universal - Semua Halaman)
// ========================================
function initHamburgerMenu() {
  const hamburger = document.getElementById('hamburgerBtn'); // Ambil tombol hamburger
  const navMenu = document.getElementById('navMenu'); // Ambil menu navigasi
  
  if (!hamburger || !navMenu) return; // Keluar jika elemen tidak ada
  
  // Toggle menu saat hamburger diklik
  hamburger.addEventListener('click', function(e) {
    e.stopPropagation(); // Cegah event bubbling
    const isOpen = navMenu.classList.toggle('show'); // Toggle class 'show'
    hamburger.classList.toggle('active'); // Toggle animasi hamburger
    hamburger.setAttribute('aria-expanded', isOpen); // Update atribut aria
    document.body.style.overflow = isOpen ? 'hidden' : ''; // Blokir scroll saat menu terbuka
  });
  
  // Tutup menu saat klik di luar
  document.addEventListener('click', function(e) {
    if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
      navMenu.classList.remove('show'); // Hapus class show
      hamburger.classList.remove('active'); // Reset hamburger
      hamburger.setAttribute('aria-expanded', 'false'); // Update aria
      document.body.style.overflow = ''; // Aktifkan scroll kembali
    }
  });
  
  // Tutup menu saat klik link navigasi
  const navLinks = navMenu.querySelectorAll('a'); // Ambil semua link dalam menu
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navMenu.classList.remove('show'); // Tutup menu
      hamburger.classList.remove('active'); // Reset hamburger
      hamburger.setAttribute('aria-expanded', 'false'); // Update aria
      document.body.style.overflow = ''; // Aktifkan scroll
    });
  });
  
  // Tutup menu saat tombol ESC ditekan
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && navMenu.classList.contains('show')) {
      navMenu.classList.remove('show'); // Tutup menu
      hamburger.classList.remove('active'); // Reset hamburger
      hamburger.setAttribute('aria-expanded', 'false'); // Update aria
      document.body.style.overflow = ''; // Aktifkan scroll
    }
  });
  
  // Tutup menu saat resize ke desktop
  window.addEventListener('resize', function() {
    if (window.innerWidth > 600) { // Jika layar lebih besar dari 600px (mobile breakpoint)
      navMenu.classList.remove('show'); // Tutup menu
      hamburger.classList.remove('active'); // Reset hamburger
      hamburger.setAttribute('aria-expanded', 'false'); // Update aria
      document.body.style.overflow = ''; // Aktifkan scroll
    }
  });
}

// ========================================
// NAVIGATION INDICATOR (Universal)
// ========================================
function initNavIndicator() {
  const navLinks = document.querySelectorAll('nav ul li a'); // Ambil semua link navigasi
  const indicator = document.querySelector('.nav-indicator'); // Ambil indikator
  
  if (!indicator || !navLinks.length) return; // Keluar jika tidak ada elemen
  
  // Set posisi indicator berdasarkan halaman aktif
  const currentPage = window.location.pathname.split('/').pop() || 'index.html'; // Ambil nama file halaman saat ini
  
  navLinks.forEach(link => {
    const linkPage = link.getAttribute('href'); // Ambil href link
    
    if (linkPage === currentPage || (currentPage === '' && linkPage === 'index.html')) {
      // Jika link sesuai dengan halaman saat ini
      updateIndicator(link, indicator); // Update posisi indicator
      link.style.color = '#e06b4c'; // Warna link aktif
    }
    
    // Update indicator saat hover
    link.addEventListener('mouseenter', function() {
      updateIndicator(this, indicator); // Geser indicator ke link
    });
  });
  
  // Reset indicator saat mouse keluar dari nav
  const nav = document.querySelector('nav'); // Ambil elemen nav
  if (nav) {
    nav.addEventListener('mouseleave', function() {
      const activeLink = document.querySelector('nav ul li a[style*="color"]'); // Cari link aktif
      if (activeLink) {
        updateIndicator(activeLink, indicator); // Kembalikan ke posisi link aktif
      } else {
        indicator.style.width = '0'; // Sembunyikan jika tidak ada link aktif
      }
    });
  }
}

// Fungsi helper untuk update posisi indicator
function updateIndicator(link, indicator) {
  const linkRect = link.getBoundingClientRect(); // Ambil posisi link
  const navRect = link.closest('nav').getBoundingClientRect(); // Ambil posisi nav
  
  indicator.style.left = (linkRect.left - navRect.left) + 'px'; // Set posisi kiri
  indicator.style.width = linkRect.width + 'px'; // Set lebar sesuai link
}

// ========================================
// FILTER BUTTONS (Index & Katalog)
// ========================================
function initFilterButtons() {
  const filterButtons = document.querySelectorAll('.btn-tertiary'); // Ambil semua tombol filter
  const foodCards = document.querySelectorAll('.food-card'); // Ambil semua card makanan
  
  if (!filterButtons.length || !foodCards.length) return; // Keluar jika tidak ada elemen
  
  filterButtons.forEach((button, index) => {
    // Set data-filter berdasarkan urutan tombol
    if (index === 0) button.setAttribute('data-filter', 'all'); // Tombol pertama = semua
    if (index === 1) button.setAttribute('data-filter', 'makanan'); // Tombol kedua = makanan
    if (index === 2) button.setAttribute('data-filter', 'minuman'); // Tombol ketiga = minuman
    
    button.addEventListener('click', function() {
      const filter = this.getAttribute('data-filter'); // Ambil nilai filter
      
      // Update tombol aktif
      filterButtons.forEach(btn => btn.classList.remove('active')); // Hapus semua active
      this.classList.add('active'); // Tambah active ke tombol yang diklik
      
      // Filter card dengan animasi
      let visibleCount = 0; // Counter card yang terlihat
      
      foodCards.forEach(card => {
        // Ambil kategori dari tag dalam card
        const tags = card.querySelectorAll('.image-tags span'); // Ambil semua tag
        let category = 'makanan'; // Default kategori
        
        tags.forEach(tag => {
          const text = tag.textContent.toLowerCase(); // Ubah ke lowercase
          if (text === 'minuman') category = 'minuman'; // Jika ada tag minuman
        });
        
        if (filter === 'all' || category === filter) { // Jika cocok dengan filter
          card.style.display = 'flex'; // Tampilkan card
          setTimeout(() => {
            card.style.opacity = '1'; // Fade in
            card.style.transform = 'scale(1)'; // Scale normal
          }, 10);
          visibleCount++; // Tambah counter
        } else {
          card.style.opacity = '0'; // Fade out
          card.style.transform = 'scale(0.9)'; // Scale kecil
          setTimeout(() => card.style.display = 'none', 300); // Sembunyikan setelah animasi
        }
      });
      
      console.log(`Filter: ${filter} | Ditampilkan: ${visibleCount} item`); // Log untuk debugging
    });
  });
  
  // Set tombol pertama sebagai aktif saat load
  if (filterButtons[0]) filterButtons[0].classList.add('active');
}

// ========================================
// SEARCH BAR (Index & Katalog)
// ========================================
function initSearchBar() {
  const searchInput = document.querySelector('.search-bar'); // Ambil input pencarian
  const searchButton = document.querySelector('.search-button'); // Ambil tombol cari
  const foodCards = document.querySelectorAll('.food-card'); // Ambil semua card makanan
  
  if (!searchInput) return; // Keluar jika input tidak ada
  
  // Pencarian real-time (dengan delay 300ms)
  searchInput.addEventListener('input', debounce(function() {
    performSearch(this.value, foodCards); // Jalankan fungsi pencarian
  }, 300));
  
  // Pencarian saat tombol diklik
  if (searchButton) {
    searchButton.addEventListener('click', () => performSearch(searchInput.value, foodCards));
  }
  
  // Pencarian saat Enter ditekan
  searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { // Jika tombol Enter ditekan
      e.preventDefault(); // Cegah submit form
      performSearch(this.value, foodCards); // Jalankan pencarian
    }
  });
}

// Fungsi untuk melakukan pencarian
function performSearch(query, cards) {
  const searchTerm = query.toLowerCase().trim(); // Ubah ke lowercase dan hapus spasi
  let visibleCount = 0; // Hitung card yang terlihat
  
  cards.forEach(card => {
    // Ambil teks dari berbagai elemen card
    const title = card.querySelector('.food-title')?.textContent.toLowerCase() || '';
    const desc = card.querySelector('.food-desc')?.textContent.toLowerCase() || '';
    const tag = card.querySelector('.food-tag')?.textContent.toLowerCase() || '';
    const region = card.querySelector('.image-tags span:first-child')?.textContent.toLowerCase() || '';
    
    // Cek apakah query cocok dengan salah satu teks
    const isMatch = !searchTerm || title.includes(searchTerm) || desc.includes(searchTerm) || 
                    tag.includes(searchTerm) || region.includes(searchTerm);
    
    if (isMatch) { // Jika cocok
      card.style.display = 'flex'; // Tampilkan
      setTimeout(() => {
        card.style.opacity = '1'; // Fade in
        card.style.transform = 'scale(1)'; // Scale normal
      }, 10);
      visibleCount++; // Tambah counter
    } else { // Jika tidak cocok
      card.style.opacity = '0'; // Fade out
      card.style.transform = 'scale(0.9)'; // Scale kecil
      setTimeout(() => card.style.display = 'none', 300); // Sembunyikan
    }
  });
  
  // Tampilkan pesan jika tidak ada hasil
  if (visibleCount === 0 && searchTerm) {
    showNoResultsMessage(searchTerm); // Tampilkan pesan
  } else {
    removeNoResultsMessage(); // Hapus pesan jika ada hasil
  }
  
  console.log(`Pencarian: "${searchTerm}" | Hasil: ${visibleCount} item`); // Log untuk debugging
}

// Tampilkan pesan "tidak ada hasil"
function showNoResultsMessage(query) {
  removeNoResultsMessage(); // Hapus pesan lama jika ada
  
  const container = document.querySelector('.cards-container'); // Ambil container card
  if (!container) return; // Keluar jika tidak ada
  
  const message = document.createElement('div'); // Buat elemen div baru
  message.className = 'no-results-message'; // Tambah class
  message.innerHTML = `
    <p style="font-size: 18px; margin-bottom: 8px;">Tidak ada hasil untuk "<strong>${query}</strong>"</p>
    <p style="font-size: 14px;">Coba kata kunci lain atau jelajahi katalog lengkap kami</p>
  `;
  message.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #6b7280;'; // Styling inline
  
  container.appendChild(message); // Tambahkan ke container
}

// Hapus pesan "tidak ada hasil"
function removeNoResultsMessage() {
  const message = document.querySelector('.no-results-message'); // Cari pesan
  if (message) message.remove(); // Hapus jika ada
}

// ========================================
// FAVORITE BUTTONS (Index & Katalog)
// ========================================
function initFavoriteButtons() {
  const favButtons = document.querySelectorAll('.fav-btn'); // Ambil semua tombol favorit
  const favorites = JSON.parse(localStorage.getItem('favorites') || '[]'); // Ambil data favorit dari localStorage
  
  favButtons.forEach((button, index) => {
    // Generate ID jika belum ada
    let id = button.getAttribute('data-id');
    if (!id) {
      id = `food-${index}`; // ID otomatis berdasarkan index
      button.setAttribute('data-id', id); // Set ID ke tombol
    }
    
    // Set status awal dari localStorage
    if (favorites.includes(id)) {
      button.classList.add('active'); // Tambah class active jika sudah difavoritkan
    }
    
    // Toggle favorit saat diklik
    button.addEventListener('click', function(e) {
      e.stopPropagation(); // Cegah event bubbling
      toggleFavorite(this, id); // Jalankan fungsi toggle
    });
  });
}

// Toggle status favorit
function toggleFavorite(button, id) {
  let favorites = JSON.parse(localStorage.getItem('favorites') || '[]'); // Ambil data favorit
  
  if (button.classList.contains('active')) { // Jika sudah favorit
    button.classList.remove('active'); // Hapus status favorit
    favorites = favorites.filter(fav => fav !== id); // Hapus dari array
    showToast('Dihapus dari favorit'); // Tampilkan notifikasi
  } else { // Jika belum favorit
    button.classList.add('active'); // Tambah status favorit
    if (!favorites.includes(id)) favorites.push(id); // Tambah ke array jika belum ada
    showToast('Ditambahkan ke favorit ❤️'); // Tampilkan notifikasi
  }
  
  localStorage.setItem('favorites', JSON.stringify(favorites)); // Simpan ke localStorage
  console.log('Favorites:', favorites); // Log untuk debugging
}

// ========================================
// DETAIL BUTTONS (Index & Katalog)
// ========================================
function initDetailButtons() {
  const detailButtons = document.querySelectorAll('.detail-btn'); // Ambil semua tombol detail
  
  detailButtons.forEach(button => {
    button.addEventListener('click', function() {
      const card = this.closest('.food-card'); // Ambil card parent
      const title = card.querySelector('.food-title')?.textContent; // Ambil judul makanan
      const id = card.querySelector('.fav-btn')?.getAttribute('data-id'); // Ambil ID dari tombol favorit
      
      showToast(`Membuka detail ${title || 'kuliner'}...`); // Tampilkan notifikasi
      console.log('Detail clicked:', { id, title }); // Log untuk debugging
      
      // Simulasi navigasi (uncomment untuk navigasi real)
      // setTimeout(() => window.location.href = `detail.html?id=${id}`, 500);
    });
  });
}

// ========================================
// SMOOTH SCROLL (Universal)
// ========================================
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => { // Ambil semua link dengan # di awal
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href'); // Ambil href
      if (href === '#' || href === '#favorit') return; // Skip jika hanya # atau #favorit (belum ada)
      
      e.preventDefault(); // Cegah default behavior
      const target = document.querySelector(href); // Ambil elemen target
      
      if (target) { // Jika target ada
        const offset = 80; // Offset dari atas (untuk header sticky)
        const position = target.getBoundingClientRect().top + window.pageYOffset - offset; // Hitung posisi
        
        window.scrollTo({ top: position, behavior: 'smooth' }); // Scroll smooth
      }
    });
  });
}

// ========================================
// CARD ANIMATION (Index & Katalog)
// ========================================
function initCardAnimation() {
  const cards = document.querySelectorAll('.food-card'); // Ambil semua card
  
  if (!cards.length) return; // Keluar jika tidak ada card
  
  // Animasi fade in saat load
  cards.forEach((card, index) => {
    card.style.opacity = '0'; // Set opacity 0
    card.style.transform = 'translateY(20px)'; // Geser ke bawah
    
    setTimeout(() => { // Delay berdasarkan index
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease'; // Tambah transition
      card.style.opacity = '1'; // Fade in
      card.style.transform = 'translateY(0)'; // Geser ke posisi normal
    }, index * 50); // Delay bertahap 50ms per card
  });
}

// ========================================
//           UTILITY FUNCTIONS
// ========================================

// Debounce: Delay eksekusi fungsi sampai user berhenti mengetik
function debounce(func, wait) {
  let timeout; // Variable untuk menyimpan timeout
  return function(...args) { // Return fungsi baru
    clearTimeout(timeout); // Hapus timeout sebelumnya
    timeout = setTimeout(() => func.apply(this, args), wait); // Set timeout baru
  };
}
1
// Toast notification - Notifikasi popup
function showToast(message, duration = 2000) {
  const existingToast = document.querySelector('.toast'); // Cari toast yang ada
  if (existingToast) existingToast.remove(); // Hapus jika ada
  
  const toast = document.createElement('div'); // Buat elemen toast
  toast.className = 'toast'; // Tambah class
  toast.textContent = message; // Set teks
  toast.style.cssText = `
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.85); color: white; padding: 12px 24px;
    border-radius: 8px; font-size: 14px; z-index: 10000;
    animation: slideUp 0.3s ease; backdrop-filter: blur(10px);
  `; // Styling inline
  
  // Tambah animasi CSS
  if (!document.querySelector('#toast-animation')) { // Jika belum ada style
    const style = document.createElement('style'); // Buat elemen style
    style.id = 'toast-animation'; // Tambah ID
    style.textContent = `
      @keyframes slideUp {
        from { opacity: 0; transform: translate(-50%, 20px); }
        to { opacity: 1; transform: translate(-50%, 0); }
      }
    `; // CSS animasi
    document.head.appendChild(style); // Tambah ke head
  }
  
  document.body.appendChild(toast); // Tambah toast ke body
  
  // Hapus toast setelah duration
  setTimeout(() => {
    toast.style.opacity = '0'; // Fade out
    setTimeout(() => toast.remove(), 300); // Hapus dari DOM
  }, duration);
}

// Export fungsi untuk digunakan di script lain (jika diperlukan)
window.PusakaRasa = {
  showToast, // Fungsi toast
  toggleFavorite // Fungsi toggle favorit
};
