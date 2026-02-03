// ====== Filter Functions ======

function filterStudents() {
    console.log('Filtering students...');
    // Reload from database with current filters
    loadStudents();
}

// Filter by status (for button toggles if needed)
function filterByStatus(status) {
    console.log('Filter by status:', status);
    
    // Update all status buttons (if you add them later)
    document.querySelectorAll('[onclick^="filterByStatus"]').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
    });

    // Highlight active button
    if (event && event.target) {
        event.target.classList.remove('bg-gray-200', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
        event.target.classList.add('bg-blue-600', 'text-white');
    }

    // Apply filter
    if (status === '') {
        filteredStudents = [...allStudents];
    } else {
        filteredStudents = allStudents.filter(s => s.status === status);
    }

    currentPage = 1;
    renderStudents();
}

// Toggle filters modal (for future advanced filters)
function toggleFilters() {
    console.log('Toggle advanced filters');
    // Implement advanced filters modal if needed
    showNotification('Advanced filters coming soon', 'info');
}