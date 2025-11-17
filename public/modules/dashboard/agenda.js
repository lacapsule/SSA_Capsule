(function () {
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
    document.addEventListener('click', function (e) {
        const eventBlock = e.target.closest('.event-block');
        if (eventBlock) {
            const id = eventBlock.dataset.eventId;
            const title = eventBlock.querySelector('.event-title')?.textContent;
            console.log('Événement cliqué:', { id, title });
            // TODO: Ouvrir modal de détails/édition
        }
    });
})();