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

async function openPendingCheckInFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const caseId = params.get('caseId');
    const openCheckIn = params.get('openCheckIn');
    const requestedSanctionType = params.get('sanctionType');
    const sanctionType = requestedSanctionType === 'suspension' ? 'suspension' : 'corrective';

    const clearNotificationOpenParams = () => {
        const nextParams = new URLSearchParams(window.location.search);
        nextParams.delete('caseId');
        nextParams.delete('openCheckIn');
        nextParams.delete('sanctionType');

        const nextQuery = nextParams.toString();
        const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash || ''}`;
        window.history.replaceState({}, document.title, nextUrl);
    };

    if (openCheckIn !== '1' || !caseId || window.__openedCheckInCaseId === caseId) {
        return;
    }

    window.__openedCheckInCaseId = caseId;

    try {
        if (typeof openCheckInModal === 'function') {
            await openCheckInModal(caseId, sanctionType);
        }

        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax=1&action=getCheckInHistory&caseId=${encodeURIComponent(caseId)}`
        });
        const result = await response.json();
        if (!result.success || !Array.isArray(result.sanctions) || result.sanctions.length === 0) {
            return;
        }

        const sanction = typeof findSanctionByType === 'function'
            ? (findSanctionByType(result.sanctions, sanctionType) || result.sanctions[0])
            : result.sanctions[0];
        if (!sanction) {
            return;
        }

        const submissions = Array.isArray(result.case_portfolio_submissions) ? result.case_portfolio_submissions : [];
        if (typeof openCommunityServiceSubmissionsModal === 'function') {
            openCommunityServiceSubmissionsModal(caseId, sanction.case_sanction_id || null, submissions);
        }

        clearNotificationOpenParams();
    } catch (error) {
        console.error('Failed to open check-in notification target:', error);

        // Even on failure, clear one-time auto-open params to avoid repeated modal attempts on reload.
        clearNotificationOpenParams();
    }
}

async function openCaseDetailsFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const caseId = params.get('caseId') || params.get('case_id');
    const openDetails = params.get('viewCase');
    const tab = params.get('tab');
    const requestedPage = Number.parseInt(params.get('page') || '', 10);

    if (openDetails !== '1' || !caseId || window.__openedViewCaseId === caseId) {
        return;
    }

    const validTabs = ['current', 'resolved', 'archived'];
    if (validTabs.includes(tab) && typeof currentTab !== 'undefined' && currentTab !== tab && typeof switchTab === 'function') {
        switchTab(tab);
        return;
    }

    const normalizedCaseId = String(caseId).trim().toLowerCase();
    const caseIndex = filteredCases.findIndex((caseItem) => String(caseItem.id).trim().toLowerCase() === normalizedCaseId);

    if (Number.isInteger(requestedPage) && requestedPage > 0) {
        updateActiveTabPage(requestedPage);
        renderCases();
    } else if (caseIndex >= 0) {
        const targetPage = Math.floor(caseIndex / casesPerPage) + 1;
        updateActiveTabPage(targetPage);
        renderCases();
    }

    window.__openedViewCaseId = caseId;

    try {
        if (typeof window.viewCase === 'function') {
            await window.viewCase(caseId);
        }
    } catch (error) {
        console.error('Failed to open case details from URL:', error);
    }
}

async function highlightCaseFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const caseId = params.get('highlightCaseId');
    const shouldHighlight = params.get('highlightCase') === '1';
    const tab = params.get('tab');

    if (!shouldHighlight || !caseId) {
        return;
    }

    const validTabs = ['current', 'resolved', 'archived'];
    if (validTabs.includes(tab) && typeof currentTab !== 'undefined' && currentTab !== tab && typeof switchTab === 'function') {
        switchTab(tab);
        return;
    }

    const normalizedCaseId = String(caseId).trim().toLowerCase();
    const caseIndex = filteredCases.findIndex((caseItem) => String(caseItem.id).trim().toLowerCase() === normalizedCaseId);

    if (caseIndex < 0) {
        return;
    }

    const targetPage = Math.floor(caseIndex / casesPerPage) + 1;
    if (currentPage !== targetPage) {
        updateActiveTabPage(targetPage);
        renderCases();
    }

    const targetRow = document.querySelector(`tr[data-case-id="${CSS.escape(String(caseId))}"]`);
    if (!targetRow) {
        return;
    }

    document.querySelectorAll('tr[data-case-id]').forEach((row) => {
        row.classList.remove('bg-blue-100', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-500', 'shadow-sm');
    });

    targetRow.classList.add('bg-blue-100', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-500', 'shadow-sm');
    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const url = new URL(window.location.href);
    url.searchParams.delete('highlightCase');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}

// Simple pagination renderer
function renderPagination() {
    const paginationContainer = document.getElementById('paginationButtons');
    const infoContainer = document.getElementById('paginationInfo');

    if (!paginationContainer || !infoContainer) return;

    const totalCases = filteredCases.length;
    const totalPages = Math.ceil(totalCases / casesPerPage);

    // Clamp currentPage to valid range
    if (currentPage > totalPages) currentPage = totalPages || 1;
    setPageForTab(currentTab, currentPage);

    // Update info text (show start-end of current page)
    const start = totalCases === 0 ? 0 : (currentPage - 1) * casesPerPage + 1;
    const end = Math.min(start + casesPerPage - 1, totalCases);
    infoContainer.textContent = `Showing ${start}-${end} of ${totalCases} cases`;

    // Clear old buttons
    paginationContainer.innerHTML = '';

    const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center mx-1';
    const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center mx-1';
    const disabledClass = 'opacity-50 cursor-not-allowed';

    const appendBtn = (text, enabled, page, isActive) => {
        const tag = enabled ? 'button' : 'span';
        const el = document.createElement(tag);
        el.textContent = text;
        el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
        if (!enabled) el.setAttribute('aria-disabled', 'true');
        if (enabled) el.addEventListener('click', () => { updateActiveTabPage(page); renderCases(); });
        paginationContainer.appendChild(el);
    };

    appendBtn('« Prev', currentPage > 1, Math.max(1, currentPage - 1), false);

    for (let i = 1; i <= totalPages; i++) {
        appendBtn(String(i), true, i, i === currentPage);
    }

    appendBtn('Next »', currentPage < totalPages, Math.min(totalPages, currentPage + 1), false);
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
        const colSpan = currentTab === 'archived' ? '8' : '7';
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
        <tr class="h-[72px] hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors" data-case-id="${caseItem.id}">
            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 w-28"><div class="truncate">${caseItem.id}</div></td>
            <td class="px-5 py-4 w-48">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex-shrink-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-white">${caseItem.student.split(' ').map(n => n[0]).join('').substring(0, 2)}</span>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseItem.student}</span>
                </div>
            </td>
            <td class="pl-5 pr-2 py-4 text-sm text-gray-700 dark:text-gray-300 w-48"><div class="truncate">${caseItem.type}</div></td>
            <td class="pl-2 pr-4 py-4 text-sm text-gray-700 dark:text-gray-300 w-28"><div class="truncate">${caseItem.date}</div></td>
            <td class="pl-4 pr-4 py-4 text-sm text-gray-700 dark:text-gray-300 w-36"><div class="truncate">${caseItem.assignedTo || 'Unassigned'}</div></td>
            <td class="pl-4 pr-1 py-4 w-32">
                <div class="truncate inline-block">
                    <span class="inline-block px-2.5 py-1 text-xs font-medium rounded ${statusColors[caseItem.statusColor]}">${caseItem.status}</span>
                </div>
            </td>
            <td class="pl-0 pr-2 py-2 whitespace-nowrap w-56">
                <div class="flex items-center gap-0.5 -ml-3">
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
                        ${String(caseItem.status || '').toLowerCase() !== 'resolved' ? `
                        <button onclick="manageSanctions('${caseItem.id}')"
                            class="px-3 py-1.5 text-base text-[#60A5FA] hover:text-blue-700 transition-colors">
                            Sanctions
                        </button>
                        ` : ''}
                        ${caseItem.status !== 'Resolved' ? `
                        <button onclick="markCaseResolved('${caseItem.id}')"
                            title="${getCaseResolutionBlockReason(caseItem) || 'Mark this case as resolved'}"
                            class="px-3 py-1.5 text-base text-green-600 hover:text-green-700 transition-colors font-medium">
                            Mark Resolved
                        </button>
                        ` : ''}
                        ${caseItem.hasCorrectiveService ? `
                        <button onclick="openCheckInModal('${caseItem.id}', 'corrective')"
                            data-case-checkin-icon="true"
                            data-case-checkin-type="corrective"
                            data-case-id="${caseItem.id}"
                            class="inline-flex relative items-center justify-center h-8 w-8 ${caseItem.hasCorrectiveServiceCompleted ? 'text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300' : 'text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300'} transition-colors" title="${caseItem.hasCorrectiveServiceCompleted ? 'Community Service Check-In Complete (100%)' : 'Community Service Check-In In Progress'}" style="padding:0;margin-left:1px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2.5" stroke="currentColor" stroke-width="2" fill="none"/>
                                <path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            ${caseItem.hasNewCommunityServiceSubmission ? '<span data-case-checkin-alert="true" class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-600 text-white text-[10px] leading-none flex items-center justify-center font-bold">!</span>' : ''}
                        </button>
                        ` : ''}
                        ${caseItem.hasSuspensionFromClass ? `
                        <button onclick="openCheckInModal('${caseItem.id}', 'suspension')"
                            data-case-checkin-icon="true"
                            data-case-checkin-type="suspension"
                            data-case-id="${caseItem.id}"
                            class="inline-flex items-center justify-center h-8 w-8 ${caseItem.hasSuspensionFromClassCompleted ? 'text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300' : 'text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300'} transition-colors" title="${caseItem.hasSuspensionFromClassCompleted ? 'Suspension Progress Complete (100%)' : 'Suspension Progress In Progress'}" style="padding:0;margin-left:1px;">
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
                <td colspan="${currentTab === 'archived' ? 8 : 7}"></td>
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
        body: `ajax=1&action=getCases&search=${encodeURIComponent(searchTerm)}&type=${encodeURIComponent(typeFilter)}&status=${encodeURIComponent(statusFilter)}&severity=${encodeURIComponent(caseSeverity)}&archived=${archived}`
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
                try {
                    allCases = data.cases;

                    // Reapply the active tab and advanced filters after every reload.
                    applyClientSideFilters();

                    openPendingCheckInFromUrl();
                    openCaseDetailsFromUrl();
                    highlightCaseFromUrl();

                    console.log('Loaded cases:', allCases.length, 'Filtered:', filteredCases.length);
                } catch (renderError) {
                    console.error('Render error:', renderError);
                    const colSpan = currentTab === 'archived' ? 8 : 7;
                    document.getElementById('casesTableBody').innerHTML = `
                        <tr><td colspan="${colSpan}" class="px-6 py-8 text-center text-red-500">
                            Error rendering cases table: ${renderError.message}
                        </td></tr>
                    `;
                }
            } else {
                console.error('Failed to load cases:', data.error);
                const colSpan = currentTab === 'archived' ? 8 : 7;
                document.getElementById('casesTableBody').innerHTML = `
                    <tr><td colspan="${colSpan}" class="px-6 py-8 text-center text-red-500">
                        Error loading cases: ${data.error || 'Unknown error'}
                    </td></tr>
                `;
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            const colSpan = currentTab === 'archived' ? 8 : 7;
            document.getElementById('casesTableBody').innerHTML = `
                <tr><td colspan="${colSpan}" class="px-6 py-8 text-center text-red-500">
                    Error: Invalid response from server. Check console for details.
                </td></tr>
            `;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        const colSpan = currentTab === 'archived' ? 8 : 7;
        document.getElementById('casesTableBody').innerHTML = `
            <tr><td colspan="${colSpan}" class="px-6 py-8 text-center text-red-500">
                Error loading cases: ${error.message}. Please check console.
            </td></tr>
        `;
    });
}

// Update table header based on current tab
function updateTableHeader() {
    const thead = document.querySelector('thead tr');
    if (!thead) return;

    const checkboxTh = thead.querySelector('.checkbox-header');
    if (checkboxTh) {
        if (currentTab === 'archived') {
            checkboxTh.innerHTML = '<div class="flex items-center justify-center gap-2">Select</div>';
        } else {
            checkboxTh.remove();
        }
    } else if (currentTab === 'archived') {
        const newCheckboxTh = document.createElement('th');
        newCheckboxTh.className = 'checkbox-header px-4 py-3 text-center text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider w-20';
        newCheckboxTh.innerHTML = '<div class="flex items-center justify-center gap-2">Select</div>';
        thead.appendChild(newCheckboxTh);
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