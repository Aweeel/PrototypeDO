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

