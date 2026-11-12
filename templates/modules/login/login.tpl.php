<div class="container">

  <div class="myform">
    {{#error}}
    <p class="form-error">{{str.login_error}}</p>
    {{/error}}

    <form method="post" action="{{action}}" novalidate>
      {{{csrf_input}}}

      <h2>{{title}}</h2>

      <input type="username" name="username" id="username" value="{{prefill.username}}" required autocomplete="username"
        placeholder="{{str.login_username}}">

      <div class="password-wrapper">
        <input type="password" name="password" id="password" required placeholder="{{str.login_password}}">
        <button type="button" id="togglePassword" aria-label="Afficher le mot de passe"></button>
      </div>

      <a href="#" id="forgotPasswordLink" class="forgot-password-link">{{str.login_password_forgot}}</a>

      <p id="adminContactMessage" class="admin-message"></p>

      <button type="submit" class="btn-style-one">{{str.login_submit}}</button>

      <a href="/" class="back-to-site">&larr; {{str.login_back}}</a>
    </form>

  </div>

  <div class="image">
    <a href="/" aria-label="{{str.login_back}}">
      <img src="/assets/img/logo.svg" alt="Sécurité Sociale de l’Alimentation, Pays de Morlaix">
    </a>
  </div>
</div>