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

// Simple pagination renderer
function renderPagination() {
    const paginationContainer = document.getElementById('paginationButtons');
    const infoContainer = document.getElementById('paginationInfo');

    if (!paginationContainer || !infoContainer) return;

    const totalCases = filteredCases.length;
    const totalPages = Math.ceil(totalCases / casesPerPage);

    // Clamp currentPage to valid range
    if (currentPage > totalPages) currentPage = totalPages || 1;

    // Update info text
    infoContainer.textContent = `Showing ${Math.min(totalCases, casesPerPage)} of ${totalCases} cases`;

    // Clear old buttons
    paginationContainer.innerHTML = '';

    // Create page buttons
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-1 mx-1 rounded ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'}`;
        btn.addEventListener('click', () => {
            currentPage = i;
            renderCases();
        });
        paginationContainer.appendChild(btn);
    }
}

// Render cases in the table
function renderCases() {
    renderTableRows();
    renderPagination();
}

// Render table rows with Sanctions button
function renderTableRows() {
    const tbody = document.getElementById('casesTableBody');
    const start = (currentPage - 1) * casesPerPage;
    const end = start + casesPerPage;
    const casesToDisplay = filteredCases.slice(start, end);

    if (casesToDisplay.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    ${currentTab === 'archived' ? 'No archived cases found.' : 'No cases found.'}
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = casesToDisplay.map(caseItem => `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">${caseItem.id}</td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseItem.student}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${caseItem.type}</td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${caseItem.date}</td>
            <td class="px-6 py-4">
                <span class="inline-block px-2.5 py-1 text-xs font-medium rounded ${statusColors[caseItem.statusColor]}">${caseItem.status}</span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    ${currentTab === 'archived' ? `
                        <button onclick="unarchiveCase('${caseItem.id}')" 
                            class="px-3 py-1.5 text-s text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Restore
                        </button>
                    ` : `
                        <button onclick="viewCase('${caseItem.id}')" 
                            class="px-3 py-1.5 text-s text-[#60A5FA] hover:text-blue-700 transition-colors">
                            View
                        </button>
                        <button onclick="editCase('${caseItem.id}')" 
                            class="px-3 py-1.5 text-s text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Edit
                        </button>
                        <button onclick="manageSanctions('${caseItem.id}')" 
                            class="px-3 py-1.5 text-s text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Sanctions
                        </button>
                    `}
                </div>
            </td>
        </tr>
    `).join('');
}

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
        return response.text();
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