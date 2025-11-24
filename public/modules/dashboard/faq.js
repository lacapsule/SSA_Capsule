/**
 * Module FAQ Dashboard
 * Utilise le pattern des autres modules : export d'une fonction init...
 */
import { DOM_SELECTORS } from '../constants.js';
import { getAllElements, addEventListenerSafe } from '../utils/dom.js';

/**
 * Initialise le comportement des questions/réponses de la FAQ
 */
export function initFaq() {
    const questions = getAllElements(DOM_SELECTORS.FAQ_QUESTION);

    if (!questions || questions.length === 0) {
        // Rien à faire
        return;
    }

    questions.forEach((q) => {
        addEventListenerSafe(q, 'click', () => {
            const active = document.querySelector(`${DOM_SELECTORS.FAQ_QUESTION}.active`);
            if (active && active !== q) {
                active.classList.toggle('active');
                const aPrev = active.nextElementSibling;
                if (aPrev && aPrev instanceof HTMLElement) {
                    aPrev.style.maxHeight = '0';
                }
            }

            q.classList.toggle('active');

            const answer = q.nextElementSibling;
            if (!answer || !(answer instanceof HTMLElement)) {
                return;
            }

            if (q.classList.contains('active')) {
                answer.style.maxHeight = `${answer.scrollHeight}px`;
            } else {
                answer.style.maxHeight = '0';
            }
        });
    });
}
