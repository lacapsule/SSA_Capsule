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
            {{else}}
            <img src="/assets/img/logoSSA.png" alt="{{titre}}">
            {{/if}}
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
    {{#pagination.showPagination}}
    <nav class="gallery-pagination">
      {{#pagination.hasFirst}}
      <a href="?page={{pagination.first}}#actu" class="page-link first-page" aria-label="Première page">&laquo; Première</a>
      {{/pagination.hasFirst}}

      {{#pagination.hasPrev}}
      <a href="?page={{pagination.prev}}#actu" class="page-link prev-page" aria-label="Page précédente">&lsaquo;</a>
      {{/pagination.hasPrev}}

      <div class="pagination-pages">
        {{#each pagination.pages}}
        <a href="?page={{number}}#actu" class="page-link{{#isCurrent}} is-active{{/isCurrent}}">{{number}}</a>
        {{/each}}
      </div>

      <span class="pagination-info">Page {{pagination.current}} / {{pagination.total}}</span>

      {{#pagination.hasNext}}
      <a href="?page={{pagination.next}}#actu" class="page-link next-page" aria-label="Page suivante">&rsaquo;</a>
      {{/pagination.hasNext}}

      {{#pagination.hasLast}}
      <a href="?page={{pagination.last}}#actu" class="page-link last-page" aria-label="Dernière page">Dernière &raquo;</a>
      {{/pagination.hasLast}}
    </nav>
    {{/pagination.showPagination}}
</section>