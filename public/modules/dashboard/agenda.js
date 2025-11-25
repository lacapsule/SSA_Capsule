// /public/modules/dashboard/agenda.js - Calendar with Home Design

const apiUrl = '/dashboard/agenda';
let currentDate = new Date();
let currentView = 'month'; // 'week', 'month', 'year'

// DOM Elements
const calendarGrid = document.getElementById('dashboard-calendar-grid');
const calendarLabel = document.getElementById('dashboard-calendar-label');
const loadingIndicator = document.getElementById('dashboard-calendar-loading');
const errorIndicator = document.getElementById('dashboard-calendar-error');
const detailsPanel = document.getElementById('public-calendar-details');

// Modals
const createModal = document.getElementById('agenda-create-modal');
const editModal = document.getElementById('agenda-edit-modal');
const deleteModal = document.getElementById('agenda-delete-modal');

// State
let selectedEvent = null;

// Init
document.addEventListener('DOMContentLoaded', () => {
    if (!calendarGrid) return;

    renderCalendar();
    setupNavigation();
    setupViewSwitch();
    setupModalListeners();
});

function setupNavigation() {
    // Navigation (Previous/Next buttons)
    document.querySelectorAll('[data-calendar-nav]').forEach(btn => {
        btn.addEventListener('click', () => {
            const delta = parseInt(btn.getAttribute('data-calendar-nav'));
            if (currentView === 'week') {
                currentDate.setDate(currentDate.getDate() + delta * 7);
            } else if (currentView === 'month') {
                currentDate.setMonth(currentDate.getMonth() + delta);
            } else if (currentView === 'year') {
                currentDate.setFullYear(currentDate.getFullYear() + delta);
            }
            renderCalendar();
        });
    });

    // Add Event button
    document.getElementById('addEventBtn')?.addEventListener('click', () => {
        openCreateModal(new Date());
    });
}

function setupViewSwitch() {
    // Calendar view switch buttons (Week/Month/Year)
    document.querySelectorAll('.calendar-view-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.calendar-view-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            currentView = btn.getAttribute('data-calendar-view');
            renderCalendar();
        });
    });
}

/* ===========================================
   CORE RENDERING LOGIC (NEW HOME DESIGN)
   =========================================== */

async function renderCalendar() {
    // Reset details panel
    resetDetails();
    
    // Update calendar grid class based on view
    calendarGrid.className = `calendar-grid calendar-grid--${currentView}`;
    calendarGrid.innerHTML = '';

    // Update label
    updateCalendarLabel();

    // Show loading
    loadingIndicator?.removeAttribute('hidden');
    errorIndicator?.setAttribute('hidden', '');

    try {
        // Fetch events for the current range
        const { startDate, endDate } = getDateRange();
        const events = await fetchEvents(startDate, endDate);

        // Render based on current view
        if (currentView === 'week') {
            renderWeekView(events);
        } else if (currentView === 'month') {
            renderMonthView(events);
        } else if (currentView === 'year') {
            renderYearView(events);
        }

        loadingIndicator?.setAttribute('hidden', '');
    } catch (err) {
        console.error('Calendar render error:', err);
        errorIndicator?.removeAttribute('hidden');
        errorIndicator.textContent = 'Erreur lors du chargement du calendrier';
        loadingIndicator?.setAttribute('hidden', '');
    }
}

function resetDetails() {
    if (!detailsPanel) return;
    selectedEvent = null;
    detailsPanel.innerHTML = `<p class="calendar-details-empty">Sélectionner un événement pour voir les détails</p>`;
}

function updateCalendarLabel() {
    let label = '';
    if (currentView === 'week') {
        const weekStart = new Date(currentDate);
        const day = weekStart.getDay();
        const diff = weekStart.getDate() - (day === 0 ? 6 : day - 1);
        weekStart.setDate(diff);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);
        label = `Semaine du ${formatLocalISODate(weekStart)} au ${formatLocalISODate(weekEnd)}`;
    } else if (currentView === 'month') {
        label = currentDate.toLocaleString('fr-FR', { month: 'long', year: 'numeric' });
    } else if (currentView === 'year') {
        label = currentDate.getFullYear().toString();
    }
    calendarLabel.textContent = label;
}

function getDateRange() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    let startDate, endDate;

    if (currentView === 'week') {
        startDate = new Date(currentDate);
        const day = startDate.getDay();
        const diff = startDate.getDate() - (day === 0 ? 6 : day - 1);
        startDate.setDate(diff);
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);
    } else if (currentView === 'month') {
        startDate = new Date(year, month, 1);
        let dayOfWeek = startDate.getDay();
        let adjustedDay = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
        startDate.setDate(1 - adjustedDay);
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 41);
    } else if (currentView === 'year') {
        startDate = new Date(year, 0, 1);
        endDate = new Date(year, 11, 31);
    }

    return { 
        startDate: formatLocalISODate(startDate), 
        endDate: formatLocalISODate(endDate) 
    };
}

function renderWeekView(events) {
    const weekStart = new Date(currentDate);
    const day = weekStart.getDay();
    const diff = weekStart.getDate() - (day === 0 ? 6 : day - 1);
    weekStart.setDate(diff);

    const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + i);
        const dateStr = formatLocalISODate(date);
        
        const dayEvents = events.filter(ev => ev.start.startsWith(dateStr));
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
            const empty = document.createElement('p');
            empty.className = 'calendar-details-empty';
            empty.textContent = '—';
            eventContainer.appendChild(empty);
        } else {
            dayEvents.forEach((event) => {
                const chip = createEventChip(event);
                eventContainer.appendChild(chip);
            });
        }

        column.appendChild(eventContainer);
        
        // Click to create event
        column.addEventListener('click', (e) => {
            if (e.target === column || e.target === header || e.target.parentElement === header) {
                openCreateModal(date);
            }
        });
        
        calendarGrid.appendChild(column);
    }
}

function renderMonthView(events) {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDayOfMonth = new Date(year, month, 1);
    let dayOfWeek = firstDayOfMonth.getDay();
    let adjustedDay = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

    const startDate = new Date(year, month, 1 - adjustedDay);
    const todayStr = formatLocalISODate(new Date());

    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        const dateStr = formatLocalISODate(date);

        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';

        if (date.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        if (dateStr === todayStr) {
            dayElement.classList.add('today');
        }

        // Day header
        const header = document.createElement('div');
        header.className = 'calendar-day-header';
        header.innerHTML = `
            <span class="calendar-day-name">${date.toLocaleDateString('fr-FR', { weekday: 'short' })}</span>
            <span class="calendar-day-number">${date.getDate()}</span>
        `;
        dayElement.appendChild(header);

        // Events container
        const eventsContainer = document.createElement('div');
        eventsContainer.className = 'calendar-events';

        const dayEvents = events.filter(ev => ev.start.startsWith(dateStr));
        if (dayEvents.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'calendar-details-empty';
            empty.textContent = '—';
            eventsContainer.appendChild(empty);
        } else {
            // Limiter à 3 événements, afficher les autres en nombre
            dayEvents.slice(0, 3).forEach(ev => {
                const chip = createEventChip(ev);
                eventsContainer.appendChild(chip);
            });
            if (dayEvents.length > 3) {
                const more = document.createElement('p');
                more.className = 'calendar-details-empty';
                more.textContent = `+${dayEvents.length - 3} autre(s)`;
                eventsContainer.appendChild(more);
            }
        }

        dayElement.appendChild(eventsContainer);

        // Click to create event
        dayElement.addEventListener('click', (e) => {
            if (e.target === dayElement || e.target === header || e.target.parentElement === header) {
                openCreateModal(date);
            }
        });

        calendarGrid.appendChild(dayElement);
    }
}

function renderYearView(events) {
    const year = currentDate.getFullYear();
    
    for (let month = 0; month < 12; month++) {
        const monthDate = new Date(year, month, 1);
        const card = document.createElement('div');
        card.className = 'calendar-month';

        const monthEvents = events.filter(ev => {
            const eventDate = new Date(ev.start);
            return eventDate.getMonth() === month && eventDate.getFullYear() === year;
        });

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
            const empty = document.createElement('p');
            empty.className = 'calendar-details-empty';
            empty.textContent = '—';
            eventContainer.appendChild(empty);
        } else {
            monthEvents.slice(0, 4).forEach((event) => {
                const chip = createEventChip(event);
                eventContainer.appendChild(chip);
            });
        }

        card.appendChild(eventContainer);
        calendarGrid.appendChild(card);
    }
}

function createEventChip(ev) {
    const button = document.createElement('button');
    button.className = 'calendar-event-chip';
    button.style.backgroundColor = ev.color || '#1d72b8';
    button.type = 'button';

    const timeStr = ev.start.substring(11, 16); // HH:MM
    button.textContent = `${timeStr} • ${ev.title}`;

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        displayEventDetails(ev);
    });

    return button;
}

function displayEventDetails(ev) {
    selectedEvent = ev;
    
    if (!detailsPanel) return;
    
    const startDate = new Date(ev.start.replace(' ', 'T'));
    const endDate = new Date(ev.end.replace(' ', 'T'));
    
    const dateLabel = startDate.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    
    const startTime = startDate.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
    
    const endTime = endDate.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
    
    const timeLabel = `${startTime} — ${endTime}`;
    
    detailsPanel.innerHTML = `
        <h3>${ev.title}</h3>
        <p><strong>${dateLabel}</strong></p>
        <p>${timeLabel}</p>
        ${ev.description ? `<p>${ev.description}</p>` : ''}
        <button type="button" class="btn btn-primary" id="detail-edit-btn" style="margin-top: 1rem;">Éditer</button>
    `;
    
    document.getElementById('detail-edit-btn')?.addEventListener('click', () => {
        openEditModal(ev);
    });
}

/* ===========================================
   MODAL LOGIC
   =========================================== */

function showModal(modalEl) {
    modalEl.showModal();
}

function closeModal(modalEl) {
    modalEl.close();
}

function setupModalListeners() {
    // Close button handlers
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-close');
            document.getElementById(modalId)?.close();
        });
    });

    // Form submissions
    document.getElementById('createEventForm')?.addEventListener('submit', handleCreateSubmit);
    document.getElementById('editEventForm')?.addEventListener('submit', handleEditSubmit);

    // Delete trigger in edit modal
    document.getElementById('triggerDeleteBtn')?.addEventListener('click', () => {
        closeModal(editModal);
        const id = document.getElementById('edit_eventId').value;
        const title = document.getElementById('edit_title').value;
        openDeleteModal(id, title);
    });

    // Delete confirmation
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', handleDeleteConfirm);
}

// --- CREATE ---
function openCreateModal(dateObj) {
    const form = document.getElementById('createEventForm');
    form?.reset();

    // Set first color radio as default
    const radios = form?.querySelectorAll('input[name="color"]');
    if (radios?.length > 0) radios[0].checked = true;

    // Pre-fill date and time (09:00 - 10:00)
    const ymd = formatLocalISODate(dateObj);
    if (document.getElementById('create_start')) {
        document.getElementById('create_start').value = `${ymd}T09:00`;
    }
    if (document.getElementById('create_end')) {
        document.getElementById('create_end').value = `${ymd}T10:00`;
    }

    showModal(createModal);
}

async function handleCreateSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    await sendRequest(`${apiUrl}/api/create`, formData, createModal);
}

// --- EDIT ---
function openEditModal(ev) {
    const form = document.getElementById('editEventForm');
    form?.reset();

    document.getElementById('edit_eventId').value = ev.id;
    document.getElementById('edit_title').value = ev.title;
    document.getElementById('edit_start').value = ev.start.replace(' ', 'T').slice(0, 16);
    document.getElementById('edit_end').value = ev.end.replace(' ', 'T').slice(0, 16);
    document.getElementById('edit_description').value = ev.description || '';

    // Select correct color radio
    const colorToSelect = ev.color || '#3788d8';
    const radios = form?.querySelectorAll('input[name="color"]');
    let found = false;

    radios?.forEach(radio => {
        if (radio.value === colorToSelect) {
            radio.checked = true;
            found = true;
        }
    });

    if (!found && radios?.length > 0) {
        radios[0].checked = true;
    }

    showModal(editModal);
}

async function handleEditSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('edit_eventId').value;
    const formData = new FormData(form);

    await sendRequest(`${apiUrl}/api/update/${id}`, formData, editModal);
}

// --- DELETE ---
function openDeleteModal(id, title) {
    document.getElementById('delete_eventId').value = id;
    document.getElementById('delete-event-title').textContent = title;
    showModal(deleteModal);
}

async function handleDeleteConfirm() {
    const id = document.getElementById('delete_eventId').value;
    const csrfToken = document.querySelector('input[name="_csrf"]')?.value;
    const formData = new FormData();
    formData.append('_csrf', csrfToken);

    try {
        const res = await fetch(`${apiUrl}/api/delete/${id}`, { method: 'POST', body: formData });
        if (res.ok) {
            closeModal(deleteModal);
            renderCalendar();
        } else {
            alert("Erreur lors de la suppression");
        }
    } catch (err) {
        console.error(err);
        alert("Erreur réseau");
    }
}


/* ===========================================
   HELPERS
   =========================================== */

// Generic request sender for create/update
async function sendRequest(url, formData, modalToClose) {
    try {
        const res = await fetch(url, { 
            method: 'POST', 
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json();

        if (res.ok && data.success) {
            closeModal(modalToClose);
            renderCalendar();
        } else {
            alert(data.message || data.errors?.join(', ') || "Une erreur est survenue");
        }
    } catch (err) {
        console.error(err);
        alert("Erreur réseau: " + err.message);
    }
}

// Fetch events from API
async function fetchEvents(start, end) {
    try {
        const res = await fetch(`${apiUrl}/api/events?start=${start}&end=${end}`);
        return await res.json();
    } catch (e) {
        console.error('Fetch events error:', e);
        return [];
    }
}

// Format date as YYYY-MM-DD (respecting local timezone)
function formatLocalISODate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}