// ====== Main Initialization ======

document.addEventListener('DOMContentLoaded', () => {
    console.log('Cases page loaded');
    
    // Load cases from database via AJAX
    loadCasesFromDB();

    // Set max date for date inputs
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.setAttribute('max', today);
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.querySelector('.fixed.inset-0');
            if (modal) modal.remove();
        }
        
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            addCase();
        }
    });
});

// Load cases from database
function loadCasesFromDB() {
    console.log('Loading cases from database...');
    
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const typeFilter = document.getElementById('typeFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    
    fetch('/PrototypeDO/pages/do/cases.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=getCases&search=${encodeURIComponent(searchTerm)}&type=${encodeURIComponent(typeFilter)}&status=${encodeURIComponent(statusFilter)}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response from server:', data);
        if (data.success) {
            allCases = data.cases;
            filteredCases = [...allCases];
            console.log('Loaded cases:', allCases.length);
            renderCases();
        } else {
            console.error('Failed to load cases:', data.error);
            document.getElementById('casesTableBody').innerHTML = `
                <tr><td colspan="7" class="px-6 py-8 text-center text-red-500">
                    Error loading cases: ${data.error || 'Unknown error'}
                </td></tr>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading cases:', error);
        document.getElementById('casesTableBody').innerHTML = `
            <tr><td colspan="7" class="px-6 py-8 text-center text-red-500">
                Error loading cases. Please refresh the page.
            </td></tr>
        `;
    });
}