// Lu de façon bloquante en tête de page (pas de defer) : marque la page « JavaScript actif » avant le premier affichage,
// pour que ce que admin.js montre ou masque (recherche, pagination, bouton Réduire) soit en place dès l'affichage, sans saut.
document.documentElement.classList.add('js');
