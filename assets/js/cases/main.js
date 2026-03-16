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
            closeAllRowMenus();
        }
        
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            addCase();
        }
    });

    // Close row dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[id^="moreMenu-"]')) {
            closeAllRowMenus();
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
    
    // Update table header based on current tab
    updateTableHeader();


    if (casesToDisplay.length === 0) {
        const colSpan = currentTab === 'archived' ? '7' : '6';
        tbody.innerHTML = `
            <tr>
                <td colspan="${colSpan}" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    ${currentTab === 'archived' ? 'No archived cases found.' : currentTab === 'resolved' ? 'No resolved cases found.' : 'No cases found.'}
                </td>
            </tr>
        `;
        return;
    }

    let tableHTML = casesToDisplay.map(caseItem => `
        <tr class="h-[72px] hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
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
            <td class="px-2 py-2 pr-4 whitespace-nowrap" style="width:1%;">
                <div class="flex items-center gap-0.5">
                    ${currentTab === 'archived' ? `
                        <button onclick="unarchiveCase('${caseItem.id}')"
                            class="px-3 py-1.5 text-base text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Restore
                        </button>
                    ` : `
                        <button onclick="viewCase('${caseItem.id}')"
                            class="px-3 py-1.5 text-base text-[#60A5FA] hover:text-blue-700 transition-colors">
                            View
                        </button>
                        <button onclick="manageSanctions('${caseItem.id}')"
                            class="px-3 py-1.5 text-base text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Sanctions
                        </button>
                        ${caseItem.status !== 'Resolved' ? `
                        <button onclick="markCaseResolved('${caseItem.id}')"
                            class="px-3 py-1.5 text-base text-green-600 hover:text-green-700 transition-colors font-medium">
                            Mark Resolved
                        </button>
                        ` : ''}
                        ${caseItem.hasCorrectiveService ? `
                        <button onclick="openCheckInModal('${caseItem.id}')"
                            class="inline-flex items-center justify-center h-8 w-8 text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300 transition-colors" title="Check-In" style="padding:0;margin-left:1px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2.5" stroke="currentColor" stroke-width="2" fill="none"/>
                                <path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                        </button>
                        ` : ''}
                    `}
                </div>
            </td>
            ${currentTab === 'archived' ? `
                <td class="px-4 py-4 text-center cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" 
                    onclick="toggleCaseCheckbox('${caseItem.id}')" 
                    title="Click to select/deselect">
                    <input type="checkbox" 
                        id="checkbox-${caseItem.id}" 
                        class="case-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer" 
                        data-case-id="${caseItem.id}" 
                        ${selectedCaseIds.has(caseItem.id.toString()) ? 'checked' : ''}
                        onchange="handleCheckboxChange('${caseItem.id}', this.checked)" 
                        onclick="event.stopPropagation()">
                </td>
            ` : ''}
        </tr>
    `).join('');

    // Add empty rows to maintain consistent table height
    const emptyRowsCount = casesPerPage - casesToDisplay.length;
    for (let i = 0; i < emptyRowsCount; i++) {
        tableHTML += `
            <tr class="h-[72px] border-b border-gray-100 dark:border-slate-700">
                <td colspan="6"></td>
            </tr>
        `;
    }

    tbody.innerHTML = tableHTML;
}

// Load cases from database
function loadCasesFromDB() {
    console.log('Loading cases from database...');
    
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const typeFilter = document.getElementById('typeFilter')?.value || '';
    let statusFilter = document.getElementById('statusFilter')?.value || '';
    
    // Handle tab-based filtering
    let archived = 'false';
    if (typeof currentTab !== 'undefined') {
        if (currentTab === 'archived') {
            archived = 'true';
        } else if (currentTab === 'resolved') {
            // For resolved tab, filter by status=Resolved and not archived
            statusFilter = 'Resolved';
        } else if (currentTab === 'current') {
            // For current tab, exclude resolved cases
            // We'll handle this on the client side after fetching
        }
    }
    
    console.log('Filters:', { searchTerm, typeFilter, statusFilter, archived, currentTab });
    
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
                
                // Filter cases based on current tab
                if (currentTab === 'current') {
                    // Exclude resolved cases from current tab
                    filteredCases = allCases.filter(c => c.status !== 'Resolved');
                } else {
                    filteredCases = [...allCases];
                }
                
                console.log('Loaded cases:', allCases.length, 'Filtered:', filteredCases.length);
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

// Update table header based on current tab
function updateTableHeader() {
    const thead = document.querySelector('thead tr');
    if (!thead) return;
    
    if (currentTab === 'archived') {
        // Remove old checkbox header if exists
        const oldCheckboxTh = thead.querySelector('.checkbox-header');
        if (oldCheckboxTh) {
            oldCheckboxTh.remove();
        }
        
        // Add checkbox column
        const checkboxTh = document.createElement('th');
        checkboxTh.className = 'checkbox-header px-4 py-3 text-center text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider w-20';
        checkboxTh.innerHTML = `
            <div class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Select
            </div>
        `;
        thead.appendChild(checkboxTh);
    } else {
        // Remove checkbox column if exists
        const checkboxTh = thead.querySelector('.checkbox-header');
        if (checkboxTh) {
            checkboxTh.remove();
        }
    }
}

// Toggle all checkboxes
function toggleAllCheckboxes(checked) {
    const checkboxes = document.querySelectorAll('.case-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateBulkRestoreButton();
}

// Toggle checkbox when clicking on the cell
function toggleCaseCheckbox(caseId) {
    const checkbox = document.getElementById(`checkbox-${caseId}`);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        handleCheckboxChange(caseId, checkbox.checked);
    }
}

// Handle checkbox state changes
function handleCheckboxChange(caseId, isChecked) {
    const caseIdStr = caseId.toString();
    
    if (isChecked) {
        selectedCaseIds.add(caseIdStr);
    } else {
        selectedCaseIds.delete(caseIdStr);
    }
    
    updateBulkRestoreButton();
}

// Update the visibility and text of bulk restore button
function updateBulkRestoreButton() {
    const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
    
    if (bulkRestoreBtn) {
        const selectedCount = selectedCaseIds.size;
        if (selectedCount > 0) {
            bulkRestoreBtn.classList.remove('hidden');
            bulkRestoreBtn.querySelector('.count').textContent = selectedCount;
        } else {
            bulkRestoreBtn.classList.add('hidden');
        }
    }
}

// Clear all selections
function clearCaseSelections() {
    selectedCaseIds.clear();
    updateBulkRestoreButton();
}

// ====== Row dropdown menu helpers ======
function toggleRowMenu(caseId) {
    const dropdown = document.getElementById('dropdown-' + caseId);
    if (!dropdown) return;
    const isHidden = dropdown.classList.contains('hidden');
    closeAllRowMenus();
    if (isHidden) dropdown.classList.remove('hidden');
}

function closeAllRowMenus() {
    document.querySelectorAll('[id^="dropdown-"]').forEach(d => d.classList.add('hidden'));
}