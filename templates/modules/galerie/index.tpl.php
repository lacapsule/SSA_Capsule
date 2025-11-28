<section class="gallery-header section" aria-labelledby="gallery-title">
   <div class="title">
      <div class="section-title">
          <h1 id="gallery-title">Galerie de la SSA</h1>
      </div>
    </div>
</section>

<section class="gallery" aria-label="Galerie de photos">
    <div class="gallery-track">
      {{#each pictures}}
      <div class="card">
        <div class="card-image-wrapper">
          <button type="button" class="gallery-img-btn" data-lightbox="{{src}}" data-lightbox-alt="{{alt}}" aria-label="Ouvrir l'image : {{alt}}">
            <img src="{{src}}" alt="{{alt}}" class="gallery-img" loading="lazy">
          </button>
        </div>
      </div>
      {{/each}}
    </div>
  {{#pagination.showPagination}}
  <nav class="gallery-pagination" aria-label="Pagination de la galerie">
    {{#pagination.hasFirst}}
    <a href="?page={{pagination.first}}" class="page-link first-page" aria-label="Première page">&laquo; <span class="visually-hidden">Première</span></a>
    {{/pagination.hasFirst}}
  
    {{#pagination.hasPrev}}
    <a href="?page={{pagination.prev}}" class="page-link prev-page" aria-label="Page précédente">&lsaquo;</a>
    {{/pagination.hasPrev}}
  
    <div class="pagination-pages">
      {{#each pagination.pages}}
      <a href="?page={{number}}" class="page-link{{#isCurrent}} is-active{{/isCurrent}}" aria-label="Page {{number}}"{{#isCurrent}} aria-current="page"{{/isCurrent}}>{{number}}</a>
      {{/each}}
    </div>
  
    <span class="pagination-info" aria-live="polite">Page {{pagination.current}} / {{pagination.total}}</span>
  
    {{#pagination.hasNext}}
    <a href="?page={{pagination.next}}" class="page-link next-page" aria-label="Page suivante">&rsaquo;</a>
    {{/pagination.hasNext}}
  
    {{#pagination.hasLast}}
    <a href="?page={{pagination.last}}" class="page-link last-page" aria-label="Dernière page"><span class="visually-hidden">Dernière</span> &raquo;</a>
    {{/pagination.hasLast}}
  </nav>
  {{/pagination.showPagination}}
</section>


<!-- Lightbox -->
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-labelledby="lightbox-title" aria-hidden="true">
  <button type="button" class="close" aria-label="Fermer la lightbox">&times;</button>
  <img class="lightbox-image" src="" alt="" id="lightbox-image">
  <button type="button" class="prev" aria-label="Image précédente">&#10094;</button>
  <button type="button" class="next" aria-label="Image suivante">&#10095;</button>
  <div class="visually-hidden" id="lightbox-title">Image de la galerie</div>
</div>