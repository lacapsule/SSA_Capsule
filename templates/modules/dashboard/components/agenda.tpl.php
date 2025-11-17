<section class="agenda-calendar" data-monday="{{monday_iso}}">
  <h1>Mon agenda</h1>
  <button type="button" id="btn-new-event" class="btn-primary">+ Nouvel événement</button>

  <nav class="week-nav">
    <a href="{{prev_week_url}}" class="nav-btn">‹ Précédent</a>
    <span class="week-label">{{week_label}}</span>
    <a href="{{next_week_url}}" class="nav-btn">Suivant ›</a>
  </nav>

  {{#events_count}}
  <p class="event-count">{{events_count}} événement(s) cette semaine</p>
  {{/events_count}}


  <section class="calendar-days">
    <section class="calendar-top-bar">
      {{#each week_dates}}
      <span class="top-bar-days">{{name}} <br> {{date}} </span>
      {{/each}}
    </section>

    <section class="calendar-week">
      {{#each week_dates}}
      <div class="calendar-day inactive">
        <span class="calendar-date">{{date}}</span>
        {{/each}}
        {{#each events}}
        <span class="calendar-task">{{title}} à {{time}}</span>
        {{/each}}
        {{#location}}
        <span class="calendar-location">{{location}}</span>
        {{/location}}
      </div>
      
      


      <!-- Calendrier semaine -->
      <div class="calendar-week">
        <!-- En-têtes des jours -->
        <div class="calendar-header">
          <div class="time-col-header"></div>
          {{#each week_dates}}
          <div class="day-header" data-date="{{iso}}">
            <div class="day-name">{{name}}</div>
            <div class="day-date">{{date}}</div>
          </div>
          {{/each}}
        </div>

        <!-- Grille horaire -->
        <div class="calendar-body">
          <!-- Colonne des heures -->
          <div class="time-column">
            <div class="time-slot">00:00</div>
            <div class="time-slot">01:00</div>
            <div class="time-slot">02:00</div>
            <div class="time-slot">03:00</div>
            <div class="time-slot">04:00</div>
            <div class="time-slot">05:00</div>
            <div class="time-slot">06:00</div>
            <div class="time-slot">07:00</div>
            <div class="time-slot">08:00</div>
            <div class="time-slot">09:00</div>
            <div class="time-slot">10:00</div>
            <div class="time-slot">11:00</div>
            <div class="time-slot">12:00</div>
            <div class="time-slot">13:00</div>
            <div class="time-slot">14:00</div>
            <div class="time-slot">15:00</div>
            <div class="time-slot">16:00</div>
            <div class="time-slot">17:00</div>
            <div class="time-slot">18:00</div>
            <div class="time-slot">19:00</div>
            <div class="time-slot">20:00</div>
            <div class="time-slot">21:00</div>
            <div class="time-slot">22:00</div>
            <div class="time-slot">23:00</div>
          </div>

          <!-- Zone des événements -->
          <div class="days-grid">
            {{#each events}}
            <div class="event-block" data-event-id="{{id}}" data-date="{{date}}" data-time="{{time}}"
              data-duration="{{duration}}">
              <div class="event-time">{{time}}</div>
              <div class="event-title">{{title}}</div>
              {{#location}}
              <div class="event-location">📍 {{location}}</div>
              {{/location}}
            </div>
            {{/each}}
          </div>
        </div>
      </div>

      {{^events_count}}
      <div class="empty-state">
        <p>Aucun événement prévu cette semaine</p>
        <p class="hint">Cliquez sur "+ Nouvel événement" pour créer votre premier événement</p>
      </div>
      {{/events_count}}

      <!-- Modal création -->
      <dialog id="modal-event">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Nouvel événement</h2>
            <button type="button" id="btn-close-modal" class="btn-close">×</button>
          </div>

          <form method="POST" action="{{create_url}}" class="event-form">
            {{{csrfInput}}}

            <div class="form-group">
              <label for="titre">Titre</label>
              <input type="text" id="titre" name="titre" placeholder="Réunion d'équipe" required autofocus>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>
              </div>

              <div class="form-group">
                <label for="heure">Heure</label>
                <input type="time" id="heure" name="heure" value="09:00" required>
              </div>
            </div>

            <div class="form-group">
              <label for="lieu">Lieu</label>
              <input type="text" id="lieu" name="lieu" placeholder="Salle de réunion (optionnel)">
            </div>

            <div class="form-group">
              <label for="duree">Durée</label>
              <select id="duree" name="duree">
                <option value="0.25">15 minutes</option>
                <option value="0.5">30 minutes</option>
                <option value="1" selected>1 heure</option>
                <option value="1.5">1h30</option>
                <option value="2">2 heures</option>
                <option value="3">3 heures</option>
                <option value="4">4 heures</option>
              </select>
            </div>

            <div class="modal-footer">
              <button type="button" id="btn-cancel" class="btn-secondary">Annuler</button>
              <button type="submit" class="btn-primary">Créer l'événement</button>
            </div>
          </form>
        </div>
      </dialog>
    </section>