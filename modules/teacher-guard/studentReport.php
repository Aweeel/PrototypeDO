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
    
    
    if ($_POST['action'] === 'submitReport') {
        $data = [
            'student_number' => $_POST['studentNumber'],
            'student_name' => $_POST['studentName'],
            'case_type' => $_POST['caseType'],
            'severity' => $_POST['severity'] ?? 'Minor', // Default to Minor if not specified
            'status' => 'Pending',
            'assigned_to' => null, // Will be assigned by DO
            'reported_by' => $_SESSION['user_id'] ?? null,
            'description' => $_POST['description'],
            'notes' => $_POST['notes'] ?? ''
        ];
        
        try {
            $newCaseId = createCase($data);
            
            // Check if duplicate violation was detected
            if ($newCaseId === false) {
                echo json_encode(['success' => false, 'error' => 'A violation of this type has already been recorded for this student today']);
                exit;
            }
            
            updateStudentOffenseCount($data['student_number']);
            
            error_log("studentReport: New case created - $newCaseId for student {$data['student_number']}");
            
            // Handle image attachments
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $attachmentPaths = [];
                $imageCount = count($_FILES['images']['name']);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];
                    
                    $attachmentPath = saveAttachmentForCase($newCaseId, $file);
                    if ($attachmentPath) {
                        $attachmentPaths[] = $attachmentPath;
                        error_log("studentReport: Image attachment saved - $attachmentPath");
                    } else {
                        error_log("studentReport: Failed to save image attachment - {$file['name']}");
                    }
                }
                
                // Add attachments to case if any were saved
                if (!empty($attachmentPaths)) {
                    addCaseAttachments($newCaseId, $attachmentPaths);
                    error_log("studentReport: Added " . count($attachmentPaths) . " attachments to case");
                }
            }
            
            // Notify DO users of the new report
            notifyDOOnNewReport(
                $newCaseId,
                $data['student_name'],
                $data['case_type'],
                $data['severity']
            );
            
            // Notify the student that they have been reported
            $notificationSent = notifyStudentOnNewCase(
                $data['student_number'],
                $newCaseId,
                $data['case_type'],
                $data['severity']
            );
            
            if ($notificationSent) {
                error_log("studentReport: Student notification sent successfully");
            } else {
                error_log("studentReport: Student notification failed or student has no user_id");
            }
            
            echo json_encode([
                'success' => true, 
                'caseId' => $newCaseId, 
                'message' => 'Report submitted successfully'
            ]);
        } catch (Exception $e) {
            error_log("studentReport: Exception - " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

$pageTitle = "Report Student Incident";
$adminName = getFormattedUserName();

// Fetch top 5 most common case types with their severity categories and descriptions
$sql = "SELECT case_type, 
        ISNULL((SELECT TOP 1 category FROM offense_types WHERE offense_name = cases.case_type), 'Minor') as severity,
        ISNULL((SELECT TOP 1 description FROM offense_types WHERE offense_name = cases.case_type), '') as description,
        COUNT(*) as count
        FROM cases
        WHERE is_archived = 0
        GROUP BY case_type
        ORDER BY count DESC
        OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY";
$topCaseTypes = fetchAll($sql);
$caseTypesList = array_column($topCaseTypes, 'case_type');
$caseTypeSeverityMap = array_combine(
    array_column($topCaseTypes, 'case_type'),
    array_column($topCaseTypes, 'severity')
);
$caseTypeDescriptionMap = array_combine(
    array_column($topCaseTypes, 'case_type'),
    array_column($topCaseTypes, 'description')
);
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
    
    <!-- Image Upload Consent Modal -->
    <div id="imageConsentModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 pt-0.5">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            Consent Required
                        </h2>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-6 leading-relaxed">
                    Before capturing or uploading any photos related to an incident report, ensure that <strong>written or verbal consent has been obtained from all individuals who appear in the image.</strong>
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-6">
                    Uploading photos without proper consent may violate privacy laws and institutional policies. By clicking "I Understand," you acknowledge this requirement.
                </p>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-slate-700 flex justify-end">
                <button type="button" 
                        onclick="acknowledgeImageConsent()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    I Understand
                </button>
            </div>
        </div>
    </div>
    
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
                                   placeholder="e.g., 02000000001"
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

                        <!-- Case Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Case Type <span class="text-red-500">*</span>
                            </label>
                            <select id="caseType" 
                                    required 
                                    onchange="handleCaseTypeChange()"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100">
                                <option value="">Select case type...</option>
                                <?php foreach ($caseTypesList as $caseType): ?>
                                    <option value="<?php echo htmlspecialchars($caseType); ?>"><?php echo htmlspecialchars($caseType); ?></option>
                                <?php endforeach; ?>
                                <option value="Other">Other (Specify in description)</option>
                            </select>
                        </div>

                        <!-- Severity (Auto-detected) -->
                        <div style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Incident Severity
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" id="severity" value="">
                                <div class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-100 dark:bg-slate-600 text-gray-900 dark:text-gray-100">
                                    <span id="severityDisplay" class="font-medium">Select a case type...</span>
                                </div>
                                <span id="severityBadge" class="px-3 py-2 rounded-lg font-semibold text-sm hidden" style="min-width: 80px; text-align: center;"></span>
                            </div>
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

                        <!-- Image Attachments -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Attach Images
                                <span class="relative inline-block ml-1 group">
                                    <button type="button" class="inline-flex items-center justify-center w-3 h-3 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600 rounded-full transition-colors" title="Consent information">
                                        i
                                    </button>
                                    <div class="absolute hidden group-hover:block bg-gray-900 dark:bg-gray-800 text-white text-xs rounded-lg py-2 px-3 whitespace-normal w-48 top-full left-1/2 transform -translate-x-1/2 mt-2 z-10 pointer-events-none shadow-lg">
                                        Written or verbal consent required from all people in photos
                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-b-gray-900 dark:border-b-gray-800"></div>
                                    </div>
                                </span>
                            </label>
                            
                            <!-- Drag and Drop Area -->
                            <div id="uploadDropZone" 
                                 class="relative w-full p-8 border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-800/50 hover:bg-gray-100 dark:hover:bg-slate-800/70 transition-colors cursor-pointer"
                                 ondrop="handleDropWithConsent(event)"
                                 ondragover="handleDragOver(event)"
                                 ondragleave="handleDragLeave(event)">
                                
                                <input type="file" 
                                       id="imageAttachments" 
                                       multiple 
                                       accept="image/*"
                                       capture="environment"
                                       onchange="handleImageSelectWithConsent()"
                                       class="hidden">
                                
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="21" cy="34" r="8" stroke-width="2"/>
                                        <path d="M35 12l-8 8m0 0l-8-8m8 8v-8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <button type="button" 
                                                onclick="openImageConsentModal(event)"
                                                class="text-blue-600 dark:text-blue-400 hover:underline">
                                            Click to upload
                                        </button>
                                        or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        JPG, PNG, WebP (Max 5MB each)
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Image Preview Container -->
                            <div id="imagePreviewContainer" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 hidden">
                                <!-- Previews will be added here -->
                            </div>
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
        // Severity and description mappings from server
        const caseTypeSeverityMap = <?php echo json_encode($caseTypeSeverityMap); ?>;
        const caseTypeDescriptionMap = <?php echo json_encode($caseTypeDescriptionMap); ?>;

        // Notification function - define early so it's available to all handlers
        function showNotification(message, type = 'info') {
            // Create notification element
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

        // Test if form exists and button click works
        console.log('Script loaded');
        
        const reportFormTest = document.getElementById('reportForm');
        console.log('Form found:', !!reportFormTest);
        
        // Add click listener to submit button directly
        const submitBtn = document.querySelector('button[type="submit"]');
        console.log('Submit button found:', !!submitBtn);
        
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked');
            });
        }
        
        // Original form submission handler
        const reportForm = document.getElementById('reportForm');
        console.log('Report form element:', reportForm);
        
        if (reportForm) {
            reportForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                console.log('Form submit event triggered');
                
                const studentName = document.getElementById('studentName').value;
                const caseType = document.getElementById('caseType').value;
                const description = document.getElementById('description').value;
                
                console.log('Form values - Student:', studentName, 'Case Type:', caseType, 'Description:', description);
                
                // Validation
                if (!studentName) {
                    showNotification('Please enter a valid student number', 'error');
                    return;
                }
                
                if (!caseType) {
                    showNotification('Please select a Case Type', 'error');
                    return;
                }
                
                if (caseType === 'Other') {
                    if (!description.trim()) {
                        showNotification('Description is required when Case Type is "Other"', 'error');
                        return;
                    }
                }
                
                // Submit
                console.log('Validation passed, submitting...');
                showNotification('Submitting report...', 'info');
                
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'submitReport');
                formData.append('studentNumber', document.getElementById('studentNumber').value);
                formData.append('studentName', studentName);
                formData.append('caseType', caseType);
                formData.append('severity', document.getElementById('severity').value);
                formData.append('description', description);
                formData.append('notes', document.getElementById('notes').value);
                
                // Add image files
                const imageFiles = document.getElementById('imageAttachments').files;
                for (let i = 0; i < imageFiles.length; i++) {
                    formData.append('images[]', imageFiles[i]);
                }
                
                try {
                    console.log('Sending fetch request...');
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    console.log('Response status:', response.status);
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        showNotification('Report submitted successfully!', 'success');
                        setTimeout(() => window.history.back(), 1500);
                    } else {
                        showNotification('Error: ' + (data.error || 'Failed to submit report'), 'error');
                        console.error('Server error:', data.error);
                    }
                } catch (error) {
                    console.error('Submit error:', error);
                    showNotification('Error submitting report. Please try again.', 'error');
                }
            });
        } else {
            console.error('Report form not found in DOM');
        }

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

        // Handle case type change
        function handleCaseTypeChange() {
            const caseType = document.getElementById('caseType').value;
            const description = document.getElementById('description');
            const descRequired = document.getElementById('descRequired');
            const severityInput = document.getElementById('severity');
            const severityDisplay = document.getElementById('severityDisplay');
            const severityBadge = document.getElementById('severityBadge');
            
            // Handle severity auto-detection and description auto-population
            if (caseType === '') {
                severityInput.value = '';
                severityDisplay.textContent = 'Select a case type...';
                severityBadge.classList.add('hidden');
                description.value = '';
                description.required = false;
                descRequired.style.display = 'none';
            } else if (caseType === 'Other') {
                // For "Other" cases, default to Minor and clear description
                severityInput.value = 'Minor';
                severityDisplay.textContent = 'Minor';
                severityBadge.textContent = 'Minor';
                severityBadge.className = 'px-3 py-2 rounded-lg font-semibold text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
                severityBadge.classList.remove('hidden');
                description.value = '';
                description.required = true;
                descRequired.style.display = 'inline';
            } else if (caseTypeSeverityMap[caseType]) {
                const severity = caseTypeSeverityMap[caseType];
                severityInput.value = severity;
                severityDisplay.textContent = severity;
                
                // Apply appropriate badge styling
                if (severity === 'Major') {
                    severityBadge.textContent = severity;
                    severityBadge.className = 'px-3 py-2 rounded-lg font-semibold text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
                } else {
                    severityBadge.textContent = severity;
                    severityBadge.className = 'px-3 py-2 rounded-lg font-semibold text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
                }
                severityBadge.classList.remove('hidden');
                
                // Auto-populate description if available
                if (caseTypeDescriptionMap[caseType]) {
                    description.value = caseTypeDescriptionMap[caseType];
                }
                
                description.required = false;
                descRequired.style.display = 'none';
            }
        }

        // Handle drag over
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropZone = document.getElementById('uploadDropZone');
            dropZone.classList.add('border-blue-400', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            dropZone.classList.remove('border-gray-300', 'dark:border-slate-600', 'bg-gray-50', 'dark:bg-slate-800/50');
        }

        // Handle drag leave
        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropZone = document.getElementById('uploadDropZone');
            dropZone.classList.remove('border-blue-400', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            dropZone.classList.add('border-gray-300', 'dark:border-slate-600', 'bg-gray-50', 'dark:bg-slate-800/50');
        }

        // Handle drop
        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropZone = document.getElementById('uploadDropZone');
            dropZone.classList.remove('border-blue-400', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            dropZone.classList.add('border-gray-300', 'dark:border-slate-600', 'bg-gray-50', 'dark:bg-slate-800/50');
            
            const files = e.dataTransfer.files;
            document.getElementById('imageAttachments').files = files;
            handleImageSelect();
        }

        // Handle image selection and preview
        function handleImageSelect() {
            const fileInput = document.getElementById('imageAttachments');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const files = fileInput.files;
            
            previewContainer.innerHTML = '';
            
            if (files.length === 0) {
                previewContainer.classList.add('hidden');
                return;
            }
            
            previewContainer.classList.remove('hidden');
            
            Array.from(files).forEach((file, index) => {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification(`File "${file.name}" exceeds 5MB limit`, 'error');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'relative bg-white dark:bg-slate-700 rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-slate-600 hover:shadow-md transition-shadow';
                    previewDiv.innerHTML = `
                        <div class="relative">
                            <img src="${e.target.result}" alt="Preview" class="w-full h-40 object-cover">
                            <button type="button" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg leading-none transition-colors shadow-md" onclick="removeImage(${index})" title="Remove image">×</button>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-slate-800 border-t border-gray-200 dark:border-slate-600">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">${file.name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${(file.size / 1024).toFixed(2)} KB</p>
                        </div>
                    `;
                    previewContainer.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            });
        }
        
        // Remove image from selection
        function removeImage(index) {
            const fileInput = document.getElementById('imageAttachments');
            const dataTransfer = new DataTransfer();
            const files = fileInput.files;
            
            Array.from(files).forEach((file, i) => {
                if (i !== index) {
                    dataTransfer.items.add(file);
                }
            });
            
            fileInput.files = dataTransfer.files;
            handleImageSelect();
        }

        // Image upload consent modal functions
        function openImageConsentModal(e) {
            e.preventDefault();
            if (!sessionStorage.getItem('imageConsentAcknowledged')) {
                document.getElementById('imageConsentModal').classList.remove('hidden');
            } else {
                document.getElementById('imageAttachments').click();
            }
        }

        function acknowledgeImageConsent() {
            sessionStorage.setItem('imageConsentAcknowledged', 'true');
            document.getElementById('imageConsentModal').classList.add('hidden');
            document.getElementById('imageAttachments').click();
        }

        function handleImageSelectWithConsent() {
            if (!sessionStorage.getItem('imageConsentAcknowledged')) {
                document.getElementById('imageConsentModal').classList.remove('hidden');
                // Clear the file input
                document.getElementById('imageAttachments').value = '';
                return;
            }
            handleImageSelect();
        }

        function handleDropWithConsent(event) {
            if (!sessionStorage.getItem('imageConsentAcknowledged')) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById('imageConsentModal').classList.remove('hidden');
                return;
            }
            handleDrop(event);
        }

    </script>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>