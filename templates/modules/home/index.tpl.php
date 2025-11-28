<section id="hero-anchor" class="hero">
  <div class="overlay">
    <img src="/assets/img/banner.webp" style="no-repeat center/cover" alt="banner" fetchpriority="high">
  </div>
  <h1>{{str.hero_title}}</h1>
  <p class="slogan">{{str.hero_slogan}}</p>
  <div class="cta-buttons">
    <a href="/projet" class="btn-style-one">{{str.hero_cta_more}}</a>
    <a href="/#contact" class="btn-style-one">{{str.hero_cta_contact}}</a>
  </div>
</section>
<!-- Composants du module A PROPOS -->
 <div id="about-anchor"></div>
 {{> component:home/components/apropos }}
 
 <!-- Composants du module ACTUALITER -->
 <div id="actu-anchor" style=height:40px></div>
{{> component:home/components/actualites }}

<!-- Composants du module EVENEMENT -->
<div id="event-anchor" style=height:40px></div>
{{> component:home/components/evenement }}

<!-- Composants du module PARTENAIRES -->
 <div id="partner-anchor" style=height:40px></div>
{{> component:home/components/partenaires }}

<!-- Composants du module CONTACT -->
 <div id="contact-anchor"></div>
{{> component:home/components/contact }}
