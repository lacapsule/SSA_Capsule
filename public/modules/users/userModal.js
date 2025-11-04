/**
 * Module de gestion de la modal de création d'utilisateur
 */
import { DOM_SELECTORS, CSS_CLASSES } from '../constants.js';
import { getElement, addEventListenerSafe } from '../../utils/dom.js';

/**
 * Ferme la popup si clic sur le fond
 * @param {HTMLElement} popup - Élément popup
 * @param {Event} e - Événement de clic
 */
function handlePopupClick(popup, e) {
    if (e.target === popup) {
        popup.classList.add(CSS_CLASSES.HIDDEN);
    }
}

/**
 * Initialise la modal de création d'utilisateur
 */
export function initUserModal() {
    const createUserBtn = getElement(DOM_SELECTORS.CREATE_USER_BTN);

    if (!createUserBtn) {
        console.warn('Bouton de création d\'utilisateur non trouvé');
        return;
    }

    addEventListenerSafe(createUserBtn, 'click', () => {
        const popup = getElement(DOM_SELECTORS.USER_POPUP);

        if (!popup) {
            console.warn('Popup non trouvée');
            return;
        }

        popup.classList.remove(CSS_CLASSES.HIDDEN);

        // Écouter les clics sur le fond pour fermer
        popup.addEventListener('click', (e) => handlePopupClick(popup, e));
    });
}
