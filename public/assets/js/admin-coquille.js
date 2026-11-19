/* Coquille d'administration : tiroir de navigation sous 64 em (Échap, voile, focus rendu), menu replié en icônes
   au-dessus (préférence gardée dans le navigateur), menus details[data-adm-menu] (un seul ouvert, Échap, clic à côté,
   sortie au clavier), raccourci « / » vers la recherche, succès éphémère (window.admEphemere, seule
   implémentation des 5 s de l'administration) et alertes .adm-alerte. Sans JavaScript : la navigation suit le contenu sur
   téléphone, les menus details s'ouvrent seuls. À copier en public/assets/js/admin-coquille.js (defer). */
(() => {
  'use strict';
  const CLE_REPLIE = 'adm-menu-replie';
  const ordinateur = window.matchMedia('(min-width: 64em)');
  const lirePref = () => { try { return localStorage.getItem(CLE_REPLIE); } catch { return null; } };
  const ecrirePref = (v) => {
    try { localStorage.setItem(CLE_REPLIE, v ? '1' : '0'); } catch { /* stockage indisponible */ }
    // Cookie lu par le gabarit PHP : menu rendu replié dès le premier affichage, sans saut.
    document.cookie = 'adm_menu_replie=' + (v ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
  };

  document.querySelectorAll('[data-adm-coquille]:not([data-pret])').forEach((coquille) => {
    coquille.setAttribute('data-pret', '');
    const barre = coquille.querySelector('[data-adm-barre]');
    const colonne = coquille.querySelector('[data-adm-colonne]');
    const voile = coquille.querySelector('[data-adm-voile]');
    let declencheur = null;

    // Tiroir (téléphone et tablette)
    const ouvrir = (bouton) => {
      if (!barre) return;
      declencheur = bouton;
      coquille.setAttribute('data-tiroir-ouvert', '');
      coquille.querySelectorAll('[data-adm-ouvrir-tiroir]').forEach((b) => b.setAttribute('aria-expanded', 'true'));
      if (colonne) colonne.inert = true;
      const cible = barre.querySelector('[aria-current]') || barre.querySelector('a, button');
      if (cible) cible.focus();
    };
    const fermer = (rendreFocus) => {
      if (!coquille.hasAttribute('data-tiroir-ouvert')) return;
      coquille.removeAttribute('data-tiroir-ouvert');
      coquille.querySelectorAll('[data-adm-ouvrir-tiroir]').forEach((b) => b.setAttribute('aria-expanded', 'false'));
      if (colonne) colonne.inert = false;
      if (rendreFocus && declencheur) declencheur.focus();
    };
    coquille.querySelectorAll('[data-adm-ouvrir-tiroir]').forEach((b) => { b.hidden = false; });
    coquille.addEventListener('click', (e) => {
      const ouvrant = e.target.closest('[data-adm-ouvrir-tiroir]');
      if (ouvrant) { ouvrir(ouvrant); return; }
      if (e.target.closest('[data-adm-fermer-tiroir]') || e.target === voile) fermer(true);
    });
    coquille.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && coquille.hasAttribute('data-tiroir-ouvert')) { e.stopPropagation(); fermer(true); }
    });
    ordinateur.addEventListener('change', () => fermer(false));

    // Menu replié en icônes (ordinateur)
    const replier = coquille.querySelector('[data-adm-replier]');
    if (replier) {
      const appliquer = (v) => {
        coquille.toggleAttribute('data-replie', v);
        replier.setAttribute('aria-pressed', String(v));
      };
      const pref = lirePref();
      if (pref !== null) appliquer(pref === '1');
      replier.addEventListener('click', () => {
        const v = !coquille.hasAttribute('data-replie');
        appliquer(v);
        ecrirePref(v);
      });
    }
  });

  // Menus details : un seul ouvert à la fois, fermés par Échap (focus rendu), un clic à côté ou une tabulation qui sort.
  const ouverts = () => document.querySelectorAll('details[data-adm-menu][open]');
  document.addEventListener('toggle', (e) => {
    const d = e.target;
    if (!(d instanceof HTMLDetailsElement) || !d.matches('[data-adm-menu]') || !d.open) return;
    ouverts().forEach((autre) => { if (autre !== d && !autre.contains(d)) autre.open = false; });
  }, true);
  document.addEventListener('click', (e) => {
    ouverts().forEach((d) => { if (!d.contains(e.target)) d.open = false; });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      ouverts().forEach((d) => {
        const dedans = d.contains(document.activeElement);
        d.open = false;
        if (dedans) d.querySelector('summary').focus();
      });
      return;
    }
    // « / » place le focus dans la recherche visible, hors d'un champ de saisie.
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    const actif = document.activeElement;
    if (actif && (actif.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(actif.tagName))) return;
    const champ = [...document.querySelectorAll('[data-adm-recherche]')].find((c) => c.offsetParent !== null);
    if (champ) { e.preventDefault(); champ.focus(); }
  });
  document.addEventListener('focusout', (e) => {
    const d = e.target.closest && e.target.closest('details[data-adm-menu][open]');
    if (d && e.relatedTarget && !d.contains(e.relatedTarget)) d.open = false;
  });

  // Succès éphémère, une seule implémentation pour toute l'administration (règle commune des messages,
  // composants/alertes) : window.admEphemere(noeud, retirer) appelle retirer() après 5 s, décompte suspendu au survol,
  // au focus dans le nœud et onglet caché, repris ensuite pour le temps restant. Renvoie une fonction d'annulation.
  // Les sections (admin-fichiers, admin-inscriptions, admin-moderation…) l'appellent pour leurs lignes « Enregistré ».
  const DUREE_SUCCES = 5000;
  const ephemere = (noeud, retirer) => {
    let reste = DUREE_SUCCES;
    let debut = 0;
    let minuteur = null;
    let fini = false;
    const lancer = () => {
      if (fini || minuteur || reste <= 0 || noeud.hidden || document.hidden || !noeud.isConnected) return;
      if (noeud.matches(':hover') || noeud.contains(document.activeElement)) return;
      debut = Date.now();
      minuteur = setTimeout(() => { minuteur = null; reste = 0; arreter(); retirer(); }, reste);
    };
    const suspendre = () => { if (!minuteur) return; clearTimeout(minuteur); minuteur = null; reste -= Date.now() - debut; };
    const sortie = (e) => { if (!noeud.contains(e.relatedTarget)) setTimeout(lancer, 0); };
    const visibilite = () => (document.hidden ? suspendre() : lancer());
    const arreter = () => {
      fini = true;
      suspendre();
      noeud.removeEventListener('mouseenter', suspendre);
      noeud.removeEventListener('mouseleave', lancer);
      noeud.removeEventListener('focusin', suspendre);
      noeud.removeEventListener('focusout', sortie);
      document.removeEventListener('visibilitychange', visibilite);
    };
    noeud.addEventListener('mouseenter', suspendre);
    noeud.addEventListener('mouseleave', lancer);
    noeud.addEventListener('focusin', suspendre);
    noeud.addEventListener('focusout', sortie);
    document.addEventListener('visibilitychange', visibilite);
    lancer();
    return arreter;
  };
  window.admEphemere = ephemere;

  // Alertes .adm-alerte : seul le succès disparaît seul (admEphemere) ; info, attention et erreur restent jusqu'à
  // fermeture. data-adm-garder sur un succès : il reste. Bouton [data-adm-alerte-fermer] posé avec hidden, affiché ici.
  // window.admAlerteEphemere(racine) arme les alertes d'un fragment inséré par un script.
  const reduit = window.matchMedia('(prefers-reduced-motion: reduce)');
  const fermerAlerte = (alerte, parClic) => {
    if (alerte.hidden) return;
    const avaitFocus = alerte.contains(document.activeElement);
    const fin = () => {
      alerte.hidden = true;
      alerte.classList.remove('adm-alerte--sortie');
      if (parClic || avaitFocus) {
        const cible = document.getElementById('contenu') || document.querySelector('main');
        if (cible) { if (!cible.hasAttribute('tabindex')) cible.setAttribute('tabindex', '-1'); cible.focus({ preventScroll: true }); }
      }
    };
    if (parClic || reduit.matches) { fin(); return; }
    alerte.classList.add('adm-alerte--sortie');
    setTimeout(fin, 160);
  };
  const armerAlerte = (alerte) => {
    alerte.querySelectorAll('[data-adm-alerte-fermer][hidden]').forEach((b) => { b.hidden = false; });
    if (alerte.dataset.admArmee !== undefined || !alerte.classList.contains('adm-alerte--succes') || alerte.hasAttribute('data-adm-garder')) return;
    alerte.dataset.admArmee = '';
    ephemere(alerte, () => fermerAlerte(alerte, false));
  };
  window.admAlerteEphemere = (racine) => {
    const r = racine || document;
    (r.matches && r.matches('.adm-alerte') ? [r] : [...r.querySelectorAll('.adm-alerte')]).forEach(armerAlerte);
  };
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-adm-alerte-fermer]');
    const alerte = b && b.closest('.adm-alerte');
    if (alerte) fermerAlerte(alerte, true);
  });
  window.admAlerteEphemere(document);
})();
