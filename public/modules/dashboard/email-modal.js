/**
 * Module de gestion de la modal de changement d'email (dashboard/account)
 * Utilise le pattern des autres modules : export d'une fonction init...
 */
import { getElement, addEventListenerSafe } from '../utils/dom.js';

/**
 * Initialise la modal de changement d'email
 */
export function initEmailModal() {
    const emailModal = getElement('#email-modal');
    const openButton = getElement('#btn-open-email-modal');

    if (!emailModal) {
        // Rien à faire si la modal n'existe pas
        return;
    }

    // Ouvrir la modal au clic du bouton
    addEventListenerSafe(openButton, 'click', () => openEmailModal(emailModal));

    // Fermer avec le bouton X
    const closeBtn = emailModal.querySelector('.modal-close-btn');
    addEventListenerSafe(closeBtn, 'click', () => closeEmailModal(emailModal));

    // Fermer avec le bouton Annuler
    const cancelBtn = emailModal.querySelector('.modal-cancel-btn');
    addEventListenerSafe(cancelBtn, 'click', () => closeEmailModal(emailModal));

    // Fermer en cliquant sur l'overlay
    const overlay = emailModal.querySelector('.modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeEmailModal(emailModal);
            }
        });
    }

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && emailModal?.hasAttribute('open')) {
            closeEmailModal(emailModal);
        }
    });

    // Client-side validation for email form
    initEmailFormValidation(emailModal);
}

/**
 * Ouvre la modal de changement d'email
 */
function openEmailModal(modal) {
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
 * Ferme la modal de changement d'email
 */
function closeEmailModal(modal) {
    if (!modal) return;
    if (modal.close) {
        modal.close();
    } else {
        modal.removeAttribute('open');
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
    const form = modal.querySelector('#email-form');
    if (form) {
        form.reset();
    }
}

/**
 * Validation côté client pour la modal d'email
 */
function initEmailFormValidation(modal) {
    if (!modal) return;
    const form = modal.querySelector('#email-form');
    if (!form) return;

    const passwordInput = form.querySelector('#password_for_email');
    const newEmailInput = form.querySelector('#new_email');
    const confirmEmailInput = form.querySelector('#confirm_email');

    function clearClientErrors() {
        form.querySelectorAll('.client-error').forEach(n => n.remove());
    }

    function showFieldError(input, message) {
        const next = input.nextElementSibling;
        if (next && next.classList && next.classList.contains('client-error')) {
            next.remove();
        }
        const p = document.createElement('p');
        p.className = 'field-error client-error';
        p.textContent = message;
        input.insertAdjacentElement('afterend', p);
    }

    function validateEmailFormat(email) {
        // basic validation, HTML5 will also validate but we provide JS feedback
        try {
            return String(email).trim() !== '' && /@/.test(email) && /\./.test(email);
        } catch (e) {
            return false;
        }
    }

    function validate() {
        clearClientErrors();
        const errors = {};

        const pwd = passwordInput?.value.trim() || '';
        const e1 = newEmailInput?.value.trim() || '';
        const e2 = confirmEmailInput?.value.trim() || '';

        if (pwd === '') {
            errors.pwd = 'Veuillez entrer votre mot de passe.';
        }
        if (!validateEmailFormat(e1)) {
            errors.new = 'Adresse email invalide.';
        }
        if (e1 !== e2) {
            errors.confirm = 'Les adresses email ne correspondent pas.';
        }

        if (errors.pwd && passwordInput) showFieldError(passwordInput, errors.pwd);
        if (errors.new && newEmailInput) showFieldError(newEmailInput, errors.new);
        if (errors.confirm && confirmEmailInput) showFieldError(confirmEmailInput, errors.confirm);

        return Object.keys(errors).length === 0;
    }

    form.addEventListener('submit', (ev) => {
        if (!validate()) {
            ev.preventDefault();
            const firstErr = form.querySelector('.client-error');
            if (firstErr && firstErr.previousElementSibling) {
                firstErr.previousElementSibling.focus();
            }
        }
    });

    [passwordInput, newEmailInput, confirmEmailInput].forEach(inp => {
        if (!inp) return;
        inp.addEventListener('input', () => {
            const next = inp.nextElementSibling;
            if (next && next.classList && next.classList.contains('client-error')) next.remove();
        });
    });
}
