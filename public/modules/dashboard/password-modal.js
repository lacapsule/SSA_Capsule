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
