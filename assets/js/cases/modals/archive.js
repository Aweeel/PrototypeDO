// ====== ARCHIVE FUNCTIONS ======

function archiveCaseConfirm(caseId) {
  const existingModal = document.querySelector('.archive-confirm-modal');
  if (existingModal) return;

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200 archive-confirm-modal";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all duration-200 scale-95 opacity-0">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Archive Case?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Case ID: ${caseId}</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                This case will be moved to the Archived tab. You can restore it later if needed.
            </p>
            
            <div class="flex justify-end gap-3">
                <button onclick="closeArchiveModal()" 
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmArchiveCase('${caseId}')" 
                    class="px-4 py-2 text-sm bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    Archive Case
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);
}

function closeArchiveModal() {
  const modal = document.querySelector('.archive-confirm-modal');
  if (modal) modal.remove();
}

async function confirmArchiveCase(caseId) {
  closeArchiveModal();

  showLoadingToast("Archiving case...");

  const formData = new FormData();
  formData.append("ajax", "1");
  formData.append("action", "archiveCase");
  formData.append("caseId", caseId);

  try {
    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    closeLoadingToast();

    if (data.success) {
      loadCasesFromDB();
      showNotification("Case archived successfully!", "success");
    } else {
      showNotification(
        "Failed to archive case: " + (data.error || "Unknown error")
      );
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error archiving case:", error);
    showNotification("Error archiving case. Please try again.", "error");
  }
}

async function unarchiveCase(caseId) {
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all duration-200 scale-95 opacity-0">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Restore Case?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Case ID: ${caseId}</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                This case will be restored to active cases and moved back to the Current tab.
            </p>
            
            <div class="flex justify-end gap-3">
                <button onclick="closeModal(this)" 
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmUnarchiveCase('${caseId}')" 
                    class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Restore Case
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);
}

async function confirmUnarchiveCase(caseId) {
  const modal = document.querySelector(".fixed.inset-0");
  if (modal) modal.remove();

  showLoadingToast("Restoring case...");

  const formData = new FormData();
  formData.append("ajax", "1");
  formData.append("action", "unarchiveCase");
  formData.append("caseId", caseId);

  try {
    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    closeLoadingToast();

    if (data.success) {
      loadCasesFromDB();
      showNotification("Case restored successfully!", "success");
    } else {
      showNotification(
        "Failed to restore case: " + (data.error || "Unknown error")
      );
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error restoring case:", error);
    showErrorToast("Error restoring case. Please try again.");
  }
}

// Bulk restore cases
async function bulkRestoreCases() {
  if (selectedCaseIds.size === 0) {
    showNotification("Please select at least one case to restore", "error");
    return;
  }

  const caseIds = Array.from(selectedCaseIds);
  
  // Show confirmation modal
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all duration-200 scale-95 opacity-0">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Restore Multiple Cases?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">${caseIds.length} case(s) selected</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                All selected cases will be restored to active cases and moved back to the Current tab.
            </p>
            
            <div class="flex justify-end gap-3">
                <button onclick="closeModal(this)" 
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmBulkRestore(${JSON.stringify(caseIds).replace(/"/g, '&quot;')})" 
                    class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Restore ${caseIds.length} Case(s)
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);
}

async function confirmBulkRestore(caseIds) {
  const modal = document.querySelector(".fixed.inset-0");
  if (modal) modal.remove();

  showLoadingToast(`Restoring ${caseIds.length} case(s)...`);

  const formData = new FormData();
  formData.append("ajax", "1");
  formData.append("action", "unarchiveCases");
  
  // Append each case ID as an array element
  caseIds.forEach(id => {
    formData.append("caseIds[]", id);
  });

  try {
    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    closeLoadingToast();

    if (data.success) {
      // Clear selections and reload
      clearCaseSelections();
      loadCasesFromDB();
      showNotification(data.message, "success");
    } else {
      showNotification(
        "Failed to restore cases: " + (data.error || "Unknown error"),
        "error"
      );
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error restoring cases:", error);
    showErrorToast("Error restoring cases. Please try again.");
  }
}

