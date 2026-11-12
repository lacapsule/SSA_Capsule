<section class="dash-section-page">
  <div class="dashboard-content">
    <div class="dash-components-header">
      <h1>Gestion des partenaires</h1>
      <div class="dash-components-actions">
        <button data-modal-open="partner-section-create-modal" class="btn btn-primary">Créer une section</button>
      </div>
    </div>

    {{#flash_success}}
    <p class="notice notice--success">{{.}}</p>
    {{/flash_success}}
    {{#flash_error}}
    <p class="notice notice--error">{{.}}</p>
    {{/flash_error}}
    {{#errors._global}}
    <p class="notice notice--error">{{.}}</p>
    {{/errors._global}}

    <!-- Hidden CSRF token for modal forms -->
    <div id="csrf-template" style="display:none">
      {{{csrf_input}}}
    </div>

    <div class="dash-components-container">
      {{^sections}}
      <p class="dash-components-empty">Aucune section trouvée.</p>
      {{/sections}}
      
      {{#sections}}
      <table class="dash-components-table">
        <colgroup>
          <col style="width:40px">
          <col style="width:200px">
          <col style="width:150px">
          <col style="width:300px">
          <col style="width:80px">
          <col style="width:100px">
          <col style="width:150px">
        </colgroup>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Type</th>
            <th>Description</th>
            <th>Position</th>
            <th>Logos</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {{#each sections}}
          <tr data-section-id="{{id}}">
            <td class="col-id" data-label="ID">{{id}}</td>
            <td class="col-name" data-label="Nom">{{name}}</td>
            <td class="col-kind" data-label="Type">
              <span class="badge badge-{{kind}}">{{kind}}</span>
            </td>
            <td class="col-description" data-label="Description" title="{{description}}">
              {{#description}}{{description}}{{/description}}
              {{^description}}<span class="muted">—</span>{{/description}}
            </td>
            <td class="col-position" data-label="Position">{{position}}</td>
            <td class="col-logos" data-label="Logos">
              <div class="logos-count-display" title="{{logos_count}} logo{{#logos_count_plural}}s{{/logos_count_plural}}">
                <img src="/assets/icons/galerie.svg" alt="Logos" class="logos-icon">
                <span class="badge logos-badge">{{logos_count}}</span>
              </div>
            </td>
            <td class="col-action" data-label="Actions">
              <div class="dash-components-actions2">
                <a href="#" title="Gérer les logos" data-manage-logos="{{id}}" data-section-name="{{name}}">
                  <img src="/assets/icons/galerie.svg" alt="Logos" title="Gérer les logos">
                </a>
                <a href="#" title="Modifier la section" data-edit-section="{{id}}">
                  <img src="/assets/icons/edit.svg" alt="Modifier" title="Modifier">
                </a>
                <a href="#" title="Supprimer la section" data-delete-section="{{id}}" data-section-name="{{name}}">
                  <img src="/assets/icons/bin.svg" alt="Supprimer" title="Supprimer">
                </a>
              </div>
            </td>
          </tr>
          {{/each}}
        </tbody>
      </table>
      {{/sections}}
    </div>
  </div>
</section>

<!-- Modal: Créer une section -->
<dialog id="partner-section-create-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Créer une section</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="partner-section-create-form" action="/dashboard/partners/sections" method="POST">
          <div id="csrf-container-create"></div>
          <div class="form-group">
            <label for="create-section-name">Nom *</label>
            <input type="text" id="create-section-name" name="name" required maxlength="255">
            <span class="field-error" id="create-section-name-error"></span>
          </div>
          <div class="form-group">
            <label for="create-section-kind">Type *</label>
            <select id="create-section-kind" name="kind" required>
              {{#kind_options}}
              <option value="{{value}}">{{label}}</option>
              {{/kind_options}}
            </select>
          </div>
          <div class="form-group">
            <label for="create-section-description">Description</label>
            <textarea id="create-section-description" name="description" rows="3"></textarea>
          </div>
          <div class="row-group">
            <div class="form-group half">
              <label for="create-section-position">Position</label>
              <input type="number" id="create-section-position" name="position" value="0" min="0">
            </div>
            <div class="form-group half">
              <label class="switch">
                <input type="checkbox" id="create-section-active" name="is_active" checked>
                <span>Active</span>
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Annuler</button>
        <button type="button" id="submit-section-create-btn" class="btn btn-primary">Créer</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Modifier une section -->
<dialog id="partner-section-edit-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Modifier la section</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="partner-section-edit-form" method="POST">
          <div id="csrf-container-edit"></div>
          <div class="form-group">
            <label for="edit-section-name">Nom *</label>
            <input type="text" id="edit-section-name" name="name" required maxlength="255">
            <span class="field-error" id="edit-section-name-error"></span>
          </div>
          <div class="form-group">
            <label for="edit-section-kind">Type *</label>
            <select id="edit-section-kind" name="kind" required>
              {{#kind_options}}
              <option value="{{value}}">{{label}}</option>
              {{/kind_options}}
            </select>
          </div>
          <div class="form-group">
            <label for="edit-section-description">Description</label>
            <textarea id="edit-section-description" name="description" rows="3"></textarea>
          </div>
          <div class="row-group">
            <div class="form-group half">
              <label for="edit-section-position">Position</label>
              <input type="number" id="edit-section-position" name="position" min="0">
            </div>
            <div class="form-group half">
              <label class="switch">
                <input type="checkbox" id="edit-section-active" name="is_active">
                <span>Active</span>
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Annuler</button>
        <button type="button" id="submit-section-edit-btn" class="btn btn-primary">Enregistrer</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Supprimer une section -->
<dialog id="partner-section-delete-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Supprimer la section</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer la section <strong id="delete-section-name"></strong> ?</p>
        <p class="warning">Cette action supprimera également tous les logos associés et est irréversible.</p>
        <form id="partner-section-delete-form" method="POST">
          <div id="csrf-container-delete"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Annuler</button>
        <button type="button" id="submit-section-delete-btn" class="btn btn-danger">Supprimer</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Gérer les logos d'une section -->
<dialog id="partner-logos-manage-modal" class="universal-modal large">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Gérer les logos - <span id="manage-logos-section-name"></span></h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Liste des logos existants -->
        <div id="logos-list-container">
          <p class="loading">Chargement des logos...</p>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <!-- Formulaire d'ajout de logos -->
        <h3>Ajouter des logos</h3>
        <form id="partner-logo-add-form" method="POST" enctype="multipart/form-data">
          <div id="csrf-container-logo-add"></div>
          <input type="hidden" id="logo-add-section-id" name="section_id">
          <div class="form-group">
            <label for="logo-add-files">Sélectionner un ou plusieurs logos *</label>
            <input type="file" id="logo-add-files" name="logos[]" accept="image/*" multiple required>
            <small>Vous pouvez sélectionner plusieurs logos à la fois</small>
            <span class="field-error" id="logo-add-files-error"></span>
          </div>
          <div id="logo-add-preview" class="upload-preview"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Fermer</button>
        <button type="button" id="submit-logo-add-btn" class="btn btn-primary">📤 Enregistrer les logos</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Modifier un logo -->
<dialog id="partner-logo-edit-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Modifier le logo</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="partner-logo-edit-form" method="POST" enctype="multipart/form-data">
          <div id="csrf-container-logo-edit"></div>
          <div class="form-group">
            <label for="logo-edit-name">Nom *</label>
            <input type="text" id="logo-edit-name" name="logo_name" required maxlength="255">
            <span class="field-error" id="logo-edit-name-error"></span>
          </div>
          <div class="form-group">
            <label for="logo-edit-url">URL *</label>
            <input type="url" id="logo-edit-url" name="logo_url" required placeholder="https://">
            <span class="field-error" id="logo-edit-url-error"></span>
          </div>
          <div class="row-group">
            <div class="form-group half">
              <label for="logo-edit-file">Logo (laisser vide pour ne pas modifier)</label>
              <input type="file" id="logo-edit-file" name="logo" accept="image/*">
              <span class="field-error" id="logo-edit-file-error"></span>
            </div>
            <div class="form-group half">
              <label for="logo-edit-position">Position</label>
              <input type="number" id="logo-edit-position" name="logo_position" min="0">
            </div>
          </div>
          <div class="logo-preview js-logo-preview" id="logo-edit-preview"></div>
          <div id="logo-edit-current">
            <p><strong>Logo actuel :</strong></p>
            <img id="logo-edit-current-img" src="" alt="Logo actuel" style="max-width: 200px; max-height: 100px;">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Annuler</button>
        <button type="button" id="submit-logo-edit-btn" class="btn btn-primary">Enregistrer</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Supprimer un logo -->
<dialog id="partner-logo-delete-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Supprimer le logo</h2>
        <button type="button" class="modal-close-btn" aria-label="Fermer">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer le logo <strong id="delete-logo-name"></strong> ?</p>
        <p class="warning">Cette action est irréversible.</p>
        <form id="partner-logo-delete-form" method="POST">
          <div id="csrf-container-logo-delete"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-cancel-btn btn btn-secondary">Annuler</button>
        <button type="button" id="submit-logo-delete-btn" class="btn btn-danger">Supprimer</button>
      </div>
    </div>
  </div>
</dialog>

<script type="module" src="/modules/dashboard/partnersModal.js"></script>
