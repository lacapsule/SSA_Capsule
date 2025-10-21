<section class="gallery" id="gallery">
  <h2>Galerie</h2>

  <div class="gallery-grid">
    {{#each pictures}}
      <picture>
        <img
          src="{{src}}"
          alt="{{alt}}"
          loading="lazy"
          decoding="async"
          width="200"
          height="200">
      </picture>
    {{/each}}
  </div>

  <nav class="gallery-pagination" style="margin-top:2rem;display:flex;justify-content:center;gap:1rem;">
    {{#pagination.hasPrev}}
      <a href="?page={{pagination.prev}}" class="prev-page">&larr; Précédent</a>
    {{/pagination.hasPrev}}

    <span>Page {{pagination.current}} / {{pagination.total}}</span>

    {{#pagination.hasNext}}
      <a href="?page={{pagination.next}}" class="next-page">Suivant &rarr;</a>
    {{/pagination.hasNext}}
  </nav>
</section>

<div class="galleryOverlay" id="image-overlay" tabindex="-1">
  <span class="close-btn" id="close-overlay">&times;</span>
  <button id="prev-img">&#8592;</button>
  <img id="overlay-img" src="" alt="Image en grand">
  <button id="next-img">&#8594;</button>
</div>
