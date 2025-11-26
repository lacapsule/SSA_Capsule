/**
 * Article Modal Manager
 * 
 * Gère les modals pour créer, modifier et supprimer des articles
 */

import { modalManager } from '../modal/universalModal.js';

/**
 * Convertit une date JJ/MM/AAAA en AAAA-MM-JJ (format input date)
 * @param {string} dateStr - Format JJ/MM/AAAA ou JJ-MM-AAAA
 * @returns {string} - Format AAAA-MM-JJ ou chaîne vide si invalide
 */
function convertDateToInputFormat(dateStr) {
  if (!dateStr) return '';

  // Essayer JJ/MM/AAAA ou JJ-MM-AAAA
  const regex = /^(\d{2})[\/-](\d{2})[\/-](\d{4})$/;
  const match = dateStr.match(regex);

  if (match) {
    const [, day, month, year] = match;
    return `${year}-${month}-${day}`;
  }

  // Si déjà au format AAAA-MM-JJ, retourner tel quel
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return dateStr;
  }

  return '';
}

/**
 * Convertit une date AAAA-MM-JJ en JJ/MM/AAAA (format affichage)
 * @param {string} dateStr - Format AAAA-MM-JJ
 * @returns {string} - Format JJ/MM/AAAA ou chaîne vide si invalide
 */
function convertDateToDisplayFormat(dateStr) {
  if (!dateStr) return '';

  const regex = /^(\d{4})-(\d{2})-(\d{2})$/;
  const match = dateStr.match(regex);

  if (match) {
    const [, year, month, day] = match;
    return `${day}/${month}/${year}`;
  }

  return dateStr;
}

class ArticleModalManager {
  constructor() {
    this.createModal = null;
    this.editModal = null;
    this.deleteModal = null;
    this.currentArticleId = null;
  }

  /**
   * Initialise les modals - appelée depuis main.js
   */
  init() {
    // Vérifier que les modals existent
    this.createModal = modalManager.get('article-create-modal');
    this.editModal = modalManager.get('article-edit-modal');
    this.deleteModal = modalManager.get('article-delete-modal');

    // Si aucune modal n'existe, ne pas initialiser
    if (!this.createModal || !this.editModal || !this.deleteModal) {
      console.warn('⚠️ Modals des articles non trouvées');
      return;
    }

    this.attachEventListeners();
    console.log('✅ Gestionnaire d\'articles initialisé');
  }

  /**
   * Attache les event listeners
   */
  attachEventListeners() {
    // Boutons "Modifier" sur chaque article
    document.addEventListener('click', (e) => {
      if (e.target.hasAttribute('data-edit-article')) {
        e.preventDefault();
        const articleId = e.target.getAttribute('data-edit-article');
        this.handleEdit(articleId);
      }
    });

    // Boutons "Supprimer" sur chaque article
    document.addEventListener('click', (e) => {
      if (e.target.hasAttribute('data-delete-article')) {
        e.preventDefault();
        const articleId = e.target.getAttribute('data-delete-article');
        const articleTitle = e.target.getAttribute('data-article-title');
        this.handleDelete(articleId, articleTitle);
      }
    });

    // Soumission du formulaire de création
    if (this.createModal) {
      const createForm = this.createModal.getForm();
      if (createForm) {
        createForm.addEventListener('submit', (e) => {
          this.handleCreateSubmit(e);
        });

        // Preview filename when selecting an image
        const inputFile = createForm.querySelector('input[type="file"][name="images[]"]');
        if (inputFile) {
          inputFile.addEventListener('change', () => {
            let prev = createForm.querySelector('#create_image_preview');
            if (!prev) {
              prev = document.createElement('div');
              prev.id = 'create_image_preview';
              prev.style.fontSize = '0.9rem';
              prev.style.color = '#6b7280';
              inputFile.parentNode?.insertBefore(prev, inputFile.nextSibling);
            }
            if (inputFile.files && inputFile.files.length > 0) {
              const names = Array.from(inputFile.files).map((file) => file.name).join(', ');
              prev.textContent = `${inputFile.files.length} fichier(s): ${names}`;
            } else {
              prev.textContent = 'Aucun fichier';
            }
          });
        }
      }
    }

    // Soumission du formulaire d'édition
    if (this.editModal) {
      const editForm = this.editModal.getForm();
      if (editForm) {
        editForm.addEventListener('submit', (e) => {
          this.handleEditSubmit(e);
        });
        // Preview filename on change
        const editInputFile = editForm.querySelector('input[type="file"][name="images[]"]');
        if (editInputFile) {
          editInputFile.addEventListener('change', () => {
            let prev = editForm.querySelector('#edit_image_preview');
            if (!prev) {
              prev = document.createElement('div');
              prev.id = 'edit_image_preview';
              prev.style.fontSize = '0.9rem';
              prev.style.color = '#6b7280';
              editInputFile.parentNode?.insertBefore(prev, editInputFile.nextSibling);
            }
            if (editInputFile.files && editInputFile.files.length > 0) {
              const names = Array.from(editInputFile.files).map((file) => file.name).join(', ');
              prev.textContent = `${editInputFile.files.length} fichier(s): ${names}`;
            } else {
              prev.textContent = 'Aucun fichier';
            }
          });
        }
      }
    }

    // Soumission du formulaire de suppression
    if (this.deleteModal) {
      const deleteForm = this.deleteModal.getForm();
      if (deleteForm) {
        deleteForm.addEventListener('submit', (e) => {
          this.handleDeleteSubmit(e);
        });
      }
    }
  }

  /**
   * Ouvre la modal d'édition
   * @param {number} articleId
   */
  async handleEdit(articleId) {
    if (!this.editModal) return;

    this.editModal.setSubmitText('Chargement...');
    this.editModal.setSubmitEnabled(false);

    try {
      this.currentArticleId = Number(articleId);
      const data = await this.fetchArticlePayload(articleId);

      if (!data.success || !data.article) {
        this.editModal.showError('Article non trouvé');
        return;
      }

      const article = data.article;

      // Remplir le formulaire avec tous les détails
      this.editModal.reset();
      this.editModal.setTitle(`Modifier "${article.titre}"`);

      const form = this.editModal.getForm();
      if (form) {
        form.action = `/dashboard/articles/edit/${articleId}`;
        const titreEl = form.querySelector('#edit_titre');
        if (titreEl) titreEl.value = article.titre || '';
        const resumeEl = form.querySelector('#edit_resume');
        if (resumeEl) resumeEl.value = article.resume || '';
        const descEl = form.querySelector('#edit_description');
        if (descEl) descEl.value = article.description || '';
        // Convertir JJ/MM/AAAA → AAAA-MM-JJ pour input type="date"
        const dateEl = form.querySelector('#edit_date_article');
        if (dateEl) dateEl.value = convertDateToInputFormat(article.date_article) || '';
        // Normaliser l'heure au format HH:MM
        const timeValue = article.hours ? article.hours.substring(0, 5) : '';
        const timeEl = form.querySelector('#edit_hours');
        if (timeEl) timeEl.value = timeValue;
        const lieuEl = form.querySelector('#edit_lieu');
        if (lieuEl) lieuEl.value = article.lieu || '';
        // File inputs cannot have their value set programmatically for security reasons.
        // Instead show a preview or the current filename if desired.
        const editImageInput = form.querySelector('#edit_images');
        if (editImageInput) {
          const previewId = 'edit_image_preview';
          let prev = form.querySelector('#' + previewId);
          if (!prev) {
            prev = document.createElement('div');
            prev.id = previewId;
            prev.style.fontSize = '0.9rem';
            prev.style.color = '#6b7280';
            editImageInput.parentNode?.insertBefore(prev, editImageInput.nextSibling);
          }
          prev.textContent = 'Ajouter des images supplémentaires (la miniature reste la première image).';
        }

        const galleryPreviewId = 'edit_gallery_preview';
        let galleryPreview = form.querySelector('#' + galleryPreviewId);
        if (!galleryPreview) {
          galleryPreview = document.createElement('div');
          galleryPreview.id = galleryPreviewId;
          galleryPreview.className = 'article-gallery-preview';
          if (editImageInput?.parentNode) {
            editImageInput.parentNode.insertBefore(galleryPreview, editImageInput.nextSibling);
          } else {
            form.appendChild(galleryPreview);
          }
        }
        this.renderMediaManager(
          galleryPreview,
          Array.isArray(article.media) ? article.media : this.buildLegacyMediaList(article),
          Number(articleId)
        );
      }

      this.editModal.setSubmitText('Modifier');
      this.editModal.setSubmitEnabled(true);
      this.editModal.open();
    } catch (err) {
      console.error('❌ Erreur lors du chargement:', err);
      this.editModal.showError('Erreur: ' + err.message);
      this.editModal.setSubmitText('Modifier');
      this.editModal.setSubmitEnabled(true);
    }
  }

  /**
   * Ouvre la modal de suppression
   * @param {number} articleId
   * @param {string} articleTitle
   */
  handleDelete(articleId, articleTitle) {
    if (!this.deleteModal) return;

    this.deleteModal.setTitle('Confirmer la suppression');

    // Mettre à jour le titre de l'article à supprimer
    const titleElement = this.deleteModal.modal?.querySelector('#delete-article-title');
    if (titleElement) {
      titleElement.textContent = `"${articleTitle}"`;
    }

    // Définir l'ID et l'action
    const form = this.deleteModal.getForm();
    if (form) {
      form.action = `/dashboard/articles/delete/${articleId}`;
      form.querySelector('#delete_id')?.setAttribute('value', articleId);
    }

    this.deleteModal.setSubmitText('Supprimer');
    this.deleteModal.open();
  }

  /**
   * Traite la soumission du formulaire de création
   */
  handleCreateSubmit(e) {
    e.preventDefault();

    const form = this.createModal?.getForm();
    if (!form) return;

    // Ensure form action is set for creation
    form.action = '/dashboard/articles/create';

    if (!form.checkValidity()) {
      const invalidFields = Array.from(form.querySelectorAll(':invalid'));
      const errorMessages = invalidFields.map(el => {
        const label = form.querySelector(`label[for="${el.id}"]`);
        return label ? label.textContent.replace('*', '').trim() : el.name;
      });

      const errorMessage = 'Veuillez remplir les champs obligatoires : ' + errorMessages.join(', ');
      this.createModal?.showError(errorMessage);
      return;
    }

    this.createModal?.setSubmitEnabled(false);
    const formData = new FormData(form);
    const csrfToken = document.querySelector('input[name="_csrf"]')?.value;
    console.log('CSRF token found in document:', csrfToken);
    if (csrfToken) {
      formData.set('_csrf', csrfToken);
    }
    const action = form.getAttribute('action') || '/dashboard/articles/create';

    // Si un fichier est présent, utiliser XHR pour afficher la progression
    const fileInput = form.querySelector('input[type="file"][name="images[]"]');
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

    const sendPromise = hasFile
      ? this.sendFormWithProgress(form, action, this.createModal)
      : this.sendFormFetch(formData, action);

    sendPromise
      .then(({ ok, status, data }) => {
        console.log('📊 Réponse serveur:', { ok, status, data });
        this.createModal?.clearProgress();

        if (ok && data.success) {
          this.createModal?.showSuccess(data.message || 'Article créé avec succès !');
          setTimeout(() => {
            this.createModal?.close();
            location.reload();
          }, 1000);
        } else {
          const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Erreur lors de la création');
          this.createModal?.showError(errorMsg);
          this.createModal?.setSubmitText('Créer');
          this.createModal?.setSubmitEnabled(true);
        }
      })
      .catch(err => {
        console.error('❌ Erreur upload:', err);
        this.createModal?.clearProgress();
        this.createModal?.showError('Erreur: ' + err.message);
        this.createModal?.setSubmitText('Créer');
        this.createModal?.setSubmitEnabled(true);
      });
  }

  /**
   * Traite la soumission du formulaire d'édition
   */
  handleEditSubmit(e) {
    e.preventDefault();

    const form = this.editModal?.getForm();
    if (!form.checkValidity()) {
      const invalidFields = Array.from(form.querySelectorAll(':invalid'));
      const errorMessages = invalidFields.map(el => {
        const label = form.querySelector(`label[for="${el.id}"]`);
        return label ? label.textContent.replace('*', '').trim() : el.name;
      });

      const errorMessage = 'Veuillez remplir les champs obligatoires : ' + errorMessages.join(', ');
      this.editModal?.showError(errorMessage);
      return;
    }

    this.editModal?.setSubmitEnabled(false);
    this.editModal?.setSubmitText('Modification en cours...');

    // Soumettre avec AJAX
    if (!form) return;

    const formData = new FormData(form);
    const csrfToken = document.querySelector('input[name="_csrf"]')?.value;
    console.log('CSRF token found in document:', csrfToken);
    if (csrfToken) {
      formData.set('_csrf', csrfToken);
    }
    
    // Debug: Log all FormData
    console.log('📝 Edit FormData contents:', {
      keys: Array.from(formData.keys()),
      values: Array.from(formData.entries()).map(([k, v]) => [k, v instanceof File ? `File: ${v.name}` : v])
    });
    
    const action = form.getAttribute('action') || '/dashboard/articles/edit';

    const fileInput = form.querySelector('input[type="file"][name="images[]"]');
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

    const sendPromise = hasFile
      ? this.sendFormWithProgress(form, action, this.editModal)
      : this.sendFormFetch(formData, action);

    sendPromise
      .then(({ ok, status, data }) => {
        console.log('📊 Réponse serveur:', { ok, status, data });
        this.editModal?.clearProgress();

        if (ok && data.success) {
          this.editModal?.showSuccess(data.message || 'Article modifié avec succès !');
          setTimeout(() => {
            this.editModal?.close();
            location.reload();
          }, 1000);
        } else {
          const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Erreur lors de la modification');
          this.editModal?.showError(errorMsg);
          this.editModal?.setSubmitText('Modifier');
          this.editModal?.setSubmitEnabled(true);
        }
      })
      .catch(err => {
        console.error('❌ Erreur upload:', err);
        this.editModal?.clearProgress();
        this.editModal?.showError('Erreur: ' + err.message);
        this.editModal?.setSubmitText('Modifier');
        this.editModal?.setSubmitEnabled(true);
      });
  }

  /**
   * Traite la soumission du formulaire de suppression
   */
  handleDeleteSubmit(e) {
    e.preventDefault();

    this.deleteModal?.setSubmitEnabled(false);
    this.deleteModal?.setSubmitText('Suppression en cours...');

    // Soumettre avec AJAX
    const form = this.deleteModal?.getForm();
    if (!form) return;

    const formData = new FormData(form);
    const action = form.getAttribute('action') || '/dashboard/articles/delete';

    fetch(action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(async (response) => {
        const contentType = response.headers.get('content-type');
        let data = {};

        if (contentType && contentType.includes('application/json')) {
          try {
            data = await response.json();
          } catch (err) {
            console.error('❌ JSON parse error:', err);
            data = { success: false, message: 'Réponse serveur invalide' };
          }
        } else {
          console.warn('⚠️ Réponse non-JSON reçue:', contentType);
          data = { success: false, message: 'Format de réponse serveur incorrect' };
        }

        return { ok: response.ok, status: response.status, data };
      })
      .then(({ ok, status, data }) => {
        console.log('📊 Réponse serveur:', { ok, status, data });

        if (ok && data.success) {
          this.deleteModal?.showSuccess(data.message || 'Article supprimé avec succès !');
          setTimeout(() => {
            this.deleteModal?.close();
            location.reload();
          }, 1000);
        } else {
          const errorMsg = data.message || 'Erreur lors de la suppression';
          this.deleteModal?.showError(errorMsg);
          this.deleteModal?.setSubmitText('Supprimer');
          this.deleteModal?.setSubmitEnabled(true);
        }
      })
      .catch(err => {
        console.error('❌ Erreur fetch:', err);
        this.deleteModal?.showError('Erreur: ' + err.message);
        this.deleteModal?.setSubmitText('Supprimer');
        this.deleteModal?.setSubmitEnabled(true);
      });
  }

  buildLegacyMediaList(article) {
    const list = [];
    const images = Array.isArray(article.images) ? [...article.images] : [];
    if (images.length === 0 && article.image) {
      images.push(article.image);
    }
    images.forEach((src, index) => {
      list.push({
        id: null,
        src,
        path: src,
        filename: this.extractFilename(src) || `media-${index + 1}`,
        isVideo: this.isVideoPath(src),
      });
    });
    return list;
  }

  renderMediaManager(container, mediaList, articleId) {
    if (!container) return;

    const normalized = this.normalizeMediaList(mediaList);
    container.dataset.articleId = articleId;
    container.classList.add('article-media-container');

    if (normalized.length === 0) {
      container.innerHTML = '<p>Aucun média pour le moment.</p>';
      return;
    }

    container.innerHTML = `
      <p>${normalized.length} média(s) lié(s) :</p>
      <div class="article-media-manager">
        ${normalized.map((media) => this.mediaItemTemplate(media)).join('')}
      </div>
    `;

    this.bindMediaManagerEvents(container, articleId);
  }

  normalizeMediaList(mediaList) {
    if (!Array.isArray(mediaList)) {
      return [];
    }

    return mediaList.map((media, index) => {
      const src = media.src || media.path || '';
      const path = media.path || src;
      const filename = media.filename || this.extractFilename(path) || `media-${index + 1}`;
      const numericId = Number.isInteger(media.id)
        ? media.id
        : Number.isInteger(Number(media.id))
          ? Number(media.id)
          : null;
      const isVideo = typeof media.isVideo === 'boolean' ? media.isVideo : this.isVideoPath(src);

      return {
        id: numericId,
        src,
        path,
        filename,
        isVideo,
        canManage: Number.isInteger(numericId),
      };
    });
  }

  mediaItemTemplate(media) {
    const preview = media.isVideo
      ? `<video src="${media.src}" preload="metadata" controls playsinline muted></video>`
      : `<img src="${media.src}" alt="${this.escapeHtml(media.filename)}">`;

    const actions = media.canManage
      ? `
        <div class="article-media-actions">
          <button type="button" class="article-media-btn" data-media-rename>Renommer</button>
          <button type="button" class="article-media-btn article-media-btn--danger" data-media-delete>Supprimer</button>
        </div>
      `
      : `<p class="article-media-readonly">Gestion indisponible pour ce média.</p>`;

    return `
      <div class="article-media-item" data-media-item data-media-id="${media.id ?? ''}">
        <div class="article-media-thumb">
          ${preview}
        </div>
        <div class="article-media-meta">
          <label>Nom du fichier</label>
          <input type="text" value="${this.escapeHtml(media.filename)}" data-media-filename ${media.canManage ? '' : 'disabled'}>
          ${actions}
          <p class="article-media-status" data-media-status></p>
        </div>
      </div>
    `;
  }

  bindMediaManagerEvents(container, articleId) {
    container.querySelectorAll('[data-media-item]').forEach((item) => {
      const mediaId = Number(item.getAttribute('data-media-id'));
      if (!mediaId) {
        return;
      }

      const deleteBtn = item.querySelector('[data-media-delete]');
      if (deleteBtn) {
        deleteBtn.addEventListener('click', () => this.handleMediaDelete(articleId, mediaId, container, item));
      }

      const renameBtn = item.querySelector('[data-media-rename]');
      if (renameBtn) {
        renameBtn.addEventListener('click', () => this.handleMediaRename(articleId, mediaId, container, item));
      }
    });
  }

  async handleMediaDelete(articleId, mediaId, container, item) {
    if (!confirm('Supprimer ce média ?')) {
      return;
    }

    const statusEl = item.querySelector('[data-media-status]');
    if (statusEl) statusEl.textContent = 'Suppression en cours...';

    const formData = new FormData();
    formData.set('_csrf', this.getCsrfToken());

    try {
      const response = await fetch(`/dashboard/articles/${articleId}/media/${mediaId}/delete`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Suppression impossible.');
      }

      if (statusEl) statusEl.textContent = 'Média supprimé.';
      await this.reloadMediaManager(articleId, container);
    } catch (err) {
      console.error(err);
      if (statusEl) statusEl.textContent = err.message || 'Erreur inconnue.';
    }
  }

  async handleMediaRename(articleId, mediaId, container, item) {
    const input = item.querySelector('[data-media-filename]');
    const statusEl = item.querySelector('[data-media-status]');
    const newName = input?.value?.trim() ?? '';

    if (!newName) {
      if (statusEl) statusEl.textContent = 'Nom requis.';
      return;
    }

    if (statusEl) statusEl.textContent = 'Renommage en cours...';

    const formData = new FormData();
    formData.set('_csrf', this.getCsrfToken());
    formData.set('name', newName);

    try {
      const response = await fetch(`/dashboard/articles/${articleId}/media/${mediaId}/rename`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Renommage impossible.');
      }

      if (statusEl) statusEl.textContent = 'Nom mis à jour.';
      await this.reloadMediaManager(articleId, container);
    } catch (err) {
      console.error(err);
      if (statusEl) statusEl.textContent = err.message || 'Erreur inconnue.';
    }
  }

  async reloadMediaManager(articleId, container) {
    try {
      const data = await this.fetchArticlePayload(articleId);
      if (data.success && data.article) {
        const media = Array.isArray(data.article.media)
          ? data.article.media
          : this.buildLegacyMediaList(data.article);
        this.renderMediaManager(container, media, articleId);
      }
    } catch (err) {
      console.warn('Impossible de recharger les médias', err);
    }
  }

  async fetchArticlePayload(articleId) {
    const response = await fetch(`/dashboard/articles/api/${articleId}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data?.message || `Erreur: ${response.status}`);
    }

    return data;
  }

  extractFilename(path) {
    if (!path) return '';
    try {
      const cleaned = path.split('?')[0];
      return cleaned.split('/').filter(Boolean).pop() || '';
    } catch (err) {
      return '';
    }
  }

  isVideoPath(path) {
    if (!path) return false;
    const target = path.split('?')[0];
    const ext = target.split('.').pop()?.toLowerCase();
    return ['mp4', 'webm', 'ogv', 'ogg', 'mov'].includes(ext || '');
  }

  getCsrfToken() {
    return document.querySelector('input[name="_csrf"]')?.value || '';
  }

  escapeHtml(value) {
    return (value ?? '').toString().replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[char]));
  }

  /**
   * Envoie un FormData en XHR et met à jour la modal avec la progression
   * @param {HTMLFormElement} form
   * @param {string} url
   * @param {UniversalModal} modal
   * @returns {Promise<{ok:boolean,status:number,data:any}>}
   */
  sendFormWithProgress(form, url, modal) {
    return new Promise((resolve, reject) => {
      const formData = new FormData(form);

      // Debug: Log all FormData keys and CSRF token specifically
      console.log('📝 FormData contents:', {
        keys: Array.from(formData.keys()),
        csrfPresent: Array.from(formData.keys()).includes('_csrf'),
        csrfValue: formData.get('_csrf') ? formData.get('_csrf').substring(0, 10) + '...' : 'NOT FOUND'
      });

      const xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      // Ensure cookies (session) are sent for CSRF/session-protected endpoints
      xhr.withCredentials = true;
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
          const percent = (e.loaded / e.total) * 100;
          modal?.setProgress(percent);
        }
      };

      xhr.onload = () => {
        try {
          const status = xhr.status;
          let data = {};
          try {
            data = JSON.parse(xhr.responseText || '{}');
          } catch (err) {
            console.error('❌ Failed to parse response:', xhr.responseText);
            data = { success: false, message: 'Réponse serveur invalide' };
          }

          // Debug response
          if (status === 403) {
            console.error('🔒 CSRF Validation Failed (403):', {
              responseText: xhr.responseText,
              responseData: data
            });
          }

          resolve({ ok: status >= 200 && status < 300, status, data });
        } catch (err) {
          reject(err);
        }
      };

      xhr.onerror = (e) => {
        reject(new Error('Network error'));
      };

      console.log('📤 Sending to:', url, 'with credentials');
      xhr.send(formData);
    });
  }

  /**
   * Envoie via fetch quand pas de fichier (simple)
   */
  sendFormFetch(formData, url) {
    return fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(async (response) => {
        const contentType = response.headers.get('content-type');
        let data = {};
        if (contentType && contentType.includes('application/json')) {
          try { data = await response.json(); } catch (err) { data = { success: false, message: 'Réponse serveur invalide' }; }
        } else {
          data = { success: false, message: 'Format de réponse serveur incorrect' };
        }
        return { ok: response.ok, status: response.status, data };
      });
  }
}

// Instance unique
let articleModalManager = null;

/**
 * Fonction d'initialisation - appelée depuis main.js
 */
export function initArticleModal() {
  if (!articleModalManager) {
    articleModalManager = new ArticleModalManager();
    articleModalManager.init();
  }
}

export { ArticleModalManager };

