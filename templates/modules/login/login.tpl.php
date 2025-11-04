<link rel="stylesheet" href="assets/css/login.css">


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

      <input type="password" name="password" id="password" required autocomplete="current-password"
        placeholder="{{str.login_password}}">

      <a href="#" id="forgotPasswordLink" class="forgot-password-link">{{str.login_password_forgot}}</a>

      <p id="adminContactMessage" class="admin-message"></p>

      <button type="submit" class="btn-style-one">{{str.login_submit}}</button>
    </form>

  </div>

  <div class="image">
    <img src="/assets/img/logo.svg">
  </div>

</div>