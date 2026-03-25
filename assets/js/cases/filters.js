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
    const resolvedBtn = document.getElementById('resolvedTab');
    const archivedBtn = document.getElementById('archivedTab');
    
    // Reset all buttons to default styles
    currentBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    resolvedBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    archivedBtn.className = 'p-2 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors ml-2';
    
    // Highlight selected tab
    if (tabName === 'current') {
        currentBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
    } else if (tabName === 'resolved') {
        resolvedBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
    } else if (tabName === 'archived') {
        archivedBtn.className = 'p-2 bg-blue-600 text-white rounded-lg ml-2';
    }
    
    // Hide bulk restore button and clear selections when not in archived tab
    const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
    if (bulkRestoreBtn && tabName !== 'archived') {
        bulkRestoreBtn.classList.add('hidden');
        clearCaseSelections();
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

function applyClientSideFilters() {
    // Start with all cases
    filteredCases = [...allCases];
    
    // First, filter by current tab status
    if (currentTab === 'current') {
        // Exclude resolved cases from current tab
        filteredCases = filteredCases.filter(c => c.status !== 'Resolved');
    } else if (currentTab === 'resolved') {
        // Show only resolved cases for resolved tab
        filteredCases = filteredCases.filter(c => c.status === 'Resolved');
    }
    // For archived tab, all cases should already be archived from the database query
    
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