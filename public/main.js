/**
 * Point d'entrée principal.
 *
 * Chaque module n'est téléchargé et initialisé que sur les pages qui en ont
 * besoin : on teste la présence de son élément déclencheur, et on ne charge
 * le module (import dynamique) que si l'élément existe. Cela évite de parser
 * et d'exécuter tout le JavaScript du site sur chaque page, et supprime les
 * avertissements « élément non trouvé » sur les pages où un module ne sert pas.
 */

// [ sélecteur déclencheur, chargeur ]
const MODULES = [
    ['#menu-toggle', () => import('./modules/nav/mobileMenu.js').then((m) => m.initMobileMenu())],
    ['#togglePassword', () => import('./modules/auth/passwordToggle.js').then((m) => m.initPasswordToggle())],
    ['#forgotPasswordLink', () => import('./modules/auth/passwordForgot.js').then((m) => m.initPasswordForgot())],
    ['#lightbox, .gallery-img', () => import('./modules/gallery/lightbox.js').then((m) => m.initLightbox())],
    ['#download', () => import('./modules/download/fileDownload.js').then((m) => m.initFileDownloader())],
    ['.question', () => import('./modules/dashboard/faq.js').then((m) => m.initFaq())],
    ['#password-modal', () => import('./modules/dashboard/password-modal.js').then((m) => m.initPasswordModal())],
    ['#email-modal', () => import('./modules/dashboard/email-modal.js').then((m) => m.initEmailModal())],
    ['[data-modal-id]', () => import('./modules/modal/universalModal.js').then((m) => m.initModals())],
    ['#article-create-modal', () => import('./modules/articles/articleModal.js').then((m) => m.initArticleModal())],
    ['.dash-components-table', () => import('./modules/dashboard/articles-sort.js').then((m) => m.initArticlesSort())],
    ['.dash-components-table', () => import('./modules/dashboard/users-filter.js').then((m) => m.initUsersFilter())],
    ['#public-calendar-modal', () => import('./modules/home/publicCalendar.js').then((m) => m.initPublicCalendar())],
    ['#home-events-list', () => import('./modules/home/homeEvents.js')],
];

function initApp() {
    for (const [selector, load] of MODULES) {
        if (document.querySelector(selector)) {
            load().catch((error) => console.error('Echec du chargement d\'un module :', error));
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
