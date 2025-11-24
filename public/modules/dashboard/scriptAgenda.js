const calendarDates = document.querySelector('.calendar-dates');
const monthYear = document.getElementById('month-year');
const prevMonthBtn = document.getElementById('prev-month');
const nextMonthBtn = document.getElementById('next-month');
const showDailyViewBtn = document.getElementById('showDailyViewBtn');
const closeDailyPanelBtn = document.getElementById('closeDailyPanelBtn');
const toggleThemeBtn = document.getElementById('toggleThemeBtn');
const weekdays = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

let currentDate = new Date();
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();
let selectedDay = currentDate.getDate();

function parseDateFR(dateStr) {
  const [year, month, day] = dateStr.split('-');
  return `${day} ${months[parseInt(month) - 1]} ${year}`;
}

// Charger le thème depuis localStorage
const savedTheme = localStorage.getItem('theme') || 'dark';
if (savedTheme === 'light') {
  document.querySelector('.global-container').classList.add('light-theme');
  toggleThemeBtn.textContent = '☀️';
} else {
  toggleThemeBtn.textContent = '🌙';
}

// Gérer la classe .daily-panel-hidden
function updateDailyPanelClass() {
  const dailyPanel = document.getElementById('daily-panel');
  const globalContainer = document.querySelector('.global-container');
  if (dailyPanel.style.display === 'none') {
    globalContainer.classList.add('daily-panel-hidden');
  } else {
    globalContainer.classList.remove('daily-panel-hidden');
  }
}

function renderWeekdays() {
  const container = document.querySelector('.calendar-weekdays');
  container.innerHTML = '';
  weekdays.forEach(day => {
    const div = document.createElement('div');
    div.textContent = day;
    container.appendChild(div);
  });
}

async function renderCalendar(month, year) {
  calendarDates.innerHTML = '';
  monthYear.textContent = `${months[month]} ${year}`;
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  const events = await fetchEventDays(month, year);

  // Afficher les jours vides avant le premier jour
  for (let i = 0; i < firstDay; i++) {
    const blank = document.createElement('div');
    calendarDates.appendChild(blank);
  }

  // Afficher les jours du mois
  for (let i = 1; i <= daysInMonth; i++) {
    const day = document.createElement('div');
    day.textContent = i;
    day.classList.add('calendar-day');

    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
    const dayOfWeek = new Date(year, month, i).getDay();

    // Ajouter des points pour les événements
    const dayEvents = events.filter(event => 
      dateStr >= event.date && (!event.date_fin || dateStr <= event.date_fin)
    );
    dayEvents.forEach(event => {
      const dot = document.createElement('span');
      // Appliquer .event-dot pour le premier jour d'un événement multi-jours, .multi-day-dot pour les jours suivants
      dot.className = (event.date_fin && event.date !== event.date_fin && dateStr === event.date) ? 'first-multi-day-dot' : 
                      (event.date_fin && event.date !== event.date_fin && dateStr > event.date) ? 'multi-day-dot' : 
                      'event-dot';
      dot.innerHTML = '&nbsp;'; // Ajouter un espace insécable pour éviter les bugs bizarres
      day.appendChild(dot);
    });

    day.addEventListener('click', () => {
      selectedDay = i;
      refreshDate(i, month, year);
      fetchEventList(i, month, year);
      // si le panneau est ouvert, le mettre à jour
      if (document.getElementById('daily-panel').style.display !== 'none') {
        renderDailyPanel(i, month, year);
      }
      document.querySelectorAll('.calendar-dates div').forEach(d => {
        d.style.border = '1px solid #00000010';
        d.classList.remove('selected-day');
      });
      day.style.border = '1px solid #00000065';
      day.classList.add('selected-day');
    });

    day.addEventListener('mouseover', () => {
      day.style.backgroundColor = '#f0f0f0';
    });

    day.addEventListener('mouseout', () => {
      day.style.backgroundColor = '';
    });

    if (i === currentDate.getDate() && month === currentDate.getMonth() && year === currentDate.getFullYear()) {
      day.style.textDecoration = 'underline';
      day.style.fontWeight = 'bold';
    }

    calendarDates.appendChild(day);
  }
}

async function fetchEventList(day, month, year) {
  const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  try {
    const response = await fetch(`script.php?date=${date}`);
    if (!response.ok) throw new Error('Erreur réseau');
    const events = await response.json();

    const eventList = document.getElementById('event_list');
    const noEvents = document.getElementById('noEvents');
    const eventListDate = document.getElementById('event_list_date');

    eventListDate.textContent = `${day} ${months[month]} ${year}`;
    eventList.innerHTML = '';

    if (events.error || events.length === 0) {
      noEvents.style.display = 'block';
      eventList.style.display = 'none';
      document.getElementById('noEventsDate').textContent = `${day} ${months[month]} ${year}`;
    } else {
      noEvents.style.display = 'none';
      eventList.style.display = 'block';
      events.forEach(event => {
        event.heure_debut = event.heure_debut ? event.heure_debut.split(':').slice(0, 2).join(':') : '';
        event.heure_fin = event.heure_fin ? event.heure_fin.split(':').slice(0, 2).join(':') : null;
        const eventDiv = document.createElement('div');
        eventDiv.classList.add('event-item');
        eventDiv.innerHTML = `
          <div class="event-content">
            <p><strong>${event.heure_debut} - ${event.heure_fin || 'Non spécifié'}</strong></p>
            <p>${event.description || 'Aucune description'}</p>
            ${event.date_fin && event.date !== event.date_fin ? `<p><em>(Du ${parseDateFR(event.date)} au ${parseDateFR(event.date_fin)})</em></p>` : ''}
          </div>
          <div class="event-actions">
            <span class="edit-icon" data-id="${event.id}" title="Modifier l'événement">✏️</span>
            <span class="delete-icon" data-id="${event.id}" title="Supprimer l'événement">🗑️</span>
          </div>
        `;
        eventList.appendChild(eventDiv);
      });

      document.querySelectorAll('.edit-icon').forEach(icon => {
        icon.addEventListener('click', () => {
          const eventId = icon.getAttribute('data-id');
          const event = events.find(e => e.id == eventId);
          if (event) {
            openEditModal(event, day, month, year);
          }
        });
      });

      document.querySelectorAll('.delete-icon').forEach(icon => {
        icon.addEventListener('click', async () => {
          const eventId = icon.getAttribute('data-id');
          if (confirm('Voulez-vous vraiment supprimer cet événement ?')) {
            try {
              const response = await fetch('script.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: eventId })
              });
              const result = await response.json();
              if (result.success) {
                await renderCalendar(currentMonth, currentYear);
                await fetchEventList(selectedDay, currentMonth, currentYear);
                await renderDailyPanel(selectedDay, currentMonth, currentYear);
              } else {
                console.error('Erreur:', result.error);
                alert('Erreur lors de la suppression de l\'événement : ' + result.error);
              }
            } catch (error) {
              console.error('Erreur lors de la suppression:', error);
              alert('Erreur lors de la suppression de l\'événement');
            }
          }
        });
      });
    }
    return events;
  } catch (error) {
    console.error('Erreur lors de la récupération des événements:', error);
    const eventList = document.getElementById('event_list');
    eventList.innerHTML = '<p>Erreur lors du chargement des événements.</p>';
    document.getElementById('noEvents').style.display = 'none';
    return [];
  }
}

async function renderDailyPanel(day, month, year) {
  const panel = document.getElementById('daily-panel');
  const panelDate = document.getElementById('daily-panel-date');
  const dailyCalendar = document.getElementById('daily-calendar');
  panelDate.textContent = `Vue journalière : ${day} ${months[month]} ${year}`;

  dailyCalendar.innerHTML = '';
  const events = await fetchEventList(day, month, year);

  for (let hour = 7; hour <= 20; hour++) {
    const hourDiv = document.createElement('div');
    hourDiv.classList.add('daily-hour');
    hourDiv.innerHTML = `<span class="hour-label">${String(hour).padStart(2, '0')}:00</span>`;
    dailyCalendar.appendChild(hourDiv);
  }

  const eventContainer = document.createElement('div');
  eventContainer.classList.add('daily-event-container');
  dailyCalendar.appendChild(eventContainer);

  events.forEach(event => {
    const startHour = event.heure_debut ? parseInt(event.heure_debut.split(':')[0]) : 7;
    const startMinutes = event.heure_debut ? parseInt(event.heure_debut.split(':')[1]) : 0;
    let endHour = event.heure_fin ? parseInt(event.heure_fin.split(':')[0]) : startHour;
    let endMinutes = event.heure_fin ? parseInt(event.heure_fin.split(':')[1]) : startMinutes;

    if (startHour < 7 || startHour > 20) return;
    if (endHour > 20) {
      endHour = 20;
      endMinutes = 0;
    }

    const pixelsPerHour = 40;
    const startPosition = (startHour - 7) * pixelsPerHour + (startMinutes / 60) * pixelsPerHour;
    const duration = event.heure_fin
      ? (endHour - startHour) + (endMinutes - startMinutes) / 60
      : 1;
    const eventDiv = document.createElement('div');
    eventDiv.classList.add('daily-event');
    eventDiv.style.top = `${startPosition}px`;
    eventDiv.style.height = `${duration * pixelsPerHour}px`;
    
    eventDiv.innerHTML = `  
      <div class="event-content">
        <strong>${event.heure_debut || 'Non spécifié'} - ${event.heure_fin || 'Non spécifié'}</strong>: 
        ${event.description || 'Aucune description'}
        ${event.date_fin && event.date !== event.date_fin ? `<br><em>(Du ${parseDateFR(event.date)} au ${parseDateFR(event.date_fin)})</em>` : ''} 
      </div>
      <div class="event-actions">
        <span class="edit-icon" data-id="${event.id}" title="Modifier l'événement">✏️</span>
        <span class="delete-icon" data-id="${event.id}" title="Supprimer l'événement">🗑️</span>
      </div>
    `;
    eventContainer.appendChild(eventDiv);
  });

  document.querySelectorAll('.daily-event .edit-icon').forEach(icon => {
    icon.addEventListener('click', () => {
      const eventId = icon.getAttribute('data-id');
      const event = events.find(e => e.id == eventId);
      if (event) {
        openEditModal(event, day, month, year);
      }
    });
  });

  document.querySelectorAll('.daily-event .delete-icon').forEach(icon => {
    icon.addEventListener('click', async () => {
      const eventId = icon.getAttribute('data-id');
      if (confirm('Voulez-vous vraiment supprimer cet événement ?')) {
        try {
          const response = await fetch('script.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: eventId })
          });
          const result = await response.json();
          if (result.success) {
            await renderCalendar(currentMonth, currentYear);
            await fetchEventList(selectedDay, currentMonth, currentYear);
            await renderDailyPanel(selectedDay, currentMonth, currentYear);
          } else {
            console.error('Erreur:', result.error);
            alert('Erreur lors de la suppression de l\'événement : ' + result.error);
          }
        } catch (error) {
          console.error('Erreur lors de la suppression:', error);
          alert('Erreur lors de la suppression de l\'événement');
        }
      }
    });
  });

  panel.style.display = 'block';
  updateDailyPanelClass(); // Mettre à jour la classe après avoir affiché le panneau
}

prevMonthBtn.addEventListener('click', () => {
  currentMonth--;
  if (currentMonth < 0) {
    currentMonth = 11;
    currentYear--;
  }
  renderCalendar(currentMonth, currentYear);
});

nextMonthBtn.addEventListener('click', () => {
  currentMonth++;
  if (currentMonth > 11) {
    currentMonth = 0;
    currentYear++;
  }
  renderCalendar(currentMonth, currentYear);
});

async function fetchEventDays(month, year) {
  const mois = `${year}-${String(month + 1).padStart(2, '0')}`;
  try {
    const response = await fetch(`script.php?mois=${mois}`);
    if (!response.ok) throw new Error('Erreur réseau');
    return await response.json();
  } catch (error) {
    console.error('Erreur lors de la récupération des jours d\'événements:', error);
    return [];
  }
}

function openEventModal(day, month, year) {
  const modalDate = document.getElementById('modal-date');
  modalDate.textContent = `Ajouter un événement pour le ${day} ${months[month]} ${year}`;
  const modalDateContainer = document.getElementById('modal-date-container');
  const modalDateEnd = document.getElementById('modal-date-end');
  const formattedDay = String(day).padStart(2, '0');
  const formattedMonth = String(month + 1).padStart(2, '0');
  modalDateContainer.value = `${year}-${formattedMonth}-${formattedDay}`;
  modalDateEnd.value = `${year}-${formattedMonth}-${formattedDay}`;
  document.getElementById('event-id').value = '';
  document.getElementById('start').value = '';
  document.getElementById('end').value = '';
  document.getElementById('notes').value = '';
  document.getElementById('submitEventBtn').textContent = 'Envoyer';
  document.getElementById('event-modal').style.display = 'block';
}

function openEditModal(event, day, month, year) {
  const modalDate = document.getElementById('modal-date');
  modalDate.textContent = `Modifier l'événement du ${day} ${months[month]} ${year}`;
  const modalDateContainer = document.getElementById('modal-date-container');
  const modalDateEnd = document.getElementById('modal-date-end');
  const formattedDay = String(day).padStart(2, '0');
  const formattedMonth = String(month + 1).padStart(2, '0');
  modalDateContainer.value = event.date;
  modalDateEnd.value = event.date_fin || '';
  document.getElementById('event-id').value = event.id;
  document.getElementById('start').value = event.heure_debut ? event.heure_debut.split(':').slice(0, 2).join(':') : '';
  document.getElementById('end').value = event.heure_fin ? event.heure_fin.split(':').slice(0, 2).join(':') : '';
  document.getElementById('notes').value = event.description || '';
  document.getElementById('submitEventBtn').textContent = 'Modifier';
  document.getElementById('event-modal').style.display = 'block';
}

document.getElementById('eventForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(document.getElementById('eventForm'));
  const eventId = document.getElementById('event-id').value;
  const method = eventId ? 'PUT' : 'POST';
  
  try {
    const response = await fetch('script.php', {
      method: method,
      body: formData
    });
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('event-modal').style.display = 'none';
      document.getElementById('event-indicator').style.display = 'block';
      document.getElementById('event-indicator').querySelector('.event-text').textContent = eventId ? 'Événement modifié' : 'Événement ajouté';
      setTimeout(() => {
        document.getElementById('event-indicator').style.display = 'none';
      }, 3000);
      
      await renderCalendar(currentMonth, currentYear);
      await fetchEventList(selectedDay, currentMonth, currentYear);
      await renderDailyPanel(selectedDay, currentMonth, currentYear);
    } else {
      console.error('Erreur:', result.error);
      alert(`Erreur lors de ${eventId ? 'la modification' : 'l\'ajout'} de l\'événement : ${result.error}`);
    }
  } catch (error) {
    console.error(`Erreur lors de ${eventId ? 'la modification' : 'la soumission'}:`, error);
    alert(`Erreur lors de ${eventId ? 'la modification' : 'la soumission'} de l\'événement`);
  }
});

function refreshDate(selectedDay, selectedMonth, selectedYear) {
  document.getElementById('noEventsDate').textContent = `${selectedDay} ${months[selectedMonth]} ${selectedYear}`;
  document.getElementById('event_list_date').textContent = `${selectedDay} ${months[selectedMonth]} ${selectedYear}`;
}

showDailyViewBtn.addEventListener('click', () => {
  if (document.getElementById('daily-panel').style.display === 'none'){
    renderDailyPanel(selectedDay, currentMonth, currentYear);  
  } else {
    document.getElementById('daily-panel').style.display = 'none';
    updateDailyPanelClass(); // Mettre à jour la classe après avoir fermé le panneau
  }
});

closeDailyPanelBtn.addEventListener('click', () => {
  document.getElementById('daily-panel').style.display = 'none';
  updateDailyPanelClass(); // Mettre à jour la classe après avoir fermé le panneau
});

document.getElementById('closeModalBtn').onclick = function() {
  document.getElementById('event-modal').style.display = 'none';
};

window.onclick = function(event) {
  if (event.target == document.getElementById('event-modal')) {
    document.getElementById('event-modal').style.display = 'none';
  }
};

refreshDate(currentDate.getDate(), currentMonth, currentYear);
fetchEventList(currentDate.getDate(), currentMonth, currentYear);
renderDailyPanel(currentDate.getDate(), currentMonth, currentYear);

// Initialiser la classe .daily-panel-hidden au chargement
updateDailyPanelClass();

document.getElementById('addEventBtn').addEventListener('click', () => {
  openEventModal(selectedDay, currentMonth, currentYear);
});

document.getElementById('printCalendarBtn').addEventListener('click', () => {
  window.alert('Prévilégiez une impression en format paysage pour une meilleure lisibilité.');
  window.print();
});

toggleThemeBtn.addEventListener('click', () => {
  const globalContainer = document.querySelector('.global-container');
  globalContainer.classList.toggle('light-theme');
  const isLightTheme = globalContainer.classList.contains('light-theme');
  toggleThemeBtn.textContent = isLightTheme ? '☀️' : '🌙';
  localStorage.setItem('theme', isLightTheme ? 'light' : 'dark');
});

renderWeekdays();
renderCalendar(currentMonth, currentYear);