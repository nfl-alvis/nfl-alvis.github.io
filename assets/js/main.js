/* ========================================
   PUSAKARASA - MAIN JAVASCRIPT
   Simplified & Fully Commented
   ======================================== */

// ========== INITIALIZATION START ==========
// Event listener yang dijalankan saat DOM sudah siap
document.addEventListener('DOMContentLoaded', function() {
  // Inisialisasi hamburger menu untuk mobile
  initHamburgerMenu();
  
  // Inisialisasi indikator navigasi (garis bawah menu aktif)
  initNavIndicator();
  
  // Inisialisasi tombol favorite untuk menyimpan makanan favorit
  initFavoriteButtons();
});
// ========== INITIALIZATION END ==========

// ========== HAMBURGER MENU START ==========
// Fungsi untuk mengatur menu hamburger (mobile)
function initHamburgerMenu() {
  // Ambil elemen tombol hamburger dari DOM
  const hamburger = document.getElementById('hamburgerBtn');
  
  // Ambil elemen menu navigasi dari DOM
  const navMenu = document.getElementById('navMenu');
  
  // Jika salah satu elemen tidak ada, hentikan fungsi
  if (!hamburger || !navMenu) return;
  
  // Event listener untuk toggle menu saat hamburger diklik
  hamburger.addEventListener('click', function(e) {
    // Cegah event bubbling ke parent
    e.stopPropagation();
    
    // Toggle class 'show' pada menu dan simpan statusnya
    const isOpen = navMenu.classList.toggle('show');
    
    // Toggle class 'active' pada hamburger untuk animasi
    hamburger.classList.toggle('active', isOpen);
    
    // Jika menu terbuka, disable scroll body; jika tertutup, enable scroll
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });
  
  // Event listener untuk menutup menu saat klik di luar menu
  document.addEventListener('click', function(e) {
    // Cek apakah klik terjadi di luar menu dan hamburger
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      // Hapus class 'show' untuk menutup menu
      navMenu.classList.remove('show');
      
      // Hapus class 'active' dari hamburger
      hamburger.classList.remove('active');
      
      // Enable scroll kembali
      document.body.style.overflow = '';
    }
  });
  
  // Event listener untuk menutup menu dengan tombol ESC
  document.addEventListener('keydown', function(e) {
    // Cek apakah tombol ESC ditekan dan menu sedang terbuka
    if (e.key === 'Escape' && navMenu.classList.contains('show')) {
      // Tutup menu
      navMenu.classList.remove('show');
      
      // Hapus class active dari hamburger
      hamburger.classList.remove('active');
      
      // Enable scroll
      document.body.style.overflow = '';
    }
  });
  
  // Event listener untuk menutup menu saat window di-resize ke desktop
  window.addEventListener('resize', function() {
    // Cek apakah lebar window lebih dari 600px (mode desktop)
    if (window.innerWidth > 600) {
      // Tutup menu otomatis
      navMenu.classList.remove('show');
      
      // Hapus class active
      hamburger.classList.remove('active');
      
      // Enable scroll
      document.body.style.overflow = '';
    }
  });
  
  // Event listener untuk menutup menu saat link navigasi diklik
  navMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', function() {
      // Tutup menu
      navMenu.classList.remove('show');
      
      // Hapus class active
      hamburger.classList.remove('active');
      
      // Enable scroll
      document.body.style.overflow = '';
    });
  });
}
// ========== HAMBURGER MENU END ==========

// ========== NAV INDICATOR START ==========
// Fungsi untuk mengatur indikator navigasi (garis bawah menu aktif)
function initNavIndicator() {
  // Ambil semua link navigasi
  const navLinks = document.querySelectorAll('nav ul li a');
  
  // Ambil elemen indikator (garis bawah)
  const indicator = document.querySelector('.nav-indicator');
  
  // Jika indicator atau navLinks tidak ada, hentikan fungsi
  if (!indicator || !navLinks.length) return;
  
  // Ambil nama halaman saat ini dari URL
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  
  // Loop semua link navigasi
  navLinks.forEach(link => {
    // Ambil nama halaman dari href link
    const linkPage = link.getAttribute('href').split('/').pop();
    
    // Jika link sesuai dengan halaman saat ini, set indikator
    if (linkPage === currentPage) {
      setIndicator(link);
    }
    
    // Event listener untuk hover effect
    link.addEventListener('mouseenter', function() {
      // Set indikator ke link yang di-hover
      setIndicator(this);
    });
  });
  
  // Event listener untuk reset indikator saat mouse leave dari nav
  document.querySelector('nav ul').addEventListener('mouseleave', function() {
    // Cari link yang sesuai dengan halaman aktif
    const activeLink = document.querySelector('nav ul li a[href$="' + currentPage + '"]');
    
    // Jika ada, set indikator ke link aktif
    if (activeLink) {
      setIndicator(activeLink);
    } else {
      // Jika tidak ada, sembunyikan indikator
      indicator.style.width = '0';
    }
  });
  
  // Fungsi helper untuk set posisi dan ukuran indikator
  function setIndicator(element) {
    // Ambil posisi dan ukuran link
    const linkRect = element.getBoundingClientRect();
    
    // Ambil posisi nav container
    const navRect = element.closest('nav ul').getBoundingClientRect();
    
    // Set posisi left indikator relatif terhadap nav
    indicator.style.left = (linkRect.left - navRect.left) + 'px';
    
    // Set width indikator sesuai dengan width link
    indicator.style.width = linkRect.width + 'px';
  }
}
// ========== NAV INDICATOR END ==========

// ========== FAVORITE BUTTONS START ==========
// Fungsi untuk mengatur tombol favorite pada setiap card
function initFavoriteButtons() {
  // Ambil semua tombol favorite
  const favButtons = document.querySelectorAll('.fav-btn');
  
  // Jika tidak ada tombol favorite, hentikan fungsi
  if (!favButtons.length) return;
  
  // Load data favorites dari localStorage
  let favorites = getFavorites();
  
  // Loop semua tombol favorite
  favButtons.forEach(button => {
    // Ambil card parent dari tombol
    const card = button.closest('.food-card');
    
    // Ambil judul makanan dari card
    const title = card?.querySelector('.food-title')?.textContent || '';
    
    // Generate ID unik dari judul
    const itemId = generateId(title);
    
    // Set data-id attribute pada tombol
    button.setAttribute('data-id', itemId);
    
    // Cek apakah item ini sudah ada di favorites
    if (favorites.includes(itemId)) {
      // Jika ada, tambahkan class 'active' (ikon hati merah)
      button.classList.add('active');
    }
    
    // Event listener saat tombol favorite diklik
    button.addEventListener('click', function(e) {
      // Cegah event bubbling
      e.stopPropagation();
      
      // Cegah default action
      e.preventDefault();
      
      // Toggle class 'active' dan simpan statusnya
      const isActive = this.classList.toggle('active');
      
      // Jika sekarang active (ditambahkan ke favorit)
      if (isActive) {
        addToFavorites(itemId);
      } else {
        // Jika sekarang tidak active (dihapus dari favorit)
        removeFromFavorites(itemId);
      }
    });
  });
  
  // Fungsi helper untuk ambil favorites dari localStorage
  function getFavorites() {
    try {
      // Parse JSON dari localStorage, jika tidak ada return array kosong
      return JSON.parse(localStorage.getItem('pusakarasa_favorites') || '[]');
    } catch (e) {
      // Jika error parsing, return array kosong
      return [];
    }
  }
  
  // Fungsi helper untuk tambah item ke favorites
  function addToFavorites(id) {
    // Ambil favorites saat ini
    let favorites = getFavorites();
    
    // Cek apakah ID belum ada di favorites
    if (!favorites.includes(id)) {
      // Tambahkan ID ke array favorites
      favorites.push(id);
      
      // Simpan kembali ke localStorage
      localStorage.setItem('pusakarasa_favorites', JSON.stringify(favorites));
    }
  }
  
  // Fungsi helper untuk hapus item dari favorites
  function removeFromFavorites(id) {
    // Ambil favorites saat ini
    let favorites = getFavorites();
    
    // Filter array, buang ID yang ingin dihapus
    favorites = favorites.filter(fav => fav !== id);
    
    // Simpan kembali ke localStorage
    localStorage.setItem('pusakarasa_favorites', JSON.stringify(favorites));
  }
  
  // Fungsi helper untuk generate ID dari text
  function generateId(text) {
    // Ubah ke lowercase dan ganti spasi dengan dash
    return text.toLowerCase().replace(/\s+/g, '-');
  }
}
// ========== FAVORITE BUTTONS END ==========

/* ========================================
   AUTH SYSTEM & TOAST NOTIFICATION
   ======================================== */

// ========== TOAST NOTIFICATION START ==========
// Fungsi untuk menampilkan notifikasi toast dari atas tengah
function showToast(message, timeout = 2000) {
  try {
    // Cek apakah toast element sudah ada
    let t = document.getElementById('pusaka-toast');
    
    // Jika belum ada, buat element baru
    if (!t) {
      t = document.createElement('div');
      t.id = 'pusaka-toast';
      
      // Set style inline untuk toast (muncul dari atas tengah)
      Object.assign(t.style, {
        position: 'fixed',           // Fixed positioning
        top: '20px',                 // 20px dari atas
        left: '50%',                 // 50% dari kiri (untuk center)
        transform: 'translateX(-50%) translateY(-100%)', // Center horizontal & sembunyikan di atas
        padding: '14px 24px',        // Padding dalam toast
        background: 'rgba(0, 0, 0, 0.9)', // Background hitam semi-transparan
        color: '#fff',               // Text putih
        borderRadius: '12px',        // Sudut melengkung
        zIndex: '99999',             // Z-index tinggi agar di atas semua element
        fontFamily: 'Poppins, sans-serif', // Font
        fontSize: '15px',            // Ukuran font
        fontWeight: '500',           // Ketebalan font
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)', // Shadow
        opacity: '0',                // Mulai dengan transparent
        transition: 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', // Animasi smooth
        minWidth: '200px',           // Lebar minimum
        maxWidth: '90%',             // Lebar maksimum (responsive)
        textAlign: 'center'          // Text di tengah
      });
      
      // Tambahkan toast ke body
      document.body.appendChild(t);
    }
    
    // Set text content toast
    t.textContent = message;
    
    // Tampilkan toast dengan animasi slide down
    setTimeout(() => {
      t.style.opacity = '1';                              // Fade in
      t.style.transform = 'translateX(-50%) translateY(0)'; // Slide down ke posisi normal
    }, 10);
    
    // Clear timeout sebelumnya jika ada
    clearTimeout(t._timeout);
    
    // Set timeout untuk hide toast
    t._timeout = setTimeout(() => {
      t.style.opacity = '0';                                // Fade out
      t.style.transform = 'translateX(-50%) translateY(-100%)'; // Slide up kembali
    }, timeout);
  } catch (e) {
    // Fallback jika error: tampilkan di console
    // (Tidak ada console.log yang tidak penting)
  }
}
// ========== TOAST NOTIFICATION END ==========

// ========== AUTH SYSTEM START ==========
// Key untuk menyimpan status auth di localStorage
const AUTH_KEY = 'pusakarasa_auth';

// Fungsi untuk cek status login user
function checkAuthStatus() {
  // Ambil status login dari localStorage
  const isLoggedIn = localStorage.getItem(AUTH_KEY) === 'true';
  
  // Update UI berdasarkan status login
  updateAuthUI(isLoggedIn);
  
  // Return status login
  return isLoggedIn;
}

// Fungsi untuk update UI auth (tombol login/profil)
// Ganti fungsi updateAuthUI di main.js dengan ini

function updateAuthUI(isLoggedIn) {
  // Ambil container auth buttons
  const authButtons = document.querySelector('.auth-buttons');
  
  // Ambil semua mobile auth items (di dalam hamburger menu)
  const mobileAuthItems = document.querySelectorAll('.mobile-auth');
  
  // Jika auth buttons tidak ada, hentikan fungsi
  if (!authButtons) return;
  
  // Cek apakah user sudah login
  if (isLoggedIn) {
    // === USER SUDAH LOGIN ===
    
    // Replace auth buttons dengan profile menu (DESKTOP ONLY)
    authButtons.innerHTML = `
      <div class="profile-menu">
        <button class="profile-btn" id="profileBtn" aria-label="Profile Menu">
          <i class="fa-regular fa-user"></i>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <div class="profile-header">
            <i class="fa-solid fa-user-circle"></i>
            <span>Pengguna</span>
          </div>
          <a href="#profil"><i class="fa-solid fa-user"></i> Profil Saya</a>
          <a href="#favorit"><i class="fa-solid fa-heart"></i> Favorit</a>
          <button class="logout-btn" id="logoutBtn">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar
          </button>
        </div>
      </div>
    `;
    
    // Update MOBILE auth items (di dalam hamburger menu SAJA)
    mobileAuthItems.forEach((item, index) => {
      // Item pertama: Profil link (HANYA DI MOBILE MENU)
      if (index === 0) {
        item.innerHTML = '<a href="#profil" class="mobile-profile-link"><i class="fa-solid fa-user-circle"></i> Profil</a>';
      } 
      // Item kedua: Logout link (HANYA DI MOBILE MENU)
      else if (index === 1) {
        item.innerHTML = '<a href="#" class="mobile-logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>';
      }
    });
    
    // Delay sedikit untuk memastikan DOM sudah di-render
    setTimeout(() => {
      // Init profile dropdown functionality
      initProfileDropdown();
      
      // Init logout button functionality
      initLogoutButton();
    }, 100);
    
  } else {
    // === USER BELUM LOGIN ===
    
    // Tampilkan tombol Masuk & Daftar (desktop)
    authButtons.innerHTML = `
      <a href="login.html">
        <button class="btn btn-secondary">Masuk</button>
      </a>
      <a href="register.html">
        <button class="btn btn-primary">Daftar</button>
      </a>
    `;
    
    // Restore default mobile auth buttons (hamburger menu)
    mobileAuthItems.forEach((item, index) => {
      // Item pertama: Tombol Masuk
      if (index === 0) {
        item.innerHTML = '<a href="login.html" class="login">Masuk</a>';
      } 
      // Item kedua: Tombol Daftar
      else if (index === 1) {
        item.innerHTML = '<a href="register.html" class="register">Daftar</a>';
      }
    });
  }
}

// Fungsi untuk init logout button functionality
function initLogoutButton() {
  // Ambil tombol logout (desktop dropdown)
  const logoutBtn = document.getElementById('logoutBtn');
  
  // Ambil link logout (mobile hamburger menu)
  const mobileLogoutLink = document.querySelector('.mobile-logout-link');
  
  // Jika tombol logout ada, attach event listener
  if (logoutBtn) {
    logoutBtn.addEventListener('click', handleLogout);
  }
  
  // Jika mobile logout link ada, attach event listener
  if (mobileLogoutLink) {
    mobileLogoutLink.addEventListener('click', function(e) {
      // Cegah default action (navigasi ke #)
      e.preventDefault();
      
      // Jalankan fungsi logout
      handleLogout();
    });
  }
}

// Fungsi untuk handle logout action
function handleLogout() {
  // Hapus status login dari localStorage
  localStorage.removeItem(AUTH_KEY);
  
  // Tampilkan toast notifikasi logout berhasil
  showToast('✅ Berhasil keluar dari akun');
  
  // Delay sedikit sebelum update UI
  setTimeout(() => {
    // Update UI ke mode logged out
    updateAuthUI(false);
  }, 500);
}

// Fungsi untuk handle login action (dipanggil dari halaman login)
function handleLogin() {
  // Set status login di localStorage
  localStorage.setItem(AUTH_KEY, 'true');
  
  // Tampilkan toast notifikasi login berhasil
  showToast('✅ Login berhasil!');
  
  // Redirect ke halaman index setelah 1 detik
  setTimeout(() => {
    window.location.href = 'index.html';
  }, 1000);
}

// Export functions ke window object agar bisa diakses dari halaman lain
window.PusakaRasa = window.PusakaRasa || {};
window.PusakaRasa.handleLogin = handleLogin;
window.PusakaRasa.handleLogout = handleLogout;
window.PusakaRasa.checkAuthStatus = checkAuthStatus;

// Jalankan checkAuthStatus saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
  checkAuthStatus();
});

// ========== PROFILE DROPDOWN START ==========
// Fungsi untuk mengatur dropdown menu profil
function initProfileDropdown() {
  // Ambil tombol profil
  const profileBtn = document.getElementById('profileBtn');
  
  // Ambil dropdown menu
  const profileDropdown = document.getElementById('profileDropdown');
  
  // Jika salah satu elemen tidak ada, hentikan fungsi
  if (!profileBtn || !profileDropdown) return;
  
  // Event listener untuk toggle dropdown saat tombol diklik
  profileBtn.addEventListener('click', function(e) {
    // Cegah event bubbling
    e.stopPropagation();
    
    // Toggle class 'show' pada dropdown
    profileDropdown.classList.toggle('show');
  });
  
  // Event listener untuk menutup dropdown saat klik di luar
  document.addEventListener('click', function(e) {
    // Cek apakah klik terjadi di luar dropdown
    if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
      // Tutup dropdown
      profileDropdown.classList.remove('show');
    }
  });
  
  // Event listener untuk menutup dropdown dengan tombol ESC
  document.addEventListener('keydown', function(e) {
    // Cek apakah tombol ESC ditekan dan dropdown sedang terbuka
    if (e.key === 'Escape' && profileDropdown.classList.contains('show')) {
      // Tutup dropdown
      profileDropdown.classList.remove('show');
    }
  });
}
// ========== PROFILE DROPDOWN END ==========
// ========== AUTH SYSTEM END ==========