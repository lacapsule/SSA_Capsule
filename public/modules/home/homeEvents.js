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
  if (!modal || !modalBody || !modalTitle) return;
  const id = Number(evt.currentTarget.dataset.eventId);
  const events = parseDataAttr(listEl, 'events');
  const ev = events.find(e => Number(e.id) === id);
  if (!ev) return;

  const category = ev.category_label ? `<span class="event-badge" style="background:${ev.category_color || ev.color}">${ev.category_label}</span>` : '';
  const links = renderLinks(ev.links);

  modalTitle.textContent = ev.title;
  modalBody.innerHTML = `
    <p><strong>Date :</strong> ${ev.date_label} ${ev.time ? 'à ' + ev.time : ''}</p>
    ${ev.location ? `<p><strong>Lieu :</strong> ${ev.location}</p>` : ''}
    ${category ? `<p>${category}</p>` : ''}
    ${ev.description ? `<p>${ev.description}</p>` : ''}
    ${ev.info ? `<p><strong>Infos :</strong> ${ev.info}</p>` : ''}
    ${links ? `<div><strong>Liens :</strong><br>${links}</div>` : ''}
  `;
  modal.showModal();
}

function initHomeEvents() {
  if (!listEl || !modal) return;

  listEl.querySelectorAll('[data-event-id]').forEach(btn => {
    btn.addEventListener('click', openModal);
  });

  modal.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => modal.close());
  });
}

document.addEventListener('DOMContentLoaded', initHomeEvents);

