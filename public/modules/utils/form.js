/**
 * Utilitaires pour gérer les formulaires
 */

/**
 * Crée et soumet un formulaire POST
 * @param {string} action - URL d'action
 * @param {Object} data - Données à envoyer
 */
export function submitForm(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    Object.entries(data).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

/**
 * Crée et soumet un formulaire avec plusieurs valeurs pour une clé
 * @param {string} action - URL d'action
 * @param {Object} data - Données simples
 * @param {Array} arrayData - Données tableau {key: string, values: Array}
 */
export function submitFormWithArray(action, data, arrayData) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    // Ajouter les données simples
    Object.entries(data).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });

    // Ajouter les données tableau
    arrayData.values.forEach(value => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = arrayData.key;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
