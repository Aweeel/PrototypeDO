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
      let editingSections = new Set();
      let hasUnsavedChanges = {};

        function showConfirmDialog(options = {}) {
          const {
            title = 'Confirm',
            message = '',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            confirmClass = 'bg-blue-600 hover:bg-blue-700',
          } = options;

          return new Promise((resolve) => {
            const existing = document.getElementById('termsConfirmDialog');
            if (existing) {
              existing.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'termsConfirmDialog';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4';
            modal.innerHTML = `
              <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                  <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">${title}</h2>
                </div>
                <div class="p-6">
                  <p class="text-sm text-gray-700 dark:text-gray-300">${message}</p>
                </div>
                <div class="flex gap-3 p-6 pt-0 justify-end">
                  <button type="button" data-cancel class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">${cancelText}</button>
                  <button type="button" data-confirm class="px-4 py-2 rounded-lg text-white transition-colors ${confirmClass}">${confirmText}</button>
                </div>
              </div>
            `;

            const finish = (value) => {
              modal.remove();
              resolve(value);
            };

            modal.querySelector('[data-confirm]').addEventListener('click', () => finish(true));
            modal.querySelector('[data-cancel]').addEventListener('click', () => finish(false));
            modal.addEventListener('click', (event) => {
              if (event.target === modal) {
                finish(false);
              }
            });

            document.body.appendChild(modal);
          });
        }

      // Track unsaved changes
      window.addEventListener('beforeunload', (e) => {
          if (Object.values(hasUnsavedChanges).some(v => v)) {
              e.preventDefault();
              e.returnValue = '';
          }
      });

      function createPencilIcon() {
          const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.setAttribute('width', '18');
          svg.setAttribute('height', '18');
          svg.setAttribute('viewBox', '0 0 24 24');
          svg.setAttribute('fill', 'currentColor');
          svg.setAttribute('stroke', 'none');
          svg.setAttribute('style', 'vertical-align: middle; display: inline; cursor: pointer;');
          
          const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          path.setAttribute('d', 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7m-13-3l9.5-9.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z');
          
          svg.appendChild(path);
          return svg;
      }

          function getTermsSectionElements(sectionId) {
            const section = document.getElementById(sectionId);
            return {
              section,
              contentDiv: section ? section.querySelector('.terms-content') : null,
              editorWrapper: document.getElementById(`editor-wrapper-${sectionId}`),
              iconContainer: document.getElementById(`icon-${sectionId}`),
              buttonsContainer: document.getElementById(`buttons-${sectionId}`)
            };
          }

          function resetTermsEditorHost(sectionId) {
            const { editorWrapper } = getTermsSectionElements(sectionId);
            if (editorWrapper) {
              editorWrapper.innerHTML = `<div id="quill-${sectionId}" class="ql-container"></div>`;
            }
          }

          function destroyTermsEditor(sectionId) {
            if (quillEditors[sectionId]) {
              delete quillEditors[sectionId];
            }
            resetTermsEditorHost(sectionId);
          }

          function syncTermsEditControls(sectionId, isEditing) {
            const { contentDiv, editorWrapper, iconContainer, buttonsContainer } = getTermsSectionElements(sectionId);

            if (contentDiv) {
              contentDiv.style.display = isEditing ? 'none' : 'block';
            }
            if (editorWrapper) {
              editorWrapper.style.display = isEditing ? 'block' : 'none';
            }
            if (iconContainer) {
              iconContainer.style.display = isEditing ? 'none' : 'inline';
            }
            if (buttonsContainer) {
              buttonsContainer.style.display = isEditing ? 'flex' : 'none';
            }
          }

          function startTermsEditing(sectionId) {
            editingSections.add(sectionId);
            initializeQuillEditor(sectionId);
            hasUnsavedChanges[sectionId] = false;
            syncTermsEditControls(sectionId, true);
          }

          function stopTermsEditing(sectionId) {
            editingSections.delete(sectionId);
            destroyTermsEditor(sectionId);
            hasUnsavedChanges[sectionId] = false;
            syncTermsEditControls(sectionId, false);
          }

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
          
          // Add edit icons and buttons to each section
          sectionIds.forEach(sectionId => {
              const section = document.getElementById(sectionId);
              if (section) {
                  const header = section.querySelector('h4');
                  
                  // Create icon container
                  const iconContainer = document.createElement('span');
                  iconContainer.className = 'edit-icon-container ml-2 cursor-pointer text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition';
                  iconContainer.id = `icon-${sectionId}`;
                  
                  const icon = createPencilIcon();
                  iconContainer.appendChild(icon);
                  iconContainer.onclick = () => toggleSectionEditMode(sectionId);
                  
                  // Create buttons container (initially hidden)
                  const buttonsContainer = document.createElement('div');
                  buttonsContainer.className = 'edit-buttons-container flex gap-2';
                  buttonsContainer.id = `buttons-${sectionId}`;
                  buttonsContainer.style.display = 'none';
                  
                  const cancelBtn = document.createElement('button');
                  cancelBtn.className = 'cancel-btn-section bg-gray-600 hover:bg-gray-700 text-white font-medium px-3 py-1 rounded-lg shadow transition';
                  cancelBtn.textContent = 'Cancel';
                  cancelBtn.onclick = () => cancelSectionEditing(sectionId);
                  
                  const saveBtn = document.createElement('button');
                  saveBtn.className = 'save-btn-section bg-green-600 hover:bg-green-700 text-white font-medium px-3 py-1 rounded-lg shadow transition';
                  saveBtn.textContent = 'Save';
                  saveBtn.onclick = () => saveSectionChanges(sectionId);
                  
                  buttonsContainer.appendChild(cancelBtn);
                  buttonsContainer.appendChild(saveBtn);
                  
                  // Create header wrapper
                  if (header) {
                      const headerWrapper = document.createElement('div');
                      headerWrapper.className = 'flex items-center justify-between';
                      
                      const titleWrapper = document.createElement('div');
                      titleWrapper.className = 'flex items-center';
                      titleWrapper.appendChild(header.cloneNode(true));
                      titleWrapper.appendChild(iconContainer);
                      
                      headerWrapper.appendChild(titleWrapper);
                      headerWrapper.appendChild(buttonsContainer);
                      
                      header.replaceWith(headerWrapper);
                  }
              }
          });
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
                              // Update with saved content
                              contentDiv.innerHTML = content;
                        contentDiv.setAttribute('data-original-html', content);
                          }
                      }
                  });
              }
          } catch (error) {
              alert('Error loading terms content. Please refresh the page.');
          }
      }

      function initializeQuillEditor(sectionId) {
          const editor = document.getElementById(`quill-${sectionId}`);
          if (editor && !quillEditors[sectionId]) {
              resetTermsEditorHost(sectionId);
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
              const { contentDiv } = getTermsSectionElements(sectionId);
              if (contentDiv) {
                  const savedHtml = contentDiv.getAttribute('data-original-html') || contentDiv.innerHTML;
                  quillEditors[sectionId].root.innerHTML = savedHtml;
              }

                const initialEditorHtml = quillEditors[sectionId].root.innerHTML;

              // Track changes
              quillEditors[sectionId].on('text-change', () => {
                  hasUnsavedChanges[sectionId] = quillEditors[sectionId].root.innerHTML !== initialEditorHtml;
                });
          }
      }

      async function saveSectionChanges(sectionId) {
          if (!editingSections.has(sectionId)) {
              alert('Section not in edit mode');
              return;
          }

          try {
              const editor = quillEditors[sectionId];
              const content = editor.root.innerHTML;

              if (hasUnsavedChanges[sectionId]) {
              const confirmed = await showConfirmDialog({
                title: 'Save Changes',
                message: 'Save the changes you made to this section?',
                confirmText: 'Save',
                confirmClass: 'bg-green-600 hover:bg-green-700'
              });

              if (!confirmed) {
                return;
              }
            }

              const formData = new FormData();
              formData.append('action', 'updateContent');
              formData.append('sections', JSON.stringify({ [sectionId]: content }));

              const response = await fetch('../shared/termsHandler.php', {
                  method: 'POST',
                  body: formData
              });

              const data = await response.json();
              if (data.success) {
                  hasUnsavedChanges[sectionId] = false;
                  
                  // Update the content div
                  const { contentDiv } = getTermsSectionElements(sectionId);
                  if (contentDiv) {
                      contentDiv.innerHTML = content;
                    contentDiv.setAttribute('data-original-html', content);
                  }
                  
                  // Exit edit mode
                  stopTermsEditing(sectionId);
              } else {
                  alert('Error saving section: ' + (data.message || 'Unknown error'));
              }
          } catch (error) {
              alert('Error saving section: ' + error.message);
          }
      }

      function toggleSectionEditMode(sectionId) {
          const isEditing = editingSections.has(sectionId);

          if (isEditing) {
              stopTermsEditing(sectionId);
              return;
          }

          startTermsEditing(sectionId);
      }

        async function cancelSectionEditing(sectionId) {
          return new Promise(async (resolve) => {
            if (hasUnsavedChanges[sectionId]) {
              const confirmed = await showConfirmDialog({
                title: 'Discard Changes',
                message: 'You have unsaved changes. Are you sure you want to cancel?',
                confirmText: 'Discard',
                confirmClass: 'bg-red-600 hover:bg-red-700'
              });

              if (!confirmed) {
                resolve(false);
                return;
              }
            }
            const { contentDiv } = getTermsSectionElements(sectionId);
            if (contentDiv && contentDiv.hasAttribute('data-original-html')) {
              contentDiv.innerHTML = contentDiv.getAttribute('data-original-html');
            }
            stopTermsEditing(sectionId);
            resolve(true);
          });
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

        .ql-container.ql-snow {
          border-color: #d1d5db;
        }

      .ql-editor {
          padding: 12px;
          min-height: 200px;
          background-color: #ffffff;
          color: #111827;
      }

        .ql-toolbar.ql-snow {
          background-color: #f3f4f6;
          border-color: #d1d5db;
          border-radius: 4px 4px 0 0;
      }

        .ql-toolbar.ql-snow button,
        .ql-toolbar.ql-snow .ql-picker-label,
        .ql-toolbar.ql-snow .ql-picker-item {
          color: #374151;
        }

        .ql-toolbar.ql-snow button svg,
        .ql-toolbar.ql-snow .ql-picker-label svg {
          fill: currentColor;
        }

        .dark .ql-container.ql-snow {
          border-color: #374151;
        }

        .dark .ql-toolbar.ql-snow {
          background-color: #1f2937;
          border-color: #374151;
      }

        .dark .ql-toolbar.ql-snow button,
        .dark .ql-toolbar.ql-snow .ql-picker-label,
        .dark .ql-toolbar.ql-snow .ql-picker-item {
          color: #e5e7eb;
        }

        .dark .ql-toolbar.ql-snow .ql-stroke {
          stroke: #e5e7eb;
        }

        .dark .ql-toolbar.ql-snow .ql-fill {
          fill: #e5e7eb;
      }

      .dark .ql-editor {
          background-color: #111827;
          color: #e5e7eb;
      }

      .dark .ql-editor.ql-blank::before {
          color: #6b7280;
      }

      .edit-icon-container {
          display: inline-flex;
          align-items: center;
      }

      .edit-buttons-container {
          gap: 0.75rem;
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

            <!-- Header title -->
            <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
              <h2 class="text-5xl font-bold text-gray-800 dark:text-gray-100">
                <?php echo htmlspecialchars($pageTitle); ?>
              </h2>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-8">
                STI Discipline Office Management System | Last updated: <?php echo date('F d, Y'); ?>
            </p>

            <!-- Terms Sections -->
            <div class="space-y-8 text-justify text-lg">

              <div id="purpose-and-use" class="space-y-4">
                <h4 class="font-semibold text-2xl">Purpose and Use</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-purpose-and-use" style="display: none;">
                  <div id="quill-purpose-and-use" class="ql-container"></div>
                </div>
              </div>

              <div id="confidentiality-and-data-protection" class="space-y-4">
                <h4 class="font-semibold text-2xl">Confidentiality and Data Protection</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-confidentiality-and-data-protection" style="display: none;">
                  <div id="quill-confidentiality-and-data-protection" class="ql-container"></div>
                </div>
              </div>

              <div id="user-responsibilities" class="space-y-4">
                <h4 class="font-semibold text-2xl">User Responsibilities</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-user-responsibilities" style="display: none;">
                  <div id="quill-user-responsibilities" class="ql-container"></div>
                </div>
              </div>

              <div id="compliance-with-school-policies" class="space-y-4">
                <h4 class="font-semibold text-2xl">Compliance with School Policies</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-compliance-with-school-policies" style="display: none;">
                  <div id="quill-compliance-with-school-policies" class="ql-container"></div>
                </div>
              </div>

              <div id="liability-limitation" class="space-y-4">
                <h4 class="font-semibold text-2xl">Liability Limitation</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-liability-limitation" style="display: none;">
                  <div id="quill-liability-limitation" class="ql-container"></div>
                </div>
              </div>

              <div id="modification-of-terms" class="space-y-4">
                <h4 class="font-semibold text-2xl">Modification of Terms</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-modification-of-terms" style="display: none;">
                  <div id="quill-modification-of-terms" class="ql-container"></div>
                </div>
              </div>

              <div id="termination" class="space-y-4">
                <h4 class="font-semibold text-2xl">Termination</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-termination" style="display: none;">
                  <div id="quill-termination" class="ql-container"></div>
                </div>
              </div>

              <div id="role-based-access-control" class="space-y-4">
                <h4 class="font-semibold text-2xl">Role-Based Access Control</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-role-based-access-control" style="display: none;">
                  <div id="quill-role-based-access-control" class="ql-container"></div>
                </div>
              </div>

              <div id="monitoring-and-audit-logging" class="space-y-4">
                <h4 class="font-semibold text-2xl">Monitoring and Audit Logging</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-monitoring-and-audit-logging" style="display: none;">
                  <div id="quill-monitoring-and-audit-logging" class="ql-container"></div>
                </div>
              </div>

              <div id="data-retention-and-deletion" class="space-y-4">
                <h4 class="font-semibold text-2xl">Data Retention and Deletion</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-data-retention-and-deletion" style="display: none;">
                  <div id="quill-data-retention-and-deletion" class="ql-container"></div>
                </div>
              </div>

              <div id="security-and-incident-response" class="space-y-4">
                <h4 class="font-semibold text-2xl">Security and Incident Response</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-security-and-incident-response" style="display: none;">
                  <div id="quill-security-and-incident-response" class="ql-container"></div>
                </div>
              </div>

              <div id="system-availability-and-maintenance" class="space-y-4">
                <h4 class="font-semibold text-2xl">System Availability and Maintenance</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-system-availability-and-maintenance" style="display: none;">
                  <div id="quill-system-availability-and-maintenance" class="ql-container"></div>
                </div>
              </div>

              <div id="account-management" class="space-y-4">
                <h4 class="font-semibold text-2xl">Account Management</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-account-management" style="display: none;">
                  <div id="quill-account-management" class="ql-container"></div>
                </div>
              </div>

              <div id="governing-law-and-jurisdiction" class="space-y-4">
                <h4 class="font-semibold text-2xl">Governing Law and Jurisdiction</h4>
                <div class="terms-content"></div>
                <div id="editor-wrapper-governing-law-and-jurisdiction" style="display: none;">
                  <div id="quill-governing-law-and-jurisdiction" class="ql-container"></div>
                </div>
              </div>

            </div>

          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>
