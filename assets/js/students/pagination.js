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
        const pageWindow = 1;

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

        if (totalPages <= 2 * pageWindow + 5) {
            for (let i = 1; i <= totalPages; i++) appendBtn(String(i), true, i, i === currentPage);
        } else {
            const visiblePages = [...new Set([
                1,
                totalPages,
                ...Array.from(
                    { length: pageWindow * 2 + 1 },
                    (_, offset) => currentPage - pageWindow + offset
                ).filter(page => page > 1 && page < totalPages)
            ])].sort((a, b) => a - b);

            visiblePages.forEach((page, index) => {
                if (index > 0 && page - visiblePages[index - 1] > 1) {
                    const firstPage = visiblePages[index - 1] + 1;
                    const lastPage = page - 1;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative inline-flex';
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = ellipsis + ' rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
                    button.textContent = '…';
                    button.title = `Go to a page between ${firstPage} and ${lastPage}`;
                    button.setAttribute('aria-label', button.title);

                    const panel = document.createElement('form');
                    panel.className = 'absolute right-0 top-full z-10 mt-2 w-56 rounded-lg border border-gray-200 bg-white p-3 text-left shadow-lg dark:border-slate-600 dark:bg-slate-800';
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
                        const open = !panel.classList.contains('hidden');
                        container.querySelectorAll('form[aria-label^="Jump to page"]').forEach(item => item.classList.add('hidden'));
                        if (!open) {
                            panel.classList.remove('hidden');
                            input.focus();
                        }
                    });
                    panel.addEventListener('submit', event => {
                        event.preventDefault();
                        const destination = Number(input.value);
                        if (!Number.isInteger(destination) || destination < firstPage || destination > lastPage) {
                            error.textContent = `Enter a whole number from ${firstPage} to ${lastPage}.`;
                            error.classList.remove('hidden');
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

        appendBtn('Next »', currentPage < totalPages, currentPage + 1, false);
    }

    renderCompactPaginationDOM(pagination, currentPage, totalPages, changePage);
}