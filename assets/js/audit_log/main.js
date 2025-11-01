// Global variables
let allLogs = [];
let filteredLogs = [];
let currentPage = 1;
const logsPerPage = 15;
let currentFilters = {
    search: '',
    actionType: '',
    user: '',
    tableName: '',
    dateFrom: '',
    dateTo: ''
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadActionTypes();
    loadTableNames();
    loadLogs();
});

// Load users for filter dropdown
async function loadUsers() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getUsers');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            const userFilter = document.getElementById('userFilter');
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.user_id;
                option.textContent = user.name;
                userFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Load action types for filter dropdown
async function loadActionTypes() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getActionTypes');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            const actionFilter = document.getElementById('actionTypeFilter');
            data.actionTypes.forEach(action => {
                const option = document.createElement('option');
                option.value = action.action;
                option.textContent = action.action;
                actionFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading action types:', error);
    }
}

// Load table names for filter dropdown
async function loadTableNames() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getTableNames');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            const tableFilter = document.getElementById('tableFilter');
            data.tableNames.forEach(table => {
                const option = document.createElement('option');
                option.value = table.table_name;
                option.textContent = formatTableName(table.table_name);
                tableFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading table names:', error);
    }
}

// Format table name for display
function formatTableName(tableName) {
    return tableName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Load audit logs
async function loadLogs() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getAuditLogs');
        formData.append('search', currentFilters.search);
        formData.append('actionType', currentFilters.actionType);
        formData.append('user', currentFilters.user);
        formData.append('tableName', currentFilters.tableName);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            allLogs = data.logs;
            filteredLogs = [...allLogs];
            currentPage = 1;
            renderTable();
            updatePagination();
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showNotification('Error loading audit logs', 'error');
    }
}

// Render table
function renderTable() {
    const tbody = document.getElementById('logsTableBody');
    const startIndex = (currentPage - 1) * logsPerPage;
    const endIndex = startIndex + logsPerPage;
    const logsToShow = filteredLogs.slice(startIndex, endIndex);

    if (logsToShow.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">No audit logs found</p>
                    <p class="text-sm mt-1">Try adjusting your filters</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = logsToShow.map(log => `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                #${log.id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                ${log.user}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">
                    ${log.action}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                ${formatTableName(log.table)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                ${log.timestamp}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                ${log.ipAddress}
            </td>

    `).join('');
}

// Filter logs
function filterLogs() {
    currentFilters.search = document.getElementById('searchInput').value.toLowerCase();
    currentFilters.actionType = document.getElementById('actionTypeFilter').value;
    currentFilters.user = document.getElementById('userFilter').value;
    currentFilters.tableName = document.getElementById('tableFilter').value;

    filteredLogs = allLogs.filter(log => {
        const matchesSearch = !currentFilters.search || 
            log.user.toLowerCase().includes(currentFilters.search) ||
            log.action.toLowerCase().includes(currentFilters.search) ||
            log.table.toLowerCase().includes(currentFilters.search) ||
            log.ipAddress.toLowerCase().includes(currentFilters.search) ||
            (log.recordId && log.recordId.toLowerCase().includes(currentFilters.search));

        const matchesAction = !currentFilters.actionType || log.action === currentFilters.actionType;
        const matchesUser = !currentFilters.user || log.userId == currentFilters.user;
        const matchesTable = !currentFilters.tableName || log.table === currentFilters.tableName;

        return matchesSearch && matchesAction && matchesUser && matchesTable;
    });

    currentPage = 1;
    renderTable();
    updatePagination();
}

// Sort logs
function sortLogs() {
    const sortValue = document.getElementById('sortFilter').value;

    switch (sortValue) {
        case 'newest':
            filteredLogs.sort((a, b) => b.id - a.id);
            break;
        case 'oldest':
            filteredLogs.sort((a, b) => a.id - b.id);
            break;
        case 'user':
            filteredLogs.sort((a, b) => a.user.localeCompare(b.user));
            break;
        case 'action':
            filteredLogs.sort((a, b) => a.action.localeCompare(b.action));
            break;
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

    if (totalPages <= 1) return;

    // Previous button
    const prevBtn = createPaginationButton('Previous', currentPage > 1, () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
            updatePagination();
        }
    });
    buttonsContainer.appendChild(prevBtn);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            const pageBtn = createPaginationButton(i.toString(), true, () => {
                currentPage = i;
                renderTable();
                updatePagination();
            }, i === currentPage);
            buttonsContainer.appendChild(pageBtn);
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            const dots = document.createElement('span');
            dots.className = 'px-3 py-2 text-gray-500';
            dots.textContent = '...';
            buttonsContainer.appendChild(dots);
        }
    }

    // Next button
    const nextBtn = createPaginationButton('Next', currentPage < totalPages, () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
            updatePagination();
        }
    });
    buttonsContainer.appendChild(nextBtn);
}

function createPaginationButton(text, enabled, onClick, isActive = false) {
    const button = document.createElement('button');
    button.textContent = text;
    button.onclick = enabled ? onClick : null;
    
    if (isActive) {
        button.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg font-medium';
    } else if (enabled) {
        button.className = 'px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors';
    } else {
        button.className = 'px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg opacity-50 cursor-not-allowed';
    }
    
    return button;
}

// Date range modal
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
    closeDateRangeModal();
    loadLogs();
}

// View details modal
function viewDetails(logId) {
    const log = allLogs.find(l => l.id === logId);
    if (!log) return;

    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('detailsContent');

    let oldValuesHtml = '';
    let newValuesHtml = '';

    // Parse old values if available
    if (log.oldValues) {
        try {
            const oldData = JSON.parse(log.oldValues);
            oldValuesHtml = `
                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Old Values</label>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                        <pre class="text-xs overflow-x-auto text-gray-900 dark:text-gray-100 whitespace-pre-wrap">${JSON.stringify(oldData, null, 2)}</pre>
                    </div>
                </div>
            `;
        } catch (e) {
            oldValuesHtml = `
                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Old Values</label>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-xs text-gray-700 dark:text-gray-300">${log.oldValues}</p>
                    </div>
                </div>
            `;
        }
    }

    // Parse new values if available
    if (log.newValues) {
        try {
            const newData = JSON.parse(log.newValues);
            newValuesHtml = `
                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">New Values</label>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                        <pre class="text-xs overflow-x-auto text-gray-900 dark:text-gray-100 whitespace-pre-wrap">${JSON.stringify(newData, null, 2)}</pre>
                    </div>
                </div>
            `;
        } catch (e) {
            newValuesHtml = `
                <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">New Values</label>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                        <p class="text-xs text-gray-700 dark:text-gray-300">${log.newValues}</p>
                    </div>
                </div>
            `;
        }
    }

    content.innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Log ID</label>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">#${log.id}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">User</label>
                    <p class="text-gray-900 dark:text-gray-100">${log.user}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Action</label>
                    <p><span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">${log.action}</span></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Table</label>
                    <p class="text-gray-900 dark:text-gray-100">${formatTableName(log.table)}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Record ID</label>
                    <p class="text-gray-900 dark:text-gray-100">${log.recordId || 'N/A'}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Timestamp</label>
                    <p class="text-gray-900 dark:text-gray-100">${log.timestamp}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">IP Address</label>
                    <p class="text-gray-900 dark:text-gray-100">${log.ipAddress}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">User Agent</label>
                    <p class="text-gray-900 dark:text-gray-100 text-xs truncate" title="${log.userAgent}">${log.userAgent}</p>
                </div>
            </div>

            ${oldValuesHtml}
            ${newValuesHtml}
        </div>
    `;

    modal.classList.remove('hidden');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

// Export logs to CSV
async function exportLogs() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'exportLogs');
        formData.append('search', currentFilters.search);
        formData.append('actionType', currentFilters.actionType);
        formData.append('user', currentFilters.user);
        formData.append('tableName', currentFilters.tableName);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showNotification('Audit logs exported successfully', 'success');
    } catch (error) {
        console.error('Error exporting logs:', error);
        showNotification('Error exporting logs', 'error');
    }
}

// Refresh logs
function refreshLogs() {
    loadLogs();
    showNotification('Audit logs refreshed', 'success');
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDateRangeModal();
        closeDetailsModal();
    }
});