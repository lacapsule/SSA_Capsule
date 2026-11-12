/**
 * Menu mobile : ferme le panneau au clic sur un lien.
 *
 * Le menu repose sur une case à cocher CSS (#menu-toggle). Quand un lien
 * pointe vers une ancre de la page courante, aucune navigation ne recharge
 * la page, donc la case restait cochée et le menu ouvert. On la décoche au
 * clic sur n'importe quel lien de la navigation.
 */
export function initMobileMenu() {
    const toggle = document.getElementById('menu-toggle');
    const navbar = document.querySelector('.navbar');
    if (!toggle || !navbar) {
        return;
    }

    navbar.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            toggle.checked = false;
        }
    });
}
