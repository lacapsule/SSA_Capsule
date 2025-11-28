/**
 * Point d'entrée pour le site public
 * Charge uniquement les modules nécessaires pour les pages publiques
 */

/**
 * Initialise les modules publics de manière asynchrone
 */
async function initPublicApp() {
    console.log('🚀 Initialisation de l\'application publique...');

    try {
        // Vérifier si la galerie existe avant de charger lightbox
        const galleryImages = document.querySelectorAll('.gallery-img');
        if (galleryImages.length > 0) {
            const { initLightbox } = await import('./modules/gallery/lightbox.js');
            initLightbox();
        }

        // Modules non-critiques chargés en lazy loading
        // Utiliser requestIdleCallback ou setTimeout pour différer le chargement
        if (window.requestIdleCallback) {
            requestIdleCallback(() => {
                loadNonCriticalModules();
            }, { timeout: 2000 });
        } else {
            setTimeout(loadNonCriticalModules, 100);
        }
        
        console.log('✅ Application publique initialisée');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
    }
}

/**
 * Charge les modules non-critiques de manière asynchrone
 */
async function loadNonCriticalModules() {
    try {
        // Module de téléchargement (seulement si nécessaire)
        const downloadLink = document.getElementById('download');
        if (downloadLink) {
            const { initFileDownloader } = await import('./modules/download/fileDownload.js');
            initFileDownloader();
        }

        // Calendrier public (seulement si présent sur la page)
        const calendarModal = document.getElementById('public-calendar-modal');
        if (calendarModal) {
            const { initPublicCalendar } = await import('./modules/home/publicCalendar.js');
            initPublicCalendar();
        }

        // Carousel d'articles (seulement si présent)
        const articleCarousel = document.querySelector('[data-article-carousel]');
        if (articleCarousel) {
            await import('./modules/article/carousel.js');
        }
    } catch (error) {
        console.warn('⚠️ Erreur lors du chargement des modules non-critiques:', error);
    }
}

// Polyfill pour requestIdleCallback (si nécessaire)
if (!window.requestIdleCallback) {
    window.requestIdleCallback = function(callback, options) {
        const timeout = options?.timeout || 0;
        const start = Date.now();
        return setTimeout(() => {
            callback({
                didTimeout: false,
                timeRemaining: () => Math.max(0, 50 - (Date.now() - start))
            });
        }, timeout);
    };
    
    window.cancelIdleCallback = function(id) {
        clearTimeout(id);
    };
}

// Initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicApp);
} else {
    initPublicApp();
}

