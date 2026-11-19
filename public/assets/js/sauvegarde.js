// Sauvegardes (modules/sauvegarde-chiffree) : confirmation de restauration dans un dialog modal.
// <a href="/admin/sauvegardes/restaurer/?fichier=nom" data-svc-restaurer="nom" data-svc-libelle="date, type"> :
//   sans JavaScript, le lien mène à la page de confirmation (même formulaire POST) ; avec, il ouvre #svc-dlg.
// Le dialog reçoit le nom du fichier (champ caché [data-svc-fichier]) et le libellé ([data-svc-objet], en texte),
// le bouton [data-svc-valider] reste désactivé tant que [data-svc-saisie] ne vaut pas RESTAURER.
// Focus initial dans le champ (autofocus), Échap natif, focus rendu au lien à la fermeture.
(function () {
  'use strict';
  var racine = document.documentElement;
  if (racine.hasAttribute('data-svc-init')) return;
  racine.setAttribute('data-svc-init', '');

  var MOT = 'RESTAURER';
  var dialogue = document.getElementById('svc-dlg');
  if (!dialogue || typeof dialogue.showModal !== 'function') return; // repli : page de confirmation
  var saisie = dialogue.querySelector('[data-svc-saisie]');
  var valider = dialogue.querySelector('[data-svc-valider]');
  var fichier = dialogue.querySelector('[data-svc-fichier]');
  var objet = dialogue.querySelector('[data-svc-objet]');
  var origine = null;

  function controler() {
    if (valider && saisie) valider.disabled = saisie.value.trim() !== MOT;
  }

  document.addEventListener('click', function (ev) {
    var cible = ev.target;
    var lien = cible.closest && cible.closest('[data-svc-restaurer]');
    if (lien) {
      ev.preventDefault();
      origine = lien;
      if (fichier) fichier.value = lien.getAttribute('data-svc-restaurer') || '';
      if (objet) objet.textContent = lien.getAttribute('data-svc-libelle') || '';
      if (saisie) saisie.value = '';
      if (valider) valider.removeAttribute('aria-busy');
      controler();
      dialogue.showModal();
      return;
    }
    if (cible.closest && cible.closest('[data-svc-fermer]') && dialogue.open) dialogue.close('');
  });

  if (saisie) saisie.addEventListener('input', controler);

  dialogue.addEventListener('close', function () {
    if (origine && origine.isConnected) origine.focus();
    origine = null;
  });

  // Envoi : bouton occupé, double clic sans effet (le serveur revérifie le mot et le jeton).
  dialogue.addEventListener('submit', function (ev) {
    if (!valider) return;
    if (valider.getAttribute('aria-busy') === 'true' || valider.disabled) { ev.preventDefault(); return; }
    valider.setAttribute('aria-busy', 'true');
  });
})();
