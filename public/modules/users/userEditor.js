/**
 * Module de gestion de l'édition d'utilisateurs
 */
import { CSS_CLASSES } from '../constants.js';
import { createElement } from '../utils/dom.js';
import { submitForm } from '../utils/forms.js';

/**
 * Crée un champ éditable
 * @param {string} value - Valeur initiale
 * @returns {HTMLDivElement}
 */
function createEditableField(value) {
    return createElement('div', {
        contenteditable: 'true',
        style: 'min-width: 100px; min-height: 20px; border: 1px solid #ccc; padding: 5px;',
    }, value);
}

/**
 * Crée un sélecteur de rôle
 * @param {string} currentRole - Rôle actuel
 * @returns {HTMLSelectElement}
 */
function createRoleSelector(currentRole) {
    const select = createElement('select');

    const roles = ['employee', 'admin'];
    roles.forEach(role => {
        const option = createElement('option', { value: role }, role);
        select.appendChild(option);
    });

    select.value = currentRole;
    return select;
}

/**
 * Crée les boutons d'action
 * @returns {Object} Objet contenant les boutons
 */
function createActionButtons() {
    return {
        save: createElement('button', { class: 'save-btn' }, 'Enregistrer'),
        cancel: createElement('button', { class: 'cancel-btn' }, 'Annuler'),
        delete: createElement('button', { class: 'suppr-btn' }, 'Supprimer'),
    };
}

/**
 * Restaure l'affichage original de la ligne
 * @param {Object} cells - Cellules de la ligne
 * @param {Object} originalData - Données originales
 */
function restoreOriginalDisplay(cells, originalData) {
    cells.username.textContent = originalData.username;
    cells.email.textContent = originalData.email;
    cells.role.className = `${originalData.role} role`;
    cells.role.innerHTML = `<p>${originalData.role}</p>`;
    cells.action.innerHTML = '<button class="editBtn" type="button" onclick="editLeUser(event)">Gérer</button>';
}

/**
 * Gère la suppression d'un utilisateur
 * @param {string} userId - ID de l'utilisateur
 */
function handleUserDeletion(userId) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ?")) {
        return;
    }

    submitForm('/dashboard/users/delete', {
        action: 'delete',
        'user_ids[]': userId,
    });
}

/**
 * Gère la sauvegarde des modifications
 * @param {string} userId - ID de l'utilisateur
 * @param {Object} cells - Cellules de la ligne
 * @param {HTMLSelectElement} roleSelect - Sélecteur de rôle
 */
function handleUserUpdate(userId, cells, roleSelect) {
    if (!confirm('Enregistrer les modifications ?')) {
        return;
    }

    const newData = {
        action: 'update',
        id: userId,
        username: cells.username.querySelector('div').textContent.trim(),
        email: cells.email.querySelector('div').textContent.trim(),
        role: roleSelect.value,
    };

    submitForm('/dashboard/users/update', newData);
}

/**
 * Active le mode édition pour un utilisateur
 * @param {Event} event - Événement de clic
 */
export function editLeUser(event) {
    console.log("Bouton 'Gérer' cliqué");

    const row = event.target.closest('tr');
    if (!row) return;

    // Récupération des cellules
    const cells = {
        username: row.querySelector('.usernameValue'),
        email: row.querySelector('.emailValue'),
        role: row.querySelector('.admin, .employee'),
        action: row.querySelector('td:last-child'),
        id: row.querySelector('.idValue'),
    };

    // Vérification que tous les éléments existent
    if (!cells.username || !cells.email || !cells.role || !cells.id) {
        console.error('Cellules manquantes dans la ligne');
        return;
    }

    const userId = cells.id.textContent.trim();

    // Sauvegarde des valeurs originales
    const originalData = {
        username: cells.username.textContent.trim(),
        email: cells.email.textContent.trim(),
        role: cells.role.classList.contains(CSS_CLASSES.ADMIN) ? 'admin' : 'employee',
    };

    // Vérifier si déjà en mode édition
    if (cells.username.querySelector('div[contenteditable="true"]')) {
        console.warn('Déjà en mode édition');
        return;
    }

    // Créer les champs éditables
    const usernameField = createEditableField(originalData.username);
    const emailField = createEditableField(originalData.email);
    const roleSelect = createRoleSelector(originalData.role);

    // Remplacer le contenu
    cells.username.innerHTML = '';
    cells.username.appendChild(usernameField);

    cells.email.innerHTML = '';
    cells.email.appendChild(emailField);

    cells.role.innerHTML = '';
    cells.role.appendChild(roleSelect);

    // Créer les boutons d'action
    const buttons = createActionButtons();
    cells.action.innerHTML = '';
    cells.action.appendChild(buttons.save);
    cells.action.appendChild(buttons.cancel);
    cells.action.appendChild(buttons.delete);

    // Gestion des événements
    buttons.delete.addEventListener('click', () => handleUserDeletion(userId));
    buttons.cancel.addEventListener('click', () => restoreOriginalDisplay(cells, originalData));
    buttons.save.addEventListener('click', () => handleUserUpdate(userId, cells, roleSelect));
}

// Rendre la fonction accessible globalement pour onclick
window.editLeUser = editLeUser;
