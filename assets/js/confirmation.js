/**
 * Shared confirmation dialog for actions that only need a yes/no decision.
 */
(function () {
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.showConfirmDialog = function (options = {}) {
        const {
            message = '',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            confirmClass = 'bg-blue-600 hover:bg-blue-700'
        } = options;

        return new Promise((resolve) => {
            const existing = document.getElementById('globalConfirmDialog');
            if (existing) {
                existing.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'globalConfirmDialog';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6">
                        <p class="text-base font-medium text-gray-900 dark:text-gray-100">${escapeHtml(message)}</p>
                    </div>
                    <div class="flex gap-3 p-6 pt-0 justify-end">
                        <button type="button" data-confirm-cancel class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">${escapeHtml(cancelText)}</button>
                        <button type="button" data-confirm-ok class="px-4 py-2 rounded-lg text-white transition-colors ${escapeHtml(confirmClass)}">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;

            const finish = (result) => {
                modal.remove();
                document.removeEventListener('keydown', onKeyDown);
                resolve(result);
            };
            const onKeyDown = (event) => {
                if (event.key === 'Escape') {
                    finish(false);
                }
            };

            modal.querySelector('[data-confirm-ok]').addEventListener('click', () => finish(true));
            modal.querySelector('[data-confirm-cancel]').addEventListener('click', () => finish(false));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    finish(false);
                }
            });
            document.addEventListener('keydown', onKeyDown);
            document.body.appendChild(modal);
        });
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-confirm-form]');
        if (!form || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        const confirmed = await window.showConfirmDialog({
            message: form.dataset.confirmMessage,
            confirmText: form.dataset.confirmText,
            cancelText: form.dataset.cancelText,
            confirmClass: form.dataset.confirmClass
        });

        if (confirmed) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
})();
