<section class="users">
  <h1>Gestion des utilisateurs</h1>

  {{#flash}}
    <p class="notice notice--success" style="color:#43c466;">{{.}}</p>
  {{/flash}}

  <div id="csrf-template" style="display:none">
    {{{csrf_input}}}
  </div>

  <div class="wrapper" id="wrapper">
    {{{csrf_input}}}
    <input type="hidden" name="action" value="delete">

    <table class="table table-striped">
      <thead>
        <tr>
          <th></th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Ajouté(e) le</th>
          <th>
            <div class="buttons">
              <button id="createUserBtn" class="iconPlus">
                <img class="imgPlus" src="/assets/icons/plus-square-svgrepo-com.png" alt="Créer" />
              </button>
            </div>
          </th>
        </tr>
      </thead>

      <tbody>
        {{#each users}}
          <tr>
            <td></td>
            <td class="idValue" name="idValue" hidden>{{id}}</td>
            <td class="usernameValue">{{username}}</td>
            <td class="emailValue">{{email}}</td>
            <td class="{{role}} role">
              <p>{{role}}</p>
            </td>
            <td>{{created_at}}</td>
            <td>
              <button class="editBtn" type="button" onclick="editLeUser(event)">Gérer</button>
            </td>
          </tr>
        {{/each}}
      </tbody>
    </table>

    <div id="placeHolderFormEnd"></div>
  </div>

  <div class="popup hidden">
    <form method="post" action="{{createAction}}">
      {{{csrf_input}}}
      <h2>Créer un utilisateur</h2>
      <input type="hidden" name="action" value="create">

      <input type="text" name="username" placeholder="Nom d'utilisateur" required>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <input type="email" name="email" placeholder="Email" required>

      <select name="role">
        <option value="employee">Employé</option>
        <option value="admin">Admin</option>
      </select>

      <button type="submit">Créer l'utilisateur</button>
    </form>
  </div>
</section>
