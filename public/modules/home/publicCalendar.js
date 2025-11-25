const API_URL = '/dashboard/agenda/api/events';

/**
 * Formate une date locale au format YYYY-MM-DD (sans décalage UTC).
 */
function formatLocalISODate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function cloneDate(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function addDays(date, days) {
  const next = new Date(date);
  next.setDate(next.getDate() + days);
  return next;
}

function startOfWeek(date) {
  const clone = cloneDate(date);
  const day = clone.getDay(); // 0 (dimanche) -> 6 (samedi)
  const diff = (day === 0 ? -6 : 1 - day);
  clone.setDate(clone.getDate() + diff);
  return cloneDate(clone);
}

function parseDate(value) {
  if (!value) return new Date();
  const normalised = value.replace(' ', 'T');
  const parsed = new Date(normalised);
  if (Number.isNaN(parsed.getTime())) {
    return new Date(value);
  }
  return parsed;
}

function formatDate(date, options = {}) {
  return date.toLocaleDateString('fr-FR', {
    ...options,
  });
}

function formatTime(date) {
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

function normalizeEvents(rawEvents) {
  return rawEvents.map((event) => {
    const startDate = parseDate(event.start);
    const endDate = parseDate(event.end);
    return {
      ...event,
      startDate,
      endDate,
      dateKey: formatLocalISODate(startDate),
      timeLabel: formatTime(startDate),
      monthIndex: startDate.getMonth(),
      year: startDate.getFullYear(),
    };
  });
}

export function initPublicCalendar() {
  const modal = document.getElementById('public-calendar-modal');
  if (!modal) {
    return;
  }

  const grid = modal.querySelector('#public-calendar-grid');
  const labelEl = modal.querySelector('#public-calendar-label');
  const details = modal.querySelector('#public-calendar-details');
  const loadingEl = modal.querySelector('#public-calendar-loading');
  const errorEl = modal.querySelector('#public-calendar-error');
  const viewButtons = Array.from(modal.querySelectorAll('[data-calendar-view]'));
  const navButtons = Array.from(modal.querySelectorAll('[data-calendar-nav]'));

  const defaultDetailsText = details?.dataset.empty || '';
  const detailTitle = details?.dataset.title || '';

  const state = {
    currentDate: new Date(),
    currentView: 'week',
    cache: new Map(),
    selectedChip: null,
  };

  function setGridClass(modifier) {
    grid.className = `calendar-grid ${modifier}`;
  }

  function showLoading(isLoading) {
    if (loadingEl) {
      loadingEl.hidden = !isLoading;
    }
  }

  function showError(message) {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.hidden = message === '';
  }

  function resetDetails() {
    if (!details) return;
    details.innerHTML = `<p class="calendar-details-empty">${defaultDetailsText}</p>`;
    state.selectedChip = null;
  }

  function selectEvent(event, chip) {
    if (!details) return;
    if (state.selectedChip) {
      state.selectedChip.classList.remove('is-selected');
    }
    chip.classList.add('is-selected');
    state.selectedChip = chip;

    const dateLabel = formatDate(event.startDate, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
    const timeLabel = `${formatTime(event.startDate)} — ${formatTime(event.endDate)}`;

    details.innerHTML = `
      <h3>${event.title}</h3>
      <p><strong>${dateLabel}</strong></p>
      <p>${timeLabel}</p>
      ${event.description ? `<p>${event.description}</p>` : ''}
    `;
  }

  async function loadEvents(startStr, endStr) {
    const cacheKey = `${startStr}_${endStr}`;
    if (state.cache.has(cacheKey)) {
      return state.cache.get(cacheKey);
    }
    try {
      const response = await fetch(`${API_URL}?start=${startStr}&end=${endStr}`);
      if (!response.ok) {
        throw new Error('Erreur API');
      }
      const data = await response.json();
      const normalized = normalizeEvents(data);
      state.cache.set(cacheKey, normalized);
      return normalized;
    } catch (err) {
      console.error(err);
      showError('Impossible de charger les événements pour le moment.');
      return [];
    }
  }

  function buildEventChip(event) {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'calendar-event-chip';
    chip.style.backgroundColor = event.color || '#1d72b8';
    chip.textContent = `${event.timeLabel} • ${event.title}`;
    chip.addEventListener('click', (e) => {
      e.stopPropagation();
      selectEvent(event, chip);
    });
    return chip;
  }

  function renderWeek(range, events) {
    setGridClass('calendar-grid--week');
    grid.innerHTML = '';
    const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    for (let i = 0; i < 7; i += 1) {
      const date = addDays(range.startDate, i);
      const dayEvents = events.filter((event) => event.dateKey === formatLocalISODate(date));
      const column = document.createElement('div');
      column.className = 'calendar-day';

      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.innerHTML = `
        <span class="calendar-day-name">${days[i]}</span>
        <span class="calendar-day-number">${date.getDate()}</span>
      `;
      column.appendChild(header);

      const eventContainer = document.createElement('div');
      eventContainer.className = 'calendar-events';

      if (dayEvents.length === 0) {
        eventContainer.innerHTML = '<p class="calendar-details-empty">—</p>';
      } else {
        dayEvents.forEach((event) => {
          const chip = buildEventChip(event);
          eventContainer.appendChild(chip);
        });
      }

      column.appendChild(eventContainer);
      grid.appendChild(column);
    }
  }

  function renderMonth(range, events) {
    setGridClass('calendar-grid--month');
    grid.innerHTML = '';
    const totalDays = 42;
    for (let i = 0; i < totalDays; i += 1) {
      const date = addDays(range.startDate, i);
      const dayEvents = events.filter((event) => event.dateKey === formatLocalISODate(date));

      const cell = document.createElement('div');
      cell.className = 'calendar-day';
      if (date.getMonth() !== range.currentMonth) {
        cell.classList.add('other-month');
      }

      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.innerHTML = `
        <span class="calendar-day-name">${date.toLocaleDateString('fr-FR', { weekday: 'short' })}</span>
        <span class="calendar-day-number">${date.getDate()}</span>
      `;
      cell.appendChild(header);

      const eventContainer = document.createElement('div');
      eventContainer.className = 'calendar-events';

      if (dayEvents.length === 0) {
        eventContainer.innerHTML = '<p class="calendar-details-empty">—</p>';
      } else {
        dayEvents.slice(0, 3).forEach((event) => {
          const chip = buildEventChip(event);
          eventContainer.appendChild(chip);
        });
        if (dayEvents.length > 3) {
          const more = document.createElement('p');
          more.className = 'calendar-details-empty';
          more.textContent = `+${dayEvents.length - 3} autre(s)`;
          eventContainer.appendChild(more);
        }
      }

      cell.appendChild(eventContainer);
      grid.appendChild(cell);
    }
  }

  function renderYear(range, events) {
    setGridClass('calendar-grid--year');
    grid.innerHTML = '';
    const months = Array.from({ length: 12 }, (_, i) => i);
    const grouped = months.map((monthIndex) => ({
      monthIndex,
      events: events.filter((event) => event.monthIndex === monthIndex),
    }));

    grouped.forEach(({ monthIndex, events: monthEvents }) => {
      const monthDate = new Date(range.startDate.getFullYear(), monthIndex, 1);
      const card = document.createElement('div');
      card.className = 'calendar-month';

      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.innerHTML = `
        <span class="calendar-day-name">${monthDate.toLocaleDateString('fr-FR', { month: 'long' })}</span>
        <span class="calendar-month-number">${monthEvents.length} évènement(s)</span>
      `;
      card.appendChild(header);

      const eventContainer = document.createElement('div');
      eventContainer.className = 'calendar-events';

      if (monthEvents.length === 0) {
        eventContainer.innerHTML = '<p class="calendar-details-empty">—</p>';
      } else {
        monthEvents.slice(0, 4).forEach((event) => {
          const chip = buildEventChip(event);
          eventContainer.appendChild(chip);
        });
      }

      card.appendChild(eventContainer);
      grid.appendChild(card);
    });
  }

  function getRange(view, baseDate) {
    if (view === 'week') {
      const startDate = startOfWeek(baseDate);
      const endDate = addDays(startDate, 7);
      return {
        label: `Semaine du ${formatDate(startDate, { day: 'numeric', month: 'long', year: 'numeric' })} au ${formatDate(addDays(endDate, -1), { day: 'numeric', month: 'long', year: 'numeric' })}`,
        startDate,
        endDate,
        startStr: formatLocalISODate(startDate),
        endStr: formatLocalISODate(endDate),
        renderer: renderWeek,
      };
    }

    if (view === 'month') {
      const currentMonth = baseDate.getMonth();
      const monthStart = new Date(baseDate.getFullYear(), currentMonth, 1);
      const gridStart = startOfWeek(monthStart);
      const endDate = addDays(gridStart, 42);
      return {
        label: formatDate(monthStart, { month: 'long', year: 'numeric' }),
        startDate: gridStart,
        endDate,
        currentMonth,
        startStr: formatLocalISODate(gridStart),
        endStr: formatLocalISODate(endDate),
        renderer: renderMonth,
      };
    }

    const yearStart = new Date(baseDate.getFullYear(), 0, 1);
    const yearEnd = new Date(baseDate.getFullYear() + 1, 0, 1);
    return {
      label: formatDate(yearStart, { year: 'numeric' }),
      startDate: yearStart,
      endDate: yearEnd,
      startStr: formatLocalISODate(yearStart),
      endStr: formatLocalISODate(yearEnd),
      renderer: renderYear,
    };
  }

  async function render() {
    resetDetails();
    showError('');
    showLoading(true);
    const range = getRange(state.currentView, state.currentDate);
    labelEl.textContent = range.label;
    const events = await loadEvents(range.startStr, range.endStr);
    showLoading(false);
    range.renderer(range, events);
  }

  viewButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const view = button.dataset.calendarView;
      if (!view || view === state.currentView) return;
      state.currentView = view;
      viewButtons.forEach((btn) => btn.classList.toggle('is-active', btn === button));
      render();
    });
  });

  navButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const direction = Number(button.dataset.calendarNav || 0);
      if (Number.isNaN(direction)) return;

      if (state.currentView === 'week') {
        state.currentDate = addDays(state.currentDate, direction * 7);
      } else if (state.currentView === 'month') {
        const next = new Date(state.currentDate);
        next.setMonth(next.getMonth() + direction);
        state.currentDate = next;
      } else {
        const next = new Date(state.currentDate);
        next.setFullYear(next.getFullYear() + direction);
        state.currentDate = next;
      }
      render();
    });
  });

  render();
}

