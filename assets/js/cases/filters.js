// ====== Filter Functions ======

function filterCases() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    filteredCases = allCases.filter(c => {
        const matchesSearch = c.student.toLowerCase().includes(searchTerm) ||
                              c.id.toLowerCase().includes(searchTerm) ||
                              c.type.toLowerCase().includes(searchTerm) ||
                              c.assignedTo.toLowerCase().includes(searchTerm);
        const matchesType = !typeFilter || c.type === typeFilter;
        const matchesStatus = !statusFilter || c.status === statusFilter;
        return matchesSearch && matchesType && matchesStatus;
    });

    currentPage = 1;
    renderCases();
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

function switchTab(tab) {
    currentTab = tab;
    
    // Update button styles
    const currentBtn = document.getElementById('currentTab');
    const archivedBtn = document.getElementById('archivedTab');
    
    if (tab === 'current') {
        currentBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
        archivedBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    } else {
        archivedBtn.className = 'px-6 py-2 bg-blue-600 text-white rounded-lg font-medium';
        currentBtn.className = 'px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors';
    }
    
    // For now, just show a message. Later you can filter archived cases
    if (tab === 'archived') {
        document.getElementById('casesTableBody').innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No archived cases found.
                </td>
            </tr>
        `;
        document.getElementById('paginationInfo').textContent = 'Showing 0 cases';
        document.getElementById('paginationButtons').innerHTML = '';
    } else {
        filterCases();
    }
}