<article class="details" itemscope itemtype="https://schema.org/Article">
  <div class="details-media">
    {{#hasCarousel}}
    <div class="article-carousel" data-article-carousel role="region" aria-label="Galerie de médias">
      <div class="article-carousel__track" data-carousel-track role="group" aria-roledescription="carousel">
        {{#each medias}}
        <figure class="article-carousel__slide" role="group" aria-roledescription="slide" aria-label="Média {{@index}}">
          {{#isVideo}}
          <video src="{{src}}" controls playsinline preload="metadata" aria-label="Vidéo"></video>
          {{/isVideo}}
          {{^isVideo}}
          <img src="{{src}}" alt="Illustration de l'article" loading="lazy" itemprop="image">
          {{/isVideo}}
        </figure>
        {{/each}}
      </div>
      <button type="button" class="article-carousel__nav article-carousel__nav--prev" data-carousel-prev aria-label="Média précédent" aria-controls="carousel-track">‹</button>
      <button type="button" class="article-carousel__nav article-carousel__nav--next" data-carousel-next aria-label="Média suivant" aria-controls="carousel-track">›</button>
      <div class="article-carousel__dots" data-carousel-dots role="tablist" aria-label="Navigation des médias">
        {{#each medias}}
        <button type="button" class="article-carousel__dot" data-carousel-dot aria-label="Aller au média {{@index}}" role="tab" aria-selected="false"></button>
        {{/each}}
      </div>
    </div>
    {{/hasCarousel}}

    {{^hasCarousel}}
      {{#article.image}}
      <img src="{{article.image}}" alt="Image principale de l'article : {{article.title}}" fetchpriority="high" itemprop="image">
      {{/article.image}}
      {{^article.image}}
      <img src="/assets/img/banner.webp" alt="Image principale de l'article : {{article.title}}" fetchpriority="high" itemprop="image">
      {{/article.image}}
    {{/hasCarousel}}
  </div>

  <header class="heading">
    <div class="details-content">
      <h1 itemprop="headline">{{article.title}}</h1>

      {{#article.summary}}
      <p class="summary" itemprop="description">{{article.summary}}</p>
      {{/article.summary}}
    </div>
    <div class="details-desc">
      {{#article.description}}
      <div class="description" itemprop="articleBody">{{article.description}}</div>
      {{/article.description}}

      <dl>
        {{#article.date}}
        <dt class="visually-hidden">Date de publication</dt>
        <dd class="date">
          <time datetime="{{article.date}}{{#article.time}}T{{article.time}}{{/article.time}}" itemprop="datePublished">
            Date : {{article.date}}{{#article.time}} à {{article.time}}{{/article.time}}
          </time>
        </dd>
        {{/article.date}}

        {{#article.author}}
        <dt class="visually-hidden">Auteur</dt>
        <dd itemprop="author" itemscope itemtype="https://schema.org/Person">
          <span itemprop="name">Article rédigé par : {{article.author}}</span>
        </dd>
        {{/article.author}}

        {{#article.place}}
        <dt class="visually-hidden">Lieu</dt>
        <dd class="lieu" itemprop="location" itemscope itemtype="https://schema.org/Place">
          <span itemprop="name">Lieu : {{article.place}}</span>
        </dd>
        {{/article.place}}
      </dl>
    </div>
  </header>
</article>

<script type="module" src="/modules/article/carousel.js"></script>