(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const chapters = document.querySelectorAll('.about-chapter');
  if (!chapters.length || !('IntersectionObserver' in window)) return;

  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    }
  }, { threshold: 0, rootMargin: '0px 0px -45px 0px' });

  const syncMotion = () => {
    observer.disconnect();
    for (const chapter of chapters) {
      chapter.classList.remove('about-reveal', 'is-visible');
      if (!reducedMotion.matches) {
        observer.observe(chapter);
        chapter.classList.add('about-reveal');
      }
    }
  };
  syncMotion();
  reducedMotion.addEventListener('change', syncMotion);

  const image = document.querySelector('.about-hero-image img');
  let scheduled = false;
  const updateImage = () => {
    if (image) {
      const offset = reducedMotion.matches ? 0 : Math.min(window.scrollY * 0.12, 65);
      image.style.setProperty('--about-scroll', `${offset}px`);
    }
    scheduled = false;
  };
  window.addEventListener('scroll', () => {
    if (!scheduled) {
      scheduled = true;
      requestAnimationFrame(updateImage);
    }
  }, { passive: true });
  reducedMotion.addEventListener('change', updateImage);
  updateImage();
})();
