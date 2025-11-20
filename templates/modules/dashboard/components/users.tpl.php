<section class="container dash-section-page">
  <header class="header-agenda">
    <h1>Gestion des utilisateurs</h1>

    {{#flash}}
    <p class="notice notice--success" style="color:#43c466;">{{.}}</p>
    {{/flash}}

    <!-- Hidden CSRF token for modal forms -->
    <div id="csrf-template" style="display:none">
      {{{csrf_input}}}
    </div>

    <button id="addUserBtn" class="btn btn-primary">+ Ajouter un utilisateur</button>

  </header>

  <div class="dash-components-container">
    <table class="dash-components-table">
      <colgroup>
        <col style="width:80px">
        <col style="width:180px">
        <col style="width:250px">
        <col style="width:100px">
        <col style="width:150px">
        <col style="width:100px">
      </colgroup>
      <thead>
        <tr>
          <th>Id</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Ajouté(e) le</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        {{#each users}}
        <tr data-user-id="{{id}}">
          <td class="idValue">{{id}}</td>
          <td class="usernameValue">{{username}}</td>
          <td class="emailValue">{{email}}</td>
          <td class="{{role}} role">{{role}}</td>
          <td class="createdAtValue">{{created_at}}</td>
          <td>
            <button class="editBtn btn btn-sm btn-info" type="button" data-user-id="{{id}}">✎ Gérer</button>
          </td>
        </tr>
        {{/each}}
      </tbody>
    </table>
  </div>

</section>

<!-- Modal: Créer utilisateur -->
<dialog id="users-create-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Nouvel utilisateur</h2>
        <button type="button" class="modal-close-btn" data-close="users-create-modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="createUserForm" action="/dashboard/users/create" method="POST">
          {{{csrf_input}}}
          <div class="form-group">
            <label for="create_username">Nom d'utilisateur *</label>
            <input type="text" id="create_username" name="username" required>
          </div>
          <div class="row-group">
            <div class="form-group half">
              <label for="create_email">Email *</label>
              <input type="email" id="create_email" name="email" required>
            </div>
            <div class="form-group half">
              <label for="create_password">Mot de passe *</label>
              <input type="password" id="create_password" name="password" required>
            </div>
          </div>
          <div class="form-group">
            <label for="create_role">Rôle</label>
            <select id="create_role" name="role" required>
              <option value="employee">Employé</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-close="users-create-modal">Annuler</button>
        <button type="button" id="submitCreateBtn" class="btn btn-primary">+ Créer</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Éditer/Gérer utilisateur -->
<dialog id="users-edit-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Gérer l'utilsateur : <strong id="user-name" style="color: var(--ssa-jaune);"></strong></h2>
        <button type="button" class="modal-close-btn" data-close="users-edit-modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm" action="/dashboard/users/update" method="POST">
          {{{csrf_input}}}
          <input type="hidden" id="edit_userId" name="id">
          
          <div class="form-group">
            <label for="edit_username">Nom d'utilisateur *</label>
            <input type="text" id="edit_username" name="username" required>
          </div>
          <div class="form-group">
            <label for="edit_email">Email *</label>
            <input type="email" id="edit_email" name="email" required>
          </div>
          <div class="form-group">
            <label for="edit_role">Rôle</label>
            <select id="edit_role" name="role" required>
              <option value="employee">Employé</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          
          <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
          
        </form>
      </div>
      <div class="modal-footer modal-footer-edit">
        <button type="button" id="changePasswordBtn" class="btn btn-secondary">🔑 Mot de passe</button>
        <div class="modal-footer-actions">
          <button type="button" id="deleteUserBtn" class="btn btn-danger">🗑 Supprimer</button>
          <button type="button" class="btn btn-secondary" data-close="users-edit-modal">Annuler</button>
          <button type="button" id="submitEditBtn" class="btn btn-primary">✓ Mettre à jour</button>
        </div>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Confirmation suppression -->
<dialog id="users-delete-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Confirmer la suppression</h2>
        <button type="button" class="modal-close-btn" data-close="users-delete-modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr(e) de vouloir supprimer <strong id="delete-user-name"></strong> ?</p>
        <p class="text-mute small">Cette action est irréversible.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-close="users-delete-modal">Annuler</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Confirmer la suppression</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Modal: Réinitialiser mot de passe -->
<dialog id="users-reset-password-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Changer le mot de passe</h2>
        <button type="button" class="modal-close-btn" data-close="users-reset-password-modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Définissez un nouveau mot de passe pour <strong id="reset-user-name"></strong> :</p>
        <form id="resetPasswordForm">
          {{{csrf_input}}}
          <input type="hidden" id="reset_userId" name="id">
          <div class="form-group">
            <label for="reset_new_password">Nouveau mot de passe *</label>
            <input type="password" id="reset_new_password" name="password" required minlength="6" placeholder="Minimum 6 caractères">
          </div>
          <div class="form-group">
            <label for="reset_confirm_password">Confirmer le mot de passe *</label>
            <input type="password" id="reset_confirm_password" name="password_confirm" required minlength="6">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-close="users-reset-password-modal">Annuler</button>
        <button type="button" id="submitResetPasswordBtn" class="btn btn-primary">🔑 Changer le mot de passe</button>
      </div>
    </div>
  </div>
</dialog>

<script src="/modules/dashboard/users.js"></script>