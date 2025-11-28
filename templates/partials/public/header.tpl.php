<header role="banner">
  <input type="checkbox" id="menu-toggle" aria-label="Menu de navigation" aria-expanded="false">
  <label for="menu-toggle" class="hamburger" aria-hidden="true">
    <span class="line"></span>
    <span class="line"></span>
    <span class="line"></span>
  </label>

  <nav class="navbar" role="navigation" aria-label="Navigation principale">
    <a href="/" class="logo-link" aria-label="Retour à l'accueil - {{str.nav_title}}">
      <img src="/assets/img/logo.svg" alt="{{str.nav_title}}" class="logo" fetchpriority="high" width="150" height="50">
    </a>

    <ul>
      <li><a href="/#hero-anchor">{{str.nav_home}}</a></li>
      <li><a href="/#about-anchor">{{str.nav_apropos}}</a></li>
      <li><a href="/#actu-anchor">{{str.nav_actualites}}</a></li>
      <li><a href="/#event-anchor">{{str.nav_agenda}}</a></li>
      <li><a href="/projet">{{str.nav_project}}</a></li>
      <li><a href="/galerie">{{str.nav_galerie}}</a></li>
      <li><a href="/#contact-anchor">{{str.nav_contact}}</a></li>
      <li aria-label="Sélection de la langue">
        <a href="?lang=fr" aria-label="Français" lang="fr"><img class="flag" src="/assets/icons/fr.svg" alt="Français" width="24" height="18"></a>
        <span aria-hidden="true"> • </span>
        <a href="?lang=br" aria-label="Breton" lang="br"><img class="flag" src="/assets/icons/br.svg" alt="Breton" width="24" height="18"></a>
      </li>
    </ul>
    
    {{#isAuthenticated}}
    <div class="user" aria-label="Actions utilisateur">
      <ul>
        <li><a class="icons" href="/dashboard" aria-label="Tableau de bord">
          <img src="/assets/icons/dashboard.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
        <li><a class="icons" href="/logout" aria-label="Déconnexion">
          <img src="/assets/icons/logout.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
      </ul>
    </div>
    {{/isAuthenticated}}

    {{^isAuthenticated}}
    <div class="user" aria-label="Connexion">
      <ul>
        <li><a class="icons" href="/login" aria-label="Se connecter">
          <img src="/assets/icons/login.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
      </ul>
    </div>
    {{/isAuthenticated}}
  </nav>
</header>