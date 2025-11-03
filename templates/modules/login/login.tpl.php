<link rel="stylesheet" href="assets/css/login.css">
<section id="login" class="login-page">
  <div class="login-form">
    <h1>{{title}}</h1>

    {{#error}}
    <p class="form-error">{{.}}</p>
    {{/error}}

    <form method="post" action="{{action}}" novalidate>
      {{{csrf_input}}}

      <div class="txtb">
        <input type="username" name="username" id="username" value="{{prefill.username}}" required autocomplete="username">
        <span data-placeholder="{{str.login_username}}"></span>
      </div>

      <div class="txtb password-wrapper">
        <input type="password" name="password" id="password" required autocomplete="current-password">
        <span data-placeholder="{{str.login_password}}"></span>
        <span id="togglePassword" class="toggle-password">&#128065;</span>
      </div>

      <input type="submit" class="btn-style-one" value="{{str.login_submit}}">
    </form>
  </div>
</section>

https://www.perplexity.ai/search/ajoute-le-fait-que-ont-puisse-Wy2X8Nq0QcCM8yCJ2wqR.A#7