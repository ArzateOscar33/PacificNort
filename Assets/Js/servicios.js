 
  // Inicializar AOS (Animate On Scroll)
  document.addEventListener('DOMContentLoaded', function() {
    // Inicializar AOS
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    }); 
 
    // Activar enlaces de navegación según la sección en vista
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-link');
    window.addEventListener('scroll', function() {
      let current = '';
      sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (pageYOffset >= (sectionTop - 200)) {
          current = section.getAttribute('id');
        }
      });
      navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').substring(1) === current) {
          link.classList.add('active');
        }
      });
    });});
