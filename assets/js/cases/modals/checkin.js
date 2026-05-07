// ====== UTILITY FUNCTIONS FOR TIME CONVERSION ======

const HOURS_PER_DAY = 8;

function convertTo24Hour(timeStr) {
  if (!timeStr || timeStr === 'Not recorded yet' || timeStr === 'Awaiting checkout') return '';
  
  // If already in 24-hour format (HH:MM)
  if (/^\d{2}:\d{2}$/.test(timeStr)) {
    return timeStr;
  }
  
  // Convert from 12-hour AM/PM format
  const match = timeStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
  if (match) {
    let hours = parseInt(match[1], 10);
    const minutes = match[2];
    const meridiem = match[3].toUpperCase();
    
    if (meridiem === 'PM' && hours !== 12) {
      hours += 12;
    } else if (meridiem === 'AM' && hours === 12) {
      hours = 0;
    }
    
    return `${String(hours).padStart(2, '0')}:${minutes}`;
  }
  
  return '';
}

function convertTo12Hour(timeStr) {
  if (!timeStr || timeStr === 'Not recorded yet' || timeStr === 'Awaiting checkout') return timeStr;
  
  const match = timeStr.match(/^(\d{2}):(\d{2})/);
  if (match) {
    let hours = parseInt(match[1], 10);
    const minutes = match[2];
    const meridiem = hours >= 12 ? 'PM' : 'AM';
    
    if (hours > 12) {
      hours -= 12;
    } else if (hours === 0) {
      hours = 12;
    }
    
    return `${hours}:${minutes} ${meridiem}`;
  }
  
  return timeStr;
}

function parseSqlDateTimeValue(value) {
  if (!value) return null;
  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value;
  }

  const directDate = new Date(value);
  if (!Number.isNaN(directDate.getTime())) {
    return directDate;
  }

  const text = String(value).trim();
  const match = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:\.(\d{1,3}))?)?/);
  if (!match) return null;

  const year = parseInt(match[1], 10);
  const month = parseInt(match[2], 10) - 1;
  const day = parseInt(match[3], 10);
  const hour = parseInt(match[4] || '0', 10);
  const minute = parseInt(match[5] || '0', 10);
  const second = parseInt(match[6] || '0', 10);
  const millis = parseInt((match[7] || '0').padEnd(3, '0'), 10);

  const parsed = new Date(year, month, day, hour, minute, second, millis);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatSqlDateTimeToTime(value, fallback = '') {
  const parsed = parseSqlDateTimeValue(value);
  if (!parsed) return fallback;
  return parsed.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function formatSqlDateTimeTo24Hour(value, fallback = '') {
  const parsed = parseSqlDateTimeValue(value);
  if (!parsed) return fallback;
  return parsed.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
}

// ====== DEADLINE STATUS FUNCTIONS ======

function getDeadlineStatus(deadline) {
  if (!deadline) return { status: 'no-deadline', label: 'No deadline' };
  
  const now = new Date();
  const deadlineTime = new Date(deadline);
  
  // Clear time portion for date comparison
  now.setHours(0, 0, 0, 0);
  deadlineTime.setHours(0, 0, 0, 0);
  
  const timeDiff = deadlineTime - now;
  const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
  
  if (daysDiff < 0) {
    return { 
      status: 'overdue', 
      label: 'OVERDUE',
      daysOverdue: Math.abs(daysDiff)
    };
  } else if (daysDiff === 0) {
    return { status: 'due-today', label: 'Due Today' };
  } else if (daysDiff <= 2) {
    return { status: 'due-soon', label: `${daysDiff} day${daysDiff > 1 ? 's' : ''} left` };
  } else {
    return { status: 'on-track', label: `${daysDiff} days remaining` };
  }
}

function getDeadlineStatusColor(status) {
  switch(status) {
    case 'overdue': return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200';
    case 'due-today': return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200';
    case 'due-soon': return 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800 text-orange-800 dark:text-orange-200';
    case 'on-track': return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200';
    default: return 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200';
  }
}

function getDeadlineStatusBorderClass(status) {
  switch(status) {
    case 'overdue': return 'border-red-200 dark:border-red-800';
    case 'due-today': return 'border-yellow-200 dark:border-yellow-800';
    case 'due-soon': return 'border-orange-200 dark:border-orange-800';
    case 'on-track': return 'border-green-200 dark:border-green-800';
    default: return 'border-gray-200 dark:border-gray-800';
  }
}

function getSanctionTypeConfig(sanctionType = 'corrective') {
  if (sanctionType === 'suspension') {
    return {
      keywords: ['suspension from class'],
      modalTitle: 'Suspension from Class Progress',
      emptyLabel: 'No suspension-from-class sanctions found',
      emptyHint: 'Apply a "Suspension from Class" sanction with duration days to enable suspension tracking.',
      completeTitle: 'Suspension Progress Complete (100%)',
      progressTitle: 'Suspension Progress In Progress'
    };
  }

  return {
    keywords: ['corrective', 'community service'],
    modalTitle: 'Community Service Check-In',
    emptyLabel: 'No time-based sanctions found',
    emptyHint: 'Apply a sanction with a duration (e.g., Corrective Reinforcement) to enable check-in tracking.',
    completeTitle: 'Community Service Check-In Complete (100%)',
    progressTitle: 'Community Service Check-In In Progress'
  };
}

function matchesSanctionTypeByName(sanctionName = '', sanctionType = 'corrective') {
  const normalizedName = String(sanctionName || '').toLowerCase();
  const { keywords } = getSanctionTypeConfig(sanctionType);
  return (keywords || []).some((keyword) => normalizedName.includes(keyword));
}

function getEffectiveDurationDays(sanction, sanctionType = 'corrective') {
  const storedDuration = parseInt(sanction?.duration_days || 0, 10);
  if (storedDuration > 0) return storedDuration;

  const sanctionName = String(sanction?.sanction_name || '').toLowerCase();

  const rangeMatch = sanctionName.match(/(\d+)\s*-\s*(\d+)\s*days?/i);
  if (rangeMatch) {
    const minDays = parseInt(rangeMatch[1], 10);
    if (minDays > 0) return minDays;
  }

  const singleMatch = sanctionName.match(/(\d+)\s*days?/i);
  if (singleMatch) {
    const explicitDays = parseInt(singleMatch[1], 10);
    if (explicitDays > 0) return explicitDays;
  }

  if (sanctionName.includes('corrective reinforcement') || sanctionName.includes('suspension from class')) {
    return 3;
  }

  if (sanctionType === 'suspension' && matchesSanctionTypeByName(sanctionName, 'suspension')) {
    return 3;
  }

  if (sanctionType === 'corrective' && matchesSanctionTypeByName(sanctionName, 'corrective')) {
    return 3;
  }

  return 0;
}

function getSanctionExtraHours(sanction) {
  const extraHours = parseInt(sanction?.duration_extra_hours || 0, 10);
  return Number.isFinite(extraHours) && extraHours > 0 ? extraHours : 0;
}

function getRequiredCorrectiveHours(sanction) {
  const baseDays = getEffectiveDurationDays(sanction, 'corrective');
  const extraHours = getSanctionExtraHours(sanction);
  if (baseDays <= 0) return 0;
  if (extraHours > 0) {
    return Math.max(0, ((baseDays - 1) * HOURS_PER_DAY) + extraHours);
  }
  return Math.max(0, baseDays * HOURS_PER_DAY);
}

function calculateCompletedHoursFromDays(days = {}) {
  let totalHours = 0;

  Object.values(days || {}).forEach((dayData) => {
    if (!dayData?.check_in_time || !dayData?.check_out_time) {
      return;
    }

    const checkIn = new Date(dayData.check_in_time);
    const checkOut = new Date(dayData.check_out_time);
    if (Number.isNaN(checkIn.getTime()) || Number.isNaN(checkOut.getTime())) {
      return;
    }

    const durationHours = (checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60);
    if (durationHours <= 0) {
      return;
    }

    totalHours += Math.min(HOURS_PER_DAY, durationHours);
  });

  return Math.round(totalHours * 100) / 100;
}

function calculateRemainingCapacityHours(deadline, now = new Date()) {
  if (!deadline) return 0;

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const deadlineDate = new Date(deadline);
  if (Number.isNaN(deadlineDate.getTime())) return 0;
  const deadlineStart = new Date(deadlineDate.getFullYear(), deadlineDate.getMonth(), deadlineDate.getDate());

  if (deadlineStart < today) {
    return 0;
  }

  const diffDays = Math.floor((deadlineStart.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
  const daysIncludingToday = diffDays + 1;
  return Math.max(0, daysIncludingToday * HOURS_PER_DAY);
}

function getCorrectiveCapacityStatus(deadline, requiredHours, completedHours) {
  const remainingHours = Math.max(0, requiredHours - completedHours);
  const maxPossibleHours = calculateRemainingCapacityHours(deadline);

  return {
    remainingHours,
    maxPossibleHours,
    shouldShowIntervention: remainingHours > 0 && maxPossibleHours < remainingHours
  };
}

function formatHourValue(hours) {
  const numeric = Number(hours || 0);
  if (!Number.isFinite(numeric)) return '0';
  return Number.isInteger(numeric) ? String(numeric) : numeric.toFixed(2);
}

function getCaseCheckInIconButton(caseId, sanctionType = 'corrective') {
  return document.querySelector(
    `[data-case-checkin-icon="true"][data-case-id="${caseId}"][data-case-checkin-type="${sanctionType}"]`
  ) || document.querySelector(`[data-case-checkin-icon="true"][data-case-id="${caseId}"]`);
}

function getCaseCheckInAlertBadge(caseId, sanctionType = 'corrective') {
  const iconBtn = getCaseCheckInIconButton(caseId, sanctionType);
  if (!iconBtn) return null;
  return iconBtn.querySelector('[data-case-checkin-alert="true"]');
}

function updateCaseCheckInAlert(caseId, hasAlert, sanctionType = 'corrective') {
  const iconBtn = getCaseCheckInIconButton(caseId, sanctionType);
  if (!iconBtn) return;

  const existingBadge = getCaseCheckInAlertBadge(caseId, sanctionType);
  if (hasAlert) {
    if (existingBadge) return;
    const badge = document.createElement('span');
    badge.setAttribute('data-case-checkin-alert', 'true');
    badge.className = 'absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-600 text-white text-[10px] leading-none flex items-center justify-center font-bold';
    badge.textContent = '!';
    if (!iconBtn.classList.contains('relative')) {
      iconBtn.classList.add('relative');
    }
    iconBtn.appendChild(badge);
    return;
  }

  if (existingBadge) {
    existingBadge.remove();
  }
}

function formatSubmissionFileSize(sizeBytes) {
  const size = Number(sizeBytes || 0);
  if (!Number.isFinite(size) || size <= 0) return 'Unknown size';
  if (size < 1024) return `${size} B`;
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
  return `${(size / (1024 * 1024)).toFixed(2)} MB`;
}

function escapeSubmissionText(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function findSanctionByType(sanctions, sanctionType = 'corrective') {
  return (sanctions || []).find((s) => matchesSanctionTypeByName(s?.sanction_name || '', sanctionType));
}

function calculateElapsedSuspensionDays(appliedDate, totalDays) {
  if (!appliedDate || !totalDays || totalDays <= 0) return 0;

  const start = new Date(appliedDate);
  if (Number.isNaN(start.getTime())) return 0;

  const startDate = new Date(start.getFullYear(), start.getMonth(), start.getDate());
  const today = new Date();
  const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
  const yesterdayDate = new Date(todayDate);
  yesterdayDate.setDate(yesterdayDate.getDate() - 1);

  // The current school day is treated as "in progress", not yet completed.
  if (startDate > yesterdayDate) {
    return 0;
  }

  let elapsedInclusive = 0;
  const current = new Date(startDate);
  while (current <= yesterdayDate) {
    const day = current.getDay(); // Sunday=0 ... Saturday=6
    if (day !== 0) {
      elapsedInclusive += 1;
    }
    current.setDate(current.getDate() + 1);
  }

  return Math.min(totalDays, elapsedInclusive);
}

function formatDateForInput(dateValue) {
  if (!dateValue) return '';
  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return '';

  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function formatDateForDisplay(dateValue) {
  if (!dateValue) return 'Not set';
  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return 'Not set';
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function openSetSuspensionStartDateModal(caseId, caseSanctionId, currentStartDate = '') {
  document.querySelectorAll('[data-suspension-start-modal="true"]').forEach((el) => el.remove());

  const initialDate = formatDateForInput(currentStartDate) || formatDateForInput(new Date());
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70] p-4';
  modal.setAttribute('data-suspension-start-modal', 'true');

  modal.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Set Initial Day</h3>
        <button type="button" onclick="closeSuspensionStartDateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Suspension progress will auto-complete per school day (Monday to Saturday).</p>
      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Day</label>
      <input type="date" id="suspensionStartDateInput" value="${initialDate}" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100" />
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" onclick="closeSuspensionStartDateModal()" class="px-3 py-2 text-sm bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-slate-600">Cancel</button>
        <button type="button" onclick="saveSuspensionStartDate('${caseId}', ${caseSanctionId})" class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
}

function closeSuspensionStartDateModal() {
  document.querySelectorAll('[data-suspension-start-modal="true"]').forEach((el) => el.remove());
}

async function saveSuspensionStartDate(caseId, caseSanctionId) {
  const modal = document.querySelector('[data-suspension-start-modal="true"]');
  const input = modal?.querySelector('#suspensionStartDateInput');
  const startDate = input?.value?.trim();

  if (!startDate) {
    showNotification('Please select a start date', 'warning');
    return;
  }

  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=setSuspensionStartDate&caseSanctionId=${caseSanctionId}&startDate=${encodeURIComponent(startDate)}`
    });

    const result = await response.json();
    if (!result.success) {
      showNotification(result.error || 'Failed to set initial day', 'error');
      return;
    }

    closeSuspensionStartDateModal();

    showNotification('Initial day updated', 'success');
    refreshCheckInModalContent(caseId, 'suspension');
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error('Set suspension start date error:', error);
  }
}

function getSuspensionDayCardsHTML(totalDays, completedDays) {
  let dayCardsHTML = '';
  const activeDay = completedDays < totalDays ? completedDays + 1 : totalDays;

  for (let day = 1; day <= totalDays; day++) {
    if (day <= completedDays) {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
          <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day}</p>
            <p class="text-xs text-green-700 dark:text-green-300 mt-0.5">Completed suspension day</p>
          </div>
          <span class="text-xs font-semibold text-green-600 dark:text-green-400 flex-shrink-0">Done</span>
        </div>`;
    } else if (day === activeDay) {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-400 dark:border-blue-500">
          <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day}</p>
            <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">Current suspension day</p>
          </div>
          <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 flex-shrink-0">In Progress</span>
        </div>`;
    } else {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-lg border border-gray-200 dark:border-slate-600 opacity-55">
          <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Day ${day}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upcoming suspension day</p>
          </div>
          <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">-</span>
        </div>`;
    }
  }

  return dayCardsHTML;
}

function getActiveCheckInModalSanctionType(caseId) {
  const modal = document.querySelector(`[data-checkin-modal="true"][data-case-id="${caseId}"]`);
  return modal?.getAttribute('data-sanction-type') || 'corrective';
}

function updateCaseCheckInIcon(caseId, isCompleted, sanctionType = 'corrective') {
  const iconBtn = getCaseCheckInIconButton(caseId, sanctionType);
  if (!iconBtn) return;

  const { completeTitle, progressTitle } = getSanctionTypeConfig(sanctionType);

  iconBtn.classList.remove(
    'text-orange-500',
    'hover:text-orange-600',
    'dark:text-orange-400',
    'dark:hover:text-orange-300',
    'text-green-600',
    'hover:text-green-700',
    'dark:text-green-400',
    'dark:hover:text-green-300'
  );

  if (isCompleted) {
    iconBtn.classList.add('text-green-600', 'hover:text-green-700', 'dark:text-green-400', 'dark:hover:text-green-300');
    iconBtn.title = completeTitle;
  } else {
    iconBtn.classList.add('text-orange-500', 'hover:text-orange-600', 'dark:text-orange-400', 'dark:hover:text-orange-300');
    iconBtn.title = progressTitle;
  }
}

function getCheckInFooterActionsHTML(caseData, caseId, completedValue, totalValue) {
  const isCaseResolved = String(caseData?.status || '').toLowerCase() === 'resolved';
  const isFullyCompleted = totalValue > 0 && completedValue >= totalValue;

  return `
    ${isFullyCompleted && !isCaseResolved ? `
      <button
        onclick="closeModal(this); confirmMarkResolved('${caseId}')"
        class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
        Mark Case as Resolved
      </button>
    ` : ''}
    <button onclick="closeModal(this)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
      Close
    </button>
  `;
}

function getDeadlineStatusSectionHTML(activeSanction, {
  isSuspension = false,
  completedDays = 0,
  totalDays = 0,
  completedHours = 0,
  totalHours = 0
} = {}) {
  const deadline = activeSanction?.deadline || null;
  if (!deadline) {
    return '';
  }

  const deadlineStatusInfo = getDeadlineStatus(deadline);
  const correctiveCapacity = !isSuspension
    ? getCorrectiveCapacityStatus(deadline, totalHours, completedHours)
    : { shouldShowIntervention: false, remainingHours: 0, maxPossibleHours: 0 };

  const parsedMaxDeadlineDay = parseInt(activeSanction?.max_deadline_day, 10);
  const maxDeadlineDay = Number.isFinite(parsedMaxDeadlineDay) && parsedMaxDeadlineDay > 0 ? parsedMaxDeadlineDay : null;
  const maxRecordedDay = parseInt(activeSanction?.max_recorded_day || 0, 10) || 0;
  const deadlineWindowExhausted = !isSuspension && maxDeadlineDay !== null && maxRecordedDay >= maxDeadlineDay && completedHours < totalHours;
  const effectiveStatus = deadlineWindowExhausted ? 'overdue' : deadlineStatusInfo.status;
  const deadlineStatusColor = getDeadlineStatusColor(effectiveStatus);

  const shouldShowIntervention =
    (deadlineStatusInfo.status === 'overdue' && ((isSuspension && completedDays < totalDays) || (!isSuspension && completedHours < totalHours))) ||
    (!isSuspension && (correctiveCapacity.shouldShowIntervention || deadlineWindowExhausted));

  const deadlineDate = parseSqlDateTimeValue(deadline);
  const deadlineDateDisplay = deadlineDate ? deadlineDate.toLocaleDateString() : '';

  return `
    <div class="border ${deadlineStatusColor} rounded-lg p-3 mb-4 flex-shrink-0" data-deadline-status-container="true">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold">Deadline Status: <span class="font-bold">${deadlineStatusInfo.status === 'overdue' ? 'OVERDUE' : deadlineStatusInfo.label}</span></p>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-600 dark:text-gray-400">${deadlineDateDisplay}</p>
        </div>
      </div>
      ${shouldShowIntervention ? `
        <div class="mt-2 pt-2 border-t ${getDeadlineStatusBorderClass(effectiveStatus)}">
          <p class="text-xs mb-2">
            ${deadlineStatusInfo.status === 'overdue'
              ? 'Deadline has passed without completion'
              : deadlineWindowExhausted
                ? `Maximum allowable service days reached (Day ${maxDeadlineDay}) and hours are still incomplete.`
                : `Not enough remaining time: ${formatHourValue(correctiveCapacity.maxPossibleHours)}h max possible before deadline, ${formatHourValue(correctiveCapacity.remainingHours)}h still needed.`}
          </p>
          <div class="flex flex-wrap gap-2 mt-2">
            <button type="button" onclick="openDeadlineActionModal(${activeSanction.case_sanction_id}, 'extend')" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium prevent-double">Extend Deadline</button>
            ${!isSuspension ? `<button type="button" onclick="openDeadlineActionModal(${activeSanction.case_sanction_id}, 'increase')" class="px-3 py-1.5 text-xs bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors font-medium prevent-double">Add Required Hours</button>` : ''}
          </div>
        </div>
      ` : ''}
    </div>
  `;
}

function getCommunityServiceSubmissionsButtonHTML(caseId, caseSanctionId, submissions = [], newCount = 0) {
  const totalSubmissions = Array.isArray(submissions) ? submissions.length : 0;
  if (totalSubmissions <= 0) {
    return ''; 
  }

  return `
    <button
      type="button"
      onclick="openCommunityServiceSubmissionsModal('${caseId}', ${caseSanctionId})"
      class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors font-medium relative prevent-double"
      title="View student-submitted community service files">
      <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
      </svg>
      <span>Portfolio</span>
      ${newCount > 0 ? `<span data-portfolio-new-count="true" class="ml-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold">${newCount}</span>` : ''}
    </button>
  `;
}

async function markCommunityServiceSubmissionsViewed(caseId, caseSanctionId, sanctionType = 'corrective') {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=markCommunityServiceSubmissionsViewed&caseId=${encodeURIComponent(caseId)}&caseSanctionId=${encodeURIComponent(caseSanctionId)}`
    });
    const result = await response.json();
    if (result.success) {
      updateCaseCheckInAlert(caseId, false, sanctionType);
      await refreshCheckInModalContent(caseId, sanctionType);
    }
  } catch (error) {
    console.error('Failed to mark community service submissions viewed:', error);
  }
}

function openCommunityServiceSubmissionsModal(caseId, caseSanctionId, submissionsOverride = null) {
  const checkInModal = document.querySelector('[data-checkin-modal="true"]');
  const sanctionType = checkInModal?.getAttribute('data-sanction-type') || 'corrective';

  let submissions = Array.isArray(submissionsOverride) ? submissionsOverride : [];
  if (submissions.length === 0) {
    if (!checkInModal) return;

    const submissionsRaw = checkInModal?.getAttribute('data-portfolio-submissions') || '[]';
    try {
      submissions = JSON.parse(decodeURIComponent(submissionsRaw));
    } catch (e) {
      submissions = [];
    }
  }

  document.querySelectorAll('[data-portfolio-modal="true"]').forEach((el) => el.remove());

  const overlay = document.createElement('div');
  overlay.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[80] p-4';
  overlay.setAttribute('data-portfolio-modal', 'true');

  const listHtml = submissions.length > 0
    ? submissions.map((item) => {
        const createdAt = item?.created_at
          ? new Date(item.created_at).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
          : 'Unknown date';
        const remarksHtml = item?.remarks
          ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${escapeSubmissionText(item.remarks)}</p>`
          : '';
        const escapedFileName = escapeSubmissionText(item.original_file_name || 'Submitted file');
        const safePath = escapeSubmissionText(item.file_path || '#');
        return `
          <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 last:border-b-0 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">${escapedFileName}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${createdAt} • ${formatSubmissionFileSize(item.file_size_bytes)}</p>
              ${remarksHtml}
            </div>
            <a href="${safePath}" target="_blank" rel="noopener" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-blue-200 dark:border-blue-500/40 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors">View</a>
          </div>
        `;
      }).join('')
    : '<div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400 text-center">No portfolio files submitted yet.</div>';

  overlay.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Community Service Portfolio Submissions</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Case ${caseId}</p>
        </div>
        <button type="button" onclick="closePortfolioSubmissionsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="overflow-y-auto">${listHtml}</div>
    </div>
  `;

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closePortfolioSubmissionsModal();
    }
  });

  document.body.appendChild(overlay);

  const newCountBadge = checkInModal?.querySelector('[data-portfolio-new-count="true"]');
  const hasUnread = !!newCountBadge || Array.isArray(submissionsOverride);
  if (hasUnread) {
    markCommunityServiceSubmissionsViewed(caseId, caseSanctionId || null, sanctionType);
  }
}

function closePortfolioSubmissionsModal() {
  document.querySelectorAll('[data-portfolio-modal="true"]').forEach((el) => el.remove());
}

// Refresh modal content without closing/reopening (prevents flashing)
async function refreshCheckInModalContent(caseId, sanctionType = 'corrective') {
  const modal = document.querySelector('[data-checkin-modal="true"]');
  if (!modal) return;

  const caseData = allCases.find(c => c.id === caseId);
  if (!caseData) return;

  if (sanctionType === 'suspension') {
    const sanctions = await loadAppliedSanctionsForView(caseId);
    const suspensionSanction = findSanctionByType(sanctions, 'suspension');
    if (!suspensionSanction) return;

    try {
      const response = await fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax=1&action=getCheckInHistory&caseId=${caseId}`
      });
      const result = await response.json();
      if (result.success) {
        suspensionSanction.case_portfolio_submissions = Array.isArray(result.case_portfolio_submissions)
          ? result.case_portfolio_submissions
          : [];
        suspensionSanction.new_portfolio_submission_count = parseInt(result.case_new_portfolio_submission_count || 0, 10) || 0;
      }
    } catch (error) {
      console.error('Failed to load portfolio submissions for suspension refresh:', error);
      suspensionSanction.case_portfolio_submissions = Array.isArray(suspensionSanction.case_portfolio_submissions)
        ? suspensionSanction.case_portfolio_submissions
        : [];
      suspensionSanction.new_portfolio_submission_count = parseInt(suspensionSanction.new_portfolio_submission_count || 0, 10) || 0;
    }

    const totalDays = getEffectiveDurationDays(suspensionSanction, 'suspension');
    const completedDays = calculateElapsedSuspensionDays(suspensionSanction.applied_date, totalDays);
    const progressPercent = totalDays > 0 ? Math.round((completedDays / totalDays) * 100) : 0;
    const dayCardsHTML = getSuspensionDayCardsHTML(totalDays, completedDays);

    updateCaseCheckInIcon(caseId, completedDays >= totalDays && totalDays > 0, 'suspension');

    renderCheckInModal(
      modal,
      caseId,
      caseData,
      suspensionSanction,
      totalDays,
      completedDays,
      dayCardsHTML,
      progressPercent,
      'suspension'
    );

    return;
  }

  // Load check-in history
  const response = await fetch('/PrototypeDO/modules/do/cases.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `ajax=1&action=getCheckInHistory&caseId=${caseId}`  
  });
  const result = await response.json();
  
  if (!result.success || !result.sanctions.length) {
    return; // No data, don't update
  }

  // Get the sanction data for the selected check-in type.
  const sanction = findSanctionByType(result.sanctions, sanctionType);
  if (!sanction) {
    return;
  }
  sanction.max_deadline_day = sanction.max_day_by_deadline_window ?? null;
  sanction.max_recorded_day = parseInt(sanction.max_recorded_day || 0, 10) || 0;
  const casePortfolioSubmissions = Array.isArray(result.case_portfolio_submissions) ? result.case_portfolio_submissions : [];
  const caseNewPortfolioSubmissionCount = parseInt(result.case_new_portfolio_submission_count || 0, 10) || 0;
  sanction.case_portfolio_submissions = casePortfolioSubmissions;
  sanction.new_portfolio_submission_count = caseNewPortfolioSubmissionCount;
  const totalDays = getEffectiveDurationDays(sanction, sanctionType);
  const days = sanction.days;

  const completedDays = Object.values(days).filter(d => d.check_in_time && d.check_out_time).length;
  const completedHours = calculateCompletedHoursFromDays(days);
  const totalHours = getRequiredCorrectiveHours(sanction);
  const progressPercent = totalHours > 0 ? Math.min(100, Math.round((completedHours / totalHours) * 100)) : 0;
  updateCaseCheckInIcon(caseId, completedHours >= totalHours && totalHours > 0, sanctionType);

  // Update progress bar
  const progressBar = modal.querySelector('[data-progress-bar]');
  if (progressBar) {
    progressBar.style.width = progressPercent + '%';
  }

  const progressText = modal.querySelector('[data-progress-text]');
  if (progressText) {
    progressText.textContent = progressPercent + '% done';
  }

  // Update completed count
  const completedSpan = modal.querySelector('[data-progress-count]');
  if (completedSpan) {
    completedSpan.textContent = `${formatHourValue(completedHours)} / ${formatHourValue(totalHours)} hours completed`;
  }

  // Refresh deadline/intervention section without recreating the whole modal.
  const deadlineSlot = modal.querySelector('[data-deadline-status-slot="true"]');
  if (deadlineSlot) {
    deadlineSlot.innerHTML = getDeadlineStatusSectionHTML(sanction, {
      isSuspension: sanctionType === 'suspension',
      completedDays,
      totalDays,
      completedHours,
      totalHours
    });
  }

  // Refresh day cards
  const dayCardsContainer = modal.querySelector('.space-y-2.overflow-y-auto');
  if (dayCardsContainer) {
    // Get student and sanction names
    const studentName = caseData.student;
    const sanctionName = sanction.sanction_name || 'Community Service';
    const caseSanctionId = sanction.case_sanction_id;

    // Rebuild day cards HTML
    const dayCardsHTML = getDayCardsHTMLFromData(days, caseId, studentName, sanctionName, caseSanctionId);
    dayCardsContainer.innerHTML = dayCardsHTML;
  }

  // Keep footer actions in sync with completion state.
  const footerActions = modal.querySelector('[data-checkin-footer-actions]');
  if (footerActions) {
    footerActions.innerHTML = getCheckInFooterActionsHTML(caseData, caseId, completedHours, totalHours);
  }

  modal.setAttribute('data-portfolio-submissions', encodeURIComponent(JSON.stringify(casePortfolioSubmissions)));
}

// ====== COMMUNITY SERVICE CHECK-IN ======

async function openCheckInModal(caseId, sanctionType = 'corrective') {
  // Prevent stacked/duplicate check-in modals when refreshing after actions.
  document.querySelectorAll('[data-checkin-modal="true"]').forEach(existingModal => existingModal.remove());

  const caseData = allCases.find(c => c.id === caseId);
  if (!caseData) return;

  const sanctionConfig = getSanctionTypeConfig(sanctionType);

  // Load sanctions for this case and filter to selected type with duration_days > 0.
  const sanctions = await loadAppliedSanctionsForView(caseId);
  const selectedTypeSanctions = sanctions.filter((s) => {
    const matchesType = matchesSanctionTypeByName(s?.sanction_name || '', sanctionType);
    const totalDays = getEffectiveDurationDays(s, sanctionType);
    return matchesType && totalDays > 0;
  });

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';

  if (sanctionType === 'suspension' && selectedTypeSanctions.length > 0) {
    const activeSanction = findSanctionByType(selectedTypeSanctions, 'suspension') || selectedTypeSanctions[0];

    try {
      const response = await fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax=1&action=getCheckInHistory&caseId=${caseId}`
      });
      const result = await response.json();
      if (result.success) {
        activeSanction.case_portfolio_submissions = Array.isArray(result.case_portfolio_submissions)
          ? result.case_portfolio_submissions
          : [];
        activeSanction.new_portfolio_submission_count = parseInt(result.case_new_portfolio_submission_count || 0, 10) || 0;
      }
    } catch (error) {
      console.error('Failed to load portfolio submissions for suspension modal:', error);
      activeSanction.case_portfolio_submissions = Array.isArray(activeSanction.case_portfolio_submissions)
        ? activeSanction.case_portfolio_submissions
        : [];
      activeSanction.new_portfolio_submission_count = parseInt(activeSanction.new_portfolio_submission_count || 0, 10) || 0;
    }

    const totalDays = getEffectiveDurationDays(activeSanction, 'suspension');
    const completedDays = calculateElapsedSuspensionDays(activeSanction.applied_date, totalDays);
    const progressPercent = totalDays > 0 ? Math.round((completedDays / totalDays) * 100) : 0;
    const dayCardsHTML = getSuspensionDayCardsHTML(totalDays, completedDays);

    updateCaseCheckInIcon(caseId, completedDays >= totalDays && totalDays > 0, 'suspension');
    renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedDays, dayCardsHTML, progressPercent, sanctionType);

    document.body.appendChild(modal);
    return;
  }

  if (selectedTypeSanctions.length === 0) {
    modal.innerHTML = `
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">${sanctionConfig.modalTitle}</h3>
        </div>
        <div class="text-center py-8">
          <div class="w-14 h-14 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">${sanctionConfig.emptyLabel}</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 px-4">${sanctionConfig.emptyHint}</p>
        </div>
        <div class="flex justify-end mt-2">
          <button onclick="closeModal(this)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">Close</button>
        </div>
      </div>
    `;
  } else {
    // Load real check-in data
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=getCheckInHistory&caseId=${caseId}`
    });
    const result = await response.json();
    
    if (!result.success || !result.sanctions.length) {
      // Fallback to UI demo
      const activeSanction = findSanctionByType(selectedTypeSanctions, sanctionType) || selectedTypeSanctions[0];
      const totalDays = getEffectiveDurationDays(activeSanction, sanctionType);
      const sanctionName = activeSanction.sanction_name || 'Community Service';
      const completedDays = totalDays <= 1 ? 0 : Math.floor((totalDays - 1) / 2);
      const activeDayNum = completedDays + 1;
      const totalHours = getRequiredCorrectiveHours(activeSanction);
      const completedHours = Math.min(totalHours, completedDays * HOURS_PER_DAY);
      const progressPercent = totalHours > 0 ? Math.min(100, Math.round((completedHours / totalHours) * 100)) : 0;

      let dayCardsHTML = getDayCardsHTML(totalDays, completedDays, activeDayNum, caseId, caseData.student, sanctionName, activeSanction.case_sanction_id || '');
      
      renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedHours, dayCardsHTML, progressPercent, sanctionType);
    } else {
      // Use real data
      const sanction = findSanctionByType(result.sanctions, sanctionType);
      const activeSanction = findSanctionByType(selectedTypeSanctions, sanctionType) || selectedTypeSanctions[0];
      if (!sanction || !activeSanction) {
        modal.remove();
        showNotification('No matching sanction data found for tracking', 'warning');
        return;
      }
      const casePortfolioSubmissions = Array.isArray(result.case_portfolio_submissions) ? result.case_portfolio_submissions : [];
      const caseNewPortfolioSubmissionCount = parseInt(result.case_new_portfolio_submission_count || 0, 10) || 0;
      activeSanction.case_portfolio_submissions = casePortfolioSubmissions;
      activeSanction.new_portfolio_submission_count = caseNewPortfolioSubmissionCount;
      activeSanction.max_deadline_day = sanction.max_day_by_deadline_window ?? null;
      activeSanction.max_recorded_day = parseInt(sanction.max_recorded_day || 0, 10) || 0;
      const totalDays = getEffectiveDurationDays(sanction, sanctionType);
      const sanctionName = sanction.sanction_name || 'Community Service';

      const days = sanction.days;
      const completedDays = Object.values(days).filter(d => d.check_in_time && d.check_out_time).length;
      const completedHours = calculateCompletedHoursFromDays(days);
      const totalHours = getRequiredCorrectiveHours(sanction);
      const progressPercent = totalHours > 0 ? Math.min(100, Math.round((completedHours / totalHours) * 100)) : 0;

      let dayCardsHTML = getDayCardsHTMLFromData(days, caseId, caseData.student, sanctionName, sanction.case_sanction_id, totalDays);

      renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedHours, dayCardsHTML, progressPercent, sanctionType);
    }
  }

  document.body.appendChild(modal);
}

function getDayCardsHTML(totalDays, completedDays, activeDayNum, caseId, studentName, sanctionName, caseSanctionId = '') {
  let dayCardsHTML = '';
  const displayDays = Math.max(1, Math.min(totalDays, activeDayNum || 1));

  for (let day = 1; day <= displayDays; day++) {
    if (day <= completedDays) {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
          <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-green-600 dark:text-green-400">In:</span> 8:00 AM
              </span>
              <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-green-600 dark:text-green-400">Out:</span> 5:00 PM
              </span>
            </div>
          </div>
          <span class="text-xs font-semibold text-green-600 dark:text-green-400 flex-shrink-0">Completed</span>
        </div>`;
    } else if (day === activeDayNum) {
      const escapedStudent = studentName.replace(/"/g, '&quot;');
      const escapedSanction = sanctionName.replace(/"/g, '&quot;');
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-400 dark:border-blue-500">
          <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
              Day ${day}
              <span class="text-xs font-normal text-blue-600 dark:text-blue-400 ml-1">— Today</span>
            </p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">In:</span>
                <span class="italic text-gray-400 dark:text-gray-500">Not recorded yet</span>
              </span>
              <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">Out:</span>
                <span class="italic text-gray-400 dark:text-gray-500">Not recorded yet</span>
              </span>
            </div>
          </div>
          <button
            data-day="${day}"
            data-caseid="${caseId}"
            data-casesanctionid="${caseSanctionId}"
            data-student="${escapedStudent}"
            data-sanction="${escapedSanction}"
            onclick="showEditMenu(this)"
            class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors group relative"
            style="position: relative;">
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
        </div>`;
    } else {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-lg border border-gray-200 dark:border-slate-600 opacity-55">
          <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Day ${day}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upcoming</p>
          </div>
          <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">—</span>
        </div>`;
    }
  }
  return dayCardsHTML;
}

function getDayCardsHTMLFromData(days, caseId, studentName, sanctionName, caseSanctionId, totalDaysLimit = null) {
  let dayCardsHTML = '';
  const totalDays = Object.keys(days).length;
  const displayDays = totalDays > 0 ? totalDays : Math.max(1, totalDaysLimit || 1);
  
  // Calculate active day (first incomplete)
  let activeDayNum = totalDays + 1;
  for (let day = 1; day <= totalDays; day++) {
    const dayData = days[day] || {};
    if (!(dayData.check_in_time && dayData.check_out_time)) {
      activeDayNum = day;
      break;
    }
  }

  for (let day = 1; day <= displayDays; day++) {
    const dayData = days[day] || {};
    const isCompleted = Boolean(dayData.check_in_time && dayData.check_out_time);
    const hasCheckIn = Boolean(dayData.check_in_time);
    const isActiveDay = day === activeDayNum;
    
    if (isCompleted) {
      const inTime = formatSqlDateTimeToTime(dayData.check_in_time, 'Not recorded yet');
      const outTime = formatSqlDateTimeToTime(dayData.check_out_time, 'Awaiting checkout');
      const inTimeHHMM = formatSqlDateTimeTo24Hour(dayData.check_in_time, '');
      const outTimeHHMM = formatSqlDateTimeTo24Hour(dayData.check_out_time, '');
      const escapedStudent = studentName.replace(/"/g, '&quot;');
      const escapedSanction = sanctionName.replace(/"/g, '&quot;');
      dayCardsHTML += `
        <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800" data-day-card data-day="${day}" data-student-name="${escapedStudent}" data-sanction-name="${escapedSanction}">
          <button onclick="showEditMenu(this, ${day}, '${caseId}', '${escapedStudent}', '${escapedSanction}', ${caseSanctionId}, '${inTimeHHMM}', '${outTimeHHMM}', true, true)" class="flex-shrink-0 p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-green-600 dark:text-green-400">In:</span>
                <span data-check-in-display>${inTime}</span>
              </span>
              <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-green-600 dark:text-green-400">Out:</span>
                <span data-check-out-display>${outTime}</span>
              </span>
            </div>
          </div>
          <span class="text-xs font-semibold text-green-600 dark:text-green-400 flex-shrink-0">Completed</span>
        </div>`;
    } else if (hasCheckIn) {
      const inTime = formatSqlDateTimeToTime(dayData.check_in_time, 'Not recorded yet');
      const inTimeHHMM = formatSqlDateTimeTo24Hour(dayData.check_in_time, '');
      const escapedStudent = studentName.replace(/"/g, '&quot;');
      const escapedSanction = sanctionName.replace(/"/g, '&quot;');
      dayCardsHTML += `
        <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-400 dark:border-blue-500" data-day-card data-day="${day}" data-student-name="${escapedStudent}" data-sanction-name="${escapedSanction}">
          <button onclick="showEditMenu(this, ${day}, '${caseId}', '${escapedStudent}', '${escapedSanction}', ${caseSanctionId}, '${inTimeHHMM}', '', false, false)" class="edit-menu-btn flex-shrink-0 p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">In:</span> <span data-check-in-display>${inTime}</span>
              </span>
              <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">Out:</span>
                <span data-check-out-display class="italic text-gray-400 dark:text-gray-500">Awaiting checkout</span>
              </span>
            </div>
          </div>
          <div class="flex-shrink-0 flex items-center gap-2">
            <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleCheckInOut(${day}, '${caseId}', ${caseSanctionId}, true); return false;" data-toggle-btn class="px-4 py-1.5 bg-red-600 dark:bg-red-700 text-white text-xs font-semibold rounded hover:bg-red-700 dark:hover:bg-red-600 transition-colors cursor-pointer">CHECK OUT</button>
          </div>
        </div>`;
    } else if (isActiveDay) {
      const escapedStudent = studentName.replace(/"/g, '&quot;');
      const escapedSanction = sanctionName.replace(/"/g, '&quot;');
      dayCardsHTML += `
        <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-400 dark:border-blue-500" data-day-card data-day="${day}" data-student-name="${escapedStudent}" data-sanction-name="${escapedSanction}">
          <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Day ${day} <span class="text-xs font-normal text-blue-600 dark:text-blue-400">— Today</span></p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">In:</span>
                <span data-check-in-display class="italic text-gray-400 dark:text-gray-500">Not recorded yet</span>
              </span>
              <span class="text-xs text-gray-300 dark:text-gray-600">|</span>
              <span class="text-xs text-gray-500">
                <span class="font-medium text-blue-500">Out:</span>
                <span data-check-out-display class="italic text-gray-400 dark:text-gray-500">Not recorded yet</span>
              </span>
            </div>
          </div>
          <div class="flex-shrink-0 flex items-center gap-2">
            <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleCheckInOut(${day}, '${caseId}', ${caseSanctionId}, false); return false;" data-toggle-btn class="px-4 py-1.5 bg-blue-600 dark:bg-blue-700 text-white text-xs font-semibold rounded hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors cursor-pointer">CHECK IN</button>
          </div>
        </div>`;
    } else {
      dayCardsHTML += `
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700/40 rounded-lg border border-gray-200 dark:border-slate-600 opacity-50">
          <div class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">${day}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Day ${day}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upcoming</p>
          </div>
          <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">—</span>
        </div>`;
    }
  }
  return dayCardsHTML;
}

function renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedMetric, dayCardsHTML, progressPercent, sanctionType = 'corrective') {
  const sanctionName = activeSanction.sanction_name || 'Community Service';
  const sanctionConfig = getSanctionTypeConfig(sanctionType);
  const isSuspension = sanctionType === 'suspension';
  const totalHours = isSuspension ? 0 : getRequiredCorrectiveHours(activeSanction);
  const completedHours = isSuspension ? 0 : (Number.isFinite(completedMetric) ? completedMetric : 0);
  const completedDays = isSuspension ? (Number.isFinite(completedMetric) ? completedMetric : 0) : 0;
  const suspensionStartDate = activeSanction.applied_date || '';
  const suspensionStartDateDisplay = formatDateForDisplay(suspensionStartDate);
  const suspensionStartDateInput = formatDateForInput(suspensionStartDate);
  const portfolioSubmissions = Array.isArray(activeSanction.case_portfolio_submissions)
    ? activeSanction.case_portfolio_submissions
    : (Array.isArray(activeSanction.portfolio_submissions) ? activeSanction.portfolio_submissions : []);
  const newPortfolioSubmissionCount = parseInt(activeSanction.new_portfolio_submission_count || 0, 10) || 0;
  
  modal.setAttribute('data-checkin-modal', 'true');
  modal.setAttribute('data-case-id', caseId);
  modal.setAttribute('data-sanction-type', sanctionType);
  modal.setAttribute('data-portfolio-submissions', encodeURIComponent(JSON.stringify(portfolioSubmissions)));
  updateCaseCheckInAlert(caseId, newPortfolioSubmissionCount > 0, sanctionType);
  
  modal.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-lg p-5 max-h-[90vh] flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between mb-4 flex-shrink-0">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">${sanctionConfig.modalTitle}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Case ${caseId}</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          ${getCommunityServiceSubmissionsButtonHTML(caseId, activeSanction.case_sanction_id, portfolioSubmissions, newPortfolioSubmissionCount)}
          <button onclick="exportCheckInCSV('${caseId}', '${caseData.student.replace(/'/g, "\\'")}', '${activeSanction.sanction_name.replace(/'/g, "\\'")}')" title="Export check-in report as CSV file" class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors font-medium">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
          </button>
          <button onclick="printCheckInReport('${caseId}', '${caseData.student.replace(/'/g, "\\'")}', '${activeSanction.sanction_name.replace(/'/g, "\\'")}'${sanctionType === 'suspension' ? ", 'suspension'" : ""})" title="Print/save check-in report as PDF" class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Student + Sanction Info Card -->
      <div class="bg-gray-50 dark:bg-slate-700/60 rounded-lg p-3 mb-4 flex-shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-gray-300 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">${caseData.student}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">${sanctionName}</p>
            ${isSuspension ? `
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-xs text-gray-600 dark:text-gray-300">Initial day: <strong>${suspensionStartDateDisplay}</strong></span>
                <button type="button" onclick="openSetSuspensionStartDateModal('${caseId}', ${activeSanction.case_sanction_id}, '${suspensionStartDateInput}')" class="px-2 py-0.5 text-[11px] font-medium border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">Set Initial Day</button>
              </div>
            ` : ''}
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${isSuspension ? totalDays : totalHours}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-0.5">${isSuspension ? 'days total' : 'hours total'}</p>
          </div>
        </div>
      </div>

      <!-- Deadline Status Badge -->
      <div data-deadline-status-slot="true">${getDeadlineStatusSectionHTML(activeSanction, { isSuspension, completedDays, totalDays, completedHours, totalHours })}</div>

      <!-- Progress Bar -->
      <div class="mb-4 flex-shrink-0">
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</span>
          <span class="text-xs font-bold text-gray-900 dark:text-gray-100" data-progress-count>${isSuspension ? `${completedDays} / ${totalDays} days completed` : `${formatHourValue(completedHours)} / ${formatHourValue(totalHours)} hours completed`}</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-slate-600 rounded-full h-2.5">
          <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${progressPercent}%" data-progress-bar></div>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
          <span data-progress-text>${progressPercent}% done</span>
        </p>
        <div hidden data-total-days>${totalDays}</div>
      </div>

      <!-- Day Cards (scrollable) -->
      <div class="space-y-2 overflow-y-auto flex-1 pr-1">
        ${dayCardsHTML}
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-2 mt-4 flex-shrink-0" data-checkin-footer-actions>
        ${getCheckInFooterActionsHTML(caseData, caseId, isSuspension ? completedDays : completedHours, isSuspension ? totalDays : totalHours)}
      </div>

    </div>
  `;
}

// ====== TIME CORRECTION MODAL ======

function showTimeCorrectionModal(dayNumber, caseId, studentName, sanctionName, caseSanctionId, checkInTime = '', checkOutTime = '') {
  // Remove only existing time correction modal; keep the check-in modal intact.
  document.querySelectorAll('[data-time-correction-modal="true"]').forEach(el => el.remove());

  const hasExistingCheckOut = !!checkOutTime;
  
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[60] p-4';
  modal.setAttribute('data-case-id', caseId);
  modal.setAttribute('data-time-correction-modal', 'true');
  
  const now = new Date();
  const currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
  
  modal.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-sm p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit Time</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Day ${dayNumber} &bull; Check In & Check Out</p>
        </div>
        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Check In Time -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Check In</label>
        <input type="time" id="checkInInput" class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400" value="${convertTo24Hour(checkInTime)}" />
      </div>

      <!-- Check Out Time -->
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Check Out</label>
        <input type="time" id="checkOutInput" ${hasExistingCheckOut ? '' : 'disabled'} class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 ${hasExistingCheckOut ? '' : 'opacity-60 cursor-not-allowed'}" value="${convertTo24Hour(checkOutTime)}" />
      </div>

      <!-- Actions -->
      <div class="flex gap-3">
        <button onclick="saveBothTimesModal(this.closest('.fixed'), '${caseId}', ${dayNumber}, ${caseSanctionId})"
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Save
        </button>
        <button onclick="this.closest('.fixed').remove()"
          class="flex-1 flex items-center justify-center px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
          Cancel
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  
  // Focus on check-in input
  setTimeout(() => {
    const input = modal.querySelector('#checkInInput');
    if (input) input.focus();
  }, 100);
}

async function saveBothTimesModal(modal, caseId, dayNumber, caseSanctionId) {
  const checkInInput = modal.querySelector('#checkInInput');
  const checkOutInput = modal.querySelector('#checkOutInput');
  const checkInValue = checkInInput.value.trim();
  const checkOutValue = checkOutInput.disabled ? '' : checkOutInput.value.trim();
  
  // At least one time must be entered
  if (!checkInValue && !checkOutValue) {
    showNotification('Please enter at least one time', 'error');
    return;
  }

  // Validate time format HH:MM if provided (time input gives HH:MM format)
  if (checkInValue && !/^\d{2}:\d{2}$/.test(checkInValue)) {
    showNotification('Invalid check-in time format', 'error');
    return;
  }
  
  if (checkOutValue && !/^\d{2}:\d{2}$/.test(checkOutValue)) {
    showNotification('Invalid check-out time format', 'error');
    return;
  }

  try {
    // Save check-in time if provided
    if (checkInValue) {
      const response = await fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax=1&action=correctTime&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}&timeType=check_in&correctedTime=${checkInValue}`
      });
      
      const result = await response.json();
      if (!result.success) {
        showNotification(result.error || 'Failed to correct check-in time', 'error');
        return;
      }
    }
    
    // Save check-out time if provided
    if (checkOutValue) {
      const response = await fetch('/PrototypeDO/modules/do/cases.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax=1&action=correctTime&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}&timeType=check_out&correctedTime=${checkOutValue}`
      });
      
      const result = await response.json();
      if (!result.success) {
        showNotification(result.error || 'Failed to correct check-out time', 'error');
        return;
      }
    }
    
    modal.remove();
    showNotification('Times saved successfully', 'success');
    
    // Refresh in place so the check-in modal stays open.
    setTimeout(() => {
      refreshCheckInModalContent(caseId, getActiveCheckInModalSanctionType(caseId));
    }, 100);
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error('Time correction error:', error);
  }
}

// ====== EDIT MENU (modal) ======

function showEditMenu(buttonEl, dayNumber, caseId, studentName, sanctionName, caseSanctionId, checkInTime, checkOutTime, hasCheckOut, isCompleted) {
  // Remove any existing modal
  const existingOverlay = document.querySelector('[data-edit-modal-overlay]');
  if (existingOverlay) {
    existingOverlay.remove();
  }
  
  // Create modal overlay
  const overlay = document.createElement('div');
  overlay.setAttribute('data-edit-modal-overlay', 'true');
  overlay.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[100] p-4';
  
  // Create modal container
  const modal = document.createElement('div');
  modal.setAttribute('data-edit-modal', 'true');
  modal.className = 'bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 rounded-xl shadow-2xl w-full max-w-sm p-6';
  
  let modalHTML = '<div class="space-y-2">';
  modalHTML += `<h3 class="text-lg font-semibold mb-3">Manage Day ${dayNumber}</h3>`;
  
  // Edit Time option
  if (checkInTime || checkOutTime) {
    modalHTML += `<button onclick="showTimeCorrectionModal(${dayNumber}, '${caseId}', '${studentName}', '${sanctionName}', ${caseSanctionId}, '${checkInTime}', '${checkOutTime}'); document.querySelector('[data-edit-modal-overlay]').remove();" class="w-full px-4 py-3 text-left text-sm rounded-lg border border-gray-200 dark:border-slate-600 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit Time</button>`;
  }
  
  // Revert options (only Revert Check In)
  if (isCompleted) {
    // Both times exist - can revert check in
    modalHTML += `<button onclick="revertTime(${dayNumber}, 'check_in', ${caseSanctionId}, '${caseId}'); document.querySelector('[data-edit-modal-overlay]').remove();" class="w-full px-4 py-3 text-left text-sm rounded-lg border border-red-200 dark:border-red-900/50 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Revert Check In</button>`;
  } else if (checkInTime) {
    // Only check-in time exists
    modalHTML += `<button onclick="revertTime(${dayNumber}, 'check_in', ${caseSanctionId}, '${caseId}'); document.querySelector('[data-edit-modal-overlay]').remove();" class="w-full px-4 py-3 text-left text-sm rounded-lg border border-red-200 dark:border-red-900/50 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Revert Check In</button>`;
  }
  
  // Close button
  modalHTML += `<button onclick="document.querySelector('[data-edit-modal-overlay]').remove();" class="w-full mt-2 px-4 py-2.5 text-sm font-medium border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Close</button>`;
  
  modalHTML += '</div>';
  modal.innerHTML = modalHTML;
  
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
  
  // Close on overlay click
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      overlay.remove();
    }
  });
}

// Show confirmation for cascade revert
function showRevertConfirmation(dayNumber, subsequentDays, onConfirm, onCancel) {
  const overlay = document.createElement('div');
  overlay.setAttribute('data-confirm-overlay', 'true');
  overlay.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[102] p-4';
  
  const modal = document.createElement('div');
  modal.className = 'bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 rounded-xl shadow-2xl w-full max-w-md p-6';
  
  const hasSubsequentDays = subsequentDays.length > 0;
  let daysText = '';
  if (hasSubsequentDays) {
    daysText = subsequentDays.length === 1 ? `day ${subsequentDays[0]}` : `days ${subsequentDays.join(', ')}`;
  }
  
  let html = `
    <div class="flex gap-3 mb-4">
      <svg class="w-6 h-6 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2m0-14a9 9 0 110 18 9 9 0 010-18zm0 2a7 7 0 100 14 7 7 0 000-14z"/>
      </svg>
      <div>
        <h3 class="text-lg font-semibold mb-1">Revert Day ${dayNumber}?</h3>
        <p class="text-sm text-red-600 dark:text-red-400">${hasSubsequentDays ? `Warning: Reverting will also clear ${daysText}` : 'Warning: This will clear the selected day and cannot be undone'}</p>
      </div>
    </div>
    
    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
      ${hasSubsequentDays ? `Day ${dayNumber} and the following in-process/completed day(s) ${daysText} will be reverted.` : `Day ${dayNumber} will be reverted.`} This cannot be undone.
    </p>
    
    <div class="flex gap-3">
      <button data-action="confirm" class="flex-1 px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
        Revert All
      </button>
      <button data-action="cancel" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
        Cancel
      </button>
    </div>
  `;
  
  modal.innerHTML = html;
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
  
  // Add event listeners for buttons
  const confirmBtn = modal.querySelector('[data-action="confirm"]');
  const cancelBtn = modal.querySelector('[data-action="cancel"]');
  
  confirmBtn.addEventListener('click', () => {
    overlay.remove();
    onConfirm();
  });
  
  cancelBtn.addEventListener('click', () => {
    overlay.remove();
    onCancel();
  });
  
  // Close on overlay click
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      overlay.remove();
      onCancel();
    }
  });
}

// Revert check-in or check-out time
async function revertTime(dayNumber, timeType, caseSanctionId, caseId) {
  // Check for subsequently completed or in-process days
  const allDayCards = document.querySelectorAll('[data-day-card]');
  const subsequentDays = [];
  
  for (let card of allDayCards) {
    const dayNum = parseInt(card.getAttribute('data-day'));
    if (dayNum > dayNumber) {
      const inDisplay = card.querySelector('[data-check-in-display]');
      const outDisplay = card.querySelector('[data-check-out-display]');
      const inText = inDisplay ? inDisplay.textContent : '';
      const outText = outDisplay ? outDisplay.textContent : '';
      
      // Check if this day has any check-in (completed or in-process)
      if (inText && inText !== 'Not recorded yet') {
        subsequentDays.push(dayNum);
      }
    }
  }
  
  // Always show confirmation before any revert.
  showRevertConfirmation(
    dayNumber,
    subsequentDays,
    async () => {
      if (subsequentDays.length > 0) {
        // Confirmed: revert this day and all subsequent days with data
        console.log('Confirming cascade revert for days:', dayNumber, subsequentDays);
        await performRevert(dayNumber, timeType, caseSanctionId, caseId, false);
        for (let day of subsequentDays) {
          // Revert both check_in and check_out for subsequent days
          console.log('Reverting cascade day:', day);
          await performRevert(day, 'check_in', caseSanctionId, caseId, false);
          await performRevert(day, 'check_out', caseSanctionId, caseId, false);
        }
        // Show final success message
        showNotification('Day ' + dayNumber + ' and subsequent days reverted', 'success');

        // Close edit menu and refresh modal
        const editMenuOverlay = document.querySelector('[data-edit-modal-overlay]');
        if (editMenuOverlay) {
          editMenuOverlay.remove();
        }

        // Refresh the modal content (prevents flashing)
        setTimeout(() => {
          refreshCheckInModalContent(caseId, getActiveCheckInModalSanctionType(caseId));
        }, 100);
      } else {
        // Confirmed: revert only the selected day
        await performRevert(dayNumber, timeType, caseSanctionId, caseId, true);
      }
    },
    () => {
      // Cancelled
      showNotification('Revert cancelled', 'info');
    }
  );
}

// Helper function to perform the actual revert
async function performRevert(dayNumber, timeType, caseSanctionId, caseId, updateUI = true) {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=revertTime&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}&timeType=${timeType}`
    });
    
    const result = await response.json();
    
    if (result.success) {
      // If updateUI is true, refresh the modal content without closing it
      if (updateUI) {
        showNotification('Day reverted successfully', 'success');
        // Close the edit menu
        const editMenuOverlay = document.querySelector('[data-edit-modal-overlay]');
        if (editMenuOverlay) {
          editMenuOverlay.remove();
        }
        // Refresh the modal content (prevents flashing)
        setTimeout(() => {
          refreshCheckInModalContent(caseId, getActiveCheckInModalSanctionType(caseId));
        }, 100);
      }
    } else {
      showNotification(result.error || 'Failed to revert time', 'error');
    }
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error('Revert time error:', error);
  }
}

// Toggle check-in/check-out (single unified button)
async function toggleCheckInOut(dayNumber, caseId, caseSanctionId, isCheckedIn) {
  const action = isCheckedIn ? 'manualCheckOut' : 'manualCheckIn';
  const actionLabel = isCheckedIn ? 'Check Out' : 'Check In';
  
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=${action}&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}`
    });
    
    if (!response.ok) {
      showNotification('Server error: ' + response.status, 'error');
      console.error('Response status:', response.status);
      return;
    }
    
    const text = await response.text();
    
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      showNotification('Invalid response from server', 'error');
      console.error('JSON parse error:', text);
      return;
    }
    
    if (result.success) {
      // Convert time to 12-hour format for display
      const displayTime = convertTo12Hour(result.time);
      showNotification(actionLabel + ' recorded at ' + displayTime, 'success');
      
      // Find the day card and update the button
      const dayCard = document.querySelector(`[data-day-card][data-day="${dayNumber}"]`);
      if (dayCard) {
        if (isCheckedIn) {
          // Just checked out - hide the button or disable it
          const button = dayCard.querySelector('[data-toggle-btn]');
          if (button) {
            button.style.display = 'none';
          }
          // Update out time display
          const outDisplay = dayCard.querySelector('[data-check-out-display]');
          if (outDisplay) {
            outDisplay.textContent = displayTime;
            outDisplay.classList.remove('italic', 'text-gray-400', 'dark:text-gray-500');
            outDisplay.classList.add('text-gray-700', 'dark:text-gray-300');
          }
          // Change card styling to completed
          dayCard.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-2', 'border-blue-400', 'dark:border-blue-500');
          dayCard.classList.add('bg-green-50', 'dark:bg-green-900/20', 'border', 'border-green-200', 'dark:border-green-800');
        } else {
          // Just checked in - change button to check out
          const button = dayCard.querySelector('[data-toggle-btn]');
          if (button) {
            button.textContent = 'CHECK OUT';
            button.classList.remove('bg-blue-600', 'dark:bg-blue-700', 'hover:bg-blue-700', 'dark:hover:bg-blue-600');
            button.classList.add('bg-red-600', 'dark:bg-red-700', 'hover:bg-red-700', 'dark:hover:bg-red-600');
            button.onclick = () => toggleCheckInOut(dayNumber, caseId, caseSanctionId, true);
          }
          // Update in time display
          const inDisplay = dayCard.querySelector('[data-check-in-display]');
          if (inDisplay) {
            inDisplay.textContent = displayTime;
            inDisplay.classList.remove('italic', 'text-gray-400', 'dark:text-gray-500');
            inDisplay.classList.add('text-gray-700', 'dark:text-gray-300');
          }
          // Update out display to "Awaiting checkout"
          const outDisplay = dayCard.querySelector('[data-check-out-display]');
          if (outDisplay) {
            outDisplay.textContent = 'Awaiting checkout';
            outDisplay.classList.add('italic', 'text-gray-400', 'dark:text-gray-500');
          }
        }
      }
      
      // Show edit button automatically
      setTimeout(() => {
        const dayCard = document.querySelector(`[data-day-card][data-day="${dayNumber}"]`);
        if (dayCard && !dayCard.querySelector('.edit-menu-btn')) {
          const inDisplay = dayCard.querySelector('[data-check-in-display]');
          const outDisplay = dayCard.querySelector('[data-check-out-display]');
          const checkInTime = inDisplay ? inDisplay.textContent : '';
          const checkOutTime = outDisplay && !outDisplay.classList.contains('italic') ? outDisplay.textContent : '';
          const studentName = dayCard.getAttribute('data-student-name') || '';
          const sanctionName = dayCard.getAttribute('data-sanction-name') || '';
          const hasCheckOut = !!checkOutTime && checkOutTime !== 'Awaiting checkout';
          const isCompleted = checkInTime && hasCheckOut;
          
          const editButton = document.createElement('button');
          editButton.className = 'edit-menu-btn flex-shrink-0 p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors';
          editButton.title = 'Edit or Revert';
          editButton.type = 'button';
          editButton.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          `;
          editButton.onclick = (e) => {
            e.preventDefault();
            showEditMenu(editButton, dayNumber, caseId, studentName, sanctionName, caseSanctionId, convertTo24Hour(checkInTime), convertTo24Hour(checkOutTime), hasCheckOut, isCompleted);
          };
          dayCard.insertBefore(editButton, dayCard.firstChild);
        }
      }, 100);

      // Rebuild modal from backend state so progress/next active day always stays correct.
      setTimeout(() => {
        refreshCheckInModalContent(caseId, getActiveCheckInModalSanctionType(caseId));
      }, 100);
    } else {
      showNotification(result.error || 'Failed to ' + actionLabel.toLowerCase(), 'error');
      console.error('Backend error:', result);
    }
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error(actionLabel + ' error:', error);
  }
}

// Manual check-in
async function performCheckIn(dayNumber, caseId, caseSanctionId) {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=manualCheckIn&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}`
    });
    
    const result = await response.json();
    
    if (result.success) {
      document.querySelectorAll('[data-edit-menu]').forEach(menu => menu.remove());
      showNotification('Checked in at ' + result.time, 'success');
      
      // Refresh the check-in modal
      setTimeout(() => {
        openCheckInModal(caseId);
      }, 500);
    } else {
      showNotification(result.error || 'Failed to check in', 'error');
    }
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error('Check-in error:', error);
  }
}

// Manual check-out
async function performCheckOut(dayNumber, caseId, caseSanctionId) {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=manualCheckOut&caseSanctionId=${caseSanctionId}&dayNumber=${dayNumber}`
    });
    
    const result = await response.json();
    
    if (result.success) {
      document.querySelectorAll('[data-edit-menu]').forEach(menu => menu.remove());
      showNotification('Checked out at ' + result.time, 'success');
      
      // Refresh the check-in modal
      setTimeout(() => {
        openCheckInModal(caseId);
      }, 500);
    } else {
      showNotification(result.error || 'Failed to check out', 'error');
    }
  } catch (error) {
    showNotification('Error: ' + error.message, 'error');
    console.error('Check-out error:', error);
  }
}

// ====== EXPORT & PRINT FUNCTIONS ======

// Export Check-In data as CSV
async function exportCheckInCSV(caseId, studentName, sanctionName) {
  try {
    const params = new URLSearchParams({ 
      export: 'csv', 
      type: 'checkin',
      caseId: caseId,
      studentName: studentName,
      sanctionName: sanctionName
    });
    const url = `/PrototypeDO/modules/do/cases.php?${params.toString()}`;
    console.log('Exporting Check-In CSV for Case:', caseId);
    window.location.href = url;
  } catch (e) {
    console.error('CSV Export Error:', e);
    showNotification('Failed to export CSV: ' + e.message, 'error');
  }
}

// Print Check-In data as PDF
async function printCheckInReport(caseId, studentName, sanctionName, sanctionType = 'corrective') {
  try {
    // Load the check-in data
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=getCheckInHistory&caseId=${caseId}`
    });
    
    const result = await response.json();
    
    if (!result.success || !result.sanctions.length) {
      showNotification('No check-in data available to print', 'error');
      return;
    }

    const sanction = result.sanctions[0];
    const totalDays = sanction.duration_days;
    const totalHours = getRequiredCorrectiveHours(sanction);
    const days = sanction.days;

    // Create print content
    const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const generatedBy = document.querySelector('[data-user-name]')?.content || window.ADMIN_NAME || 'User';
    const printRoot = document.getElementById('print-root') || createPrintRoot();

    let checkInHTML = buildCheckInPrintHTML(caseId, studentName, sanctionName, totalDays, totalHours, days, today, generatedBy, sanctionType);
    printRoot.innerHTML = checkInHTML;

    console.log('Print preview ready for Check-In Case:', caseId);
    window.print();
  } catch (e) {
    console.error('Print Report Error:', e);
    showNotification('Failed to prepare print: ' + e.message, 'error');
  }
}

// Helper function to create print root if it doesn't exist
function createPrintRoot() {
  const printRoot = document.createElement('div');
  printRoot.id = 'print-root';
  printRoot.style.display = 'none';
  document.body.appendChild(printRoot);
  return printRoot;
}

// Build HTML for print report
function buildCheckInPrintHTML(caseId, studentName, sanctionName, totalDays, totalHours, days, today, generatedBy = 'User', sanctionType = 'corrective') {
  let rowsHTML = '';
  let completedCount = 0;
  let completedHours = 0;
  
  // Determine report title based on sanction type
  const reportTitle = sanctionType === 'suspension' ? 'Suspension from Class Check-In Report' : 'Community Service Check-In Report';

  Object.entries(days).forEach(([dayNum, dayData]) => {
    const day = parseInt(dayNum);
    const inTime = dayData.check_in_time ? formatSqlDateTimeToTime(dayData.check_in_time, '—') : '—';
    const outTime = dayData.check_out_time ? formatSqlDateTimeToTime(dayData.check_out_time, '—') : '—';
    const status = dayData.check_in_time && dayData.check_out_time ? 'Completed' : (dayData.check_in_time ? 'In Progress' : 'Pending');
    
    if (status === 'Completed') {
      completedCount++;
      const inDate = parseSqlDateTimeValue(dayData.check_in_time);
      const outDate = parseSqlDateTimeValue(dayData.check_out_time);
      if (inDate && outDate && !Number.isNaN(inDate.getTime()) && !Number.isNaN(outDate.getTime())) {
        const diffHours = Math.max(0, (outDate.getTime() - inDate.getTime()) / (1000 * 60 * 60));
        completedHours += Math.min(HOURS_PER_DAY, diffHours);
      }
    }

    rowsHTML += `<tr>
      <td class="px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-slate-700">${day}</td>
      <td class="px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-slate-700">${inTime}</td>
      <td class="px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-slate-700">${outTime}</td>
      <td class="px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-slate-700"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ${status === 'Completed' ? 'bg-green-50 text-green-700' : (status === 'In Progress' ? 'bg-blue-50 text-blue-700' : 'bg-gray-50 text-gray-700')}">${status}</span></td>
    </tr>`;
  });

  const progressPercent = sanctionType === 'suspension'
    ? Math.round((completedCount / totalDays) * 100)
    : (totalHours > 0 ? Math.min(100, Math.round((completedHours / totalHours) * 100)) : 0);

  return `
    <div id="print-content" style="font-family: Arial, sans-serif; color: #111827;padding:20px;">
      <div class="flex justify-between items-center border-b-2 border-blue-700 pb-2 mb-4 font-sans" style="display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #1e3a8a;padding-bottom:0.5rem;margin-bottom:1rem;font-family:Arial,sans-serif;">
        <div>
          <span class="font-bold text-blue-700 text-sm" style="font-weight:bold;color:#1e40af;">STI Discipline Office</span><br>
          <span class="text-xs text-gray-600" style="font-size:0.75rem;color:#4b5563;">${reportTitle}</span>
        </div>
        <div class="text-xs text-gray-600" style="font-size:0.75rem;color:#4b5563;text-align:right;">
          <div>Generated: ${today}</div>
          <div>By: ${generatedBy}</div>
        </div>
      </div>

      <div style="border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;margin-bottom:1.5rem;background:#f9fafb;">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;">
          <div>
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin-bottom:0.25rem;">Case ID</p>
            <p style="font-size:0.875rem;font-weight:600;color:#111827;">${caseId}</p>
          </div>
          <div>
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin-bottom:0.25rem;">Student</p>
            <p style="font-size:0.875rem;font-weight:600;color:#111827;">${studentName}</p>
          </div>
          <div>
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin-bottom:0.25rem;">Sanction Type</p>
            <p style="font-size:0.875rem;font-weight:600;color:#111827;">${sanctionName}</p>
          </div>
          <div>
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin-bottom:0.25rem;">Duration</p>
            <p style="font-size:0.875rem;font-weight:600;color:#111827;">${sanctionType === 'suspension' ? `${totalDays} days` : `${totalHours} hours`}</p>
          </div>
        </div>

        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e5e7eb;">
          <div style="margin-bottom:0.5rem;display:flex;justify-content:space-between;">
            <span style="font-size:0.75rem;font-weight:500;color:#6b7280;">Progress</span>
            <span style="font-size:0.875rem;font-weight:600;color:#111827;">${sanctionType === 'suspension' ? `${completedCount} / ${totalDays} days completed` : `${formatHourValue(completedHours)} / ${formatHourValue(totalHours)} hours completed`}</span>
          </div>
          <div style="width:100%;background-color:#e5e7eb;border-radius:9999px;height:0.625rem;overflow:hidden;">
            <div style="background-color:#2563eb;height:0.625rem;border-radius:9999px;width:${progressPercent}%;"></div>
          </div>
          <p style="font-size:0.75rem;color:#9ca3af;margin-top:0.25rem;">${progressPercent}% done</p>
        </div>
      </div>

      <h3 style="font-size:0.875rem;font-weight:bold;color:#1e3a8a;border-left:4px solid #1e3a8a;padding-left:0.625rem;margin:1.25rem 0 0.625rem 0;">Check-In Details</h3>
      <div style="overflow-x:auto;margin-bottom:1rem;">
        <table style="page-break-inside:auto;width:100%;border-collapse:collapse;border:1px solid #e5e7eb;margin-bottom:0.5rem;font-size:0.75rem;">
          <thead>
            <tr>
              <th style="background:#1e3a8a;color:white;padding:0.4rem 0.375rem;font-size:0.5rem;font-weight:600;text-align:left;">Day</th>
              <th style="background:#1e3a8a;color:white;padding:0.4rem 0.375rem;font-size:0.5rem;font-weight:600;text-align:left;">Check In</th>
              <th style="background:#1e3a8a;color:white;padding:0.4rem 0.375rem;font-size:0.5rem;font-weight:600;text-align:left;">Check Out</th>
              <th style="background:#1e3a8a;color:white;padding:0.4rem 0.375rem;font-size:0.5rem;font-weight:600;text-align:left;">Status</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHTML}
          </tbody>
        </table>
      </div>
    </div>

    <style>
      @media print {
        body > * { display: none !important; }
        #print-root, #print-root * { display: block !important; }
      }
    </style>
  `;
}

// ====== DEADLINE EXTENSION AND PENALTY HANDLERS ======

function closeDeadlineActionModal() {
  document.querySelectorAll('[data-deadline-action-modal="true"]').forEach((el) => el.remove());
}

function openDeadlineActionModal(caseSanctionId, actionType) {
  closeDeadlineActionModal();

  const isExtend = actionType === 'extend';
  const title = isExtend ? 'Extend Deadline' : 'Add Required Hours';
  const label = isExtend ? 'Days to extend' : 'Hours to add';
  const buttonText = isExtend ? 'Extend' : 'Add Hours';
  const buttonClass = isExtend ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700';
  const defaultValue = isExtend ? '3' : '8';

  const overlay = document.createElement('div');
  overlay.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[110] p-4';
  overlay.setAttribute('data-deadline-action-modal', 'true');

  overlay.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">${title}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Case Sanction #${caseSanctionId}</p>
        </div>
        <button type="button" onclick="closeDeadlineActionModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">${label}</label>
      <input type="number" id="deadlineActionDaysInput" min="1" max="240" step="1" value="${defaultValue}" class="w-full px-3 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100" />
      <div style="display:flex;justify-content:center;align-items:center;gap:0.75rem;margin-top:1.25rem;">
        <button type="button" onclick="submitDeadlineAction(${caseSanctionId}, '${actionType}')" style="min-width:120px;" class="px-4 py-2.5 text-sm text-white rounded-lg ${buttonClass} transition-colors font-medium">${buttonText}</button>
        <button type="button" onclick="closeDeadlineActionModal()" style="min-width:120px;" class="px-4 py-2.5 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Cancel</button>
      </div>
    </div>
  `;

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeDeadlineActionModal();
    }
  });

  document.body.appendChild(overlay);
  setTimeout(() => overlay.querySelector('#deadlineActionDaysInput')?.focus(), 50);
}

async function submitDeadlineAction(caseSanctionId, actionType) {
  const input = document.getElementById('deadlineActionDaysInput');
  const numericValue = Math.max(1, parseInt(input?.value || '1', 10) || 1);

  if (actionType === 'extend') {
    await handleExtendDeadline(caseSanctionId, Math.min(30, numericValue));
  } else {
    await handleIncreaseHours(caseSanctionId, Math.min(240, numericValue));
  }
}

async function handleExtendDeadline(caseSanctionId, daysToAdd = 7) {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=extendSanctionDeadline&caseSanctionId=${caseSanctionId}&daysToAdd=${daysToAdd}`
    });

    const result = await response.json();
    if (result.success) {
      showNotification(`Deadline extended by ${daysToAdd} day${daysToAdd === 1 ? '' : 's'}`, 'success');
      closeDeadlineActionModal();
      const modal = document.querySelector('[data-checkin-modal="true"]');
      if (modal) {
        const caseId = modal.getAttribute('data-case-id');
        const sanctionType = modal.getAttribute('data-sanction-type') || 'corrective';
        refreshCheckInModalContent(caseId, sanctionType);
      }
    } else {
      showNotification('Error extending deadline: ' + (result.error || 'Unknown error'), 'error');
    }
  } catch (error) {
    console.error('Error extending deadline:', error);
    showNotification('Error extending deadline', 'error');
  }
}

async function handleIncreaseHours(caseSanctionId, additionalHours = 8) {
  try {
    const response = await fetch('/PrototypeDO/modules/do/cases.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ajax=1&action=increaseSanctionDuration&caseSanctionId=${caseSanctionId}&additionalHours=${additionalHours}`
    });

    const result = await response.json();
    if (result.success) {
      showNotification(`Required hours increased by ${additionalHours} hour${additionalHours === 1 ? '' : 's'}`, 'success');
      closeDeadlineActionModal();
      const modal = document.querySelector('[data-checkin-modal="true"]');
      if (modal) {
        const caseId = modal.getAttribute('data-case-id');
        const sanctionType = modal.getAttribute('data-sanction-type') || 'corrective';
        refreshCheckInModalContent(caseId, sanctionType);
      }
    } else {
      showNotification('Error increasing duration: ' + (result.error || 'Unknown error'), 'error');
    }
  } catch (error) {
    console.error('Error increasing duration:', error);
    showNotification('Error increasing duration', 'error');
  }
}