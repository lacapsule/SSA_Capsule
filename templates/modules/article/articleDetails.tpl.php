<section class="details">
  <div class="heading">
    <h1>{{article.title}}</h1>

    {{#article.date}}
      <p class="date">Date : {{article.date}}{{#article.time}} à {{article.time}}{{/article.time}}</p>
    {{/article.date}}

    {{#article.author}}
      <p>Article rédigé par : {{article.author}}</p>
    {{/article.author}}

    {{#article.place}}
      <p class="lieu">Lieu : {{article.place}}</p>
    {{/article.place}}
  </div>

  <div class="separateur"></div>

  {{#article.summary}}
    <h3>{{article.summary}}</h3>
  {{/article.summary}}

  {{#article.description}}
    <p class="description">{{article.description}}</p>
  {{/article.description}}

  <div class="separateur"></div>

  {{#article.image}}
    <img src="{{article.image}}" alt="illustration">
  {{/article.image}}
  {{^article.image}}
    <img src="/assets/img/banner.webp" alt="illustration">
  {{/article.image}}
</section>
