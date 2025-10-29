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

function getStatusColor(status) {
  switch (status) {
    case "Pending":
      return "yellow";
    case "Under Review":
      return "blue";
    case "Resolved":
      return "green";
    case "Escalated":
      return "red";
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

// ====== VIEW CASE MODAL ======

function viewCase(caseId) {
  const caseData = allCases.find((c) => c.id === caseId);
  if (!caseData) return;

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-5">
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
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${
                          caseData.student
                        }</span>
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
            </div>

            <div class="flex justify-between gap-2 mt-5">
                <button onclick="archiveCaseConfirm('${
                  caseData.id
                }')" class="px-4 py-2 text-sm border border-orange-600 text-orange-600 rounded hover:bg-orange-50 dark:hover:bg-orange-900/20 font-medium">
                    Archive
                </button>
                <div class="flex gap-2">
                    <button onclick="markCaseResolved('${
                      caseData.id
                    }')" class="px-4 py-2 text-sm border border-green-600 text-green-600 rounded hover:bg-green-50 dark:hover:bg-green-900/20 font-medium">
                        Mark Resolved
                    </button>
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
        </div>
    `;
  document.body.appendChild(modal);
}

// Mark case as resolved
async function markCaseResolved(caseId) {
  if (!confirm("Mark this case as resolved? This will update the case status."))
    return;

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

    if (data.success) {
      // Close any open modal
      const modal = document.querySelector(".fixed.inset-0");
      if (modal) modal.remove();
      
      // Reload cases
      if (typeof loadCasesFromDB === 'function') {
        loadCasesFromDB();
      }
      
      // Show success message
      if (typeof showSuccessToast === 'function') {
        showSuccessToast("Case marked as resolved successfully!");
      } else {
        alert("Case marked as resolved successfully!");
      }
    } else {
      alert("Error: " + (data.error || "Failed to mark case as resolved"));
    }
  } catch (error) {
    console.error("Error marking case as resolved:", error);
    alert("Error marking case as resolved. Please try again.");
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
                          caseData.status === "Under Review" ? "selected" : ""
                        }>Under Review</option>
                        <option ${
                          caseData.status === "Resolved" ? "selected" : ""
                        }>Resolved</option>
                        <option ${
                          caseData.status === "Escalated" ? "selected" : ""
                        }>Escalated</option>
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
                    <button type="button" onclick="archiveCaseFromEdit('${caseData.id}')" 
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
        alert('Description is required when Case Type is "Others"');
        return;
      }

      // Validate custom offense type
      if (caseType === "Others") {
        const customOffense =
          document.getElementById("editCustomOffense").value;
        if (!customOffense.trim()) {
          alert("Please specify the offense type");
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
          if (typeof loadCasesFromDB === 'function') {
            loadCasesFromDB();
          }
          if (typeof showSuccessToast === 'function') {
            showSuccessToast("Case updated successfully!");
          } else {
            alert("Case updated successfully!");
          }
        } else {
          alert("Error: " + (data.error || "Failed to update case"));
        }
      } catch (error) {
        console.error("Error updating case:", error);
        alert("Error updating case. Please try again.");
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
                        placeholder="e.g., 02000372341">
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
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1.5">Status</label>
                    <select id="newStatus" class="w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option>Pending</option>
                        <option>Under Review</option>
                        <option>Resolved</option>
                        <option>Escalated</option>
                    </select>
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
        nameInput.readOnly = false;
        nameInput.placeholder = "Student not found - enter name manually";
        nameInput.className =
          "w-full px-2.5 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100";
        statusEl.textContent = "Student not in database - enter name manually";
        statusEl.className =
          "text-xs mt-1 text-orange-600 dark:text-orange-400";
      }
    }, 500);
  });

  // Form submission handler
  document
    .getElementById("addCaseForm")
    .addEventListener("submit", async (e) => {
      e.preventDefault();

      const offenseType = document.getElementById("newOffenseType").value;
      const caseType = document.getElementById("newCaseType").value;
      const description = document.getElementById("newDescription").value;

      if (!offenseType) {
        alert("Please select an Offense Type (Minor or Major)");
        return;
      }

      if (!caseType) {
        alert("Please select a Case Type");
        return;
      }

      if (caseType === "Others") {
        if (!description.trim()) {
          alert('Description is required when Case Type is "Others"');
          return;
        }
        const customOffense = document.getElementById("newCustomOffense").value;
        if (!customOffense.trim()) {
          alert("Please specify the offense type");
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
      formData.append("status", document.getElementById("newStatus").value);
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
          if (typeof loadCasesFromDB === 'function') {
            loadCasesFromDB();
          }
          if (typeof showSuccessToast === 'function') {
            showSuccessToast("Case created successfully!");
          } else {
            alert("Case created successfully!");
          }
        } else {
          alert("Error: " + (data.error || "Failed to create case"));
        }
      } catch (error) {
        console.error("Error creating case:", error);
        alert("Error creating case. Please try again.");
      }
    });
}

// Handle offense type change in add modal
async function handleAddOffenseTypeChange() {
  const offenseType = document.getElementById("newOffenseType").value;
  const caseTypeDiv = document.getElementById("newCaseTypeDiv");
  const caseTypeInput = document.getElementById("newCaseType");
  const datalist = document.getElementById("newCaseTypeList");

  if (!offenseType) {
    caseTypeDiv.style.display = "none";
    return;
  }

  // Show case type dropdown
  caseTypeDiv.style.display = "block";

  // Load offense types from database
  const offenses = await loadOffenseTypes(offenseType);

  // Populate datalist
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

// Handle case type change in add modal
function handleAddCaseTypeChange() {
  const caseType = document.getElementById("newCaseType").value;
  const description = document.getElementById("newDescription");
  const descRequired = document.getElementById("newDescRequired");
  const customOffenseDiv = document.getElementById("newCustomOffenseDiv");
  const customOffenseInput = document.getElementById("newCustomOffense");

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

// ====== MANAGE SANCTIONS MODAL ======

async function manageSanctions(caseId) {
  const caseData = allCases.find((c) => c.id === caseId);
  if (!caseData) return;

  // Load available sanctions
  const sanctions = await loadSanctions();

  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4";
  modal.innerHTML = `
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Manage Sanctions - ${
                  caseData.id
                }</h3>
                <button onclick="closeModal(this)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-3 bg-gray-50 dark:bg-slate-700 rounded">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Student:</strong> ${
                      caseData.student
                    }
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Case Type:</strong> ${
                      caseData.type
                    }
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">Offense Type:</strong> 
                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded ${
                      caseData.severity === "Major"
                        ? "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                        : "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                    }">${caseData.severity}</span>
                </p>
            </div>

            <form id="applySanctionForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Select Sanction <span class="text-red-500">*</span></label>
                    <select id="sanctionSelect" required onchange="handleSanctionChange()" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        <option value="">Choose a sanction...</option>
                        ${sanctions
                          .map(
                            (s) => `
                            <option value="${
                              s.sanction_id
                            }" data-default-days="${
                              s.default_duration_days || ""
                            }" data-severity="${
                              s.severity_level || ""
                            }" data-description="${s.description || ""}">
                                ${s.sanction_name}${
                              s.severity_level
                                ? " (" + s.severity_level + ")"
                                : ""
                            }
                            </option>
                        `
                          )
                          .join("")}
                    </select>
                </div>

                <div id="sanctionDescription" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-gray-700 dark:text-gray-300" style="display: none;">
                    <!-- Sanction description will be shown here -->
                </div>

                <div id="durationDiv" style="display: none;">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
                    <input type="number" id="sanctionDuration" min="1" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100"
                        placeholder="Enter number of days...">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Notes</label>
                    <textarea id="sanctionNotes" rows="3" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none" 
                        placeholder="Any additional information about this sanction..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-slate-700">
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

            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-slate-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Applied Sanctions</h4>
                <div id="appliedSanctionsList" class="space-y-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loading sanctions...</p>
                </div>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  // Load and display applied sanctions
  loadAppliedSanctions(caseId);

  // Store sanctions data for later use
  window.sanctionsData = sanctions;

  document
    .getElementById("applySanctionForm")
    .addEventListener("submit", async (e) => {
      e.preventDefault();

      const sanctionId = document.getElementById("sanctionSelect").value;
      const duration = document.getElementById("sanctionDuration").value;
      const notes = document.getElementById("sanctionNotes").value;

      if (!sanctionId) {
        alert("Please select a sanction");
        return;
      }

      // Check if duration is required
      const durationDiv = document.getElementById("durationDiv");
      if (durationDiv.style.display !== "none" && !duration) {
        alert("Please enter the duration in days");
        return;
      }

      const formData = new FormData();
      formData.append("ajax", "1");
      formData.append("action", "applySanction");
      formData.append("caseId", caseId);
      formData.append("sanctionId", sanctionId);
      if (duration) formData.append("durationDays", duration);
      formData.append("notes", notes);

      try {
        const response = await fetch("/PrototypeDO/modules/do/cases.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          alert("Sanction applied successfully!");
          // Reset form
          document.getElementById("applySanctionForm").reset();
          document.getElementById("durationDiv").style.display = "none";
          document.getElementById("sanctionDescription").style.display = "none";
          // Reload applied sanctions list
          loadAppliedSanctions(caseId);
        } else {
          alert("Error: " + (data.error || "Failed to apply sanction"));
        }
      } catch (error) {
        console.error("Error applying sanction:", error);
        alert("Error applying sanction. Please try again.");
      }
    });
}

// Handle sanction selection change
function handleSanctionChange() {
  const select = document.getElementById("sanctionSelect");
  const selectedOption = select.options[select.selectedIndex];
  const durationDiv = document.getElementById("durationDiv");
  const durationInput = document.getElementById("sanctionDuration");
  const descriptionDiv = document.getElementById("sanctionDescription");

  if (!selectedOption.value) {
    durationDiv.style.display = "none";
    descriptionDiv.style.display = "none";
    return;
  }

  const defaultDays = selectedOption.dataset.defaultDays;
  const sanctionName = selectedOption.text.toLowerCase();
  const description = selectedOption.dataset.description;

  // Show description
  if (description && description !== "null" && description !== "") {
    descriptionDiv.innerHTML = `<strong>Description:</strong> ${description}`;
    descriptionDiv.style.display = "block";
  } else {
    descriptionDiv.style.display = "none";
  }

  // Check if sanction requires duration
  const requiresDuration =
    sanctionName.includes("suspension") ||
    sanctionName.includes("probation") ||
    sanctionName.includes("community service") ||
    defaultDays;

  if (requiresDuration) {
    durationDiv.style.display = "block";
    durationInput.required = true;
    if (defaultDays && defaultDays !== "null" && defaultDays !== "") {
      durationInput.value = defaultDays;
    }
  } else {
    durationDiv.style.display = "none";
    durationInput.required = false;
    durationInput.value = "";
  }
}

// Load applied sanctions for a case
async function loadAppliedSanctions(caseId) {
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

    const listDiv = document.getElementById("appliedSanctionsList");

    if (data.success && data.sanctions && data.sanctions.length > 0) {
      listDiv.innerHTML = data.sanctions
        .map(
          (s) => `
                <div class="p-3 bg-gray-50 dark:bg-slate-700 rounded">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${
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
                        </div>
                        <span class="text-xs px-2 py-1 rounded ${
                          s.severity_level === "High"
                            ? "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                            : s.severity_level === "Medium"
                            ? "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                            : "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                        }">${s.severity_level || "N/A"}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Applied on: ${new Date(
                      s.applied_date
                    ).toLocaleDateString()}</p>
                </div>
            `
        )
        .join("");
    } else {
      listDiv.innerHTML =
        '<p class="text-sm text-gray-500 dark:text-gray-400">No sanctions applied yet.</p>';
    }
  } catch (error) {
    console.error("Error loading applied sanctions:", error);
    document.getElementById("appliedSanctionsList").innerHTML =
      '<p class="text-sm text-red-500">Error loading sanctions.</p>';
  }
}

// ====== ARCHIVE FUNCTIONS ======

// Archive case with confirmation
function archiveCaseConfirm(caseId) {
  const modal = document.createElement("div");
  modal.className =
    "fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 transition-opacity duration-200";
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
                <button onclick="closeModal(this)" 
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

  // Animate in
  setTimeout(() => {
    const modalContent = modal.querySelector("div > div");
    modalContent.classList.remove("scale-95", "opacity-0");
    modalContent.classList.add("scale-100", "opacity-100");
  }, 10);
}

async function confirmArchiveCase(caseId) {
  const modal = document.querySelector(".fixed.inset-0");
  if (modal) modal.remove();

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
      showSuccessToast("Case archived successfully!");
    } else {
      showErrorToast(
        "Failed to archive case: " + (data.error || "Unknown error")
      );
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error archiving case:", error);
    showErrorToast("Error archiving case. Please try again.");
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

  // Animate in
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
      showSuccessToast("Case restored successfully!");
    } else {
      showErrorToast(
        "Failed to restore case: " + (data.error || "Unknown error")
      );
    }
  } catch (error) {
    closeLoadingToast();
    console.error("Error restoring case:", error);
    showErrorToast("Error restoring case. Please try again.");
  }
}

// ====== Toast Notifications (Tailwind Only) ======

function showLoadingToast(message) {
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
  const toast = document.createElement("div");
  toast.className =
    "fixed top-4 right-4 z-50 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg shadow-lg p-4 flex items-center gap-3 transition-all duration-300 transform translate-x-full";
  toast.innerHTML = `
        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <span class="text-green-800 dark:text-green-200 font-medium">${message}</span>
    `;
  document.body.appendChild(toast);

  setTimeout(() => toast.classList.remove("translate-x-full"), 10);
  setTimeout(() => {
    toast.classList.add("translate-x-full");
    setTimeout(() => toast.remove(), 300);
  }, 2000);
}

function showErrorToast(message) {
  const toast = document.createElement("div");
  toast.className =
    "fixed top-4 right-4 z-50 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-lg p-4 flex items-center gap-3 transition-all duration-300 transform translate-x-full";
  toast.innerHTML = `
        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
        <span class="text-red-800 dark:text-red-200 font-medium">${message}</span>
    `;
  document.body.appendChild(toast);

  setTimeout(() => toast.classList.remove("translate-x-full"), 10);
  setTimeout(() => {
    toast.classList.add("translate-x-full");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}
