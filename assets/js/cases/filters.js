// ====== Filter Functions ======

// Global filter state
let activeFilters = {
    offenseType: '',
    caseType: '',
    status: '',
    dateFrom: '',
    dateTo: '',
    caseId: ''
};

function filterCases() {
    // Reload from database with filters
    loadCasesFromDB();
}

function sortCases() {
    const sortValue = document.getElementById('sortFilter').value;
    
    if (sortValue === 'newest') {
        filteredCases.sort((a, b) => b.id.localeCompare(a.id));
    } else if (sortValue === 'oldest') {
        filteredCases.sort((a, b) => a.id.localeCompare(b.id));
    } else if (sortValue === 'status') {
        filteredCases.sort((a, b) => a.status.localeCompare(b.status));
    }
    
    renderCases();
}

function switchTab(tabName) {
    currentTab = tabName;
    
    // Update button styles
    const currentBtn = document.getElementById('currentTab');
    const archivedBtn = document.getElementById('archivedTab');
    
    if (tabName === 'current') {
        currentBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
        archivedBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    } else {
        archivedBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
        currentBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    }
    
    // Load cases based on tab
    loadCasesFromDB();
}

// ====== NEW: Minor/Major Filter Buttons ======

function filterByOffenseType(type) {
    activeFilters.offenseType = type;
    
    // Update button styles
    const allBtn = document.getElementById('allOffensesBtn');
    const minorBtn = document.getElementById('minorBtn');
    const majorBtn = document.getElementById('majorBtn');
    
    // Reset all buttons
    allBtn.className = 'px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors';
    minorBtn.className = 'px-4 py-2 bg-white dark:bg-slate-800 border border-yellow-500 text-yellow-700 dark:text-yellow-300 rounded-lg font-medium text-sm hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors';
    majorBtn.className = 'px-4 py-2 bg-white dark:bg-slate-800 border border-red-500 text-red-700 dark:text-red-300 rounded-lg font-medium text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors';
    
    // Highlight selected button
    if (type === '') {
        allBtn.className = 'px-4 py-2 bg-gray-600 text-white rounded-lg font-medium text-sm';
    } else if (type === 'Minor') {
        minorBtn.className = 'px-4 py-2 bg-yellow-600 text-white rounded-lg font-medium text-sm';
    } else if (type === 'Major') {
        majorBtn.className = 'px-4 py-2 bg-red-600 text-white rounded-lg font-medium text-sm';
    }
    
    // Apply client-side filter
    applyClientSideFilters();
}

// ====== NEW: Advanced Filters Modal ======

async function openAdvancedFilters() {
    // Load all offense types for dropdown
    const allOffenses = await loadOffenseTypes('');
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Advanced Filters</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="advancedFilterForm" class="space-y-4">
                <!-- Case ID Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Case ID
                    </label>
                    <input type="text" id="filterCaseId" value="${activeFilters.caseId}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="e.g., C-1092">
                </div>

                <!-- Case Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Case Type
                    </label>
                    <select id="filterCaseType" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">All Types</option>
                        ${allOffenses.map(o => `
                            <option value="${o.offense_name}" ${activeFilters.caseType === o.offense_name ? 'selected' : ''}>
                                ${o.offense_name}
                            </option>
                        `).join('')}
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Status
                    </label>
                    <select id="filterStatus"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">All Status</option>
                        <option value="Pending" ${activeFilters.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="Under Review" ${activeFilters.status === 'Under Review' ? 'selected' : ''}>Under Review</option>
                        <option value="Resolved" ${activeFilters.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                        <option value="Escalated" ${activeFilters.status === 'Escalated' ? 'selected' : ''}>Escalated</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            From Date
                        </label>
                        <input type="date" id="filterDateFrom" value="${activeFilters.dateFrom}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            To Date
                        </label>
                        <input type="date" id="filterDateTo" value="${activeFilters.dateTo}"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <!-- Active Filters Summary -->
                <div id="activeFiltersSummary" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg" style="display: none;">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">Active Filters:</p>
                    <div id="filterTags" class="flex flex-wrap gap-2">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="clearAllFilters()"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">
                        Clear All
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeModal(this)"
                            class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    // Update filter summary
    updateFilterSummary();

    // Handle form submission
    document.getElementById('advancedFilterForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Update active filters
        activeFilters.caseId = document.getElementById('filterCaseId').value;
        activeFilters.caseType = document.getElementById('filterCaseType').value;
        activeFilters.status = document.getElementById('filterStatus').value;
        activeFilters.dateFrom = document.getElementById('filterDateFrom').value;
        activeFilters.dateTo = document.getElementById('filterDateTo').value;
        
        // Close modal
        closeModal(e.target);
        
        // Apply filters
        applyClientSideFilters();
    });
}

function updateFilterSummary() {
    const summaryDiv = document.getElementById('activeFiltersSummary');
    const tagsDiv = document.getElementById('filterTags');
    
    let hasFilters = false;
    let tags = '';
    
    if (activeFilters.offenseType) {
        hasFilters = true;
        tags += `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
            Offense: ${activeFilters.offenseType}
        </span>`;
    }
    
    if (activeFilters.caseId) {
        hasFilters = true;
        tags += `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
            Case ID: ${activeFilters.caseId}
        </span>`;
    }
    
    if (activeFilters.caseType) {
        hasFilters = true;
        tags += `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
            Type: ${activeFilters.caseType}
        </span>`;
    }
    
    if (activeFilters.status) {
        hasFilters = true;
        tags += `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
            Status: ${activeFilters.status}
        </span>`;
    }
    
    if (activeFilters.dateFrom || activeFilters.dateTo) {
        hasFilters = true;
        const fromDate = activeFilters.dateFrom || '...';
        const toDate = activeFilters.dateTo || '...';
        tags += `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded">
            Date: ${fromDate} to ${toDate}
        </span>`;
    }
    
    if (hasFilters) {
        summaryDiv.style.display = 'block';
        tagsDiv.innerHTML = tags;
    } else {
        summaryDiv.style.display = 'none';
    }
}

function clearAllFilters() {
    activeFilters = {
        offenseType: '',
        caseType: '',
        status: '',
        dateFrom: '',
        dateTo: '',
        caseId: ''
    };
    
    // Reset Minor/Major buttons
    filterByOffenseType('');
    
    // Close modal if open
    const modal = document.querySelector('.fixed.inset-0');
    if (modal) modal.remove();
    
    // Reload all cases
    loadCasesFromDB();
}

function applyClientSideFilters() {
    // Start with all cases
    filteredCases = [...allCases];
    
    // Apply offense type filter
    if (activeFilters.offenseType) {
        filteredCases = filteredCases.filter(c => c.severity === activeFilters.offenseType);
    }
    
    // Apply case type filter
    if (activeFilters.caseType) {
        filteredCases = filteredCases.filter(c => c.type === activeFilters.caseType);
    }
    
    // Apply status filter
    if (activeFilters.status) {
        filteredCases = filteredCases.filter(c => c.status === activeFilters.status);
    }
    
    // Apply case ID filter
    if (activeFilters.caseId) {
        filteredCases = filteredCases.filter(c => c.id.toLowerCase().includes(activeFilters.caseId.toLowerCase()));
    }
    
    // Apply date range filter
    if (activeFilters.dateFrom || activeFilters.dateTo) {
        filteredCases = filteredCases.filter(c => {
            const caseDate = new Date(c.date);
            const fromDate = activeFilters.dateFrom ? new Date(activeFilters.dateFrom) : new Date('1900-01-01');
            const toDate = activeFilters.dateTo ? new Date(activeFilters.dateTo) : new Date('2100-12-31');
            return caseDate >= fromDate && caseDate <= toDate;
        });
    }
    
    // Re-render
    currentPage = 1;
    renderCases();
}