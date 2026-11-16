// Site public : amélioration progressive. Sans ce script, le menu reste déplié et la barre « Sur cette page »
// reste une simple liste de liens.
document.documentElement.classList.add('js');

function initSite() {
  initNav();
  initApparition();
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

// Apparition au défilement (rule web-design, « Mouvement ») : opacité et décalage, une seule fois, ~300 ms.
// Sans ce script ou en mouvement réduit, tout est visible tout de suite ; ce qui est déjà à l'écran au chargement
// n'est jamais masqué (aucun retard de lecture ni de LCP).
function initApparition() {
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const cibles = document.querySelectorAll('main > :not(.ouverture):not(.page-head) :is(h2, .lien-fleche, .rendez-vous__item, .etapes-chiffrees__liste > li, .actu, .structure, .qui__texte, .qui__objectif, .qui__financeurs li, .appel__illustration, .appel__actions, figure, .engage__photo, .prose > p, .prose > ul, .portail, .carte-article, .evenement, .lieu, .galerie__vignette)');
  const observateur = new IntersectionObserver((entrees) => {
    for (const entree of entrees) {
      if (!entree.isIntersecting) continue;
      entree.target.classList.add('apparu');
      observateur.unobserve(entree.target);
    }
  }, { rootMargin: '0px 0px -8% 0px' });
  cibles.forEach((el) => {
    // Contenu d'un volet fermé (details) : jamais vu par l'observateur, il resterait masqué à l'ouverture.
    if (el.closest('details:not([open])') || el.getBoundingClientRect().top < window.innerHeight) return;
    // Décalage en cascade entre voisins (cartes d'une grille), plafonné : la dernière carte n'attend jamais.
    el.style.setProperty('--rang', String(Math.min([...el.parentElement.children].indexOf(el), 4)));
    el.classList.add('apparition');
    observateur.observe(el);
  });
}
