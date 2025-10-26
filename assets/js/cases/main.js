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
    const archived = (typeof currentTab !== 'undefined' && currentTab === 'archived') ? 'true' : 'false';
    
    console.log('Filters:', { searchTerm, typeFilter, statusFilter, archived });
    
    fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=getCases&search=${encodeURIComponent(searchTerm)}&type=${encodeURIComponent(typeFilter)}&status=${encodeURIComponent(statusFilter)}&archived=${archived}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text(); // Get as text first to see what we're receiving
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
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
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            document.getElementById('casesTableBody').innerHTML = `
                <tr><td colspan="7" class="px-6 py-8 text-center text-red-500">
                    Error: Invalid response from server. Check console for details.
                </td></tr>
            `;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('casesTableBody').innerHTML = `
            <tr><td colspan="7" class="px-6 py-8 text-center text-red-500">
                Error loading cases: ${error.message}. Please check console.
            </td></tr>
        `;
    });
}