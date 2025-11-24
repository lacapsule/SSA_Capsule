# 🎯 Modal Universelle - Guide d'Utilisation

## 📋 Vue d'ensemble

La modal universelle est un composant réutilisable qui permet de créer, modifier et supprimer des éléments à travers une interface cohérente.

## 🏗️ Structure

### Fichiers
- `templates/partials/dashboard/modal.tpl.php` - Template Mustache
- `public/assets/css/modal.css` - Styles CSS
- `public/modules/modal/universalModal.js` - JavaScript principal
- `public/modules/articles/articleModal.js` - Exemple pour les articles

## 📝 Utilisation basique

### 1. Créer une modal dans le template

```php
<!-- Modal de création -->
{{> partial:dashboard/modal 
   modalId="article-create-modal"
   modalTitle="Créer un article"
   modalBody="{{{createForm}}}"
   formId="article-create-form"
   submitText="Créer"
   cancelText="Annuler"
   showFooter="true"
}}
```

### 2. Variables disponibles

| Variable | Type | Description |
|----------|------|-------------|
| `modalId` | string | Identifiant unique de la modal |
| `modalTitle` | string | Titre de la modal |
| `modalBody` | string | Contenu HTML de la modal |
| `formId` | string | ID du formulaire (optionnel) |
| `submitText` | string | Texte du bouton valider |
| `cancelText` | string | Texte du bouton annuler |
| `showFooter` | boolean | Afficher les boutons d'action |

## 🎮 Utilisation JavaScript

### Initialisation

```javascript
import { modalManager } from '../modal/universalModal.js';

// Récupérer une instance
const modal = modalManager.get('article-create-modal');

// Ouvrir
modal.open();

// Fermer
modal.close();
```

### Méthodes disponibles

```javascript
const modal = modalManager.get('my-modal');

// Ouverture/fermeture
modal.open();
modal.close();
modal.toggle();

// Contenu
modal.setTitle('Nouveau titre');
modal.setContent('<p>Nouveau contenu</p>');

// Formulaire
modal.getForm();                      // Récupérer le formulaire
modal.getFormData();                  // Récupérer les données (FormData)
modal.setFormData({nom: 'Jean', age: 30});
modal.validateForm();                 // Valider
modal.reset();                        // Réinitialiser

// Messages
modal.showError('Un erreur est survenue');
modal.showSuccess('Opération réussie !');

// Boutons
modal.setSubmitEnabled(false);        // Désactiver le bouton valider
modal.setSubmitText('En cours...');   // Changer le texte

// Réinitialisation
modal.reset();                        // Nettoyer messages et formulaire
```

## 🚀 Exemple complet - Articles

### 1. Template HTML

```php
<!-- Bouton Créer -->
<a href="#" data-modal-open="article-create-modal" class="btn btn-primary">
  Créer un article
</a>

<!-- Boutons Modifier/Supprimer dans la table -->
{{#each articles}}
<button data-edit-article="{{id}}" class="btn btn-primary">Modifier</button>
<button data-delete-article="{{id}}" data-article-title="{{titre}}" class="btn btn-danger">Supprimer</button>
{{/each}}

<!-- Modals -->
{{> partial:dashboard/modal 
   modalId="article-create-modal"
   modalTitle="Créer un article"
   modalBody="<form id='article-create-form'></form>"
   formId="article-create-form"
   submitText="Créer"
   cancelText="Annuler"
   showFooter="true"
}}

{{> partial:dashboard/modal 
   modalId="article-edit-modal"
   modalTitle="Modifier l'article"
   modalBody="<form id='article-edit-form'></form>"
   formId="article-edit-form"
   submitText="Modifier"
   cancelText="Annuler"
   showFooter="true"
}}

{{> partial:dashboard/modal 
   modalId="article-delete-modal"
   modalTitle="Supprimer l'article"
   modalBody="<p>Êtes-vous sûr ?</p>"
   formId="article-delete-form"
   submitText="Supprimer"
   cancelText="Annuler"
   showFooter="true"
}}
```

### 2. JavaScript

```javascript
import { modalManager } from '../modal/universalModal.js';

const modal = modalManager.get('article-create-modal');

// Ouvrir au clic
document.querySelector('[data-modal-open="article-create-modal"]')
  .addEventListener('click', () => modal.open());

// Gérer la soumission
modal.getForm().addEventListener('submit', async (e) => {
  e.preventDefault();
  
  modal.setSubmitEnabled(false);
  modal.setSubmitText('En cours...');
  
  try {
    const response = await fetch('/api/articles', {
      method: 'POST',
      body: new FormData(modal.getForm())
    });
    
    if (!response.ok) throw new Error('Erreur');
    
    modal.showSuccess('Article créé !');
    setTimeout(() => {
      modal.close();
      window.location.reload();
    }, 1500);
  } catch (error) {
    modal.showError('Erreur: ' + error.message);
    modal.setSubmitEnabled(true);
  }
});
```

## 🎨 Personnalisation CSS

### Variables disponibles

```css
/* Couleurs */
--modal-bg: white;
--modal-border: #e5e7eb;
--modal-shadow: rgba(0, 0, 0, 0.15);

/* Dimensions */
--modal-max-width: 600px;
--modal-border-radius: 8px;
--modal-padding: 20px;

/* Animations */
@keyframes fadeIn { ... }
@keyframes slideUp { ... }
```

### Personnaliser l'apparence

```css
.universal-modal {
  --modal-max-width: 800px;
  --modal-bg: #f9fafb;
}

.modal-header h2 {
  color: #1f2937;
  font-size: 1.75rem;
}

.btn-primary {
  background-color: #your-color;
}
```

## 📦 Cas d'usage recommandés

✅ Créer/modifier/supprimer des éléments
✅ Formulaires complexes
✅ Confirmations d'action
✅ Affichage de contenu détaillé
✅ Gestion des utilisateurs
✅ Gestion des événements

## ⚠️ Points importants

1. **Chaque modal doit avoir un ID unique**
2. **Les formulaires doivent avoir un attribut `id`**
3. **Utiliser `data-modal-open` et `data-modal-close` pour les boutons**
4. **Importer `universalModal.js` dans votre main.js**
5. **Inclure `modal.css` dans le layout**

## 🔗 Intégration dans main.js

```javascript
// main.js
import './modules/modal/universalModal.js';
import './modules/articles/articleModal.js';
```

## 📱 Responsive

La modal s'adapte automatiquement aux écrans:
- Mobile: largeur 95%
- Tablette: largeur 90%
- Desktop: largeur 600px (max)

## 🐛 Dépannage

### La modal ne s'ouvre pas
- Vérifier que `data-modal-id` est présent dans la modal
- Vérifier que l'ID utilisé dans `data-modal-open` correspond

### Le formulaire n'est pas soumis
- Vérifier que le formulaire est dans `.modal-body`
- Vérifier que le bouton submit est du type `submit`

### Les styles ne s'appliquent pas
- Vérifier que `modal.css` est inclus dans le layout
- Vérifier la spécificité CSS
