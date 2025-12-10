const listEl = document.getElementById('home-events-list');
const modal = document.getElementById('home-event-modal');
const modalBody = document.getElementById('home-event-modal-body');
const modalTitle = document.getElementById('home-event-modal-title');

function parseDataAttr(el, key) {
  const raw = el?.dataset?.[key];
  if (!raw) return [];
  try {
    return JSON.parse(raw);
  } catch (e) {
    console.warn('home events: parse error', e);
    return [];
  }
}

function renderLinks(linksStr) {
  if (!linksStr) return '';
  const items = linksStr.split(',').map(i => i.trim()).filter(Boolean);
  return items.map(item => {
    let label = item;
    let url = item;
    if (item.includes(':')) {
      const parts = item.split(':').map(p => p.trim());
      if (parts.length >= 2) {
        label = parts[0];
        url = parts.slice(1).join(':').trim();
      }
    }
    if (!/^https?:\/\//.test(url)) return '';
    try {
      new URL(url);
      return `<div class="event-link"><strong>${label} :</strong> <a href="${encodeURI(url)}" target="_blank" rel="noopener noreferrer">${url}</a></div>`;
    } catch {
      return '';
    }
  }).filter(Boolean).join('');
}

function openModal(evt) {
  console.log('homeEvents: openModal appelé', evt);
  
  if (!modal || !modalBody || !modalTitle) {
    console.warn('homeEvents: modal, modalBody ou modalTitle non trouvé', { modal, modalBody, modalTitle });
    return;
  }
  
  const target = evt.currentTarget || evt.target;
  const id = Number(target?.dataset?.eventId);
  
  console.log('homeEvents: id extrait', { id, target, dataset: target?.dataset });
  
  if (!id || isNaN(id)) {
    console.warn('homeEvents: eventId non trouvé ou invalide', { id, target });
    return;
  }
  
  const events = parseDataAttr(listEl, 'events');
  console.log('homeEvents: événements parsés', { events, count: events.length });
  
  const ev = events.find(e => Number(e.id) === id);
  
  if (!ev) {
    console.warn('homeEvents: événement non trouvé', { id, events });
    return;
  }

  console.log('homeEvents: événement trouvé', ev);

  const links = renderLinks(ev.links);

  modalTitle.textContent = ev.title || 'Événement';
  modalBody.innerHTML = `
    <p><strong>Date :</strong> ${ev.date_label || ''} ${ev.time ? 'à ' + ev.time : ''}</p>
    ${ev.location ? `<p><strong>Lieu :</strong> ${ev.location}</p>` : ''}
    ${ev.description ? `<p>${ev.description}</p>` : ''}
    ${ev.info ? `<p><strong>Infos :</strong> ${ev.info}</p>` : ''}
    ${links ? `<div><strong>Liens :</strong><br>${links}</div>` : ''}
  `;
  
  try {
    console.log('homeEvents: ouverture de la modale');
    modal.showModal();
    console.log('homeEvents: modale ouverte');
  } catch (error) {
    console.error('homeEvents: erreur lors de l\'ouverture de la modale', error);
  }
}

function initHomeEvents() {
  if (!listEl || !modal) {
    console.warn('homeEvents: listEl ou modal non trouvé', { listEl, modal });
    return;
  }

  console.log('homeEvents: initialisation', { listEl, modal });

  // Utiliser la délégation d'événements pour gérer les clics même si les cards sont créées dynamiquement
  listEl.addEventListener('click', (evt) => {
    // Chercher le bouton parent avec data-event-id
    let card = evt.target.closest('[data-event-id]');
    
    // Si pas trouvé, chercher le parent avec la classe evenement-item
    if (!card) {
      card = evt.target.closest('.evenement-item');
    }
    
    // Si toujours pas trouvé, chercher le parent avec la classe event-card
    if (!card) {
      card = evt.target.closest('.event-card');
    }
    
    // Si toujours pas trouvé, chercher le parent evenement-item-inner et remonter
    if (!card) {
      const inner = evt.target.closest('.evenement-item-inner');
      if (inner && inner.parentElement) {
        card = inner.parentElement;
      }
    }
    
    if (card) {
      evt.preventDefault();
      evt.stopPropagation();
      console.log('homeEvents: clic détecté sur card', card, card.dataset, card.className);
      
      // Si l'ID n'est pas dans dataset, essayer de le trouver dans les classes ou autres attributs
      if (!card.dataset.eventId) {
        // Chercher tous les boutons dans listEl pour trouver celui qui contient cette card
        const allCards = listEl.querySelectorAll('.evenement-item, .event-card, [data-event-id]');
        for (const c of allCards) {
          if (c.contains(card) || card === c) {
            card = c;
            break;
          }
        }
      }
      
      openModal({ currentTarget: card });
    } else {
      console.log('homeEvents: clic non sur une card', evt.target, evt.target.parentElement);
    }
  });

  // Gérer la fermeture de la modale
  modal.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  // Fermer la modale en cliquant sur le backdrop
  modal.addEventListener('click', (evt) => {
    if (evt.target === modal) {
      modal.close();
    }
  });
  
  console.log('homeEvents: listeners attachés');
}

// Initialiser dès que le DOM est prêt
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHomeEvents);
} else {
  // DOM déjà chargé
  initHomeEvents();
}

