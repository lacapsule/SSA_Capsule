<header id="header">
 
  <input type="checkbox" id="menu-toggle">
  <label for="menu-toggle" class="hamburger">
    <span class="line"></span>
    <span class="line"></span>
    <span class="line"></span>
  </label>

  <nav class="navbar">
 <a href="/" class="logo-link">
    <img src="/assets/img/logo.svg" alt="{{str.nav_title}}" class="logo">
  </a>

    <ul>
      <li><a href="/">{{str.nav_home}}</a></li>
      <li><a href="/#about">{{str.nav_apropos}}</a></li>
      <li><a href="/#actu">{{str.nav_actualites}}</a></li>
      <li><a href="/#evenement">{{str.nav_agenda}}</a></li>
      <li><a href="/projet">{{str.nav_project}}</a></li>
      <li><a href="/galerie">{{str.nav_galerie}}</a></li>
      <li><a href="/#contact">{{str.nav_contact}}</a></li>
      <li>
        <a href="?lang=fr"><img class="flag" src="/assets/icons/fr.svg" alt="FR"></a> •
        <a href="?lang=br"><img class="flag" src="/assets/icons/br.svg" alt="BR"></a>
      </li>

      <!-- FIX: Marche pas (souci niveau JS à voir plus tard) -->
      <!-- <form method="get" action="" id="lang-form"> -->
      <!--   <select name="lang" id="lang-switch" aria-label="Choisir la langue"> -->
      <!--     {{#each languages}} -->
      <!--       <option value="{{code}}" {{#selected}}selected{{/selected}}>{{label}}</option> -->
      <!--     {{/each}} -->
      <!--   </select> -->
      <!-- </form> -->
    </ul>
    {{#isAuthenticated}}
    <div class="user">
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
    </div>
  </nav>
</header>