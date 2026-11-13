// SPDX-License-Identifier: GPL-3.0-or-later
// Couleurs de marque vers tokens N0 vérifiés : contraste WCAG, déclinaison dans OKLCH (teinte gardée, clarté
// ajustée, saturation réduite si la couleur sort du gamut sRVB), jeux complets clair et sombre.

const borne = (x, a = 0, b = 1) => Math.min(b, Math.max(a, x));

export function versRvb(hex) {
  const h = hex.replace('#', '');
  const plein = h.length === 3 ? h.split('').map((c) => c + c).join('') : h.slice(0, 6);
  return [0, 2, 4].map((i) => parseInt(plein.slice(i, i + 2), 16) / 255);
}

export function versHex([r, g, b]) {
  return '#' + [r, g, b].map((c) => Math.round(borne(c) * 255).toString(16).padStart(2, '0')).join('');
}

const lineaire = (c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
const gamma = (c) => (c <= 0.0031308 ? 12.92 * c : 1.055 * c ** (1 / 2.4) - 0.055);

export function luminance(hex) {
  const [r, g, b] = versRvb(hex).map(lineaire);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contraste(a, b) {
  const [x, y] = [luminance(a), luminance(b)].sort((m, n) => n - m);
  return (x + 0.05) / (y + 0.05);
}

// OKLab et OKLCH (Björn Ottosson, 2020).
export function versOklch(hex) {
  const [r, g, b] = versRvb(hex).map(lineaire);
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
  const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
  const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
  const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
  return { l: L, c: Math.hypot(A, B), h: ((Math.atan2(B, A) * 180) / Math.PI + 360) % 360 };
}

function oklchVersRvbLineaire({ l, c, h }) {
  const A = c * Math.cos((h * Math.PI) / 180);
  const B = c * Math.sin((h * Math.PI) / 180);
  const l_ = (l + 0.3963377774 * A + 0.2158037573 * B) ** 3;
  const m_ = (l - 0.1055613458 * A - 0.0638541728 * B) ** 3;
  const s_ = (l - 0.0894841775 * A - 1.291485548 * B) ** 3;
  return [
    4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_,
    -1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_,
    -0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_,
  ];
}

/** OKLCH vers hexadécimal, en réduisant la saturation jusqu'à rentrer dans le gamut sRVB. */
export function depuisOklch({ l, c, h }) {
  let bas = 0;
  let haut = c;
  const dans = (cc) => oklchVersRvbLineaire({ l, c: cc, h }).every((x) => x >= -1e-4 && x <= 1 + 1e-4);
  if (dans(c)) return versHex(oklchVersRvbLineaire({ l, c, h }).map((x) => gamma(borne(x))));
  for (let i = 0; i < 24; i++) { const mi = (bas + haut) / 2; if (dans(mi)) bas = mi; else haut = mi; }
  return versHex(oklchVersRvbLineaire({ l, c: bas, h }).map((x) => gamma(borne(x))));
}

/** Mélange de deux couleurs (proportion t de la première) en sRVB, comme color-mix(in srgb) : teintes douces. */
export function melanger(a, b, t) {
  const x = versRvb(a);
  const y = versRvb(b);
  return versHex(x.map((v, i) => v * t + y[i] * (1 - t)));
}

/** Même teinte, autre clarté (et saturation facultative). */
export const varier = (hex, { l, c } = {}) => {
  const o = versOklch(hex);
  return depuisOklch({ l: l ?? o.l, c: c ?? o.c, h: o.h });
};

/**
 * Décline une couleur pour atteindre un contraste sur un fond : même teinte, clarté déplacée vers le sens
 * qui éloigne du fond (plus foncée sur fond clair, plus claire sur fond sombre). Renvoie la couleur d'origine
 * si elle suffit déjà.
 */
export function ajusterContraste(hex, fond, cible) {
  if (contraste(hex, fond) >= cible) return hex;
  const o = versOklch(hex);
  const versSombre = luminance(fond) > 0.18;
  let bas = versSombre ? 0 : o.l;
  let haut = versSombre ? o.l : 1;
  let meilleure = versSombre ? '#000000' : '#ffffff';
  for (let i = 0; i < 30; i++) {
    const mi = (bas + haut) / 2;
    const essai = depuisOklch({ l: mi, c: o.c, h: o.h });
    if (contraste(essai, fond) >= cible) { meilleure = essai; if (versSombre) bas = mi; else haut = mi; } else if (versSombre) haut = mi; else bas = mi;
  }
  return meilleure;
}

/** Couleur « chromatique » (pas un gris, pas un quasi-blanc ni un quasi-noir) : candidate de marque. */
export const estChromatique = (hex) => { const o = versOklch(hex); return o.c > 0.04 && o.l > 0.12 && o.l < 0.97; };
const ecartTeinte = (a, b) => { const d = Math.abs(versOklch(a).h - versOklch(b).h) % 360; return Math.min(d, 360 - d); };

/**
 * Couleurs de marque depuis l'inventaire : logo d'abord, puis couleurs du site par poids.
 * Renvoie { primaire, accent, fondActuel, texteActuel } (accent = seconde teinte distincte, sinon la primaire).
 */
export function couleursDeMarque(identite) {
  const site = (identite.couleurs ?? []).filter((c) => !c.note);
  const candidates = [...new Set([...(identite.couleursLogo ?? []), ...site.map((c) => c.hex)])].filter(estChromatique);
  const parPoids = site.filter((c) => estChromatique(c.hex)).sort((a, b) => b.poids - a.poids).map((c) => c.hex);
  const primaire = parPoids[0] ?? candidates[0] ?? null;
  const accent = candidates.find((c) => primaire && ecartTeinte(c, primaire) > 35) ?? primaire;
  const fond = site.find((c) => /fond/.test(c.roleProbable) && !estChromatique(c.hex))?.hex ?? null;
  const texte = site.find((c) => c.roleProbable === 'texte')?.hex ?? null;
  return { primaire, accent, fondActuel: fond, texteActuel: texte };
}

/**
 * Jeu de tokens couleur d'un thème. options : { primaire, accent, fond (clair) , sombre (bool), teinteNeutre }.
 * Tous les rôles reprennent les noms de base/tokens.css de la boîte, plus --color-marque et --color-marque-texte.
 */
export function jeuCouleurs({ primaire, accent, fond, sombre = false, style = 'neutre' }) {
  const teinte = primaire ?? accent ?? '#6b7280';
  const neutre = (l, c = 0.012) => varier(teinte, { l, c });
  const t = {};
  if (!sombre) {
    t['--color-bg'] = fond ?? neutre(0.985, 0.006);
    t['--color-surface'] = '#ffffff';
    t['--color-surface-2'] = neutre(0.955, 0.01);
    t['--color-text'] = neutre(0.22, 0.02);
    t['--color-text-muted'] = ajusterContraste(neutre(0.45, 0.02), t['--color-surface-2'], 4.6);
    t['--color-line'] = neutre(0.9, 0.012);
    t['--color-line-strong'] = neutre(0.8, 0.015);
    t['--color-border'] = ajusterContraste(neutre(0.58, 0.02), t['--color-bg'], 3.1);
  } else {
    t['--color-bg'] = neutre(0.17, 0.02);
    t['--color-surface'] = neutre(0.21, 0.022);
    t['--color-surface-2'] = neutre(0.25, 0.024);
    t['--color-text'] = neutre(0.95, 0.01);
    t['--color-text-muted'] = ajusterContraste(neutre(0.75, 0.02), t['--color-surface-2'], 4.6);
    t['--color-line'] = neutre(0.3, 0.02);
    t['--color-line-strong'] = neutre(0.4, 0.02);
    t['--color-border'] = ajusterContraste(neutre(0.55, 0.02), t['--color-bg'], 3.1);
  }
  const plusExigeant = (c, cible) => [t['--color-bg'], t['--color-surface'], t['--color-surface-2']].reduce((x, f) => ajusterContraste(x, f, cible), c);
  const acc = accent ?? teinte;
  t['--color-accent'] = plusExigeant(acc, 4.6);
  t['--color-accent-strong'] = sombre ? varier(t['--color-accent'], { l: Math.min(0.95, versOklch(t['--color-accent']).l + 0.08) }) : varier(t['--color-accent'], { l: Math.max(0.2, versOklch(t['--color-accent']).l - 0.08) });
  t['--color-accent-soft'] = melanger(t['--color-accent'], t['--color-surface'], sombre ? 0.12 : 0.08);
  t['--color-accent-text'] = contraste('#ffffff', t['--color-accent']) >= contraste(neutre(0.15, 0.02), t['--color-accent']) ? '#ffffff' : neutre(0.15, 0.02);
  t['--color-focus'] = plusExigeant(acc, 3.1);
  t['--color-marque'] = primaire ?? acc;
  t['--color-marque-texte'] = contraste('#ffffff', t['--color-marque']) >= 4.5 ? '#ffffff' : contraste(neutre(0.15, 0.02), t['--color-marque']) >= 4.5 ? neutre(0.15, 0.02) : ajusterContraste('#ffffff', t['--color-marque'], 4.5);
  const statut = (h) => plusExigeant(depuisOklch({ l: sombre ? 0.78 : 0.5, c: 0.15, h }), 4.6);
  t['--color-danger'] = statut(27);
  t['--color-success'] = statut(150);
  t['--color-warning'] = statut(70);
  t['--color-info'] = statut(260);
  // Rôles de zone : ce qui distingue les pistes sur les vraies pages (en-tête et haut de page), en tokens.
  const zones = {
    neutre: ['--color-surface', '--color-text', '--color-surface', '--color-text'],
    'entete-marque': ['--color-marque', '--color-marque-texte', '--color-surface', '--color-text'],
    aere: ['--color-bg', '--color-text', '--color-accent-soft', '--color-text'],
    'marque-en-grand': ['--color-marque', '--color-marque-texte', '--color-marque', '--color-marque-texte'],
  }[style] ?? [];
  [t['--color-entete-fond'], t['--color-entete-texte'], t['--color-heros-fond'], t['--color-heros-texte']] = zones.map((k) => t[k]);
  // Accent propre à chaque zone (liens et boutons posés dessus) : l'accent décliné pour 4,5:1 sur le fond de la
  // zone, sinon la couleur de texte de la zone. Un bouton de zone prend l'accent en fond et le fond de zone en texte.
  for (const z of ['entete', 'heros']) {
    const fondZone = t[`--color-${z}-fond`];
    const essai = ajusterContraste(acc, fondZone, 4.6);
    t[`--color-${z}-accent`] = contraste(essai, fondZone) >= 4.5 && contraste(essai, fondZone) > 0 && !['#000000', '#ffffff'].includes(essai) ? essai : t[`--color-${z}-texte`];
  }
  return t;
}

/** Paires à contrôler : [premier plan, fond, cible, usage]. */
export function verifierJeu(t) {
  const paires = [];
  for (const fond of ['--color-bg', '--color-surface', '--color-surface-2']) {
    paires.push(['--color-text', fond, 4.5, 'texte'], ['--color-text-muted', fond, 4.5, 'texte secondaire'], ['--color-accent', fond, 4.5, 'lien et texte d\'accent'], ['--color-focus', fond, 3, 'contour de focus']);
    for (const s of ['--color-danger', '--color-success', '--color-warning', '--color-info']) paires.push([s, fond, 4.5, 'message']);
  }
  paires.push(['--color-entete-texte', '--color-entete-fond', 4.5, 'texte de l\'en-tête'], ['--color-heros-texte', '--color-heros-fond', 4.5, 'texte du haut de page']);
  paires.push(['--color-entete-accent', '--color-entete-fond', 4.5, 'lien et bouton de l\'en-tête'], ['--color-heros-accent', '--color-heros-fond', 4.5, 'lien et bouton du haut de page']);
  paires.push(['--color-text', '--color-accent-soft', 4.5, 'texte sur fond teinté'], ['--color-text-muted', '--color-accent-soft', 4.5, 'texte secondaire sur fond teinté']);
  paires.push(['--color-border', '--color-bg', 3, 'contour de champ'], ['--color-accent-text', '--color-accent', 4.5, 'texte sur bouton'], ['--color-marque-texte', '--color-marque', 4.5, 'texte sur la couleur de marque']);
  return paires.map(([a, b, cible, usage]) => ({ avant: a, fond: b, cible, usage, ratio: contraste(t[a], t[b]), ok: contraste(t[a], t[b]) >= cible }));
}
