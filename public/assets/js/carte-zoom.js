// Petite carte zoomable (lieu d'un événement, contact ; décision du 2026-11-17) : boutons + et −, retour à la vue de
// départ, molette quand la souris est sur la carte (sans faire défiler la page), glisser à la souris ou au doigt, et au
// clavier (flèches, + et −, 0) quand la carte a le focus. --z (pixels d'écran par unité) garde la taille du repère et
// des noms à l'écran. Sans ce script, la carte reste affichée dans sa vue de départ.
function initCartesZoom() {
  document.querySelectorAll('[data-carte-zoom]').forEach((zone) => {
    if (zone.dataset.pret) return;
    zone.dataset.pret = '1';
    const svg = zone.querySelector('svg');
    if (!svg) return;
    const nombres = (a) => (a || '').split(/\s+/).map(Number);
    const depart = nombres(svg.getAttribute('viewBox'));
    const limites = nombres(svg.dataset.limites || svg.getAttribute('viewBox'));
    const ZOOM_MAX = Number(svg.dataset.zoomMax || 8);
    let vue = depart.slice();
    const unite = () => vue[2] / (svg.getBoundingClientRect().width || 1);
    function borner() {
      const [lx, ly, lw, lh] = limites;
      vue[2] = Math.min(Math.max(vue[2], depart[2] / ZOOM_MAX), Math.max(lw, depart[2]));
      vue[3] = vue[2] * (depart[3] / depart[2]);
      vue[0] = Math.min(Math.max(vue[0], lx - vue[2] * 0.5), lx + lw - vue[2] * 0.5);
      vue[1] = Math.min(Math.max(vue[1], ly - vue[3] * 0.5), ly + lh - vue[3] * 0.5);
    }
    function appliquer() {
      svg.setAttribute('viewBox', vue.map((v) => v.toFixed(1)).join(' '));
      svg.style.setProperty('--z', String(1 / unite()));
    }
    function zoomer(f, cx = vue[0] + vue[2] / 2, cy = vue[1] + vue[3] / 2) {
      // Largeur plafonnée avant de placer la vue : au zoom maximal, un cran de plus ne déplace plus la carte.
      const w = Math.min(Math.max(vue[2] / f, depart[2] / ZOOM_MAX), Math.max(limites[2], depart[2]));
      if (Math.abs(w - vue[2]) < 1e-6) return;
      const h = w * (depart[3] / depart[2]);
      vue = [cx - (cx - vue[0]) * (w / vue[2]), cy - (cy - vue[1]) * (h / vue[3]), w, h];
      borner();
      appliquer();
    }
    zone.querySelectorAll('[data-zoom]').forEach((b) => {
      b.hidden = false;
      b.addEventListener('click', () => {
        if (b.dataset.zoom === 'plus') zoomer(1.6);
        else if (b.dataset.zoom === 'moins') zoomer(1 / 1.6);
        else { vue = depart.slice(); appliquer(); }
      });
    });
    zone.querySelector('[data-controles]')?.removeAttribute('hidden');
    svg.addEventListener('wheel', (ev) => {
      ev.preventDefault();
      const r = svg.getBoundingClientRect();
      zoomer(ev.deltaY < 0 ? 1.25 : 0.8, vue[0] + (ev.clientX - r.left) * unite(), vue[1] + (ev.clientY - r.top) * unite());
    }, { passive: false });
    let glisse = null;
    svg.addEventListener('pointerdown', (ev) => { if (ev.button === 0) glisse = { x: ev.clientX, y: ev.clientY, vue: vue.slice(), id: ev.pointerId, bouge: false }; });
    svg.addEventListener('pointermove', (ev) => {
      if (!glisse) return;
      const dx = ev.clientX - glisse.x;
      const dy = ev.clientY - glisse.y;
      if (!glisse.bouge && Math.hypot(dx, dy) < 4) return;
      if (!glisse.bouge) { glisse.bouge = true; svg.setPointerCapture(glisse.id); svg.classList.add('est-deplacee'); }
      const u = unite();
      vue = [glisse.vue[0] - dx * u, glisse.vue[1] - dy * u, glisse.vue[2], glisse.vue[3]];
      borner();
      appliquer();
    });
    const fin = () => { svg.classList.remove('est-deplacee'); glisse = null; };
    svg.addEventListener('pointerup', fin);
    svg.addEventListener('pointercancel', fin);
    zone.addEventListener('keydown', (ev) => {
      if (ev.target !== zone) return;
      const pas = vue[2] * 0.12;
      const fleches = { ArrowLeft: [-pas, 0], ArrowRight: [pas, 0], ArrowUp: [0, -pas], ArrowDown: [0, pas] };
      if (fleches[ev.key]) { ev.preventDefault(); vue[0] += fleches[ev.key][0]; vue[1] += fleches[ev.key][1]; borner(); appliquer(); }
      else if (ev.key === '+' || ev.key === '=') { ev.preventDefault(); zoomer(1.6); }
      else if (ev.key === '-') { ev.preventDefault(); zoomer(1 / 1.6); }
      else if (ev.key === '0') { ev.preventDefault(); vue = depart.slice(); appliquer(); }
    });
    if ('ResizeObserver' in window) new ResizeObserver(appliquer).observe(svg);
    appliquer();
  });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCartesZoom);
else initCartesZoom();
