// Hamburger Menu Toggle dengan Animasi
console.log("🚀 Script loaded!");

document.addEventListener("DOMContentLoaded", function () {
  const btn = document.getElementById("hamburgerBtn");
  const menu = document.getElementById("navMenu");

  if (!btn || !menu) return;

  function closeMenu() {
    menu.classList.remove("show");
    btn.classList.remove("active");
    btn.setAttribute("aria-expanded", "false");
  }

  function openMenu() {
    menu.classList.add("show");
    btn.classList.add("active");
    btn.setAttribute("aria-expanded", "true");
  }

  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    if (menu.classList.contains("show")) closeMenu();
    else openMenu();
  });

  // click outside to close
  document.addEventListener("click", function (e) {
    if (!menu.contains(e.target) && !btn.contains(e.target)) closeMenu();
  });

  // esc key to close
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeMenu();
  });

  // on resize ensure mobile menu closed when switching to desktop
  window.addEventListener("resize", function () {
    if (window.innerWidth > 600) closeMenu();
  });
});
