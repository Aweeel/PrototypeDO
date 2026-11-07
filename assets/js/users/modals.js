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
function formatContactSuffix(input) {
    input.value = input.value
        .replace(/\D/g, '')
        .slice(0, 9)
        .replace(/^(\d{0,2})(\d{0,3})(\d{0,4}).*$/, function(_, first, second, third) {
            return first + (second ? ' ' + second : '') + (third ? ' ' + third : '');
        });
}

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
    modal.className = 'hidden fixed inset-0 overflow-y-auto overscroll-contain bg-black bg-opacity-50 z-50 flex items-start justify-center p-2 sm:items-center sm:p-4';
    modal.innerHTML = `
        <div class="my-0 flex max-h-[calc(100dvh-1rem)] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-[#111827] sm:my-4 sm:max-h-[calc(100dvh-2rem)]">
            <div class="flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700 sm:p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Add New User</h2>
                <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add_form" onsubmit="submitAddUser(event)" class="min-h-0 overflow-y-auto p-4 space-y-4 sm:p-6">
                <div>
                    <label for="add_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select id="add_role" name="role" required onchange="handleRoleChange('add')"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Role --</option>
                        <option value="system_manager">System Manager</option>
                        <option value="discipline_office">Discipline Office</option>
                        <option value="teacher">Teacher</option>
                        <option value="security">Security</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div id="add_teacher_subrole_container" class="hidden">
                    <label for="add_teacher_subrole" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher Subrole</label>
                    <select id="add_teacher_subrole" name="teacher_subrole"
                        onchange="syncTeacherSubroleField('add', document.getElementById('add_role').value, this.value)"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">                        <option value="">-- No Subrole --</option>
                        <option value="department_head">Department Head</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Department Heads can be invited to hearings by the DO.</p>
                </div>
                <div id="add_program_container" class="hidden">
                    <label for="add_program" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                    <select id="add_program" name="program"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                      <option value="">Select Program...</option>
                        <option value="BSCS">BSCS</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCpE">BSCpE</option>
                        <option value="BSMA">BSMA</option>
                        <option value="BSA">BSA</option>
                        <option value="BSHM">BSHM</option>
                        <option value="BMMA">BMMA</option>
                        <option value="BACOMM">BACOMM</option>
                        <option value="BAPsych">BAPsych</option>
                        <option value="BSTM">BSTM</option>
                        <option value="BSCRIM">BSCRIM</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required for department head teachers.</p>
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
                <div id="add_student_id_container" class="hidden">
                    <label for="add_student_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student ID *</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 px-3 text-gray-700 dark:border-slate-600 dark:bg-slate-600 dark:text-gray-200">02000</span>
                        <input type="text" id="add_student_id" name="student_id" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="000001"
                            class="min-w-0 flex-1 rounded-r-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter the 6-digit number after the fixed 02000 prefix.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <input type="text" id="add_first_name" placeholder="First Name *" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    <input type="text" id="add_middle_name" placeholder="Middle Name" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    <input type="text" id="add_last_name" placeholder="Last Name *" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                </div>
                <div id="add_student_details_container" class="hidden space-y-4">
                    <div>
                        <label for="add_student_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student Type *</label>
                        <select id="add_student_type" name="student_type" onchange="loadStudentOptions('add')" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <option value="">-- Select Student Type --</option><option value="SHS">SHS</option><option value="College">College</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_grade_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grade/Year Level *</label>
                        <select id="add_grade_year" name="grade_year" onchange="loadStudentOptions('add')"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Select Grade/Year --</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_track_course" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Track/Course</label>
                        <select id="add_track_course" name="track_course" onchange="loadStudentOptions('add')"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Select Track/Course --</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_section" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                        <select id="add_section" name="section"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Select Section --</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_guardian_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Name</label>
                        <input type="text" id="add_guardian_name" name="guardian_name"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label for="add_guardian_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Contact</label>
                        <input type="tel" id="add_guardian_contact" name="guardian_contact"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
                <div id="email_container">
                    <label for="add_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                    <div class="flex">
                        <input type="text" id="add_email" name="email" required pattern="[A-Za-z0-9._%+-]+" placeholder="name"
                            class="min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100">
                        <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 text-gray-700 dark:border-slate-600 dark:bg-slate-600 dark:text-gray-200">@sti.edu</span>
                    </div>

                </div>
                <div>
                    <label for="add_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 px-3 text-gray-700 dark:border-slate-600 dark:bg-slate-600 dark:text-gray-200">09</span>
                        <input type="tel" id="add_contact_number" name="contact_number" inputmode="numeric" maxlength="11" pattern="[0-9]{2} [0-9]{3} [0-9]{4}" placeholder="12 345 6789"
                            oninput="formatContactSuffix(this)"
                            class="min-w-0 flex-1 rounded-r-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100">
                    </div>

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
    
    const emailLocalPart = document.getElementById('add_email').value.trim().replace(/@sti\.edu(?:\.ph)?$/i, '');
    const email = `${emailLocalPart}@sti.edu`;
    const first_name = document.getElementById('add_first_name').value;
    const middle_name = document.getElementById('add_middle_name').value;
    const last_name = document.getElementById('add_last_name').value;
    const role = document.getElementById('add_role').value;
    const contactSuffix = document.getElementById('add_contact_number').value.replace(/\D/g, '');
    const contact_number = contactSuffix.length === 9 ? `09${contactSuffix}` : '';
    const teacher_subrole = document.getElementById('add_teacher_subrole')?.value || '';
    const program = document.getElementById('add_program')?.value || '';
    const teacher_id = document.getElementById('add_teacher_id')?.value || '';
    const do_id = document.getElementById('add_do_id')?.value || '';
    const studentIdSuffix = document.getElementById('add_student_id')?.value.replace(/\D/g, '') || '';
    const student_id = studentIdSuffix ? `02000${studentIdSuffix}` : '';
    const grade_year = document.getElementById('add_grade_year')?.value || '';
    const track_course = document.getElementById('add_track_course')?.value || '';
    const section = document.getElementById('add_section')?.value || '';
    const student_type = document.getElementById('add_student_type')?.value || '';
    const guardian_name = document.getElementById('add_guardian_name')?.value || '';
    const guardian_contact = document.getElementById('add_guardian_contact')?.value || '';

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'createUser');
    // username and password are handled server‑side
    formData.append('email', email);
    formData.append('first_name', first_name);
    formData.append('middle_name', middle_name);
    formData.append('last_name', last_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('teacher_subrole', teacher_subrole);
    formData.append('program', program);
    formData.append('teacher_id', teacher_id);
    formData.append('do_id', do_id);
    formData.append('student_id', student_id);
    formData.append('grade_year', grade_year);
    formData.append('track_course', track_course);
    formData.append('section', section);
    formData.append('student_type', student_type);
    formData.append('guardian_name', guardian_name);
    formData.append('guardian_contact', guardian_contact);

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
            showMessage('Unable to create user: ' + (data.error || 'The server did not provide a reason. Check the user details and try again.'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('The user could not be created because the server did not return a valid response. Check your connection and try again.', 'error');
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
    modal.className = 'hidden fixed inset-0 overflow-y-auto overscroll-contain bg-black bg-opacity-50 z-50 flex items-start justify-center p-2 sm:items-center sm:p-4';
    modal.innerHTML = `
        <div class="my-0 flex max-h-[calc(100dvh-1rem)] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white shadow-xl border border-gray-200 dark:border-slate-700 dark:bg-[#111827] sm:my-4 sm:max-h-[calc(100dvh-2rem)]">
            <div class="flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700 sm:p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Edit User</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit_form" onsubmit="submitEditUser(event)" class="min-h-0 overflow-y-auto p-4 sm:p-6">
                <input type="hidden" id="edit_user_id">

                <div class="space-y-4">
                    <div>
                        <label for="edit_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                        <div class="flex">
                            <input type="text" id="edit_email" name="email" required pattern="[A-Za-z0-9._%+-]+" placeholder="name"
                                class="min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100">
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 text-gray-700 dark:border-slate-600 dark:bg-slate-600 dark:text-gray-200">@sti.edu</span>
                        </div>
                    </div>
                    <div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <input type="text" id="edit_first_name" placeholder="First Name *" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <input type="text" id="edit_middle_name" placeholder="Middle Name" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <input type="text" id="edit_last_name" placeholder="Last Name *" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    <div>
                        <label for="edit_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                        <select id="edit_role" name="role" required 
                            onchange="handleRoleChange('edit')"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                            <option value="system_manager">System Manager</option>
                            <option value="discipline_office">Discipline Office</option>
                            <option value="teacher">Teacher</option>
                            <option value="security">Security</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div id="edit_teacher_subrole_container" class="hidden">
                        <label for="edit_teacher_subrole" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher Subrole</label>
                        <select id="edit_teacher_subrole" name="teacher_subrole"
                            onchange="syncTeacherSubroleField('edit', document.getElementById('edit_role').value, this.value)"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">                            <option value="">-- No Subrole --</option>
                            <option value="department_head">Department Head</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Department Heads can be invited to hearings by the DO.</p>
                    </div>
                    <div id="edit_program_container" class="hidden">
                        <label for="edit_program" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                        <select id="edit_program" name="program"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Program --</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Tourism Management">Tourism Management</option>
                            <option value="Criminal Justice Education">Criminal Justice Education</option>
                            <option value="Hospitality Management">Hospitality Management</option>
                            <option value="Business & Management">Business & Management</option>
                            <option value="Arts & Sciences">Arts & Sciences</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required for department head teachers.</p>
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
                    <div id="edit_student_id_container" class="hidden">
                        <label for="edit_student_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student ID *</label>
                        <input type="text" id="edit_student_id" name="student_id" placeholder="02000000001"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div id="edit_student_details_container" class="hidden space-y-4">
                        <div>
                            <label for="edit_student_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student Type *</label>
                            <select id="edit_student_type" name="student_type" onchange="loadStudentOptions('edit')" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option value="">-- Select Type --</option><option value="SHS">SHS</option><option value="College">College</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_grade_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grade/Year Level *</label>
                            <select id="edit_grade_year" name="grade_year" onchange="loadStudentOptions('edit')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">-- Select Grade/Year --</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_track_course" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Track/Course</label>
                            <select id="edit_track_course" name="track_course" onchange="loadStudentOptions('edit')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">-- Select Track/Course --</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_section" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                            <select id="edit_section" name="section"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">-- Select Section --</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_guardian_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Name</label>
                            <input type="text" id="edit_guardian_name" name="guardian_name"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label for="edit_guardian_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guardian Contact</label>
                            <input type="tel" id="edit_guardian_contact" name="guardian_contact"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label for="edit_contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Number</label>
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 px-3 text-gray-700 dark:border-slate-600 dark:bg-slate-600 dark:text-gray-200">09</span>
                            <input type="tel" id="edit_contact_number" name="contact_number" inputmode="numeric" maxlength="11" pattern="[0-9]{2} [0-9]{3} [0-9]{4}" placeholder="12 345 6789"
                                oninput="formatContactSuffix(this)"
                                class="min-w-0 flex-1 rounded-r-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100">
                        </div>
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
    const editEmailLocalPart = document.getElementById('edit_email').value.trim().replace(/@sti\.edu(?:\.ph)?$/i, '');
    const email = `${editEmailLocalPart}@sti.edu`;
    const first_name = document.getElementById('edit_first_name').value;
    const middle_name = document.getElementById('edit_middle_name').value;
    const last_name = document.getElementById('edit_last_name').value;
    const role = document.getElementById('edit_role').value;
    const editContactSuffix = document.getElementById('edit_contact_number').value.replace(/\D/g, '');
    const contact_number = editContactSuffix.length === 9 ? `09${editContactSuffix}` : '';
    const teacher_subrole = document.getElementById('edit_teacher_subrole')?.value || '';
    const program = document.getElementById('edit_program')?.value || '';
    const teacher_id = document.getElementById('edit_teacher_id')?.value || '';
    const do_id = document.getElementById('edit_do_id')?.value || '';
    const student_id = document.getElementById('edit_student_id')?.value || '';
    const grade_year = document.getElementById('edit_grade_year')?.value || '';
    const track_course = document.getElementById('edit_track_course')?.value || '';
    const section = document.getElementById('edit_section')?.value || '';
    const student_type = document.getElementById('edit_student_type')?.value || '';
    const guardian_name = document.getElementById('edit_guardian_name')?.value || '';
    const guardian_contact = document.getElementById('edit_guardian_contact')?.value || '';

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'updateUser');
    formData.append('user_id', user_id);
    formData.append('email', email);
    formData.append('first_name', first_name);
    formData.append('middle_name', middle_name);
    formData.append('last_name', last_name);
    formData.append('role', role);
    formData.append('contact_number', contact_number);
    formData.append('teacher_subrole', teacher_subrole);
    formData.append('program', program);
    formData.append('teacher_id', teacher_id);
    formData.append('do_id', do_id);
    formData.append('student_id', student_id);
    formData.append('grade_year', grade_year);
    formData.append('track_course', track_course);
    formData.append('section', section);
    formData.append('student_type', student_type);
    formData.append('guardian_name', guardian_name);
    formData.append('guardian_contact', guardian_contact);

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
            showMessage('Unable to update user: ' + (data.error || 'The server did not provide a reason. Check the user details and try again.'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('The user could not be updated because the server did not return a valid response. Check your connection and try again.', 'error');
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
            showMessage('Unable to reset password: ' + (data.error || 'The server did not provide a reason. Check the account and try again.'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('The password could not be reset because the server did not return a valid response. Check your connection and try again.', 'error');
    }
}

// Helper to generate next role-specific ID
async function generateNextRoleID(role) {
    return null;
}

function syncTeacherSubroleField(formType, roleValue, subroleValue = '') {
    const subroleContainer = document.getElementById(`${formType}_teacher_subrole_container`);
    const subroleSelect = document.getElementById(`${formType}_teacher_subrole`);
    const programContainer = document.getElementById(`${formType}_program_container`);
    const programSelect = document.getElementById(`${formType}_program`);
    const teacherIdContainer = document.getElementById(`${formType}_teacher_id_container`);
    const teacherIdInput = document.getElementById(`${formType}_teacher_id`);
    const doIdContainer = document.getElementById(`${formType}_do_id_container`);
    const doIdInput = document.getElementById(`${formType}_do_id`);
    const studentIdContainer = document.getElementById(`${formType}_student_id_container`);
    const studentIdInput = document.getElementById(`${formType}_student_id`);
    const studentDetailsContainer = document.getElementById(`${formType}_student_details_container`);
    const gradeYearInput = document.getElementById(`${formType}_grade_year`);
    const studentTypeInput = document.getElementById(`${formType}_student_type`);

    const isTeacher = roleValue === 'teacher';
    const isDo = roleValue === 'discipline_office';
    const isStudent = roleValue === 'student';
    const isDepartmentHead = isTeacher && subroleValue === 'department_head';

    if (subroleContainer && subroleSelect) {
        subroleContainer.classList.toggle('hidden', !isTeacher);
        subroleSelect.value = isTeacher ? (subroleValue || '') : '';
    }

    if (programContainer && programSelect) {
        programContainer.classList.toggle('hidden', !isDepartmentHead);
        programSelect.required = isDepartmentHead;
        if (!isDepartmentHead) {
            programSelect.value = '';
        }
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

    if (studentIdContainer && studentIdInput) {
        studentIdContainer.classList.toggle('hidden', !isStudent);
        studentIdInput.required = isStudent;
        if (!isStudent) {
            studentIdInput.value = '';
        }
    }

    if (studentDetailsContainer) {
        studentDetailsContainer.classList.toggle('hidden', !isStudent);
    }
    if (gradeYearInput) {
        gradeYearInput.required = isStudent;
        if (!isStudent) {
            gradeYearInput.value = '';
        }
    }
    if (studentTypeInput) {
        studentTypeInput.required = isStudent;
        if (!isStudent) {
            studentTypeInput.value = '';
        }
    }
}

async function loadStudentOptions(formType) {
    const studentType = document.getElementById(`${formType}_student_type`).value;
    const gradeYear = document.getElementById(`${formType}_grade_year`).value;
    const trackCourse = document.getElementById(`${formType}_track_course`).value;
    if (!studentType) {
        ['grade_year', 'track_course', 'section'].forEach(field => {
            document.getElementById(`${formType}_${field}`).innerHTML = `<option value="">-- Select ${field === 'grade_year' ? 'Grade/Year' : field === 'track_course' ? 'Track/Course' : 'Section'} --</option>`;
        });
        return;
    }
    const response = await fetch(window.location.pathname, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ajax: '1', action: 'getStudentOptions', student_type: studentType, grade_year: gradeYear, track_course: trackCourse})
    });
    const data = await response.json();
    if (!data.success) return;
    ['grade_year', 'track_course', 'section'].forEach(field => {
        const select = document.getElementById(`${formType}_${field}`);
        const current = select.value;
        select.innerHTML = `<option value="">-- Select ${field === 'grade_year' ? 'Grade/Year' : field === 'track_course' ? 'Track/Course' : 'Section'} --</option>`;
        (data.options[field] || []).forEach(value => select.add(new Option(value, value)));
        if ((data.options[field] || []).includes(current)) {
            select.value = current;
        }
    });
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

    syncTeacherSubroleField(
        formType,
        roleSelect.value,
        document.getElementById(`${formType}_teacher_subrole`)?.value || ''
    );
    if (roleSelect.value === 'student') {
        loadStudentOptions(formType);
    }
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
