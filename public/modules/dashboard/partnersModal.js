/**
 * Partners Modal Manager
 * 
 * Gère les modales pour créer, modifier et supprimer des sections partenaires et leurs logos
 * Avec support pour upload multiple, annulation individuelle et barre de progression
 */

const apiUrl = '/dashboard/partners';
let currentSectionId = null;
let currentLogoId = null;
let currentSectionData = null;
let pendingUploads = new Map(); // Pour gérer les uploads individuels

// DOM Elements
const createSectionModal = document.getElementById('partner-section-create-modal');
const editSectionModal = document.getElementById('partner-section-edit-modal');
const deleteSectionModal = document.getElementById('partner-section-delete-modal');
const manageLogosModal = document.getElementById('partner-logos-manage-modal');
const editLogoModal = document.getElementById('partner-logo-edit-modal');
const deleteLogoModal = document.getElementById('partner-logo-delete-modal');

// Helper function to get fresh CSRF token
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

// Helper function to inject CSRF token into a container
function injectCsrfToken(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  const token = getCsrfToken();
  if (token) {
    container.innerHTML = `<input type="hidden" name="${token.name}" value="${token.value}">`;
  }
}

// Helper function to open/close modals
function openModal(modal) {
  if (modal) {
    modal.showModal();
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modal) {
  if (modal) {
    modal.close();
    document.body.style.overflow = '';
  }
}

// Initialize modals
function initModals() {
  // Close buttons
  document.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('dialog');
      if (modal) closeModal(modal);
    });
  });

  // Cancel buttons
  document.querySelectorAll('.modal-cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('dialog');
      if (modal) closeModal(modal);
    });
  });

  // Click outside to close
  document.querySelectorAll('.universal-modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal || e.target.classList.contains('modal-overlay')) {
        closeModal(modal);
      }
    });
  });

  // Open create modal
  document.querySelectorAll('[data-modal-open="partner-section-create-modal"]').forEach(btn => {
    btn.addEventListener('click', () => {
      injectCsrfToken('csrf-container-create');
      document.getElementById('partner-section-create-form').reset();
      openModal(createSectionModal);
    });
  });

  // Edit section
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-edit-section]')) {
      const sectionId = e.target.closest('[data-edit-section]').getAttribute('data-edit-section');
      handleEditSection(sectionId);
    }
  });

  // Delete section
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-delete-section]')) {
      const btn = e.target.closest('[data-delete-section]');
      const sectionId = btn.getAttribute('data-delete-section');
      const sectionName = btn.getAttribute('data-section-name');
      handleDeleteSection(sectionId, sectionName);
    }
  });

  // Manage logos
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-manage-logos]')) {
      const btn = e.target.closest('[data-manage-logos]');
      const sectionId = btn.getAttribute('data-manage-logos');
      const sectionName = btn.getAttribute('data-section-name');
      handleManageLogos(sectionId, sectionName);
    }
  });

  // Form submissions
  document.getElementById('submit-section-create-btn')?.addEventListener('click', handleCreateSection);
  document.getElementById('submit-section-edit-btn')?.addEventListener('click', handleUpdateSection);
  document.getElementById('submit-section-delete-btn')?.addEventListener('click', handleConfirmDeleteSection);
  document.getElementById('submit-logo-add-btn')?.addEventListener('click', handleAddLogos);
  document.getElementById('submit-logo-edit-btn')?.addEventListener('click', handleUpdateLogo);
  document.getElementById('submit-logo-delete-btn')?.addEventListener('click', handleConfirmDeleteLogo);

  // Logo preview - multiple files like gallery
  document.getElementById('logo-add-files')?.addEventListener('change', (e) => {
    handleLogoFileSelection(e);
  });
  document.getElementById('logo-edit-file')?.addEventListener('change', (e) => {
    previewLogo(e.target.files[0], 'logo-edit-preview');
  });
}

// Handle edit section
async function handleEditSection(sectionId) {
  try {
    // Get section data from the table row
    const row = document.querySelector(`[data-section-id="${sectionId}"]`);
    if (!row) {
      throw new Error('Section non trouvée dans le tableau');
    }
    
    const name = row.querySelector('.col-name')?.textContent?.trim() || '';
    const kind = row.querySelector('.col-kind .badge')?.textContent?.trim() || 'partenaire';
    const description = row.querySelector('.col-description')?.textContent?.trim() || '';
    const position = parseInt(row.querySelector('.col-position')?.textContent?.trim() || '0', 10);
    
    // Try to fetch full data from API
    try {
      const response = await fetch(`${apiUrl}/sections/${sectionId}`);
      if (response.ok) {
        const section = await response.json();
        currentSectionId = sectionId;
        
        // Fill form with API data
        document.getElementById('edit-section-name').value = section.name || '';
        document.getElementById('edit-section-kind').value = section.kind || 'partenaire';
        document.getElementById('edit-section-description').value = section.description || '';
        document.getElementById('edit-section-position').value = section.position || 0;
        document.getElementById('edit-section-active').checked = (section.is_active ?? 1) === 1;
      } else {
        // Fallback to table data
        currentSectionId = sectionId;
        document.getElementById('edit-section-name').value = name;
        document.getElementById('edit-section-kind').value = kind;
        document.getElementById('edit-section-description').value = description;
        document.getElementById('edit-section-position').value = position;
        document.getElementById('edit-section-active').checked = true;
      }
    } catch (apiError) {
      // Fallback to table data
      console.warn('API error, using table data:', apiError);
      currentSectionId = sectionId;
      document.getElementById('edit-section-name').value = name;
      document.getElementById('edit-section-kind').value = kind;
      document.getElementById('edit-section-description').value = description;
      document.getElementById('edit-section-position').value = position;
      document.getElementById('edit-section-active').checked = true;
    }
    
    // Update form action
    document.getElementById('partner-section-edit-form').action = `${apiUrl}/sections/${sectionId}/update`;
    injectCsrfToken('csrf-container-edit');
    
    openModal(editSectionModal);
  } catch (error) {
    console.error('Erreur:', error);
    alert('Impossible de charger la section: ' + error.message);
  }
}

// Handle delete section
function handleDeleteSection(sectionId, sectionName) {
  currentSectionId = sectionId;
  document.getElementById('delete-section-name').textContent = sectionName;
  document.getElementById('partner-section-delete-form').action = `${apiUrl}/sections/${sectionId}/delete`;
  injectCsrfToken('csrf-container-delete');
  openModal(deleteSectionModal);
}

// Handle create section
function handleCreateSection() {
  const form = document.getElementById('partner-section-create-form');
  const formData = new FormData(form);
  
  // Add is_active checkbox
  formData.set('is_active', document.getElementById('create-section-active').checked ? '1' : '0');
  
  fetch(form.action, {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.json();
    }
  })
  .then(data => {
    if (data && data.error) {
      alert(data.error);
    } else {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de la création de la section');
  });
}

// Handle update section
function handleUpdateSection() {
  const form = document.getElementById('partner-section-edit-form');
  const formData = new FormData(form);
  
  // Add is_active checkbox
  formData.set('is_active', document.getElementById('edit-section-active').checked ? '1' : '0');
  
  fetch(form.action, {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.json();
    }
  })
  .then(data => {
    if (data && data.error) {
      alert(data.error);
    } else {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de la mise à jour de la section');
  });
}

// Handle confirm delete section
function handleConfirmDeleteSection() {
  const form = document.getElementById('partner-section-delete-form');
  
  fetch(form.action, {
    method: 'POST',
    body: new FormData(form)
  })
  .then(response => {
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.json();
    }
  })
  .then(data => {
    if (data && data.error) {
      alert(data.error);
    } else {
      window.location.reload();
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de la suppression de la section');
  });
}

// Handle manage logos
async function handleManageLogos(sectionId, sectionName) {
  currentSectionId = sectionId;
  document.getElementById('manage-logos-section-name').textContent = sectionName;
  document.getElementById('logo-add-section-id').value = sectionId;
  document.getElementById('partner-logo-add-form').action = `${apiUrl}/sections/${sectionId}/logo`;
  injectCsrfToken('csrf-container-logo-add');
  
  // Reset preview
  document.getElementById('logo-add-preview').innerHTML = '';
  pendingUploads.clear();
  
  // Load logos
  await loadLogos(sectionId);
  
  openModal(manageLogosModal);
}

// Load logos for a section
async function loadLogos(sectionId) {
  const container = document.getElementById('logos-list-container');
  container.innerHTML = '<p class="loading">Chargement des logos...</p>';
  
  try {
    const response = await fetch(`${apiUrl}/sections/${sectionId}/logos`);
    if (!response.ok) {
      throw new Error('Erreur lors du chargement des logos');
    }
    
    const data = await response.json();
    const logos = data.logos || [];
    
    if (logos.length === 0) {
      container.innerHTML = '<p class="muted">Aucun logo pour le moment.</p>';
      return;
    }
    
    let html = '<div class="logos-grid-manage">';
    logos.forEach(logo => {
      html += `
        <div class="logo-card-manage">
          <img src="${logo.logo}" alt="${logo.name}" style="max-width: 150px; max-height: 80px;">
          <div class="logo-info">
            <h4>${logo.name}</h4>
            <p><a href="${logo.url}" target="_blank">${logo.url}</a></p>
            <p>Position: ${logo.position}</p>
          </div>
          <div class="logo-actions">
            <button class="btn btn-sm btn-primary" data-edit-logo="${logo.id}" data-logo-name="${logo.name}" data-logo-url="${logo.url}" data-logo-position="${logo.position}" data-logo-path="${logo.logo}">Modifier</button>
            <button class="btn btn-sm btn-danger" data-delete-logo="${logo.id}" data-logo-name="${logo.name}">Supprimer</button>
          </div>
        </div>
      `;
    });
    html += '</div>';
    
    container.innerHTML = html;
    
    // Attach event listeners
    container.querySelectorAll('[data-edit-logo]').forEach(btn => {
      btn.addEventListener('click', () => {
        const logoId = btn.getAttribute('data-edit-logo');
        const logoName = btn.getAttribute('data-logo-name');
        const logoUrl = btn.getAttribute('data-logo-url');
        const logoPosition = btn.getAttribute('data-logo-position');
        const logoPath = btn.getAttribute('data-logo-path');
        handleEditLogo(logoId, logoName, logoUrl, logoPosition, logoPath);
      });
    });
    
    container.querySelectorAll('[data-delete-logo]').forEach(btn => {
      btn.addEventListener('click', () => {
        const logoId = btn.getAttribute('data-delete-logo');
        const logoName = btn.getAttribute('data-logo-name');
        handleDeleteLogo(logoId, logoName);
      });
    });
  } catch (error) {
    console.error('Erreur:', error);
    container.innerHTML = '<p class="error">Erreur lors du chargement des logos</p>';
  }
}

// Handle edit logo
function handleEditLogo(logoId, logoName, logoUrl, logoPosition, logoPath) {
  currentLogoId = logoId;
  
  document.getElementById('logo-edit-name').value = logoName || '';
  document.getElementById('logo-edit-url').value = logoUrl || '';
  document.getElementById('logo-edit-position').value = logoPosition || 0;
  document.getElementById('logo-edit-current-img').src = logoPath || '';
  document.getElementById('logo-edit-preview').innerHTML = '';
  
  document.getElementById('partner-logo-edit-form').action = `${apiUrl}/logos/${logoId}/update`;
  injectCsrfToken('csrf-container-logo-edit');
  
  openModal(editLogoModal);
}

// Handle delete logo
function handleDeleteLogo(logoId, logoName) {
  currentLogoId = logoId;
  document.getElementById('delete-logo-name').textContent = logoName;
  document.getElementById('partner-logo-delete-form').action = `${apiUrl}/logos/${logoId}/delete`;
  injectCsrfToken('csrf-container-logo-delete');
  openModal(deleteLogoModal);
}

// Handle multiple logo file selection with preview and individual cancellation
function handleLogoFileSelection(e) {
  const files = e.target.files;
  const preview = document.getElementById('logo-add-preview');
  
  if (!preview || files.length === 0) return;
  
  // Clear existing previews that are not pending
  const existingItems = preview.querySelectorAll('.upload-preview-item:not(.pending-upload)');
  existingItems.forEach(item => item.remove());
  
  Array.from(files).forEach((file, index) => {
    if (!file.type.startsWith('image/')) return;
    
    const uploadId = `logo-${Date.now()}-${index}`;
    const reader = new FileReader();
    reader.onload = (e) => {
      const div = document.createElement('div');
      div.className = 'upload-preview-item';
      div.dataset.uploadId = uploadId;
      div.innerHTML = `
        <div class="preview-header">
          <button type="button" class="btn-remove-preview" data-upload-id="${uploadId}" title="Retirer cette image">
            <span>&times;</span>
          </button>
        </div>
        <img src="${e.target.result}" alt="Preview" class="preview-image">
        <div class="form-group">
          <label for="logo_name_${uploadId}">Nom du logo *</label>
          <input type="text" id="logo_name_${uploadId}" name="logo_names[]" 
                 placeholder="Nom du logo" value="${file.name.replace(/\.[^/.]+$/, '')}" required maxlength="255">
        </div>
        <div class="form-group">
          <label for="logo_url_${uploadId}">URL du logo *</label>
          <input type="url" id="logo_url_${uploadId}" name="logo_urls[]" 
                 placeholder="https://" required>
        </div>
        <div class="form-group">
          <label for="logo_position_${uploadId}">Position</label>
          <input type="number" id="logo_position_${uploadId}" name="logo_positions[]" 
                 value="${index}" min="0">
        </div>
        <div class="upload-status" id="status-${uploadId}"></div>
      `;
      
      preview.appendChild(div);
      
      // Attach remove button listener
      div.querySelector('.btn-remove-preview')?.addEventListener('click', () => {
        removePreviewItem(uploadId);
      });
      
      // Store in pending uploads (file stored directly, not in hidden input)
      pendingUploads.set(uploadId, {
        file: file,
        element: div,
        status: 'pending'
      });
    };
    reader.readAsDataURL(file);
  });
}

// Remove a preview item
function removePreviewItem(uploadId) {
  const item = pendingUploads.get(uploadId);
  if (item) {
    item.element.remove();
    pendingUploads.delete(uploadId);
    updateFileInput();
  }
}

// Update the main file input to reflect remaining files (optional, for display only)
function updateFileInput() {
  // Note: We don't actually need to update the main input
  // since we're building FormData manually from pendingUploads
  // This function is kept for potential future use
}

// Handle add logos (multiple) with progress bar
async function handleAddLogos() {
  const form = document.getElementById('partner-logo-add-form');
  const submitBtn = document.getElementById('submit-logo-add-btn');
  
  if (pendingUploads.size === 0) {
    alert('Veuillez sélectionner au moins un logo.');
    return;
  }
  
  // Validate all required fields before submitting
  const validationErrors = [];
  pendingUploads.forEach((item, uploadId) => {
    if (item.status === 'pending') {
      const nameInput = document.getElementById(`logo_name_${uploadId}`);
      const urlInput = document.getElementById(`logo_url_${uploadId}`);
      
      if (!nameInput || !nameInput.value.trim()) {
        validationErrors.push(`Le nom est requis pour le logo ${uploadId}`);
      }
      if (!urlInput || !urlInput.value.trim()) {
        validationErrors.push(`L'URL est requise pour le logo ${uploadId}`);
      }
    }
  });
  
  if (validationErrors.length > 0) {
    alert('Veuillez remplir tous les champs requis:\n' + validationErrors.join('\n'));
    return;
  }
  
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = '📤 Enregistrement...';
  }
  
  // Show progress bar
  showProgressBar();
  updateProgress(10);
  
  // Create FormData with only pending uploads
  const formData = new FormData();
  const token = getCsrfToken();
  if (token) {
    formData.append(token.name, token.value);
  }
  formData.append('section_id', document.getElementById('logo-add-section-id').value);
  
  const logoFiles = [];
  const logoNames = [];
  const logoUrls = [];
  const logoPositions = [];
  
  // Collect data from pending uploads
  pendingUploads.forEach((item, uploadId) => {
    if (item.status === 'pending') {
      logoFiles.push(item.file);
      const nameInput = document.getElementById(`logo_name_${uploadId}`);
      const urlInput = document.getElementById(`logo_url_${uploadId}`);
      const positionInput = document.getElementById(`logo_position_${uploadId}`);
      
      if (nameInput && urlInput) {
        logoNames.push(nameInput.value.trim());
        logoUrls.push(urlInput.value.trim());
        logoPositions.push(positionInput?.value || '0');
      }
    }
  });
  
  // Append files as array (important: use 'logos[]' to match controller expectation)
  // Include filename for better server handling
  logoFiles.forEach((file, index) => {
    formData.append('logos[]', file, file.name);
    formData.append(`logo_names[${index}]`, logoNames[index]);
    formData.append(`logo_urls[${index}]`, logoUrls[index]);
    formData.append(`logo_positions[${index}]`, logoPositions[index]);
  });
  
  updateProgress(20);
  
  try {
    updateProgress(30);
    
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    updateProgress(50);
    
    // Check if response is JSON or redirect
    const contentType = response.headers.get('content-type') || '';
    let data;
    
    if (response.redirected) {
      // If redirected, it's a success but not JSON
      hideProgressBar();
      window.location.href = response.url;
      return;
    }
    
    if (contentType.includes('application/json')) {
      try {
        data = await response.json();
      } catch (e) {
        throw new Error('Réponse JSON invalide du serveur: ' + e.message);
      }
    } else {
      // Try to parse as JSON anyway
      try {
        const text = await response.text();
        data = JSON.parse(text);
      } catch (e) {
        // If not JSON and not redirected, it's an error
        throw new Error('Réponse invalide du serveur (status: ' + response.status + ')');
      }
    }
    
    updateProgress(80);
    
    if (data && data.success) {
      updateProgress(100);
      
      // Small delay to show 100%
      await new Promise(resolve => setTimeout(resolve, 300));
      
      // Reload logos list
      await loadLogos(currentSectionId);
      form.reset();
      document.getElementById('logo-add-preview').innerHTML = '';
      pendingUploads.clear();
      hideProgressBar();
      
      // Show success message
      showSuccessMessage(data.message || 'Logos ajoutés avec succès.');
      
      // Reset file input
      const mainInput = document.getElementById('logo-add-files');
      if (mainInput) {
        mainInput.value = '';
      }
    } else {
      hideProgressBar();
      const errorMsg = data?.error || 'Erreur lors de l\'ajout des logos.';
      if (data?.errors && Array.isArray(data.errors)) {
        // Show individual errors
        showIndividualErrors(data.errors);
        console.error('Erreurs détaillées:', data.errors);
      } else {
        alert(errorMsg);
        console.error('Erreur complète:', data);
      }
    }
  } catch (error) {
    console.error('Erreur lors de l\'upload:', error);
    hideProgressBar();
    alert('Erreur lors de l\'ajout des logos: ' + (error.message || error));
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = '📤 Enregistrer les logos';
    }
  }
}

// Show progress bar
function showProgressBar() {
  let progressBar = document.getElementById('upload-progress-bar');
  if (!progressBar) {
    progressBar = document.createElement('div');
    progressBar.id = 'upload-progress-bar';
    progressBar.className = 'upload-progress-bar';
    progressBar.innerHTML = `
      <div class="progress-bar-container">
        <div class="progress-bar-fill" id="progress-bar-fill"></div>
      </div>
      <p class="progress-text" id="progress-text">Traitement des images...</p>
    `;
    const preview = document.getElementById('logo-add-preview');
    if (preview && preview.parentNode) {
      preview.parentNode.insertBefore(progressBar, preview);
    } else {
      // Fallback: append to form
      const form = document.getElementById('partner-logo-add-form');
      if (form && form.parentNode) {
        form.parentNode.insertBefore(progressBar, form.nextSibling);
      }
    }
  }
  progressBar.style.display = 'block';
  updateProgress(0);
}

// Update progress bar
function updateProgress(percent) {
  const fill = document.getElementById('progress-bar-fill');
  const text = document.getElementById('progress-text');
  if (fill) {
    fill.style.width = `${percent}%`;
  }
  if (text) {
    if (percent < 50) {
      text.textContent = 'Traitement des images...';
    } else if (percent < 100) {
      text.textContent = 'Enregistrement...';
    } else {
      text.textContent = 'Terminé !';
    }
  }
}

// Hide progress bar
function hideProgressBar() {
  const progressBar = document.getElementById('upload-progress-bar');
  if (progressBar) {
    progressBar.style.display = 'none';
  }
}

// Show success message
function showSuccessMessage(message) {
  const preview = document.getElementById('logo-add-preview');
  if (preview) {
    const successDiv = document.createElement('div');
    successDiv.className = 'upload-success-message';
    successDiv.textContent = message;
    preview.parentNode.insertBefore(successDiv, preview);
    setTimeout(() => {
      successDiv.remove();
    }, 3000);
  }
}

// Show individual errors
function showIndividualErrors(errors) {
  pendingUploads.forEach((item, uploadId, index) => {
    if (errors[index]) {
      const statusDiv = item.element.querySelector('.upload-status');
      if (statusDiv) {
        statusDiv.className = 'upload-status error';
        statusDiv.textContent = errors[index];
      }
    }
  });
}

// Handle update logo
function handleUpdateLogo() {
  const form = document.getElementById('partner-logo-edit-form');
  const formData = new FormData(form);
  
  fetch(form.action, {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.json();
    }
  })
  .then(data => {
    if (data && data.error) {
      alert(data.error);
    } else {
      // Reload logos list and close modal
      loadLogos(currentSectionId);
      closeModal(editLogoModal);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de la mise à jour du logo');
  });
}

// Handle confirm delete logo
function handleConfirmDeleteLogo() {
  const form = document.getElementById('partner-logo-delete-form');
  
  fetch(form.action, {
    method: 'POST',
    body: new FormData(form)
  })
  .then(response => {
    if (response.redirected) {
      window.location.href = response.url;
    } else {
      return response.json();
    }
  })
  .then(data => {
    if (data && data.error) {
      alert(data.error);
    } else {
      // Reload logos list and close modal
      loadLogos(currentSectionId);
      closeModal(deleteLogoModal);
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de la suppression du logo');
  });
}

// Preview single logo (for edit)
function previewLogo(file, previewId) {
  const preview = document.getElementById(previewId);
  if (!file || !preview) return;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px;">`;
  };
  reader.readAsDataURL(file);
}

// Init on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initModals);
} else {
  initModals();
}
