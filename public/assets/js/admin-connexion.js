// Écran de connexion du back-office (sections/admin-connexion) : état d'envoi du bouton, double envoi bloqué.
// Chargé avec defer, avec mot-de-passe.js (composants/champ-mot-de-passe) et saisie-code.js (composants/saisie-code).
(function () {
  'use strict';
  var racine = document.documentElement;
  if (racine.hasAttribute('data-cnx-pret')) return;
  racine.setAttribute('data-cnx-pret', '');

  function mettreEnAttente(bouton, statut) {
    var libelle = bouton.querySelector('[data-cnx-libelle]');
    if (libelle && !bouton.dataset.cnxRepos) bouton.dataset.cnxRepos = libelle.textContent;
    bouton.setAttribute('aria-busy', 'true');
    bouton.setAttribute('aria-disabled', 'true');
    if (libelle && bouton.dataset.cnxEnvoi) libelle.textContent = bouton.dataset.cnxEnvoi;
    if (statut && bouton.dataset.cnxEnvoi) statut.textContent = bouton.dataset.cnxEnvoi;
  }

  function remettre(form) {
    var bouton = form.querySelector('[data-cnx-envoi]');
    var statut = form.querySelector('[data-cnx-statut]');
    if (!bouton || !bouton.dataset.cnxRepos) return;
    var libelle = bouton.querySelector('[data-cnx-libelle]');
    bouton.removeAttribute('aria-busy');
    bouton.removeAttribute('aria-disabled');
    if (libelle) libelle.textContent = bouton.dataset.cnxRepos;
    if (statut) statut.textContent = '';
  }

  // L'événement submit n'arrive qu'après la validation native : un formulaire incomplet ne passe pas en attente.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('[data-cnx-form]')) return;
    var bouton = form.querySelector('[data-cnx-envoi]');
    if (!bouton) return;
    if (bouton.getAttribute('aria-busy') === 'true') { e.preventDefault(); return; }
    mettreEnAttente(bouton, form.querySelector('[data-cnx-statut]'));
  });

  // Retour arrière depuis la page suivante (cache du navigateur) : bouton de nouveau utilisable.
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) document.querySelectorAll('[data-cnx-form]').forEach(remettre);
  });

})();
