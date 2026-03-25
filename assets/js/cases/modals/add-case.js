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
          showNotification("Error: " + (data.message || "Failed to create case"), "error");
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

