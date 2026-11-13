// Site public : amélioration progressive. Sans ce script, le menu reste déplié et la barre « Sur cette page »
// reste une simple liste de liens.
document.documentElement.classList.add('js');

function initSite() {
  initNav();
  document.querySelectorAll('[data-subnav]').forEach(initSubnav);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSite);
else initSite();

// Menu repliable sur petit écran ; Échap le referme et rend le focus au bouton.
function initNav() {
  const nav = document.querySelector('[data-nav]');
  const toggle = nav && nav.querySelector('.site-nav__toggle');
  if (!nav || !toggle || nav.dataset.ready) return;
  nav.dataset.ready = '1';
  toggle.hidden = false;
  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    nav.toggleAttribute('data-open', open);
  };
  toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
}

// « Sur cette page » : le lien de la section lue est marqué (aria-current) et ramené dans la partie visible de la barre.
function initSubnav(nav) {
  if (nav.dataset.ready || !('IntersectionObserver' in window)) return;
  nav.dataset.ready = '1';
  const links = [...nav.querySelectorAll('a[href^="#"]')];
  const list = nav.querySelector('ul');
  const sections = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1)))).filter(Boolean);
  const visible = new Set();
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const mark = () => {
    const current = sections.find((section) => visible.has(section));
    links.forEach((link) => {
      const on = current !== undefined && link.hash === `#${current.id}`;
      if (on) {
        link.setAttribute('aria-current', 'true');
        list.scrollTo({ left: link.offsetLeft - list.clientWidth / 2 + link.offsetWidth / 2, behavior: calm ? 'auto' : 'smooth' });
      } else {
        link.removeAttribute('aria-current');
      }
    });
  };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
    mark();
  }, { rootMargin: '-30% 0px -60% 0px' });
  sections.forEach((section) => observer.observe(section));
}
