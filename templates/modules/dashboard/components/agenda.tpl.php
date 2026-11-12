<section class="evenement section dash-section-page" id="dashboard-calendar" data-categories='{{categories_json}}'>
  <div class="agenda-contain">
    <div class="title">
      <header class="section-title">
        <h1>Mon Agenda</h1>
        <div class="agenda-action">
          <button id="addEventBtn" class="btn btn-primary">+ Nouvel événement</button>
          <button id="addCategoryBtn" class="btn btn-secondary">+ Créer une catégorie</button>
        </div>
      </header>
    </div>

    <div class="calendar-controls">
      <div class="calendar-view-switch">
        <button type="button" class="calendar-view-btn" data-calendar-view="week">Semaine</button>
        <button type="button" class="calendar-view-btn is-active" data-calendar-view="month">Mois</button>
        <button type="button" class="calendar-view-btn" data-calendar-view="year">Année</button>
      </div>
      <div class="calendar-nav">
        <button type="button" class="calendar-nav-btn" data-calendar-nav="-1" aria-label="Précédent"><img
            src="/assets/icons/arrow-left.svg" alt=""></button>
        <div id="dashboard-calendar-label">—</div>
        <button type="button" class="calendar-nav-btn" data-calendar-nav="1" aria-label="Suivant"><img
            src="/assets/icons/arrow-right.svg" alt=""></button>
      </div>
    </div>
    <div class="categories-banner">
      <h3>Catégories disponibles</h3>
      <div class="categories-list-banner">
        {{#each categories}}
        <div class="category-badge-banner">
          <span class="category-color-dot" style="background-color: {{{color}}}"></span>
          <span class="category-label">{{{label}}}</span>
        </div>
        {{/each}}
        {{^categories}}
        <p class="calendar-details-empty">Aucune catégorie disponible</p>
        {{/categories}}
      </div>
    </div>
    <div id="public-calendar-details" class="calendar-details"
      data-empty="Sélectionner un événement pour voir les détails" data-title="Détails">
      <p class="calendar-details-empty">Sélectionner un événement pour voir les détails</p>
    </div>
    <div id="dashboard-calendar-loading" class="calendar-loading" hidden>Chargement...</div>
    <p id="dashboard-calendar-error" class="calendar-error" hidden></p>

    <div id="dashboard-calendar-grid" class="calendar-grid calendar-grid--month" aria-live="polite"
      aria-label="Calendrier"></div>
  </div>
</section>

<dialog id="agenda-create-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Nouvel événement</h2>
      <button type="button" class="modal-close-btn" data-close="agenda-create-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <form id="createEventForm">
        {{{csrfInput}}}
        <div class="form-group">
          <label for="create_title">Titre *</label>
          <input type="text" id="create_title" name="title" required>
        </div>
        <div class="form-group checkbox-group">
          <label class="checkbox-label">
            <input type="checkbox" id="create_all_day" name="all_day">
            <span>Toute la journée</span>
          </label>
        </div>
        <div class="row-group" id="create-date-section">
          <div class="form-group half">
            <label for="create_date">Date début *</label>
            <input type="date" id="create_date" name="date" required>
          </div>
          <div class="form-group half">
            <label for="create_end_date">Date fin *</label>
            <input type="date" id="create_end_date" name="end_date" required>
          </div>
        </div>
        <div class="row-group" id="create-time-section">
          <div class="form-group half">
            <label for="create_start_time">Heure début *</label>
            <input type="time" id="create_start_time" name="start_time" required>
          </div>
          <div class="form-group half">
            <label for="create_end_time">Heure fin *</label>
            <input type="time" id="create_end_time" name="end_time" required>
          </div>
        </div>
        <div class="form-group">
          <label for="create_description">Description</label>
          <textarea id="create_description" name="description" placeholder="Description"></textarea>
        </div>
        <div class="form-group">
          <label for="create_info">Infos (optionnel)</label>
          <input id="create_info" name="info" placeholder="Infos pratiques, teaser">
        </div>
        <div class="form-group">
          <label for="create_category">Catégorie (définit la couleur)</label>
          <select id="create_category" name="category_id">
            <option value="">Aucune</option>
            {{#each categories}}
            <option value="{{id}}" data-color="{{color}}">{{label}}</option>
            {{/each}}
          </select>
          <input type="hidden" id="create_color" name="color" value="#3788d8">
        </div>
        <div class="form-group">
          <label for="create_location">Lieux</label>
          <input id="create_location" name="location" placeholder="Salle/Batiment/Ville">
        </div>
        <div class="form-group">
          <label for="create_links">Liens</label>
          <input id="create_links" name="links" placeholder="Ex: inscription : https://exemple.com, formulaire : https://lien.com">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-annuler" data-close="agenda-create-modal">Annuler</button>
      <button type="submit" form="createEventForm" class="btn btn-primary">Enregistrer</button>
    </div>
  </div>
</dialog>

<dialog id="agenda-edit-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Modifier l'événement</h2>
      <button type="button" class="modal-close-btn" data-close="agenda-edit-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <form id="editEventForm">
        {{{csrfInput}}}
        <input type="hidden" id="edit_eventId" name="id">

        <div class="form-group">
          <label for="edit_title">Titre *</label>
          <input type="text" id="edit_title" name="title" required>
        </div>
        <div class="row-group">
          <div class="form-group half">
            <label for="edit_date">Date début *</label>
            <input type="date" id="edit_date" name="date" required>
          </div>
          <div class="form-group half">
            <label for="edit_end_date">Date fin *</label>
            <input type="date" id="edit_end_date" name="end_date" required>
          </div>
        </div>
        <div class="row-group">
          <div class="form-group half">
            <label for="edit_start_time">Heure début *</label>
            <input type="time" id="edit_start_time" name="start_time" required>
          </div>
          <div class="form-group half">
            <label for="edit_end_time">Heure fin *</label>
            <input type="time" id="edit_end_time" name="end_time" required>
          </div>
        </div>
        <div class="form-group">
          <label for="edit_description">Description</label>
          <textarea id="edit_description" name="description" placeholder="Description"></textarea>
        </div>
        <div class="form-group">
          <label for="edit_info">Infos (optionnel)</label>
          <input id="edit_info" name="info" placeholder="Infos pratiques, teaser">
        </div>
        <div class="form-group">
          <label for="edit_category">Catégorie (définit la couleur)</label>
          <select id="edit_category" name="category_id">
            <option value="">Aucune</option>
            {{#each categories}}
            <option value="{{id}}" data-color="{{color}}">{{label}}</option>
            {{/each}}
          </select>
          <input type="hidden" id="edit_color" name="color" value="#3788d8">
        </div>
        <div class="form-group">
          <label for="edit_location">Lieux</label>
          <input id="edit_location" name="location" placeholder="Salle/Batiment/Ville">
        </div>
        <div class="form-group">
          <label for="edit_links">Liens</label>
          <input id="edit_links" name="links" placeholder="Ex: inscription : https://exemple.com, formulaire : https://lien.com">
        </div>
      </form>
    </div>
    <div class="modal-footer space-between">
      <button type="button" id="triggerDeleteBtn" class="btn btn-danger-outline">Supprimer</button>
      <div class="right-actions">
        <button type="button" class="btn btn-annuler" data-close="agenda-edit-modal">Annuler</button>
        <button type="submit" form="editEventForm" class="btn btn-primary">Mettre à jour</button>
      </div>
    </div>
  </div>
</dialog>

<dialog id="agenda-delete-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Confirmer la suppression</h2>
      <button type="button" class="modal-close-btn" data-close="agenda-delete-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <p>Voulez-vous vraiment supprimer cet événement ?</p>
      <p class="text-mute"><strong id="delete-event-title"></strong></p>
      <p class="text-mute small">Cette action est irréversible.</p>
      <input type="hidden" id="delete_eventId">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-close="agenda-delete-modal">Annuler</button>
      <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</button>
    </div>
  </div>
</dialog>

<dialog id="agenda-category-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Créer une catégorie</h2>
      <button type="button" class="modal-close-btn" data-close="agenda-category-modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <form id="createCategoryForm">
        {{{csrfInput}}}
        <div class="form-group">
          <label for="category_name">Nom technique *</label>
          <input type="text" id="category_name" name="name" placeholder="collectif" required>
          <small>Identifiant unique (minuscules, sans espaces)</small>
        </div>
        <div class="form-group">
          <label for="category_label">Libellé *</label>
          <input type="text" id="category_label" name="label" placeholder="Collectif" required>
        </div>
        <div class="form-group">
          <label for="category_color">Couleur *</label>
          <input type="color" id="category_color" name="color" value="#3788d8" required>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-annuler" data-close="agenda-category-modal">Annuler</button>
      <button type="submit" form="createCategoryForm" class="btn btn-primary">Créer</button>
    </div>
  </div>
</dialog>

<script src="/modules/dashboard/agenda.js"></script>