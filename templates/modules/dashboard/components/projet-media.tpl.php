<section class="dash-section-page projet-media">
  <div class="dashboard-content">
    <div class="dash-components-header">
      <h1>Médias de la page Projet</h1>
    </div>

    <form action="/dashboard/projet" method="post" enctype="multipart/form-data" class="dash-components-form">
      {{{csrf_input}}}
      <div class="media-grid">
        <div class="media-card">
          <h3>Bannière (hero)</h3>
          <div class="media-preview">
            {{#media.hero}}
            <img src="{{media.hero}}" alt="Hero actuel">
            {{/media.hero}}
            {{^media.hero}}
            <p class="calendar-details-empty">Aucune image</p>
            {{/media.hero}}
          </div>
          <p class="hint">Image large (fond), JPEG/PNG/WebP recommandé.</p>
          <input type="file" name="hero" id="hero" accept="image/*">
        </div>

        <div class="media-card">
          <h3>Illustration haute</h3>
          <div class="media-preview">
            {{#media.illustration_top}}
            <img src="{{media.illustration_top}}" alt="Illustration haute">
            {{/media.illustration_top}}
            {{^media.illustration_top}}
            <p class="calendar-details-empty">Aucune image</p>
            {{/media.illustration_top}}
          </div>
          <p class="hint">Visuel décoratif (dessin ou photo).</p>
          <input type="file" name="illustration_top" id="illustration_top" accept="image/*">
        </div>

        <div class="media-card">
          <h3>Illustration basse</h3>
          <div class="media-preview">
            {{#media.illustration_bottom}}
            <img src="{{media.illustration_bottom}}" alt="Illustration basse">
            {{/media.illustration_bottom}}
            {{^media.illustration_bottom}}
            <p class="calendar-details-empty">Aucune image</p>
            {{/media.illustration_bottom}}
          </div>
          <p class="hint">Visuel bas de page (logo, photo, vidéo).</p>
          <input type="file" name="illustration_bottom" id="illustration_bottom" accept="image/*">
        </div>
      </div>

      <div class="dash-components-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</section>

