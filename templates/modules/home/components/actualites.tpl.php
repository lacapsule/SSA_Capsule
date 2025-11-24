<section class="actu section" id="actu">
  <div class="contain">
    <div class="title">
      <div class="section-title">
        <h2>{{str.news_title}}</h2>
      </div>
    </div>
    <div class="row">
      {{#each articles}}
        <div class="actu-item-inner shadow-dark">
          <div class="actu-img">
            <img src="{{image}}" alt="{{titre}}">
            <div class="actu-date">{{date_actu}}</div>
          </div>
          <div class="actu-info">
            <h4 class="actu-title">{{titre}}</h4>
            <p class="actu-description">{{resume}}</p>
            <a href="/article/{{id}}" class="btn-style-two">{{str.read_more}}</a>
          </div>
        </div>
      {{/each}}
    </div>
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
</section>