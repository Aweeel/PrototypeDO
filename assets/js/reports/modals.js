// Reports Page - Modal & Export Functions

// ── CSV Export ───────────────────────────────────────────
function exportCSV(type) {
    try {
        const filters = getFilters(type);
        const params = new URLSearchParams({ export: 'csv', type, ...filters });
        const url = `${PAGE_URL}?${params.toString()}`;
        console.log('Exporting CSV for:', type);
        window.location.href = url;
    } catch (e) {
        console.error('CSV Export Error:', e);
        alert('Failed to export CSV: ' + e.message);
    }
}

// ── Print ────────────────────────────────────────────────
function printReport(type) {
    try {
        const cached = reportCache[type];
        if (!cached) {
            alert('Please generate a report first before printing');
            return;
        }

        const today = new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'});
        const printRoot = document.getElementById('print-root');

        if (!printRoot) {
            throw new Error('Print root element not found in DOM');
        }

        // Inject a clean print header + the same HTML used in preview
        printRoot.innerHTML = `
            <div class="flex justify-between items-center border-b-2 border-blue-700 pb-2 mb-4 font-sans">
                <span class="font-bold text-blue-700 text-sm">STI Discipline Office</span>
                <span class="text-xs text-gray-600">Generated: ${today} &nbsp;|&nbsp; By: ${esc(ADMIN_NAME)}</span>
            </div>
            <div class="preview-wrap">${buildHTML(type, cached.data)}</div>`;

        console.log('Print preview ready for:', type);
        window.print();
    } catch (e) {
        console.error('Print Report Error:', e);
        alert('Failed to prepare print: ' + e.message);
    }
}