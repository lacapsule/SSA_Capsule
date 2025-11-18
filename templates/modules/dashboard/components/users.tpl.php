<section class="container dash-section-page">
  <header class="header-agenda">
    <h1>Gestion des utilisateurs</h1>

    {{#flash}}
    <p class="notice notice--success" style="color:#43c466;">{{.}}</p>
    {{/flash}}

    <button id="addEventBtn" class="btn btn-primary">Ajouter un utilisateur</button>

  </header>

  <div class="dash-components-container">
    {{{csrf_input}}}
    <input type="hidden" name="action" value="delete">

    <table class="dash-components-table">
      <colgroup>
        <col style="width:40px">
        <col style="width:180px">
        <col style="width:250px">
        <col style="width:120px">
        <col style="width:120px">
        <col style="width:80px">
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
        <tr>
          <td class="idValue" name="idValue">{{id}}</td>
          <td class="usernameValue">{{username}}</td>
          <td class="emailValue">{{email}}</td>
          <td class="{{role}} role">{{role}}</td>
          <td>{{created_at}}</td>
          <td>
            <button class="editBtn" type="button" onclick="editLeUser(event)">Gérer</button>
          </td>
        </tr>
        {{/each}}
      </tbody>
    </table>
  </div>

</section>

<dialog id="users-create-modal" class="universal-modal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Nouvelle utilisateur</h2>
        <button type="button" class="modal-close-btn" data-close="agenda-create-modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="createEventForm">
          {{{csrfInput}}}
          <div class="form-group">
            <label for="create_name">Nom *</label>
            <input type="text" id="create_name" name="username" required>
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
            <label>Rôle</label>
            <div class="role-selector">
              <input type="radio" id="role_employer" name="role" value="employer" checked>
              <label for="role_employer" class="role-option" title="Employé">Employé</label>
              <input type="radio" id="role_admin" name="role" value="admin">
              <label for="role_admin" class="role-option" title="Admin">Admin</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-annuler" data-close="users-create-modal">Annuler</button>
        <button type="submit" form="createEventForm" class="btn btn-primary">Ajouter</button>
      </div>
    </div>
  </div>
</dialog>