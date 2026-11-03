// Reports Page - Modal & Export Functions

// ── CSV Export ───────────────────────────────────────────
// Uses the filters from the report that was last generated, so the CSV
// always matches what is shown in the preview and the printed PDF.
function exportCSV(type) {
    try {
        const cached = reportCache[type];
        if (!cached) {
            showNotificationModal('Please generate a report first before exporting', 'warning');
            return;
        }
        const params = new URLSearchParams({ export: 'csv', type, ...cached.filters });
        window.location.href = `${PAGE_URL}?${params.toString()}`;
    } catch (e) {
        console.error('CSV Export Error:', e);
        showNotificationModal('Failed to export CSV: ' + e.message, 'error');
    }
}

// ── Print ────────────────────────────────────────────────
function printReport(type) {
    try {
        const cached = reportCache[type];
        if (!cached) {
            showNotificationModal('Please generate a report first before printing', 'warning');
            return;
        }

        const printRoot = document.getElementById('print-root');
        if (!printRoot) {
            throw new Error('Print root element not found in DOM');
        }

        // The report banner already shows the date and author, so no extra print header.
        printRoot.innerHTML = `<div class="preview-wrap">${buildHTML(type, cached.data)}</div>`;

        // Paper is white: switch off dark mode while printing, then restore it.
        const wasDark = document.documentElement.classList.contains('dark');
        document.documentElement.classList.remove('dark');
        window.addEventListener('afterprint', () => {
            if (wasDark) document.documentElement.classList.add('dark');
        }, { once: true });

        window.print();
    } catch (e) {
        console.error('Print Report Error:', e);
        showNotificationModal('Failed to prepare print: ' + e.message, 'error');
    }
}