/**
 * Module de gestion des checkboxes utilisateurs
 */
import { DOM_SELECTORS } from '../constants.js';
import { getElement, getAllElements } from '../utils/dom.js';

/**
 * Met à jour l'état du bouton de suppression
 * @param {NodeList} checkboxes - Liste des checkboxes
 * @param {HTMLButtonElement} deleteBtn - Bouton de suppression
 */
function toggleDeleteButton(checkboxes, deleteBtn) {
    const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
    deleteBtn.disabled = !isAnyChecked;
}

/**
 * Initialise la gestion des checkboxes utilisateurs
 */
export function initUserCheckboxes() {
    const checkboxes = getAllElements(DOM_SELECTORS.USER_CHECKBOXES);
    const deleteBtn = getElement(DOM_SELECTORS.DELETE_USER_BTN);

    if (!checkboxes.length || !deleteBtn) {
        console.warn('Éléments de checkboxes utilisateurs non trouvés');
        return;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            toggleDeleteButton(checkboxes, deleteBtn);
        });
    });

    // État initial
    toggleDeleteButton(checkboxes, deleteBtn);
}
