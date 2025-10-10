<section class="article-form">
  <h1>{{title}}</h1>

  {{#flash}}
    <p class="notice notice--success">{{.}}</p>
  {{/flash}}

  {{#errors._global}}
    <p class="notice notice--error">{{.}}</p>
  {{/errors._global}}

  <form method="post" action="{{action}}" novalidate>
    {{{csrf_input}}}

    <div class="form-row">
      <label for="titre">Titre</label>
      <input id="titre" name="titre" type="text" value="{{article.titre}}" required />
      {{#errors.titre}}<p class="field-error">{{.}}</p>{{/errors.titre}}
    </div>

    <div class="form-row">
      <label for="resume">Résumé</label>
      <input id="resume" name="resume" type="text" value="{{article.resume}}" />
      {{#errors.resume}}<p class="field-error">{{.}}</p>{{/errors.resume}}
    </div>

    <div class="form-row">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="6">{{article.description}}</textarea>
      {{#errors.description}}<p class="field-error">{{.}}</p>{{/errors.description}}
    </div>

    <div class="form-grid">
      <div class="form-row">
        <label for="date_article">Date (YYYY-MM-DD)</label>
        <input id="date_article" name="date_article" type="date" value="{{article.date_article}}" />
        {{#errors.date_article}}<p class="field-error">{{.}}</p>{{/errors.date_article}}
      </div>

      <div class="form-row">
        <label for="hours">Heure (HH:MM)</label>
        <input id="hours" name="hours" type="time" value="{{article.hours}}" />
        {{#errors.hours}}<p class="field-error">{{.}}</p>{{/errors.hours}}
      </div>
    </div>

    <div class="form-row">
      <label for="lieu">Lieu</label>
      <input id="lieu" name="lieu" type="text" value="{{article.lieu}}" />
      {{#errors.lieu}}<p class="field-error">{{.}}</p>{{/errors.lieu}}
    </div>

    <div class="actions">
      <button type="submit" class="btn primary">Enregistrer</button>
      <a href="/dashboard/articles" class="btn">Annuler</a>
    </div>
  </form>
</section>
