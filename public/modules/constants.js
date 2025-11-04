/**
 * Configuration globale de l'application
 * Contient tous les sélecteurs DOM et constantes
 */
export const DOM_SELECTORS = {
    // Auth
    PASSWORD_TOGGLE: '#togglePassword',
    PASSWORD_INPUT: '#password',

    // Gallery
    GALLERY_IMAGES: '.gallery-img',
    LIGHTBOX: '#lightbox',
    LIGHTBOX_IMAGE: '.lightbox-image',
    LIGHTBOX_CLOSE: '.close',
    LIGHTBOX_PREV: '.prev',
    LIGHTBOX_NEXT: '.next',

    // Download
    DOWNLOAD_LINK: '#download',

    // Users
    USER_CHECKBOXES: '.user-checkbox',
    DELETE_USER_BTN: '.deleteUser',
    CREATE_USER_BTN: '#createUserBtn',
    USER_POPUP: '.popup',

    // Calendar
    MODAL_CREATE_EVENT: '#modalCreateEvent',
    BTN_OPEN_MODAL: '#btn-open-modal',
    CLOSE_MODAL: '#closeModal',
    EVENT_CONTAINER: '#event-container',
};

export const FILE_CONFIG = {
    CANDIDATURE: {
        url: '/assets/files/dossier_de_candidature.pdf',
        filename: 'dossier_de_candidature.pdf',
    },
};

export const ANIMATION_DELAYS = {
    IMAGE_TRANSITION: 200,
};

export const CSS_CLASSES = {
    HIDDEN: 'hidden',
    SHOW: 'show',
    VISIBLE: 'visible',
    ACTIVE_FILTER: 'active-filter',
    ADMIN: 'admin',
    EMPLOYEE: 'employee',
};
