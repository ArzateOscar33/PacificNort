// Inicializar AOS (Animate On Scroll)
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar AOS
  AOS.init({
    duration: 800,
    easing: "ease-in-out",
    once: true,
  });

  // Activar enlaces de navegación según la sección en vista
  const sections = document.querySelectorAll("section");
  const navLinks = document.querySelectorAll(".nav-link");
  window.addEventListener("scroll", function () {
    let current = "";
    sections.forEach((section) => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.clientHeight;
      if (pageYOffset >= sectionTop - 200) {
        current = section.getAttribute("id");
      }
    });
    navLinks.forEach((link) => {
      link.classList.remove("active");
      if (link.getAttribute("href").substring(1) === current) {
        link.classList.add("active");
      }
    });
  });
  // Botón para volver al inicio
  const backToTopBtn = document.querySelector(".back-to-top");

  if (backToTopBtn) {
    window.addEventListener("scroll", function () {
      if (window.scrollY > 300) {
        backToTopBtn.classList.add("show");
      } else {
        backToTopBtn.classList.remove("show");
      }
    });

    backToTopBtn.addEventListener("click", function (e) {
      e.preventDefault();

      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }

  // Carrusel pausado al pasar el mouse
  const carousel = document.getElementById("galleryCarousel");
  if (carousel) {
    const carouselInstance = new bootstrap.Carousel(carousel, {
      interval: 5000,
      wrap: true,
    });
    carousel.addEventListener("mouseenter", function () {
      carouselInstance.pause();
    });
    carousel.addEventListener("mouseleave", function () {
      carouselInstance.cycle();
    });
  }
});
