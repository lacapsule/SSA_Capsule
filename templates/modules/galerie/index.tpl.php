<section class="gallery-header section">
   <div class="title">
      <div class="section-title">
          <h1>Gallerie de la SSA</h1>
      </div>
    </div>
</section>

<section class="gallery">
  <div class="contain">
    <div class="gallery-track">
      {{#each pictures}}
      <div class="card">
        <div class="card-image-wrapper">
          <img src="{{src}}" alt="{{alt}}" class="gallery-img">
        </div>
      </div>
      {{/each}}
    </div>
  </div>
</section>

{{#pagination.showPagination}}
<nav class="gallery-pagination">
  {{#pagination.hasFirst}}
  <a href="?page={{pagination.first}}" class="page-link first-page" aria-label="Première page">&laquo; Première</a>
  {{/pagination.hasFirst}}

  {{#pagination.hasPrev}}
  <a href="?page={{pagination.prev}}" class="page-link prev-page" aria-label="Page précédente">&lsaquo;</a>
  {{/pagination.hasPrev}}

  <div class="pagination-pages">
    {{#each pagination.pages}}
    <a href="?page={{number}}" class="page-link{{#isCurrent}} is-active{{/isCurrent}}">{{number}}</a>
    {{/each}}
  </div>

  <span class="pagination-info">Page {{pagination.current}} / {{pagination.total}}</span>

  {{#pagination.hasNext}}
  <a href="?page={{pagination.next}}" class="page-link next-page" aria-label="Page suivante">&rsaquo;</a>
  {{/pagination.hasNext}}

  {{#pagination.hasLast}}
  <a href="?page={{pagination.last}}" class="page-link last-page" aria-label="Dernière page">Dernière &raquo;</a>
  {{/pagination.hasLast}}
</nav>
{{/pagination.showPagination}}

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
  <span class="close">&times;</span>
  <img class="lightbox-image" src="" alt="">
  <a class="prev">&#10094;</a>
  <a class="next">&#10095;</a>
</div>