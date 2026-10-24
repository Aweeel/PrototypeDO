// ====== Pagination Functions ======

function renderCases() {
    
    const tbody = document.getElementById('casesTableBody');
    const startIndex = (currentPage - 1) * casesPerPage;
    const endIndex = startIndex + casesPerPage;
    const paginatedCases = filteredCases.slice(startIndex, endIndex);


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
        <tr data-case-id="${c.id}" class="hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
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
                           ${c.severity === 'Minor' && c.status === 'Unrecorded' && c.offenseNumber >= 3 && !c.escalationSeen ? `<button onclick="openMinorEscalation('${c.id}')" title="Escalate minor offense to Major" class="inline-flex items-center justify-center w-8 h-8 text-orange-600 hover:text-orange-700 dark:text-orange-300 dark:hover:text-orange-200 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 rounded mr-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" /></svg></button>` : ''}
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
        const pageWindow = 1;

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

        if (totalPages <= 2 * pageWindow + 5) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const visiblePages = [...new Set([
                1,
                totalPages,
                ...Array.from({ length: pageWindow * 2 + 1 }, (_, offset) => currentPage - pageWindow + offset)
                    .filter(page => page > 1 && page < totalPages)
            ])].sort((a, b) => a - b);

            visiblePages.forEach((page, index) => {
                if (index > 0 && page - visiblePages[index - 1] > 1) {
                    const firstPage = visiblePages[index - 1] + 1;
                    const lastPage = page - 1;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative z-20 inline-flex';
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = ellipsis + ' rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
                    button.textContent = '…';
                    button.title = `Go to a page between ${firstPage} and ${lastPage}`;
                    button.setAttribute('aria-label', button.title);
                    const panel = document.createElement('form');
                    panel.className = 'fixed z-50 mt-2 w-56 max-w-[calc(100vw-1rem)] rounded-lg border border-gray-200 bg-white p-3 text-left shadow-lg dark:border-slate-600 dark:bg-slate-800';
                    panel.setAttribute('aria-label', `Jump to page ${firstPage} to ${lastPage}`);
                    panel.innerHTML = `
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Go to page</label>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" min="${firstPage}" max="${lastPage}" step="1" required
                                class="min-w-0 flex-1 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100"
                                placeholder="${firstPage}-${lastPage}">
                            <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Go</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter a page from ${firstPage} to ${lastPage}.</p>
                        <p class="hidden mt-2 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    `;
                    panel.classList.add('hidden');
                    const input = panel.querySelector('input');
                    const error = panel.querySelector('[role="alert"]');
                    button.addEventListener('click', () => {
                        const isOpen = !panel.classList.contains('hidden');
                        container.querySelectorAll('form[aria-label^="Jump to page"]').forEach(item => item.classList.add('hidden'));
                        if (!isOpen) {
                            panel.classList.remove('hidden');
                            const buttonRect = button.getBoundingClientRect();
                            const panelWidth = Math.min(224, window.innerWidth - 16);
                            const left = Math.max(8, Math.min(
                                buttonRect.right - panelWidth,
                                window.innerWidth - panelWidth - 8
                            ));
                            panel.style.left = `${left}px`;
                            const top = buttonRect.bottom + panel.offsetHeight + 8 <= window.innerHeight
                                ? buttonRect.bottom
                                : Math.max(8, buttonRect.top - panel.offsetHeight - 8);
                            panel.style.top = `${top}px`;
                            input.focus();
                        }
                    });
                    panel.addEventListener('submit', event => {
                        event.preventDefault();
                        const destination = Number(input.value);
                        if (!Number.isInteger(destination) || destination < firstPage || destination > lastPage) {
                            error.textContent = `Enter a whole number from ${firstPage} to ${lastPage}.`;
                            error.classList.remove('hidden');
                            input.focus();
                            return;
                        }
                        panel.classList.add('hidden');
                        onPageChange(destination);
                    });
                    input.addEventListener('input', () => error.classList.add('hidden'));
                    wrapper.append(button, panel);
                    container.appendChild(wrapper);
                }
                appendBtn(String(page), true, page, page === currentPage);
            });
        }

        appendBtn('Next »', currentPage < totalPages, Math.min(totalPages, currentPage + 1), false);
    }

    renderCompactPaginationDOM(pagination, currentPage, totalPages, changePage);
}