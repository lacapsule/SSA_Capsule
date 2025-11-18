<section class="container dash-section-page">
  <header class="header-agenda">
    <h1>Mon Agenda</h1>
    <div class="controls">
      <div class="navmonth">
        <button id="prevBtn" class="btn-nav">‹</button>
        <div id="monthLabel">Chargement...</div>
        <button id="nextBtn" class="btn-nav">›</button>
      </div>
      <button id="addEventBtn" class="btn btn-primary">+ Nouvel événement</button>
    </div>
  </header>

  <!-- contenue du calendrier -->
  <div id="calendar-container">
    <div id="calendar-header"></div>
    <div id="calendar-grid"></div>
  </div>
</section>

<dialog id="agenda-create-modal" class="universal-modal">
  <div class="modal-overlay">
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
          <div class="row-group">
            <div class="form-group half">
              <label for="create_start">Début *</label>
              <input type="datetime-local" id="create_start" name="start" required>
            </div>
            <div class="form-group half">
              <label for="create_end">Fin *</label>
              <input type="datetime-local" id="create_end" name="end" required>
            </div>
          </div>
          <div class="form-group">
            <label>Couleur</label>
            <div class="color-selector">
              <label class="color-option" title="Standard">
                <input type="radio" name="color" value="#3788d8" checked>
                <span class="color-circle" style="background-color: #3788d8;"></span>
              </label>
              <label class="color-option" title="Validé">
                <input type="radio" name="color" value="#43c466">
                <span class="color-circle" style="background-color: #43c466;"></span>
              </label>
              <label class="color-option" title="Urgent">
                <input type="radio" name="color" value="#fdb544">
                <span class="color-circle" style="background-color: #fdb544;"></span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label for="create_description">Lieu</label>
            <input id="create_description" name="description" rows="3" placeholder="Salle/Batiement/Ville"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-annuler" data-close="agenda-create-modal">Annuler</button>
        <button type="submit" form="createEventForm" class="btn btn-primary">Enregistrer</button>
      </div>
    </div>
  </div>
</dialog>

<dialog id="agenda-edit-modal" class="universal-modal">
  <div class="modal-overlay">
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
              <label for="edit_start">Début *</label>
              <input type="datetime-local" id="edit_start" name="start" required>
            </div>
            <div class="form-group half">
              <label for="edit_end">Fin *</label>
              <input type="datetime-local" id="edit_end" name="end" required>
            </div>
          </div>
          <div class="form-group">
            <label>Couleur</label>
            <div class="color-selector">
              <label class="color-option" title="Standard">
                <input type="radio" name="color" value="#3788d8" checked>
                <span class="color-circle" style="background-color: #3788d8;"></span>
              </label>
              <label class="color-option" title="Validé">
                <input type="radio" name="color" value="#43c466">
                <span class="color-circle" style="background-color: #43c466;"></span>
              </label>
              <label class="color-option" title="Urgent">
                <input type="radio" name="color" value="#fdb544">
                <span class="color-circle" style="background-color: #fdb544;"></span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_description">Lieu</label>
            <input id="edit_description" name="description" rows="3"></textarea>
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
  </div>
</dialog>

<dialog id="agenda-delete-modal" class="universal-modal">
  <div class="modal-overlay">
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
  </div>
</dialog>

<script src="/modules/dashboard/agenda.js"></script>