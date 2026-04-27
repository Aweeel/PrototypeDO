// Global variables
let allLogs = [];
let filteredLogs = [];
let currentPage = 1;
const logsPerPage = 10;
let ADMIN_NAME = '';
let currentFilters = {
    search: '',
    actionType: '',
    user: '',
    dateFrom: '',
    dateTo: ''
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    ADMIN_NAME = window.ADMIN_NAME || document.querySelector('meta[data-admin-name]')?.content || 'User';
    console.log('Final ADMIN_NAME:', ADMIN_NAME);
    loadUsers();
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

// Load user roles for filter dropdown
async function loadUsers() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getUsers');

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        
        console.log('Users/Roles response:', data);
        
        if (data.success) {
            const userFilter = document.getElementById('userFilter');
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.role;
                option.textContent = user.display;
                userFilter.appendChild(option);
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
        
        console.log('Action types response:', data);
        
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
        formData.append('user', currentFilters.user);
        formData.append('dateFrom', currentFilters.dateFrom);
        formData.append('dateTo', currentFilters.dateTo);

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        console.log('Audit logs response:', data);
        
        if (data.success && data.logs) {
            allLogs = data.logs;
            filteredLogs = [...allLogs];
            currentPage = 1;
            renderTable();
            updatePagination();
        } else {
            console.error('Failed to load logs:', data.error || 'No logs in response');
            const tbody = document.getElementById('logsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-red-500 dark:text-red-400">
                        <p class="text-lg font-medium">Error loading audit logs</p>
                        <p class="text-sm mt-1">${data.error || 'Failed to fetch audit logs'}</p>
                    </td>
                </tr>`;
            document.getElementById('paginationInfo').textContent = 'Error loading logs';
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showNotification('Error loading audit logs: ' + error.message, 'error');
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-red-500 dark:text-red-400">
                    <p class="text-lg font-medium">Error loading audit logs</p>
                    <p class="text-sm mt-1">${error.message}</p>
                </td>
            </tr>`;
        document.getElementById('paginationInfo').textContent = 'Error loading logs';
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

    let tableHTML = logsToShow.map(log => `
      <tr class="h-[72px] hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer" onclick="openLogDetailModal(${JSON.stringify(log).replace(/"/g, '&quot;')})">
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">#${log.id}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${log.user}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${log.role}</td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">${log.action}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${log.timestamp}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${log.ipAddress}</td>
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

    if (totalPages <= 1) return;

    const prevBtn = createPaginationButton('Previous', currentPage > 1, () => {
        if (currentPage > 1) { currentPage--; renderTable(); updatePagination(); }
    });
    buttonsContainer.appendChild(prevBtn);

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            const pageBtn = createPaginationButton(i.toString(), true, () => {
                currentPage = i; renderTable(); updatePagination();
            }, i === currentPage);
            buttonsContainer.appendChild(pageBtn);
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            const dots = document.createElement('span');
            dots.className = 'px-3 py-2 text-gray-500';
            dots.textContent = '...';
            buttonsContainer.appendChild(dots);
        }
    }

    const nextBtn = createPaginationButton('Next', currentPage < totalPages, () => {
        if (currentPage < totalPages) { currentPage++; renderTable(); updatePagination(); }
    });
    buttonsContainer.appendChild(nextBtn);
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
                    if (currentFilters.user) filters.push(`Role: ${document.querySelector(`#userFilter option[value="${currentFilters.user}"]`)?.textContent || currentFilters.user}`);
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
                showNotification('Error preparing export', 'error');
            });
    } catch (error) {
        console.error('Error exporting logs:', error);
        showNotification('Error exporting logs', 'error');
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
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">`;
    
    logs.forEach(log => {
        html += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800">
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">#${log.id}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.user}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.role}</td>
                        <td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs font-semibold ${log.actionColor}">${log.action}</span></td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.timestamp}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${log.ipAddress}</td>
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
        showNotification('Error exporting CSV', 'error');
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

        console.log('Print preview ready for audit logs');
        window.print();
    } catch (e) {
        console.error('Print Report Error:', e);
        showNotification('Failed to prepare print', 'error');
    }
}

// Notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-[1000] transition-all duration-300`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
