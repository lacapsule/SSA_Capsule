<section class="articles">
  <h1>Gestion des articles</h1>
  <p><a href="{{createUrl}}">Créer un article</a></p>

  {{^articles}}
    <p>Aucun article trouvé.</p>
  {{/articles}}

  {{#articles}}
    <div class="wrapper">
      <table>
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
              <td>{{id}}</td>
              <td>{{titre}}</td>
              <td>{{resume}}</td>
              <td>{{date}}</td>
              <td>{{author}}</td>
              <td class="buttons">
                <a href="{{editUrl}}">Modifier</a>
                <form action="{{deleteUrl}}" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet article ?');">
                  {{{csrf_input}}}
                  <button type="submit" style="background-color:#ED7F7F;">Supprimer</button>
                </form>
              </td>
            </tr>
          {{/each}}
        </tbody>
      </table>
    </div>
  {{/articles}}
</section>
