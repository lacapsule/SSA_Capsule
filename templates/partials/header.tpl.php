<header id="header">
  <a href="/" class="logo-link">
    <img src="/assets/img/logo.svg" alt="{{str.nav_title}}" class="logo">
  </a>

  <div class="hamburger">
    <span class="line"></span><span class="line"></span><span class="line"></span><span class="line"></span>
  </div>

  <nav class="navbar">
    <ul>
      <li><a href="/">{{str.nav_home}}</a></li>
      <li><a href="/#about">{{str.nav_apropos}}</a></li>
      <li><a href="/#news">{{str.nav_actualites}}</a></li>
      <li><a href="/#agenda">{{str.nav_agenda}}</a></li>
      <li><a href="/projet">{{str.nav_project}}</a></li>
      <li><a href="/galerie">{{str.nav_galerie}}</a></li>
      <li><a href="/#contact">{{str.nav_contact}}</a></li>

      <li>
        <form method="get" action="">
            <select name="lang" id="lang-switch">
            {{#each languages}}
              <option value="{{code}}" {{#selected}}selected{{/selected}}>{{label}}</option>
            {{/each}}
          </select>
        </form>
      </li>

      {{#isAuthenticated}}
        <li><a class="icons" href="/dashboard/account">
          <img src="/assets/icons/dashboard.svg" alt="Dashboard icon">
        </a></li>
        <li><a class="icons" href="/logout">
          <img src="/assets/icons/logout.svg" alt="Logout icon">
        </a></li>
      {{/isAuthenticated}}

      {{^isAuthenticated}}
        <li><a class="icons" href="/login">
          <img src="/assets/icons/login.svg" alt="Login icon">
        </a></li>
      {{/isAuthenticated}}
    </ul>
  </nav>
</header>
