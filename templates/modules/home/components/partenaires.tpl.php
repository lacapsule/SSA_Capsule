<section class="partners section" aria-labelledby="partners-title">
  <div class="contain">
    <div class="title">
      <div class="section-title padd-15">
        <h2 id="partners-title">{{str.partners_title}}</h2>
      </div>
    </div>
    <div class="partenaires" role="list">
      <div class="item">
        {{#each partenaires}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener" role="listitem" aria-label="Visiter le site de {{name}} (nouvelle fenêtre)">
          <img src="{{logo}}" alt="Logo de {{name}}" loading="lazy">
        </a>
        {{/each}}
      </div>
    </div>
  </div>
</section>

<section class="sponsor section" aria-labelledby="sponsors-title">
  <div class="contain">
    <div class="title">
      <div class="section-title padd-15">
        <h2 id="sponsors-title">Financeurs</h2>
      </div>
    </div>
    <div class="financeurs" role="list">
      <div class="item">
        {{#each financeurs}}
        <a href="{{url}}" target="_blank" rel="noreferrer noopener" role="listitem" aria-label="Visiter le site de {{name}} (nouvelle fenêtre)">
          <img src="{{logo}}" alt="Logo de {{name}}" loading="lazy">
        </a>
        {{/each}}
      </div>
    </div>
  </div>
</section>