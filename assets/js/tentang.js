// Hamburger Menu Toggle dengan Animasi
console.log('🚀 Script loaded!');

const hamburger = document.getElementById('hamburgerBtn');
const navMenu = document.getElementById('navMenu');

console.log('Hamburger:', hamburger);
console.log('NavMenu:', navMenu);

if (hamburger && navMenu) {
  console.log('✅ Elements found!');
  
  // Toggle menu saat klik hamburger
  hamburger.addEventListener('click', function(e) {
    console.log('🍔 Hamburger clicked!');
    e.preventDefault();
    e.stopPropagation();
    
    // Toggle class 'show' pada menu
    navMenu.classList.toggle('show');
    
    // Toggle class 'active' pada hamburger untuk animasi
    hamburger.classList.toggle('active');
    
    // Debug info
    const isShown = navMenu.classList.contains('show');
    console.log('Menu status:', isShown ? 'SHOWN ✓' : 'HIDDEN');
    console.log('Menu classes:', navMenu.className);
  });

  // Close menu saat klik di luar
  document.addEventListener('click', function(e) {
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      if (navMenu.classList.contains('show')) {
        navMenu.classList.remove('show');
        hamburger.classList.remove('active');
      }
    }
  });

  // Close menu saat klik link
  const navLinks = navMenu.querySelectorAll('a');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navMenu.classList.remove('show');
      hamburger.classList.remove('active');
    });
  });

  console.log('✅ All event listeners attached!');
} else {
  console.error('❌ Elements not found!');
  console.log('hamburger:', hamburger);
  console.log('navMenu:', navMenu);
}