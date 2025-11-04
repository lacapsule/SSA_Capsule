/**
 * Module de gestion de la visibilité du mot de passe
 */
import { DOM_SELECTORS } from '../constants.js';
import { getElement, addEventListenerSafe } from '../utils/dom.js';

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

// Script Password oublié, phrase contact admin
document.addEventListener('DOMContentLoaded', function () {

    const forgotLink = document.getElementById('forgotPasswordLink');
    const messageElement = document.getElementById('adminContactMessage');

    // Vérifie que les éléments existent
    if (forgotLink && messageElement) {

        // Ajoute un écouteur d'événement sur le clic
        forgotLink.addEventListener('click', function (event) {

            // Empêche le lien (href="#") de recharger la page ou de sauter
            event.preventDefault();

            // Définit le texte et affiche l'élément
            messageElement.textContent = "Veuillez contacter l'administrateur du site.";
            messageElement.style.display = 'block';
        });
    }
});