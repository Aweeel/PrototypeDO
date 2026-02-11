// ====== Modal Management ======

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === resetPasswordModal) {
        closeResetPasswordModal();
    }
});

// Close modals with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddModal();
        closeEditModal();
        closeResetPasswordModal();
    }
});

// ====== Add User Modal ======
function openAddModal() {
    const modal = document.getElementById('addModal');
    if (!modal) {
        createAddModal();
    }
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('add_form').reset();
}

function closeAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function createAddModal() {
    const modal = document.createElement('div');
    modal.id = 'addModal';
    modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Add New User</h2>
                <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add_form" onsubmit="submitAddUser(event)" class="p-6 space-y-4">
                <div>
                    <label for="add_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username *</label>
                    <input type="text" id="add_username" name="username" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="add_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                    <input type="email" id="add_email" name="email" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="add_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                    <input type="text" id="add_full_name" name="full_name" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="add_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select id="add_role" name="role" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Role --</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="discipline_office">Discipline Office</option>
                        <option value="teacher">Teacher</option>
                        <option value="security">Security</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div>
                    <label for="add_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                    <input type="tel" id="add_contact_number" name="contact_number" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="add_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password *</label>
                    <input type="password" id="add_password" name="password" required minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 6 characters</p>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeAddModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

async function submitAddUser(event) {
    event.preventDefault();
    
    const username = document.getElementById('add_username').value;
    const email = document.getElementById('add_email').value;
    const full_name = document.getElementById('add_full_name').value;
    const role = document.getElementById('add_role').value;
    const contact_number = document.getElementById('add_contact_number').value;
    const password = document.getElementById('add_password').value;

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'createUser');
    formData.append('username', username);
    formData.append('email', email);
    formData.append('full_name', full_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('password', password);

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();
        
        if (data.success) {
            showMessage('User created successfully', 'success');
            closeAddModal();
            loadUsers();
        } else {
            showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error creating user', 'error');
    }
}

// ====== Edit User Modal ======
function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function createEditModal() {
    const modal = document.createElement('div');
    modal.id = 'editModal';
    modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Edit User</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit_form" onsubmit="submitEditUser(event)" class="p-6 space-y-4">
                <input type="hidden" id="edit_user_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <p id="edit_username" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"></p>
                </div>
                <div>
                    <label for="edit_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                    <input type="email" id="edit_email" name="email" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="edit_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                    <input type="text" id="edit_full_name" name="full_name" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="edit_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select id="edit_role" name="role" required 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                        <option value="super_admin">Super Admin</option>
                        <option value="discipline_office">Discipline Office</option>
                        <option value="teacher">Teacher</option>
                        <option value="security">Security</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div>
                    <label for="edit_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                    <input type="tel" id="edit_contact_number" name="contact_number" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                    <input type="checkbox" id="edit_is_active" name="is_active" 
                        class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                    <label for="edit_is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

async function submitEditUser(event) {
    event.preventDefault();
    
    const user_id = document.getElementById('edit_user_id').value;
    const email = document.getElementById('edit_email').value;
    const full_name = document.getElementById('edit_full_name').value;
    const role = document.getElementById('edit_role').value;
    const contact_number = document.getElementById('edit_contact_number').value;
    const is_active = document.getElementById('edit_is_active').checked ? 1 : 0;

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'updateUser');
    formData.append('user_id', user_id);
    formData.append('email', email);
    formData.append('full_name', full_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('is_active', is_active);

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();
        
        if (data.success) {
            showMessage('User updated successfully', 'success');
            closeEditModal();
            loadUsers();
        } else {
            showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error updating user', 'error');
    }
}

// ====== Reset Password Modal ======
function closeResetPasswordModal() {
    const modal = document.getElementById('resetPasswordModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function createResetPasswordModal() {
    const modal = document.createElement('div');
    modal.id = 'resetPasswordModal';
    modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Reset Password</h2>
                <button onclick="closeResetPasswordModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="reset_form" onsubmit="submitResetPassword(event)" class="p-6 space-y-4">
                <input type="hidden" id="reset_user_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <p id="reset_username" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"></p>
                </div>
                <div>
                    <label for="reset_new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password *</label>
                    <input type="password" id="reset_new_password" name="new_password" required minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 6 characters</p>
                </div>
                <div>
                    <label for="reset_confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password *</label>
                    <input type="password" id="reset_confirm_password" name="confirm_password" required minlength="6"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeResetPasswordModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

async function submitResetPassword(event) {
    event.preventDefault();
    
    const new_password = document.getElementById('reset_new_password').value;
    const confirm_password = document.getElementById('reset_confirm_password').value;
    
    if (new_password !== confirm_password) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    const user_id = document.getElementById('reset_user_id').value;

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'resetPassword');
    formData.append('user_id', user_id);
    formData.append('new_password', new_password);

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();
        
        if (data.success) {
            showMessage('Password reset successfully', 'success');
            closeResetPasswordModal();
        } else {
            showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error resetting password', 'error');
    }
}

// ====== Initialize Modals ======
document.addEventListener('DOMContentLoaded', function() {
    createAddModal();
    createEditModal();
    createResetPasswordModal();
});
