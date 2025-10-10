<section class="agenda">
  <h1>Mon agenda</h1>

  <button id="btn-open-modal">Nouv. Event</button>

  <div id="modalCreateEvent" {{#modal_open}}{{/modal_open}}>
    <form method="POST" action="{{create_url}}">
      {{{csrf_input}}}

      <h2>Créer un Event</h2>
      <button type="button" id="closeModal">X</button>

      <label>Intitulé</label>
      <input type="text" name="titre" placeholder="..." required>

      <label>Date</label>
      <input type="date" name="date" required>

      <label>Horaire</label>
      <input type="time" name="heure" required>

      <label>Lieu</label>
      <input type="text" name="lieu" placeholder="..." required>

      <!-- Optionnel: durée en heures (1.5 = 1h30) -->
      <label>Durée (h)</label>
      <input type="number" name="duree" min="0.5" step="0.5" value="1.0">

      <button type="submit">Créer l'événement</button>
    </form>
  </div>

  <div class="navigation">
    <a href="{{prev_week_url}}">&lt; Semaine précédente</a>
    <span>Semaine du {{week_label}}</span>
    <a href="{{next_week_url}}">Semaine suivante &gt;</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>Heure</th>
        {{#days}}
          <th>{{name}}<br>({{date}})</th>
        {{/days}}
      </tr>
    </thead>
    <tbody>
      {{#hours}}
        <tr>
          <td class="time-slot">{{display}}</td>
          {{#days}}
            <td {{#has_events}}class="event-cell"{{/has_events}}>
              {{#events}}
                <div class="{{css_class}}" style="height: {{height_px}}px; top: {{top_px}}px;">
                  <strong>{{title}}</strong><br>
                  ({{time}}, {{duration}}h)<br>
                  {{location}}

                  <div class="detail" hidden>
                    <h2>Détails de l'événement</h2>
                    <p>Intitulé : {{title}}</p>
                    <p>Date : {{date}}</p>
                    <p>Horaire : {{time}}</p>
                    <p>Lieu : {{location}}</p>
                    <button type="button" id="closeDetail">Fermer</button>
                    <button type="button" id="suppr-btn">Supprimer</button>
                  </div>
                </div>
              {{/events}}
            </td>
          {{/days}}
        </tr>
      {{/hours}}
    </tbody>
  </table>
</section>
