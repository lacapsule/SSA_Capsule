# SSA BACK

## Dashboard :
- Ajouter un lien vers la page galerie.

Dans cette page :
- Gérer la suppression et l’ajout de photos.
- Convertir les formats de photos lors de l’upload (jpeg, png, etc.), puis enregistrer en webp.
- Renommer automatiquement les photos à l’ajout pour éviter les conflits (exemple : img_dateUpload_ID).
- Prévoir une gestion de l’ordre d’affichage des photos dans la galerie.

### Compte avec statut "employé" :
- Accorder l’accès au dashboard.
- Masquer l’accès à la page "Utilisateurs".
- Permettre la modification du mot de passe et mail depuis la page "Mon profil".
- Intégration d'une image de profile (optionnel à voir si on le fait vraiment).

### Utilisateurs :
- Affichage du nom et date création non présente.
- L'ajout/Suppression Utilisateur pas pris en compte dans la BDD.
- récupération des informations nom, mail, role pour la modal de gestion de l'utilisateur

### Agenda :
- Colonne de la gestion des couleurs dans la BDD ne reste pas et met la page en erreur.

## Page "home" pour articles & événements :
- Séparer techniquement les articles et événements pour qu’ils ne soient plus liés lors de la suppression.
- Adapter la suppression pour qu’effacer un événement ne supprime plus l’article associé, et inversement.
- Faire en sorte que les articles restent visibles sur le site, même après leur date de publication passée, contrairement aux événements qui sont masqués ou supprimés une fois terminés.

---
# SSA FRONT

## Dashboard
### Galerie :
- CSS à faire une fois le back fait.
### Dashboard home :
- Tips utilisation dashboard.
### Mon profils
- Info Profil : Nom/Mail/IMG.               OK sauf backend
- Gestion : Password/Mail.                  OK sauf backend
### Projet
- Intégration de vidéo (pas encore fourni).
- Mosaïque "100 volontaire" (pas encore fourni).
### Home
- Btn modals (voir plus) pour actualité et événements affichant la liste des futurs et 3 derniers passés.
