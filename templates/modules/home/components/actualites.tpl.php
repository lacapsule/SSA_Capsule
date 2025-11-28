<section class="actu section" id="actu" aria-labelledby="actu-title">
  <div class="contain">
    <div class="title">
      <div class="section-title">
        <h2 id="actu-title">{{str.news_title}}</h2>
      </div>
    </div>
    <div class="row">
      {{#each articles}}
        <article class="actu-item-inner shadow-dark" itemscope itemtype="https://schema.org/Article">
          <div class="actu-img">
              <img src="{{image}}" alt="Image de l'article : {{titre}}" loading="lazy" itemprop="image">
            {{else}}
            <img src="/assets/img/logoSSA.png" alt="Image de l'article : {{titre}}" loading="lazy" itemprop="image">
            {{/if}}
            <time class="actu-date" datetime="{{date_actu}}" itemprop="datePublished">{{date_actu}}</time>
          </div>
          <div class="actu-info">
            <h3 class="actu-title" itemprop="headline">{{titre}}</h3>
            <p class="actu-description" itemprop="description">{{resume}}</p>
            <a href="/article/{{id}}" class="btn-style-two" aria-label="Lire l'article : {{titre}}" itemprop="url">{{str.read_more}}</a>
          </div>
        </article>
      {{/each}}
    </div>
  </div>
  {{#pagination.showPagination}}
  <nav class="gallery-pagination" aria-label="Pagination des actualités">
    {{#pagination.hasFirst}}
    <a href="?page={{pagination.first}}#actu" class="page-link first-page" aria-label="Première page">&laquo; <span class="pagi-hide">Première</span></a>
    {{/pagination.hasFirst}}

    {{#pagination.hasPrev}}
    <a href="?page={{pagination.prev}}#actu" class="page-link prev-page" aria-label="Page précédente">&lsaquo;</a>
    {{/pagination.hasPrev}}

    <div class="pagination-pages">
      {{#each pagination.pages}}
      <a href="?page={{number}}#actu" class="page-link{{#isCurrent}} is-active{{/isCurrent}}" aria-label="Page {{number}}"{{#isCurrent}} aria-current="page"{{/isCurrent}}>{{number}}</a>
      {{/each}}
    </div>

    <span class="pagination-info" aria-live="polite">Page {{pagination.current}} / {{pagination.total}}</span>

    {{#pagination.hasNext}}
    <a href="?page={{pagination.next}}#actu" class="page-link next-page" aria-label="Page suivante">&rsaquo;</a>
    {{/pagination.hasNext}}

    {{#pagination.hasLast}}
    <a href="?page={{pagination.last}}#actu" class="page-link last-page" aria-label="Dernière page"><span class="pagi-hide">Dernière</span> &raquo;</a>
    {{/pagination.hasLast}}
  </nav>
  {{/pagination.showPagination}}
  </section>