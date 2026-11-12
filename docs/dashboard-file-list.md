# Liste des fichiers du dashboard (SSA_DEV)

Ce document recense tous les fichiers liés au nouveau **dashboard** dans le projet **SSA_DEV**. Ils sont classés par *back‑end* et *front‑end* pour faciliter le transfert vers le site `apprenons_autrement_dev`.

---

## 🔧 Back‑end

- `app/Modules/Dashboard/DashboardController.php`  
- `app/Modules/Dashboard/DashboardService.php`  
- `app/Modules/Dashboard/DashboardPresenter.php`  
- `templates/layouts/dashboard.tpl.php` (layout principal du dashboard)

> Ces fichiers gèrent la logique serveur, les services métiers et la présentation de base.

---

## 🎨 Front‑end

### Templates / HTML
- `templates/layouts/dashboard.tpl.php` (voir aussi en back‑end)

### CSS
Tous les fichiers ci-dessous se trouvent dans `public/assets/css/dashboard/` :

- `dash-sidebar.css`
- `dash-article.css`
- `dash-projet.css`
- `dash-agenda.css`
- `dash-partners.css`
- `dash-users.css`
- `main.css` (ensemble des styles généraux)
- `dash-galerie.css`
- `dash-account.css`
- `dash-faq.css`

Fichiers de sauvegarde (répertoires `bak` et racine) :
- `public/assets/css/bak/dash-global.css`
- `public/assets/css/bak/dashboard/dash-main.css`
- `public/assets/css/bak/dashboard/dash-partners.css`
- `public/assets/css/bak/dashboard/dash-faq.css`
- `public/assets/css/bak/dashboard/dash-users.css`
- `public/assets/css/bak/dashboard/dash-sidebar.css`
- `public/assets/css/bak/dashboard/dash-article.css`
- `public/assets/css/bak/dashboard/dash-account.css`
- `public/assets/css/bak/dashboard/dash-galerie.css`
- `public/assets/css/bak/dashboard/dash-agenda.css`
- `public/assets/css/dash-global.css`

### Images / Icônes
- `public/assets/icons/dashboard.svg`

> Aucun fichier JavaScript spécifique n'a été détecté par le terme `dash`.

---

## 📝 Remarques
- Le template `dashboard.tpl.php` est partagé entre back et front puisqu'il contient à la fois la structure HTML et les inclusions de styles.
- Lors de la migration vers `apprenons_autrement_dev`, veillez à copier à la fois les fichiers backend **et** l'arborescence CSS/icônes.
- Si de nouveaux éléments frontend (JS, images supplémentaires) ont été ajoutés dans d'autres dossiers, pensez à les rechercher avec `find` ou `grep`.

Bonne migration ! 😊