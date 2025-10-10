<section class="partenaires">
  <h2>{{str.partners_title}}</h2>

  <div class="icons partners">
    <h3>Partenaires</h3>
    <div class="logos">
      {{#each partenaires}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener">
          <img src="{{logo}}" alt="{{name}}">
        </a>
      {{/each}}
    </div>
  </div>

  <div class="separator"></div>

  <div class="icons financeurs">
    <h3>Financeurs</h3>
    <div class="logos">
      {{#each financeurs}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener">
          <img src="{{logo}}" alt="{{name}}">
        </a>
      {{/each}}
    </div>
  </div>
</section>
