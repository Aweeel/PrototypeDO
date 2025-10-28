// ====== Filter Functions ======

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

function toggleFilters() {
    const filterSection = document.getElementById('filterSection');
    if (filterSection.classList.contains('hidden')) {
        filterSection.classList.remove('hidden');
    } else {
        filterSection.classList.add('hidden');
    }
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