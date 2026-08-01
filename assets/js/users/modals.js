// ====== Modal Management ======

function showConfirmDialog(options = {}) {
    const {
        title = 'Confirm',
        message = '',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        confirmClass = 'bg-blue-600 hover:bg-blue-700',
    } = options;

    return new Promise((resolve) => {
        const existing = document.getElementById('confirmActionModal');
        if (existing) {
            existing.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'confirmActionModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">${title}</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-700 dark:text-gray-300">${message}</p>
                </div>
                <div class="flex gap-3 p-6 pt-0 justify-end">
                    <button type="button" data-confirm-cancel class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        ${cancelText}
                    </button>
                    <button type="button" data-confirm-ok class="px-4 py-2 rounded-lg text-white transition-colors ${confirmClass}">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        const cleanup = (result) => {
            modal.remove();
            resolve(result);
        };

        modal.querySelector('[data-confirm-ok]').addEventListener('click', () => cleanup(true));
        modal.querySelector('[data-confirm-cancel]').addEventListener('click', () => cleanup(false));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                cleanup(false);
            }
        });

        document.addEventListener('keydown', function onKeyDown(event) {
            if (event.key === 'Escape' && document.getElementById('confirmActionModal')) {
                document.removeEventListener('keydown', onKeyDown);
                cleanup(false);
            }
        });

        document.body.appendChild(modal);
    });
}

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
    handleRoleChange('add');
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
                    <label for="add_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select id="add_role" name="role" required onchange="handleRoleChange('add')"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Role --</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="discipline_office">Discipline Office</option>
                        <option value="teacher">Teacher</option>
                        <option value="security">Security</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div id="add_teacher_subrole_container" class="hidden">
                    <label for="add_teacher_subrole" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher Subrole</label>
                    <select id="add_teacher_subrole" name="teacher_subrole"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                        <option value="">-- No Subrole --</option>
                        <option value="department_head">Department Head</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Department Heads can be invited to hearings by the DO.</p>
                </div>
                <div id="add_teacher_id_container" class="hidden">
                    <label for="add_teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher ID *</label>
                    <input type="text" id="add_teacher_id" name="teacher_id" placeholder="01000000001"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required for teacher accounts.</p>
                </div>
                <div id="add_do_id_container" class="hidden">
                    <label for="add_do_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discipline Office ID *</label>
                    <input type="text" id="add_do_id" name="do_id" placeholder="03000000001"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required for discipline office accounts.</p>
                </div>
                <div>
                    <label for="add_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                    <input type="text" id="add_full_name" name="full_name" required placeholder="eg. John Doe"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div id="email_container">
                    <label for="add_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                    <input type="email" id="add_email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="add_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                    <input type="tel" id="add_contact_number" name="contact_number"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <!-- password will default to "password"; user must change on first login -->
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Accounts are created with the default password <strong>password</strong>. Users should change it on their first login.
                </p>
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
    
    const email = document.getElementById('add_email').value;
    const full_name = document.getElementById('add_full_name').value;
    const role = document.getElementById('add_role').value;
    const contact_number = document.getElementById('add_contact_number').value;
    const teacher_subrole = document.getElementById('add_teacher_subrole')?.value || '';
    const teacher_id = document.getElementById('add_teacher_id')?.value || '';
    const do_id = document.getElementById('add_do_id')?.value || '';

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'createUser');
    // username and password are handled server‑side
    formData.append('email', email);
    formData.append('full_name', full_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('teacher_subrole', teacher_subrole);
    formData.append('teacher_id', teacher_id);
    formData.append('do_id', do_id);

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
            <form id="edit_form" onsubmit="submitEditUser(event)" class="p-6">
                <input type="hidden" id="edit_user_id">

                <div class="space-y-4">
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
                            onchange="handleRoleChange('edit')"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                            <option value="super_admin">Super Admin</option>
                            <option value="discipline_office">Discipline Office</option>
                            <option value="teacher">Teacher</option>
                            <option value="security">Security</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div id="edit_teacher_subrole_container" class="hidden">
                        <label for="edit_teacher_subrole" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher Subrole</label>
                        <select id="edit_teacher_subrole" name="teacher_subrole"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                            <option value="">-- No Subrole --</option>
                            <option value="department_head">Department Head</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Department Heads can be invited to hearings by the DO.</p>
                    </div>
                    <div id="edit_teacher_id_container" class="hidden">
                        <label for="edit_teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher ID *</label>
                        <input type="text" id="edit_teacher_id" name="teacher_id" placeholder="01000000001"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div id="edit_do_id_container" class="hidden">
                        <label for="edit_do_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discipline Office ID *</label>
                        <input type="text" id="edit_do_id" name="do_id" placeholder="03000000001"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label for="edit_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                        <input type="tel" id="edit_contact_number" name="contact_number" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
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
    const teacher_subrole = document.getElementById('edit_teacher_subrole')?.value || '';
    const teacher_id = document.getElementById('edit_teacher_id')?.value || '';
    const do_id = document.getElementById('edit_do_id')?.value || '';

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'updateUser');
    formData.append('user_id', user_id);
    formData.append('email', email);
    formData.append('full_name', full_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('teacher_subrole', teacher_subrole);
    formData.append('teacher_id', teacher_id);
    formData.append('do_id', do_id);

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
            <form id="reset_form" onsubmit="submitResetPassword(event)" class="p-6">
                <input type="hidden" id="reset_user_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <p id="reset_username" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"></p>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Password will be reset to the default password: <strong>password</strong>
                    </p>
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
    
    const user_id = document.getElementById('reset_user_id').value;

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'resetPassword');
    formData.append('user_id', user_id);
    formData.append('new_password', 'password');

    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();
        
        if (data.success) {
            showMessage('Password reset to default successfully', 'success');
            closeResetPasswordModal();
            // Reload users to update the badges
            setTimeout(() => loadUsers(), 500);
        } else {
            showMessage('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error resetting password', 'error');
    }
}

// Helper to generate next role-specific ID
async function generateNextRoleID(role) {
    return null;
}

function syncTeacherSubroleField(formType, roleValue, subroleValue = '') {
    const subroleContainer = document.getElementById(`${formType}_teacher_subrole_container`);
    const subroleSelect = document.getElementById(`${formType}_teacher_subrole`);
    const teacherIdContainer = document.getElementById(`${formType}_teacher_id_container`);
    const teacherIdInput = document.getElementById(`${formType}_teacher_id`);
    const doIdContainer = document.getElementById(`${formType}_do_id_container`);
    const doIdInput = document.getElementById(`${formType}_do_id`);

    const isTeacher = roleValue === 'teacher';
    const isDo = roleValue === 'discipline_office';

    if (subroleContainer && subroleSelect) {
        subroleContainer.classList.toggle('hidden', !isTeacher);
        subroleSelect.required = isTeacher;
        subroleSelect.value = isTeacher ? (subroleValue || '') : '';
    }

    if (teacherIdContainer && teacherIdInput) {
        teacherIdContainer.classList.toggle('hidden', !isTeacher);
        teacherIdInput.required = isTeacher;
        if (!isTeacher) {
            teacherIdInput.value = '';
        }
    }

    if (doIdContainer && doIdInput) {
        doIdContainer.classList.toggle('hidden', !isDo);
        doIdInput.required = isDo;
        if (!isDo) {
            doIdInput.value = '';
        }
    }
}

// Handle role change in add user form
async function handleRoleChange(formType = 'add') {
    const roleSelect = document.getElementById(`${formType}_role`);
    const emailInput = formType === 'add' ? document.getElementById('add_email') : null;

    if (emailInput) {
        emailInput.readOnly = false;
        emailInput.classList.remove('bg-gray-50', 'dark:bg-slate-800');
    }

    if (!roleSelect) {
        return;
    }

    syncTeacherSubroleField(formType, roleSelect.value, document.getElementById(`${formType}_teacher_subrole`)?.value || '');
}

// Kept for compatibility with the existing oninput hook if any pages still call it.
function updateStudentEmail() {
    return;
}

// ====== Initialize Modals ======
document.addEventListener('DOMContentLoaded', function() {
    createAddModal();
    createEditModal();
    createResetPasswordModal();
});
