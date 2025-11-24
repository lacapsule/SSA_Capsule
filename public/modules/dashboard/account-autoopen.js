/*
 * account-autoopen.js
 * Module pour ouvrir automatiquement les modals qui contiennent des erreurs
 * Evite l'utilisation de scripts inline (CSP-friendly)
 */

export function initAccountAutoOpen() {
  if (typeof document === 'undefined') return;

  // Attendre le DOMContentLoaded si nécessaire
  const run = () => {
    // Slight delay to let modal manager initialise
    setTimeout(() => {
      document.querySelectorAll('.modal-error-message').forEach((b) => {
        const dialog = b.closest ? b.closest('dialog') : null;
        if (!dialog) return;
        const id = dialog.id;
        try {
          if (window.modalManager && typeof window.modalManager.open === 'function') {
            window.modalManager.open(id);
          } else if (typeof dialog.showModal === 'function') {
            dialog.showModal();
          } else {
            dialog.setAttribute('open', '');
            dialog.style.display = 'flex';
          }
        } catch (e) {
          // ignore
        }
      });
    }, 10);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
}
