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

    function renderCompactPaginationDOM(container, currentPage, totalPages, onPageChange) {
        if (!container) return;
        const btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
        const active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold';
        const disabledClass = 'opacity-50 cursor-not-allowed';
        const ellipsis = 'px-2';
        const maxButtons = 7;

        container.innerHTML = '';
        if (totalPages <= 1) return;

        const appendBtn = (text, enabled, page, isActive) => {
            const el = document.createElement(enabled ? 'button' : 'span');
            el.textContent = text;
            el.className = isActive ? active : (btnBase + (enabled ? '' : ' ' + disabledClass));
            if (enabled && typeof onPageChange === 'function') el.addEventListener('click', () => onPageChange(page));
            container.appendChild(el);
        };

        appendBtn('« Prev', currentPage > 1, currentPage - 1, false);

        if (totalPages <= maxButtons) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const innerCount = maxButtons - 2;
            let start = Math.max(2, currentPage - Math.floor(innerCount / 2));
            let end = Math.min(totalPages - 1, start + innerCount - 1);
            if (end - start + 1 < innerCount) start = Math.max(2, end - innerCount + 1);

            appendBtn('1', true, 1, currentPage === 1);
            if (start > 2) { const s = document.createElement('span'); s.className = ellipsis; s.textContent = '…'; container.appendChild(s); }

            for (let i = start; i <= end; i++) appendBtn(String(i), true, i, i === currentPage);

            if (end < totalPages - 1) { const s = document.createElement('span'); s.className = ellipsis; s.textContent = '…'; container.appendChild(s); }
            appendBtn(String(totalPages), true, totalPages, currentPage === totalPages);
        }

        appendBtn('Next »', currentPage < totalPages, currentPage + 1, false);
    }

    renderCompactPaginationDOM(pagination, currentPage, totalPages, changePage);
}