// Tableau de bord des modules PHP : amélioration progressive propre au gabarit (fenêtre de modification, confirmation,
// aperçu, filtres instantanés, onglets dynamiques, champ de lien). La navigation (barre latérale, tiroir, menu replié,
// menus), les messages (.adm-alerte : seul le succès disparaît seul après 5 s) sont ceux de la coquille de référence
// (admin-coquille.js) ; le bouton Afficher des mots de passe est celui de composants/champ-mot-de-passe (mot-de-passe.js).
// Sans JavaScript, chaque écran reste utilisable (listes complètes, formulaires sur leur propre page, suppression sans
// fenêtre de confirmation).
document.documentElement.classList.add('js');

function initAdmin() {
  initConfirm();
  initModal();
  initPreview();
  initRoleToggle();
  enhance(document);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdmin);
else initAdmin();

/** Comportements d'un fragment de page ; rejoué sur le contenu chargé dans une fenêtre ou remplacé après enregistrement. */
function enhance(root) {
  initFilters(root);
  initTabsets(root);
  initLinkFields(root);
  initRepeatRows(root);
  root.querySelectorAll('[data-mdp][hidden]').forEach((button) => { button.hidden = false; });
  if (root !== document && typeof window.admAlerteEphemere === 'function') window.admAlerteEphemere(root);
  document.dispatchEvent(new CustomEvent('admin:enhance', { detail: { root } }));
}

// Compte : le périmètre ne concerne qu'un éditeur ; grisé et vidé quand le rôle est Administrateur.
function initRoleToggle() {
  document.addEventListener('change', (event) => {
    const role = event.target.closest('[data-role-select]');
    const scope = role && role.form && role.form.querySelector('[data-role-org]');
    if (!scope) return;
    scope.disabled = role.value !== 'editor';
    if (scope.disabled) scope.value = '';
  });
}

/** Fenêtre de confirmation ; résout true si la personne confirme. Sans <dialog> : confirmation native. */
function askConfirm(text, okLabel = 'Confirmer') {
  const dialog = document.getElementById('confirm-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return Promise.resolve(window.confirm(text));
  dialog.querySelector('#confirm-text').textContent = text;
  dialog.querySelector('[data-confirm-ok]').textContent = okLabel;
  const opener = document.activeElement;
  dialog.showModal();
  dialog.querySelector('[data-confirm-cancel]').focus();
  return new Promise((resolve) => {
    const done = (value) => {
      dialog.removeEventListener('click', onClick);
      dialog.removeEventListener('close', onClose);
      if (dialog.open) dialog.close();
      if (!value && opener && opener.isConnected) opener.focus();
      resolve(value);
    };
    const onClick = (event) => {
      if (event.target.closest('[data-confirm-ok]')) done(true);
      else if (event.target.closest('[data-confirm-cancel]')) done(false);
    };
    const onClose = () => done(false);
    dialog.addEventListener('click', onClick);
    dialog.addEventListener('close', onClose);
  });
}

// <form data-confirm="Question ?"> : confirmation avant l'envoi.
function initConfirm() {
  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form || form.dataset.confirmed) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (await askConfirm(form.dataset.confirm)) {
      form.dataset.confirmed = '1';
      form.requestSubmit();
    }
  }, true);
}

// Fenêtre de modification : un lien [data-modal] charge la page visée et en affiche [data-modal-content].
// L'envoi reste celui du serveur : après redirection, le contenu de fond est remplacé par sa version à jour.
function initModal() {
  const dialog = document.getElementById('modal');
  if (!dialog || typeof dialog.showModal !== 'function' || !window.fetch || !window.DOMParser) return;
  const body = dialog.querySelector('[data-modal-body]');
  const foot = dialog.querySelector('[data-modal-foot-slot]');
  const heading = dialog.querySelector('#modal-title');
  const live = document.getElementById('admin-live');
  let opener = null;
  let url = '';
  let dirty = false;
  let busy = false;
  // lint-ok : page d'administration du même site (fetch same-origin), analyse inerte, aucun script exécuté.
  const parse = (html) => new DOMParser().parseFromString(html, 'text/html');

  const show = (doc, at) => {
    const content = doc.querySelector('[data-modal-content]');
    if (!content) return false;
    url = at;
    dirty = false;
    heading.textContent = content.dataset.modalTitle || doc.querySelector('h1')?.textContent.trim() || '';
    const notices = [...doc.querySelectorAll('#contenu > .adm-messages > .adm-alerte--erreur')];
    const footer = content.querySelector('[data-modal-foot]');
    body.replaceChildren(...notices, content);
    foot.replaceChildren(...(footer ? [footer] : []));
    foot.hidden = !footer;
    enhance(dialog);
    if (!dialog.open) dialog.showModal();
    body.scrollTop = 0;
    const first = body.querySelector('[aria-invalid="true"], input:not([type="hidden"]):not([disabled]), textarea, select');
    (first || body).focus({ preventScroll: true });
    return true;
  };
  const open = async (href, trigger) => {
    opener = trigger;
    try {
      const response = await fetch(href, { credentials: 'same-origin' });
      if (!response.ok) throw new Error(String(response.status));
      if (!show(parse(await response.text()), response.url)) location.assign(href);
    } catch {
      location.assign(href);
    }
  };
  const close = () => {
    dirty = false;
    if (dialog.open) dialog.close();
  };
  const requestClose = async () => {
    if (!dirty || await askConfirm('Fermer sans enregistrer ? Vos modifications seront perdues.', 'Fermer sans enregistrer')) close();
  };
  const swapMain = (doc, at) => {
    const main = document.getElementById('contenu');
    const fresh = doc.getElementById('contenu');
    if (!main || !fresh) {
      location.assign(at);
      return;
    }
    const y = window.scrollY;
    const href = opener?.getAttribute('href');
    main.replaceChildren(...fresh.childNodes);
    const target = new URL(at);
    history.replaceState(null, '', target.pathname + target.search);
    enhance(main);
    window.scrollTo(0, y);
    const notice = main.querySelector('.adm-alerte');
    if (live && notice) live.textContent = notice.textContent.trim();
    opener = (href && main.querySelector(`[href="${CSS.escape(href)}"]`)) || main;
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-modal]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    event.preventDefault();
    open(link.href, link);
  });
  dialog.addEventListener('click', (event) => {
    if (event.target.closest('[data-modal-close]')) {
      event.preventDefault();
      requestClose();
    } else if (event.target === dialog) {
      requestClose();
    }
  });
  dialog.addEventListener('cancel', (event) => {
    event.preventDefault();
    requestClose();
  });
  dialog.addEventListener('close', () => {
    body.replaceChildren();
    foot.replaceChildren();
    if (opener && opener.isConnected) opener.focus();
  });
  dialog.addEventListener('input', () => { dirty = true; });
  dialog.addEventListener('change', () => { dirty = true; });
  dialog.addEventListener('submit', async (event) => {
    const form = event.target;
    if ((form.method || '').toLowerCase() !== 'post' || busy || event.defaultPrevented) return;
    event.preventDefault();
    busy = true;
    const button = event.submitter;
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form, button), credentials: 'same-origin' });
      const doc = parse(await response.text());
      const content = doc.querySelector('[data-modal-content]');
      const samePage = new URL(response.url).pathname === new URL(url).pathname;
      if (response.redirected && !(content && !samePage)) {
        let fresh = doc;
        let at = response.url;
        if (new URL(response.url).pathname !== location.pathname) {
          fresh = parse(await (await fetch(location.href, { credentials: 'same-origin' })).text());
          at = location.href;
          const notice = doc.querySelector('#contenu > .adm-messages');
          if (notice) fresh.getElementById('contenu')?.prepend(notice);
        }
        swapMain(fresh, at);
        close();
      } else if (show(doc, response.url)) {
        dirty = true; // formulaire renvoyé avec erreurs : la saisie n'est pas enregistrée
      } else {
        throw new Error(String(response.status));
      }
    } catch (failure) {
      const status = failure instanceof Error && /^\d+$/.test(failure.message) ? Number(failure.message) : 0;
      const error = document.createElement('div');
      error.className = 'adm-alerte adm-alerte--erreur';
      error.setAttribute('role', 'alert');
      const texte = document.createElement('p');
      texte.className = 'adm-alerte__texte';
      error.append(texte);
      texte.textContent = status > 0
        ? `Le serveur a répondu par une erreur (code ${status}) : vérifiez dans la liste si l'enregistrement a eu lieu avant de réessayer.`
        : 'Pas de réponse du serveur : vérifiez votre connexion, puis dans la liste si l\'enregistrement a eu lieu, avant de réessayer.';
      body.prepend(error);
    } finally {
      busy = false;
      if (button && button.isConnected) button.disabled = false;
    }
  });
  window.addEventListener('beforeunload', (event) => {
    if (dirty && dialog.open) event.preventDefault();
  });
}

// Lien [data-preview] : la page visée s'affiche dans une fenêtre, à la largeur d'un ordinateur ou d'un mobile.
function initPreview() {
  const dialog = document.getElementById('preview-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const frame = dialog.querySelector('[data-preview-frame]');
  let opener = null;
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-preview]');
    if (!link || event.ctrlKey || event.metaKey) return;
    event.preventDefault();
    opener = link;
    frame.src = link.href;
    dialog.showModal();
    dialog.querySelector('[data-preview-width][aria-pressed="true"]').focus();
  });
  dialog.addEventListener('click', (event) => {
    const width = event.target.closest('[data-preview-width]');
    if (width) {
      dialog.querySelectorAll('[data-preview-width]').forEach((b) => b.setAttribute('aria-pressed', String(b === width)));
      frame.classList.toggle('adm-apercu-cadre--mobile', width.dataset.previewWidth === 'mobile');
    }
    if (event.target.closest('[data-preview-close]') || event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => {
    frame.removeAttribute('src');
    if (opener && opener.isConnected) opener.focus();
  });
}

// Filtres instantanés : <div data-list> contient [data-filters] (masqué sans script), des lignes (tbody tr ou
// [data-filter-row]) portant data-f-<clé>, et [data-filter-empty]. La recherche porte sur le texte de la ligne.
// Pagination : au-delà de data-per-page lignes (20 par défaut), les lignes retenues s'affichent par pages, avec une
// barre Précédentes / Suivantes sous la liste ; sans script, toute la liste reste affichée.
function initFilters(root) {
  root.querySelectorAll('[data-list]').forEach((list) => {
    if (list.dataset.ready) return;
    list.dataset.ready = '1';
    const bar = list.querySelector('[data-filters]');
    if (!bar) return;
    bar.hidden = false;
    const q = bar.querySelector('[data-filter-q]');
    const selects = [...bar.querySelectorAll('[data-filter]')];
    const rows = [...list.querySelectorAll('tbody tr, [data-filter-row]')];
    const count = bar.querySelector('[data-filter-count]');
    const empty = list.querySelector('[data-filter-empty]');
    const grids = [...list.querySelectorAll('[data-filter-grid]')];
    const resets = [...list.querySelectorAll('[data-filter-reset]')];
    const words = (n) => `${n} ${n > 1 ? count.dataset.many : count.dataset.one}`;
    const per = Math.max(1, Number(list.dataset.perPage) || 20);
    let page = 1;
    const pager = document.createElement('nav');
    pager.className = 'adm-pages';
    pager.setAttribute('aria-label', 'Pages de la liste');
    pager.hidden = true;
    const prev = document.createElement('button');
    const next = document.createElement('button');
    const etat = document.createElement('p');
    prev.type = 'button'; next.type = 'button';
    prev.className = 'btn btn--neutre'; next.className = 'btn btn--neutre';
    prev.textContent = 'Précédentes'; next.textContent = 'Suivantes';
    etat.className = 'adm-pages__etat';
    etat.setAttribute('aria-live', 'polite');
    pager.append(prev, etat, next);
    if (empty) empty.before(pager); else list.append(pager);
    const norm = (s) => s.toLocaleLowerCase('fr').normalize('NFD').replace(/[̀-ͯ]/g, '');
    const key = (name) => `f${name[0].toUpperCase()}${name.slice(1)}`;
    rows.forEach((row) => { row.dataset.text = norm(row.textContent); });
    const apply = (keepPage = false) => {
      const term = q ? norm(q.value.trim()) : '';
      const hits = rows.filter((row) => (term === '' || row.dataset.text.includes(term))
        && selects.every((s) => s.value === '' || row.dataset[key(s.dataset.filter)] === s.value));
      const shown = hits.length;
      const pages = Math.max(1, Math.ceil(shown / per));
      if (!keepPage) page = 1;
      page = Math.min(page, pages);
      const from = (page - 1) * per;
      const visible = new Set(hits.slice(from, from + per));
      rows.forEach((row) => { row.hidden = !visible.has(row); });
      pager.hidden = pages < 2;
      prev.disabled = page === 1;
      next.disabled = page === pages;
      etat.textContent = `${from + 1} à ${Math.min(from + per, shown)} sur ${shown}, page ${page} sur ${pages}`;
      const active = term !== '' || selects.some((s) => s.value !== '');
      if (count) count.textContent = active ? `${shown} sur ${words(rows.length)}` : words(rows.length);
      if (empty) empty.hidden = shown !== 0;
      grids.forEach((grid) => { grid.hidden = active && !grid.querySelector('tbody tr:not([hidden]), [data-filter-row]:not([hidden])'); });
      const reset = bar.querySelector('[data-filter-reset]');
      if (reset) reset.hidden = !active;
    };
    const turn = (delta) => {
      page += delta;
      apply(true);
      bar.scrollIntoView({ block: 'start' });
    };
    prev.addEventListener('click', () => turn(-1));
    next.addEventListener('click', () => turn(1));
    q?.addEventListener('input', () => apply());
    selects.forEach((s) => s.addEventListener('change', () => apply()));
    resets.forEach((b) => b.addEventListener('click', () => {
      if (q) q.value = '';
      selects.forEach((s) => { s.value = ''; });
      apply();
      (q || selects[0])?.focus();
    }));
    apply();
  });
}

// Onglets (motif ARIA tabs, flèches, Début, Fin) ; l'onglet ouvert est gardé dans l'adresse (?onglet=).
function initTabsets(root) {
  root.querySelectorAll('[data-tabset]').forEach((set) => {
    if (set.dataset.ready) return;
    set.dataset.ready = '1';
    const list = set.querySelector('[data-tabset-list]');
    if (!list) return;
    list.hidden = false;
    const tabs = [...list.querySelectorAll('[data-tab]')];
    const panels = [...set.querySelectorAll('[data-tab-panel]')];
    const select = (slug, focus = false) => {
      tabs.forEach((tab) => {
        const on = tab.dataset.tab === slug;
        tab.setAttribute('aria-selected', String(on));
        tab.tabIndex = on ? 0 : -1;
        if (on && focus) tab.focus();
      });
      panels.forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== slug; });
      try {
        const url = new URL(location.href);
        url.searchParams.set('onglet', slug);
        history.replaceState(null, '', url);
      } catch { /* adresse non modifiable (aperçu isolé) */ }
    };
    list.addEventListener('click', (event) => {
      const tab = event.target.closest('[data-tab]');
      if (tab) select(tab.dataset.tab);
    });
    list.addEventListener('keydown', (event) => {
      const index = tabs.indexOf(document.activeElement);
      const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -index, End: tabs.length - 1 - index };
      if (index < 0 || !(event.key in moves)) return;
      event.preventDefault();
      select(tabs[(index + moves[event.key] + tabs.length) % tabs.length].dataset.tab, true);
    });
    select((tabs.find((t) => t.getAttribute('aria-selected') === 'true') || tabs[0]).dataset.tab);
  });
}

// Destination d'un lien (partials/link.php) : la saisie libre n'apparaît que pour « Un autre site » ou « Une adresse e-mail ».
function initLinkFields(root) {
  root.querySelectorAll('[data-link-field]').forEach((field) => {
    if (field.dataset.ready) return;
    field.dataset.ready = '1';
    const select = field.querySelector('[data-link-target]');
    const free = field.querySelector('[data-link-free]');
    const input = free.querySelector('[data-link-free-input]');
    const sync = () => {
      const isMail = select.value === '__mail';
      free.toggleAttribute('data-off', select.value !== '__web' && !isMail);
      free.querySelector('[data-link-free-label]').textContent = isMail ? 'Adresse e-mail' : 'Adresse du site';
      input.placeholder = isMail ? 'nom@exemple.fr' : 'www.exemple.fr';
      input.type = isMail ? 'email' : 'text';
    };
    select.addEventListener('change', () => {
      sync();
      if (!free.hasAttribute('data-off')) input.focus();
    });
    sync();
  });
}

// Lignes répétées ([data-lignes] > [data-ligne], liens d'un article) : les lignes remplies et une ligne vide sont
// affichées, les autres attendent le bouton « Ajouter » ; sans script, toutes les lignes restent visibles.
function initRepeatRows(root) {
  root.querySelectorAll('[data-lignes]').forEach((box) => {
    if (box.dataset.ready) return;
    box.dataset.ready = '1';
    const rows = [...box.querySelectorAll('[data-ligne]')];
    const isEmpty = (row) => [...row.querySelectorAll('input, textarea')].every((i) => i.value.trim() === '');
    let spare = false;
    rows.forEach((row) => {
      if (!isEmpty(row)) return;
      if (spare) row.hidden = true;
      spare = true;
    });
    const hiddenRows = () => rows.filter((row) => row.hidden);
    if (hiddenRows().length === 0) return;
    const bar = document.createElement('div');
    bar.className = 'adm-rangee';
    const add = document.createElement('button');
    add.type = 'button';
    add.className = 'btn btn--neutre';
    add.textContent = box.dataset.lignesAjout || 'Ajouter une ligne';
    bar.append(add);
    rows[rows.length - 1].after(bar);
    add.addEventListener('click', () => {
      const next = hiddenRows()[0];
      if (!next) return;
      next.hidden = false;
      next.querySelector('input, textarea')?.focus();
      bar.hidden = hiddenRows().length === 0;
    });
  });
}
