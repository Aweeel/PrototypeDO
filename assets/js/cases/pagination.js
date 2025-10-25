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
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No cases found.
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
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
                    <span class="text-gray-900 dark:text-gray-100">${c.student}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.type}</td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.date}</td>
            <td class="px-6 py-4 text-sm">
                <span class="px-2.5 py-1 text-xs font-medium rounded ${statusColors[c.statusColor]}">${c.status}</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">${c.assignedTo}</td>
            <td class="px-6 py-4 text-sm">
                <button onclick="viewCase('${c.id}')" class="text-blue-600 dark:text-blue-400 hover:underline mr-3">View</button>
                <button onclick="editCase('${c.id}')" class="text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
            </td>
        </tr>
    `).join('');

    updatePaginationInfo();
    updatePaginationButtons();
}

function changePage(page) {
    const totalPages = Math.ceil(filteredCases.length / casesPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
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

    if (totalPages === 0) {
        pagination.innerHTML = '';
        return;
    }

    let html = `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} 
            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
            Previous
        </button>
    `;

    for (let i = 1; i <= totalPages; i++) {
        html += `
            <button onclick="changePage(${i})" 
                class="px-3 py-1.5 text-sm border rounded ${
                    i === currentPage 
                        ? 'bg-blue-600 text-white border-blue-600' 
                        : 'border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300'
                }">
                ${i}
            </button>
        `;
    }

    html += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} 
            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
            Next
        </button>
    `;

    pagination.innerHTML = html;
}