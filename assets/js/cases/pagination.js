// ====== Pagination Functions ======

function renderCases() {
    console.log('Rendering cases...');
    console.log('Filtered cases:', filteredCases.length);
    
    const tbody = document.getElementById('casesTableBody');
    const startIndex = (currentPage - 1) * casesPerPage;
    const endIndex = startIndex + casesPerPage;
    const paginatedCases = filteredCases.slice(startIndex, endIndex);

    console.log('Paginated cases:', paginatedCases.length);

    if (paginatedCases.length === 0) {
        const message = currentTab === 'archived' ? 'No archived cases found.' : 'No cases found.';
        tbody.innerHTML = `
            <tr>
                <td colspan="${caseSeverity === 'Minor' ? 9 : 8}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    ${message}
                </td>
            </tr>
        `;
        updatePaginationInfo();
        updatePaginationButtons();
        return;
    }

    tbody.innerHTML = paginatedCases.map(c => `
        <tr class="hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">${c.id}</td>
            <td class="px-6 py-4 text-sm">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex-shrink-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-white">${c.student.split(' ').map(n => n[0]).join('').substring(0, 2)}</span>
                    </div>
                    <span class="text-gray-900 dark:text-gray-100">${c.student}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.type}</td>
            ${caseSeverity === 'Minor' ? `<td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${formatOffenseNumber(c.offenseNumber)}</td>` : ''}
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.date}</td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.assignedTo || 'Unassigned'}</td>
            <td class="px-6 py-4 text-sm">
                <span class="px-2.5 py-1 text-xs font-medium rounded ${statusColors[c.statusColor]}">${c.status}</span>
            </td>
            <td class="pl-0 pr-2 py-4 text-sm">
                <div class="flex items-center gap-0.5 -ml-3">
                ${currentTab === 'archived' 
                    ? `<button onclick="unarchiveCase('${c.id}')" class="text-green-600 dark:text-green-400 hover:underline mr-3">Restore</button>`
                    : `<button onclick="viewCase('${c.id}')" class="text-blue-600 dark:text-blue-400 hover:underline mr-3">View</button>
                       ${(c.severity === 'Minor' ? c.status !== 'Recorded' : String(c.status || '').toLowerCase() !== 'resolved') ? `<button onclick="manageSanctions('${c.id}')" class="text-blue-600 dark:text-blue-400 hover:underline mr-3">Sanctions</button>` : ''}
                       ${c.severity !== 'Minor' && c.status !== 'Resolved' ? `<span class="text-gray-300 dark:text-gray-600 mx-2">|</span><button onclick="markCaseResolved('${c.id}')" title="${getCaseResolutionBlockReason(c) || 'Mark this case as resolved'}" class="text-green-600 dark:text-green-400 hover:underline font-medium">Mark Resolved</button>` : ''}`
                }
                </div>
            </td>
        </tr>
    `).join('');

    updatePaginationInfo();
    updatePaginationButtons();
}

function changePage(page) {
    const totalPages = Math.ceil(filteredCases.length / casesPerPage);
    if (page < 1 || page > totalPages) return;
    updateActiveTabPage(page);
    renderCases();
}

function updatePaginationInfo() {
    const info = document.getElementById('paginationInfo');
    const start = filteredCases.length === 0 ? 0 : (currentPage - 1) * casesPerPage + 1;
    const end = Math.min(start + casesPerPage - 1, filteredCases.length);
    info.textContent = `Showing ${start}-${end} of ${filteredCases.length} cases`;
}

function updatePaginationButtons() {
    const pagination = document.getElementById('paginationButtons');
    const totalPages = Math.ceil(filteredCases.length / casesPerPage);

    function renderCompactPaginationDOM(container, currentPage, totalPages, onPageChange) {
        if (!container) return;
        const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center';
        const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center';
        const disabledClass = 'opacity-50 cursor-not-allowed';
        const ellipsis = 'px-2';
        const maxButtons = 7;

        container.innerHTML = '';
        // Always render pagination controls even if there's only one page

        const appendBtn = (text, enabled, page, isActive) => {
            const tag = enabled ? 'button' : 'span';
            const el = document.createElement(tag);
            el.textContent = text;
            el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
            if (!enabled) el.setAttribute('aria-disabled', 'true');
            if (enabled && typeof onPageChange === 'function') el.addEventListener('click', () => onPageChange(page));
            container.appendChild(el);
        };

        appendBtn('« Prev', currentPage > 1, Math.max(1, currentPage - 1), false);

        if (totalPages <= maxButtons) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const innerCount = maxButtons - 2;
            let start = Math.max(2, currentPage - Math.floor(innerCount / 2));
            let end = Math.min(totalPages - 1, start + innerCount - 1);
            if (end - start + 1 < innerCount) start = Math.max(2, end - innerCount + 1);

            appendBtn('1', true, 1, currentPage === 1);
            if (start > 2) { const s = document.createElement('span'); s.className = btnBase + ' ' + disabledClass; s.textContent = '…'; container.appendChild(s); }

            for (let i = start; i <= end; i++) appendBtn(String(i), true, i, i === currentPage);

            if (end < totalPages - 1) { const s = document.createElement('span'); s.className = btnBase + ' ' + disabledClass; s.textContent = '…'; container.appendChild(s); }
            appendBtn(String(totalPages), true, totalPages, currentPage === totalPages);
        }

        appendBtn('Next »', currentPage < totalPages, Math.min(totalPages, currentPage + 1), false);
    }

    renderCompactPaginationDOM(pagination, currentPage, totalPages, changePage);
}