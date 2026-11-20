// Champ « address » : suggestions du géocodeur (via /admin/adresses, même origine), liste déroulante accessible
// (motif ARIA combobox) et phrase d'état sous le champ. Sans JavaScript : champ texte simple, position cherchée
// à l'enregistrement. À charger après admin.js : rejoué sur chaque fragment (fenêtre de modification comprise).
document.addEventListener('admin:enhance', (event) => initAddresses(event.detail.root));
if (document.readyState !== 'loading') initAddresses(document);
else document.addEventListener('DOMContentLoaded', () => initAddresses(document));

function initAddresses(root) {
  root.querySelectorAll('[data-address]').forEach((box) => {
    if (box.dataset.ready) return;
    box.dataset.ready = '1';
    const input = box.querySelector('[data-address-input]');
    const list = box.querySelector('[data-address-list]');
    const status = box.parentElement.querySelector('[data-address-status]');
    const source = box.dataset.addressSource;
    let timer = 0;
    let active = -1;
    let request = 0;
    const setStatus = (text, kind = '') => {
      status.textContent = text;
      status.className = `geo-status${kind ? ` geo-status--${kind}` : ''}`;
    };
    const options = () => [...list.querySelectorAll('[role="option"]')];
    const close = () => {
      list.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      active = -1;
    };
    const highlight = (index) => {
      const opts = options();
      active = (index + opts.length) % opts.length;
      opts.forEach((o, i) => o.setAttribute('aria-selected', String(i === active)));
      input.setAttribute('aria-activedescendant', opts[active].id);
      opts[active].scrollIntoView({ block: 'nearest' });
    };
    // Champs séparés (data-address-fill-postcode, -city) : la rue dans ce champ, le reste dans ses champs.
    const fills = { postcode: box.dataset.addressFillPostcode, city: box.dataset.addressFillCity };
    const separe = Boolean(fills.postcode || fills.city);
    const choose = (option) => {
      input.value = separe && option.dataset.name ? option.dataset.name : option.dataset.value;
      Object.entries(fills).forEach(([part, id]) => {
        const field = id && document.getElementById(id);
        if (field && option.dataset[part]) field.value = option.dataset[part];
      });
      close();
      setStatus('Adresse reconnue : elle sera placée sur la carte à l\'enregistrement.', 'ok');
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const search = async () => {
      const q = input.value.trim();
      if (q.length < 4) {
        close();
        return;
      }
      const id = ++request;
      try {
        const response = await fetch(`${source}&q=${encodeURIComponent(q)}`, { credentials: 'same-origin' });
        const items = response.ok ? await response.json() : [];
        if (id !== request) return;
        list.replaceChildren(...items.map((item, i) => {
          const li = document.createElement('li');
          li.id = `${list.id}-${i}`;
          li.setAttribute('role', 'option');
          li.setAttribute('aria-selected', 'false');
          li.dataset.value = item.label;
          li.dataset.name = item.name || '';
          li.dataset.postcode = item.postcode || '';
          li.dataset.city = item.city || '';
          const strong = document.createElement('strong');
          strong.textContent = item.label;
          const small = document.createElement('small');
          small.textContent = item.context;
          li.append(strong, small);
          return li;
        }));
        list.hidden = items.length === 0;
        input.setAttribute('aria-expanded', String(items.length > 0));
        active = -1;
        if (items.length === 0) setStatus('Aucune adresse trouvée : vérifiez le numéro, la rue et la ville.', 'off');
        else setStatus(`${items.length} suggestion${items.length > 1 ? 's' : ''} : choisissez l'adresse dans la liste.`);
      } catch {
        setStatus('Suggestions indisponibles : l\'adresse sera cherchée à l\'enregistrement.');
      }
    };
    input.addEventListener('input', () => {
      clearTimeout(timer);
      setStatus(input.value.trim() === '' ? 'Sans adresse, la fiche figure dans la liste mais pas sur la carte.' : 'Recherche de l\'adresse en cours.');
      timer = setTimeout(search, 300);
    });
    input.addEventListener('keydown', (event) => {
      const opts = options();
      if (event.key === 'ArrowDown' && opts.length) {
        event.preventDefault();
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        highlight(active + 1);
      } else if (event.key === 'ArrowUp' && opts.length && !list.hidden) {
        event.preventDefault();
        highlight(active - 1);
      } else if (event.key === 'Enter' && !list.hidden && active >= 0) {
        event.preventDefault();
        choose(opts[active]);
      } else if (event.key === 'Escape' && !list.hidden) {
        // Ferme la liste sans fermer la fenêtre de modification.
        event.preventDefault();
        event.stopPropagation();
        close();
      }
    });
    list.addEventListener('mousedown', (event) => {
      const option = event.target.closest('[role="option"]');
      if (option) {
        event.preventDefault();
        choose(option);
      }
    });
    input.addEventListener('blur', () => setTimeout(close, 100));
  });
}
