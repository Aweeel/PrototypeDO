// ====== Global Variables ======
let allUsers = [];
let filteredUsers = [];
let selectedUserIds = new Set();
let currentPage = 1;
let itemsPerPage = 7;
let archiveMode = false;
let sortColumn = null;
let sortDirection = 'asc';
const MIN_WIDTHS = [200, 190, 140, 125, 100, 130];
const START_WIDTHS = [300, 280, 150, 150, 120, 150];
const FIXED_WIDTHS = [175, 70];
const sumWidths = (list) => list.reduce((total, width) => total + width, 0);
let columnWidths = [...START_WIDTHS];
let tableResizeObserver = null;

// ====== Initialization ======
document.addEventListener('DOMContentLoaded', function () {
    loadUsers();
    setupEventDelegation();
    setupTableSizing();
})

// Setup event delegation for action buttons
function setupEventDelegation() {
    document.addEventListener('click', function(e) {
        const sortButton = e.target.closest('button[data-sort]');
        if (sortButton) {
            const column = sortButton.dataset.sort;
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            currentPage = 1;
            renderUsers();
            return;
        }

        // Make sure we get the button even if SVG is clicked
        const button = e.target.closest('button[data-action]');
        
        if (!button) return;
        
        const action = button.getAttribute('data-action');
        const userId = parseInt(button.getAttribute('data-user-id'));
        
        switch(action) {
            case 'edit':
                editUser(userId);
                break;
            case 'reset':
                resetPassword(userId);
                break;
            case 'toggle':
                const status = button.getAttribute('data-status') === '1';
                toggleUserStatus(userId, status);
                break;
            case 'archive':
                archiveUser(userId);
                break;
            case 'restore':
                restoreUser(userId);
                break;
        }
    });

    document.querySelectorAll('.column-resizer').forEach(resizer => {
        resizer.addEventListener('pointerdown', startColumnResize);
    });
}

function setupTableSizing() {
    const container = document.getElementById('usersTableContainer');
    if (!container) return;

    tableResizeObserver = new ResizeObserver(applyColumnWidths);
    tableResizeObserver.observe(container);

    document.querySelectorAll('.column-resizer').forEach(handle => {
        handle.addEventListener('dblclick', () => {
            columnWidths = [...START_WIDTHS];
            applyColumnWidths();
        });
    });

    applyColumnWidths();
}

function fitColumnWidths(availableWidth) {
    const target = Math.max(availableWidth - sumWidths(FIXED_WIDTHS), sumWidths(MIN_WIDTHS));
    let widths = columnWidths.map((width, index) => Math.max(width, MIN_WIDTHS[index]));
    const diff = target - sumWidths(widths);

    if (diff > 0) {
        const total = sumWidths(widths);
        widths = widths.map(width => width + diff * (width / total));
    } else if (diff < 0) {
        const slack = widths.map((width, index) => width - MIN_WIDTHS[index]);
        const totalSlack = sumWidths(slack);
        if (totalSlack > 0) {
            widths = widths.map((width, index) => width + diff * (slack[index] / totalSlack));
        }
    }
    columnWidths = widths;
}

function applyColumnWidths() {
    const table = document.querySelector('.users-table');
    const container = document.getElementById('usersTableContainer');
    if (!table || !container) return;

    const available = Math.floor(container.clientWidth) - 1;
    if (available <= 0) return;
    fitColumnWidths(available);
    const allWidths = [...columnWidths, ...FIXED_WIDTHS];
    const tableWidth = sumWidths(allWidths);
    table.style.width = `${tableWidth}px`;
    table.style.minWidth = `${tableWidth}px`;
    table.querySelectorAll('col').forEach((column, index) => {
        column.style.width = `${allWidths[index]}px`;
    });
}

function startColumnResize(event) {
    const resizer = event.currentTarget;
    const columnIndex = Number(resizer.dataset.columnIndex);
    if (columnIndex < 0 || columnIndex >= columnWidths.length - 1) return;

    event.preventDefault();
    event.stopPropagation();
    const startX = event.clientX;
    const leftStart = columnWidths[columnIndex];
    const pairTotal = leftStart + columnWidths[columnIndex + 1];

    resizer.setPointerCapture(event.pointerId);
    resizer.classList.add('is-resizing');
    document.body.classList.add('is-column-resizing');

    const resize = (moveEvent) => {
        const wanted = leftStart + moveEvent.clientX - startX;
        const left = Math.min(Math.max(wanted, MIN_WIDTHS[columnIndex]), pairTotal - MIN_WIDTHS[columnIndex + 1]);
        columnWidths[columnIndex] = left;
        columnWidths[columnIndex + 1] = pairTotal - left;
        applyColumnWidths();
    };

    const stopResize = () => {
        if (resizer.hasPointerCapture(event.pointerId)) {
            resizer.releasePointerCapture(event.pointerId);
        }
        resizer.classList.remove('is-resizing');
        document.body.classList.remove('is-column-resizing');
        resizer.removeEventListener('pointermove', resize);
        resizer.removeEventListener('pointerup', stopResize);
        resizer.removeEventListener('pointercancel', stopResize);
    };

    resizer.addEventListener('pointermove', resize);
    resizer.addEventListener('pointerup', stopResize);
    resizer.addEventListener('pointercancel', stopResize);
}

// ====== Load Users from Database ======
async function loadUsers() {
    currentPage = 1;
    selectedUserIds.clear();
    updateBulkActionBar();
    
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=getUsers&archive=${archiveMode ? 1 : 0}&search=${encodeURIComponent(searchTerm)}&role=${encodeURIComponent(roleFilter)}&status=${encodeURIComponent(statusFilter)}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        function exportUsersCsv() {
            const params = new URLSearchParams({
                export_csv: '1',
                archive: archiveMode ? '1' : '0',
                search: document.getElementById('searchInput')?.value || '',
                role: document.getElementById('roleFilter')?.value || '',
                status: document.getElementById('statusFilter')?.value || ''
            });
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        }

        const data = await response.json();

        if (data.success) {
            allUsers = data.users;
            filteredUsers = [...allUsers];
            renderUsers();
            loadStats();
            
            // Check if we should open reset password modal for a specific user
            const urlParams = new URLSearchParams(window.location.search);
            const resetUserId = urlParams.get('resetUser');
            
            if (resetUserId) {
                const userIdInt = parseInt(resetUserId);
                const foundUser = allUsers.find(u => u.user_id == userIdInt);
                
                if (foundUser) {
                    // Ensure modals are created
                    const modal = document.getElementById('resetPasswordModal');
                    if (!modal) {
                        createResetPasswordModal();
                    }
                    resetPassword(userIdInt);
                    // Remove the parameter from URL to avoid re-opening on reload
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        } else {
            console.error('Failed to load users:', data.error);
            showMessage('Unable to load users: ' + (data.error || 'The server did not provide a reason. Check your connection and try again.'), 'error');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showMessage('Users could not be loaded because the server did not return a valid response. Check your connection and try again.', 'error');
    }
}

function setArchiveMode(isArchived) {
    archiveMode = isArchived;
    const activeAccountsTab = document.getElementById('activeAccountsTab');
    const archivedAccountsTab = document.getElementById('archivedAccountsTab');

    activeAccountsTab?.classList.toggle('bg-blue-600', !isArchived);
    activeAccountsTab?.classList.toggle('text-white', !isArchived);
    activeAccountsTab?.classList.toggle('bg-white', isArchived);
    activeAccountsTab?.classList.toggle('text-gray-700', isArchived);
    activeAccountsTab?.classList.toggle('dark:bg-slate-700', isArchived);
    activeAccountsTab?.classList.toggle('dark:text-gray-300', isArchived);

    archivedAccountsTab?.classList.toggle('bg-blue-600', isArchived);
    archivedAccountsTab?.classList.toggle('text-white', isArchived);
    archivedAccountsTab?.classList.toggle('bg-white', !isArchived);
    archivedAccountsTab?.classList.toggle('text-gray-700', !isArchived);
    archivedAccountsTab?.classList.toggle('dark:bg-slate-700', !isArchived);
    archivedAccountsTab?.classList.toggle('dark:text-gray-300', !isArchived);
    document.getElementById('bulkArchiveButton')?.classList.toggle('hidden', isArchived);
    document.getElementById('bulkRestoreButton')?.classList.toggle('hidden', !isArchived);
    loadUsers();
}

// ====== Filter Users ======
function filterUsers() {
    loadUsers();
}

// ====== Change Items Per Page ======
function changeItemsPerPage(value) {
    itemsPerPage = parseInt(value);
    currentPage = 1;
    renderUsers();
    updatePaginationInfo();
    updatePaginationButtons();
}

// ====== Render Users Table ======
function renderUsers() {
    const tableBody = document.getElementById('usersTableBody');
    const getStatusToggleIcon = (isActive) => isActive
        ? `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        `
        : `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        `;

    if (filteredUsers.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm6-12h-2m0 0h-2m2 0v2m0-2v-2" />
                    </svg>
                    No users found
                </td>
            </tr>
        `;
        updatePaginationInfo();
        updatePaginationButtons();
        return;
    }

    const sortedUsers = sortColumn
        ? [...filteredUsers].sort((a, b) => {
            const first = String(a[sortColumn] ?? '').toLowerCase();
            const second = String(b[sortColumn] ?? '').toLowerCase();
            return first.localeCompare(second, undefined, { numeric: true }) * (sortDirection === 'asc' ? 1 : -1);
        })
        : [...filteredUsers];
    document.querySelectorAll('button[data-sort]').forEach(button => {
        const active = button.dataset.sort === sortColumn;
        const indicator = button.querySelector('.table-control-sort-indicator');
        indicator?.classList.toggle('is-active', active);
        indicator?.classList.toggle('is-ascending', active && sortDirection === 'asc');
        indicator?.classList.toggle('is-descending', active && sortDirection === 'desc');
        button.setAttribute('aria-sort', active ? sortDirection : 'none');
    });

    // Calculate pagination
    const totalPages = Math.ceil(sortedUsers.length / itemsPerPage);
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedUsers = sortedUsers.slice(startIndex, endIndex);

    let tableHTML = paginatedUsers.map(user => `
        ${(() => {
            const isActive = Number(user.is_active) === 1;
            const statusText = isActive ? 'Active' : 'Inactive';
            const details = [];

            const roleId = user.role === 'system_manager'
                ? user.manager_id
                : user.role === 'security'
                    ? user.security_id
                    : user.role_id;
            if (roleId) {
                details.push((user.role === 'system_manager' ? 'Manager ID' : user.role === 'security' ? 'Security ID' : 'Role ID') + ': ' + escapeHtml(roleId));
            }

            const detailsMarkup = [
                details.length ? `<span>${details.join(' • ')}</span>` : '',
                user.role === 'teacher' && user.teacher_subrole
                    ? `<span class="block">Subrole: ${escapeHtml(formatTeacherSubrole(user.teacher_subrole))}</span>`
                    : ''
            ].join('');

            return `
        <tr class="group h-[72px] overflow-hidden hover:bg-gray-50 dark:hover:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700 transition-colors ${selectedUserIds.has(user.user_id) ? 'bg-blue-50 dark:bg-slate-800' : ''}">
            <td class="h-[72px] overflow-hidden px-4 py-4 align-middle ${selectedUserIds.has(user.user_id) ? 'shadow-[inset_4px_0_0_0_#2563eb]' : ''}" title="${escapeHtml(user.full_name)}">
                <div class="h-10 overflow-hidden">
                    <p class="truncate font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(user.full_name)}</p>
                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">${detailsMarkup}</p>
                </div>
            </td>
            <td class="h-[72px] overflow-hidden px-4 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle whitespace-nowrap" title="${escapeHtml(user.email)}">${escapeHtml(user.email)}</td>
            <td class="h-[72px] overflow-hidden px-4 py-4 align-middle" title="${escapeHtml(formatRole(user.role))}">
                <span class="inline-flex max-w-full items-center overflow-hidden text-ellipsis whitespace-nowrap px-3 py-1 rounded-full text-xs font-semibold ${getRoleBadgeClass(user.role)}">
                    ${formatRole(user.role)}
                </span>
            </td>
            <td class="h-[72px] overflow-hidden px-4 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle whitespace-nowrap" title="${escapeHtml(user.contact_number || 'N/A')}">${escapeHtml(user.contact_number || 'N/A')}</td>
            <td class="h-[72px] overflow-hidden px-4 py-4 align-middle" title="${statusText}">
                <span class="inline-flex max-w-full items-center overflow-hidden text-ellipsis whitespace-nowrap px-3 py-1 rounded-full text-xs font-semibold ${isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'}">
                    ${statusText}
                </span>
            </td>
            <td class="h-[72px] overflow-hidden px-4 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle whitespace-nowrap" title="${escapeHtml(user.last_login || '—')}">${escapeHtml(user.last_login || '—')}</td>
            <td class="h-[72px] overflow-hidden px-4 py-4 align-middle whitespace-nowrap" title="Actions">
                <div class="flex w-max flex-nowrap gap-1.5">
                    <button data-action="edit" data-user-id="${user.user_id}" 
                        class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors cursor-pointer" title="Edit User">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button data-action="reset" data-user-id="${user.user_id}" 
                        class="relative p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors cursor-pointer" title="Reset Password">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        ${user.has_pending_reset ? '<span class="absolute top-0 right-0 inline-flex items-center justify-center h-5 w-5 rounded-full text-xs font-bold bg-red-500 text-white">!</span>' : ''}
                    </button>
                    <button data-action="toggle" data-user-id="${user.user_id}" data-status="${isActive ? 1 : 0}" 
                        class="p-2 ${isActive ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20'} rounded-lg transition-colors cursor-pointer" title="${isActive ? 'Deactivate User' : 'Activate User'}">
                        ${getStatusToggleIcon(isActive)}
                    </button>
                    <button data-action="${archiveMode ? 'restore' : 'archive'}" data-user-id="${user.user_id}" 
                        class="p-2 ${archiveMode ? 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20' : 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'} rounded-lg transition-colors cursor-pointer" title="${archiveMode ? 'Restore User' : 'Archive User'}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            ${archiveMode
                                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 7l-3 3 3 3m10-6l3 3-3 3M4 10h16m-4 4v4m-4-4v4m-4-4v4" />'
                                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l2-4h14l2 4M4 8h16v12H4V8zm4 4h8" />'}
                        </svg>
                    </button>
                </div>
            </td>
            <td class="h-[72px] overflow-hidden px-3 py-4 text-center align-middle whitespace-nowrap">
                <input type="checkbox" class="user-checkbox w-5 h-5 rounded border-gray-300 dark:border-slate-600 text-blue-600 dark:accent-blue-600 cursor-pointer accent-blue-600" data-user-id="${user.user_id}" ${selectedUserIds.has(user.user_id) ? 'checked' : ''} onchange="updateSelection()">
            </td>
        </tr>`;
        })()}
    `).join('');

    // Add empty rows to maintain consistent table height
    const emptyRowsCount = itemsPerPage - paginatedUsers.length;
    for (let i = 0; i < emptyRowsCount; i++) {
        tableHTML += `
            <tr class="h-[72px] border-b border-gray-100 dark:border-slate-700">
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
                <td class="px-4 py-4"></td>
            </tr>
        `;
    }

    tableBody.innerHTML = tableHTML;
    updatePaginationInfo();
    updatePaginationButtons();
    syncCheckboxStates();
}

function removeUserFromCurrentView(userId) {
    allUsers = allUsers.filter(user => user.user_id != userId);
    filteredUsers = filteredUsers.filter(user => user.user_id != userId);
    selectedUserIds.delete(userId);

    const totalPages = Math.ceil(filteredUsers.length / itemsPerPage) || 1;
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    renderUsers();
    updateBulkActionBar();
}

// ====== Sync Checkbox States ======
function syncCheckboxStates() {
    // Explicitly set .checked property to match selectedUserIds for each visible checkbox
    // This ensures the visual state is correct when navigating between pages
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        const userId = parseInt(checkbox.getAttribute('data-user-id'));
        checkbox.checked = selectedUserIds.has(userId);
    });
}

// ====== Update Pagination Info ======
function updatePaginationInfo() {
    const totalUsers = filteredUsers.length;
    const totalPages = Math.ceil(totalUsers / itemsPerPage) || 1;
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, totalUsers);
    
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        if (totalUsers === 0) {
            paginationInfo.textContent = 'No users found';
        } else {
            paginationInfo.textContent = `Showing ${startIndex}-${endIndex} of ${totalUsers} user${totalUsers !== 1 ? 's' : ''} (Page ${currentPage} of ${totalPages})`;
        }
    }
}

// ====== Update Pagination Buttons ======
function updatePaginationButtons() {
    const totalUsers = filteredUsers.length;
    const totalPages = Math.ceil(totalUsers / itemsPerPage) || 1;
    const paginationButtons = document.getElementById('paginationButtons');
    
    if (!paginationButtons) {
        if (paginationButtons) {
            paginationButtons.innerHTML = '';
        }
        return;
    }

    function renderCompactPaginationDOM(container, currentPage, totalPages, onPageChange) {
        if (!container) return;
        const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center';
        const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center';
        const disabledClass = 'opacity-50 cursor-not-allowed';
        const pageWindow = 1;

        container.innerHTML = '';
        const appendBtn = (text, enabled, page, isActive) => {
            const el = document.createElement(enabled ? 'button' : 'span');
            el.textContent = text;
            el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
            if (enabled && typeof onPageChange === 'function') el.addEventListener('click', () => onPageChange(page));
            container.appendChild(el);
        };

        appendBtn('« Prev', currentPage > 1, Math.max(1, currentPage - 1), false);

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
                    button.className = 'px-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
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

    renderCasesStylePagination(paginationButtons, currentPage, totalPages, goToPage);
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

// ====== Go To Page ======
function goToPage(page) {
    const totalPages = Math.ceil(filteredUsers.length / itemsPerPage) || 1;
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderUsers();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// ====== User Actions ======
function editUser(userId) {
    const user = allUsers.find(u => u.user_id == userId);
    if (!user) {
        console.error('User not found:', userId);
        return;
    }

    const modal = document.getElementById('editModal');
    if (!modal) {
        createEditModal();
    }

    try {
        document.getElementById('edit_user_id').value = user.user_id;
        document.getElementById('edit_email').value = (user.email || '').replace(/@sti\.edu(?:\.ph)?$/i, '');
        const nameParts = (user.full_name || '').trim().split(/\s+/);
        document.getElementById('edit_first_name').value = user.first_name || nameParts.shift() || '';
        document.getElementById('edit_last_name').value = user.last_name || (nameParts.pop() || '');
        document.getElementById('edit_middle_name').value = user.middle_name || nameParts.join(' ');
        document.getElementById('edit_role').value = user.role;
        const roleIdFields = {
            system_manager: 'edit_manager_id',
            security: 'edit_security_id',
            teacher: 'edit_teacher_id',
            discipline_office: 'edit_do_id',
            student: 'edit_student_id'
        };
        const roleIdField = document.getElementById(roleIdFields[user.role]);
        if (roleIdField) {
                roleIdField.value = user.role === 'student'
                ? (user.role_id || user.student_id || '').replace(/^02000/, '')
                    : user.role === 'system_manager'
                        ? (user.manager_id || '')
                        : user.role === 'security'
                            ? (user.security_id || '')
                            : (user.role_id || user.teacher_id || user.do_id || '');
        }
        document.getElementById('edit_contact_number').value = (user.contact_number || '')
            .replace(/\D/g, '')
            .replace(/^09/, '')
            .slice(0, 9)
            .replace(/^(\d{0,2})(\d{0,3})(\d{0,4}).*$/, function(_, a, b, c) {
                return a + (b ? ' ' + b : '') + (c ? ' ' + c : '');
            });
        const editTeacherId = document.getElementById('edit_teacher_id');
        const editDoId = document.getElementById('edit_do_id');
        const editStudentId = document.getElementById('edit_student_id');
        if (editTeacherId) {
            editTeacherId.value = user.teacher_id || '';
        }
        if (editDoId) {
            editDoId.value = user.do_id || '';
        }
        if (editStudentId) {
            editStudentId.value = user.student_id || '';
        }
        document.getElementById('edit_grade_year').value = user.grade_year || '';
        document.getElementById('edit_track_course').value = user.track_course || '';
        document.getElementById('edit_section').value = user.section || '';
        document.getElementById('edit_student_type').value = user.student_type || '';
        document.getElementById('edit_guardian_name').value = user.guardian_name || '';
        document.getElementById('edit_guardian_contact').value = user.guardian_contact || '';
        const editTeacherSubrole = document.getElementById('edit_teacher_subrole');
        if (editTeacherSubrole) {
            editTeacherSubrole.value = user.teacher_subrole || '';
        }
        const editProgram = document.getElementById('edit_program');
        if (editProgram) {
            editProgram.value = user.program || '';
        }
        if (typeof handleRoleChange === 'function') {
            handleRoleChange('edit');
        }
        document.getElementById('editModal').classList.remove('hidden');
    } catch(e) {
        console.error('Error in editUser:', e);
    }
}

function resetPassword(userId) {
    const user = allUsers.find(u => u.user_id == userId);
    if (!user) {
        return;
    }

    // Ensure modal exists
    let modal = document.getElementById('resetPasswordModal');
    if (!modal) {
        createResetPasswordModal();
        modal = document.getElementById('resetPasswordModal');
    }
    
    if (!modal) {
        return;
    }

    try {
        const resetUserIdEl = document.getElementById('reset_user_id');
        const resetUsernameEl = document.getElementById('reset_username');
        
        if (!resetUserIdEl || !resetUsernameEl) {
            return;
        }
        
        resetUserIdEl.value = user.user_id;
        resetUsernameEl.textContent = user.email;
        
        // Show modal
        modal.classList.remove('hidden');
    } catch(e) {
        console.error('Error in resetPassword:', e);
    }
}

function toggleUserStatus(userId, currentStatus) {
    const user = allUsers.find(u => u.user_id == userId);
    const userLabel = user ? `${user.full_name} (${user.email})` : 'this user';

    showConfirmDialog({
        message: `${currentStatus ? 'Deactivate' : 'Activate'} ${userLabel}?`,
        confirmText: currentStatus ? 'Deactivate' : 'Activate',
        confirmClass: currentStatus ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700'
    }).then((confirmed) => {
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'toggleStatus');
        formData.append('user_id', userId);

        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('User status updated successfully', 'success');
                loadUsers();
            } else {
                showMessage('Unable to update user: ' + (data.error || 'The server did not provide a reason. Check the user details and try again.'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('The user status could not be updated because the server did not return a valid response. Check your connection and try again.', 'error');
        });
    });
}

function archiveUser(userId) {
    const user = allUsers.find(u => u.user_id == userId);
    const identifier = user ? `${user.full_name} (${user.email})` : 'this user';

    showConfirmDialog({
        message: `Archive ${identifier}?`,
        confirmText: 'Archive',
        confirmClass: 'bg-red-600 hover:bg-red-700'
    }).then((confirmed) => {
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'archiveUser');
        formData.append('user_id', userId);

        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('User archived successfully', 'success');
                removeUserFromCurrentView(userId);
            } else {
                showMessage('Unable to archive user: ' + (data.error || 'The server did not provide a reason. Check the user account and try again.'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('The user could not be archived because the server did not return a valid response. Check your connection and try again.', 'error');
        });
    });
}

function restoreUser(userId) {
    const user = allUsers.find(u => u.user_id == userId);
    const identifier = user ? `${user.full_name} (${user.email})` : 'this user';

    showConfirmDialog({
        message: `Restore ${identifier}?`,
        confirmText: 'Restore',
        confirmClass: 'bg-green-600 hover:bg-green-700'
    }).then((confirmed) => {
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'restoreUser');
        formData.append('user_id', userId);

        fetch(window.location.pathname, { method: 'POST', body: new URLSearchParams(formData) })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('User restored successfully', 'success');
                    removeUserFromCurrentView(userId);
                } else {
                    showMessage('Unable to restore user: ' + (data.error || 'The server did not provide a reason. Check the user account and try again.'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('The user could not be restored because the server did not return a valid response. Check your connection and try again.', 'error');
            });
    });
}

// ====== Load Statistics ======
async function loadStats() {
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ajax=1&action=getStats'
        });

        if (!response.ok) throw new Error('Failed to load stats');

        const data = await response.json();
        if (data.success) {
            // Stats loaded successfully
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// ====== Message Display ======
function showMessage(message, type = 'info') {
    showNotification(message, type);
}

// ====== Selection Management ======
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const isChecked = selectAllCheckbox.checked;
    
    // Simply update all visible checkboxes to match the header state
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    // Let updateSelection() be the single source of truth - it syncs DOM with selectedUserIds
    updateSelection();
}

function updateSelectAllPageButtons() {
    // Buttons have been removed - no action needed
}

function updatePageCheckboxState() {
    // Update the header checkbox to reflect current page's selection state (if it exists)
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        const totalCheckboxes = document.querySelectorAll('.user-checkbox').length;
        const checkedCheckboxes = document.querySelectorAll('.user-checkbox:checked').length;
        
        selectAllCheckbox.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
        selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
    }
}

function clearAllPages() {
    // Clear all visible checkboxes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('tr').classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
    });
    
    // Manually clear all selections since we're clearing across all pages
    selectedUserIds.clear();
    
    updateBulkActionBar();
    updatePageCheckboxState();
}

function updateSelection() {
    // Sync current page checkboxes with selectedUserIds
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        const userId = parseInt(checkbox.getAttribute('data-user-id'));
        
        if (checkbox.checked) {
            selectedUserIds.add(userId);
        } else {
            selectedUserIds.delete(userId);
        }
    });
    
    updatePageCheckboxState();
    updateBulkActionBar();
    // Re-render rows to sync highlight via template literal (not classList)
    syncCheckboxStates();
}

function clearSelection() {
    selectedUserIds.clear();
    updateBulkActionBar();
    updatePageCheckboxState();
    renderUsers(); // Re-render so the tr template literal handles highlighting correctly
}

function updateBulkActionBar() {
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedUserIds.size > 0) {
        bulkActionsBar.classList.remove('hidden');
        const totalUsers = filteredUsers.length;
        if (selectedUserIds.size === totalUsers) {
            selectedCount.textContent = `All ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''} selected`;
        } else if (selectedUserIds.size === allUsers.length) {
            selectedCount.textContent = `All ${selectedUserIds.size} users across all pages selected`;
        } else {
            selectedCount.textContent = `${selectedUserIds.size} of ${totalUsers} user${totalUsers !== 1 ? 's' : ''} selected`;
        }
    } else {
        bulkActionsBar.classList.add('hidden');
    }
    
    updateSelectAllPageButtons();
}

// Select all users on current page
function selectThisPageOnly() {
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelection();
}

// Select all users across all pages
function selectAllPages() {
    allUsers.forEach(user => selectedUserIds.add(parseInt(user.user_id)));
    // Only check visible boxes; don't call updateSelection() which reads unchecked DOM boxes
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = true);
    updatePageCheckboxState();
    updateBulkActionBar();
}

// ====== Bulk Actions ======
function bulkSetActive() {
    if (selectedUserIds.size === 0) {
        showMessage('No users selected', 'error');
        return;
    }

    showConfirmDialog({
        message: `Activate ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}?`,
        confirmText: 'Activate',
        confirmClass: 'bg-green-600 hover:bg-green-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('setActive', Array.from(selectedUserIds));
    });
}

function bulkSetInactive() {
    if (selectedUserIds.size === 0) {
        showMessage('No users selected', 'error');
        return;
    }

    showConfirmDialog({
        message: `Deactivate ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}?`,
        confirmText: 'Deactivate',
        confirmClass: 'bg-yellow-600 hover:bg-yellow-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('setInactive', Array.from(selectedUserIds));
    });
}

function bulkArchive() {
    if (selectedUserIds.size === 0) {
        showMessage('No users selected', 'error');
        return;
    }

    showConfirmDialog({
        message: `Archive ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}?`,
        confirmText: 'Archive',
        confirmClass: 'bg-red-600 hover:bg-red-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('archiveUsers', Array.from(selectedUserIds));
    });
}

function bulkRestore() {
    if (selectedUserIds.size === 0) {
        showMessage('No users selected', 'error');
        return;
    }

    showConfirmDialog({
        message: `Restore ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}?`,
        confirmText: 'Restore',
        confirmClass: 'bg-green-600 hover:bg-green-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('restoreUsers', Array.from(selectedUserIds));
    });
}

async function performBulkAction(action, userIds) {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', action);
    formData.append('user_ids', JSON.stringify(userIds));
    
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message || 'Bulk action completed successfully', 'success');
            selectedUserIds.clear();
            loadUsers(); // This already resets everything and handles re-rendering
        } else {
            showMessage('Unable to update users: ' + (data.error || 'The server did not provide a reason. Check the selected accounts and try again.'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('The bulk action could not be completed because the server did not return a valid response. Check your connection and try again.', 'error');
    }
}

// ====== Utility Functions ======
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatRole(role) {
    const roles = {
        'system_manager': 'System Manager',
        'discipline_office': 'Discipline Office',
        'teacher': 'Teacher',
        'security': 'Security',
        'student': 'Student'
    };
    return roles[role] || String(role || '').replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
}

function formatTeacherSubrole(subrole) {
    const subroles = {
        'department_head': 'Department Head'
    };

    return subroles[subrole] || subrole;
}

function getRoleBadgeClass(role) {
    const classes = {
        'system_manager': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 font-semibold',
        'discipline_office': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-semibold',
        'teacher': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-semibold',
        'security': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 font-semibold',
        'student': 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300 font-semibold'
    };
    return classes[role] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 font-semibold';
}
