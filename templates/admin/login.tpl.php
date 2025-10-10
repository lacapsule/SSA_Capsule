<h1>{{title}}</h1>

<section class="login">
  {{#error}}
    <p class="form-error">{{.}}</p>
  {{/error}}

  <form method="post" action="{{action}}" novalidate>
    {{{csrf_input}}}

    <label for="username">{{str.login_username}}</label>
    <input id="username"
           name="username"
           type="text"
           value="{{prefill.username}}"
           required
           autocomplete="username" />

    <label for="password">{{str.login_password}}</label>
    <input id="password"
           name="password"
           type="password"
           required
           autocomplete="current-password" />

    <button type="submit" class="btn primary">{{str.login_submit}}</button>
  </form>
</section>
