// assets/modules/dashboard/galerie.js

const apiUrl = '/dashboard/galerie';
let selectedPhotos = new Set();

// Éléments DOM
const uploadModal = document.getElementById('galerie-upload-modal');
const editModal = document.getElementById('galerie-edit-modal');
const deleteModal = document.getElementById('galerie-delete-modal');

const uploadPhotosForm = document.getElementById('uploadPhotosForm');
const editPhotoForm = document.getElementById('editPhotoForm');

// Fonction utilitaire pour obtenir un token CSRF frais
function getCsrfToken() {
    const csrfTemplate = document.getElementById('csrf-template');
    if (csrfTemplate) {
        const input = csrfTemplate.querySelector('input[name*="csrf"]') || 
                     csrfTemplate.querySelector('input[name*="token"]');
        if (input) {
            return {
                name: input.name,
                value: input.value
            };
        }
    }
    return null;
}

// Fonctions utilitaires pour la gestion des modales
function closeModal(modal) {
    if (!modal) return;
    
    // Forcer la fermeture en utilisant plusieurs méthodes pour garantir la fermeture
    try {
        if (typeof modal.close === 'function') {
            modal.close();
        }
    } catch (e) {
        console.warn('Erreur lors de la fermeture de la modale avec close():', e);
    }
    
    // Forcer le masquage avec CSS
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    
    // Supprimer l'attribut open
    modal.removeAttribute('open');
    
    // Restaurer le défilement du body
    document.body.style.overflow = '';
    
    // Supprimer tout backdrop/overlay
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

function closeAllModals() {
    const modals = [uploadModal, editModal, deleteModal].filter(Boolean);
    modals.forEach(modal => {
        closeModal(modal);
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Configuration des écouteurs d'événements par ordre de priorité
    // Les clics sur les boutons doivent être traités avant les fermetures de modales
    setupEventListeners();
    setupFormHandlers();
    setupCheckboxHandlers();
    setupModalClosers(); // Les fermetures de modales doivent être en dernier pour éviter les conflits
});

function setupEventListeners() {
    // Bouton d'ajout de photos
    const addPhotosBtn = document.getElementById('addPhotosBtn');
    if (addPhotosBtn) {
        addPhotosBtn.addEventListener('click', openUploadModal);
    }

    // Bouton de suppression de la sélection
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', deleteSelectedPhotos);
    }

    // Utiliser la délégation d'événements pour les boutons d'édition et de suppression
    // Cela garantit qu'ils fonctionnent même après un rechargement de page ou des mises à jour dynamiques
    // Utiliser la phase de capture pour s'assurer qu'elle s'exécute avant les autres gestionnaires de clic
    document.addEventListener('click', (e) => {
        // Vérifier si le clic est sur le bouton d'édition ou ses enfants
        const editBtn = e.target.closest('.edit-photo-btn');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // Empêcher les autres gestionnaires
            const filename = editBtn.dataset.filename;
            const alt = editBtn.dataset.alt;
            console.log('Bouton d\'édition cliqué:', filename, alt);
            if (filename) {
                openEditModal(filename, alt || '');
            }
            return false;
        }

        // Vérifier si le clic est sur le bouton de suppression ou ses enfants
        const deleteBtn = e.target.closest('.delete-photo-btn');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // Empêcher les autres gestionnaires
            const filename = deleteBtn.dataset.filename;
            console.log('Bouton de suppression cliqué:', filename);
            if (filename) {
                openDeleteModal(filename);
            }
            return false;
        }
    }, true); // Utiliser la phase de capture

    // Gestionnaire de changement du champ de fichier
    const photosInput = document.getElementById('photos');
    if (photosInput) {
        photosInput.addEventListener('change', handleFileSelection);
    }
}

function setupModalClosers() {
    // Fermer les modales lors du clic sur un bouton avec data-close
    // Utiliser la délégation d'événements pour gérer les clics sur les boutons et leurs enfants (comme les spans)
    document.addEventListener('click', (e) => {
        // Trouver l'élément le plus proche avec l'attribut data-close
        const closeBtn = e.target.closest('[data-close]');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            const modalId = closeBtn.getAttribute('data-close');
            const modal = document.getElementById(modalId);
            if (modal) {
                closeModal(modal);
            }
        }
    });

    // Gérer également la touche Escape pour fermer toute modale ouverte
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Trouver toutes les modales potentiellement ouvertes
            const openModals = document.querySelectorAll('dialog[open], dialog[style*="flex"], dialog[style*="block"]');
            openModals.forEach(modal => {
                closeModal(modal);
            });
            // Fermer explicitement nos modales connues
            closeAllModals();
        }
    });

    // Fermer la modale lors d'un clic à l'extérieur (sur le backdrop)
    [uploadModal, editModal, deleteModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                // Si le clic est directement sur l'élément dialog (backdrop), fermer la modale
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        }
    });
}

function setupFormHandlers() {
    // Soumission du formulaire d'upload
    const submitUploadBtn = document.getElementById('submitUploadBtn');
    if (submitUploadBtn) {
        submitUploadBtn.addEventListener('click', submitUploadPhotos);
    }

    // Soumission du formulaire d'édition
    const submitEditBtn = document.getElementById('submitEditBtn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', submitEditPhoto);
    }

    // Confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', confirmDelete);
    }
}

function setupCheckboxHandlers() {
    // Gestionnaires de changement des checkboxes
    document.querySelectorAll('.photo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const filename = checkbox.dataset.filename;
            if (e.target.checked) {
                selectedPhotos.add(filename);
            } else {
                selectedPhotos.delete(filename);
            }
            updateDeleteSelectedButton();
        });
    });

    // Checkbox "Tout sélectionner" (si nécessaire)
    // On pourrait ajouter une checkbox "Tout sélectionner" dans l'en-tête
}

function updateDeleteSelectedButton() {
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (deleteSelectedBtn) {
        if (selectedPhotos.size > 0) {
            deleteSelectedBtn.style.display = 'inline-block';
            deleteSelectedBtn.textContent = `Supprimer la sélection (${selectedPhotos.size})`;
        } else {
            deleteSelectedBtn.style.display = 'none';
        }
    }
}

function openUploadModal() {
    // Fermer d'abord toutes les autres modales ouvertes
    closeAllModals();
    
    if (uploadModal) {
        // Réinitialiser tout état précédent
        uploadModal.style.pointerEvents = 'auto';
        uploadModal.style.display = '';
        uploadModal.style.visibility = '';
        uploadModal.style.opacity = '';
        
        uploadModal.showModal?.() || (uploadModal.style.display = 'flex');
        uploadModal.setAttribute('open', '');
        document.body.style.overflow = 'hidden';
        
        // Réinitialiser le formulaire
        if (uploadPhotosForm) {
            uploadPhotosForm.reset();
        }
        const preview = document.getElementById('upload-preview');
        if (preview) {
            preview.innerHTML = '';
        }
    }
}

function openEditModal(filename, alt) {
    console.log('Ouverture de la modale d\'édition pour:', filename, alt);
    
    // Fermer d'abord toutes les autres modales ouvertes
    closeAllModals();
    
    if (!editModal) {
        console.error('Modale d\'édition non trouvée');
        return;
    }
    
    const filenameInput = document.getElementById('edit_filename');
    const altInput = document.getElementById('edit_alt');
    const previewImg = document.getElementById('edit_photo_preview');
    
    if (filenameInput) filenameInput.value = filename;
    if (altInput) altInput.value = alt || '';
    if (previewImg) {
        previewImg.src = `/assets/img/gallery/${filename}`;
        previewImg.alt = alt || '';
    }
    
    // Réinitialiser tout état précédent
    editModal.style.pointerEvents = 'auto';
    editModal.style.display = '';
    editModal.style.visibility = '';
    editModal.style.opacity = '';
    
    editModal.showModal?.() || (editModal.style.display = 'flex');
    editModal.setAttribute('open', '');
    document.body.style.overflow = 'hidden';
    
    console.log('Modale d\'édition ouverte');
}

function openDeleteModal(filename) {
    console.log('Ouverture de la modale de suppression pour:', filename);
    
    // Fermer d'abord toutes les autres modales ouvertes
    closeAllModals();
    
    if (!deleteModal) {
        console.error('Modale de suppression non trouvée');
        return;
    }
    
    const message = document.getElementById('delete-message');
    if (message) {
        message.textContent = 'Êtes-vous sûr(e) de vouloir supprimer cette photo ?';
    }
    deleteModal.dataset.filename = filename;
    
    // Réinitialiser tout état précédent
    deleteModal.style.pointerEvents = 'auto';
    deleteModal.style.display = '';
    deleteModal.style.visibility = '';
    deleteModal.style.opacity = '';
    
    deleteModal.showModal?.() || (deleteModal.style.display = 'flex');
    deleteModal.setAttribute('open', '');
    document.body.style.overflow = 'hidden';
    
    console.log('Modale de suppression ouverte');
}

function handleFileSelection(e) {
    const files = e.target.files;
    const preview = document.getElementById('upload-preview');
    
    if (!preview || files.length === 0) return;
    
    preview.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'upload-preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                <div class="form-group" style="margin-top: 0.5rem;">
                    <label for="photo_name_${index}">Nom de la photo ${index + 1}</label>
                    <input type="text" id="photo_name_${index}" name="photo_names[${index}]" 
                           placeholder="Nom optionnel" value="${file.name.replace(/\.[^/.]+$/, '')}">
                </div>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

async function submitUploadPhotos() {
    if (!uploadPhotosForm) return;
    
    const submitBtn = document.getElementById('submitUploadBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '📤 Enregistrement...';
    }
    
    const formData = new FormData(uploadPhotosForm);
    const csrf = getCsrfToken();
    if (csrf) {
        formData.append(csrf.name, csrf.value);
    }
    
    try {
        const response = await fetch(`${apiUrl}/upload`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Fermer la modale immédiatement et de manière forcée
            if (uploadModal) {
                closeModal(uploadModal);
                // Empêcher toute interaction supplémentaire
                uploadModal.style.pointerEvents = 'none';
            }
            
            // Petit délai pour s'assurer que la modale est complètement fermée avant le rechargement
            // Cela empêche la modale de réapparaître après le rechargement
            setTimeout(() => {
                window.location.reload();
            }, 150);
        } else {
            // Garder la modale ouverte en cas d'erreur pour que l'utilisateur puisse réessayer
            alert(data.message || 'Erreur lors de l\'ajout des photos.');
        }
    } catch (error) {
        console.error('Erreur lors de l\'upload des photos:', error);
        alert('Erreur lors de l\'ajout des photos.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '📤 Enregistrer les photos';
        }
    }
}

async function submitEditPhoto() {
    if (!editPhotoForm) return;
    
    const submitBtn = document.getElementById('submitEditBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '💾 Enregistrement...';
    }
    
    const formData = new FormData(editPhotoForm);
    const csrf = getCsrfToken();
    if (csrf) {
        formData.append(csrf.name, csrf.value);
    }
    
    try {
        const response = await fetch(`${apiUrl}/rename`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Fermer la modale immédiatement et de manière forcée
            if (editModal) {
                closeModal(editModal);
                editModal.style.pointerEvents = 'none';
            }
            
            // Petit délai pour s'assurer que la modale est complètement fermée avant le rechargement
            setTimeout(() => {
                window.location.reload();
            }, 150);
        } else {
            // Garder la modale ouverte en cas d'erreur pour que l'utilisateur puisse réessayer
            alert(data.message || 'Erreur lors de la modification de la photo.');
        }
    } catch (error) {
        console.error('Erreur lors de l\'édition de la photo:', error);
        alert('Erreur lors de la modification de la photo.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '💾 Enregistrer';
        }
    }
}

async function confirmDelete() {
    const filename = deleteModal.dataset.filename;
    if (!filename) return;
    
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Suppression...';
    }
    
    const formData = new FormData();
    formData.append('filename', filename);
    const csrf = getCsrfToken();
    if (csrf) {
        formData.append(csrf.name, csrf.value);
    }
    
    try {
        const response = await fetch(`${apiUrl}/delete`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Photo supprimée avec succès.');
            closeModal(deleteModal);
            window.location.reload();
        } else {
            alert(data.message || 'Erreur lors de la suppression de la photo.');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression de la photo:', error);
        alert('Erreur lors de la suppression de la photo.');
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = '🗑 Confirmer la suppression';
        }
    }
}

async function deleteSelectedPhotos() {
    if (selectedPhotos.size === 0) return;
    
    if (!confirm(`Êtes-vous sûr(e) de vouloir supprimer ${selectedPhotos.size} photo(s) ?`)) {
        return;
    }
    
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.disabled = true;
        deleteSelectedBtn.textContent = 'Suppression...';
    }
    
    const formData = new FormData();
    Array.from(selectedPhotos).forEach(filename => {
        formData.append('filenames[]', filename);
    });
    const csrf = getCsrfToken();
    if (csrf) {
        formData.append(csrf.name, csrf.value);
    }
    
    try {
        const response = await fetch(`${apiUrl}/delete-batch`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || `${data.deleted} photo(s) supprimée(s) avec succès.`);
            selectedPhotos.clear();
            updateDeleteSelectedButton();
            window.location.reload();
        } else {
            alert(data.message || 'Erreur lors de la suppression des photos.');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression des photos:', error);
        alert('Erreur lors de la suppression des photos.');
    } finally {
        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = false;
            deleteSelectedBtn.textContent = 'Supprimer la sélection';
        }
    }
}

