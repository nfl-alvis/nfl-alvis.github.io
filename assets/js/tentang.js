/* ========================================
   PUSAKARASA - TENTANG PAGE SCRIPTS
   Hamburger Menu dengan Animasi
   ======================================== */

console.log("🚀 Script loaded!");

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // HAMBURGER MENU INITIALIZATION START
  // ========================================
  const btn = document.getElementById("hamburgerBtn");
  const menu = document.getElementById("navMenu");

  // Guard clause - keluar jika elemen tidak ditemukan
  if (!btn || !menu) return;

  // Function: Menutup menu
  function closeMenu() {
    menu.classList.remove("show");
    btn.classList.remove("active");
    btn.setAttribute("aria-expanded", "false");
  }

  // Function: Membuka menu
  function openMenu() {
    menu.classList.add("show");
    btn.classList.add("active");
    btn.setAttribute("aria-expanded", "true");
  }

  // Event: Toggle menu saat hamburger diklik
  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    menu.classList.contains("show") ? closeMenu() : openMenu();
  });

  // Event: Tutup menu saat klik di luar menu
  document.addEventListener("click", function (e) {
    if (!menu.contains(e.target) && !btn.contains(e.target)) {
      closeMenu();
    }
  });

  // Event: Tutup menu saat tekan tombol ESC
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeMenu();
  });

  // Event: Tutup menu saat resize ke desktop view
  window.addEventListener("resize", function () {
    if (window.innerWidth > 600) closeMenu();
  });
  // ========================================
  // HAMBURGER MENU INITIALIZATION END
  // ========================================
});