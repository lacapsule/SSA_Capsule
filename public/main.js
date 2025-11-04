/**
 * Point d'entrée principal de l'application
 * Initialise tous les modules nécessaires
 */

// Import des modules
import { initPasswordToggle } from './modules/auth/passwordToggle.js';
import { initLightbox } from './modules/gallery/lightbox.js';
import { initFileDownloader } from './modules/download/fileDownload.js';
import { initUserCheckboxes } from './modules/users/userCheckboxes.js';
import { initUserModal } from './modules/users/userModal.js';
import { initEventManager } from './modules/calendar/eventManager.js';

/**
 * Initialise tous les modules de l'application
 */
function initApp() {
    console.log('🚀 Initialisation de l\'application...');

    try {
        // Modules d'authentification
        initPasswordToggle();

        // Modules de galerie
        initLightbox();

        // Modules de téléchargement
        initFileDownloader();

        // Modules de gestion des utilisateurs
        initUserCheckboxes();
        initUserModal();

        // Modules de calendrier
        initEventManager();

        console.log('✅ Application initialisée avec succès');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
    }
}

// Initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
