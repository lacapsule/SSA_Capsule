
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
        // Chercher d'abord les boutons de galerie, sinon les images directement
        const galleryButtons = getAllElements('.gallery-img-btn');
        this.images = galleryButtons.length > 0 ? galleryButtons : getAllElements(DOM_SELECTORS.GALLERY_IMAGES);
        
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
        // Clic sur les images ou boutons
        this.images.forEach((element, index) => {
            // Support du clic
            addEventListenerSafe(element, 'click', () => {
                this.currentIndex = index;
                this.show();
            });
            
            // Support de la touche Entrée et Espace pour l'accessibilité clavier
            addEventListenerSafe(element, 'keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.currentIndex = index;
                    this.show();
                }
            });
        });

        // Boutons de contrôle
        if (this.closeBtn) {
            addEventListenerSafe(this.closeBtn, 'click', () => this.close());
        }
        if (this.prevBtn) {
            addEventListenerSafe(this.prevBtn, 'click', () => this.showPrevious());
        }
        if (this.nextBtn) {
            addEventListenerSafe(this.nextBtn, 'click', () => this.showNext());
        }

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
        this.lightbox.setAttribute('aria-hidden', 'false');
        this.updateImage();
        document.addEventListener('keydown', this.handleKeyNavigation.bind(this));
        document.body.style.overflow = 'hidden'; // Empêche le scroll du body
        
        // Focus sur le bouton de fermeture
        if (this.closeBtn) {
            this.closeBtn.focus();
        }
        
        // Masquer le contenu principal pour les lecteurs d'écran
        const main = document.querySelector('main');
        if (main) {
            main.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Ferme la lightbox
     */
    close() {
        this.lightbox.classList.remove(CSS_CLASSES.SHOW);
        this.lightbox.setAttribute('aria-hidden', 'true');
        document.removeEventListener('keydown', this.handleKeyNavigation.bind(this));
        document.body.style.overflow = ''; // Restaure le scroll
        
        // Restaurer l'accessibilité du contenu principal
        const main = document.querySelector('main');
        if (main) {
            main.setAttribute('aria-hidden', 'false');
        }
        
        // Retourner le focus à l'élément qui a ouvert la lightbox
        if (this.images && this.images[this.currentIndex]) {
            this.images[this.currentIndex].focus();
        }
    }

    /**
     * Met à jour l'image affichée
     */
    updateImage() {
        this.lightboxImg.classList.remove(CSS_CLASSES.VISIBLE);

        setTimeout(() => {
            const currentElement = this.images[this.currentIndex];
            if (currentElement) {
                // Si c'est un bouton, récupérer l'image à l'intérieur
                const imgElement = currentElement.tagName === 'BUTTON' 
                    ? currentElement.querySelector('img') 
                    : currentElement;
                
                if (imgElement) {
                    this.lightboxImg.src = currentElement.dataset.lightbox || imgElement.src;
                    this.lightboxImg.alt = currentElement.dataset.lightboxAlt || imgElement.alt || 'Image de la galerie';
                } else {
                    this.lightboxImg.src = currentElement.dataset.lightbox || currentElement.src;
                    this.lightboxImg.alt = currentElement.dataset.lightboxAlt || currentElement.alt || 'Image de la galerie';
                }
                
                // Mettre à jour le titre pour les lecteurs d'écran
                const titleElement = document.getElementById('lightbox-title');
                if (titleElement) {
                    titleElement.textContent = `Image ${this.currentIndex + 1} sur ${this.images.length} : ${this.lightboxImg.alt}`;
                }
                
                this.lightboxImg.onload = () => {
                    this.lightboxImg.classList.add(CSS_CLASSES.VISIBLE);
                };
            }
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
