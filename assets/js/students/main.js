// ====== Global Variables ======
let allStudents = [];
let filteredStudents = [];
let currentPage = 1;
const studentsPerPage = 6;
let currentStudentId = null;

// ====== Initialization ======
document.addEventListener('DOMContentLoaded', function () {
    console.log('Student History page loaded');
    loadStudents();
    
    // Setup CSV import form handler
    const importForm = document.getElementById('importCsvForm');
    if (importForm) {
        importForm.addEventListener('submit', handleCsvImport);
    }
});

// ====== Load Students from Database ======
async function loadStudents() {
    console.log('Loading students from database...');
    
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const gradeFilter = document.getElementById('gradeFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    
    console.log('Filters:', { searchTerm, gradeFilter, statusFilter });
    
    try {
        const response = await fetch('/PrototypeDO/modules/do/studentHistory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=getStudents&search=${encodeURIComponent(searchTerm)}&grade=${encodeURIComponent(gradeFilter)}&status=${encodeURIComponent(statusFilter)}`
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Raw response:', text);
        
        const data = JSON.parse(text);
        console.log('Parsed data:', data);

        if (data.success) {
            allStudents = data.students;
            filteredStudents = [...allStudents];
            console.log('Loaded students:', allStudents.length);
            renderStudents();
        } else {
            console.error('Failed to load students:', data.error);
            showEmptyState('Error loading students: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error loading students:', error);
        showEmptyState('Error loading students. Please check console for details.');
    }
}

// ====== Render Students Grid ======
function renderStudents() {
    console.log('Rendering students...');
    console.log('Filtered students:', filteredStudents.length);
    
    const grid = document.getElementById('studentsGrid');
    const start = (currentPage - 1) * studentsPerPage;
    const end = start + studentsPerPage;
    const pageStudents = filteredStudents.slice(start, end);

    console.log('Page students:', pageStudents.length);

    if (pageStudents.length === 0) {
        showEmptyState('No students found');
        updatePaginationInfo();
        updatePaginationButtons();
        return;
    }

    grid.innerHTML = pageStudents.map(student => `
        <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg p-5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <!-- Student Info -->
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-gray-200 dark:bg-slate-700 rounded-full flex-shrink-0 flex items-center justify-center">
                        <span class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                            ${student.name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">${student.name}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ID: ${student.studentId}</p>
                    </div>
                </div>

                <!-- Grade & Strand -->
                <div class="flex items-center gap-8 px-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-0.5">Grade</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">${student.grade}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-0.5">Strand/Course</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">${student.strand}</p>
                    </div>
                </div>

                <!-- Incidents -->
                <div class="px-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-0.5">Incidents</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">${student.incidents}</p>
                </div>

                <!-- Last Incident -->
                <div class="px-4 min-w-[120px]">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-0.5">Last Incident</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                        ${student.lastIncident ? formatDate(student.lastIncident) : '-'}
                    </p>
                </div>

                <!-- Status -->
                <div class="px-4 min-w-[140px]">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-0.5">Status</p>
                    ${getStatusBadge(student.status)}
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button onclick="viewHistory('${student.id}')" 
                        class="px-3 py-1.5 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                        View Full History
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    updatePaginationInfo();
    updatePaginationButtons();
}

// Get status badge HTML
function getStatusBadge(status) {
    const statusConfig = {
        'Watch List': 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100',
        'Good Standing': 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
        'Resolved': 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-100'
    };

    const colorClass = statusConfig[status] || statusConfig['Good Standing'];
    
    return `<span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full ${colorClass}">${status}</span>`;
}

// Format date helper
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// ====== Helper Functions ======

// Show empty state
function showEmptyState(message = 'No students found') {
    const grid = document.getElementById('studentsGrid');
    grid.innerHTML = `
        <div class="bg-white dark:bg-[#111827] rounded-lg border border-gray-200 dark:border-slate-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">${message}</h3>
            <p class="text-gray-600 dark:text-gray-400">Try adjusting your search or filter criteria</p>
        </div>
    `;
    
    document.getElementById('paginationInfo').textContent = 'Showing 0 students';
    document.getElementById('paginationButtons').innerHTML = '';
}

// View student history
async function viewHistory(studentId) {
    currentStudentId = studentId;
    const student = allStudents.find(s => s.id === studentId);
    
    if (!student) return;

    const modal = document.getElementById('historyModal');
    const content = document.getElementById('historyContent');
    
    // Show loading state
    content.innerHTML = `
        <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent"></div>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Loading student history...</p>
        </div>
    `;
    
    modal.classList.remove('hidden');

    try {
        // Fetch case history from server
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax=1&action=getStudentHistory&studentId=${studentId}`
        });

        const data = await response.json();

        if (data.success) {
            const cases = data.cases || [];
            
            content.innerHTML = `
                <div class="mb-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gray-200 dark:bg-slate-700 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-semibold text-gray-600 dark:text-gray-400">
                                ${student.name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                            </span>
                        </div>
                        <div>
                            <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">${student.name}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                ID: ${student.studentId} • ${student.grade} • ${student.strand}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Total Incidents</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">${student.incidents}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Major</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">${student.majorOffenses || 0}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Minor</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">${student.minorOffenses || 0}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/50 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Status</p>
                            ${getStatusBadge(student.status)}
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Incident History</h5>
                    <div class="space-y-3">
                        ${cases.length > 0 ? cases.map(c => `
                            <div class="bg-gray-50 dark:bg-slate-800/50 rounded-lg p-4 border border-gray-200 dark:border-slate-700">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-1">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">${c.case_type}</span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full ${
                                                c.severity === 'Major' 
                                                    ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                                            }">${c.severity}</span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full ${getStatusColorClass(c.status)}">${c.status}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Case ID: ${c.case_id} • ${formatDate(c.date_reported)}
                                            ${c.reported_by_name ? ` • Reported by: ${c.reported_by_name}` : ''}
                                        </p>
                                        ${c.description ? `
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">${c.description}</p>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('') : `
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>No incidents recorded</p>
                            </div>
                        `}
                    </div>
                </div>
            `;
        } else {
            throw new Error(data.error || 'Failed to load student history');
        }
    } catch (error) {
        console.error('Error loading student history:', error);
        content.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-900 dark:text-gray-100 font-semibold mb-2">Failed to load history</p>
                <p class="text-gray-500 dark:text-gray-400 text-sm">${error.message}</p>
            </div>
        `;
    }
}

// Get status color class for badges
function getStatusColorClass(status) {
    const statusConfig = {
        'Pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'Under Review': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'Resolved': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'Escalated': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        'Dismissed': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
    };
    
    return statusConfig[status] || statusConfig['Pending'];
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

// ====== CSV Import Functions ======
// Note: openImportModal and closeImportModal are in modals.js

// Handle CSV import form submission
async function handleCsvImport(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showNotification('Please select a CSV file', 'error');
        return;
    }
    
    // Show progress
    document.getElementById('importProgress').classList.remove('hidden');
    document.getElementById('importBtn').disabled = true;
    document.getElementById('importResult').classList.add('hidden');
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('import_csv', '1');
    
    try {
        const response = await fetch('/PrototypeDO/modules/do/studentHistory.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        // Hide progress
        document.getElementById('importProgress').classList.add('hidden');
        document.getElementById('importBtn').disabled = false;
        
        if (data.success) {
            const resultDiv = document.getElementById('importResult');
            resultDiv.classList.remove('hidden');
            
            let errorHtml = '';
            if (data.errors && data.errors.length > 0) {
                errorHtml = `
                    <div class="mt-2">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">Errors:</p>
                        <ul class="text-xs text-red-600 dark:text-red-400 list-disc list-inside space-y-1">
                            ${data.errors.slice(0, 5).map(err => `<li>${err}</li>`).join('')}
                            ${data.errors.length > 5 ? `<li>...and ${data.errors.length - 5} more errors</li>` : ''}
                        </ul>
                    </div>
                `;
            }
            
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="font-semibold text-green-800 dark:text-green-300">Import Completed!</p>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-400">
                        Successfully imported/updated: <strong>${data.imported}</strong> students<br>
                        Skipped: <strong>${data.skipped}</strong> rows
                    </p>
                    ${errorHtml}
                </div>
            `;
            
            showNotification(`Imported ${data.imported} students successfully`, 'success');
            
            // Reload students after 2 seconds
            setTimeout(() => {
                loadStudents();
                closeImportModal();
            }, 2000);
        } else {
            const resultDiv = document.getElementById('importResult');
            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <p class="font-semibold text-red-800 dark:text-red-300">Import Failed</p>
                    </div>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">${data.error}</p>
                </div>
            `;
            showNotification('Import failed: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Import error:', error);
        document.getElementById('importProgress').classList.add('hidden');
        document.getElementById('importBtn').disabled = false;
        
        const resultDiv = document.getElementById('importResult');
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <p class="font-semibold text-red-800 dark:text-red-300">Network Error</p>
                </div>
                <p class="text-sm text-red-700 dark:text-red-400 mt-1">${error.message}</p>
            </div>
        `;
        showNotification('Network error during import', 'error');
    }
}

// ====== Add Note Modal (Removed) ======
// Note: Add Note functionality has been removed

// ====== Notifications ======
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