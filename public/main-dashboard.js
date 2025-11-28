/**
 * Point d'entrée pour le dashboard
 * Charge tous les modules nécessaires pour l'administration
 */

/**
 * Initialise tous les modules du dashboard
 */
async function initDashboardApp() {
    console.log('🚀 Initialisation du dashboard...');

    try {
        // Charger les modules de manière optimisée
        const [
            { initPasswordToggle },
            { initPasswordForgot },
            { initFileDownloader },
            { initUserCheckboxes },
            { initUserModal },
            { initEventManager },
            { initFaq },
            { initPasswordModal },
            { initEmailModal },
            { initModals },
            { initArticleModal },
            { initArticlesSort },
            { initUsersFilter }
        ] = await Promise.all([
            import('./modules/auth/passwordToggle.js'),
            import('./modules/auth/passwordForgot.js'),
            import('./modules/download/fileDownload.js'),
            import('./modules/users/userCheckboxes.js'),
            import('./modules/users/userModal.js'),
            import('./modules/calendar/eventManager.js'),
            import('./modules/dashboard/faq.js'),
            import('./modules/dashboard/password-modal.js'),
            import('./modules/dashboard/email-modal.js'),
            import('./modules/modal/universalModal.js'),
            import('./modules/articles/articleModal.js'),
            import('./modules/dashboard/articles-sort.js'),
            import('./modules/dashboard/users-filter.js')
        ]);

        // Initialiser les modules d'authentification
        initPasswordToggle();
        initPasswordForgot();

        // Modules de téléchargement
        initFileDownloader();

        // Modules de gestion des utilisateurs
        initUserCheckboxes();
        initUserModal();

        // Modules de calendrier
        initEventManager();

        // Dashboard — FAQ
        initFaq();

        // Dashboard — Password Modal
        initPasswordModal();

        // Dashboard — Email Modal
        initEmailModal();

        // Modals universelles
        initModals();

        // Articles client-side sorting
        initArticlesSort();
        
        // Users client-side filter + date sort
        initUsersFilter();
        
        // Articles modal
        initArticleModal();

        console.log('✅ Dashboard initialisé avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
    }
}

// Initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardApp);
} else {
    initDashboardApp();
}

