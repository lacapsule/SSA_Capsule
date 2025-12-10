const listEl = document.getElementById('home-events-list');
const modal = document.getElementById('home-event-modal');
const modalBody = document.getElementById('home-event-modal-body');
const modalTitle = document.getElementById('home-event-modal-title');

function parseDataAttr(el, key) {
  // Essayer d'abord de lire depuis une balise script avec l'ID correspondant
  const scriptId = key === 'events' ? 'home-events-data' : 
                   key === 'categories' ? 'home-categories-data' : null;
  
  if (scriptId) {
    const scriptEl = document.getElementById(scriptId);
    if (scriptEl) {
      try {
        const text = scriptEl.textContent.trim();
        return JSON.parse(text);
      } catch (e) {
        console.error('home events: parse error depuis script', { error: e, key, scriptId });
      }
    }
  }
  
  // Fallback: essayer avec dataset (qui décode automatiquement)
  let raw = el?.dataset?.[key];
  
  // Si pas trouvé, essayer avec getAttribute directement
  if (!raw && el) {
    raw = el.getAttribute(`data-${key}`);
  }
  
  if (!raw) {
    console.warn('home events: données non trouvées', { el, key });
    return [];
  }
  
  // Nettoyer la chaîne (enlever les espaces, guillemets supplémentaires)
  raw = String(raw).trim();
  
  // Si la chaîne commence et se termine par des guillemets, les enlever
  if ((raw.startsWith('"') && raw.endsWith('"')) || 
      (raw.startsWith("'") && raw.endsWith("'"))) {
    raw = raw.slice(1, -1);
  }
  
  try {
    // Décoder les entités HTML si nécessaire
    const decoded = raw.replace(/&quot;/g, '"')
                      .replace(/&#39;/g, "'")
                      .replace(/&amp;/g, '&')
                      .replace(/&lt;/g, '<')
                      .replace(/&gt;/g, '>');
    return JSON.parse(decoded);
  } catch (e) {
    console.error('home events: parse error', {
      error: e,
      raw: raw.substring(0, 200), // Afficher les 200 premiers caractères pour debug
      key,
      el
    });
    return [];
  }
}

function renderLinks(linksStr) {
  if (!linksStr || typeof linksStr !== 'string') return '';
  
  // Nettoyer la chaîne
  const cleaned = linksStr.trim();
  if (!cleaned) return '';
  
  // Parser le format "label : url" séparé par des virgules
  const items = cleaned.split(',').map(i => i.trim()).filter(Boolean);
  
  return items.map(item => {
    let label = item;
    let url = item;
    
    // Vérifier s'il y a un séparateur ":" pour label et URL
    // On cherche le premier ":" qui n'est pas dans le protocole (http:// ou https://)
    const colonIndex = item.indexOf(':');
    if (colonIndex > 0 && !item.substring(0, colonIndex).match(/^https?$/)) {
      const parts = item.split(':').map(p => p.trim());
      if (parts.length >= 2) {
        label = parts[0];
        url = parts.slice(1).join(':').trim(); // Rejoindre en cas d'URL avec ':'
      }
    }
    
    // Nettoyer l'URL
    url = url.trim();
    
    // Si l'URL ne commence pas par http:// ou https://, l'ajouter
    if (url && !url.match(/^https?:\/\//i)) {
      // Vérifier si c'est une URL valide sans protocole
      if (url.match(/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*\.[a-zA-Z]{2,}/)) {
        url = 'https://' + url;
      } else {
        // Si ce n'est pas une URL valide, ne pas créer de lien
        return '';
      }
    }
    
    // Valider que c'est une URL
    if (!url.match(/^https?:\/\//i)) {
      return '';
    }
    
    try {
      const urlObj = new URL(url);
      const displayUrl = urlObj.href;
      
      return `<a href="${encodeURI(displayUrl)}" target="_blank" rel="noopener noreferrer" class="event-link">${displayUrl}</a>`;
    } catch (e) {
      console.warn('homeEvents: URL invalide', { url, error: e });
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
  let id = Number(target?.dataset?.eventId);
  
  // Si l'ID n'est pas dans dataset, essayer de le trouver dans l'attribut data-event-id directement
  if (!id || isNaN(id)) {
    const dataAttr = target?.getAttribute('data-event-id');
    if (dataAttr) {
      id = Number(dataAttr);
    }
  }
  
  // Si toujours pas d'ID, essayer de trouver la card parente et extraire l'ID depuis les événements
  if (!id || isNaN(id)) {
    const events = parseDataAttr(listEl, 'events');
    // Chercher l'événement en comparant le titre ou d'autres propriétés visibles
    const titleEl = target.querySelector?.('.evenement-title') || 
                    target.closest?.('.evenement-item')?.querySelector('.evenement-title');
    if (titleEl) {
      const title = titleEl.textContent?.trim();
      const ev = events.find(e => e.title === title);
      if (ev) {
        id = Number(ev.id);
      }
    }
  }
  
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
    ${ev.description ? `<p><strong>Description :</strong> ${ev.description}</p>` : ''}
    ${ev.info ? `<p><strong>Infos :</strong> ${ev.info}</p>` : ''}
    ${links ? `<p><strong>Liens :</strong> ${links}</p>` : ''}
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

