<!-- Profile Card Component -->
<div class="profile-card-container">
  <div class="profile-card">
    <!-- Header with avatar -->
    <div class="profile-header">
      <!-- DEBUG: show what's available -->
      <!-- user: {{user}} -->
      <div class="profile-avatar">
        <span class="avatar-initials">{{user.initial}}</span>
      </div>
      <h2 class="profile-name">{{user.username}}</h2>
      <p class="profile-email">{{user.email}}</p>
    </div>

    <!-- Profile Information -->
    <div class="profile-info">

      <!-- Action Button -->
      <button 
        class="btn-edit-password" 
        id="btn-open-password-modal"
        type="button">
        Modifier mot de passe
      </button>
    </div>
  </div>
</div>

<!-- Universal Modal for Password Change -->
<dialog id="password-modal" class="universal-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Changer de mot de passe</h3>
      <button type="button" class="modal-close-btn" aria-label="Fermer">×</button>
    </div>

    <div class="modal-body">
      <form id="password-form" method="post" action="{{accountPasswordAction}}" autocomplete="off" novalidate>
        {{{csrf_input}}}

        <div class="form-group">
          <label for="old_password">
            <span>Ancien mot de passe</span>
          </label>
          <input
            type="password"
            name="old_password"
            id="old_password"
            required
            autocomplete="current-password"
            minlength="8"
            placeholder="Entrez votre mot de passe actuel" />
          {{#errors.old_password}}<p class="field-error">{{.}}</p>{{/errors.old_password}}
        </div>

        <div class="form-group">
          <label for="new_password">
            <span>Nouveau mot de passe</span>
          </label>
          <input
            type="password"
            name="new_password"
            id="new_password"
            required
            autocomplete="new-password"
            minlength="8"
            placeholder="Entrez votre nouveau mot de passe" />
          {{#errors.new_password}}<p class="field-error">{{.}}</p>{{/errors.new_password}}
        </div>

        <div class="form-group">
          <label for="confirm_new_password">
            <span>Confirmer le nouveau mot de passe</span>
          </label>
          <input
            type="password"
            name="confirm_new_password"
            id="confirm_new_password"
            required
            autocomplete="new-password"
            minlength="8"
            placeholder="Confirmez votre nouveau mot de passe" />
          {{#errors.confirm_new_password}}<p class="field-error">{{.}}</p>{{/errors.confirm_new_password}}
        </div>

        <div class="modal-footer">
          <button type="button" class="modal-cancel-btn">Annuler</button>
          <button type="submit" class="modal-submit-btn">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</dialog>
