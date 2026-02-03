// ====== Pagination Functions ======

function changePage(page) {
    const totalPages = Math.ceil(filteredStudents.length / studentsPerPage);
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    renderStudents();
    
    // Scroll to top smoothly
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updatePaginationInfo() {
    const info = document.getElementById('paginationInfo');
    const start = filteredStudents.length === 0 ? 0 : (currentPage - 1) * studentsPerPage + 1;
    const end = Math.min(start + studentsPerPage - 1, filteredStudents.length);
    info.textContent = `Showing ${start}-${end} of ${filteredStudents.length} students`;
}

function updatePaginationButtons() {
    const pagination = document.getElementById('paginationButtons');
    const totalPages = Math.ceil(filteredStudents.length / studentsPerPage);

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

    // Show page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
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
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<span class="px-2 text-gray-500">...</span>`;
        }
    }

    html += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} 
            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
            Next
        </button>
    `;

    pagination.innerHTML = html;
}