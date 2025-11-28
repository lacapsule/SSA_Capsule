<section class="partners section">
  <div class="contain">
    <div class="title">
      <div class="section-title padd-15">
        <h2>{{str.partners_title}}</h2>
      </div>
    </div>
    <div class="partenaires">
      <div class="item">
        {{#each partenaires}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener">
          <img src="{{logo}}" alt="{{name}}" loading="lazy">
        </a>
        {{/each}}
      </div>
    </div>
  </div>
</section>

<section class="sponsor section">
  <div class="contain">
    <div class="title">
      <div class="section-title padd-15">
        <h2>Financeurs</h2>
      </div>
    </div>
    <div class="financeurs">
      <div class="item">
        {{#each financeurs}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener">
          <img src="{{logo}}" alt="{{name}}" loading="lazy">
        </a>
        {{/each}}
      </div>
    </div>
  </div>
</section>