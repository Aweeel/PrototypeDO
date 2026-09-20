// ====== Global Variables ======
let allUsers = [];
let filteredUsers = [];
let selectedUserIds = new Set();
let currentPage = 1;
let itemsPerPage = 7;

// ====== Initialization ======
document.addEventListener('DOMContentLoaded', function () {
    loadUsers();
    setupEventDelegation();
})

// Setup event delegation for action buttons
function setupEventDelegation() {
    document.addEventListener('click', function(e) {
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
            case 'delete':
                deleteUser(userId);
                break;
        }
    });
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
            body: `ajax=1&action=getUsers&search=${encodeURIComponent(searchTerm)}&role=${encodeURIComponent(roleFilter)}&status=${encodeURIComponent(statusFilter)}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
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
            showMessage('Error loading users: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showMessage('Error loading users. Please try again.', 'error');
    }
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

    // Calculate pagination
    const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedUsers = filteredUsers.slice(startIndex, endIndex);

    let tableHTML = paginatedUsers.map(user => `
        ${(() => {
            const isActive = Number(user.is_active) === 1;
            const statusText = isActive ? 'Active' : 'Inactive';
            const details = [];

            if (user.role === 'student' && user.student_id) {
                details.push('Student ID: ' + escapeHtml(user.student_id));
            }

            if (user.role === 'teacher' && user.teacher_id) {
                details.push('Teacher ID: ' + escapeHtml(user.teacher_id));
            }

            if (user.role === 'teacher' && user.teacher_subrole) {
                details.push('Subrole: ' + escapeHtml(formatTeacherSubrole(user.teacher_subrole)));
            }

            if (user.role === 'discipline_office' && user.do_id) {
                details.push('DO ID: ' + escapeHtml(user.do_id));
            }

            return `
        <tr class="group h-[72px] overflow-hidden hover:bg-gray-50 dark:hover:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700 border-l-4 transition-colors ${selectedUserIds.has(user.user_id) ? 'bg-blue-50 dark:bg-slate-800 border-l-blue-600' : 'border-l-transparent'}">
            <td class="px-6 py-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(user.full_name)}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">${details.join(' • ')}</p>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(user.email)}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${getRoleBadgeClass(user.role)}">
                    ${formatRole(user.role)}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${user.contact_number || 'N/A'}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'}">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${user.last_login || '—'}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-nowrap gap-1.5">
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
                    <button data-action="delete" data-user-id="${user.user_id}" 
                        class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer" title="Delete User">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </td>
            <td class="px-3 py-4 text-center whitespace-nowrap">
                <input type="checkbox" class="user-checkbox w-5 h-5 rounded border-gray-300 dark:border-slate-600 text-blue-600 dark:accent-blue-600 cursor-pointer accent-blue-600" data-user-id="${user.user_id}" ${selectedUserIds.has(user.user_id) ? 'checked' : ''} onchange="updateSelection()">
            </td>
        </tr>`;
        })()}
    `).join('');

    // Add empty rows to maintain consistent table height
    const emptyRowsCount = itemsPerPage - paginatedUsers.length;
    for (let i = 0; i < emptyRowsCount; i++) {
        tableHTML += `
            <tr class="h-[72px] border-b border-gray-100 dark:border-slate-700 border-l-4 border-l-transparent">
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
                <td class="px-6 py-4"></td>
            </tr>
        `;
    }

    tableBody.innerHTML = tableHTML;
    updatePaginationInfo();
    updatePaginationButtons();
    syncCheckboxStates();
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
    
    if (!paginationButtons || totalPages <= 1) {
        if (paginationButtons) {
            paginationButtons.innerHTML = '';
        }
        return;
    }

    function renderCompactPaginationDOM(container, currentPage, totalPages, onPageChange) {
        if (!container) return;
        const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
        const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold';
        const disabledClass = 'opacity-50 cursor-not-allowed';
        const ellipsis = 'px-2';
        const maxButtons = 7;

        container.innerHTML = '';
        if (totalPages <= 1) return;

        const appendBtn = (text, enabled, page, isActive) => {
            const el = document.createElement(enabled ? 'button' : 'span');
            el.textContent = text;
            el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
            if (enabled && typeof onPageChange === 'function') el.addEventListener('click', () => onPageChange(page));
            container.appendChild(el);
        };

        appendBtn('« Prev', currentPage > 1, currentPage - 1, false);

        if (totalPages <= maxButtons) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const innerCount = maxButtons - 2;
            let start = Math.max(2, currentPage - Math.floor(innerCount / 2));
            let end = Math.min(totalPages - 1, start + innerCount - 1);
            if (end - start + 1 < innerCount) start = Math.max(2, end - innerCount + 1);

            appendBtn('1', true, 1, currentPage === 1);
            if (start > 2) { const s = document.createElement('span'); s.className = ellipsis; s.textContent = '…'; container.appendChild(s); }

            for (let i = start; i <= end; i++) appendBtn(String(i), true, i, i === currentPage);

            if (end < totalPages - 1) { const s = document.createElement('span'); s.className = ellipsis; s.textContent = '…'; container.appendChild(s); }
            appendBtn(String(totalPages), true, totalPages, currentPage === totalPages);
        }

        appendBtn('Next »', currentPage < totalPages, currentPage + 1, false);
    }

    renderCompactPaginationDOM(paginationButtons, currentPage, totalPages, function(p) { goToPage(p); });
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
    console.log('editUser called:', userId, 'allUsers:', allUsers.length);
    const user = allUsers.find(u => u.user_id == userId);
    if (!user) {
        console.error('User not found:', userId);
        return;
    }
    console.log('Found user:', user);

    const modal = document.getElementById('editModal');
    if (!modal) {
        console.log('Creating editModal...');
        createEditModal();
    }

    try {
        document.getElementById('edit_user_id').value = user.user_id;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_contact_number').value = user.contact_number || '';
        const editTeacherId = document.getElementById('edit_teacher_id');
        const editDoId = document.getElementById('edit_do_id');
        if (editTeacherId) {
            editTeacherId.value = user.teacher_id || '';
        }
        if (editDoId) {
            editDoId.value = user.do_id || '';
        }
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
        console.log('Modal opened');
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
        title: currentStatus ? 'Deactivate User' : 'Activate User',
        message: `Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} ${userLabel}?`,
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
                showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error updating status', 'error');
        });
    });
}

function deleteUser(userId) {
    const user = allUsers.find(u => u.user_id == userId);
    const identifier = user ? `${user.full_name} (${user.email})` : 'this user';

    showConfirmDialog({
        title: 'Delete User',
        message: `Are you sure you want to delete ${identifier}? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'bg-red-600 hover:bg-red-700'
    }).then((confirmed) => {
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'deleteUser');
        formData.append('user_id', userId);

        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('User deleted successfully', 'success');
                loadUsers();
            } else {
                showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error deleting user', 'error');
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
    const messageDiv = document.createElement('div');
    messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium z-[1000] ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        'bg-blue-500'
    } shadow-lg`;
    messageDiv.textContent = message;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
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
        title: 'Activate Users',
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
        title: 'Deactivate Users',
        message: `Deactivate ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}?`,
        confirmText: 'Deactivate',
        confirmClass: 'bg-yellow-600 hover:bg-yellow-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('setInactive', Array.from(selectedUserIds));
    });
}

function bulkDelete() {
    if (selectedUserIds.size === 0) {
        showMessage('No users selected', 'error');
        return;
    }

    showConfirmDialog({
        title: 'Delete Users',
        message: `Delete ${selectedUserIds.size} user${selectedUserIds.size !== 1 ? 's' : ''}? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'bg-red-600 hover:bg-red-700'
    }).then((confirmed) => {
        if (confirmed) performBulkAction('deleteUsers', Array.from(selectedUserIds));
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
            showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error performing bulk action', 'error');
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
        'super_admin': 'Super Admin',
        'discipline_office': 'Discipline Office',
        'teacher': 'Teacher',
        'security': 'Security',
        'student': 'Student'
    };
    return roles[role] || role;
}

function formatTeacherSubrole(subrole) {
    const subroles = {
        'department_head': 'Department Head'
    };

    return subroles[subrole] || subrole;
}

function getRoleBadgeClass(role) {
    const classes = {
        'super_admin': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 font-semibold',
        'discipline_office': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-semibold',
        'teacher': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-semibold',
        'security': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 font-semibold',
        'student': 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300 font-semibold'
    };
    return classes[role] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 font-semibold';
}
