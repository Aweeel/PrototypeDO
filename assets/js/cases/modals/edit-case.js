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

