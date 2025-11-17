const links = document.querySelectorAll("nav ul li a");
const indicator = document.querySelector(".nav-indicator");

function moveIndicator(element) {
  const rect = element.getBoundingClientRect();
  const navRect = element.closest("nav").getBoundingClientRect();
  indicator.style.left = rect.left - navRect.left + "px";
  indicator.style.width = rect.width + "px";
}

// Posisi awal pada link aktif
const activeLink = document.querySelector("nav ul li a.active");
if (activeLink) moveIndicator(activeLink);

// Saat link diklik → pindah aktif + geser garis
links.forEach((link) => {
  link.addEventListener("click", function () {
    links.forEach((l) => l.classList.remove("active"));
    this.classList.add("active");
    moveIndicator(this);
  });
});



const burger = document.getElementById("hamburgerBtn");
const menu = document.getElementById("navMenu");

burger.addEventListener("click", () => {
  menu.classList.toggle("show");
});
