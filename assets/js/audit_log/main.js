// Global variables
let allLogs = [];
let filteredLogs = [];
let currentPage = 1;
const logsPerPage = 10;
let ADMIN_NAME = '';
let auditActivityChart = null;
let currentFilters = {
    search: '',
    actionType: '',
    role: '',
    dateFrom: '',
    dateTo: ''
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    ADMIN_NAME = window.ADMIN_NAME || document.querySelector('meta[data-admin-name]')?.content || 'User';
    loadRoles();
    loadActionTypes();
    loadLogs();
    
    // Close modal on outside click
    const modal = document.getElementById('exportModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeExportModal();
            }
        });
    }
});

// Load roles for the role filter dropdown
async function loadRoles() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getRoles');

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            const roleFilter = document.getElementById('userFilter');
            const roleLabels = new Set();
            data.roles.forEach(role => {
                const label = formatValueLabel('role', role.role);
                if (roleLabels.has(label)) {
                    return;
                }
                const option = document.createElement('option');
                option.value = role.role;
                option.textContent = label;
                roleFilter.appendChild(option);
                roleLabels.add(label);
            });
        } else {
            console.error('Failed to load roles:', data.error);
        }
    } catch (error) {
        console.error('Error loading roles:', error);
    }
}

// Load action types for filter dropdown
async function loadActionTypes() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getActionTypes');

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            const actionFilter = document.getElementById('actionTypeFilter');
            data.actionTypes.forEach(action => {
                const option = document.createElement('option');
                option.value = action.action;
                option.textContent = action.action;
                actionFilter.appendChild(option);
            });
        } else {
            console.error('Failed to load action types:', data.error);
        }
    } catch (error) {
        console.error('Error loading action types:', error);
    }
}

// Load audit logs
async function loadLogs() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getAuditLogs');
        formData.append('search', currentFilters.search);
        formData.append('actionType', currentFilters.actionType);
        formData.append('role', currentFilters.role);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.logs) {
            allLogs = data.logs;
            filteredLogs = [...allLogs];
            currentPage = 1;
            updateAuditActivityChart();
            renderTable();
            updatePagination();
        } else {
            console.error('Failed to load logs:', data.error || 'No logs in response');
            const tbody = document.getElementById('logsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-red-500 dark:text-red-400">
                        <p class="text-lg font-medium">Unable to load audit logs</p>
                        <p class="text-sm mt-1">${data.error || 'Failed to fetch audit logs'}</p>
                    </td>
                </tr>`;
            document.getElementById('paginationInfo').textContent = 'Error loading logs';
        }

        function updateAuditActivityChart() {
            const canvas = document.getElementById('auditActivityChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const totals = filteredLogs.reduce((counts, log) => {
                counts[log.action] = (counts[log.action] || 0) + 1;
                return counts;
            }, {});
            const labels = Object.keys(totals);
            const colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#9333ea', '#0891b2', '#db2777', '#4b5563'];

            if (auditActivityChart) auditActivityChart.destroy();
            auditActivityChart = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: labels.map(label => totals[label]),
                        backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                        tooltip: {
                            callbacks: {
                                label: context => ` ${context.label}: ${context.raw}`
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showNotification('Unable to load audit logs: ' + error.message, 'error');
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-red-500 dark:text-red-400">
                    <p class="text-lg font-medium">Unable to load audit logs</p>
                    <p class="text-sm mt-1">${error.message}</p>
                </td>
            </tr>`;
        document.getElementById('paginationInfo').textContent = 'Error loading logs';
    }
}

document.addEventListener('table:sort', (event) => {
    if (event.target?.dataset?.sortSource !== 'audit') {
        return;
    }

    const fields = ['id', 'user', 'role', 'action', 'timestamp', 'recordId'];
    const field = fields[event.detail.columnIndex];
    if (!field) {
        return;
    }
    const direction = event.detail.direction === 'ascending' ? 1 : -1;
    filteredLogs.sort((left, right) => String(left[field] ?? '')
        .localeCompare(String(right[field] ?? ''), undefined, { numeric: true, sensitivity: 'base' }) * direction);
    currentPage = 1;
    renderTable();
    updatePagination();
});

// Render table
function renderTable() {
    const tbody = document.getElementById('logsTableBody');
    const tableContainer = document.getElementById('auditTableContainer');
    const table = tableContainer?.querySelector('table[data-table-controls]');
    const startIndex = (currentPage - 1) * logsPerPage;
    const endIndex = startIndex + logsPerPage;
    const logsToShow = filteredLogs.slice(startIndex, endIndex);

    if (logsToShow.length === 0) {
        if (table) {
            table.dataset.tableControlReset = 'true';
        }
        tableContainer?.classList.replace('overflow-x-auto', 'overflow-x-hidden');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-lg font-medium">No audit logs found</p>
                    <p class="text-sm mt-1">Try adjusting your filters</p>
                </td>
            </tr>`;
        return;
    }

    if (table) {
        table.dataset.tableControlReset = 'true';
    }
    tableContainer?.classList.replace('overflow-x-hidden', 'overflow-x-auto');
    let tableHTML = logsToShow.map(log => `
      <tr class="h-[72px] hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer" onclick="openLogDetailModal(${JSON.stringify(log).replace(/"/g, '&quot;')})">
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">#${log.id}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${log.user}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${formatValueLabel('role', log.role || 'N/A')}</td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">${log.action}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${log.timestamp}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${log.recordId || 'N/A'}</td>
      </tr>
    `).join('');

    // Add empty rows to maintain consistent table height
    const emptyRowsCount = logsPerPage - logsToShow.length;
    for (let i = 0; i < emptyRowsCount; i++) {
        tableHTML += `
            <tr class="h-[72px]">
                <td colspan="6"></td>
            </tr>
        `;
    }

    tbody.innerHTML = tableHTML;
}

// Filter logs
// Apply filters and reload from backend
function filterLogs() {
    currentFilters.search = document.getElementById('searchInput').value.trim();
    currentFilters.actionType = document.getElementById('actionTypeFilter').value;
    currentFilters.user = document.getElementById('userFilter').value;
    // Always reload logs from backend to apply PHP-side filters
    loadLogs();
}

// Sorting
function sortLogs() {
    const sortValue = document.getElementById('sortFilter').value;

    switch (sortValue) {
        case 'newest': filteredLogs.sort((a, b) => b.id - a.id); break;
        case 'oldest': filteredLogs.sort((a, b) => a.id - b.id); break;
        case 'user': filteredLogs.sort((a, b) => a.user.localeCompare(b.user)); break;
        case 'action': filteredLogs.sort((a, b) => a.action.localeCompare(b.action)); break;
    }

    renderTable();
}

// Pagination
function updatePagination() {
    const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
    const startIndex = (currentPage - 1) * logsPerPage + 1;
    const endIndex = Math.min(currentPage * logsPerPage, filteredLogs.length);

    document.getElementById('paginationInfo').textContent = 
        filteredLogs.length > 0 
            ? `Showing ${startIndex}-${endIndex} of ${filteredLogs.length} logs`
            : 'No logs found';

    const buttonsContainer = document.getElementById('paginationButtons');
    buttonsContainer.innerHTML = '';
    function renderCompactPaginationDOM(container, currentPage, totalPages, onPageChange) {
        if (!container) return;
        const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center';
        const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center';
        const disabledClass = 'opacity-50 cursor-not-allowed';
        const ellipsis = 'px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
        const pageWindow = 1;

        container.innerHTML = '';
        const appendBtn = (text, enabled, page, isActive) => {
            const el = document.createElement(enabled ? 'button' : 'span');
            el.textContent = text;
            el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
            if (enabled && typeof onPageChange === 'function') el.addEventListener('click', () => onPageChange(page));
            container.appendChild(el);
        };

        appendBtn('« Prev', currentPage > 1, currentPage - 1, false);

        if (totalPages <= 2 * pageWindow + 5) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const visiblePages = [...new Set([
                1,
                totalPages,
                ...Array.from({ length: pageWindow * 2 + 1 }, (_, offset) => currentPage - pageWindow + offset)
                    .filter(page => page > 1 && page < totalPages)
            ])].sort((a, b) => a - b);

            visiblePages.forEach((page, index) => {
                if (index > 0 && page - visiblePages[index - 1] > 1) {
                    const firstPage = visiblePages[index - 1] + 1;
                    const lastPage = page - 1;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative z-20 inline-flex';
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = ellipsis;
                    button.textContent = '…';
                    button.title = `Go to a page between ${firstPage} and ${lastPage}`;
                    button.setAttribute('aria-label', button.title);
                    const panel = document.createElement('form');
                    panel.className = 'fixed z-50 mt-2 w-56 max-w-[calc(100vw-1rem)] rounded-lg border border-gray-200 bg-white p-3 text-left shadow-lg dark:border-slate-600 dark:bg-slate-800';
                    panel.setAttribute('aria-label', `Jump to page ${firstPage} to ${lastPage}`);
                    panel.innerHTML = `
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Go to page</label>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" min="${firstPage}" max="${lastPage}" step="1" required
                                class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100"
                                placeholder="${firstPage}-${lastPage}">
                            <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Go</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter a page from ${firstPage} to ${lastPage}.</p>
                        <p class="hidden mt-2 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    `;
                    panel.classList.add('hidden');
                    const input = panel.querySelector('input');
                    const error = panel.querySelector('[role="alert"]');
                    button.addEventListener('click', () => {
                        const isOpen = !panel.classList.contains('hidden');
                        container.querySelectorAll('form[aria-label^="Jump to page"]').forEach(item => item.classList.add('hidden'));
                        if (!isOpen) {
                            panel.classList.remove('hidden');
                            const buttonRect = button.getBoundingClientRect();
                            const panelWidth = Math.min(224, window.innerWidth - 16);
                            const left = Math.max(8, Math.min(
                                buttonRect.right - panelWidth,
                                window.innerWidth - panelWidth - 8
                            ));
                            panel.style.left = `${left}px`;
                            const top = buttonRect.bottom + panel.offsetHeight + 8 <= window.innerHeight
                                ? buttonRect.bottom
                                : Math.max(8, buttonRect.top - panel.offsetHeight - 8);
                            panel.style.top = `${top}px`;
                            input.focus();
                        }
                    });
                    panel.addEventListener('submit', event => {
                        event.preventDefault();
                        const destination = Number(input.value);
                        if (!Number.isInteger(destination) || destination < firstPage || destination > lastPage) {
                            error.textContent = `Enter a whole number from ${firstPage} to ${lastPage}.`;
                            error.classList.remove('hidden');
                            input.focus();
                            return;
                        }
                        panel.classList.add('hidden');
                        onPageChange(destination);
                    });
                    input.addEventListener('input', () => error.classList.add('hidden'));
                    wrapper.append(button, panel);
                    container.appendChild(wrapper);
                }
                appendBtn(String(page), true, page, page === currentPage);
            });
        }

        appendBtn('Next »', currentPage < totalPages, Math.min(totalPages, currentPage + 1), false);
    }

    renderCasesStylePagination(buttonsContainer, currentPage, totalPages, function(p) { currentPage = p; renderTable(); updatePagination(); });
}

function renderCasesStylePagination(container, currentPage, totalPages, onPageChange) {
    const base = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center mx-1';
    const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center mx-1';
    container.innerHTML = '';
    const add = (text, enabled, page, selected) => {
        const element = document.createElement(enabled ? 'button' : 'span');
        element.textContent = text;
        element.className = selected ? active : base + (enabled ? '' : ' opacity-50 cursor-not-allowed');
        if (enabled) element.addEventListener('click', () => onPageChange(page));
        container.appendChild(element);
    };
    add('« Prev', currentPage > 1, Math.max(1, currentPage - 1), false);
    const pages = [...new Set([1, 2, 3, currentPage, totalPages])].filter((page) => page >= 1 && page <= totalPages).sort((a, b) => a - b);
    pages.forEach((page, index) => {
        if (index && page - pages[index - 1] > 1) {
            const ellipsis = document.createElement('button');
            ellipsis.type = 'button';
            ellipsis.textContent = '…';
            ellipsis.className = base;
            ellipsis.addEventListener('click', () => {
                const jump = document.createElement('span');
                jump.className = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-1 mx-1';
                jump.innerHTML = `<input type="number" min="1" max="${totalPages}" value="${currentPage}" class="w-16 rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1 text-sm"><button type="button" class="rounded bg-blue-600 px-2 py-1 text-sm text-white">Go</button>`;
                jump.querySelector('button').addEventListener('click', () => {
                    const destination = Number(jump.querySelector('input').value);
                    if (Number.isInteger(destination) && destination >= 1 && destination <= totalPages) onPageChange(destination);
                });
                ellipsis.replaceWith(jump);
                jump.querySelector('input').focus();
                jump.querySelector('input').select();
            });
            container.appendChild(ellipsis);
        }
        add(String(page), true, page, page === currentPage);
    });
    add('Next »', currentPage < totalPages, Math.min(totalPages, currentPage + 1), false);
}

function createPaginationButton(text, enabled, onClick, isActive = false) {
    const button = document.createElement('button');
    button.textContent = text;
    button.onclick = enabled ? onClick : null;
    if (isActive) button.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg font-medium';
    else if (enabled) button.className = 'px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors';
    else button.className = 'px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg opacity-50 cursor-not-allowed';
    return button;
}
function openAdvancedFilters() {
    document.getElementById('dateRangeModal').classList.remove('hidden');
}

function closeDateRangeModal() {
    document.getElementById('dateRangeModal').classList.add('hidden');
}

function applyDateFilter() {
    currentFilters.dateFrom = document.getElementById('dateFrom').value;
    currentFilters.dateTo = document.getElementById('dateTo').value;
    closeDateRangeModal();
    loadLogs();
}

function clearDateFilter() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    currentFilters.dateFrom = '';
    currentFilters.dateTo = '';
    loadLogs();
}

// Refresh logs
function refreshLogs() {
    loadLogs();
}

// Export logs to CSV
function exportLogs() {
    try {
        // Load current logs and show preview modal
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getAuditLogs');
        formData.append('search', currentFilters.search);
        formData.append('actionType', currentFilters.actionType);
        formData.append('user', currentFilters.user);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Generate filters summary
                    const filters = [];
                    if (currentFilters.search) filters.push(`Search: "${currentFilters.search}"`);
                    if (currentFilters.actionType) filters.push(`Action: ${currentFilters.actionType}`);
                    if (currentFilters.role) filters.push(`Role: ${document.querySelector(`#userFilter option[value="${currentFilters.role}"]`)?.textContent || currentFilters.role}`);
                    if (currentFilters.dateFrom) filters.push(`From: ${currentFilters.dateFrom}`);
                    if (currentFilters.dateTo) filters.push(`To: ${currentFilters.dateTo}`);
                    
                    document.getElementById('filtersSummary').textContent = 
                        filters.length > 0 ? filters.join(' | ') : 'None';
                    
                    document.getElementById('exportCount').textContent = 
                        `${data.logs.length} ${data.logs.length === 1 ? 'log' : 'logs'} found`;
                    
                    // Build preview table
                    buildExportPreview(data.logs);
                    
                    // Show modal
                    document.getElementById('exportModal').classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error('Error preparing export:', err);
                showNotification('Unable to prepare export', 'error');
            });
    } catch (error) {
        console.error('Error exporting logs:', error);
        showNotification('Unable to export logs', 'error');
    }
}

function buildExportPreview(logs) {
    const content = document.getElementById('exportPreviewContent');
    const today = new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'});
    
    if (logs.length === 0) {
        content.innerHTML = `
            <div class="text-center text-gray-500 py-12">
                <p class="text-lg font-medium">No audit logs to export</p>
                <p class="text-sm mt-1">Try adjusting your filters</p>
            </div>`;
        return;
    }
    
    let html = `
        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-slate-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">STI Discipline Office – Audit Log Report</h4>
            <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
                <div>
                    <span class="font-medium">Exported by:</span> ${ADMIN_NAME}
                </div>
                <div>
                    <span class="font-medium">Date & Time:</span> ${today}
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Log ID</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">User</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Role</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Action</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Timestamp</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Record ID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">`;
    
    logs.forEach(log => {
        html += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800">
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">#${log.id}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.user}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${formatValueLabel('role', log.role || 'N/A')}</td>
                        <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs font-semibold ${log.actionColor}">${log.action}</span></td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.timestamp}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.recordId || 'N/A'}</td>
                    </tr>`;
    });
    
    html += `
                </tbody>
            </table>
        </div>`;
    
    content.innerHTML = html;
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function exportAuditLogsCSV() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'exportLogs');
        formData.append('search', currentFilters.search);
        formData.append('actionType', currentFilters.actionType);
        formData.append('user', currentFilters.user);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        for (let [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        showNotification('Audit log export started', 'success');
        closeExportModal();
    } catch (error) {
        console.error('Error exporting CSV:', error);
        showNotification('Unable to export CSV', 'error');
    }
}

function printAuditReport() {
    try {
        const printRoot = document.getElementById('print-root');
        
        if (!printRoot) {
            throw new Error('Print root element not found in DOM');
        }

        let previewContent = document.getElementById('exportPreviewContent').innerHTML;
        
        printRoot.innerHTML = `
            <div class="flex justify-between items-center border-b-2 border-blue-700 pb-2 mb-4 font-sans">
                <span class="font-bold text-blue-700 text-sm">STI Discipline Office</span>
                <span class="text-xs text-gray-600">By: ${ADMIN_NAME}</span>
            </div>
            <div class="preview-wrap">${previewContent}</div>`;

        window.print();
    } catch (e) {
        console.error('Print Report Error:', e);
        showNotification('Failed to prepare print', 'error');
    }
}

// Notifications
function showNotification(message, type = 'info') {
    showNotificationModal(message, type);
}
