// ====== CASES MODALS.JS ======

// Get all students for dropdown
function loadStudents() {
  return fetch("/PrototypeDO/modules/do/cases.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "ajax=1&action=getStudents",
  })
    .then((response) => response.json())
    .then((data) => data.students || [])
    .catch((error) => {
      console.error("Error loading students:", error);
      return [];
    });
}

// Load offense types from database
async function loadOffenseTypes(category = "") {
  try {
    const formData = new FormData();
    formData.append("ajax", "1");
    formData.append("action", "getOffenseTypes");
    if (category) formData.append("category", category);

    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();
    return data.success ? data.offenses : [];
  } catch (error) {
    console.error("Error loading offense types:", error);
    return [];
  }
}

// Load sanctions from database
async function loadSanctions() {
  try {
    const formData = new FormData();
    formData.append("ajax", "1");
    formData.append("action", "getSanctions");

    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();
    return data.success ? data.sanctions : [];
  } catch (error) {
    console.error("Error loading sanctions:", error);
    return [];
  }
}

// ====== Student Lookup Function ======

async function lookupStudentByNumber(studentNumber) {
  try {
    const formData = new FormData();
    formData.append("ajax", "1");
    formData.append("action", "getStudentByNumber");
    formData.append("studentNumber", studentNumber);

    const response = await fetch("/PrototypeDO/modules/do/cases.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();
    return data.success ? data.student : null;
  } catch (error) {
    console.error("Error looking up student:", error);
    return null;
  }
}

// ====== UTILITY FUNCTIONS ======

// Toggle sanctions view in case modal
function toggleSanctionsView(button) {
    const content = button.nextElementSibling;
    const svg = button.querySelector('svg');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        svg.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        svg.style.transform = 'rotate(0deg)';
    }
}

function getStatusColor(status) {
  switch (status) {
    case "Pending":
      return "yellow";
    case "On Going":
      return "blue";
    case "Resolved":
      return "green";
    default:
      return "gray";
  }
}

// Helper function to convert date format for input field
function convertDateToInput(dateString) {
  try {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  } catch (e) {
    return "";
  }
}

// Close modal
function closeModal(element) {
  const modal = element.closest(".fixed");
  if (modal) modal.remove();
}

// Open image in larger view modal
function openImageModal(imageSrc) {
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-75 z-50 p-4";
  modal.innerHTML = `
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl max-h-[90vh] overflow-auto flex flex-col">
      <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Attachment Preview</h3>
        <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="flex-1 flex items-center justify-center p-4">
        <img src="${imageSrc}" alt="Attachment" class="max-w-full max-h-[calc(90vh-140px)] object-contain">
      </div>
    </div>
  `;
  document.body.appendChild(modal);
}

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

// ====== EDIT CASE MODAL ======

async function editCase(caseId) {
  const caseData = allCases.find((c) => c.id === caseId);
  if (!caseData) return;

  // Load offense types for the current severity
  const offenses = await loadOffenseTypes(caseData.severity);

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit Case: ${
                  caseData.id
                }</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editCaseForm" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student</label>
                        <input type="text" value="${caseData.student}" readonly
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case ID</label>
                        <input type="text" value="${caseData.id}" readonly
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Offense Type <span class="text-red-500">*</span></label>
                        <select id="editOffenseType" required onchange="handleEditOffenseTypeChange()" 
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                            <option value="Minor" ${
                              caseData.severity === "Minor" ? "selected" : ""
                            }>Minor</option>
                            <option value="Major" ${
                              caseData.severity === "Major" ? "selected" : ""
                            }>Major</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Date Reported <span class="text-red-500">*</span></label>
                        <input type="date" id="editDate" value="${convertDateToInput(
                          caseData.date
                        )}" required
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input list="editCaseTypeList" id="editCaseType" required
                            value="${caseData.type}"
                            onchange="handleEditCaseTypeChange()"
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            placeholder="Type to search or select...">
                        <datalist id="editCaseTypeList">
                            ${offenses
                              .map(
                                (o) =>
                                  `<option value="${o.offense_name}">${o.offense_name}</option>`
                              )
                              .join("")}
                            <option value="Others">Others (Specify in description)</option>
                        </datalist>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start typing to filter options</p>
                </div>

                <div id="editCustomOffenseDiv" style="display: none;">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Specify Offense Type <span class="text-red-500">*</span></label>
                    <input type="text" id="editCustomOffense" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="Enter custom offense type...">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status <span class="text-red-500">*</span></label>
                    <select id="editStatus" required class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option ${
                          caseData.status === "Pending" ? "selected" : ""
                        }>Pending</option>
                        <option ${
                          caseData.status === "On Going" ? "selected" : ""
                        }>On Going</option>
                        <option ${
                          caseData.status === "Resolved" ? "selected" : ""
                        }>Resolved</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        Description <span id="editDescRequired" class="text-red-500" style="display: none;">*</span>
                    </label>
                    <textarea id="editDescription" rows="3" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none">${
                          caseData.description
                        }</textarea>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                    <textarea id="editNotes" rows="2" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none">${
                          caseData.notes
                        }</textarea>
                </div>

                <div class="flex justify-between gap-2 mt-4 pt-3 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="archiveCaseFromEdit('${
                      caseData.id
                    }')" 
                        class="px-4 py-2 text-sm border border-orange-600 text-orange-600 rounded hover:bg-orange-50 dark:hover:bg-orange-900/20 font-medium flex items-center">
                        Archive
                    </button>
                    
                    <div class="flex gap-2">
                        <button type="button" onclick="closeModal(this)" 
                            class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    `;
  document.body.appendChild(modal);

  // Check if current case type is "Others"
  if (
    caseData.type === "Others" ||
    !offenses.find((o) => o.offense_name === caseData.type)
  ) {
    handleEditCaseTypeChange();
  }

  document
    .getElementById("editCaseForm")
    .addEventListener("submit", async (e) => {
      e.preventDefault();

      const caseType = document.getElementById("editCaseType").value;
      const description = document.getElementById("editDescription").value;

      // Validate "Others" requires description
      if (caseType === "Others" && !description.trim()) {
        showNotification('Description is required when Case Type is "Others"', "warning");
        return;
      }

      // Validate custom offense type
      if (caseType === "Others") {
        const customOffense =
          document.getElementById("editCustomOffense").value;
        if (!customOffense.trim()) {
          showNotification("Please specify the offense type", "warning");
          return;
        }
      }

      const formData = new FormData();
      formData.append("ajax", "1");
      formData.append("action", "updateCase");
      formData.append("caseId", caseData.id);
      formData.append(
        "type",
        caseType === "Others"
          ? document.getElementById("editCustomOffense").value
          : caseType
      );
      formData.append(
        "dateReported",
        document.getElementById("editDate").value
      );
      formData.append(
        "severity",
        document.getElementById("editOffenseType").value
      );
      formData.append("status", document.getElementById("editStatus").value);
      formData.append("description", description);
      formData.append("notes", document.getElementById("editNotes").value);

      try {
        const response = await fetch("/PrototypeDO/modules/do/cases.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          closeModal(e.target);
          if (typeof loadCasesFromDB === "function") {
            loadCasesFromDB();
          }
          showNotification("Case updated successfully!", "success");
        } else {
          showNotification("Error: " + (data.error || "Failed to update case"), "error");
        }
      } catch (error) {
        console.error("Error updating case:", error);
        showNotification("Error updating case. Please try again.", "error");
      }
    });
}

// Handle offense type change in edit modal
async function handleEditOffenseTypeChange() {
  const offenseType = document.getElementById("editOffenseType").value;
  const caseTypeInput = document.getElementById("editCaseType");
  const datalist = document.getElementById("editCaseTypeList");

  // Load new offense types
  const offenses = await loadOffenseTypes(offenseType);

  // Update datalist
  datalist.innerHTML =
    offenses
      .map(
        (o) => `<option value="${o.offense_name}">${o.offense_name}</option>`
      )
      .join("") +
    '<option value="Others">Others (Specify in description)</option>';

  // Clear current selection
  caseTypeInput.value = "";
}

// Handle case type change in edit modal
function handleEditCaseTypeChange() {
  const caseType = document.getElementById("editCaseType").value;
  const description = document.getElementById("editDescription");
  const descRequired = document.getElementById("editDescRequired");
  const customOffenseDiv = document.getElementById("editCustomOffenseDiv");
  const customOffenseInput = document.getElementById("editCustomOffense");

  if (caseType === "Others") {
    description.required = true;
    descRequired.style.display = "inline";
    customOffenseDiv.style.display = "block";
    customOffenseInput.required = true;
  } else {
    description.required = false;
    descRequired.style.display = "none";
    customOffenseDiv.style.display = "none";
    customOffenseInput.required = false;
  }
}

// Archive case from edit modal
async function archiveCaseFromEdit(caseId) {
  // Close edit modal first
  const editModal = document.querySelector(".fixed.inset-0");
  if (editModal) editModal.remove();

  // Show confirmation modal
  archiveCaseConfirm(caseId);
}

// ====== ADD CASE MODAL ======

async function addCase() {
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto transform transition-transform duration-200 scale-95 opacity-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Add New Case</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="addCaseForm" class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student Number <span class="text-red-500">*</span></label>
                    <input type="text" id="newStudentNumber" required 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="e.g., 02000000001">
                    <p id="studentLookupStatus" class="text-xs mt-1 hidden"></p>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Student Name <span class="text-red-500">*</span></label>
                    <input type="text" id="newStudentName" required readonly
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100 cursor-not-allowed"
                        placeholder="Enter student number first...">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Offense Type <span class="text-red-500">*</span></label>
                    <select id="newOffenseType" required onchange="handleAddOffenseTypeChange()" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">Select offense type...</option>
                        <option value="Minor">Minor</option>
                        <option value="Major">Major</option>
                    </select>
                </div>

                <div id="newCaseTypeDiv" style="display: none;">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Case Type <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input list="newCaseTypeList" id="newCaseType" required
                            onchange="handleAddCaseTypeChange()"
                            class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                            placeholder="Type to search or select...">
                        <datalist id="newCaseTypeList">
                            <!-- Populated dynamically -->
                        </datalist>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start typing to filter options</p>
                </div>

                <div id="newCustomOffenseDiv" style="display: none;">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Specify Offense Type <span class="text-red-500">*</span></label>
                    <input type="text" id="newCustomOffense" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="Enter custom offense type...">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        Description <span id="newDescRequired" class="text-red-500" style="display: none;">*</span>
                    </label>
                    <textarea id="newDescription" rows="3" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Describe the incident..."></textarea>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                    <textarea id="newNotes" rows="2" 
                        class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Additional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3">
                    <button type="button" onclick="closeModal(this)" class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                        Add Case
                    </button>
                </div>
            </form>
        </div>
    `;
  document.body.appendChild(modal);

  // Animate in
  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);

  // Add student number lookup with debounce
  let lookupTimeout;
  document.getElementById("newStudentNumber").addEventListener("input", (e) => {
    clearTimeout(lookupTimeout);
    const studentNumber = e.target.value.trim();
    const statusEl = document.getElementById("studentLookupStatus");
    const nameInput = document.getElementById("newStudentName");

    if (studentNumber.length < 5) {
      statusEl.classList.add("hidden");
      nameInput.value = "";
      return;
    }

    statusEl.textContent = "Looking up student...";
    statusEl.className = "text-xs mt-1 text-blue-600 dark:text-blue-400";
    statusEl.classList.remove("hidden");

    lookupTimeout = setTimeout(async () => {
      const student = await lookupStudentByNumber(studentNumber);

      if (student) {
        nameInput.value = `${student.first_name} ${student.last_name}`;
        nameInput.readOnly = true;
        nameInput.className =
          "w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100 cursor-not-allowed";
        statusEl.textContent = "✓ Student found";
        statusEl.className = "text-xs mt-1 text-green-600 dark:text-green-400";
      } else {
        nameInput.value = "";
        nameInput.readOnly = true;
        nameInput.placeholder = "Student not found in database";
        nameInput.className =
          "w-full px-2.5 py-2 text-sm border border-red-300 dark:border-red-600 rounded bg-red-50 dark:bg-red-900/20 text-red-900 dark:text-red-100 cursor-not-allowed";
        statusEl.textContent =
          "⚠ Student not found. Please check the student number.";
        statusEl.className = "text-xs mt-1 text-red-600 dark:text-red-400";
      }
    }, 500);
  });

  // Form submission handler
  document
    .getElementById("addCaseForm")
    .addEventListener("submit", async (e) => {
      e.preventDefault();

      const studentName = document.getElementById("newStudentName").value;
      const offenseType = document.getElementById("newOffenseType").value;
      const caseType = document.getElementById("newCaseType").value;
      const description = document.getElementById("newDescription").value;

      // Validate student exists
      if (!studentName) {
        showNotification(
          "Please enter a valid student number. Student must exist in the database.",
          "warning"
        );
        return;
      }

      if (!offenseType) {
        showNotification("Please select an Offense Type (Minor or Major)", "warning");
        return;
      }

      if (!caseType) {
        showNotification("Please select a Case Type", "warning");
        return;
      }

      if (caseType === "Others") {
        if (!description.trim()) {
          showNotification('Description is required when Case Type is "Others"', "warning");
          return;
        }
        const customOffense = document.getElementById("newCustomOffense").value;
        if (!customOffense.trim()) {
          showNotification("Please specify the offense type", "warning");
          return;
        }
      }

      const formData = new FormData();
      formData.append("ajax", "1");
      formData.append("action", "createCase");
      formData.append(
        "studentNumber",
        document.getElementById("newStudentNumber").value
      );
      formData.append(
        "studentName",
        document.getElementById("newStudentName").value
      );
      formData.append(
        "type",
        caseType === "Others"
          ? document.getElementById("newCustomOffense").value
          : caseType
      );
      formData.append("severity", offenseType);
      formData.append("description", description);
      formData.append("notes", document.getElementById("newNotes").value);

      try {
        const response = await fetch("/PrototypeDO/modules/do/cases.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          closeModal(e.target);
          loadCasesFromDB();
          showNotification("Case created successfully!", "success");
        } else {
          showNotification("Error: " + (data.error || "Failed to create case"), "error");
        }
      } catch (error) {
        console.error("Error creating case:", error);
        showNotification("Error creating case. Please try again.", "error");
      }
    });
}

// Handle offense type change in add modal
async function handleAddOffenseTypeChange() {
    const offenseType = document.getElementById('newOffenseType').value;
    const caseTypeDiv = document.getElementById('newCaseTypeDiv');
    const caseTypeInput = document.getElementById('newCaseType');
    const datalist = document.getElementById('newCaseTypeList');
    
    if (!offenseType) {
        caseTypeDiv.style.display = 'none';
        return;
    }
    
    caseTypeDiv.style.display = 'block';
    const offenses = await loadOffenseTypes(offenseType);
    
    datalist.innerHTML = offenses.map(o => `<option value="${o.offense_name}">${o.offense_name}</option>`).join('') +
        '<option value="Others">Others (Specify in description)</option>';
    
    caseTypeInput.value = '';
}

// Handle case type change in add modal
function handleAddCaseTypeChange() {
    const caseType = document.getElementById('newCaseType').value;
    const description = document.getElementById('newDescription');
    const descRequired = document.getElementById('newDescRequired');
    const customOffenseDiv = document.getElementById('newCustomOffenseDiv');
    const customOffenseInput = document.getElementById('newCustomOffense');
    
    if (caseType === 'Others') {
        description.required = true;
        descRequired.style.display = 'inline';
        customOffenseDiv.style.display = 'block';
        customOffenseInput.required = true;
    } else {
        description.required = false;
        descRequired.style.display = 'none';
        customOffenseDiv.style.display = 'none';
        customOffenseInput.required = false;
    }
}

// ====== MANAGE SANCTIONS MODAL ======

async function manageSanctions(caseId) {
    const caseData = allCases.find(c => c.id === caseId);
    if (!caseData) return;
    
    const sanctions = await loadSanctions();
    
    // Fetch student data for offense history
    let studentOffenseData = null;
    try {
        const studentResponse = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax=1&action=getStudentByNumber&studentNumber=${caseData.studentId}`
        });
        const studentResult = await studentResponse.json();
        if (studentResult.success && studentResult.student) {
            // Fetch full student details including offense counts
            const detailResponse = await fetch('/PrototypeDO/modules/do/studentHistory.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&action=getStudents`
            });
            const detailResult = await detailResponse.json();
            if (detailResult.success) {
                studentOffenseData = detailResult.students.find(s => s.id === caseData.studentId);
            }
        }
    } catch (error) {
        console.error('Error fetching student data:', error);
    }
    
    // Fetch recommended sanction based on escalation algorithm
    let recommendationHTML = '';
    let recommendationData = null;
    try {
        recommendationData = await fetchRecommendedSanction(caseData.studentId, caseData.type, caseData.severity);
        if (recommendationData && recommendationData.sanction_name) {
            const isHighSeverity = recommendationData.subcategory === 'D' || 
                                  recommendationData.sanction_name.includes('Non-readmission') || 
                                  recommendationData.sanction_name.includes('Exclusion') ||
                                  recommendationData.sanction_name.includes('Expulsion');
            
            const colorClass = isHighSeverity 
                ? 'bg-red-50 dark:bg-slate-800 border-red-200 dark:border-red-500' 
                : 'bg-blue-50 dark:bg-slate-800 border-blue-200 dark:border-blue-500';
            const iconColor = isHighSeverity ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400';
            const textColor = isHighSeverity ? 'text-red-900 dark:text-red-100' : 'text-blue-900 dark:text-blue-100';
            const buttonColor = isHighSeverity
                ? 'bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800'
                : 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800';
            
            // Build offense history display
            let offenseHistoryHTML = '';
            if (studentOffenseData) {
                const totalOffenses = studentOffenseData.incidents || 0;
                const majorOffenses = studentOffenseData.majorOffenses || 0;
                const minorOffenses = studentOffenseData.minorOffenses || 0;
                
                offenseHistoryHTML = `
                    <div class="mt-2 pt-2 border-t ${isHighSeverity ? 'border-red-200 dark:border-red-800' : 'border-blue-200 dark:border-blue-800'}">
                        <p class="text-xs ${textColor} font-semibold mb-2">Offense History:</p>
                        <div class="grid grid-cols-3 gap-1.5">
                            <div class="text-center p-1.5 bg-white dark:bg-slate-900/30 rounded">
                                <p class="text-sm font-bold ${textColor}">${totalOffenses}</p>
                                <p class="text-xs ${textColor} opacity-70">Total</p>
                            </div>
                            <div class="text-center p-1.5 bg-white dark:bg-slate-900/30 rounded">
                                <p class="text-sm font-bold text-red-600 dark:text-red-400">${majorOffenses}</p>
                                <p class="text-xs ${textColor} opacity-70">Major</p>
                            </div>
                            <div class="text-center p-1.5 bg-white dark:bg-slate-900/30 rounded">
                                <p class="text-sm font-bold text-yellow-600 dark:text-yellow-400">${minorOffenses}</p>
                                <p class="text-xs ${textColor} opacity-70">Minor</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            recommendationHTML = `
                <div class="p-4 ${colorClass} border rounded-lg shadow-xl">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h5 class="font-semibold ${textColor} mb-2 text-sm"> Recommendation</h5>
                            <p class="text-xs ${textColor} mb-2">
                                <strong>Suggested:</strong> ${recommendationData.sanction_name}
                                ${recommendationData.duration_range && !recommendationData.sanction_name.toLowerCase().includes(recommendationData.duration_range.toLowerCase()) ? `<br><span class="opacity-80">${recommendationData.duration_range}</span>` : ''}
                            </p>
                            <p class="text-xs ${textColor} opacity-90 mb-2">
                                ${recommendationData.reason.replace(/(first|second|third|fourth|1st|2nd|3rd|4th)/gi, `<span class="font-bold px-1 py-0.5 rounded ${isHighSeverity ? 'bg-red-200 dark:bg-red-700 text-red-900 dark:text-red-100' : 'bg-blue-200 dark:bg-blue-700 text-blue-900 dark:text-blue-100'}">$1</span>`)}
                                ${recommendationData.subcategory ? `<br><span class="opacity-75">Category ${recommendationData.subcategory}</span>` : ''}
                            </p>
                            ${recommendationData.requires_ched_approval ? `
                                <p class="mt-2 text-xs ${textColor} font-semibold">
                                    ⚠️ CHED approval required
                                </p>
                            ` : ''}
                            ${offenseHistoryHTML}
                            <button type="button" onclick="applyRecommendedSanction('${recommendationData.sanction_name}', ${recommendationData.duration_days || 'null'})" 
                                class="mt-3 w-full px-3 py-1.5 ${buttonColor} text-white text-xs rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Apply Recommendation
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching recommendation:', error);
    }
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[60] p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl flex flex-col" style="max-height: 90vh;">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl flex flex-col" style="max-height: 90vh;">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700 flex-shrink-0">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Manage Sanctions - ${caseData.id}</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 flex-shrink-0">
                <div class="p-3 bg-gray-50 dark:bg-slate-700 rounded">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Student:</strong> ${caseData.student}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Case Type:</strong> ${caseData.type}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Offense Type:</strong> 
                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded ${caseData.severity === 'Major' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'}">${caseData.severity}</span>
                </p>
                </div>
            </div>

            <div class="overflow-y-auto flex-1 px-5">
            <form id="applySanctionForm" class="space-y-4">
                <div class="relative">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Select Sanction <span class="text-red-500">*</span>
                                ${recommendationData ? `
                                    <span class="relative inline-block ml-1 handbook-tooltip-trigger">
                                        <button type="button" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-help" onmouseenter="showHandbookTooltip()" onmouseleave="scheduleHideHandbookTooltip()">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <!-- Tooltip content -->
                                        <div id="handbookTooltip" class="hidden absolute left-full top-0 ml-2 z-50 w-80 transition-all duration-200" style="max-height: 500px; overflow-y: auto;" onmouseenter="keepHandbookTooltip()" onmouseleave="scheduleHideHandbookTooltip()">
                                            ${recommendationHTML}
                                        </div>
                                    </span>
                                ` : ''}
                            </label>
                            <select id="sanctionSelect" required onchange="handleSanctionChange()" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option value="">Choose...</option>
                                ${sanctions.map(s => `
                                    <option value="${s.sanction_id}" data-default-days="${s.default_duration_days || ''}" data-severity="${s.severity_level || ''}" data-description="${s.description || ''}" data-requires-schedule="${s.requires_schedule || 0}">
                                        ${s.sanction_name}${s.severity_level ? ' (Level ' + s.severity_level + ')' : ''}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div id="durationDiv" class="w-28 flex-shrink-0" style="display: none;">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Days <span class="text-red-500">*</span></label>
                            <input type="number" id="sanctionDuration" min="1" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 text-center"
                                placeholder="0">
                        </div>
                    </div>
                </div>

                <div id="sanctionDescription" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-gray-700 dark:text-gray-300" style="display: none; min-height: 60px;">
                </div>

                <!-- Schedule Button -->
                <div>
                    <button type="button" onclick="openSchedulePopup()" id="scheduleToggleBtn"
                        class="w-full px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span id="scheduleButtonText">Add Schedule</span>
                        <span id="scheduleRequiredBadge" class="hidden ml-1 px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs rounded">Required</span>
                    </button>
                    <!-- Hidden inputs to store schedule data -->
                    <input type="hidden" id="sanctionScheduleDate" value="">
                    <input type="hidden" id="sanctionScheduleTime" value="">
                    <input type="hidden" id="sanctionScheduleEndTime" value="">
                    <input type="hidden" id="sanctionScheduleNotes" value="">
                    <!-- Schedule display -->
                    <div id="scheduleDisplay" class="hidden mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">
                        <div class="flex items-center justify-between">
                            <span id="scheduleDisplayText" class="text-blue-800 dark:text-blue-300"></span>
                            <button type="button" onclick="clearSchedule()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Notes</label>
                    <textarea id="sanctionNotes" rows="3" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Any additional information about this sanction..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pb-2 pt-2">
                    <button type="button" onclick="closeModal(this)" 
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                        Apply Sanction
                    </button>
                </div>
            </form>
            </div>

            <div class="border-t border-gray-200 dark:border-slate-700 p-5 flex-shrink-0" style="max-height: 250px; overflow-y: auto;">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Applied Sanctions</h4>
                <div id="appliedSanctionsList" class="space-y-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loading sanctions...</p>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    loadAppliedSanctions(caseId);
    window.sanctionsData = sanctions;

    document.getElementById('applySanctionForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const sanctionId = document.getElementById('sanctionSelect').value;
        const duration = document.getElementById('sanctionDuration').value;
        const notes = document.getElementById('sanctionNotes').value;
        const scheduleDate = document.getElementById('sanctionScheduleDate').value;
        const scheduleTime = document.getElementById('sanctionScheduleTime').value;
        const scheduleEndTime = document.getElementById('sanctionScheduleEndTime').value;
        const scheduleNotes = document.getElementById('sanctionScheduleNotes').value;

        if (!sanctionId) {
            showNotification('Please select a sanction', "warning");
            return;
        }

        const selectedOption = document.getElementById('sanctionSelect').options[document.getElementById('sanctionSelect').selectedIndex];
        const requiresSchedule = selectedOption.dataset.requiresSchedule === '1';
        
        // Validate schedule if required
        if (requiresSchedule && !scheduleDate) {
            showNotification('This sanction requires a scheduled date', "warning");
            return;
        }
        
        // Validate time range if schedule date is provided
        if (scheduleDate && scheduleTime) {
            if (!scheduleEndTime) {
                showNotification('Please enter an end time for the hearing', "warning");
                return;
            }
            
            // Check if end time is after start time
            if (scheduleEndTime <= scheduleTime) {
                showNotification('End time must be after start time', "warning");
                return;
            }
        }

        const durationDiv = document.getElementById('durationDiv');
        if (durationDiv.style.display !== 'none' && !duration) {
            showNotification('Please enter the duration in days', "warning");
            return;
        }

        const sanctionName = selectedOption.text;
        
        // Format time range for display
        let timeRangeDisplay = '';
        if (scheduleTime && scheduleEndTime) {
            timeRangeDisplay = `${scheduleTime} - ${scheduleEndTime}`;
        }
        
        const confirmModal = document.createElement('div');
        confirmModal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70] p-4';
        confirmModal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Apply Sanction?</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">This action will be recorded</p>
                    </div>
                </div>
                
                <div class="mb-4 p-3 bg-gray-50 dark:bg-slate-700 rounded">
                    <p class="text-sm"><strong>Sanction:</strong> ${sanctionName}</p>
                    ${duration ? `<p class="text-sm"><strong>Duration:</strong> ${duration} days</p>` : ''}
                    ${scheduleDate ? `<p class="text-sm"><strong>Scheduled:</strong> ${new Date(scheduleDate).toLocaleDateString()} ${timeRangeDisplay}</p>` : ''}
                    ${scheduleNotes ? `<p class="text-sm"><strong>Schedule Info:</strong> ${scheduleNotes}</p>` : ''}
                    ${notes ? `<p class="text-sm"><strong>Notes:</strong> ${notes}</p>` : ''}
                </div>
                
                <div class="flex justify-end gap-3">
                    <button onclick="closeModal(this)" 
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmApplySanction('${caseId}', '${sanctionId}', '${duration}', \`${notes.replace(/`/g, '\\`')}\`, '${scheduleDate}', '${scheduleTime}', '${scheduleEndTime}', \`${scheduleNotes.replace(/`/g, '\\`')}\`)" 
                        class="px-4 py-2 text-sm bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        Confirm & Apply
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModal);
    });
}

// Confirm apply sanction
async function confirmApplySanction(caseId, sanctionId, duration, notes, scheduleDate, scheduleTime, scheduleEndTime, scheduleNotes) {
    const confirmModal = document.querySelectorAll('.fixed.inset-0')[1];
    if (confirmModal) confirmModal.remove();

    showLoadingToast("Applying sanction...");

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'applySanction');
    formData.append('caseId', caseId);
    formData.append('sanctionId', sanctionId);
    if (duration) formData.append('durationDays', duration);
    formData.append('notes', notes);
    if (scheduleDate) formData.append('scheduleDate', scheduleDate);
    if (scheduleTime) formData.append('scheduleTime', scheduleTime);
    if (scheduleEndTime) formData.append('scheduleEndTime', scheduleEndTime);
    if (scheduleNotes) formData.append('scheduleNotes', scheduleNotes);

    try {
        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        closeLoadingToast();
        
        if (data.success) {
            document.getElementById('applySanctionForm').reset();
            document.getElementById('durationDiv').style.display = 'none';
            document.getElementById('sanctionDescription').style.display = 'none';
            
            // Clear schedule fields and display
            clearSchedule();
            
            loadAppliedSanctions(caseId);
            
            // Update case status to "On Going" in real-time
            const caseIndex = allCases.findIndex(c => c.id === caseId);
            if (caseIndex !== -1) {
                allCases[caseIndex].status = 'On Going';
                allCases[caseIndex].statusColor = 'blue';
            }
            
            // Re-render the table to show updated status
            if (typeof filterCases === 'function') {
                filterCases();
            } else if (typeof renderCases === 'function') {
                renderCases();
            }
            
            showSuccessToast('Sanction applied and status updated to On Going!');
        } else {
            showErrorToast('Failed to apply sanction: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        closeLoadingToast();
        console.error('Error applying sanction:', error);
        showNotification('Error applying sanction. Please try again.', "error");
    }
}

/**
 * Apply the recommended sanction to the form (auto-fill)
 * @param {string} sanctionName - The name of the recommended sanction
 * @param {number|null} durationDays - Recommended duration in days
 */
function applyRecommendedSanction(sanctionName, durationDays) {
    const sanctionSelect = document.getElementById('sanctionSelect');
    const durationInput = document.getElementById('sanctionDuration');
    
    if (!sanctionSelect) {
        console.error('Sanction select element not found');
        return;
    }
    
    // Find the matching sanction option by name
    let matchedOption = null;
    for (let i = 0; i < sanctionSelect.options.length; i++) {
        const option = sanctionSelect.options[i];
        const optionText = option.text.toLowerCase();
        const searchName = sanctionName.toLowerCase();
        
        // Try exact match first
        if (optionText === searchName || option.text === sanctionName) {
            matchedOption = option;
            break;
        }
        
        // Try partial match (for cases like "Suspension (7 days)" matching "Suspension from Class")
        if (optionText.includes(searchName) || searchName.includes(optionText.split('(')[0].trim().toLowerCase())) {
            matchedOption = option;
            break;
        }
    }
    
    if (matchedOption) {
        // Select the sanction
        sanctionSelect.value = matchedOption.value;
        
        // Trigger change event to update dependencies (duration field, description, etc.)
        handleSanctionChange();
        
        // Wait a moment for the duration field to be shown, then set the value
        setTimeout(() => {
            if (durationDays && durationInput) {
                durationInput.value = durationDays;
            }
        }, 100);
        
        // Scroll to the form
        document.getElementById('applySanctionForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Hide the tooltip
        const tooltip = document.getElementById('handbookTooltip');
        if (tooltip) {
            tooltip.classList.add('hidden');
        }
        
        // Show success feedback
        showNotification(`Recommended sanction "${sanctionName}" has been selected`, 'success');
    } else {
        // If exact match not found, show available sanctions that are close
        console.warn('Could not find exact match for:', sanctionName);
        showNotification(`Could not auto-select "${sanctionName}". Please select it manually from the dropdown.`, 'warning');
    }
}

// Handle sanction selection change
function handleSanctionChange() {
    const select = document.getElementById('sanctionSelect');
    const scheduleSection = document.getElementById('scheduleSection');
    const selectedOption = select.options[select.selectedIndex];
    const durationDiv = document.getElementById('durationDiv');
    const durationInput = document.getElementById('sanctionDuration');
    const descriptionDiv = document.getElementById('sanctionDescription');
    
    if (!selectedOption.value) {
        durationDiv.style.display = 'none';
        descriptionDiv.style.display = 'none';
        return;
    }
    
    const defaultDays = selectedOption.dataset.defaultDays;
    const sanctionName = selectedOption.text.toLowerCase();
    const description = selectedOption.dataset.description;
    const requiresSchedule = selectedOption.dataset.requiresSchedule === '1';
    
    // Update schedule button based on sanction type
    const scheduleBtn = document.getElementById('scheduleToggleBtn');
    const scheduleBadge = document.getElementById('scheduleRequiredBadge');
    
    if (requiresSchedule) {
        scheduleBadge.classList.remove('hidden');
        scheduleBtn.classList.add('border-red-300', 'dark:border-red-700', 'bg-red-50', 'dark:bg-red-900/20', 'hover:bg-red-100', 'dark:hover:bg-red-900/30', 'text-red-700', 'dark:text-red-300');
        scheduleBtn.classList.remove('border-blue-200', 'dark:border-blue-700', 'bg-blue-50', 'dark:bg-blue-900/20', 'hover:bg-blue-100', 'dark:hover:bg-blue-900/30', 'text-blue-700', 'dark:text-blue-300');
        // Auto-open schedule section if required
        document.getElementById('scheduleSection').style.display = 'block';
        document.getElementById('scheduleButtonText').textContent = 'Schedule Event (Required)';
    } else {
        scheduleBadge.classList.add('hidden');
        scheduleBtn.classList.remove('border-red-300', 'dark:border-red-700', 'bg-red-50', 'dark:bg-red-900/20', 'hover:bg-red-100', 'dark:hover:bg-red-900/30', 'text-red-700', 'dark:text-red-300');
        scheduleBtn.classList.add('border-blue-200', 'dark:border-blue-700', 'bg-blue-50', 'dark:bg-blue-900/20', 'hover:bg-blue-100', 'dark:hover:bg-blue-900/30', 'text-blue-700', 'dark:text-blue-300');
        document.getElementById('scheduleButtonText').textContent = 'Add Schedule';
    }
    
    if (description && description !== 'null' && description !== '') {
        descriptionDiv.innerHTML = `<strong>Description:</strong> ${description}`;
        descriptionDiv.style.display = 'block';
    } else {
        descriptionDiv.style.display = 'none';
    }
    
    // Sanctions that should never show a duration field
    const noDurationSanctions = ['preventive suspension'];

    // Explicit default durations by sanction name pattern
    const durationDefaults = {
        'corrective reinforcement': 3,
        'suspension from class': 3,
    };

    // Check if this sanction explicitly should not have a duration
    const skipDuration = noDurationSanctions.some(n => sanctionName.includes(n));

    let smartDefault = null;
    for (const [key, days] of Object.entries(durationDefaults)) {
        if (sanctionName.includes(key)) {
            smartDefault = days;
            break;
        }
    }

    // Also extract first number from name as fallback (e.g. "Suspension (7 days)")
    const durationMatch = sanctionName.match(/(\d+)\s*days?/i);
    const extractedDays = durationMatch ? parseInt(durationMatch[1]) : null;

    const requiresDuration = !skipDuration && (
                            smartDefault !== null ||
                            extractedDays !== null ||
                            sanctionName.includes('probation') ||
                            sanctionName.includes('community service') ||
                            (defaultDays && defaultDays !== 'null' && defaultDays !== ''));

    if (requiresDuration) {
        durationDiv.style.display = 'block';
        durationInput.required = true;

        if (smartDefault !== null) {
            durationInput.value = smartDefault;
        } else if (extractedDays) {
            durationInput.value = extractedDays;
        } else if (defaultDays && defaultDays !== 'null' && defaultDays !== '') {
            durationInput.value = defaultDays;
        } else {
            durationInput.value = '';
        }
    } else {
        durationDiv.style.display = 'none';
        durationInput.required = false;
        durationInput.value = '';
    }
}

// Toggle schedule section visibility (deprecated - replaced with popup)
function toggleScheduleSection() {
    openSchedulePopup();
}

// Open schedule popup modal
function openSchedulePopup() {
    const existingData = {
        date: document.getElementById('sanctionScheduleDate')?.value || '',
        time: document.getElementById('sanctionScheduleTime')?.value || '',
        endTime: document.getElementById('sanctionScheduleEndTime')?.value || '',
        notes: document.getElementById('sanctionScheduleNotes')?.value || ''
    };
    
    const modal = document.createElement('div');
    modal.id = 'schedulePopupModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70] p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Schedule Hearing</h3>
                <button onclick="closeSchedulePopup()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="popupScheduleDate" value="${existingData.date}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time <span class="text-red-500">*</span></label>
                        <input type="time" id="popupScheduleTime" value="${existingData.time}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time <span class="text-red-500">*</span></label>
                        <input type="time" id="popupScheduleEndTime" value="${existingData.endTime}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div class="flex items-end">
                        <span id="popupScheduleDuration" class="text-xs text-gray-500 dark:text-gray-400 pb-2"></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Schedule Notes</label>
                    <input type="text" id="popupScheduleNotes" value="${existingData.notes}"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="e.g., Counseling session, Hearing, etc.">
                </div>
                
                <!-- Conflict Warning -->
                <div id="popupConflictWarning" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">⚠️ Scheduling Conflict</h4>
                            <div id="popupConflictDetails" class="text-xs text-red-700 dark:text-red-400 space-y-1"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-slate-700">
                <button onclick="closeSchedulePopup()" class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">
                    Cancel
                </button>
                <button onclick="saveSchedule()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Schedule
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    initializePopupListeners();
}

// Close schedule popup
function closeSchedulePopup() {
    const modal = document.getElementById('schedulePopupModal');
    if (modal) modal.remove();
}

// Save schedule from popup
function saveSchedule() {
    const date = document.getElementById('popupScheduleDate').value;
    const time = document.getElementById('popupScheduleTime').value;
    const endTime = document.getElementById('popupScheduleEndTime').value;
    const notes = document.getElementById('popupScheduleNotes').value;
    
    // Validation
    if (!date) {
        showNotification('Please select a date', 'warning');
        return;
    }
    
    if (!time) {
        showNotification('Please select a start time', 'warning');
        return;
    }
    
    if (!endTime) {
        showNotification('Please select an end time', 'warning');
        return;
    }
    
    if (endTime <= time) {
        showNotification('End time must be after start time', 'warning');
        return;
    }
    
    // Check for conflicts
    const conflictWarning = document.getElementById('popupConflictWarning');
    if (conflictWarning && !conflictWarning.classList.contains('hidden')) {
        showNotification('Cannot save - there is a time conflict. Please choose a different time.', 'error');
        return;
    }
    
    // Save to hidden fields
    document.getElementById('sanctionScheduleDate').value = date;
    document.getElementById('sanctionScheduleTime').value = time;
    document.getElementById('sanctionScheduleEndTime').value = endTime;
    document.getElementById('sanctionScheduleNotes').value = notes;
    
    // Update display
    updateScheduleDisplay();
    
    // Close popup
    closeSchedulePopup();
    
    showNotification('Schedule saved successfully', 'success');
}

// Clear schedule data
function clearSchedule() {
    document.getElementById('sanctionScheduleDate').value = '';
    document.getElementById('sanctionScheduleTime').value = '';
    document.getElementById('sanctionScheduleEndTime').value = '';
    document.getElementById('sanctionScheduleNotes').value = '';
    
    const display = document.getElementById('scheduleDisplay');
    if (display) display.classList.add('hidden');
    
    const buttonText = document.getElementById('scheduleButtonText');
    if (buttonText) buttonText.textContent = 'Add Schedule';
}

// Update schedule display
function updateScheduleDisplay() {
    const date = document.getElementById('sanctionScheduleDate').value;
    const time = document.getElementById('sanctionScheduleTime').value;
    const endTime = document.getElementById('sanctionScheduleEndTime').value;
    const notes = document.getElementById('sanctionScheduleNotes').value;
    
    const display = document.getElementById('scheduleDisplay');
    const displayText = document.getElementById('scheduleDisplayText');
    const buttonText = document.getElementById('scheduleButtonText');
    
    if (date && time && endTime) {
        const dateObj = new Date(date);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = `${time} - ${endTime}`;
        
        let scheduleText = `📅 ${dateStr} at ${timeStr}`;
        if (notes) {
            scheduleText += ` - ${notes}`;
        }
        
        displayText.textContent = scheduleText;
        display.classList.remove('hidden');
        buttonText.textContent = 'Edit Schedule';
    } else {
        display.classList.add('hidden');
        buttonText.textContent = 'Add Schedule';
    }
}

// Initialize popup event listeners
function initializePopupListeners() {
    const dateInput = document.getElementById('popupScheduleDate');
    const timeInput = document.getElementById('popupScheduleTime');
    const endTimeInput = document.getElementById('popupScheduleEndTime');
    
    if (dateInput && timeInput && endTimeInput) {
        dateInput.addEventListener('change', () => {
            calculatePopupDuration();
            checkPopupConflicts();
        });
        
        timeInput.addEventListener('change', () => {
            calculatePopupDuration();
            checkPopupConflicts();
        });
        
        endTimeInput.addEventListener('change', () => {
            calculatePopupDuration();
            checkPopupConflicts();
        });
        
        // Initial calculation        calculatePopupDuration();
        if (dateInput.value && timeInput.value && endTimeInput.value) {
            checkPopupConflicts();
        }
    }
}

// Calculate and display hearing duration
function calculateScheduleDuration() {
    const startTime = document.getElementById('sanctionScheduleTime')?.value;
    const endTime = document.getElementById('sanctionScheduleEndTime')?.value;
    const durationSpan = document.getElementById('scheduleDuration');
    
    if (!durationSpan) return;
    
    if (startTime && endTime) {
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;
        const diffMinutes = endMinutes - startMinutes;
        
        if (diffMinutes > 0) {
            const hours = Math.floor(diffMinutes / 60);
            const minutes = diffMinutes % 60;
            
            let durationText = 'Duration: ';
            if (hours > 0) {
                durationText += `${hours} hour${hours > 1 ? 's' : ''}`;
            }
            if (minutes > 0) {
                if (hours > 0) durationText += ' ';
                durationText += `${minutes} min${minutes > 1 ? 's' : ''}`;
            }
            
            durationSpan.textContent = durationText;
            durationSpan.className = 'text-xs text-green-600 dark:text-green-400 pb-2';
        } else {
            durationSpan.textContent = 'End time must be after start time';
            durationSpan.className = 'text-xs text-red-600 dark:text-red-400 pb-2';
        }
    } else {
        durationSpan.textContent = '';
    }
}

// Initialize time calculation listeners
function initializeTimeCalculation() {
    const startTimeInput = document.getElementById('sanctionScheduleTime');
    const endTimeInput = document.getElementById('sanctionScheduleEndTime');
    const dateInput = document.getElementById('sanctionScheduleDate');
    
    if (startTimeInput && endTimeInput) {
        // Remove existing listeners to avoid duplicates
        startTimeInput.removeEventListener('change', calculateScheduleDuration);
        endTimeInput.removeEventListener('change', calculateScheduleDuration);
        startTimeInput.removeEventListener('change', checkScheduleConflicts);
        endTimeInput.removeEventListener('change', checkScheduleConflicts);
        
        // Add new listeners for duration calculation
        startTimeInput.addEventListener('change', calculateScheduleDuration);
        endTimeInput.addEventListener('change', calculateScheduleDuration);
        
        // Add new listeners for conflict checking
        startTimeInput.addEventListener('change', checkScheduleConflicts);
        endTimeInput.addEventListener('change', checkScheduleConflicts);
    }
    
    if (dateInput) {
        dateInput.removeEventListener('change', checkScheduleConflicts);
        dateInput.addEventListener('change', checkScheduleConflicts);
    }
}

// Check for scheduling conflicts in real-time
let conflictCheckTimeout = null;
async function checkScheduleConflicts() {
    // Clear previous timeout
    if (conflictCheckTimeout) {
        clearTimeout(conflictCheckTimeout);
    }
    
    const dateInput = document.getElementById('sanctionScheduleDate');
    const startTimeInput = document.getElementById('sanctionScheduleTime');
    const endTimeInput = document.getElementById('sanctionScheduleEndTime');
    const conflictWarning = document.getElementById('conflictWarning');
    const conflictDetails = document.getElementById('conflictDetails');
    
    if (!dateInput || !startTimeInput || !endTimeInput || !conflictWarning || !conflictDetails) {
        return;
    }
    
    const scheduleDate = dateInput.value;
    const scheduleTime = startTimeInput.value;
    const scheduleEndTime = endTimeInput.value;
    
    // Hide warning if inputs are incomplete
    if (!scheduleDate || !scheduleTime || !scheduleEndTime) {
        conflictWarning.classList.add('hidden');
        return;
    }
    
    // Validate end time is after start time
    if (scheduleEndTime <= scheduleTime) {
        conflictWarning.classList.add('hidden');
        return;
    }
    
    // Debounce the API call
    conflictCheckTimeout = setTimeout(async () => {
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'checkConflicts');
            formData.append('scheduleDate', scheduleDate);
            formData.append('scheduleTime', scheduleTime);
            formData.append('scheduleEndTime', scheduleEndTime);
            
            const response = await fetch('/PrototypeDO/modules/do/cases.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success && data.hasConflict && data.conflicts.length > 0) {
                // Show conflict warning
                conflictDetails.innerHTML = data.conflicts.map(conflict => `
                    <div class="flex items-start gap-1">
                        <span class="text-red-600 dark:text-red-400">\u2022</span>
                        <span><strong>${conflict.name}</strong> is already scheduled at ${conflict.time}</span>
                    </div>
                `).join('');
                conflictWarning.classList.remove('hidden');
            } else {
                // Hide conflict warning
                conflictWarning.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error checking conflicts:', error);
            conflictWarning.classList.add('hidden');
        }
    }, 500); // 500ms debounce
}

// Calculate duration for popup
function calculatePopupDuration() {
    const startTime = document.getElementById('popupScheduleTime')?.value;
    const endTime = document.getElementById('popupScheduleEndTime')?.value;
    const durationSpan = document.getElementById('popupScheduleDuration');
    
    if (!durationSpan) return;
    
    if (startTime && endTime) {
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;
        const diffMinutes = endMinutes - startMinutes;
        
        if (diffMinutes > 0) {
            const hours = Math.floor(diffMinutes / 60);
            const minutes = diffMinutes % 60;
            
            let durationText = '';
            if (hours > 0) {
                durationText += `${hours} hr${hours > 1 ? 's' : ''}`;
            }
            if (minutes > 0) {
                if (hours > 0) durationText += ' ';
                durationText += `${minutes} min`;
            }
            
            durationSpan.textContent = durationText;
            durationSpan.className = 'text-xs text-green-600 dark:text-green-400 pb-2';
        } else {
            durationSpan.textContent = 'Invalid time range';
            durationSpan.className = 'text-xs text-red-600 dark:text-red-400 pb-2';
        }
    } else {
        durationSpan.textContent = '';
    }
}

// Check conflicts for popup
let popupConflictCheckTimeout = null;
async function checkPopupConflicts() {
    // Clear previous timeout
    if (popupConflictCheckTimeout) {
        clearTimeout(popupConflictCheckTimeout);
    }
    
    const dateInput = document.getElementById('popupScheduleDate');
    const startTimeInput = document.getElementById('popupScheduleTime');
    const endTimeInput = document.getElementById('popupScheduleEndTime');
    const conflictWarning = document.getElementById('popupConflictWarning');
    const conflictDetails = document.getElementById('popupConflictDetails');
    
    if (!dateInput || !startTimeInput || !endTimeInput || !conflictWarning || !conflictDetails) {
        return;
    }
    
    const scheduleDate = dateInput.value;
    const scheduleTime = startTimeInput.value;
    const scheduleEndTime = endTimeInput.value;
    
    // Hide warning if inputs are incomplete
    if (!scheduleDate || !scheduleTime || !scheduleEndTime) {
        conflictWarning.classList.add('hidden');
        return;
    }
    
    // Validate end time is after start time
    if (scheduleEndTime <= scheduleTime) {
        conflictWarning.classList.add('hidden');
        return;
    }
    
    // Debounce the API call
    popupConflictCheckTimeout = setTimeout(async () => {
        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'checkConflicts');
            formData.append('scheduleDate', scheduleDate);
            formData.append('scheduleTime', scheduleTime);
            formData.append('scheduleEndTime', scheduleEndTime);
            
            const response = await fetch('/PrototypeDO/modules/do/cases.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success && data.hasConflict && data.conflicts.length > 0) {
                // Show conflict warning
                conflictDetails.innerHTML = data.conflicts.map(conflict => `
                    <div class="flex items-start gap-1">
                        <span class="text-red-600 dark:text-red-400">•</span>
                        <span><strong>${conflict.name}</strong> at ${conflict.time}</span>
                    </div>
                `).join('');
                conflictWarning.classList.remove('hidden');
            } else {
                // Hide conflict warning
                conflictWarning.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error checking conflicts:', error);
            conflictWarning.classList.add('hidden');
        }
    }, 500); // 500ms debounce
}

// Load applied sanctions for a case
async function loadAppliedSanctions(caseId) {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'getCaseSanctions');
        formData.append('caseId', caseId);

        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        const listDiv = document.getElementById('appliedSanctionsList');
        
        if (data.success && data.sanctions && data.sanctions.length > 0) {
            listDiv.innerHTML = data.sanctions.map(s => {
                // Format scheduled time range if available
                let scheduledInfo = '';
                if (s.scheduled_date) {
                    const dateStr = new Date(s.scheduled_date).toLocaleDateString();
                    if (s.scheduled_time && s.scheduled_end_time) {
                        // Format time range
                        const startTime = s.scheduled_time.substring(0, 5); // HH:MM
                        const endTime = s.scheduled_end_time.substring(0, 5); // HH:MM
                        scheduledInfo = `<p class="text-xs text-blue-600 dark:text-blue-400 mt-1">📅 Scheduled: ${dateStr} (${startTime} - ${endTime})</p>`;
                    } else if (s.scheduled_time) {
                        const timeStr = s.scheduled_time.substring(0, 5);
                        scheduledInfo = `<p class="text-xs text-blue-600 dark:text-blue-400 mt-1">📅 Scheduled: ${dateStr} at ${timeStr}</p>`;
                    } else {
                        scheduledInfo = `<p class="text-xs text-blue-600 dark:text-blue-400 mt-1">📅 Scheduled: ${dateStr}</p>`;
                    }
                    if (s.schedule_notes) {
                        scheduledInfo += `<p class="text-xs text-gray-500 dark:text-gray-400 italic">${s.schedule_notes}</p>`;
                    }
                }
                
                return `
                <div class="p-3 bg-gray-50 dark:bg-slate-700 rounded">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${s.sanction_name}</p>
                            ${s.duration_days ? `<p class="text-xs text-gray-600 dark:text-gray-400">Duration: ${s.duration_days} days</p>` : ''}
                            ${scheduledInfo}
                            ${s.notes ? `<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">${s.notes}</p>` : ''}
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Applied on: ${new Date(s.applied_date).toLocaleDateString()}</p>
                        </div>
                        <div class="flex flex-col gap-2 items-end">
                            <span class="text-xs px-2 py-1 rounded whitespace-nowrap ${
                                s.severity_level >= 4 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                                s.severity_level >= 3 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' :
                                s.severity_level >= 2 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                            }">Level ${s.severity_level || 'N/A'}</span>
                            <button onclick="editSanction('${caseId}', '${s.case_sanction_id}', '${s.sanction_name}', '${s.duration_days || ''}', \`${(s.notes || '').replace(/`/g, '\\`')}\`)" 
                                class="text-xs px-2 py-1 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                Edit
                            </button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        } else {
            listDiv.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No sanctions applied yet.</p>';
        }
    } catch (error) {
        console.error('Error loading applied sanctions:', error);
        document.getElementById('appliedSanctionsList').innerHTML = '<p class="text-sm text-red-500">Error loading sanctions.</p>';
    }
}

// ====== EDIT SANCTION FUNCTION ======

async function editSanction(caseId, caseSanctionId, sanctionName, currentDuration, currentNotes) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70] p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Edit Sanction</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="editSanctionForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Sanction</label>
                    <input type="text" value="${sanctionName}" readonly
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (Days)</label>
                    <input type="number" id="editSanctionDuration" min="1" value="${currentDuration}"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="Enter number of days...">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty if not applicable</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea id="editSanctionNotes" rows="3" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Additional notes...">${currentNotes}</textarea>
                </div>

                <div class="flex justify-between gap-2 pt-3 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="removeSanctionFromEdit('${caseId}', '${caseSanctionId}')" 
                        class="px-4 py-2 text-sm border border-red-600 text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        Remove
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeModal(this)" 
                            class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('editSanctionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const duration = document.getElementById('editSanctionDuration').value;
        const notes = document.getElementById('editSanctionNotes').value;

        closeModal(e.target);
        showLoadingToast("Updating sanction...");

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'updateSanction');
        formData.append('caseSanctionId', caseSanctionId);
        if (duration) formData.append('durationDays', duration);
        formData.append('notes', notes);

        try {
            const response = await fetch('/PrototypeDO/modules/do/cases.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            closeLoadingToast();
            
            if (data.success) {
                loadAppliedSanctions(caseId);
                showNotification('Sanction updated successfully!', "success");
            } else {
                showNotification('Failed to update sanction: ' + (data.error || 'Unknown error'), "error");
            }
        } catch (error) {
            closeLoadingToast();
            console.error('Error updating sanction:', error);
            showNotification('Error updating sanction. Please try again.', "error");
        }
    });
}

// ====== REMOVE SANCTION FUNCTION ======

// Remove sanction from edit modal
async function removeSanctionFromEdit(caseId, caseSanctionId) {
    // Close edit modal first
    const editModal = document.querySelectorAll('.fixed.inset-0')[1];
    if (editModal) editModal.remove();
    
    // Show confirmation modal
    removeSanction(caseId, caseSanctionId);
}

async function removeSanction(caseId, caseSanctionId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70] p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Remove Sanction?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">This action cannot be undone</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                Are you sure you want to remove this sanction from the case? The sanction record will be permanently deleted.
            </p>
            
            <div class="flex justify-end gap-3">
                <button onclick="closeModal(this)" 
                    class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmRemoveSanction('${caseId}', '${caseSanctionId}')" 
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Remove Sanction
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function confirmRemoveSanction(caseId, caseSanctionId) {
    // Close confirmation modal
    const modals = document.querySelectorAll('.fixed.inset-0');
    if (modals.length > 0) modals[modals.length - 1].remove();

    showLoadingToast("Removing sanction...");

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'removeSanction');
    formData.append('caseSanctionId', caseSanctionId);

    try {
        const response = await fetch('/PrototypeDO/modules/do/cases.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        closeLoadingToast();
        
        if (data.success) {
            loadAppliedSanctions(caseId);
            
            // If the status changed, update it in real-time
            if (data.statusChanged && data.newStatus) {
                const caseIndex = allCases.findIndex(c => c.id === caseId);
                if (caseIndex !== -1) {
                    allCases[caseIndex].status = data.newStatus;
                    allCases[caseIndex].statusColor = getStatusColor(data.newStatus);
                }
                
                // Update filtered cases as well
                const filteredIndex = filteredCases.findIndex(c => c.id === caseId);
                if (filteredIndex !== -1) {
                    filteredCases[filteredIndex].status = data.newStatus;
                    filteredCases[filteredIndex].statusColor = getStatusColor(data.newStatus);
                }
                
                // Re-render the table to show updated status
                if (typeof renderCases === 'function') {
                    renderCases();
                }
                
                showSuccessToast('Sanction removed successfully! Status updated to ' + data.newStatus);
            } else {
                showSuccessToast('Sanction removed successfully!');
            }
        } else {
            showErrorToast('Failed to remove sanction: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        closeLoadingToast();
        console.error('Error removing sanction:', error);
        showNotification('Error removing sanction. Please try again.', "error");
    }
}

// Helper function to get status color
function getStatusColor(status) {
    const colorMap = {
        'Pending': 'yellow',
        'On Going': 'blue',
        'Resolved': 'green',
        'Closed': 'gray'
    };
    return colorMap[status] || 'gray';
}

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

// ====== Toast Notifications ======

function showLoadingToast(message) {
  const existingToast = document.getElementById("loadingToast");
  if (existingToast) existingToast.remove();

  const toast = document.createElement("div");
  toast.id = "loadingToast";
  toast.className =
    "fixed top-4 right-4 z-50 bg-white dark:bg-slate-800 rounded-lg shadow-lg p-4 flex items-center gap-3 border border-gray-200 dark:border-slate-700";
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