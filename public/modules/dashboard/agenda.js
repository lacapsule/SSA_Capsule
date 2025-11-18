// assets/modules/dashboard/agenda.js

const apiUrl = '/dashboard/agenda';
let currentDate = new Date();

// DOM Elements
const calendarHeader = document.getElementById('calendar-header');
const calendarGrid = document.getElementById('calendar-grid');
const monthLabel = document.getElementById('monthLabel');

// Modals
const createModal = document.getElementById('agenda-create-modal');
const editModal = document.getElementById('agenda-edit-modal');
const deleteModal = document.getElementById('agenda-delete-modal');

// Init
document.addEventListener('DOMContentLoaded', () => {
    if (!calendarGrid) return;

    renderCalendar();
    setupNavigation();
    setupModalListeners();
});

function setupNavigation() {
    document.getElementById('prevBtn').addEventListener('click', () => changeMonth(-1));
    document.getElementById('nextBtn').addEventListener('click', () => changeMonth(1));
    document.getElementById('addEventBtn').addEventListener('click', () => {
        // Par défaut, ouvre pour aujourd'hui à 09:00
        openCreateModal(new Date());
    });
}

function changeMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    renderCalendar();
}

/* ===========================================
   CORE RENDERING LOGIC (Fixes Date Bug)
   =========================================== */
async function renderCalendar() {
    calendarGrid.innerHTML = '';
    calendarHeader.innerHTML = '';

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Mise à jour label
    monthLabel.textContent = currentDate.toLocaleString('fr-FR', { month: 'long', year: 'numeric' });

    // 1. Générer l'en-tête (Lundi -> Dimanche)
    const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    days.forEach(day => {
        const div = document.createElement('div');
        div.className = 'day-name';
        div.textContent = day;
        calendarHeader.appendChild(div);
    });

    // 2. Calcul des bornes du calendrier
    // On trouve le premier jour du mois
    const firstDayOfMonth = new Date(year, month, 1);
    // On trouve le lundi précédent (ou ce jour si c'est lundi)
    // getDay(): 0=Dim, 1=Lun... on veut 0=Lun, 6=Dim pour le calcul
    let dayOfWeek = firstDayOfMonth.getDay(); // 0 (Dim) à 6 (Sam) standard JS
    // Conversion pour Lundi=0 ... Dimanche=6
    let adjustedDay = (dayOfWeek === 0) ? 6 : dayOfWeek - 1;

    // Date de début de la grille (Lundi de la première semaine)
    const startDate = new Date(year, month, 1 - adjustedDay);

    // On affiche toujours 6 semaines (42 cases) pour garder la taille fixe
    const totalDays = 42;

    // Préparer les chaînes YYYY-MM-DD pour l'API
    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + totalDays);

    const startStr = formatLocalISODate(startDate);
    const endStr = formatLocalISODate(endDate);

    // 3. Récupérer les événements
    const events = await fetchEvents(startStr, endStr);

    // 4. Boucle de création des cellules
    const todayStr = formatLocalISODate(new Date());

    for (let i = 0; i < totalDays; i++) {
        // Créer une copie fraîche de la date pour cette case
        const cellDate = new Date(startDate);
        cellDate.setDate(startDate.getDate() + i);

        const cellDateStr = formatLocalISODate(cellDate); // YYYY-MM-DD local

        const cell = document.createElement('div');
        cell.className = 'day-cell';
        if (cellDate.getMonth() !== month) cell.classList.add('other-month');
        if (cellDateStr === todayStr) cell.classList.add('today');

        // Ajout attribut data pour responsive (nom du jour)
        const dayName = cellDate.toLocaleString('fr-FR', { weekday: 'long' });

        // Numéro du jour
        const dateNum = document.createElement('div');
        dateNum.className = 'date-number';
        dateNum.textContent = cellDate.getDate();
        dateNum.setAttribute('data-dayname', dayName); // Pour le CSS mobile
        cell.appendChild(dateNum);

        // Conteneur événements
        const evContainer = document.createElement('div');
        evContainer.className = 'events-wrapper';

        // Filtrer les événements du jour par comparaison de chaînes strictes
        // Cela corrige le bug du "6 qui apparait le 7" dû aux fuseaux horaires
        const dayEvents = events.filter(ev => ev.start.startsWith(cellDateStr));

        dayEvents.forEach(ev => {
            const el = createEventElement(ev);
            evContainer.appendChild(el);
        });

        cell.appendChild(evContainer);

        // Clic sur une case vide -> Création
        cell.addEventListener('click', (e) => {
            if (e.target === cell || e.target === evContainer || e.target === dateNum) {
                openCreateModal(cellDate);
            }
        });

        calendarGrid.appendChild(cell);
    }
}

function createEventElement(ev) {
    const div = document.createElement('div');
    div.className = 'event-item';
    div.style.backgroundColor = ev.color || '#3788d8';

    // Icones SVG inline
    const iconClock = `<svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/></svg>`;
    const iconPin = `<svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`;

    const timeStr = ev.start.substring(11, 16); // HH:MM

    div.innerHTML = `
        <div class="event-row event-title">${ev.title}</div>
        <div class="event-row">
            ${iconClock} <span>${timeStr}</span>
        </div>
        ${ev.description ? `<div class="event-row">${iconPin} <span>${ev.description}</span></div>` : ''}
    `;

    div.addEventListener('click', (e) => {
        e.stopPropagation();
        openEditModal(ev);
    });

    return div;
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
    // Gestion fermeture générique (boutons X et Annuler)
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-close');
            document.getElementById(id).close();
        });
    });

    // Formulaires
    document.getElementById('createEventForm').addEventListener('submit', handleCreateSubmit);
    document.getElementById('editEventForm').addEventListener('submit', handleEditSubmit);

    // Bouton "Supprimer" dans la modale d'édition -> Ouvre confirmation
    document.getElementById('triggerDeleteBtn').addEventListener('click', () => {
        closeModal(editModal);
        const id = document.getElementById('edit_eventId').value;
        const title = document.getElementById('edit_title').value;
        openDeleteModal(id, title);
    });

    // Confirmation suppression
    document.getElementById('confirmDeleteBtn').addEventListener('click', handleDeleteConfirm);
}

// --- CREATE ---
function openCreateModal(dateObj) {
    const form = document.getElementById('createEventForm');
    form.reset();

    // Par défaut, on coche le premier radio (Bleu)
    const radios = form.querySelectorAll('input[name="color"]');
    if (radios.length > 0) radios[0].checked = true;

    // Pré-remplir date et heure (09:00 - 10:00)
    const ymd = formatLocalISODate(dateObj);
    document.getElementById('create_start').value = `${ymd}T09:00`;
    document.getElementById('create_end').value = `${ymd}T10:00`;

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
    form.reset();

    document.getElementById('edit_eventId').value = ev.id;
    document.getElementById('edit_title').value = ev.title;
    document.getElementById('edit_start').value = ev.start.replace(' ', 'T').slice(0, 16);
    document.getElementById('edit_end').value = ev.end.replace(' ', 'T').slice(0, 16);
    document.getElementById('edit_description').value = ev.description || '';

    // Gestion de la sélection de couleur via Radio Buttons
    const colorToSelect = ev.color || '#3788d8'; // Fallback bleu
    const radios = form.querySelectorAll('input[name="color"]');
    let found = false;

    radios.forEach(radio => {
        if (radio.value === colorToSelect) {
            radio.checked = true;
            found = true;
        }
    });

    // Si la couleur de l'event n'est pas dans nos 3 choix (ex: ancien event), on coche le bleu par défaut
    if (!found && radios.length > 0) {
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
    }
}

/* ===========================================
   HELPERS
   =========================================== */

// Helper générique pour create/update
async function sendRequest(url, formData, modalToClose) {
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        const data = await res.json();

        if (res.ok && data.success) {
            closeModal(modalToClose);
            renderCalendar();
        } else {
            alert(data.message || "Une erreur est survenue");
        }
    } catch (err) {
        console.error(err);
        alert("Erreur réseau");
    }
}

async function fetchEvents(start, end) {
    try {
        const res = await fetch(`${apiUrl}/api/events?start=${start}&end=${end}`);
        return await res.json();
    } catch (e) {
        console.error(e);
        return [];
    }
}

// Retourne YYYY-MM-DD en respectant le fuseau local (évite le décalage UTC)
function formatLocalISODate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}