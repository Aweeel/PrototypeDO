// =========================
// Audit Log Filters Script
// =========================

// -------------------------
// Filtering logic
// -------------------------
function filterLogs() {
    currentFilters.search = document.getElementById('searchInput').value.trim();
    currentFilters.actionType = document.getElementById('actionTypeFilter').value;
    currentFilters.user = document.getElementById('userFilter').value;  // This contains the role now
    currentPage = 1;
    loadLogs();
}

// -------------------------
// Date range modal controls
// -------------------------
function openAdvancedFilters() {
    document.getElementById('dateRangeModal').classList.remove('hidden');
}

function closeDateRangeModal() {
    document.getElementById('dateRangeModal').classList.add('hidden');
}

function applyDateFilter() {
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;

    currentFilters.dateFrom = from;
    currentFilters.dateTo = to;

    closeDateRangeModal();
    currentPage = 1;
    loadLogs();
}

function clearDateFilter() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    currentFilters.dateFrom = '';
    currentFilters.dateTo = '';
    currentPage = 1;
    loadLogs();
}

function clearAllFilters() {
    // Clear all filter inputs
    document.getElementById('searchInput').value = '';
    document.getElementById('actionTypeFilter').value = '';
    document.getElementById('userFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('sortFilter').value = 'newest';
    
    // Reset filter object
    currentFilters.search = '';
    currentFilters.actionType = '';
    currentFilters.user = '';
    currentFilters.dateFrom = '';
    currentFilters.dateTo = '';
    
    // Reload logs
    currentPage = 1;
    loadLogs();
}
