<section class="dash-section-page">
      <div class="dashboard-content">
  <div class="dash-components-header">
    <h1>Gestion de la gallerie</h1>
    </div>
  </div>

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

<nav class="gallery-pagination">
  {{#pagination.hasPrev}}
  <a href="?page={{pagination.prev}}" class="prev-page">&laquo; Précédent</a>
  {{/pagination.hasPrev}}

  <span>Page {{pagination.current}} / {{pagination.total}}</span>

  {{#pagination.hasNext}}
  <a href="?page={{pagination.next}}" class="next-page">Suivant &raquo;</a>
  {{/pagination.hasNext}}
</nav>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
  <span class="close">&times;</span>
  <img class="lightbox-image" src="" alt="">
  <a class="prev">&#10094;</a>
  <a class="next">&#10095;</a>
</div>