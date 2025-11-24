/**
 * Module de gestion de la modal de changement de mot de passe (dashboard/account)
 * Utilise le pattern des autres modules : export d'une fonction init...
 */
import { getElement, addEventListenerSafe } from '../utils/dom.js';

/**
 * Initialise la modal de changement de mot de passe
 */
export function initPasswordModal() {
    const passwordModal = getElement('#password-modal');
    const openButton = getElement('#btn-open-password-modal');

    if (!passwordModal) {
        // Rien à faire si la modal n'existe pas
        return;
    }

    // Ouvrir la modal au clic du bouton
    addEventListenerSafe(openButton, 'click', () => openPasswordModal(passwordModal));

    // Fermer avec le bouton X
    const closeBtn = passwordModal.querySelector('.modal-close-btn');
    addEventListenerSafe(closeBtn, 'click', () => closePasswordModal(passwordModal));

    // Fermer avec le bouton Annuler
    const cancelBtn = passwordModal.querySelector('.modal-cancel-btn');
    addEventListenerSafe(cancelBtn, 'click', () => closePasswordModal(passwordModal));

    // Fermer en cliquant sur l'overlay
    const overlay = passwordModal.querySelector('.modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closePasswordModal(passwordModal);
            }
        });
    }

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && passwordModal?.hasAttribute('open')) {
            closePasswordModal(passwordModal);
        }
    });

    // Générer les initiales automatiquement
    generateProfileInitials();
    // Client-side validation for password form
    initPasswordFormValidation(passwordModal);

}

/**
 * Ouvre la modal de changement de mot de passe
 */
function openPasswordModal(modal) {
    if (!modal) return;
    if (modal.showModal) {
        modal.showModal();
    } else {
        modal.setAttribute('open', '');
        modal.style.display = 'flex';
    }
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const firstInput = modal.querySelector('input[type="password"]');
        if (firstInput) {
            firstInput.focus();
        }
    }, 100);
}

/**
 * Ferme la modal de changement de mot de passe
 */
function closePasswordModal(modal) {
    if (!modal) return;
    if (modal.close) {
        modal.close();
    } else {
        modal.removeAttribute('open');
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
    const form = modal.querySelector('#password-form');
    if (form) {
        form.reset();
    }
}

/**
 * Génère les initiales du profil à partir du nom
 */
function generateProfileInitials() {
    const nameElement = getElement('.profile-name');
    const avatarElement = getElement('.avatar-initials');

    if (nameElement && avatarElement) {
        const fullName = nameElement.textContent.trim();
        if (fullName) {
            const initials = fullName
                .split(' ')
                .map((word) => word.charAt(0).toUpperCase())
                .join('')
                .slice(0, 2);

            avatarElement.textContent = initials || 'U';
        }
    }
}

/**
 * Ajoute la validation côté client sur le formulaire de changement de mot de passe
 * - vérifie la longueur minimale
 * - vérifie la correspondance nouveau/confirmation
 */
function initPasswordFormValidation(modal) {
    if (!modal) return;
    const form = modal.querySelector('#password-form');
    if (!form) return;

    const oldInput = form.querySelector('#old_password');
    const newInput = form.querySelector('#new_password');
    const confirmInput = form.querySelector('#confirm_new_password');

    const MIN_LEN = parseInt(newInput?.getAttribute('minlength') || '8', 10);

    function clearClientErrors() {
        form.querySelectorAll('.client-error').forEach(n => n.remove());
    }

    function showFieldError(input, message) {
        // Remove previous
        const next = input.nextElementSibling;
        if (next && next.classList && next.classList.contains('client-error')) {
            next.remove();
        }
        const p = document.createElement('p');
        p.className = 'field-error client-error';
        p.textContent = message;
        input.insertAdjacentElement('afterend', p);
    }

    function validate() {
        clearClientErrors();
        const errors = {};
        const oldVal = oldInput?.value.trim() || '';
        const newVal = newInput?.value || '';
        const confVal = confirmInput?.value || '';

        if (oldVal === '') {
            errors.old = 'Veuillez entrer votre mot de passe actuel.';
        }
        if (newVal.length < MIN_LEN) {
            errors.new = `Le mot de passe doit contenir au moins ${MIN_LEN} caractères.`;
        }
        if (newVal !== confVal) {
            errors.confirm = 'Les nouveaux mots de passe ne correspondent pas.';
        }

        if (errors.old && oldInput) showFieldError(oldInput, errors.old);
        if (errors.new && newInput) showFieldError(newInput, errors.new);
        if (errors.confirm && confirmInput) showFieldError(confirmInput, errors.confirm);

        return Object.keys(errors).length === 0;
    }

    // Prevent submit when invalid
    form.addEventListener('submit', (e) => {
        if (!validate()) {
            e.preventDefault();
            // focus first invalid
            const firstErr = form.querySelector('.client-error');
            if (firstErr && firstErr.previousElementSibling) {
                firstErr.previousElementSibling.focus();
            }
        }
    });

    // Clear field-specific errors on input
    [oldInput, newInput, confirmInput].forEach((inp) => {
        if (!inp) return;
        inp.addEventListener('input', () => {
            // remove only the client-side error for this field
            const next = inp.nextElementSibling;
            if (next && next.classList && next.classList.contains('client-error')) {
                next.remove();
            }
        });
    });
}
