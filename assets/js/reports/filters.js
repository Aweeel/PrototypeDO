// Filter reports by search
function filterReports() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const formatFilter = document.getElementById('formatFilter').value;

    filteredReports = allReports.filter(report => {
        const matchesSearch = report.name.toLowerCase().includes(searchTerm);
        const matchesFormat = !formatFilter || report.format === formatFilter;
        
        return matchesSearch && matchesFormat;
    });

    currentPage = 1;
    renderReports();
    updatePagination();
}

// Filter by category
function filterByCategory(category) {
    filteredReports = allReports.filter(report => report.category === category);
    currentPage = 1;
    renderReports();
    updatePagination();
}

// Sort reports
function sortReports() {
    const sortBy = document.getElementById('sortFilter').value;

    switch (sortBy) {
        case 'newest':
            filteredReports.sort((a, b) => new Date(b.dateGenerated) - new Date(a.dateGenerated));
            break;
        case 'oldest':
            filteredReports.sort((a, b) => new Date(a.dateGenerated) - new Date(b.dateGenerated));
            break;
        case 'name':
            filteredReports.sort((a, b) => a.name.localeCompare(b.name));
            break;
    }

    renderReports();
}

// Apply date filter
function applyDateFilter() {
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Please select both dates');
        return;
    }

    filteredReports = allReports.filter(report => {
        const reportDate = new Date(report.dateGenerated);
        const fromDate = new Date(dateFrom);
        const toDate = new Date(dateTo);
        return reportDate >= fromDate && reportDate <= toDate;
    });

    currentPage = 1;
    closeDateRangeModal();
    renderReports();
    updatePagination();
}

// Clear date filter
function clearDateFilter() {
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    filteredReports = [...allReports];
    currentPage = 1;
    closeDateRangeModal();
    renderReports();
    updatePagination();
}