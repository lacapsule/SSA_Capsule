/**
 * Utilitaires pour manipuler le DOM de manière sécurisée
 */

/**
 * Récupère un élément du DOM de manière sécurisée
 * @param {string} selector - Sélecteur CSS
 * @returns {HTMLElement|null}
 */
export function getElement(selector) {
    return document.querySelector(selector);
}

/**
 * Récupère tous les éléments correspondant au sélecteur
 * @param {string} selector - Sélecteur CSS
 * @returns {NodeList}
 */
export function getAllElements(selector) {
    return document.querySelectorAll(selector);
}

/**
 * Ajoute un écouteur d'événement de manière sécurisée
 * @param {HTMLElement|null} element - Élément DOM
 * @param {string} event - Type d'événement
 * @param {Function} handler - Gestionnaire d'événement
 * @returns {boolean} - True si l'écouteur a été ajouté
 */
export function addEventListenerSafe(element, event, handler) {
    if (!element) {
        console.warn(`Élément non trouvé pour l'événement: ${event}`);
        return false;
    }
    element.addEventListener(event, handler);
    return true;
}

/**
 * Crée un élément HTML avec des attributs
 * @param {string} tag - Nom de la balise
 * @param {Object} attributes - Attributs à ajouter
 * @param {string} textContent - Contenu texte (optionnel)
 * @returns {HTMLElement}
 */
export function createElement(tag, attributes = {}, textContent = '') {
    const element = document.createElement(tag);

    Object.entries(attributes).forEach(([key, value]) => {
        if (key === 'class') {
            element.className = value;
        } else {
            element.setAttribute(key, value);
        }
    });

    if (textContent) {
        element.textContent = textContent;
    }

    return element;
}
