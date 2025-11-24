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

{{#pagination.show}}
<div class="pagination-info">
  <span>Page {{pagination.current}} / {{pagination.total}}</span>
  {{#pagination.hasPrev}}
  <span class="pagination-prev-label">Page précédente : {{pagination.prev}}</span>
  {{/pagination.hasPrev}}
</div>
<nav class="gallery-pagination" aria-label="Pagination">
  {{#pagination.hasPrev}}
  <a href="{{pagination.firstUrl}}" class="page-link" aria-label="Première page">&laquo;&laquo;</a>
  <a href="{{pagination.prevUrl}}" class="page-link" aria-label="Page précédente">&laquo;</a>
  {{/pagination.hasPrev}}
  {{^pagination.hasPrev}}
  <span class="page-link is-disabled" aria-hidden="true">&laquo;&laquo;</span>
  <span class="page-link is-disabled" aria-hidden="true">&laquo;</span>
  {{/pagination.hasPrev}}

  {{#pagination.showFirstEdge}}
  <a href="{{pagination.firstUrl}}" class="page-link">1</a>
  {{/pagination.showFirstEdge}}
  {{#pagination.showGapBefore}}
  <span class="page-link ellipsis">…</span>
  {{/pagination.showGapBefore}}

  {{#pagination.pages}}
    {{#isCurrent}}
    <span class="page-link is-active" aria-current="page">{{number}}</span>
    {{/isCurrent}}
    {{^isCurrent}}
    <a href="{{url}}" class="page-link">{{number}}</a>
    {{/isCurrent}}
  {{/pagination.pages}}

  {{#pagination.showGapAfter}}
  <span class="page-link ellipsis">…</span>
  {{/pagination.showGapAfter}}
  {{#pagination.showLastEdge}}
  <a href="{{pagination.lastUrl}}" class="page-link">{{pagination.total}}</a>
  {{/pagination.showLastEdge}}

  {{#pagination.hasNext}}
  <a href="{{pagination.nextUrl}}" class="page-link" aria-label="Page suivante">&raquo;</a>
  <a href="{{pagination.lastUrl}}" class="page-link" aria-label="Dernière page">&raquo;&raquo;</a>
  {{/pagination.hasNext}}
  {{^pagination.hasNext}}
  <span class="page-link is-disabled" aria-hidden="true">&raquo;</span>
  <span class="page-link is-disabled" aria-hidden="true">&raquo;&raquo;</span>
  {{/pagination.hasNext}}
</nav>
{{/pagination.show}}

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
  <span class="close">&times;</span>
  <img class="lightbox-image" src="" alt="">
  <a class="prev">&#10094;</a>
  <a class="next">&#10095;</a>
</div>