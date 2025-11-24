// Hamburger Menu Toggle
const hamburger = document.getElementById('hamburgerBtn');
const navMenu = document.getElementById('navMenu');

if (hamburger && navMenu) {
  // Toggle menu saat klik hamburger
  hamburger.addEventListener('click', function(e) {
    e.stopPropagation();
    navMenu.classList.toggle('show');
  });

  // Close menu saat klik di luar
  document.addEventListener('click', function(e) {
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
      navMenu.classList.remove('show');
    }
  });

  // Close menu saat klik link
  const navLinks = navMenu.querySelectorAll('a');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navMenu.classList.remove('show');
    });
  });
}

// Filter buttons functionality (optional enhancement)
const filterButtons = document.querySelectorAll('.btn-tertiary');
if (filterButtons.length > 0) {
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Remove active state from all buttons
      filterButtons.forEach(btn => btn.classList.remove('active'));
      // Add active state to clicked button
      this.classList.add('active');
      
      // You can add filtering logic here
      const filterType = this.textContent.toLowerCase();
      console.log('Filter:', filterType);
    });
  });
}