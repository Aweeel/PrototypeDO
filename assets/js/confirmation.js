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
            modal.className = 'global-confirm-overlay fixed inset-0 z-[1200] flex items-center justify-center bg-black/50 p-4';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.innerHTML = `
                <style>
                    #globalConfirmDialog {
                        -webkit-text-size-adjust: 100%;
                        text-size-adjust: 100%;
                    }

                    #globalConfirmDialog .global-confirm-panel {
                        box-sizing: border-box;
                        width: min(398px, calc(100vw - 32px));
                        color-scheme: dark;
                        font-family: inherit;
                    }

                    #globalConfirmDialog .global-confirm-message {
                        margin: 0;
                        font-size: 16px;
                        line-height: 1.5;
                    }

                    #globalConfirmDialog .global-confirm-actions {
                        display: flex;
                        justify-content: flex-end;
                        gap: 12px;
                    }

                    #globalConfirmDialog button {
                        -webkit-appearance: none;
                        appearance: none;
                        box-sizing: border-box;
                        min-height: 42px;
                        font: inherit;
                        font-size: 14px;
                        line-height: 1.25;
                        white-space: nowrap;
                    }
                </style>
                <div class="global-confirm-panel overflow-hidden rounded-lg border border-slate-600 bg-[#111827] text-gray-100 shadow-xl">
                    <div class="p-6">
                        <p class="global-confirm-message font-medium text-gray-100">${escapeHtml(message)}</p>
                    </div>
                    <div class="global-confirm-actions p-6 pt-0">
                        <button type="button" data-confirm-cancel class="appearance-none rounded-lg border border-slate-600 bg-transparent px-4 py-2 text-gray-300 transition-colors hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400" style="-webkit-appearance: none; appearance: none;">${escapeHtml(cancelText)}</button>
                        <button type="button" data-confirm-ok class="appearance-none rounded-lg border border-transparent px-4 py-2 text-white transition-colors ${escapeHtml(confirmClass)} focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400" style="-webkit-appearance: none; appearance: none;">${escapeHtml(confirmText)}</button>
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

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-logout-link]');
        if (!link) {
            return;
        }

        event.preventDefault();
        const confirmed = await window.showConfirmDialog({
            message: 'Are you sure you want to log out?',
            confirmText: 'Log Out',
            confirmClass: 'bg-red-600 hover:bg-red-700'
        });

        if (confirmed) {
            window.location.href = link.href;
        }
    });
})();
