
/**
 * Module de gestion de la galerie lightbox
 */
import { DOM_SELECTORS, ANIMATION_DELAYS, CSS_CLASSES } from '../constants.js';
import { getElement, getAllElements, addEventListenerSafe } from '../utils/dom.js';

class Lightbox {
    constructor() {
        this.images = null;
        this.lightbox = null;
        this.lightboxImg = null;
        this.closeBtn = null;
        this.prevBtn = null;
        this.nextBtn = null;
        this.currentIndex = 0;
    }

    /**
     * Initialise la lightbox
     */
    init() {
        this.images = getAllElements(DOM_SELECTORS.GALLERY_IMAGES);
        this.lightbox = getElement(DOM_SELECTORS.LIGHTBOX);
        this.lightboxImg = getElement(DOM_SELECTORS.LIGHTBOX_IMAGE);
        this.closeBtn = getElement(DOM_SELECTORS.LIGHTBOX_CLOSE);
        this.prevBtn = getElement(DOM_SELECTORS.LIGHTBOX_PREV);
        this.nextBtn = getElement(DOM_SELECTORS.LIGHTBOX_NEXT);

        if (!this.images.length || !this.lightbox) {
            console.warn('Éléments de lightbox non trouvés');
            return;
        }

        this.attachEventListeners();
    }

    /**
     * Attache tous les écouteurs d'événements
     */
    attachEventListeners() {
        // Clic sur les images
        this.images.forEach((img, index) => {
            addEventListenerSafe(img, 'click', () => {
                this.currentIndex = index;
                this.show();
            });
        });

        // Boutons de contrôle
        addEventListenerSafe(this.closeBtn, 'click', () => this.close());
        addEventListenerSafe(this.prevBtn, 'click', () => this.showPrevious());
        addEventListenerSafe(this.nextBtn, 'click', () => this.showNext());

        // Clic sur le fond
        addEventListenerSafe(this.lightbox, 'click', (e) => {
            if (e.target === this.lightbox) {
                this.close();
            }
        });
    }

    /**
     * Affiche la lightbox
     */
    show() {
        this.lightbox.classList.add(CSS_CLASSES.SHOW);
        this.updateImage();
        document.addEventListener('keydown', this.handleKeyNavigation.bind(this));
    }

    /**
     * Ferme la lightbox
     */
    close() {
        this.lightbox.classList.remove(CSS_CLASSES.SHOW);
        document.removeEventListener('keydown', this.handleKeyNavigation.bind(this));
    }

    /**
     * Met à jour l'image affichée
     */
    updateImage() {
        this.lightboxImg.classList.remove(CSS_CLASSES.VISIBLE);

        setTimeout(() => {
            this.lightboxImg.src = this.images[this.currentIndex].src;
            this.lightboxImg.onload = () => {
                this.lightboxImg.classList.add(CSS_CLASSES.VISIBLE);
            };
        }, ANIMATION_DELAYS.IMAGE_TRANSITION);
    }

    /**
     * Affiche l'image précédente
     */
    showPrevious() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.updateImage();
    }

    /**
     * Affiche l'image suivante
     */
    showNext() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.updateImage();
    }

    /**
     * Gère la navigation au clavier
     * @param {KeyboardEvent} e - Événement clavier
     */
    handleKeyNavigation(e) {
        switch (e.key) {
            case 'ArrowLeft':
                this.showPrevious();
                break;
            case 'ArrowRight':
                this.showNext();
                break;
            case 'Escape':
                this.close();
                break;
        }
    }
}

/**
 * Initialise la lightbox
 */
export function initLightbox() {
    const lightbox = new Lightbox();
    lightbox.init();
}
