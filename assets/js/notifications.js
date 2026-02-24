/**
 * Unified Notification System
 * Use this for all confirmation, success, and error messages throughout the application
 */

/**
 * Show a notification toast message
 * @param {string} message - The message to display
 * @param {string} type - Type: 'success', 'error', 'info', 'warning'
 * @param {number} duration - How long to show (ms), default 3000
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Determine colors based on type
    let bgClass, borderClass, textClass, iconPath;
    
    switch(type) {
        case 'success':
            bgClass = 'bg-green-500 dark:bg-green-600';
            borderClass = 'border-green-600 dark:border-green-700';
            textClass = 'text-white dark:text-white';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
            break;
        case 'error':
            bgClass = 'bg-red-500 dark:bg-red-600';
            borderClass = 'border-red-600 dark:border-red-700';
            textClass = 'text-white dark:text-white';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
            duration = 4000; // Errors stay longer
            break;
        case 'warning':
            bgClass = 'bg-yellow-500 dark:bg-yellow-600';
            borderClass = 'border-yellow-600 dark:border-yellow-700';
            textClass = 'text-white dark:text-white';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-10a8 8 0 100 16 8 8 0 000-16z" />';
            break;
        case 'info':
        default:
            bgClass = 'bg-blue-500 dark:bg-blue-600';
            borderClass = 'border-blue-600 dark:border-blue-700';
            textClass = 'text-white dark:text-white';
            iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
            break;
    }
    
    // Create the toast element
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 ${bgClass} border ${borderClass} rounded-lg shadow-lg p-4 flex items-start gap-3 transition-all duration-300 transform translate-x-full`;
    toast.innerHTML = `
        <div class="w-5 h-5 flex-shrink-0 text-white mt-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                ${iconPath}
            </svg>
        </div>
        <span class="${textClass} font-medium text-sm pt-0.5">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    
    // Animate out and remove
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, duration);
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
