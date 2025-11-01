// Global variables
let allLogs = [];
let filteredLogs = [];
let currentPage = 1;
const logsPerPage = 10;
let currentFilters = {
    search: '',
    actionType: '',
    user: '',
    dateFrom: '',
    dateTo: ''
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadActionTypes();
    loadLogs();
});

// Load users for filter dropdown
async function loadUsers() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getUsers');

        const response = await fetch(window.location.href, { method: 'POST', body: formData });
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

    tbody.innerHTML = logsToShow.map(log => `
      <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
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


// Notifications
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
