<section class="details">
  <div class="details-media">
    {{#article.hasCarousel}}
    <div class="article-carousel" data-article-carousel>
      <div class="article-carousel__track" data-carousel-track>
        {{#article.images}}
        <figure class="article-carousel__slide">
          <img src="{{.}}" alt="illustration">
        </figure>
        {{/article.images}}
      </div>
      <button class="article-carousel__nav article-carousel__nav--prev" data-carousel-prev aria-label="Image précédente">‹</button>
      <button class="article-carousel__nav article-carousel__nav--next" data-carousel-next aria-label="Image suivante">›</button>
      <div class="article-carousel__dots" data-carousel-dots>
        {{#article.images}}
        <button class="article-carousel__dot" data-carousel-dot aria-label="Aller à l'image"></button>
        {{/article.images}}
      </div>
    </div>
    {{/article.hasCarousel}}

    {{^article.hasCarousel}}
      {{#article.image}}
      <img src="{{article.image}}" alt="illustration">
      {{/article.image}}
      {{^article.image}}
      <img src="/assets/img/banner.webp" alt="illustration">
      {{/article.image}}
    {{/article.hasCarousel}}
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