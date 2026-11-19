/* Écrans de connexion du back-office : QR code otpauth:// dessiné en SVG dans le navigateur (aucun service externe),
   code à 6 chiffres limité aux chiffres. Bibliothèque QR : qrcode.min.js (MIT), chargée seulement sur l'écran d'enrôlement. */
(() => {
  'use strict';

  // QR code construit nœud par nœud (aucun innerHTML) : compatible avec une CSP stricte et Trusted Types.
  const boite = document.querySelector('[data-otpauth]');
  if (boite) {
    try {
      if (typeof window.qrcode !== 'function') throw new Error('bibliothèque QR absente');
      const code = window.qrcode(0, 'M');
      code.addData(boite.dataset.otpauth);
      code.make();
      const n = code.getModuleCount();
      const ns = 'http://www.w3.org/2000/svg';
      const svg = document.createElementNS(ns, 'svg');
      svg.setAttribute('viewBox', `-4 -4 ${n + 8} ${n + 8}`);
      svg.setAttribute('shape-rendering', 'crispEdges');
      svg.setAttribute('aria-hidden', 'true');
      svg.setAttribute('focusable', 'false');
      let d = '';
      for (let r = 0; r < n; r += 1) {
        for (let c = 0; c < n; c += 1) if (code.isDark(r, c)) d += `M${c} ${r}h1v1h-1z`;
      }
      const path = document.createElementNS(ns, 'path');
      path.setAttribute('d', d);
      svg.append(path);
      boite.replaceChildren(svg);
    } catch (e) {
      boite.classList.add('auth-qr-image--echec');
      boite.removeAttribute('role');
      boite.removeAttribute('aria-label');
      boite.textContent = 'QR code indisponible : saisissez la clé ci-contre dans l\'application.';
    }
  }

  // Code à 6 chiffres : espaces et lettres retirés au fil de la saisie (collage d'un code « 123 456 » accepté).
  document.querySelectorAll('input[data-chiffres]').forEach((champ) => {
    champ.addEventListener('input', () => {
      const propre = champ.value.replace(/\D/g, '').slice(0, 6);
      if (propre !== champ.value) champ.value = propre;
    });
  });

  // Connexion et mot de passe oublié dans la même carte : le lien bascule sur place (sans script, il mène à la page
  // du formulaire). L'adresse déjà tapée est reprise, le focus va au titre du volet affiché.
  const volets = [...document.querySelectorAll('[data-volet]')];
  if (volets.length > 1) {
    const montrer = (nom) => {
      const vise = volets.find((v) => v.dataset.volet === nom);
      if (!vise) return false;
      const depuis = volets.find((v) => !v.hidden);
      const email = depuis && depuis.querySelector('input[type=email]');
      volets.forEach((v) => { v.hidden = v !== vise; });
      const cible = vise.querySelector('input[type=email]');
      if (email && cible && email.value && !cible.value) cible.value = email.value;
      const titre = vise.querySelector('h1');
      if (titre) titre.focus();
      return true;
    };
    document.addEventListener('click', (ev) => {
      const lien = ev.target.closest('[data-bascule]');
      if (lien && montrer(lien.dataset.bascule)) ev.preventDefault();
    });
  }
})();
