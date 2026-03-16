// Reports Page - Modal & Export Functions

// ── CSV Export ───────────────────────────────────────────
function exportCSV(type) {
    const p = new URLSearchParams({ export:'csv', type, ...getFilters(type) });
    window.location.href = `${PAGE_URL}?${p.toString()}`;
}

// ── Print ────────────────────────────────────────────────
function printReport(type) {
    const cached = reportCache[type];
    if (!cached) return;

    const today = new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'});
    const printRoot = document.getElementById('print-root');

    // Inject a clean print header + the same HTML used in preview
    printRoot.innerHTML = `
        <div class="flex justify-between items-center border-b-2 border-blue-700 pb-2 mb-4 font-sans">
            <span class="font-bold text-blue-700 text-sm">STI Discipline Office</span>
            <span class="text-xs text-gray-600">Generated: ${today} &nbsp;|&nbsp; By: ${esc(ADMIN_NAME)}</span>
        </div>
        <div class="preview-wrap">${buildHTML(type, cached.data)}</div>`;

    window.print();
}
