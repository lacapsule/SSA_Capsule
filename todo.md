# SSA BACK

## Dashboard :
-[x] Ajouter un lien vers la page galerie.

Dans cette page :
-[x] Gérer la suppression et l’ajout de photos.
-[x] Convertir les formats de photos lors de l’upload (jpeg, png, etc.), puis enregistrer en webp.
-[x] Renommer automatiquement les photos à l’ajout pour éviter les conflits (exemple : img_dateUpload_ID).
-[x] Prévoir une gestion de l’ordre d’affichage des photos dans la galerie. (Dernières photos upload en premier)
-[x] Pagination qui permet pas d'aller a la page que ont veux et a la dernière directement

### Utilisateurs :
-[x] Affichage du nom et date création non présente.
-[x] L'ajout/Suppression Utilisateur pas pris en compte dans la BDD.
-[x] récupération des informations nom, mail, role pour la modal de gestion de l'utilisateur

### Agenda :
-[x] Colonne de la gestion des couleurs dans la BDD ne reste pas et met la page en erreur.
-[] Lié les événements ajouter dans l'agenda au évènement afficher sur la page d'acceuil

### Profil :
-[x] Changement de mot de passe pas pris en compte. (Fonctionne dans "Utilisateurs")

## Page "home" pour articles & événements :
-[] Séparer techniquement les articles et événements pour qu’ils ne soient plus liés lors de la suppression.
-[] Adapter la suppression pour qu’effacer un événement ne supprime plus l’article associé, et inversement.
-[] Faire en sorte que les articles restent visibles sur le site, même après leur date de publication passée, contrairement aux événements qui sont masqués ou supprimés une fois terminés.

---
# SSA FRONT

## Dashboard
### Galerie :
-[x] CSS à faire une fois le back fait.

### Dashboard home :
-[~] Tips utilisation dashboard.

### Mon profils
-[x] Info Profil : Nom/Mail.
-[~] Gestion : Password/Mail.

### Projet
-[] Intégration de vidéo (pas encore fourni).
-[] Mosaïque "100 volontaire" (pas encore fourni).

### Accueil
-[x] Evenement : pagination
-[~] Articles : calendrier