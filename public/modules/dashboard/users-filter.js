/*
 * users-filter.js
 * Client-side filtering (search by name) and simple date sort for users table
 */

function parseDateFlexible(value) {
  if (!value) return null;
  const iso = Date.parse(value);
  if (!isNaN(iso)) return new Date(iso);
  const m = value.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
  if (m) return new Date(parseInt(m[3],10), parseInt(m[2],10)-1, parseInt(m[1],10));
  const m2 = value.match(/(\d{4})-(\d{2})-(\d{2})/);
  if (m2) return new Date(parseInt(m2[1],10), parseInt(m2[2],10)-1, parseInt(m2[3],10));
  return null;
}

export function initUsersFilter() {
  if (typeof document === 'undefined') return;
  const table = document.querySelector('.dash-components-table');
  if (!table) return;
  // Ensure this is the users table by checking for .col-name
  if (!table.querySelector('.col-name')) return;

  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const searchInput = document.getElementById('users-search-name');
  const headerDate = table.querySelector('th[data-sort-field="users-date"]');
  const iconDate = document.getElementById('sort-icon-users-date');

  const getRows = () => Array.from(tbody.querySelectorAll('tr'));

  let dateAsc = false;

  const applyFilter = () => {
    const q = searchInput?.value?.trim().toLowerCase() || '';
    const rows = getRows();
    rows.forEach(r => {
      const name = r.querySelector('.col-name')?.textContent?.trim().toLowerCase() || '';
      const show = q === '' || name.includes(q);
      r.style.display = show ? '' : 'none';
    });
  };

  const doSortByDate = () => {
    const rows = getRows().filter(r => r.style.display !== 'none');
    rows.sort((a,b) => {
      const da = parseDateFlexible(a.querySelector('.col-date')?.textContent?.trim() || '');
      const db = parseDateFlexible(b.querySelector('.col-date')?.textContent?.trim() || '');
      if (da && db) return dateAsc ? da - db : db - da;
      if (da && !db) return -1;
      if (!da && db) return 1;
      return 0;
    });
    rows.forEach(r => tbody.appendChild(r));

    // update icon
      const upSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      const downSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      if (iconDate) {
        // parse safe SVG and inject
        try {
          const parser = new DOMParser();
          const doc = parser.parseFromString(dateAsc ? upSvg : downSvg, 'image/svg+xml');
          const svgEl = doc.documentElement;
          while (iconDate.firstChild) iconDate.removeChild(iconDate.firstChild);
          iconDate.appendChild(document.importNode(svgEl, true));
          iconDate.classList.add('active');
        } catch (e) {
          iconDate.innerHTML = dateAsc ? upSvg : downSvg;
          iconDate.classList.add('active');
        }
      }
  };

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      applyFilter();
    });
  }

  if (headerDate) {
    headerDate.addEventListener('click', () => {
      dateAsc = !dateAsc;
      doSortByDate();
    });
  }

  // initial filter (none) and initial sort (most recent first)
  applyFilter();
  dateAsc = false;
  doSortByDate();
}
