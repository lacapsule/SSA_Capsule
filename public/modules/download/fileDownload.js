/**
 * Module de gestion du téléchargement de fichiers
 */
import { DOM_SELECTORS, FILE_CONFIG } from '../constants.js';
import { getElement, addEventListenerSafe, createElement } from '../utils/dom.js';

/**
 * Télécharge un fichier de manière programmatique
 * @param {string} fileUrl - URL du fichier
 * @param {string} filename - Nom du fichier à télécharger
 */
function downloadFile(fileUrl, filename) {
    const link = createElement('a', {
        href: fileUrl,
        download: filename,
    });

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Initialise le gestionnaire de téléchargement
 */
export function initFileDownloader() {
    const downloadLink = getElement(DOM_SELECTORS.DOWNLOAD_LINK);

    if (!downloadLink) {
        console.warn('Lien de téléchargement non trouvé');
        return;
    }

    if (typeof FILE_CONFIG === 'undefined' || !FILE_CONFIG?.CANDIDATURE) {
        console.warn('Configuration de téléchargement absente (FILE_CONFIG)');
        return;
    }

    addEventListenerSafe(downloadLink, 'click', (event) => {
        // Empêche le téléchargement natif (double clic) : on gère via JS
        event?.preventDefault?.();
        downloadFile(
            FILE_CONFIG.CANDIDATURE.url,
            FILE_CONFIG.CANDIDATURE.filename
        );
    });
}
