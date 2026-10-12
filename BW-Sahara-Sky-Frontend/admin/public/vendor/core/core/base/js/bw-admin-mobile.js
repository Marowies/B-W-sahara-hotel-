(() => {
    'use strict';
    const phone = matchMedia('(max-width: 991.98px)');
    const sidebar = document.getElementById('sidebar-menu');
    const toggle = document.querySelector('#sidebar-menu-main > div > .navbar-toggler');
    const content = document.querySelector('.page-wrapper');
    if (sidebar && toggle) {
        const backdrop = document.createElement('button');
        backdrop.type = 'button'; backdrop.className = 'bw-admin-menu-backdrop';
        backdrop.tabIndex = -1;
        backdrop.setAttribute('aria-label', sidebar.querySelector('[aria-label]')?.getAttribute('aria-label') || 'Close');
        document.body.append(backdrop);
        let previousInert = false, active = false;
        const close = () => { if (sidebar.classList.contains('show')) toggle.click(); };
        const sync = () => {
            const open = phone.matches && sidebar.classList.contains('show');
            document.body.classList.toggle('bw-admin-menu-open', open);
            if (open && !active) {
                previousInert = content?.inert || false;
                if (content) content.inert = true;
                sidebar.setAttribute('role', 'dialog'); sidebar.setAttribute('aria-modal', 'true');
                sidebar.setAttribute('aria-label', 'B&W Sahara Sky');
                sidebar.querySelector('.bw-mobile-menu-heading button')?.focus();
            } else if (!open && active) {
                if (content) content.inert = previousInert;
                sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); sidebar.removeAttribute('aria-label');
                if (phone.matches) toggle.focus();
            }
            active = open;
        };
        backdrop.addEventListener('click', close);
        sidebar.addEventListener('shown.bs.collapse', sync);
        sidebar.addEventListener('hidden.bs.collapse', sync);
        new MutationObserver(sync).observe(sidebar, {attributes:true, attributeFilter:['class']});
        phone.addEventListener('change', sync);
        document.addEventListener('keydown', event => {
            if (!active) return;
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            if (event.key === 'Tab') {
                const items = [...sidebar.querySelectorAll('a[href],button,input,select,[tabindex]')].filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden');
                const first = items[0], last = items.at(-1);
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
            }
        });
        sync();
    }
    // Preserve native selection/bulk actions. An explicit folder button uses the
    // existing folder-open handler, avoiding a hidden double-tap requirement.
    let pending = false;
    const observedTables = new WeakSet();
    const tableResize = typeof ResizeObserver === 'function' ? new ResizeObserver(() => schedule()) : null;
    const imageFallback = img => {
        if (!(img instanceof HTMLImageElement) || img.dataset.bwFallback) return;
        const media = img.closest('.rv-media-thumbnail,.bw-calendar-rooms');
        const logo = img.classList.contains('navbar-brand-image');
        if (!media && !logo && !img.classList.contains('avatar')) return;
        img.dataset.bwFallback = '1';
        img.classList.add('bw-missing-image');
        img.title = document.body.dir === 'rtl' ? 'المعاينة غير متاحة' : 'Preview unavailable';
        img.src = '/vendor/core/core/base/css/bw-brand/img/' + (logo ? 'logo.webp' : media ? 'media-placeholder.svg' : 'avatar-placeholder.svg');
    };
    document.addEventListener('error', event => imageFallback(event.target), true);
    const enhance = () => {
        pending = false;
        document.querySelectorAll('.rv-media-thumbnail img,.bw-calendar-rooms img,img.avatar,img.navbar-brand-image').forEach(img => {
            if (img.complete && img.naturalWidth === 0) imageFallback(img);
        });
        document.querySelectorAll('table.dataTable tbody td.dtr-control').forEach(cell => {
            let button = cell.querySelector('.bw-row-details');
            if (!button) {
                button = document.createElement('button');
                button.type = 'button'; button.className = 'bw-row-details';
                button.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
                cell.prepend(button);
            }
            button.setAttribute('aria-expanded', String(cell.closest('tr').classList.contains('parent')));
            button.setAttribute('aria-label', document.documentElement.dir === 'rtl' ? 'تفاصيل الصف' : 'Row details');
            cell.removeAttribute('aria-expanded');cell.removeAttribute('aria-label');
        });
        document.querySelectorAll('.table-responsive').forEach(table => {
            const grid = table.querySelector('table');
            const overflowing = grid ? grid.scrollWidth > table.clientWidth + 2 : table.scrollWidth > table.clientWidth + 2;
            if (grid && tableResize && !observedTables.has(grid)) {
                observedTables.add(grid);
                tableResize.observe(grid);
                tableResize.observe(table);
            }
            table.classList.toggle('bw-scroll-table', overflowing);
            if (!table.hasAttribute('tabindex')) table.tabIndex = 0;
            if (!table.hasAttribute('role')) table.setAttribute('role', 'region');
            if (!table.hasAttribute('aria-label')) table.setAttribute('aria-label', document.querySelector('.page-title')?.textContent.trim() || document.title);
            table.dataset.bwScrollHint = document.body.dir === 'rtl' ? 'اسحب أفقيًا لعرض باقي الأعمدة' : 'Swipe sideways to see more columns';
        });
        document.querySelectorAll('.rv-media-container .js-media-list-title').forEach(item => {
            if (item.querySelector('.bw-media-open') || !window.jQuery || window.jQuery(item).data('is_folder') !== true) return;
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'bw-media-open btn';
            const label = item.closest('.rv-media-container').dataset.bwOpenLabel || 'Open';
            button.setAttribute('aria-label', label + ': ' + (item.querySelector('.title,.rv-media-file-name')?.textContent.trim() || ''));
            button.title = label;
            button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
            button.addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); item.dispatchEvent(new MouseEvent('dblclick', {bubbles:true})); });
            (item.querySelector('.rv-media-item') || item).append(button);
        });
    };
    const schedule = () => { if (!pending) { pending = true; requestAnimationFrame(enhance); } };
    window.addEventListener('resize', schedule, {passive:true});
    window.jQuery?.(document).on('responsive-display.dt draw.dt', schedule);
    new MutationObserver(records => { if (records.some(record => [...record.addedNodes].some(node => node.nodeType === 1))) schedule(); }).observe(document.body, {childList:true,subtree:true});
    enhance();
})();
