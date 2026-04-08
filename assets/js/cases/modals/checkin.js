// ====== UTILITY FUNCTIONS FOR TIME CONVERSION ======

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

function getSanctionTypeConfig(sanctionType = 'corrective') {
  if (sanctionType === 'suspension') {
    return {
      keyword: 'suspension from class',
      modalTitle: 'Suspension from Class Progress',
      emptyLabel: 'No suspension-from-class sanctions found',
      emptyHint: 'Apply a "Suspension from Class" sanction with duration days to enable suspension tracking.',
      completeTitle: 'Suspension Progress Complete (100%)',
      progressTitle: 'Suspension Progress In Progress'
    };
  }

  return {
    keyword: 'corrective',
    modalTitle: 'Community Service Check-In',
    emptyLabel: 'No time-based sanctions found',
    emptyHint: 'Apply a sanction with a duration (e.g., Corrective Reinforcement) to enable check-in tracking.',
    completeTitle: 'Community Service Check-In Complete (100%)',
    progressTitle: 'Community Service Check-In In Progress'
  };
}

function getCaseCheckInIconButton(caseId, sanctionType = 'corrective') {
  return document.querySelector(
    `[data-case-checkin-icon="true"][data-case-id="${caseId}"][data-case-checkin-type="${sanctionType}"]`
  ) || document.querySelector(`[data-case-checkin-icon="true"][data-case-id="${caseId}"]`);
}

function findSanctionByType(sanctions, sanctionType = 'corrective') {
  const { keyword } = getSanctionTypeConfig(sanctionType);
  return (sanctions || []).find((s) => String(s?.sanction_name || '').toLowerCase().includes(keyword));
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
        <button type="button" onclick="this.closest('[data-suspension-start-modal=\"true\"]').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Suspension progress will auto-complete per school day (Monday to Saturday).</p>
      <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Day</label>
      <input type="date" id="suspensionStartDateInput" value="${initialDate}" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100" />
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" onclick="this.closest('[data-suspension-start-modal=\"true\"]').remove()" class="px-3 py-2 text-sm bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-slate-600">Cancel</button>
        <button type="button" onclick="saveSuspensionStartDate('${caseId}', ${caseSanctionId})" class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
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

    if (modal) {
      modal.remove();
    }

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

function getCheckInFooterActionsHTML(caseData, caseId, completedDays, totalDays) {
  const isCaseResolved = String(caseData?.status || '').toLowerCase() === 'resolved';
  const allDaysCompleted = totalDays > 0 && completedDays >= totalDays;

  return `
    ${allDaysCompleted && !isCaseResolved ? `
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

    const totalDays = parseInt(suspensionSanction.duration_days || 0, 10);
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
  const totalDays = sanction.duration_days;
  const days = sanction.days;
  
  // Calculate completed days and active day
  const completedDays = Object.values(days).filter(d => d.check_in_time && d.check_out_time).length;
  const progressPercent = Math.round((completedDays / totalDays) * 100);
  updateCaseCheckInIcon(caseId, completedDays >= totalDays && totalDays > 0, sanctionType);

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
  const heading = modal.querySelector('.flex.items-center.justify-between');
  if (heading) {
    const completedSpan = heading.querySelector('span:nth-child(2)');
    if (completedSpan && completedSpan.textContent.includes('/')) {
      completedSpan.textContent = completedDays + ' / ' + totalDays + ' days completed';
    }
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
    footerActions.innerHTML = getCheckInFooterActionsHTML(caseData, caseId, completedDays, totalDays);
  }
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
    const sanctionName = String(s?.sanction_name || '').toLowerCase();
    return sanctionName.includes(sanctionConfig.keyword) && s.duration_days && parseInt(s.duration_days) > 0;
  });

  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';

  if (sanctionType === 'suspension' && selectedTypeSanctions.length > 0) {
    const activeSanction = findSanctionByType(selectedTypeSanctions, 'suspension') || selectedTypeSanctions[0];
    const totalDays = parseInt(activeSanction.duration_days || 0, 10);
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
      const totalDays = parseInt(activeSanction.duration_days);
      const sanctionName = activeSanction.sanction_name || 'Community Service';
      const completedDays = totalDays <= 1 ? 0 : Math.floor((totalDays - 1) / 2);
      const activeDayNum = completedDays + 1;
      const progressPercent = Math.round((completedDays / totalDays) * 100);

      let dayCardsHTML = getDayCardsHTML(totalDays, completedDays, activeDayNum, caseId, caseData.student, sanctionName, activeSanction.case_sanction_id || '');
      
      renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedDays, dayCardsHTML, progressPercent, sanctionType);
    } else {
      // Use real data
      const sanction = findSanctionByType(result.sanctions, sanctionType);
      const activeSanction = findSanctionByType(selectedTypeSanctions, sanctionType) || selectedTypeSanctions[0];
      if (!sanction || !activeSanction) {
        modal.remove();
        showNotification('No matching sanction data found for tracking', 'warning');
        return;
      }
      const totalDays = sanction.duration_days;
      const sanctionName = sanction.sanction_name || 'Community Service';
      
      // Calculate completed days and active day
      const days = sanction.days;
      const completedDays = Object.values(days).filter(d => d.check_in_time && d.check_out_time).length;
      const activeDayNum = completedDays + 1;
      const progressPercent = Math.round((completedDays / totalDays) * 100);

      let dayCardsHTML = getDayCardsHTMLFromData(days, caseId, caseData.student, sanctionName, sanction.case_sanction_id);
      
      renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedDays, dayCardsHTML, progressPercent, sanctionType);
    }
  }

  document.body.appendChild(modal);
}

function getDayCardsHTML(totalDays, completedDays, activeDayNum, caseId, studentName, sanctionName, caseSanctionId = '') {
  let dayCardsHTML = '';
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

function getDayCardsHTMLFromData(days, caseId, studentName, sanctionName, caseSanctionId) {
  let dayCardsHTML = '';
  const totalDays = Object.keys(days).length;
  
  // Calculate active day (first incomplete)
  let activeDayNum = totalDays + 1;
  for (let day = 1; day <= totalDays; day++) {
    const dayData = days[day] || {};
    if (!(dayData.check_in_time && dayData.check_out_time)) {
      activeDayNum = day;
      break;
    }
  }
  
  for (let day = 1; day <= totalDays; day++) {
    const dayData = days[day] || {};
    const isCompleted = dayData.check_in_time && dayData.check_out_time;
    const hasCheckIn = dayData.check_in_time !== null;
    const isActiveDay = day === activeDayNum;
    
    if (isCompleted) {
      const inTime = convertTo12Hour(new Date(dayData.check_in_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
      const outTime = convertTo12Hour(new Date(dayData.check_out_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
      const inTimeHHMM = new Date(dayData.check_in_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
      const outTimeHHMM = new Date(dayData.check_out_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
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
      const inTime = convertTo12Hour(new Date(dayData.check_in_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));
      const inTimeHHMM = new Date(dayData.check_in_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
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

function renderCheckInModal(modal, caseId, caseData, activeSanction, totalDays, completedDays, dayCardsHTML, progressPercent, sanctionType = 'corrective') {
  const sanctionName = activeSanction.sanction_name || 'Community Service';
  const sanctionConfig = getSanctionTypeConfig(sanctionType);
  const isSuspension = sanctionType === 'suspension';
  const suspensionStartDate = activeSanction.applied_date || '';
  const suspensionStartDateDisplay = formatDateForDisplay(suspensionStartDate);
  const suspensionStartDateInput = formatDateForInput(suspensionStartDate);
  
  modal.setAttribute('data-checkin-modal', 'true');
  modal.setAttribute('data-case-id', caseId);
  modal.setAttribute('data-sanction-type', sanctionType);
  
  modal.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-lg p-5 max-h-[90vh] flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between mb-4 flex-shrink-0">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">${sanctionConfig.modalTitle}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Case ${caseId}</p>
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
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${totalDays}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-0.5">days total</p>
          </div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="mb-4 flex-shrink-0">
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</span>
          <span class="text-xs font-bold text-gray-900 dark:text-gray-100">${completedDays} / ${totalDays} days completed</span>
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
        ${getCheckInFooterActionsHTML(caseData, caseId, completedDays, totalDays)}
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
