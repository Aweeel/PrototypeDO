<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'lookupStudent') {
        $studentNumber = $_POST['studentNumber'] ?? '';
        
        if (empty($studentNumber)) {
            echo json_encode(['success' => false, 'error' => 'Student number required']);
            exit;
        }
        
        $student = getStudentById($studentNumber);
        
        if ($student) {
            echo json_encode([
                'success' => true,
                'student' => [
                    'student_id' => $student['student_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'full_name' => $student['first_name'] . ' ' . $student['last_name'],
                    'grade_year' => $student['grade_year'] ?? 'N/A',
                    'track_course' => $student['track_course'] ?? 'N/A'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Student not found']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'getOffenseTypes') {
        $category = $_POST['category'] ?? '';
        if ($category) {
            $offenses = getOffenseTypesByCategory($category);
        } else {
            $offenses = getAllOffenseTypes();
        }
        echo json_encode(['success' => true, 'offenses' => $offenses]);
        exit;
    }
    
    if ($_POST['action'] === 'submitReport') {
        $data = [
            'student_number' => $_POST['studentNumber'],
            'student_name' => $_POST['studentName'],
            'case_type' => $_POST['caseType'],
            'severity' => $_POST['severity'] ?? 'Minor',
            'status' => 'Pending',
            'assigned_to' => null, // Will be assigned by DO
            'reported_by' => $_SESSION['user_id'] ?? null,
            'description' => $_POST['description'],
            'notes' => $_POST['notes'] ?? ''
        ];
        
        try {
            $newCaseId = createCase($data);
            updateStudentOffenseCount($data['student_number']);
            
            // Notify DO users of the new report
            notifyDOOnNewReport(
                $newCaseId,
                $data['student_name'],
                $data['case_type'],
                $data['severity']
            );
            
            echo json_encode([
                'success' => true, 
                'caseId' => $newCaseId, 
                'message' => 'Report submitted successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

$pageTitle = "Report Student Incident";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        if (localStorage.getItem("theme") === "dark") {
            document.documentElement.classList.add("dark");
        }
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
    
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                <!-- Page Title -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Report Student Incident
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Submit a new incident report for disciplinary review
                    </p>
                </div>

                <!-- Report Form -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
                    <form id="reportForm" class="p-6 space-y-6">
                        <!-- Student Number Lookup -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Student Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="studentNumber" 
                                   required 
                                   placeholder="e.g., 02000372341"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none">
                            <p id="studentLookupStatus" class="text-xs mt-1 hidden"></p>
                        </div>

                        <!-- Student Name (auto-filled) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Student Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="studentName" 
                                   required 
                                   readonly
                                   placeholder="Enter student number first..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100 cursor-not-allowed">
                        </div>

                        <!-- Student Info Display -->
                        <div id="studentInfoDisplay" class="hidden p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Grade/Year:</span>
                                    <span id="studentGrade" class="ml-2 font-medium text-gray-900 dark:text-gray-100"></span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Track/Course:</span>
                                    <span id="studentTrack" class="ml-2 font-medium text-gray-900 dark:text-gray-100"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Offense Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Offense Type <span class="text-red-500">*</span>
                            </label>
                            <select id="offenseType" 
                                    required 
                                    onchange="handleOffenseTypeChange()"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option value="">Select offense type...</option>
                                <option value="Minor">Minor</option>
                                <option value="Major">Major</option>
                            </select>
                        </div>

                        <!-- Case Type (hidden until offense type selected) -->
                        <div id="caseTypeDiv" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Case Type <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input list="caseTypeList" 
                                       id="caseType" 
                                       required
                                       onchange="handleCaseTypeChange()"
                                       placeholder="Type to search or select..."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <datalist id="caseTypeList">
                                    <!-- Populated dynamically -->
                                </datalist>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start typing to filter options</p>
                        </div>

                        <!-- Custom Offense Type (shown when "Others" selected) -->
                        <div id="customOffenseDiv" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Specify Offense Type <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="customOffense"
                                   placeholder="Enter custom offense type..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description <span id="descRequired" class="text-red-500" style="display: none;">*</span>
                            </label>
                            <textarea id="description" 
                                      rows="4"
                                      placeholder="Describe the incident in detail..."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none"></textarea>
                        </div>

                        <!-- Additional Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Additional Notes
                            </label>
                            <textarea id="notes" 
                                      rows="3"
                                      placeholder="Any additional information..."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 resize-none"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                            <button type="button" 
                                    onclick="window.history.back()"
                                    class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Student lookup with debounce
        let lookupTimeout;
        document.getElementById('studentNumber').addEventListener('input', (e) => {
            clearTimeout(lookupTimeout);
            const studentNumber = e.target.value.trim();
            const statusEl = document.getElementById('studentLookupStatus');
            const nameInput = document.getElementById('studentName');
            const infoDisplay = document.getElementById('studentInfoDisplay');

            if (studentNumber.length < 5) {
                statusEl.classList.add('hidden');
                nameInput.value = '';
                infoDisplay.classList.add('hidden');
                return;
            }

            statusEl.textContent = 'Looking up student...';
            statusEl.className = 'text-xs mt-1 text-blue-600 dark:text-blue-400';
            statusEl.classList.remove('hidden');

            lookupTimeout = setTimeout(async () => {
                try {
                    const formData = new FormData();
                    formData.append('ajax', '1');
                    formData.append('action', 'lookupStudent');
                    formData.append('studentNumber', studentNumber);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        nameInput.value = data.student.full_name;
                        nameInput.readOnly = true;
                        nameInput.className = 'w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100 cursor-not-allowed';
                        
                        document.getElementById('studentGrade').textContent = data.student.grade_year;
                        document.getElementById('studentTrack').textContent = data.student.track_course;
                        infoDisplay.classList.remove('hidden');
                        
                        statusEl.textContent = '✓ Student found';
                        statusEl.className = 'text-xs mt-1 text-green-600 dark:text-green-400';
                    } else {
                        nameInput.value = '';
                        nameInput.readOnly = true;
                        nameInput.placeholder = 'Student not found in database';
                        nameInput.className = 'w-full px-3 py-2 border border-red-300 dark:border-red-600 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-900 dark:text-red-100 cursor-not-allowed';
                        infoDisplay.classList.add('hidden');
                        
                        statusEl.textContent = '⚠ Student not found. Please check the student number.';
                        statusEl.className = 'text-xs mt-1 text-red-600 dark:text-red-400';
                    }
                } catch (error) {
                    console.error('Lookup error:', error);
                    statusEl.textContent = '⚠ Error looking up student';
                    statusEl.className = 'text-xs mt-1 text-red-600 dark:text-red-400';
                }
            }, 500);
        });

        // Handle offense type change
        async function handleOffenseTypeChange() {
            const offenseType = document.getElementById('offenseType').value;
            const caseTypeDiv = document.getElementById('caseTypeDiv');
            const caseTypeInput = document.getElementById('caseType');
            const datalist = document.getElementById('caseTypeList');
            
            if (!offenseType) {
                caseTypeDiv.style.display = 'none';
                return;
            }
            
            caseTypeDiv.style.display = 'block';
            
            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'getOffenseTypes');
                formData.append('category', offenseType);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const hasOthers = data.offenses.some(o => o.offense_name === 'Others');
                    const options = data.offenses.map(o => 
                        `<option value="${o.offense_name}">${o.offense_name}</option>`
                    ).join('');
                    const othersOption = !hasOthers ? '<option value="Others">Others (Specify in description)</option>' : '';
                    
                    datalist.innerHTML = options + othersOption;
                }
            } catch (error) {
                console.error('Error loading offense types:', error);
            }
            
            caseTypeInput.value = '';
        }

        // Handle case type change
        function handleCaseTypeChange() {
            const caseType = document.getElementById('caseType').value;
            const description = document.getElementById('description');
            const descRequired = document.getElementById('descRequired');
            const customOffenseDiv = document.getElementById('customOffenseDiv');
            const customOffenseInput = document.getElementById('customOffense');
            
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

        // Form submission
        document.getElementById('reportForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const studentName = document.getElementById('studentName').value;
            const offenseType = document.getElementById('offenseType').value;
            const caseType = document.getElementById('caseType').value;
            const description = document.getElementById('description').value;
            
            // Validation
            if (!studentName) {
                showNotification('Please enter a valid student number', 'error');
                return;
            }
            
            if (!offenseType) {
                showNotification('Please select an Offense Type', 'error');
                return;
            }
            
            if (!caseType) {
                showNotification('Please select a Case Type', 'error');
                return;
            }
            
            if (caseType === 'Others') {
                if (!description.trim()) {
                    showNotification('Description is required when Case Type is "Others"', 'error');
                    return;
                }
                const customOffense = document.getElementById('customOffense').value;
                if (!customOffense.trim()) {
                    showNotification('Please specify the offense type', 'error');
                    return;
                }
            }
            
            // Submit
            showNotification('Submitting report...', 'info');
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'submitReport');
            formData.append('studentNumber', document.getElementById('studentNumber').value);
            formData.append('studentName', studentName);
            formData.append('caseType', caseType === 'Others' ? document.getElementById('customOffense').value : caseType);
            formData.append('severity', offenseType);
            formData.append('description', description);
            formData.append('notes', document.getElementById('notes').value);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Report submitted successfully!', 'success');
                    setTimeout(() => window.history.back(), 1500);
                } else {
                    showNotification('Error: ' + (data.error || 'Failed to submit report'), 'error');
                }
            } catch (error) {
                console.error('Submit error:', error);
                showNotification('Error submitting report. Please try again.', 'error');
            }
        });

        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>