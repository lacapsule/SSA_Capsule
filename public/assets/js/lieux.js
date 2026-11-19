// Outil « Trouver un lieu » (décisions du 2026-11-17, rule web-design « Choix nombreux ») : commune choisie dans la liste
// déroulante ou sur la carte, recherche par nom ou produit (sans accent ni casse), « Tout afficher », calques de la carte
// du collectif cochés dans la légende. Carte : zoom et déplacement (boutons, molette, glisser, flèches, + et −, 0),
// points proches regroupés en ronds numérotés, lieux à la même position en un seul repère, noms de communes posés sans
// chevauchement, « Carte » d'une fiche qui
// zoome sur le lieu. Molette (demande de Kouzia, 2026-11-17) : souris sur la carte, elle zoome et ne fait jamais défiler
// la page ; le dézoom s'arrête à la vue de départ.
// Carte en grand : les fiches s'ouvrent dans une barre latérale à droite. Amélioration progressive : sans ce script,
// outils masqués, tout est affiché ; le contenu est toujours dans la page.
function initLieux() {
  const zone = document.querySelector('[data-lieux]');
  if (!zone || zone.dataset.pret) return;
  zone.dataset.pret = '1';
  const $ = (s) => zone.querySelector(s);
  const outils = $('[data-outils]');
  const calques = $('[data-calques]');
  const choix = $('[data-commune-choix]');
  const champ = $('[data-recherche-champ]');
  const intro = $('[data-intro]');
  const indication = $('[data-synthese]');
  const resume = $('[data-resume]');
  const aucun = $('[data-aucun]');
  const panneau = $('[data-panneau]');
  const barre = $('[data-panneau-barre]');
  const dessin = $('[data-dessin]');
  const carte = $('.carte-lieux');
  const groupes = [...zone.querySelectorAll('[data-groupe]')];
  const liensCarte = carte ? [...carte.querySelectorAll('[data-commune]')] : [];
  const points = carte ? [...carte.querySelectorAll('[data-groupe-point]')] : [];
  const grappes = carte ? carte.querySelector('[data-grappes]') : null;
  const cases = [...zone.querySelectorAll('[data-calque-choix]')];
  const pageCarte = zone.classList.contains('lieux-explorer--page');
  const simple = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const lieux = groupes.flatMap((g) => [...g.querySelectorAll('[data-lieu]')].map((l) => ({ g, l, calque: l.dataset.calque, texte: simple(g.querySelector('.lieux-groupe__titre').textContent + ' ' + l.textContent) })));
  const nommer = (n) => `${n} ${n > 1 ? zone.dataset.plusieurs : zone.dataset.un}`;
  const doux = () => (window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth');
  let tout = false;
  let repere = null;

  // --- Vue de la carte : viewBox déplacé et agrandi, bornée au dessin ; tailles des points et des noms compensées. ---
  const nombres = (a) => (a || '').split(/\s+/).map(Number);
  const cadre = carte ? nombres(carte.dataset.vue) : [0, 0, 1, 1];
  let depart = cadre.slice();
  const limites = carte ? nombres(carte.dataset.limites) : [0, 0, 1, 1];
  let vue = depart.slice();
  // Jusqu'à l'échelle d'un marché (environ 60 m de large) : les stands, à quelques mètres les uns des autres, se
  // séparent chacun à sa position.
  const ZOOM_MAX = 1000;
  function borner() {
    const [lx, ly, lw, lh] = limites;
    vue[2] = Math.min(Math.max(vue[2], depart[2] / ZOOM_MAX), Math.max(lw, depart[2]));
    vue[3] = vue[2] * (depart[3] / depart[2]);
    // Bornes larges (un quart de la vue au moins reste sur le dessin) : aucune vue permise, dont celle de départ, n'est
    // ramenée de force, donc aucun saut au premier zoom ou au premier glisser.
    vue[0] = Math.min(Math.max(vue[0], lx - vue[2] * 0.75), lx + lw - vue[2] * 0.25);
    vue[1] = Math.min(Math.max(vue[1], ly - vue[3] * 0.75), ly + lh - vue[3] * 0.25);
  }
  function appliquer() {
    if (!carte) return;
    carte.setAttribute('viewBox', vue.map((v) => v.toFixed(1)).join(' '));
    // --z : pixels d'écran par unité de la carte ; points, ronds et noms gardent ainsi leur taille à l'écran.
    carte.style.setProperty('--z', String(1 / unite()));
    // Vue d'ensemble : les routes moyennes (carte en grand) ne sont dessinées qu'une fois zoomé (moins de tracés à peindre).
    carte.classList.toggle('est-loin', vue[2] > depart[2] / 2.5);
    fermerFiche();
    // Pendant un zoom ou un déplacement continu, les regroupements ne sont recalculés qu'à l'arrêt du mouvement
    // (ralentissement constaté sur la vue d'ensemble, 2026-11-17) ; tracés peints en mode rapide pendant ce temps.
    carte.classList.add('est-en-mouvement');
    clearTimeout(appliquer.minuterie);
    appliquer.minuterie = setTimeout(() => { carte.classList.remove('est-en-mouvement'); regrouper(); }, 120);
  }
  function zoomer(facteur, cx = vue[0] + vue[2] / 2, cy = vue[1] + vue[3] / 2) {
    // Largeur plafonnée avant de placer la vue : au zoom maximal, un cran de plus ne déplace plus la carte.
    const w = Math.min(Math.max(vue[2] / facteur, depart[2] / ZOOM_MAX), Math.max(limites[2], depart[2]));
    if (Math.abs(w - vue[2]) < 1e-6) return;
    const h = w * (depart[3] / depart[2]);
    vue = [cx - (cx - vue[0]) * (w / vue[2]), cy - (cy - vue[1]) * (h / vue[3]), w, h];
    borner();
    appliquer();
  }
  function centrer(x, y, echelle) {
    const w = depart[2] / echelle;
    const h = depart[3] / echelle;
    vue = [x - w / 2, y - h / 2, w, h];
    borner();
    appliquer();
  }
  const unite = () => vue[2] / (carte.getBoundingClientRect().width || 1);
  // Vue de départ : tous les lieux dans la partie libre de la carte, hors des outils et de la légende posés dessus, à la
  // proportion de la carte affichée. Carte en hauteur (téléphone) : la largeur est un peu rognée plutôt que de laisser
  // de grands vides au-dessus et au-dessous du territoire ; la carte se déplace au doigt.
  function cadrer() {
    if (!carte) return;
    const r = carte.getBoundingClientRect();
    if (!r.width || !r.height) return;
    const libre = { haut: 12, bas: pageCarte ? 36 : 12, gauche: 12, droite: 12 };
    const coupe = (b) => b.width && b.height && b.top < r.bottom && b.bottom > r.top && b.left < r.right && b.right > r.left;
    const o = outils.getBoundingClientRect();
    if (coupe(o) && o.top < r.top + r.height / 2) libre.haut = Math.max(libre.haut, o.bottom - r.top + 12);
    const l = calques.getBoundingClientRect();
    if (coupe(l)) {
      if (l.width >= r.width / 2) libre.bas = Math.max(libre.bas, r.bottom - l.top + 12);
      else libre.gauche = Math.max(libre.gauche, l.right - r.left + 12);
    }
    const fw = r.width - libre.gauche - libre.droite;
    const fh = r.height - libre.haut - libre.bas;
    const enHauteur = fh > fw;
    const e = Math.min((fw / cadre[2]) * (enHauteur ? 1.7 : 1), fh / cadre[3]);
    const w = r.width / e;
    const h = r.height / e;
    const intacte = vue.every((v, i) => Math.abs(v - depart[i]) < 0.01);
    depart = [cadre[0] - (libre.gauche + (fw - cadre[2] * e) / 2) / e, cadre[1] - (libre.haut + (fh - cadre[3] * e) / 2) / e, w, h];
    // Vue déjà zoomée ou déplacée par la personne : gardée, seule sa proportion suit la nouvelle taille de la carte.
    if (intacte) vue = depart.slice();
    else { vue[3] = vue[2] * (h / w); borner(); }
    appliquer();
  }
  function versCarte(ev) {
    const r = carte.getBoundingClientRect();
    return [vue[0] + (ev.clientX - r.left) * unite(), vue[1] + (ev.clientY - r.top) * unite()];
  }

  // --- Regroupement : points visibles à moins de 18 px les uns des autres réunis en un rond numéroté. ---
  // Lieux à la même position (producteurs d'un marché : la carte du collectif leur donne la position du marché) :
  // un seul repère, jamais des positions inventées.
  const sites = [];
  for (const p of points) {
    const x = +p.getAttribute('cx');
    const y = +p.getAttribute('cy');
    const s = sites.find((t) => Math.hypot(t.x - x, t.y - y) < 0.005);
    if (s) s.points.push(p); else sites.push({ x, y, points: [p] });
  }
  function repereSvg(classe, x, y, n, label, donnees) {
    const ns = 'http://www.w3.org/2000/svg';
    const g = document.createElementNS(ns, 'g');
    g.setAttribute('class', classe);
    g.setAttribute('tabindex', '0');
    g.setAttribute('role', 'button');
    g.setAttribute('aria-label', label);
    Object.assign(g.dataset, { x: String(x), y: String(y) }, donnees);
    const c = document.createElementNS(ns, 'circle');
    c.setAttribute('cx', String(x));
    c.setAttribute('cy', String(y));
    const t = document.createElementNS(ns, 'text');
    t.setAttribute('x', String(x));
    t.setAttribute('y', String(y));
    t.textContent = String(n);
    g.append(c, t);
    grappes.append(g);
  }

  // --- Regroupement : lieux visibles à moins de 28 px les uns des autres réunis en un rond numéroté (un clic zoome) ;
  // plusieurs lieux à la même position : un repère « même lieu » (un clic ouvre leur liste). ---
  function regrouper() {
    if (!grappes) return;
    grappes.replaceChildren();
    // Au zoom le plus fort, plus aucun regroupement : chaque lieu à sa position.
    const seuil = vue[2] <= (depart[2] / ZOOM_MAX) * 1.05 ? 0 : 28 * unite();
    points.forEach((p) => p.classList.remove('est-groupe'));
    const visibles = sites.map((s) => ({ ...s, vus: s.points.filter((p) => !p.classList.contains('est-masque')) })).filter((s) => s.vus.length);
    const pris = new Set();
    for (const s of visibles) {
      if (pris.has(s)) continue;
      const proches = visibles.filter((t) => !pris.has(t) && Math.hypot(t.x - s.x, t.y - s.y) < seuil);
      proches.forEach((t) => pris.add(t));
      const membres = proches.flatMap((t) => t.vus);
      if (membres.length < 2) continue;
      membres.forEach((p) => { if (p !== repere) p.classList.add('est-groupe'); });
      if (proches.length === 1) {
        repereSvg('carte-lieux__grappe carte-lieux__grappe--lieu', s.x, s.y, membres.length, `${nommer(membres.length)} au même endroit : voir la liste`, { groupe: membres[0].dataset.groupePoint });
      } else {
        const gx = proches.reduce((a, t) => a + t.x * t.vus.length, 0) / membres.length;
        const gy = proches.reduce((a, t) => a + t.y * t.vus.length, 0) / membres.length;
        const ecart = Math.max(...proches.map((t) => Math.hypot(t.x - gx, t.y - gy)));
        repereSvg('carte-lieux__grappe', gx, gy, membres.length, `${nommer(membres.length)} : zoomer`, { ecart: String(ecart) });
      }
    }
    placerNoms();
  }

  // Rond numéroté : zoom jusqu'à séparer ses lieux. Repère « même lieu » (position identique) : la liste s'ouvre.
  function ouvrirGrappe(g) {
    if (g.dataset.groupe) { repere = null; choisir(g.dataset.groupe); if (pageCarte) panneau.scrollTo({ top: 0, behavior: doux() }); else if (window.matchMedia('(max-width: 64rem)').matches) panneau.scrollIntoView({ block: 'start', behavior: doux() }); }
    else {
      // Zoom direct jusqu'à ce que les lieux du rond s'écartent d'environ 90 px (au moins ×2,5).
      const ecart = Math.max(+g.dataset.ecart || 0, 0.01);
      const largeur = carte.getBoundingClientRect().width || 1;
      const facteur = Math.max(2.5, (vue[2] / largeur) * (90 / ecart));
      zoomer(facteur, +g.dataset.x, +g.dataset.y);
    }
  }

  // --- Noms des communes : au-dessus du centre, sinon dessous, à droite, à gauche, un peu plus loin ; masqués s'ils
  // touchent partout un point, un rond ou un autre nom ; la commune choisie passe en premier. ---
  const noms = carte ? [...carte.querySelectorAll('.carte-lieux__noms:not(.carte-lieux__noms--choisi) text')] : [];
  function placerNoms() {
    if (!carte) return;
    const u = unite();
    const boites = [];
    const recouvre = (a) => boites.some((b) => a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h);
    for (const p of points) {
      if (p.classList.contains('est-masque') || p.classList.contains('est-groupe')) continue;
      const rr = (p === repere || p.classList.contains('est-choisi') ? 10 : 6) * u;
      boites.push({ x: +p.getAttribute('cx') - rr, y: +p.getAttribute('cy') - rr, w: 2 * rr, h: 2 * rr });
    }
    for (const g of grappes.children) { const rr = 14 * u; boites.push({ x: +g.dataset.x - rr, y: +g.dataset.y - rr, w: 2 * rr, h: 2 * rr }); }
    const choisi = carte.querySelector('[data-nom-choisi]');
    const liste = choisi && choisi.textContent ? [choisi, ...noms.filter((n) => n.textContent !== choisi.textContent)] : noms;
    for (const n of liste) {
      if (!n.dataset.cy) { n.dataset.cx = n.getAttribute('x'); n.dataset.cy = n.getAttribute('y'); }
      const cx = +n.dataset.cx;
      const cy = +n.dataset.cy;
      n.classList.remove('est-cache');
      n.setAttribute('x', String(cx));
      n.setAttribute('y', String(cy));
      const demi = n.getBBox().width / 2 + 16 * u;
      let place = false;
      for (const [dx, dy] of [[0, -18], [0, 20], [demi, 0], [-demi, 0], [0, -36], [0, 38]]) {
        n.setAttribute('x', String(cx + dx));
        n.setAttribute('y', String(cy + dy * u));
        const b = n.getBBox();
        const boite = { x: b.x - 2 * u, y: b.y - 2 * u, w: b.width + 4 * u, h: b.height + 4 * u };
        const dedans = boite.x >= vue[0] && boite.y >= vue[1] && boite.x + boite.w <= vue[0] + vue[2] && boite.y + boite.h <= vue[1] + vue[3];
        if (n === choisi || (dedans && !recouvre(boite))) { boites.push(boite); place = true; break; }
      }
      if (!place) n.classList.add('est-cache');
    }
    noms.forEach((n) => { if (choisi && choisi.textContent && n.textContent === choisi.textContent) n.classList.add('est-cache'); });
  }

  // --- Infobulle (survol d'un point : nom du lieu) et petite fiche (clic : infos et itinéraire, copie de la fiche de la
  // liste, sans rien inventer). Échap ou un clic ailleurs la ferme. ---
  const ficheDe = (p) => zone.querySelector(`[data-zoom-lieu="${p.dataset.lieuId}"]`)?.closest('[data-lieu]') ?? null;
  const bulle = document.createElement('p');
  bulle.className = 'carte-infobulle';
  bulle.hidden = true;
  bulle.setAttribute('aria-hidden', 'true');
  const fiche = document.createElement('div');
  fiche.className = 'carte-fiche';
  fiche.hidden = true;
  fiche.setAttribute('role', 'dialog');
  fiche.setAttribute('aria-label', zone.dataset.ficheTitre || 'Lieu');
  dessin?.append(bulle, fiche);
  let ficheSource = null;
  function placer(el, cx, cy) {
    const r = dessin.getBoundingClientRect();
    const s = carte.getBoundingClientRect();
    const x = s.left - r.left + (cx - vue[0]) / unite();
    const y = s.top - r.top + (cy - vue[1]) / unite();
    const l = Math.min(Math.max(8, x - el.offsetWidth / 2), r.width - el.offsetWidth - 8);
    const h = y - el.offsetHeight - 14 < 8 ? y + 14 : y - el.offsetHeight - 14;
    el.style.setProperty('left', `${l}px`);
    el.style.setProperty('top', `${Math.min(Math.max(8, h), r.height - el.offsetHeight - 8)}px`);
  }
  function fermerFiche() {
    if (!fiche || fiche.hidden) return;
    fiche.hidden = true;
    fiche.replaceChildren();
    ficheSource?.focus?.();
    ficheSource = null;
  }
  function ouvrirFiche(p) {
    const li = ficheDe(p);
    if (!li) return;
    const copie = li.cloneNode(true);
    copie.removeAttribute('hidden');
    copie.querySelector('[data-zoom-lieu]')?.remove();
    const fermer = document.createElement('button');
    fermer.type = 'button';
    fermer.className = 'carte-fiche__fermer';
    fermer.setAttribute('aria-label', zone.dataset.fermer || 'Fermer');
    const ns = 'http://www.w3.org/2000/svg';
    const icone = document.createElementNS(ns, 'svg');
    icone.setAttribute('viewBox', '0 0 24 24');
    icone.setAttribute('width', '18');
    icone.setAttribute('height', '18');
    icone.setAttribute('aria-hidden', 'true');
    const trait = document.createElementNS(ns, 'path');
    trait.setAttribute('d', 'M6 6l12 12M18 6 6 18');
    trait.setAttribute('fill', 'none');
    trait.setAttribute('stroke', 'currentColor');
    trait.setAttribute('stroke-width', '2');
    trait.setAttribute('stroke-linecap', 'round');
    icone.append(trait);
    fermer.append(icone);
    fermer.addEventListener('click', fermerFiche);
    fiche.replaceChildren(fermer, ...copie.childNodes);
    fiche.hidden = false;
    bulle.hidden = true;
    placer(fiche, +p.getAttribute('cx'), +p.getAttribute('cy'));
    fermer.focus();
  }
  if (carte) {
    carte.addEventListener('pointerover', (ev) => {
      const p = ev.target.closest?.('.carte-lieux__points circle');
      if (!p || !fiche.hidden) return;
      const li = ficheDe(p);
      bulle.textContent = li ? li.querySelector('.lieu__nom').textContent : '';
      bulle.hidden = !li;
      placer(bulle, +p.getAttribute('cx'), +p.getAttribute('cy'));
    });
    carte.addEventListener('pointerout', (ev) => { if (ev.target.closest?.('.carte-lieux__points circle')) bulle.hidden = true; });
    carte.addEventListener('click', (ev) => {
      const p = ev.target.closest?.('.carte-lieux__points circle');
      if (!p || zone.dataset.glisse) return;
      ev.preventDefault();
      ev.stopPropagation();
      ficheSource = dessin;
      ouvrirFiche(p);
    });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape' && !fiche.hidden) { ev.stopPropagation(); fermerFiche(); } }, true);
    document.addEventListener('pointerdown', (ev) => { if (!fiche.hidden && !fiche.contains(ev.target)) fermerFiche(); });
  }

  // --- Filtres, calques et liste ---
  function afficher() {
    const commune = choix.value;
    const q = simple(champ.value.trim());
    const actifs = new Set(cases.filter((c) => c.checked).map((c) => c.value));
    const demande = commune !== '' || q !== '' || tout;
    let visibles = 0;
    for (const { g, l, calque, texte } of lieux) {
      const ok = demande && actifs.has(calque) && (commune === '' || g.id === commune) && (q === '' || texte.includes(q));
      l.hidden = !ok;
      if (ok) visibles++;
    }
    for (const g of groupes) {
      const n = g.querySelectorAll('[data-lieu]:not([hidden])').length;
      g.hidden = n === 0;
      g.querySelector('[data-groupe-nombre]').textContent = n ? `(${n})` : '';
    }
    if (intro) intro.hidden = demande;
    if (indication) indication.hidden = demande;
    if (pageCarte) { zone.classList.toggle('a-un-panneau', demande); if (barre) barre.hidden = !demande; }
    aucun.hidden = !demande || visibles > 0;
    resume.textContent = demande ? zone.dataset.trouves.replace('{n}', nommer(visibles)) : '';
    points.forEach((p) => {
      p.classList.toggle('est-masque', !actifs.has(p.dataset.calque));
      p.classList.toggle('est-choisi', commune !== '' && p.dataset.groupePoint === commune);
    });
    if (carte) carte.classList.toggle('a-un-choix', commune !== '');
    liensCarte.forEach((a) => a.classList.toggle('est-choisie', a.dataset.commune === commune));
    const contourChoisi = carte?.querySelector('[data-contour-choisi]');
    const lienDuChoix = liensCarte.find((a) => a.dataset.commune === commune);
    if (contourChoisi) contourChoisi.setAttribute('d', lienDuChoix ? lienDuChoix.querySelector('path').getAttribute('d') : '');
    // Nom de la commune choisie, toujours affiché (même au zoom, même pour une petite commune).
    const nomChoisi = carte?.querySelector('[data-nom-choisi]');
    const lienChoisi = liensCarte.find((a) => a.dataset.commune === commune);
    if (nomChoisi) {
      nomChoisi.textContent = lienChoisi ? lienChoisi.dataset.nom : '';
      if (lienChoisi) { nomChoisi.setAttribute('x', lienChoisi.dataset.cx); nomChoisi.setAttribute('y', lienChoisi.dataset.cy); }
    }
    regrouper();
  }
  function choisir(commune) {
    choix.value = commune;
    tout = false;
    afficher();
  }

  outils.hidden = false;
  calques.hidden = false;
  $('[data-controles]').hidden = false;
  zone.querySelectorAll('[data-zoom-lieu]').forEach((b) => { b.hidden = false; });
  choix.addEventListener('change', () => { tout = false; repere = null; afficher(); });
  champ.addEventListener('input', afficher);
  cases.forEach((c) => c.addEventListener('change', afficher));
  outils.addEventListener('submit', (ev) => ev.preventDefault());
  $('[data-tout]').addEventListener('click', () => { tout = true; choix.value = ''; champ.value = ''; afficher(); });
  $('[data-fermer-panneau]')?.addEventListener('click', () => { choix.value = ''; champ.value = ''; tout = false; repere = null; afficher(); dessin.focus(); });

  // Boutons de zoom, molette, glisser.
  zone.querySelectorAll('[data-zoom]').forEach((b) => b.addEventListener('click', () => {
    if (b.dataset.zoom === 'plus') zoomer(1.6);
    else if (b.dataset.zoom === 'moins') zoomer(1 / 1.6);
    else { vue = depart.slice(); appliquer(); }
  }));
  if (carte) {
    carte.addEventListener('wheel', (ev) => {
      // Souris sur la carte : la molette ne fait que zoomer (demande de Kouzia), sans aller au-delà de la vue de départ.
      ev.preventDefault();
      let facteur = ev.deltaY < 0 ? 1.25 : 0.8;
      if (facteur < 1) {
        if (vue[2] >= depart[2] * 0.999) return;
        facteur = Math.max(facteur, vue[2] / depart[2]);
      }
      const [x, y] = versCarte(ev);
      zoomer(facteur, x, y);
    }, { passive: false });
    let glisse = null;
    carte.addEventListener('pointerdown', (ev) => {
      if (ev.button !== 0) return;
      glisse = { x: ev.clientX, y: ev.clientY, vue: vue.slice(), bouge: false, id: ev.pointerId };
    });
    carte.addEventListener('pointermove', (ev) => {
      if (!glisse) return;
      const dx = ev.clientX - glisse.x;
      const dy = ev.clientY - glisse.y;
      if (!glisse.bouge && Math.hypot(dx, dy) < 5) return;
      if (!glisse.bouge) { glisse.bouge = true; carte.setPointerCapture(glisse.id); carte.classList.add('est-deplacee'); }
      const u = unite();
      vue = [glisse.vue[0] - dx * u, glisse.vue[1] - dy * u, glisse.vue[2], glisse.vue[3]];
      borner();
      appliquer();
    });
    const fin = () => { if (glisse?.bouge) { carte.classList.remove('est-deplacee'); zone.dataset.glisse = '1'; setTimeout(() => { delete zone.dataset.glisse; }, 0); } glisse = null; };
    carte.addEventListener('pointerup', fin);
    carte.addEventListener('pointercancel', fin);
  }
  // Clavier sur la carte : flèches, + et −, 0.
  dessin?.addEventListener('keydown', (ev) => {
    if (ev.target !== dessin) return;
    const pas = vue[2] * 0.12;
    const actions = { ArrowLeft: () => { vue[0] -= pas; }, ArrowRight: () => { vue[0] += pas; }, ArrowUp: () => { vue[1] -= pas; }, ArrowDown: () => { vue[1] += pas; } };
    if (actions[ev.key]) { ev.preventDefault(); actions[ev.key](); borner(); appliquer(); }
    else if (ev.key === '+' || ev.key === '=') { ev.preventDefault(); zoomer(1.6); }
    else if (ev.key === '-' || ev.key === '−') { ev.preventDefault(); zoomer(1 / 1.6); }
    else if (ev.key === '0') { ev.preventDefault(); vue = depart.slice(); appliquer(); }
  });

  // Clics : commune de la carte, grappe (zoom), « Carte » d'une fiche (zoom sur le lieu).
  zone.addEventListener('click', (ev) => {
    if (zone.dataset.glisse) { ev.preventDefault(); return; }
    const grappe = ev.target.closest('.carte-lieux__grappe');
    if (grappe) { ouvrirGrappe(grappe); return; }
    const zoom = ev.target.closest('[data-zoom-lieu]');
    if (zoom) {
      const p = carte?.querySelector(`[data-lieu-id="${zoom.dataset.zoomLieu}"]`);
      if (!p) return;
      if (repere) repere.classList.remove('est-repere');
      repere = p;
      p.classList.add('est-repere');
      if (choix.value !== zoom.dataset.groupeDuLieu) choisir(zoom.dataset.groupeDuLieu);
      centrer(+p.getAttribute('cx'), +p.getAttribute('cy'), 80); // échelle du quartier : le lieu se distingue de ses voisins de marché
      if (!pageCarte) zone.querySelector('.lieux-carte').scrollIntoView({ block: 'nearest', behavior: doux() });
      return;
    }
    const lien = ev.target.closest('[data-commune]');
    if (!lien) return;
    ev.preventDefault();
    repere = null;
    choisir(lien.dataset.commune);
    if (pageCarte) panneau.scrollTo({ top: 0, behavior: doux() });
    else if (window.matchMedia('(max-width: 64rem)').matches) panneau.scrollIntoView({ block: 'start', behavior: doux() });
  });
  zone.addEventListener('keydown', (ev) => {
    const grappe = ev.target.closest?.('.carte-lieux__grappe');
    if (grappe && (ev.key === 'Enter' || ev.key === ' ')) { ev.preventDefault(); ouvrirGrappe(grappe); }
  });
  // Survol ou focus d'une commune : son contour passe au-dessus de ceux de ses voisines.
  const contour = carte?.querySelector('[data-contour]');
  if (contour) {
    const montrer = (ev) => { const a = ev.target.closest?.('.carte-lieux__lien'); contour.setAttribute('d', a ? a.querySelector('path').getAttribute('d') : ''); contour.classList.toggle('est-focus', ev.type === 'focusin' && Boolean(a)); };
    carte.addEventListener('pointerover', montrer);
    carte.addEventListener('focusin', montrer);
    carte.addEventListener('pointerleave', () => contour.setAttribute('d', ''));
    carte.addEventListener('focusout', () => { contour.setAttribute('d', ''); contour.classList.remove('est-focus'); });
  }
  if (carte && 'ResizeObserver' in window) {
    let largeur = 0;
    new ResizeObserver(() => {
      const r = carte.getBoundingClientRect();
      if (pageCarte || r.width !== largeur) cadrer();
      largeur = r.width;
    }).observe(pageCarte ? dessin : carte);
  }

  // Adresse avec une ancre de commune (#commune-morlaix) : cette commune est choisie d'emblée.
  const suivreAncre = () => {
    const ancre = decodeURIComponent(location.hash.slice(1));
    if (ancre && groupes.some((g) => g.id === ancre)) { champ.value = ''; choisir(ancre); }
  };
  window.addEventListener('hashchange', suivreAncre);
  appliquer();
  afficher();
  cadrer();
  regrouper();
  suivreAncre();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initLieux);
else initLieux();
