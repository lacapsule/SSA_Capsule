<section class="hero">
  <div class="overlay">
    <img src="/assets/img/banner.webp" style="no-repeat center/cover" alt="banner">
  </div>
  <h1>{{str.hero_title}}</h1>
  <p class="slogan">{{str.hero_slogan}}</p>
  <div class="cta-buttons">
    <a href="/projet" class="btn-style-one">{{str.hero_cta_more}}</a>
    <a href="/#contact" class="btn-style-one">{{str.hero_cta_contact}}</a>
  </div>
</section>
<!-- Composants du module A PROPOS -->
{{> component:home/components/apropos }}
<!-- Composants du module ACTUALITER -->
{{> component:home/components/actualites }}
<!-- Composants du module EVENEMENT -->
{{> component:home/components/evenement }}
<!-- Composants du module PARTENAIRES -->
{{> component:home/components/partenaires }}
<!-- Composants du module CONTACT -->
{{> component:home/components/contact }}
