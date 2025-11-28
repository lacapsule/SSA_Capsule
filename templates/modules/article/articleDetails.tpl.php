<section class="details">
  <div class="details-media">
    {{#hasCarousel}}
    <div class="article-carousel" data-article-carousel>
      <div class="article-carousel__track" data-carousel-track>
        {{#each medias}}
        <figure class="article-carousel__slide">
          {{#isVideo}}
          <video src="{{src}}" controls playsinline preload="metadata"></video>
          {{/isVideo}}
          {{^isVideo}}
          <img src="{{src}}" alt="illustration" loading="lazy">
          {{/isVideo}}
        </figure>
        {{/each}}
      </div>
      <button class="article-carousel__nav article-carousel__nav--prev" data-carousel-prev aria-label="Media précédent">‹</button>
      <button class="article-carousel__nav article-carousel__nav--next" data-carousel-next aria-label="Media suivant">›</button>
      <div class="article-carousel__dots" data-carousel-dots>
        {{#each medias}}
        <button class="article-carousel__dot" data-carousel-dot aria-label="Aller au média"></button>
        {{/each}}
      </div>
    </div>
    {{/hasCarousel}}

    {{^hasCarousel}}
      {{#article.image}}
      <img src="{{article.image}}" alt="illustration" fetchpriority="high">
      {{/article.image}}
      {{^article.image}}
      <img src="/assets/img/banner.webp" alt="illustration" fetchpriority="high">
      {{/article.image}}
    {{/hasCarousel}}
  </div>

  <div class="heading">
    <div class="details-content">
      <h1>{{article.title}}</h1>

      {{#article.summary}}
      <h3>{{article.summary}}</h3>
      {{/article.summary}}
    </div>
    <div class="details-desc">
      {{#article.description}}
      <p class="description">{{article.description}}</p>
      {{/article.description}}

      {{#article.date}}
      <p class="date">Date : {{article.date}}{{#article.time}} à {{article.time}}{{/article.time}}</p>
      {{/article.date}}

      {{#article.author}}
      <p>Article rédigé par : {{article.author}}</p>
      {{/article.author}}

      {{#article.place}}
      <p class="lieu">Lieu : {{article.place}}</p>
      {{/article.place}}
    </div>
  </div>
</section>

<script type="module" src="/modules/article/carousel.js"></script>