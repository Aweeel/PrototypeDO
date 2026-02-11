// ====== Global Variables ======
let allUsers = [];
let filteredUsers = [];

// ====== Initialization ======
document.addEventListener('DOMContentLoaded', function () {
    console.log('Admin Users page loaded');
    loadUsers();
});

// ====== Load Users from Database ======
async function loadUsers() {
    console.log('Loading users from database...');
    
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
            console.log('Loaded users:', allUsers.length);
            renderUsers();
            loadStats();
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
    console.log('Filtering users...');
    loadUsers();
}

// ====== Render Users Table ======
function renderUsers() {
    console.log('Rendering users...');
    
    const tableBody = document.getElementById('usersTableBody');

    if (filteredUsers.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm6-12h-2m0 0h-2m2 0v2m0-2v-2" />
                    </svg>
                    No users found
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = filteredUsers.map(user => `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
            <td class="px-6 py-4">
                <div>
                    <p class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(user.username)}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(user.full_name)}</p>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${escapeHtml(user.email)}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleBadgeClass(user.role)}">
                    ${formatRole(user.role)}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${user.contact_number || 'N/A'}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.is_active ? 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'}">
                    ${user.status}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${user.last_login}</td>
            <td class="px-6 py-4">
                <div class="flex gap-2">
                    <button onclick="editUser(${user.user_id})" 
                        class="px-3 py-1.5 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit User">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button onclick="resetPassword(${user.user_id})" 
                        class="px-3 py-1.5 text-sm text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors" title="Reset Password">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </button>
                    <button onclick="toggleUserStatus(${user.user_id}, ${user.is_active})" 
                        class="px-3 py-1.5 text-sm text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors" title="Toggle Status">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <button onclick="deleteUser(${user.user_id})" 
                        class="px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete User">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    updatePaginationInfo();
}

// ====== Update Pagination Info ======
function updatePaginationInfo() {
    const totalUsers = filteredUsers.length;
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        paginationInfo.textContent = `Showing ${totalUsers} user${totalUsers !== 1 ? 's' : ''}`;
    }
}

// ====== Update Pagination Buttons ======
function updatePaginationButtons() {
    // Not needed if showing all users at once
}

// ====== User Actions ======
function editUser(userId) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;

    const modal = document.getElementById('editModal');
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_username').textContent = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_contact_number').value = user.contact_number || '';
    document.getElementById('edit_is_active').checked = user.is_active === 1;
    
    modal.classList.remove('hidden');
}

function resetPassword(userId) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;

    const modal = document.getElementById('resetPasswordModal');
    document.getElementById('reset_user_id').value = user.user_id;
    document.getElementById('reset_username').textContent = user.username;
    document.getElementById('reset_new_password').value = '';
    document.getElementById('reset_confirm_password').value = '';
    
    modal.classList.remove('hidden');
}

function toggleUserStatus(userId, currentStatus) {
    if (!confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this user?')) {
        return;
    }

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
}

function deleteUser(userId) {
    const user = allUsers.find(u => u.user_id === userId);
    const username = user ? user.username : 'Unknown';
    
    if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        return;
    }

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
            // You can display stats here if needed
            console.log('Stats loaded:', data.stats);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// ====== Message Display ======
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium z-50 ${
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

function getRoleBadgeClass(role) {
    const classes = {
        'super_admin': 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100',
        'discipline_office': 'bg-purple-100 text-purple-800 dark:bg-[#3F0F5C] dark:text-purple-100',
        'teacher': 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
        'security': 'bg-orange-100 text-orange-800 dark:bg-[#7C2D12] dark:text-orange-100',
        'student': 'bg-gray-100 text-gray-800 dark:bg-[#374151] dark:text-gray-100'
    };
    return classes[role] || 'bg-gray-100 text-gray-800 dark:bg-[#374151] dark:text-gray-100';
}
