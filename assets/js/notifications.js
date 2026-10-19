/**
 * Unified Notification System
 * Use this for all confirmation, success, and error messages throughout the application
 */

/**
 * Show a reusable notification modal
 * @param {string} message - The message to display
 * @param {string} type - Type: 'success', 'error', 'info', 'warning'
 * @param {number} duration - How long to show (ms), default 3000
 */
function showNotificationModal(message, type = 'info', duration = 3000) {
    const existingModal = document.getElementById('globalNotificationModal');
    if (existingModal) {
        existingModal.remove();
    }

    let title, iconPath, iconClass;
    
    switch(type) {
        case 'success':
            title = 'Success';
            iconClass = 'text-green-600 dark:text-green-400';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
            break;
        case 'error':
            title = 'Action not completed';
            iconClass = 'text-red-600 dark:text-red-400';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
            duration = 4000; // Errors stay longer
            break;
        case 'warning':
            title = 'Warning';
            iconClass = 'text-yellow-600 dark:text-yellow-400';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-10a8 8 0 100 16 8 8 0 000-16z" />';
            break;
        case 'info':
        default:
            title = 'Information';
            iconClass = 'text-blue-600 dark:text-blue-400';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
            break;
    }
    
    const modal = document.createElement('div');
    modal.id = 'globalNotificationModal';
    modal.className = 'fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 p-4';
    modal.setAttribute('role', 'alertdialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
        <div class="w-full max-w-md rounded-lg bg-white dark:bg-[#111827] shadow-xl border border-gray-200 dark:border-slate-700 p-6">
            <div class="flex items-start gap-4">
                <div class="w-6 h-6 flex-shrink-0 ${iconClass}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                ${iconPath}
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">${title}</h2>
                    <p data-notification-message class="mt-1 text-sm text-gray-600 dark:text-gray-300"></p>
                </div>
                <button type="button" data-notification-close class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Close notification">&times;</button>
            </div>
        </div>
    `;

    modal.querySelector('[data-notification-message]').textContent = message;
    const handleEscape = (event) => {
        if (event.key === 'Escape') close();
    };
    const close = () => {
        modal.remove();
        document.removeEventListener('keydown', handleEscape);
    };
    modal.querySelector('[data-notification-close]').addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', handleEscape);
    document.body.appendChild(modal);
    window.setTimeout(close, duration);
}

function showNotification(message, type = 'info', duration = 3000) {
    showNotificationModal(message, type, duration);
}

/**
 * Alias functions for convenience
 */
function showSuccessNotification(message) {
    showNotification(message, 'success', 3000);
}

function showErrorNotification(message) {
    showNotification(message, 'error', 4000);
}

function showWarningNotification(message) {
    showNotification(message, 'warning', 3500);
}

function showInfoNotification(message) {
    showNotification(message, 'info', 3000);
}

// Legacy aliases for backward compatibility
function showSuccessToast(message) {
    showSuccessNotification(message);
}

function showErrorToast(message) {
    showErrorNotification(message);
}
