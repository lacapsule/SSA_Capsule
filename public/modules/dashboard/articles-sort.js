/*
 * articles-sort.js
 * Client-side sorting for the articles table (no server changes needed)
 */

function parseDateFlexible(value) {
  if (!value) return null;
  // Try ISO first
  const iso = Date.parse(value);
  if (!isNaN(iso)) return new Date(iso);

  // Try dd/mm/yyyy or dd-mm-yyyy
  const m = value.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
  if (m) {
    const day = parseInt(m[1], 10);
    const month = parseInt(m[2], 10) - 1;
    const year = parseInt(m[3], 10);
    return new Date(year, month, day);
  }

  // Fallback: try to extract numbers YYYY-MM-DD
  const m2 = value.match(/(\d{4})-(\d{2})-(\d{2})/);
  if (m2) return new Date(parseInt(m2[1],10), parseInt(m2[2],10)-1, parseInt(m2[3],10));

  return null;
}

export function initArticlesSort() {
  if (typeof document === 'undefined') return;

  const table = document.querySelector('.dash-components-table');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const getRows = () => Array.from(tbody.querySelectorAll('tr'));

  const sortBy = document.getElementById('articles-sort-by');
  const sortOrder = document.getElementById('articles-sort-order');
  const headerTitre = table.querySelector('th[data-sort-field="titre"]');
  const headerDate = table.querySelector('th[data-sort-field="date"]');
  const iconTitre = document.getElementById('sort-icon-titre');
  const iconDate = document.getElementById('sort-icon-date');

  const doSort = () => {
    const field = sortBy ? sortBy.value : 'date';
    const order = sortOrder ? sortOrder.value : 'desc';

    const rows = getRows();

    const comparator = (a, b) => {
      let va = '';
      let vb = '';

      if (field === 'titre') {
        va = a.querySelector('.col-title')?.textContent?.trim() || '';
        vb = b.querySelector('.col-title')?.textContent?.trim() || '';
        // For alpha sorts we may want case-insensitive comparison
        const ia = va.toLowerCase();
        const ib = vb.toLowerCase();
        if (order === 'alpha-asc') return ia.localeCompare(ib);
        if (order === 'alpha-desc') return ib.localeCompare(ia);
        // Fallback to alphabetical
        return ia.localeCompare(ib);
      }

      // Date field
      const da = parseDateFlexible(a.querySelector('.col-date')?.textContent?.trim() || '');
      const db = parseDateFlexible(b.querySelector('.col-date')?.textContent?.trim() || '');

      if (da && db) {
        if (order === 'asc') return da - db;
        if (order === 'desc') return db - da;
      }
      // If one is invalid, push it to the end
      if (da && !db) return -1;
      if (!da && db) return 1;

      // As final fallback compare titles
      va = a.querySelector('.col-title')?.textContent?.trim() || '';
      vb = b.querySelector('.col-title')?.textContent?.trim() || '';
      return va.toLowerCase().localeCompare(vb.toLowerCase());
    };

    const sorted = rows.sort((r1, r2) => comparator(r1, r2));

    // Re-append in sorted order
    sorted.forEach(r => tbody.appendChild(r));

    // Update header icons (inject inline SVG reliably)
    const upSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const downSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    const clearIcon = (el) => {
      if (!el) return;
      while (el.firstChild) el.removeChild(el.firstChild);
      el.classList.remove('active');
    };

    const setIcon = (el, svgString) => {
      if (!el) return;
      try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(svgString, 'image/svg+xml');
        const svgEl = doc.documentElement;
        while (el.firstChild) el.removeChild(el.firstChild);
        const imported = document.importNode(svgEl, true);
        el.appendChild(imported);
        el.classList.add('active');
      } catch (e) {
        el.innerHTML = svgString;
        el.classList.add('active');
      }
    };

    if (iconTitre) clearIcon(iconTitre);
    if (iconDate) clearIcon(iconDate);

    if (field === 'titre') {
      if (iconTitre) setIcon(iconTitre, order === 'alpha-desc' ? downSvg : upSvg);
    } else if (field === 'date') {
      if (iconDate) setIcon(iconDate, order === 'asc' ? upSvg : downSvg);
    }
  };

  if (sortBy) sortBy.addEventListener('change', doSort);
  if (sortOrder) sortOrder.addEventListener('change', doSort);

  // Clicks on headers toggle sort
  const toggleOrder = (field) => {
    if (!sortBy || !sortOrder) return;
    if (field === 'titre') {
      // toggle between alpha asc/desc
      if (sortBy.value !== 'titre') sortBy.value = 'titre';
      sortOrder.value = sortOrder.value === 'alpha-asc' ? 'alpha-desc' : 'alpha-asc';
    } else if (field === 'date') {
      if (sortBy.value !== 'date') sortBy.value = 'date';
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    }
    doSort();
  };

  if (headerTitre) headerTitre.addEventListener('click', () => toggleOrder('titre'));
  if (headerDate) headerDate.addEventListener('click', () => toggleOrder('date'));

  // Initial sort default: recent first
  doSort();
}
