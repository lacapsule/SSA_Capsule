/**
 * Module de gestion de la visibilité du mot de passe
 */
import { DOM_SELECTORS } from '../constants.js';
import { getElement, addEventListenerSafe } from '../../utils/dom.js';

/**
 * Active le toggle de visibilité du mot de passe
 */
export function initPasswordToggle() {
    const toggleButton = getElement(DOM_SELECTORS.PASSWORD_TOGGLE);
    const passwordInput = getElement(DOM_SELECTORS.PASSWORD_INPUT);

    if (!toggleButton || !passwordInput) {
        console.warn('Éléments de toggle password non trouvés');
        return;
    }

    addEventListenerSafe(toggleButton, 'click', () => {
        togglePasswordVisibility(passwordInput, toggleButton);
    });
}

/**
 * Bascule la visibilité du mot de passe
 * @param {HTMLInputElement} input - Champ de mot de passe
 * @param {HTMLElement} button - Bouton de toggle
 */
function togglePasswordVisibility(input, button) {
    const isPasswordVisible = input.type === 'text';
    input.type = isPasswordVisible ? 'password' : 'text';
    button.style.opacity = isPasswordVisible ? '0.6' : '1';
}
