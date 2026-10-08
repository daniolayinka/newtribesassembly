import Alpine from 'alpinejs';
import AOS from 'aos';
import 'aos/dist/aos.css';
window.Alpine = Alpine;
Alpine.data('ntaHeroSlider', (count) => ({
  current: 0,
  timer: null,
  paused: false,
  init() { this.resume(); },
  next() { this.current = (this.current + 1) % count; },
  previous() { this.current = (this.current - 1 + count) % count; },
  go(index) { this.current = index; },
  pause() { this.paused = true; this.stop(); },
  resume() {
    this.paused = false;
    if (count > 1 && !this.timer && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      this.timer = window.setInterval(() => this.next(), 7000);
    }
  },
  stop() {
    if (this.timer) window.clearInterval(this.timer);
    this.timer = null;
  },
  destroy() { this.stop(); },
}));
Alpine.start();
const menuToggle = document.querySelector('.menu-toggle');
const primaryNavigation = document.querySelector('#primary-navigation');
if (menuToggle && primaryNavigation) {
  const submenuButtons = [];
  const closeSubmenus = (except = null) => {
    submenuButtons.forEach(({ item, button }) => {
      if (item === except) return;
      item.classList.remove('submenu-open');
      button.setAttribute('aria-expanded', 'false');
    });
  };
  primaryNavigation.querySelectorAll('.menu-item-has-children').forEach((item, index) => {
    const link = item.querySelector(':scope > a');
    const submenu = item.querySelector(':scope > .sub-menu');
    if (!link || !submenu) return;
    const button = document.createElement('button');
    submenu.id = submenu.id || `primary-submenu-${index + 1}`;
    button.type = 'button';
    button.className = 'submenu-toggle';
    button.setAttribute('aria-controls', submenu.id);
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', `Toggle ${link.textContent.trim()} submenu`);
    link.insertAdjacentElement('afterend', button);
    button.addEventListener('click', () => {
      const shouldOpen = !item.classList.contains('submenu-open');
      closeSubmenus(item);
      item.classList.toggle('submenu-open', shouldOpen);
      button.setAttribute('aria-expanded', String(shouldOpen));
    });
    submenuButtons.push({ item, button });
  });
  const setMenuState = (open) => {
    primaryNavigation.classList.toggle('open', open);
    menuToggle.classList.toggle('is-open', open);
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.querySelector('.screen-reader-text').textContent = open ? 'Close menu' : 'Open menu';
    if (!open) closeSubmenus();
  };
  menuToggle.addEventListener('click', () => setMenuState(!primaryNavigation.classList.contains('open')));
  primaryNavigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuState(false)));
  document.addEventListener('click', (event) => {
    if (!primaryNavigation.contains(event.target) && !menuToggle.contains(event.target)) {
      closeSubmenus();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      const wasOpen = primaryNavigation.classList.contains('open');
      setMenuState(false);
      if (wasOpen) menuToggle.focus();
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 980) setMenuState(false);
  });
}
document.querySelectorAll('main > section, main > article').forEach((section, index) => {
  if (!section.hasAttribute('data-aos')) {
    section.setAttribute('data-aos', index % 2 ? 'fade-up' : 'fade');
  }
});
AOS.init({ once: true, duration: 650, offset: 36, disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches });
