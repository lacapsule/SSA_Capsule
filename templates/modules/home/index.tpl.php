<section id="hero-anchor" class="hero" aria-labelledby="hero-title">
  <div class="overlay" aria-hidden="true">
    <img src="/assets/img/banner.webp" style="no-repeat center/cover" alt="" fetchpriority="high" role="presentation">
  </div>
  <h1 id="hero-title">{{str.hero_title}}</h1>
  <p class="slogan">{{str.hero_slogan}}</p>
  <nav class="cta-buttons" aria-label="Actions principales">
    <a href="/projet" class="btn-style-one">{{str.hero_cta_more}}</a>
    <a href="/#contact" class="btn-style-one">{{str.hero_cta_contact}}</a>
  </nav>
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
