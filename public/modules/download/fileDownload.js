/**
 * Module de gestion du téléchargement de fichiers
 */
import { DOM_SELECTORS } from '../constants.js';
import { getElement, addEventListenerSafe, createElement } from '../../utils/dom.js';

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

    addEventListenerSafe(downloadLink, 'click', () => {
        downloadFile(
            FILE_CONFIG.CANDIDATURE.url,
            FILE_CONFIG.CANDIDATURE.filename
        );
    });
}
