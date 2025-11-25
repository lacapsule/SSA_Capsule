<section class="container dash-section-page">
  <header class="header-galerie">
    <h1>Gestion de la galerie</h1>

    {{#flash}}
    <p class="notice notice--success" style="color:#43c466;">{{.}}</p>
    {{/flash}}

    <!-- Hidden CSRF token for modal forms -->
    <div id="csrf-template" style="display:none">
      {{{csrf_input}}}
    </div>

    <div class="galerie-actions">
      <button id="addPhotosBtn" class="btn btn-primary">+ Ajouter des photos</button>
      <button id="deleteSelectedBtn" class="btn btn-danger" style="display:none;">Supprimer la sélection</button>
    </div>
  </header>

  <div class="gallery-track-container">
    <div class="gallery-track">
      {{#each pictures}}
      <div class="card" data-filename="{{filename}}">
        <div class="card-checkbox-wrapper">
          <input type="checkbox" class="photo-checkbox" data-filename="{{filename}}" aria-label="Sélectionner cette photo">
        </div>
        <div class="card-image-wrapper">
          <img src="{{src}}" alt="{{alt}}" class="gallery-img">
        </div>
        <div class="card-actions">
          <button class="btn btn-sm btn-info edit-photo-btn" data-filename="{{filename}}" data-alt="{{alt}}" title="Modifier le nom">
            ✎
          </button>
          <button class="btn btn-sm btn-danger delete-photo-btn" data-filename="{{filename}}" title="Supprimer">
            🗑
          </button>
        </div>
      </div>
      {{/each}}
    </div>
  </div>

  {{#pagination.showPagination}}
  <nav class="gallery-pagination">
    {{#pagination.hasFirst}}
    <a href="?page={{pagination.first}}" class="page-link first-page" aria-label="Première page">&laquo; Première</a>
    {{/pagination.hasFirst}}

    {{#pagination.hasPrev}}
    <a href="?page={{pagination.prev}}" class="page-link prev-page" aria-label="Page précédente">&lsaquo;</a>
    {{/pagination.hasPrev}}

    <div class="pagination-pages">
      {{#each pagination.pages}}
      <a href="?page={{number}}" class="page-link{{#isCurrent}} is-active{{/isCurrent}}">{{number}}</a>
      {{/each}}
    </div>

    <span class="pagination-info">Page {{pagination.current}} / {{pagination.total}}</span>

    {{#pagination.hasNext}}
    <a href="?page={{pagination.next}}" class="page-link next-page" aria-label="Page suivante">&rsaquo;</a>
    {{/pagination.hasNext}}

    {{#pagination.hasLast}}
    <a href="?page={{pagination.last}}" class="page-link last-page" aria-label="Dernière page">Dernière &raquo;</a>
    {{/pagination.hasLast}}
  </nav>
  {{/pagination.showPagination}}
</section>

<!-- Modal: Ajouter des photos -->
<dialog id="galerie-upload-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Ajouter des photos</h2>
      <button type="button" class="modal-close-btn" data-close="galerie-upload-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <form id="uploadPhotosForm" enctype="multipart/form-data">
        {{{csrf_input}}}
        <div class="form-group">
          <label for="photos">Sélectionner une ou plusieurs photos *</label>
          <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
          <small>Vous pouvez sélectionner plusieurs photos à la fois</small>
        </div>
        <div id="upload-preview" class="upload-preview"></div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-close="galerie-upload-modal">Annuler</button>
      <button type="button" id="submitUploadBtn" class="btn btn-primary">📤 Enregistrer les photos</button>
    </div>
  </div>
</dialog>

<!-- Modal: Modifier le nom d'une photo -->
<dialog id="galerie-edit-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Modifier le nom de la photo</h2>
      <button type="button" class="modal-close-btn" data-close="galerie-edit-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <form id="editPhotoForm">
        {{{csrf_input}}}
        <input type="hidden" id="edit_filename" name="filename">
        <div class="form-group">
          <label for="edit_alt">Nom de la photo *</label>
          <input type="text" id="edit_alt" name="alt" required>
        </div>
        <div class="photo-preview">
          <img id="edit_photo_preview" src="" alt="" style="max-width: 100%; max-height: 300px; margin-top: 1rem;">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-close="galerie-edit-modal">Annuler</button>
      <button type="button" id="submitEditBtn" class="btn btn-primary">💾 Enregistrer</button>
    </div>
  </div>
</dialog>

<!-- Modal: Confirmation suppression -->
<dialog id="galerie-delete-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Confirmer la suppression</h2>
      <button type="button" class="modal-close-btn" data-close="galerie-delete-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <p id="delete-message">Êtes-vous sûr(e) de vouloir supprimer cette photo ?</p>
      <p class="text-mute small">Cette action est irréversible.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-close="galerie-delete-modal">Annuler</button>
      <button type="button" id="confirmDeleteBtn" class="btn btn-danger">🗑 Confirmer la suppression</button>
    </div>
  </div>
</dialog>

<script src="/modules/dashboard/galerie.js"></script>
