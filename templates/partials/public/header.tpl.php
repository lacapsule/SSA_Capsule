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

    <ul role="menubar">
      <li role="none"><a href="/#hero-anchor" role="menuitem">{{str.nav_home}}</a></li>
      <li role="none"><a href="/#about-anchor" role="menuitem">{{str.nav_apropos}}</a></li>
      <li role="none"><a href="/#actu-anchor" role="menuitem">{{str.nav_actualites}}</a></li>
      <li role="none"><a href="/#event-anchor" role="menuitem">{{str.nav_agenda}}</a></li>
      <li role="none"><a href="/projet" role="menuitem">{{str.nav_project}}</a></li>
      <li role="none"><a href="/galerie" role="menuitem">{{str.nav_galerie}}</a></li>
      <li role="none"><a href="/#contact-anchor" role="menuitem">{{str.nav_contact}}</a></li>
      <li role="none" aria-label="Sélection de la langue">
        <a href="?lang=fr" aria-label="Français" lang="fr"><img class="flag" src="/assets/icons/fr.svg" alt="Français" width="24" height="18"></a>
        <span aria-hidden="true"> • </span>
        <a href="?lang=br" aria-label="Breton" lang="br"><img class="flag" src="/assets/icons/br.svg" alt="Breton" width="24" height="18"></a>
      </li>
    </ul>
    
    {{#isAuthenticated}}
    <div class="user" role="group" aria-label="Actions utilisateur">
      <ul role="menubar">
        <li role="none"><a class="icons" href="/dashboard" role="menuitem" aria-label="Tableau de bord">
          <img src="/assets/icons/dashboard.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
        <li role="none"><a class="icons" href="/logout" role="menuitem" aria-label="Déconnexion">
          <img src="/assets/icons/logout.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
      </ul>
    </div>
    {{/isAuthenticated}}

    {{^isAuthenticated}}
    <div class="user" role="group" aria-label="Connexion">
      <ul role="menubar">
        <li role="none"><a class="icons" href="/login" role="menuitem" aria-label="Se connecter">
          <img src="/assets/icons/login.svg" alt="" width="20" height="20" aria-hidden="true">
        </a></li>
      </ul>
    </div>
    {{/isAuthenticated}}
  </nav>
</header>