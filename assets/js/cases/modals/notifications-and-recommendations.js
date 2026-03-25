// ====== Toast Notifications ======

function showLoadingToast(message) {
  const existingToast = document.getElementById("loadingToast");
  if (existingToast) existingToast.remove();

  const toast = document.createElement("div");
  toast.id = "loadingToast";
  toast.className =
    "fixed top-4 right-4 z-[1000] bg-white dark:bg-slate-800 rounded-lg shadow-lg p-4 flex items-center gap-3 border border-gray-200 dark:border-slate-700";
  toast.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-900 dark:text-gray-100">${message}</span>
    `;
  document.body.appendChild(toast);
}

function closeLoadingToast() {
  const toast = document.getElementById("loadingToast");
  if (toast) toast.remove();
}

function showSuccessToast(message) {
  // Delegate to unified notification system
  showNotification(message, 'success');
}

function showErrorToast(message) {
  // Delegate to unified notification system
  showNotification(message, 'error');
}

function showNotification(message, type = 'success', onClose = null) {
  const existingToast = document.querySelector('[data-notification-toast]');
  if (existingToast) existingToast.remove();

  const toast = document.createElement('div');
  toast.setAttribute('data-notification-toast', 'true');

  let bgColor = 'bg-green-500';
  let iconSVG = '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';

  if (type === 'error') {
    bgColor = 'bg-red-500';
    iconSVG = '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
  }

  toast.className = 'fixed top-4 right-4 z-[1000] ' + bgColor + ' text-white rounded-lg shadow-lg p-4 flex items-center gap-3 max-w-md';
  toast.innerHTML = iconSVG + '<span class="text-sm font-medium">' + message + '</span>';

  document.body.appendChild(toast);

  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
      if (onClose) onClose();
    }
  }, 2500);
}

/**
 * Fetch recommended sanction based on student's offense history
 * @param {string} studentId - The student ID
 * @param {string} caseType - The type of offense (e.g., "Cheating", "Smoking")
 * @param {string} severity - Either "Minor" or "Major"
 * @returns {Promise<Object>} Recommendation object
 */
async function fetchRecommendedSanction(studentId, caseType, severity) {
  try {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'getRecommendedSanction');
    formData.append('studentId', studentId);
    formData.append('caseType', caseType);
    formData.append('severity', severity);

    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    
    if (data.success) {
      return data.recommendation;
    } else {
      console.error('Failed to fetch recommendation:', data.error);
      return null;
    }
  } catch (error) {
    console.error('Error fetching recommended sanction:', error);
    return null;
  }
}

// Tooltip control variables
let handbookTooltipTimeout = null;

/**
 * Show the handbook recommendation tooltip
 */
function showHandbookTooltip() {
  clearTimeout(handbookTooltipTimeout);
  const tooltip = document.getElementById('handbookTooltip');
  if (tooltip) {
    tooltip.classList.remove('hidden');
  }
}

/**
 * Keep the handbook tooltip visible (when hovering over it)
 */
function keepHandbookTooltip() {
  clearTimeout(handbookTooltipTimeout);
}

/**
 * Schedule hiding the handbook tooltip with a small delay
 */
function scheduleHideHandbookTooltip() {
  handbookTooltipTimeout = setTimeout(() => {
    const tooltip = document.getElementById('handbookTooltip');
    if (tooltip) {
      tooltip.classList.add('hidden');
    }
  }, 200); // 200ms delay to allow moving mouse to tooltip
}

