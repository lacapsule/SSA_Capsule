<!-- templates/components/dashboard/dash_agenda.tpl.php -->
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
        <div class="event-block" 
             data-event-id="{{id}}"
             data-date="{{date}}" 
             data-time="{{time}}"
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

<script>
(function() {
  const modal = document.getElementById('modal-event');
  const btnOpen = document.getElementById('btn-new-event');
  const btnCancel = document.getElementById('btn-cancel');
  const btnClose = document.getElementById('btn-close-modal');

  btnOpen?.addEventListener('click', () => modal.showModal());
  btnCancel?.addEventListener('click', () => modal.close());
  btnClose?.addEventListener('click', () => modal.close());

  // ✅ Positionner les événements correctement
  function positionEvents() {
    const container = document.querySelector('.agenda-calendar');
    const daysGrid = document.querySelector('.days-grid');
    if (!daysGrid || !container) return;

    // Récupérer le lundi de la semaine (ISO format)
    const mondayISO = container.dataset.monday;
    if (!mondayISO) return;

    // Créer un map des dates -> colonnes
    const dayHeaders = document.querySelectorAll('.day-header[data-date]');
    const dateToColumn = {};
    dayHeaders.forEach((header, index) => {
      dateToColumn[header.dataset.date] = index + 2; // +2 car colonne 1 = heures
    });

    // Positionner chaque événement
    const events = document.querySelectorAll('.event-block');
    events.forEach(event => {
      const eventDate = event.dataset.date;
      const eventTime = event.dataset.time;
      const duration = parseFloat(event.dataset.duration) || 1;
      
      // Trouver la colonne (jour de la semaine)
      const column = dateToColumn[eventDate];
      if (!column) {
        console.warn('Date non trouvée dans la semaine:', eventDate);
        event.style.display = 'none';
        return;
      }
      
      // Calculer la position horaire (en minutes depuis minuit)
      const [hours, minutes] = eventTime.split(':').map(Number);
      const totalMinutes = hours * 60 + minutes;
      const hourHeight = 60; // 60px par heure
      const topPosition = (totalMinutes / 60) * hourHeight;
      const height = Math.max(duration * hourHeight, 30); // min 30px
      
      // Appliquer le positionnement
      event.style.cssText = `
        grid-column: ${column};
        top: ${topPosition}px;
        height: ${height}px;
        display: block;
      `;
    });
  }

  // Positionner au chargement
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', positionEvents);
  } else {
    positionEvents();
  }

  // Click sur événement
  document.addEventListener('click', function(e) {
    const eventBlock = e.target.closest('.event-block');
    if (eventBlock) {
      const id = eventBlock.dataset.eventId;
      const title = eventBlock.querySelector('.event-title')?.textContent;
      console.log('Événement cliqué:', { id, title });
      // TODO: Ouvrir modal de détails/édition
    }
  });
})();
</script>
