// Get all students for dropdown
function loadStudents() {
    return fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1&action=getStudents'
    })
    .then(response => response.json())
    .then(data => data.students || [])
    .catch(error => {
        console.error('Error loading students:', error);
        return [];
    });
}

// ====== Modal Management for Student Cases ======

function getStatusColor(status) {
    switch (status) {
        case 'Pending': return 'yellow';
        case 'Under Review': return 'blue';
        case 'Resolved': return 'green';
        case 'Escalated': return 'red';
        default: return 'gray';
    }
}

// View Case Modal
function viewCase(caseId) {
    const caseData = allCases.find(c => c.id === caseId);
    if (!caseData) return;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Case Details: ${caseData.id}</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student</p>
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.student}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status</p>
                        <span class="inline-block px-2.5 py-1 text-xs font-medium rounded ${statusColors[caseData.statusColor]}">${caseData.status}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Assigned To</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.assignedTo}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.type}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Date Reported</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.date}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Description</p>
                    <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 p-2.5 rounded">
                        ${caseData.description}
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</p>
                    <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 p-2.5 rounded">
                        ${caseData.notes || 'No notes available.'}
                    </div>
                </div>
            </div>

            <div class="flex justify-between gap-2 mt-5">
                <button onclick="archiveCaseConfirm('${caseData.id}')" class="px-4 py-2 text-sm border border-red-600 text-red-600 rounded hover:bg-red-50 dark:hover:bg-red-900/20 font-medium">
                    Archive Case
                </button>
                <div class="flex gap-2">
                    <button onclick="editCase('${caseData.id}'); closeModal(this);" class="px-4 py-2 text-sm border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 font-medium">
                        Edit Case
                    </button>
                    <button onclick="closeModal(this)" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Edit Case Modal
function editCase(caseId) {
    const caseData = allCases.find(c => c.id === caseId);
    if (!caseData) return;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit Case: ${caseData.id}</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editCaseForm" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student</label>
                        <input type="text" value="${caseData.student}" readonly
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case ID</label>
                        <input type="text" value="${caseData.id}" readonly
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type</label>
                        <select id="editType" class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <option ${caseData.type === 'Tardiness' ? 'selected' : ''}>Tardiness</option>
                            <option ${caseData.type === 'Dress Code' ? 'selected' : ''}>Dress Code</option>
                            <option ${caseData.type === 'Classroom Disruption' ? 'selected' : ''}>Classroom Disruption</option>
                            <option ${caseData.type === 'Academic Dishonesty' ? 'selected' : ''}>Academic Dishonesty</option>
                            <option ${caseData.type === 'Attendance' ? 'selected' : ''}>Attendance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Date Reported</label>
                        <input type="text" value="${caseData.date}" readonly
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status</label>
                    <select id="editStatus" class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option ${caseData.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option ${caseData.status === 'Under Review' ? 'selected' : ''}>Under Review</option>
                        <option ${caseData.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                        <option ${caseData.status === 'Escalated' ? 'selected' : ''}>Escalated</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Description</label>
                    <textarea id="editDescription" rows="3" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none">${caseData.description}</textarea>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                    <textarea id="editNotes" rows="2" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none">${caseData.notes}</textarea>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3">
                    <button type="button" onclick="closeModal(this)" class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('editCaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'updateCase');
        formData.append('caseId', caseData.id);
        formData.append('type', document.getElementById('editType').value);
        formData.append('severity', caseData.severity || 'Minor');
        formData.append('status', document.getElementById('editStatus').value);
        formData.append('description', document.getElementById('editDescription').value);
        formData.append('notes', document.getElementById('editNotes').value);

        try {
            const response = await fetch('/PrototypeDO/modules/do/cases.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeModal(e.target);
                loadCasesFromDB(); // Reload from database
                alert('Case updated successfully!');
            } else {
                alert('Error: ' + (data.error || 'Failed to update case'));
            }
        } catch (error) {
            console.error('Error updating case:', error);
            alert('Error updating case. Please try again.');
        }
    });
}

// Add Case Modal
// Add Case Modal - With manual student input
async function addCase() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Add New Case</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="addCaseForm" class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student Number <span class="text-red-500">*</span></label>
                    <input type="text" id="newStudentNumber" required 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="e.g., 02000372341">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student Name <span class="text-red-500">*</span></label>
                    <input type="text" id="newStudentName" required 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="e.g., Juan Dela Cruz">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type <span class="text-red-500">*</span></label>
                    <select id="newType" required class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">Select type...</option>
                        <option>Tardiness</option>
                        <option>Dress Code</option>
                        <option>Classroom Disruption</option>
                        <option>Academic Dishonesty</option>
                        <option>Attendance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Severity</label>
                    <select id="newSeverity" class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option>Minor</option>
                        <option>Major</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status</label>
                    <select id="newStatus" class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option>Pending</option>
                        <option>Under Review</option>
                        <option>Resolved</option>
                        <option>Escalated</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Description <span class="text-red-500">*</span></label>
                    <textarea id="newDescription" rows="3" required
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Describe the incident..."></textarea>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                    <textarea id="newNotes" rows="2" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Additional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3">
                    <button type="button" onclick="closeModal(this)" class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                        Add Case
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('addCaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'createCase');
        formData.append('studentNumber', document.getElementById('newStudentNumber').value);
        formData.append('studentName', document.getElementById('newStudentName').value);
        formData.append('type', document.getElementById('newType').value);
        formData.append('severity', document.getElementById('newSeverity').value);
        formData.append('status', document.getElementById('newStatus').value);
        formData.append('description', document.getElementById('newDescription').value);
        formData.append('notes', document.getElementById('newNotes').value);

        try {
            const response = await fetch('/PrototypeDO/modules/do/cases.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeModal(e.target);
                loadCasesFromDB(); // Reload table from database
                alert('Case created successfully!');
            } else {
                alert('Error: ' + (data.error || 'Failed to create case'));
            }
        } catch (error) {
            console.error('Error creating case:', error);
            alert('Error creating case. Please try again.');
        }
    });
}

// Archive case with confirmation
function archiveCaseConfirm(caseId) {
    if (confirm('Are you sure you want to archive this case? It will be moved to the Archived tab.')) {
        archiveCaseAction(caseId);
    }
}

async function archiveCaseAction(caseId) {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'archiveCase');
    formData.append('caseId', caseId);

    try {
        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Close any open modals
            const modal = document.querySelector('.fixed.inset-0');
            if (modal) modal.remove();
            
            // Reload cases
            loadCasesFromDB();
            alert('Case archived successfully!');
        } else {
            alert('Error: ' + (data.error || 'Failed to archive case'));
        }
    } catch (error) {
        console.error('Error archiving case:', error);
        alert('Error archiving case. Please try again.');
    }
}

// Unarchive case
async function unarchiveCase(caseId) {
    if (!confirm('Are you sure you want to restore this case to active cases?')) return;
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'unarchiveCase');
    formData.append('caseId', caseId);

    try {
        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCasesFromDB();
            alert('Case restored successfully!');
        } else {
            alert('Error: ' + (data.error || 'Failed to restore case'));
        }
    } catch (error) {
        console.error('Error restoring case:', error);
        alert('Error restoring case. Please try again.');
    }
}

// Close modal
function closeModal(element) {
    const modal = element.closest('.fixed');
    if (modal) modal.remove();
}