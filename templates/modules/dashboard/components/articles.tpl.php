<section class="dash-article-page">
  <div class="dash-article-header">
    <h1>Gestion des articles</h1>
    <div class="dash-article-actions">
      <a href="/dashboard/articles/create" class="btn btn-primary">Créer un article</a>
    </div>
  </div>

  {{^articles}}
  <div class="dash-article-container">
    <p class="dash-article-empty">Aucun article trouvé.</p>
  </div>
  {{/articles}}

  {{#articles}}
  <div class="dash-article-container">
    <table class="dash-article-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Titre</th>
          <th>Résumé</th>
          <th>Date</th>
          <th>Auteur</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        {{#each articles}}
        <tr>
          <td data-label="ID">{{id}}</td>
          <td data-label="Titre" class="col-title">{{titre}}</td>
          <td data-label="Résumé" class="col-meta">{{resume}}</td>
          <td data-label="Date">{{date}}</td>
          <td data-label="Auteur">{{author}}</td>
          <td data-label="Actions">
            <div class="article-actions">
              <a href="{{editUrl}}" class="btn btn-primary">Modifier</a>
              <form action="{{deleteUrl}}" method="post" onsubmit="return confirm('Supprimer cet article ?');">
                {{{csrf_input}}}
                <button type="submit" class="btn btn-secondary">Supprimer</button>
              </form>
            </div>
          </td>
        </tr>
        {{/each}}
      </tbody>
    </table>
  </div>
  {{/articles}}

</section>