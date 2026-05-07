<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = "Terms and Conditions";
$adminName = getFormattedUserName() ?? ($_SESSION['admin_name'] ?? 'Admin');
$isSuperAdmin = $_SESSION['user_role'] === 'super_admin';

// Only super admins can access this page
if (!$isSuperAdmin) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>

  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Quill WYSIWYG Editor -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  
  <script>
      // Ensure tailwind uses class-based dark mode
      tailwind.config = {
          darkMode: 'class'
      }

      // Restore saved theme on page load
      if (localStorage.getItem("theme") === "dark") {
          document.documentElement.classList.add("dark");
      }

      function toggleDarkMode() {
          const html = document.documentElement;
          const isDark = html.classList.toggle("dark");
          localStorage.setItem("theme", isDark ? "dark" : "light");
      }

      let quillEditors = {};
      let editMode = false;
      let hasUnsavedChanges = false;

      // Track unsaved changes
      window.addEventListener('beforeunload', (e) => {
          if (hasUnsavedChanges) {
              e.preventDefault();
              e.returnValue = '';
          }
      });

      const sectionIds = [
          'purpose-and-use',
          'confidentiality-and-data-protection',
          'user-responsibilities',
          'compliance-with-school-policies',
          'liability-limitation',
          'modification-of-terms',
          'termination',
          'role-based-access-control',
          'monitoring-and-audit-logging',
          'data-retention-and-deletion',
          'security-and-incident-response',
          'system-availability-and-maintenance',
          'account-management',
          'governing-law-and-jurisdiction'
      ];

      document.addEventListener("DOMContentLoaded", () => {
          // Load terms content on page load
          loadTermsContent();
      });

      async function loadTermsContent() {
          try {
              const formData = new FormData();
              formData.append('action', 'getAllContent');
              
              const response = await fetch('../shared/termsHandler.php', {
                  method: 'POST',
                  body: formData
              });
              
              const data = await response.json();
              if (data.success && data.content) {
                  // For each saved section, update its content div
                  Object.entries(data.content).forEach(([sectionId, content]) => {
                      const section = document.getElementById(sectionId);
                      if (section) {
                          const contentDiv = section.querySelector('.terms-content');
                          if (contentDiv) {
                              // Store original content in data attribute for cancel functionality
                              if (!contentDiv.hasAttribute('data-original-html')) {
                                  contentDiv.setAttribute('data-original-html', contentDiv.innerHTML);
                              }
                              // Update with saved content
                              contentDiv.innerHTML = content;
                          }
                      }
                  });
              }
          } catch (error) {
              console.error('Error loading terms content:', error);
          }
      }

      function initializeQuillEditors() {
          // Initialize all Quill editors only when entering edit mode
          sectionIds.forEach(sectionId => {
              const editor = document.getElementById(`quill-${sectionId}`);
              if (editor && !quillEditors[sectionId]) {
                  quillEditors[sectionId] = new Quill(`#quill-${sectionId}`, {
                      theme: 'snow',
                      modules: {
                          toolbar: [
                              [{ 'header': [1, 2, 3, false] }],
                              ['bold', 'italic', 'underline', 'strike'],
                              ['blockquote', 'code-block'],
                              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                              ['link'],
                              ['clean']
                          ]
                      }
                  });

                  // Load content into editor
                  const contentDiv = document.querySelector(`#${sectionId} .terms-content`);
                  if (contentDiv) {
                      quillEditors[sectionId].root.innerHTML = contentDiv.innerHTML;
                  }

                  // Track changes
                  quillEditors[sectionId].on('text-change', () => {
                      hasUnsavedChanges = true;
                  });
              }
          });
      }

      async function saveAllChanges() {
          if (!editMode) {
              alert('Not in edit mode');
              return;
          }

          try {
              const sectionsData = {};
              
              Object.entries(quillEditors).forEach(([sectionId, editor]) => {
                  sectionsData[sectionId] = editor.root.innerHTML;
              });

              const formData = new FormData();
              formData.append('action', 'updateContent');
              formData.append('sections', JSON.stringify(sectionsData));

              const response = await fetch('../shared/termsHandler.php', {
                  method: 'POST',
                  body: formData
              });

              const data = await response.json();
              if (data.success) {
                  alert('Terms and Conditions updated successfully!');
                  hasUnsavedChanges = false;
                  loadTermsContent();
                  toggleEditMode();
              } else {
                  alert('Error saving terms: ' + (data.message || 'Unknown error'));
              }
          } catch (error) {
              console.error('Error saving terms:', error);
              alert('Error saving terms: ' + error.message);
          }
      }

      function toggleEditMode() {
          editMode = !editMode;
          const editBtn = document.getElementById('editBtn');
          const saveBtn = document.getElementById('saveBtn');
          const cancelBtn = document.getElementById('cancelBtn');
          const editors = document.querySelectorAll('.ql-container');
          const toolbars = document.querySelectorAll('.ql-toolbar');

          if (editMode) {
              initializeQuillEditors();
              editBtn.style.display = 'none';
              saveBtn.style.display = 'inline-block';
              cancelBtn.style.display = 'inline-block';
              editors.forEach(e => e.classList.remove('hidden'));
              toolbars.forEach(e => e.classList.remove('hidden'));
              document.querySelectorAll('.terms-content').forEach(e => e.classList.add('hidden'));
          } else {
              editBtn.style.display = 'inline-block';
              saveBtn.style.display = 'none';
              cancelBtn.style.display = 'none';
              editors.forEach(e => e.classList.add('hidden'));
              toolbars.forEach(e => e.classList.add('hidden'));
              document.querySelectorAll('.terms-content').forEach(e => e.classList.remove('hidden'));
          }
      }

      function cancelEditing() {
          if (hasUnsavedChanges && !confirm('You have unsaved changes. Are you sure you want to cancel?')) {
              return;
          }
          hasUnsavedChanges = false;
          loadTermsContent();
          toggleEditMode();
      }
  </script>
  
  <style>
      html { scroll-behavior: smooth; }

      [id] {
          scroll-margin-top: 7rem;
      }

      .custom-scrollbar::-webkit-scrollbar {
          width: 8px;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb {
          background-color: rgba(156, 163, 175, 0.5);
          border-radius: 4px;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb:hover {
          background-color: rgba(96, 165, 250, 0.8);
      }

      .ql-container {
          font-size: 16px;
          font-family: inherit;
      }

      .ql-editor {
          padding: 12px;
          min-height: 200px;
      }

      .ql-toolbar {
          background-color: #f3f4f6;
          border-radius: 4px 4px 0 0;
      }

      .dark .ql-toolbar {
          background-color: #1f2937;
      }

      .dark .ql-container {
          border-color: #374151;
      }

      .dark .ql-editor {
          color: #e5e7eb;
      }

      .dark .ql-editor.ql-blank::before {
          color: #6b7280;
      }
  </style>

</head>

<body class="bg-gray-50 dark:bg-[#1E293B] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <!-- Fixed Header -->
  <header class="fixed top-0 left-64 right-0 z-50 bg-white dark:bg-[#1E293B] border-b border-gray-200 dark:border-slate-700 shadow-sm">
    <?php include __DIR__ . '/../../includes/header.php'; ?>
  </header>

  <!-- Main Container -->
  <div class="ml-64 h-screen flex">
    <!-- Main Content Area (Scrollable) -->
    <main class="flex-1 overflow-hidden custom-scrollbar">
      <div class="w-full h-full pt-28 px-8">
        <!-- Content Column -->
        <div class="w-full h-full">
          <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 
                rounded-lg shadow-sm pl-20 pb-20 pr-20 pt-8
                overflow-y-auto max-h-[calc(100vh-9rem)] custom-scrollbar">

            <!-- Header title + Admin buttons -->
            <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
              <h2 class="text-5xl font-bold text-gray-800 dark:text-gray-100">
                <?php echo htmlspecialchars($pageTitle); ?>
              </h2>
              <div class="flex gap-3">
                <button
                  id="editBtn"
                  onclick="toggleEditMode()"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg shadow transition"
                >
                  Edit Terms
                </button>
                <button
                  id="saveBtn"
                  onclick="saveAllChanges()"
                  style="display: none;"
                  class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg shadow transition"
                >
                  Save Changes
                </button>
                <button
                  id="cancelBtn"
                  onclick="cancelEditing()"
                  style="display: none;"
                  class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-4 py-2 rounded-lg shadow transition"
                >
                  Cancel
                </button>
              </div>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-8">
                STI Discipline Office Management System | Last updated: <?php echo date('F d, Y'); ?>
            </p>

            <!-- Terms Sections -->
            <div class="space-y-8 text-justify text-lg">

              <div id="purpose-and-use" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-purpose-and-use" class="ql-container hidden"></div>
              </div>

              <div id="confidentiality-and-data-protection" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-confidentiality-and-data-protection" class="ql-container hidden"></div>
              </div>

              <div id="user-responsibilities" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-user-responsibilities" class="ql-container hidden"></div>
              </div>

              <div id="compliance-with-school-policies" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-compliance-with-school-policies" class="ql-container hidden"></div>
              </div>

              <div id="liability-limitation" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-liability-limitation" class="ql-container hidden"></div>
              </div>

              <div id="modification-of-terms" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-modification-of-terms" class="ql-container hidden"></div>
              </div>

              <div id="termination" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-termination" class="ql-container hidden"></div>
              </div>

              <div id="role-based-access-control" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-role-based-access-control" class="ql-container hidden"></div>
              </div>

              <div id="monitoring-and-audit-logging" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-monitoring-and-audit-logging" class="ql-container hidden"></div>
              </div>

              <div id="data-retention-and-deletion" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-data-retention-and-deletion" class="ql-container hidden"></div>
              </div>

              <div id="security-and-incident-response" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-security-and-incident-response" class="ql-container hidden"></div>
              </div>

              <div id="system-availability-and-maintenance" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-system-availability-and-maintenance" class="ql-container hidden"></div>
              </div>

              <div id="account-management" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-account-management" class="ql-container hidden"></div>
              </div>

              <div id="governing-law-and-jurisdiction" class="space-y-4">
                <div class="terms-content"></div>
                <div id="quill-governing-law-and-jurisdiction" class="ql-container hidden"></div>
              </div>

            </div>

          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>
