// Grand bouton de lecture posé sur l'image d'attente d'une vidéo d'article (revue visuelle du 2026-11-17) : la vidéo se
// lance au clic, ses commandes reviennent ensuite. Sans ce script, la vidéo garde ses commandes habituelles.
function initVideos() {
  document.querySelectorAll('[data-video]').forEach((zone) => {
    if (zone.dataset.pret) return;
    zone.dataset.pret = '1';
    const video = zone.querySelector('video');
    const bouton = zone.querySelector('[data-video-lecture]');
    if (!video || !bouton) return;
    video.removeAttribute('controls');
    bouton.hidden = false;
    bouton.addEventListener('click', () => {
      bouton.hidden = true;
      video.setAttribute('controls', '');
      video.play().catch(() => {});
      video.focus();
    });
  });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initVideos);
else initVideos();
