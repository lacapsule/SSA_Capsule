<section id="news" class="news">
  <h2>{{str.news_title}}</h2>

  <!--
  <div class="filters">
    <button class="filter-btn" data-filter="all">{{str.news_filter_all}}</button>
    <button class="filter-btn" data-filter="sante">{{str.news_filter_sante}}</button>
    <button class="filter-btn" data-filter="environnement">{{str.news_filter_env}}</button>
    <button class="filter-btn" data-filter="mobilisation">{{str.news_filter_mob}}</button>
  </div>
  -->

  <div class="news-grid">
    {{#each articles}}
      <article class="news-item" data-category="{{category}}">
        <h3>{{titre}}</h3>
        <p>{{resume}}</p>
        <img src="{{image}}" alt="illustration article">
        <a href="/article/{{id}}" class="read-more">
          {{str.read_more}}
        </a>
      </article>
    {{/each}}
  </div>
</section>
