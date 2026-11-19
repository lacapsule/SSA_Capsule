/* Minuteur de session (public/assets/js/minuteur.js, defer), sur le partiel session/minuteur.
   Avertit avant l'expiration (WCAG 2.2.1) : dialogue natif « Votre session expire dans 1 min 58 s », décompte visible
   chaque seconde mais annoncé sans insister (toutes les 30 s, puis à 10 s), « Rester connecté » prolonge par un POST
   léger avec le jeton CSRF. Échéance partagée entre onglets (BroadcastChannel, repli sur l'événement storage).
   À l'échéance : saisie des formulaires [data-minuteur-brouillon] gardée dans sessionStorage, puis redirection
   vers la connexion avec retour à la page ; la saisie est remise au retour. Sans adresse data-prolonger (catalogue),
   tout se passe dans la page. DOM modifié par textContent seulement. */
(() => {
  'use strict';
  const racine = document.querySelector('[data-minuteur]');
  if (!racine || racine.dataset.minuteurInit !== undefined) return;
  racine.dataset.minuteurInit = '';

  const $ = (sel) => racine.querySelector(sel);
  const dialogue = $('[data-minuteur-dialogue]');
  const titre = $('[data-minuteur-titre]');
  const texte = $('[data-minuteur-texte]');
  const horloge = $('[data-minuteur-horloge]');
  const annonce = $('[data-minuteur-annonce]');
  const statut = $('[data-minuteur-statut]');
  const erreur = $('[data-minuteur-erreur]');
  const form = $('[data-minuteur-form]');
  const prolonger = $('[data-minuteur-prolonger]');
  const sortir = $('[data-minuteur-sortir]');
  const reconnecter = $('[data-minuteur-reconnecter]');
  if (!dialogue || typeof dialogue.showModal !== 'function') return;

  const urlEtat = racine.dataset.etat || '';
  const urlProlonger = racine.dataset.prolonger || '';
  const urlExpiree = racine.dataset.expiree || '';
  const avertir = Number(racine.dataset.avertir || 120) * 1000;
  const inactivite = Number(racine.dataset.inactivite || 1800) * 1000;
  const CLE = 'minuteur-session';
  const SECRET = /pass|mot_de_passe|motdepasse|secret|token|jeton|csrf|code|iban|carte|card/i;

  let finInactivite = Date.now() + Number(racine.dataset.restant || 0) * 1000;
  let finAbsolue = Date.now() + Number(racine.dataset.absolu || 0) * 1000;
  let minuteurAvertir = null;
  let minuteurFin = null;
  let tic = null;
  let prochaineAnnonce = -1;
  let absolueVue = false;
  let termine = false;
  let dernierProlongement = Date.now();

  const fin = () => Math.min(finInactivite, finAbsolue);
  const reste = () => Math.max(0, Math.ceil((fin() - Date.now()) / 1000));

  // « 1 min 58 s », « 2 min », « 45 s ».
  function duree(s) {
    if (s < 60) return `${s} s`;
    const m = Math.floor(s / 60);
    const r = s % 60;
    return r ? `${m} min ${r} s` : `${m} min`;
  }
  function dire(zone, message) {
    if (!zone) return;
    zone.textContent = '';
    setTimeout(() => { zone.textContent = message; }, 60);
  }

  // ---------------------------------------------------------------- canal entre onglets
  const canal = 'BroadcastChannel' in window ? new BroadcastChannel(CLE) : null;
  function diffuser(message) {
    const m = { ...message, n: Math.random() };
    if (canal) canal.postMessage(m);
    else try { localStorage.setItem(CLE, JSON.stringify(m)); } catch { /* stockage indisponible */ }
  }
  function recevoir(m) {
    if (!m || termine) return;
    if (m.type === 'prolonge') {
      finInactivite = Math.max(finInactivite, Number(m.finInactivite) || 0);
      finAbsolue = Number(m.finAbsolue) || finAbsolue;
      if (dialogue.open && fin() - Date.now() > avertir) fermer();
      planifier();
    } else if (m.type === 'expire') {
      expirer(false);
    }
  }
  if (canal) canal.addEventListener('message', (e) => recevoir(e.data));
  else window.addEventListener('storage', (e) => { if (e.key === CLE && e.newValue) try { recevoir(JSON.parse(e.newValue)); } catch { /* message illisible */ } });

  // ---------------------------------------------------------------- échéances
  function planifier() {
    clearTimeout(minuteurAvertir);
    clearTimeout(minuteurFin);
    if (termine) return;
    const maintenant = Date.now();
    if (!dialogue.open && !(absolueVue && finAbsolue <= finInactivite)) {
      minuteurAvertir = setTimeout(verifierPuisAvertir, Math.max(0, fin() - avertir - maintenant));
    }
    minuteurFin = setTimeout(() => expirer(true), Math.max(0, fin() - maintenant));
  }

  function appliquer(restant, absolu, partager) {
    finInactivite = Date.now() + restant * 1000;
    finAbsolue = Date.now() + absolu * 1000;
    dernierProlongement = Date.now();
    if (partager) diffuser({ type: 'prolonge', finInactivite, finAbsolue });
    planifier();
  }

  async function lireEtat() {
    const r = await fetch(urlEtat, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
    if (!r.ok) throw new Error(String(r.status));
    return r.json();
  }

  // Avant d'avertir : le serveur fait foi (un autre onglet a pu naviguer et prolonger sans script).
  async function verifierPuisAvertir() {
    if (urlEtat) {
      try {
        const d = await lireEtat();
        if (!d.connecte) { expirer(true); return; }
        finInactivite = Date.now() + d.restant * 1000;
        finAbsolue = Date.now() + d.avant_absolue * 1000;
        if (fin() - Date.now() > avertir + 1000) { planifier(); return; }
      } catch { /* réseau absent : l'échéance locale reste la référence */ }
    }
    ouvrir();
  }

  // ---------------------------------------------------------------- dialogue
  function afficherEtat(mode) {
    dialogue.dataset.etat = mode;
    erreur.hidden = true;
    const r = reste();
    if (mode === 'expiree') {
      titre.textContent = 'Session expirée';
      texte.replaceChildren(document.createTextNode('Votre session a pris fin. Votre saisie est gardée sur cet appareil\u00a0: reconnectez-vous pour reprendre là où vous en étiez.'));
      horloge.textContent = '0:00';
      prolonger.hidden = true;
      if (sortir) sortir.hidden = true;
      reconnecter.hidden = false;
      return;
    }
    const fort = document.createElement('strong');
    fort.dataset.minuteurReste = '';
    fort.textContent = duree(r);
    if (mode === 'absolue') {
      titre.textContent = 'Votre session se termine bientôt';
      texte.replaceChildren('Votre session atteint sa durée maximale dans ', fort, ' et ne peut pas être prolongée. Enregistrez votre travail ; vous pourrez vous reconnecter aussitôt.');
      prolonger.textContent = "J'ai compris";
    } else {
      titre.textContent = 'Votre session va expirer';
      texte.replaceChildren('Votre session expire dans ', fort, '. Sans action de votre part, vous serez déconnecté\u00a0; votre saisie en cours sera gardée sur cet appareil.');
      prolonger.textContent = 'Rester connecté';
    }
    prolonger.hidden = false;
    if (sortir) sortir.hidden = false;
    reconnecter.hidden = true;
  }

  function ouvrir() {
    if (termine) return;
    const mode = finAbsolue <= finInactivite ? 'absolue' : 'avertissement';
    afficherEtat(mode);
    // Annonces : la description est lue à l'ouverture, puis toutes les 30 s et une dernière fois à 10 s.
    const r = reste();
    prochaineAnnonce = r > 30 ? Math.floor((r - 1) / 30) * 30 : (r > 10 ? 10 : -1);
    if (!dialogue.open) dialogue.showModal();
    prolonger.focus();
    battre();
    clearInterval(tic);
    tic = setInterval(battre, 1000);
  }

  function battre() {
    const r = reste();
    const m = Math.floor(r / 60);
    horloge.textContent = `${m}:${String(r % 60).padStart(2, '0')}`;
    const fort = texte.querySelector('[data-minuteur-reste]');
    if (fort) fort.textContent = duree(r);
    if (r <= 0) { expirer(true); return; }
    if (prochaineAnnonce > 0 && r <= prochaineAnnonce) {
      dire(annonce, `Votre session expire dans ${duree(r)}.`);
      prochaineAnnonce = prochaineAnnonce > 30 ? prochaineAnnonce - 30 : (prochaineAnnonce === 30 ? 10 : -1);
    }
  }

  function fermer() {
    clearInterval(tic);
    tic = null;
    if (dialogue.open) dialogue.close();
    planifier(); // l'avertissement suivant n'est programmé que dialogue fermé
  }

  async function demanderProlongation(silencieux) {
    if (!urlProlonger) {
      // Catalogue : pas de serveur, la prolongation se fait dans la page.
      appliquer(inactivite / 1000, Math.max(0, Math.ceil((finAbsolue - Date.now()) / 1000)), true);
      return true;
    }
    try {
      const r = await fetch(urlProlonger, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const d = await r.json().catch(() => ({}));
      if (r.status === 401 || d.expiree) { expirer(true); return false; }
      if (!r.ok || !d.ok) throw new Error(String(r.status));
      appliquer(d.restant, d.avant_absolue, true);
      return true;
    } catch {
      if (!silencieux) {
        erreur.textContent = 'La session n\'a pas pu être prolongée : connexion impossible. Vérifiez le réseau puis réessayez.';
        erreur.hidden = false;
      }
      return false;
    }
  }

  form.addEventListener('submit', async (e) => {
    const bouton = e.submitter;
    if (bouton && bouton === sortir) return; // déconnexion : envoi normal vers formaction
    e.preventDefault();
    if (dialogue.dataset.etat === 'absolue') { absolueVue = true; fermer(); return; }
    prolonger.disabled = true;
    const ok = await demanderProlongation(false);
    prolonger.disabled = false;
    if (ok) {
      fermer();
      dire(statut, `Session prolongée : elle expirera après ${duree(Math.round(inactivite / 1000))} sans activité.`);
    }
  });

  // Échap : la personne est là, c'est une réponse. Avertissement : prolongation ; durée maximale : « J'ai compris ».
  dialogue.addEventListener('cancel', (e) => {
    e.preventDefault();
    if (dialogue.dataset.etat === 'expiree') return;
    form.requestSubmit(prolonger);
  });

  // ---------------------------------------------------------------- expiration et brouillons
  function champsGardables(f) {
    return Array.from(f.elements).filter((el) => el.name && !el.disabled && !el.name.startsWith('_') && !SECRET.test(el.name)
      && !['password', 'hidden', 'file', 'submit', 'button', 'reset'].includes(el.type));
  }
  function garderBrouillons() {
    document.querySelectorAll('form[data-minuteur-brouillon][id]').forEach((f) => {
      const valeurs = champsGardables(f).map((el) => [el.name, el.type === 'checkbox' || el.type === 'radio' ? (el.checked ? el.value : null) : el.value]);
      if (valeurs.some(([, v]) => v)) {
        try { sessionStorage.setItem(`${CLE}:${location.pathname}:${f.id}`, JSON.stringify(valeurs)); } catch { /* stockage plein ou refusé */ }
      }
    });
  }
  function remettreBrouillons() {
    document.querySelectorAll('form[data-minuteur-brouillon][id]').forEach((f) => {
      const cle = `${CLE}:${location.pathname}:${f.id}`;
      let valeurs = null;
      try { valeurs = JSON.parse(sessionStorage.getItem(cle) || 'null'); sessionStorage.removeItem(cle); } catch { return; }
      if (!Array.isArray(valeurs)) return;
      const champs = champsGardables(f);
      valeurs.forEach(([nom, valeur], i) => {
        const el = champs.filter((c) => c.name === nom)[valeurs.slice(0, i).filter(([n]) => n === nom).length];
        if (!el) return;
        if (el.type === 'checkbox' || el.type === 'radio') el.checked = valeur !== null;
        else el.value = valeur;
      });
      const note = document.createElement('p');
      note.className = 'minuteur-restaure';
      note.setAttribute('role', 'status');
      note.textContent = 'Votre saisie d\'avant l\'expiration de la session a été remise. Vérifiez-la puis enregistrez.';
      f.prepend(note);
    });
  }

  function expirer(partager) {
    if (termine) return;
    termine = true;
    clearTimeout(minuteurAvertir);
    clearTimeout(minuteurFin);
    clearInterval(tic);
    garderBrouillons();
    if (partager) diffuser({ type: 'expire' });
    afficherEtat('expiree');
    if (!dialogue.open) dialogue.showModal();
    reconnecter.focus();
    dire(annonce, 'Session expirée. Redirection vers la page de connexion.');
    if (urlExpiree) setTimeout(() => window.location.assign(urlExpiree), 1500);
  }

  // Onglet réveillé (ordinateur en veille, onglet en arrière-plan) : les minuteurs ont pu prendre du retard.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden || termine) return;
    if (fin() - Date.now() <= avertir) verifierPuisAvertir();
    else planifier();
  });

  // Option : une frappe ou un clic prolonge en silence, au plus une fois toutes les 5 minutes.
  if (racine.hasAttribute('data-activite')) {
    const activite = () => {
      if (!termine && !dialogue.open && Date.now() - dernierProlongement > 300000) {
        dernierProlongement = Date.now();
        demanderProlongation(true);
      }
    };
    ['keydown', 'pointerdown'].forEach((t) => document.addEventListener(t, activite, { passive: true }));
  }

  // Bouton d'essai (page de démonstration ou de recette) : l'avertissement s'ouvre dans 3 secondes.
  document.addEventListener('click', (e) => {
    if (!e.target.closest || !e.target.closest('[data-minuteur-essai]') || termine) return;
    finInactivite = Date.now() + avertir + 3000;
    planifier();
  });

  remettreBrouillons();
  // Cette page vient d'être servie : la session est fraîche, les autres onglets l'apprennent.
  diffuser({ type: 'prolonge', finInactivite, finAbsolue });
  planifier();
})();
