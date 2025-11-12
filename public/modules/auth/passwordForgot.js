/**
 * Module de gestion de la visibilité du mot de passe
 */
import { DOM_SELECTORS } from '../constants.js';
import { getElement, addEventListenerSafe } from '../utils/dom.js';


// Script Password oublié, phrase contact admin
export function initPasswordForgot() {

    const forgotLink = getElement(DOM_SELECTORS.FORGOT_PASSWORD_LINK);
    const messageElement = getElement(DOM_SELECTORS.ADMIN_CONTACT_MESSAGE);

    // Vérifie que les éléments existent
    if (forgotLink && messageElement) {

        // Ajoute un écouteur d'événement sur le clic
        addEventListenerSafe(forgotLink, 'click', (event) => {

            // Empêche le lien (href="#") de recharger la page ou de sauter
            event.preventDefault();

            // Définit le texte et affiche l'élément
            messageElement.textContent = "Veuillez contacter l'administrateur du site.";
            messageElement.style.display = 'block';
        });
    }
}
