// ====== VIEW CASE MODAL ======

async function viewCase(caseId) {
  const caseData = allCases.find((c) => c.id === caseId);
  if (!caseData) return;

  // Load applied sanctions for this case
  const sanctions = await loadAppliedSanctionsForView(caseId);

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Case Details: ${
                  caseData.id
                }</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student</p>
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 block">${
                              caseData.student
                            }</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">ID: ${caseData.student_id || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Grade/Year</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.grade_level || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Track/Course</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${caseData.track_course || 'N/A'}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status</p>
                        <span class="inline-block px-2.5 py-1 text-xs font-medium rounded ${
                          statusColors[caseData.statusColor]
                        }">${caseData.status}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Offense Type</p>
                        <span class="inline-block px-2.5 py-1 text-xs font-medium rounded ${
                          caseData.severity === "Major"
                            ? "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                            : "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                        }">${caseData.severity}</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${
                      caseData.type
                    }</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Date Reported</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${
                      caseData.date
                    }</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Assigned To</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${
                      caseData.assignedTo
                    }</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Description</p>
                    <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 p-2.5 rounded">
                        ${caseData.description}
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</p>
                    <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 p-2.5 rounded">
                        ${caseData.notes || "No notes available."}
                    </div>
                </div>

                ${
                  caseData.attachments && caseData.attachments.length > 0
                    ? `
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Attachments (${caseData.attachments.length})</p>
                    <div class="grid grid-cols-2 gap-2">
                        ${caseData.attachments
                          .map(
                            (attachment) => `
                            <div class="relative bg-gray-100 dark:bg-slate-700 rounded overflow-hidden border border-gray-300 dark:border-slate-600 cursor-pointer hover:border-blue-400 dark:hover:border-blue-400 transition-colors" onclick="openImageModal('${attachment}')">
                                <img src="${attachment}" alt="Case attachment" class="w-full h-24 object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-20 transition-all flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white opacity-0 hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                                    </svg>
                                </div>
                            </div>
                        `
                          )
                          .join("")}
                    </div>
                </div>
                `
                    : ""
                }

                <!-- Applied Sanctions Section - Collapsible -->
                <div class="pt-3 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="toggleSanctionsView(this)" 
                        class="w-full flex items-center justify-between text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">
                        <span>Applied Sanctions (${sanctions.length})</span>
                        <svg class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="sanctions-content space-y-2" style="display: none;">
                        ${
                          sanctions.length > 0
                            ? sanctions
                                .map(
                                  (s) => `
                            <div class="p-2.5 bg-gray-50 dark:bg-slate-700 rounded text-sm">
                                <p class="font-medium text-gray-900 dark:text-gray-100">${
                                  s.sanction_name
                                }</p>
                                ${
                                  s.duration_days
                                    ? `<p class="text-xs text-gray-600 dark:text-gray-400">Duration: ${s.duration_days} days</p>`
                                    : ""
                                }
                                ${
                                  s.scheduled_date
                                    ? `<p class="text-xs text-blue-600 dark:text-blue-400 mt-1">📅 Scheduled: ${new Date(s.scheduled_date).toLocaleDateString()}${s.scheduled_time ? ' at ' + s.scheduled_time.substring(0, 5) : ''}</p>`
                                    : ""
                                }
                                ${
                                  s.scheduled_by_name
                                    ? `<p class="text-xs text-gray-500 dark:text-gray-400">Scheduled by: ${s.scheduled_by_name}</p>`
                                    : ""
                                }
                                ${
                                  s.notes
                                    ? `<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">${s.notes}</p>`
                                    : ""
                                }
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Applied: ${new Date(
                                  s.applied_date
                                ).toLocaleDateString()}</p>
                            </div>
                        `
                                )
                                .join("")
                            : '<p class="text-sm text-gray-500 dark:text-gray-400">No sanctions applied yet.</p>'
                        }
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button onclick="editCase('${
                  caseData.id
                }'); closeModal(this);" class="px-4 py-2 text-sm border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 font-medium">
                    Edit
                </button>
                <button onclick="closeModal(this)" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                    Close
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);
}

// Load applied sanctions for view modal (separate function)
async function loadAppliedSanctionsForView(caseId) {
  try {
    const formData = new FormData();
    formData.append("ajax", "1");
    formData.append("action", "getCaseSanctions");
    formData.append("caseId", caseId);

    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();
    return data.success && data.sanctions ? data.sanctions : [];
  } catch (error) {
    console.error("Error loading sanctions:", error);
    return [];
  }
}

// Mark case as resolved with confirmation
async function markCaseResolved(caseId) {
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all duration-200 scale-95 opacity-0">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mark Case as Resolved?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Case ID: ${caseId}</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                This will update the case status to "Resolved". This action can be changed later if needed.
            </p>
            
            <div class="flex justify-end gap-3">
                <button onclick="closeModal(this)" 
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmMarkResolved('${caseId}')" 
                    class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Mark as Resolved
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  // Animate in
  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);
}

async function confirmMarkResolved(caseId) {
  // Close confirmation modal
  const modal = document.querySelector(".fixed.inset-0");
  if (modal) modal.remove();

  showLoadingToast("Marking case as resolved...");

  const formData = new FormData();
  formData.append("ajax", "1");
  formData.append("action", "markResolved");
  formData.append("caseId", caseId);

  try {
    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    closeLoadingToast();

    if (data.success) {
      // Update case status to "Resolved" in real-time
      const caseIndex = allCases.findIndex(c => c.id === caseId);
      if (caseIndex !== -1) {
        allCases[caseIndex].status = 'Resolved';
        allCases[caseIndex].statusColor = 'green';
      }
      
      // If we're in the current tab, remove resolved case from filtered list
      if (currentTab === 'current') {
        filteredCases = allCases.filter(c => c.status !== 'Resolved');
        renderCases();
      } else {
        // Re-render the table to show updated status
        if (typeof filterCases === 'function') {
          filterCases();
        } else if (typeof renderCases === 'function') {
          renderCases();
        }
      }
      
      showNotification("Case marked as resolved successfully!", "success");
    } else {
      showNotification("Failed to mark case as resolved: " + (data.error || "Unknown error"), "error");
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error marking case as resolved:", error);
    showNotification("Error marking case as resolved. Please try again.", "error");
  }
}

