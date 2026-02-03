// Generate Report Modal
function openGenerateModal() {
    document.getElementById('generateModal').classList.remove('hidden');
    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    document.getElementById('startDate').value = lastMonth.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
}

function closeGenerateModal() {
    document.getElementById('generateModal').classList.add('hidden');
    document.getElementById('generateReportForm').reset();
}

// Date Range Modal
function openDateRangeModal() {
    document.getElementById('dateRangeModal').classList.remove('hidden');
}

function closeDateRangeModal() {
    document.getElementById('dateRangeModal').classList.add('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeGenerateModal();
        closeDateRangeModal();
    }
});

// Close modals when clicking outside
document.getElementById('generateModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'generateModal') {
        closeGenerateModal();
    }
});

document.getElementById('dateRangeModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'dateRangeModal') {
        closeDateRangeModal();
    }
});