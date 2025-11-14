<section class="dash-article-page">
  <div class="dash-article-header">
    <h1>Gestion des articles</h1>
    <div class="dash-article-actions">
      <button data-modal-open="article-create-modal" class="btn btn-primary">Créer un article</button>
    </div>
  </div>

  {{^articles}}
  <div class="dash-article-container">
    <p class="dash-article-empty">Aucun article trouvé.</p>
  </div>
  {{/articles}}

  {{#articles}}
  <div class="dash-article-container">
    <table class="dash-article-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Titre</th>
          <th>Résumé</th>
          <th>Date</th>
          <th>Auteur</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        {{#each articles}}
        <tr>
          <td data-label="ID">{{id}}</td>
          <td data-label="Titre" class="col-title">{{titre}}</td>
          <td data-label="Résumé" class="col-meta">{{resume}}</td>
          <td data-label="Date">{{date}}</td>
          <td data-label="Auteur">{{author}}</td>
          <td data-label="Actions">
            <div class="article-actions">
              <button data-edit-article="{{id}}" class="btn btn-primary">Modifier</button>
              <button data-delete-article="{{id}}" data-article-title="{{titre}}" class="btn btn-secondary">Supprimer</button>
            </div>
          </td>
        </tr>
        {{/each}}
      </tbody>
    </table>
  </div>
  {{/articles}}

  <!-- Modal de création -->
  <dialog id="article-create-modal" class="universal-modal" data-modal-id="article-create-modal">
    <div class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Créer un nouvel article</h2>
          <button type="button" class="modal-close-btn" aria-label="Fermer la modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="article-create-form" method="post" action="/dashboard/articles/create">
            <div class="form-group">
              <label for="titre">Titre *</label>
              <input type="text" id="titre" name="titre" required>
            </div>
            <div class="form-group">
              <label for="resume">Résumé</label>
              <input type="text" id="resume" name="resume">
            </div>
            <div class="form-group">
              <label for="description">Description</label>
              <textarea id="description" name="description"></textarea>
            </div>
            <div class="form-group">
              <label for="date_article">Date *</label>
              <input type="date" id="date_article" name="date_article" required>
            </div>
            <div class="form-group">
              <label for="hours">Heure</label>
              <input type="time" id="hours" name="hours">
            </div>
            <div class="form-group">
              <label for="lieu">Lieu</label>
              <input type="text" id="lieu" name="lieu">
            </div>
            <div class="form-group">
              <label for="image">Image de la miniature</label>
              <input type="file" id="image" name="image" accept="image/*">
            </div>
            {{{csrf_input}}}
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="modal-cancel-btn btn btn-secondary">
            Annuler
          </button>
          <button type="submit" form="article-create-form" class="modal-submit-btn btn btn-primary">
            Créer
          </button>
        </div>
      </div>
    </div>
  </dialog>

  <!-- Modal de modification -->
  <dialog id="article-edit-modal" class="universal-modal" data-modal-id="article-edit-modal">
    <div class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Modifier l'article</h2>
          <button type="button" class="modal-close-btn" aria-label="Fermer la modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="article-edit-form" method="post">
            <div class="form-group">
              <label for="edit_titre">Titre *</label>
              <input type="text" id="edit_titre" name="titre" required>
            </div>
            <div class="form-group">
              <label for="edit_resume">Résumé</label>
              <input type="text" id="edit_resume" name="resume">
            </div>
            <div class="form-group">
              <label for="edit_description">Description</label>
              <textarea id="edit_description" name="description"></textarea>
            </div>
            <div class="form-group">
              <label for="edit_date_article">Date *</label>
              <input type="date" id="edit_date_article" name="date_article" required>
            </div>
            <div class="form-group">
              <label for="edit_hours">Heure</label>
              <input type="time" id="edit_hours" name="hours">
            </div>
            <div class="form-group">
              <label for="edit_lieu">Lieu</label>
              <input type="text" id="edit_lieu" name="lieu">
            </div>
            <div class="form-group">
              <label for="edit_image">Image de la miniature</label>
              <input type="file" id="edit_image" name="image" accept="image/*">
            </div>
            <input type="hidden" name="id" id="edit_id">
            {{{csrf_input}}}
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="modal-cancel-btn btn btn-secondary">
            Annuler
          </button>
          <button type="submit" form="article-edit-form" class="modal-submit-btn btn btn-primary">
            Modifier
          </button>
        </div>
      </div>
    </div>
  </dialog>

  <!-- Modal de suppression -->
  <dialog id="article-delete-modal" class="universal-modal" data-modal-id="article-delete-modal">
    <div class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Confirmer la suppression</h2>
          <button type="button" class="modal-close-btn" aria-label="Fermer la modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer cet article ?</p>
          <p style="color: #6b7280; font-size: 0.9rem;"><strong id="delete-article-title"></strong></p>
          <p style="color: #6b7280; font-size: 0.9rem;">Cette action est irréversible.</p>
          <form id="article-delete-form" method="post">
            <input type="hidden" name="id" id="delete_id">
            {{{csrf_input}}}
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="modal-cancel-btn btn btn-secondary">
            Annuler
          </button>
          <button type="submit" form="article-delete-form" class="modal-submit-btn btn btn-danger">
            Supprimer
          </button>
        </div>
      </div>
    </div>
  </dialog>

</section>