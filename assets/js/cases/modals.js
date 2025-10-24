// ====== Modal Management for Student Cases ======

// View Case Modal
function viewCase(caseId) {
    const caseData = allCases.find(c => c.id === caseId);
    if (!caseData) return;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 relative animate-fadeIn">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Case Details</h2>
            <div class="space-y-2 text-gray-700 dark:text-gray-300">
                <p><strong>ID:</strong> ${caseData.id}</p>
                <p><strong>Student:</strong> ${caseData.student}</p>
                <p><strong>Type:</strong> ${caseData.type}</p>
                <p><strong>Date:</strong> ${caseData.date}</p>
                <p><strong>Status:</strong> ${caseData.status}</p>
                <p><strong>Assigned To:</strong> ${caseData.assignedTo}</p>
                <p><strong>Description:</strong> ${caseData.description || 'N/A'}</p>
                <p><strong>Notes:</strong> ${caseData.notes || 'N/A'}</p>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeModal(this)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Close
                </button>
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
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 relative animate-fadeIn">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Edit Case</h2>
            <form id="editCaseForm" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium">Student</label>
                    <input type="text" id="editStudent" value="${caseData.student}" 
                        class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Type</label>
                    <input type="text" id="editType" value="${caseData.type}" 
                        class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <select id="editStatus" 
                        class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600">
                        <option ${caseData.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option ${caseData.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                        <option ${caseData.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                        <option ${caseData.status === 'Dismissed' ? 'selected' : ''}>Dismissed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Assigned To</label>
                    <input type="text" id="editAssigned" value="${caseData.assignedTo}" 
                        class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600">
                </div>

                <div>
                    <label class="block text-sm font-medium">Description</label>
                    <textarea id="editDescription" 
                        class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600">${caseData.description}</textarea>
                </div>

                <div class="flex justify-end mt-4 space-x-2">
                    <button type="button" onclick="closeModal(this)" class="px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('editCaseForm').addEventListener('submit', (e) => {
        e.preventDefault();
        // Update data (for now in memory; later you can connect to PHP/MySQL)
        caseData.student = document.getElementById('editStudent').value;
        caseData.type = document.getElementById('editType').value;
        caseData.status = document.getElementById('editStatus').value;
        caseData.assignedTo = document.getElementById('editAssigned').value;
        caseData.description = document.getElementById('editDescription').value;

        renderCases(); // re-render table
        closeModal(e.target);
    });
}


// Add Case Modal
function addCase() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 relative animate-fadeIn">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Add New Case</h2>
            <form id="addCaseForm" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium">Student</label>
                    <input type="text" id="newStudent" class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Type</label>
                    <input type="text" id="newType" class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <select id="newStatus" class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600">
                        <option>Pending</option>
                        <option>In Progress</option>
                        <option>Resolved</option>
                        <option>Dismissed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Assigned To</label>
                    <input type="text" id="newAssigned" class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600">
                </div>

                <div>
                    <label class="block text-sm font-medium">Description</label>
                    <textarea id="newDescription" class="w-full p-2 border rounded-md dark:bg-slate-700 dark:border-slate-600"></textarea>
                </div>

                <div class="flex justify-end mt-4 space-x-2">
                    <button type="button" onclick="closeModal(this)" class="px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Add Case
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('addCaseForm').addEventListener('submit', (e) => {
        e.preventDefault();

        const newCase = {
            id: 'C-' + (Math.floor(Math.random() * 9000) + 1000),
            student: document.getElementById('newStudent').value,
            type: document.getElementById('newType').value,
            date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            status: document.getElementById('newStatus').value,
            assignedTo: document.getElementById('newAssigned').value,
            statusColor: getStatusColor(document.getElementById('newStatus').value),
            description: document.getElementById('newDescription').value,
            notes: ''
        };

        allCases.unshift(newCase); // add to top
        filteredCases = [...allCases];
        currentPage = 1;
        renderCases();
        closeModal(e.target);
    });
}


// Close any modal
function closeModal(element) {
    const modal = element.closest('.fixed');
    if (modal) modal.remove();
}


// Helper: Get status color
function getStatusColor(status) {
    switch (status) {
        case 'Pending': return 'yellow';
        case 'In Progress': return 'blue';
        case 'Resolved': return 'green';
        case 'Dismissed': return 'red';
        default: return 'gray';
    }
}
