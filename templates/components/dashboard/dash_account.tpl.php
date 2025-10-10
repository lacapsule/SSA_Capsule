<section class="account">
  <h1>Mon mot de passe</h1>

  {{#flash}}
    <p class="notice notice--success">{{.}}</p>
  {{/flash}}

  {{#errors}}
    <ul class="notice notice--error">
      {{#each errors}}
        <li>{{.}}</li>
      {{/each}}
    </ul>
  {{/errors}}

  <div id="update-password-form">
    <h4>Changer de mot de passe</h4>

    <form method="post"
          action="{{accountPasswordAction}}"
          autocomplete="off"
          novalidate>
      {{{csrf_input}}}

      <label for="old_password">
        <span>Ancien mot de passe</span>
      </label>
      <input
        type="password"
        name="old_password"
        id="old_password"
        required
        autocomplete="current-password"
        minlength="8" />

      <label for="new_password">
        <span>Nouveau mot de passe</span>
      </label>
      <input
        type="password"
        name="new_password"
        id="new_password"
        required
        autocomplete="new-password"
        minlength="8" />

      <label for="confirm_new_password">
        <span>Confirmer le nouveau mot de passe</span>
      </label>
      <input
        type="password"
        name="confirm_new_password"
        id="confirm_new_password"
        required
        autocomplete="new-password"
        minlength="8" />

      <button type="submit" id="submit-update-password">
        Mettre à jour
      </button>
    </form>
  </div>
</section>
