/* Champ mot de passe : bouton bascule « Afficher le mot de passe » (aria-pressed, libellé fixe). Sans dépendance.
   À copier en public/assets/js/mot-de-passe.js, chargé avec defer. */
(() => {
  'use strict';
  const boutons = () => document.querySelectorAll('[data-mdp]');

  // Bouton montré seulement quand le script tourne : sans JavaScript, le champ reste un champ mot de passe ordinaire.
  // Un champ désactivé garde son bouton désactivé : rien à afficher ni à modifier.
  boutons().forEach((b) => {
    b.hidden = false;
    const champ = document.getElementById(b.dataset.mdp);
    if (champ && champ.disabled) b.disabled = true;
  });

  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-mdp]');
    if (!b) return;
    const champ = document.getElementById(b.dataset.mdp);
    if (!champ) return;
    const montrer = champ.type === 'password';
    champ.type = montrer ? 'text' : 'password';
    // Un seul mécanisme : l'état est porté par aria-pressed, le libellé ne change pas.
    b.setAttribute('aria-pressed', String(montrer));
  });

  // À l'envoi, les champs reviennent masqués : rien ne reste lisible à l'écran ni proposé à l'enregistrement en clair.
  document.addEventListener('submit', (e) => {
    e.target.querySelectorAll('[data-mdp][aria-pressed="true"]').forEach((b) => {
      const champ = document.getElementById(b.dataset.mdp);
      if (champ) champ.type = 'password';
      b.setAttribute('aria-pressed', 'false');
    });
  });
})();

/* Variante .champ-mdp--force : critères cochés en direct, niveau écrit, annonce polie retardée.
   Mêmes règles que source/app/Securite/PasswordPolicy.php : min et max lus dans data-mdp-min et data-mdp-max,
   libellés écrits par le gabarit, COURANTS identique à config/mots-de-passe-courants.txt (test PHP).
   Tout se calcule dans le navigateur : rien n'est envoyé au serveur pendant la saisie. */
(() => {
  'use strict';
  const COURANTS = new Set(['123456789', '1234567890', 'azerty', 'azertyuiop', 'qwerty', 'qwertyuiop', 'motdepasse', 'password', 'passw0rd', 'admin', 'administrateur', 'bonjour', 'bienvenue', 'soleil', 'doudou', 'loulou', 'chouchou', 'chocolat', 'france', 'football', 'iloveyou', 'jetaime', 'princesse', 'dragon', 'monkey', 'sunshine', 'superman', 'batman', 'starwars', 'pokemon', 'utilisateur', 'changeme', 'secret', 'test', 'abcdef', 'abcdefgh', 'coucou', 'salut', 'liberte', 'vacances', 'famille', 'toto']);
  const NIVEAUX = ['à saisir', 'insuffisant', 'correct', 'solide', 'très solide'];
  const DELAI_ANNONCE = 1000;   // annonce une fois la frappe arrêtée, pas à chaque caractère

  const longueur = (v) => [...v].length;
  function tropSimple(bas) {
    const car = [...bas];
    if (new Set(car).size <= 2) return true;
    const codes = car.map((c) => c.codePointAt(0));
    return [1, -1].some((sens) => codes.every((code, i) => {
      if (i === 0) return true;
      const ecart = code - codes[i - 1];
      const boucle = /^\d\d$/.test(car[i] + car[i - 1]) && ecart === -9 * sens;
      return ecart === sens || boucle;
    }));
  }
  function estCourant(v) {
    const bas = v.trim().toLowerCase();
    const coeur = bas.replace(/^[^\p{L}]+|[^\p{L}]+$/gu, '');
    return COURANTS.has(bas) || (coeur !== '' && COURANTS.has(coeur)) || tropSimple(bas);
  }
  function contientIdentifiant(v, identifiant) {
    const id = identifiant.trim().toLowerCase();
    const local = id.includes('@') ? id.slice(0, id.indexOf('@')) : id;
    const bas = v.toLowerCase();
    return [id, local].some((m) => longueur(m) >= 3 && bas.includes(m));
  }
  /** Indication de robustesse (n'entre pas dans la règle) : longueur et variété des caractères. */
  function niveau(v, valide) {
    if (!v) return 0;
    if (!valide) return 1;
    let jeu = 0;
    if (/\p{Ll}/u.test(v)) jeu += 26;
    if (/\p{Lu}/u.test(v)) jeu += 26;
    if (/\p{N}/u.test(v)) jeu += 10;
    if (/[^\p{L}\p{N}]/u.test(v)) jeu += 33;
    if (/[^\x00-\x7f]/.test(v)) jeu += 50;
    const bits = longueur(v) * Math.log2(Math.max(jeu, 10));
    return bits >= 80 ? 4 : bits >= 60 ? 3 : 2;
  }

  document.querySelectorAll('[data-mdp-force]').forEach((bloc) => {
    const champ = bloc.querySelector('input');
    if (!champ || bloc.dataset.mdpForcePret) return;
    bloc.dataset.mdpForcePret = '1';
    const min = Number(bloc.dataset.mdpMin) || 12;
    const max = Number(bloc.dataset.mdpMax) || 128;
    const champId = bloc.dataset.mdpIdentifiant ? document.getElementById(bloc.dataset.mdpIdentifiant) : null;
    const jauge = bloc.querySelector('.mdp-force');
    const texteNiveau = bloc.querySelector('[data-mdp-niveau]');
    const annonce = bloc.querySelector('[data-mdp-annonce]');
    let minuterie = null;
    let dernier = '';
    if (jauge) jauge.hidden = false;

    const maj = () => {
      const v = champ.value;
      const n = longueur(v);
      const tests = {
        longueur: n >= min && n <= max,
        courant: v !== '' && !estCourant(v),
        identifiant: v !== '' && !(champId && champId.value && contientIdentifiant(v, champId.value)),
      };
      const items = [...bloc.querySelectorAll('[data-critere]')];
      items.forEach((li) => {
        const ok = Boolean(tests[li.dataset.critere]);
        li.dataset.ok = String(ok);
        const etat = li.querySelector('[data-etat]');
        if (etat) etat.textContent = ok ? ' : rempli' : ' : à remplir';
      });
      const remplis = items.filter((li) => li.dataset.ok === 'true').length;
      const valide = remplis === items.length;
      const rang = niveau(v, valide);
      if (jauge) jauge.dataset.niveau = String(rang);
      if (texteNiveau) texteNiveau.textContent = n > max ? `trop long (${max} caractères au plus)` : NIVEAUX[rang];
      // Annonce polie, après une pause de frappe, seulement si elle change.
      clearTimeout(minuterie);
      if (!annonce || !v) return;
      minuterie = setTimeout(() => {
        const phrase = `Niveau ${texteNiveau ? texteNiveau.textContent : NIVEAUX[rang]}. ${remplis} critère${remplis > 1 ? 's' : ''} rempli${remplis > 1 ? 's' : ''} sur ${items.length}.`;
        if (phrase !== dernier) { dernier = phrase; annonce.textContent = phrase; }
      }, DELAI_ANNONCE);
    };
    champ.addEventListener('input', maj);
    if (champId) champId.addEventListener('input', maj);
    maj();
  });
})();
