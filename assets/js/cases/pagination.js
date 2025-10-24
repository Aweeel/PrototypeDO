function renderCases() {
    const tbody = document.getElementById('casesTableBody');
    const startIndex = (currentPage - 1) * casesPerPage;
    const endIndex = startIndex + casesPerPage;
    const paginatedCases = filteredCases.slice(startIndex, endIndex);

    if (paginatedCases.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="7" class="text-center py-6 text-gray-500 dark:text-gray-400">No cases found.</td></tr>
        `;
        updatePaginationInfo();
        updatePaginationButtons();
        return;
    }

    tbody.innerHTML = paginatedCases.map(c => `
        <tr>
            <td class="px-6 py-3">${c.id}</td>
            <td class="px-6 py-3">${c.student}</td>
            <td class="px-6 py-3">${c.type}</td>
            <td class="px-6 py-3">${c.date}</td>
            <td class="px-6 py-3"><span class="px-3 py-1 text-xs rounded-full ${statusColors[c.statusColor]}">${c.status}</span></td>
            <td class="px-6 py-3">${c.assignedTo}</td>
            <td class="px-6 py-3">
                <button onclick="viewCase('${c.id}')" class="text-blue-600">View</button>
                <button onclick="editCase('${c.id}')" class="text-blue-600 ml-2">Edit</button>
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
    const start = (currentPage - 1) * casesPerPage + 1;
    const end = Math.min(start + casesPerPage - 1, filteredCases.length);
    info.textContent = `Showing ${start}-${end} of ${filteredCases.length}`;
}

function updatePaginationButtons() {
    const pagination = document.getElementById('paginationButtons');
    pagination.innerHTML = '';
    const totalPages = Math.ceil(filteredCases.length / casesPerPage);

    const makeBtn = (text, disabled, clickHandler, isActive = false) => {
        const btn = document.createElement('button');
        btn.textContent = text;
        btn.disabled = disabled;
        btn.className = `px-3 py-1 border rounded ${
            isActive ? 'bg-blue-600 text-white' :
            'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700'
        } disabled:opacity-50`;
        btn.onclick = clickHandler;
        return btn;
    };

    pagination.appendChild(makeBtn('Prev', currentPage === 1, () => changePage(currentPage - 1)));
    for (let i = 1; i <= totalPages; i++) {
        pagination.appendChild(makeBtn(i, false, () => changePage(i), i === currentPage));
    }
    pagination.appendChild(makeBtn('Next', currentPage === totalPages, () => changePage(currentPage + 1)));
}
