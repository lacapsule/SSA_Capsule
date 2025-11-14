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
    <nav class="gallery-pagination">
      {{#pagination.hasPrev}}
      <a href="?page={{pagination.prev}}#actu" class="prev-page">&laquo; Précédent</a>
      {{/pagination.hasPrev}}

      {{#pagination.show_pagination_info}}
      <span>Page {{pagination.current}} / {{pagination.total}}</span>
      {{/pagination.show_pagination_info}}

      {{#pagination.hasNext}}
      <a href="?page={{pagination.next}}#actu" class="next-page">Suivant &raquo;</a>
      {{/pagination.hasNext}}
    </nav>
</section>