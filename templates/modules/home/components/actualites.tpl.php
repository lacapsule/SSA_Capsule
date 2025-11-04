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
            <img src="assets/img/test_fond.jpg" alt="actu">
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
</section>