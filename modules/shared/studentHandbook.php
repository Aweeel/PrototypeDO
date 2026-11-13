<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Student Handbook';
$isSystemManager = ($_SESSION['user_role'] ?? '') === 'system_manager';
$pdfPath = __DIR__ . '/../../assets/PDF/STI_TER_HANDBOOK.pdf';
$pdfUrl = '../../assets/PDF/STI_TER_HANDBOOK.pdf';
$pdfVersion = file_exists($pdfPath) ? (string) filemtime($pdfPath) : (string) time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  </script>
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class' };
    window.toggleDarkMode = function () {
      const isDark = document.documentElement.classList.toggle('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      document.dispatchEvent(new CustomEvent('themechange', { detail: { isDark } }));
    };
  </script>
</head>
<body class="bg-gray-50 dark:bg-[#1E293B] text-gray-900 dark:text-gray-100 antialiased">
  <div id="sidebarWrapper">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  </div>

  <?php include __DIR__ . '/../../includes/header.php'; ?>

  <main class="h-[var(--app-viewport-height)] overflow-hidden p-4 pt-24 md:ml-64 md:p-6 md:pt-28">
    <div class="mx-auto flex h-full w-full flex-col">
      <div class="flex shrink-0 justify-end gap-3 pb-3">
        <a
          href="<?php echo htmlspecialchars($pdfUrl . '?v=' . rawurlencode($pdfVersion)); ?>"
          download="STI_TER_HANDBOOK.pdf"
          class="rounded-lg bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow hover:bg-blue-700"
        >
          Download PDF
        </a>
        <?php if ($isSystemManager): ?>
          <button
            type="button"
            onclick="openUploadPDFModal()"
            class="rounded-lg bg-green-600 px-6 py-3 text-base font-semibold text-white shadow hover:bg-green-700"
          >
            Upload PDF
          </button>
        <?php endif; ?>
      </div>
      <div class="min-h-0 flex-1 overflow-auto overscroll-contain rounded-xl border border-gray-200 bg-white shadow-lg [-webkit-overflow-scrolling:touch] dark:border-slate-700 dark:bg-[#111827]">
        <iframe
          title="<?php echo htmlspecialchars($pageTitle); ?>"
          src="<?php echo htmlspecialchars($pdfUrl . '?v=' . rawurlencode($pdfVersion)); ?>"
          scrolling="yes"
          class="h-full min-h-full w-full border-0"
        ></iframe>
      </div>
    </div>
  </main>

  <?php if ($isSystemManager): ?>
    <div id="uploadPDFModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50">
      <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-[#111827]">
        <h2 class="mb-4 text-2xl font-bold">Upload Student Handbook PDF</h2>
        <input id="pdfFileInput" type="file" accept=".pdf,application/pdf" class="w-full rounded-lg border border-gray-400 px-3 py-2 text-gray-900 dark:border-slate-500 dark:bg-slate-700 dark:text-white">
        <p class="mt-2 text-sm text-gray-500">Maximum file size: 50MB</p>
        <div id="uploadStatus" class="my-4 text-sm"></div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeUploadPDFModal()" class="rounded-lg bg-gray-300 px-4 py-2 font-medium text-gray-900 dark:bg-slate-600 dark:text-white">Cancel</button>
          <button type="button" onclick="uploadPDF()" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Upload</button>
        </div>
      </div>
    </div>

    <script>
      const uploadModal = document.getElementById('uploadPDFModal');
      const uploadStatus = document.getElementById('uploadStatus');
      const fileInput = document.getElementById('pdfFileInput');

      function openUploadPDFModal() {
        uploadModal.classList.remove('hidden');
        uploadModal.classList.add('flex');
        uploadStatus.textContent = '';
        fileInput.value = '';
      }

      function closeUploadPDFModal() {
        uploadModal.classList.add('hidden');
        uploadModal.classList.remove('flex');
      }

      function uploadPDF() {
        const file = fileInput.files[0];
        if (!file) {
          uploadStatus.textContent = 'Please select a file.';
          return;
        }
        if (file.type !== 'application/pdf' || !file.name.toLowerCase().endsWith('.pdf')) {
          uploadStatus.textContent = 'Only PDF files are allowed.';
          return;
        }
        if (file.size > 50 * 1024 * 1024) {
          uploadStatus.textContent = 'File size exceeds 50MB limit.';
          return;
        }

        uploadStatus.textContent = 'Uploading...';
        const formData = new FormData();
        formData.append('action', 'uploadPDF');
        formData.append('pdf_file', file);

        fetch('handbookHandler.php', { method: 'POST', body: formData })
          .then(response => response.json())
          .then(data => {
            if (!data.success) {
              throw new Error(data.error || 'Upload failed.');
            }
            uploadStatus.textContent = 'PDF uploaded successfully.';
            setTimeout(() => window.location.reload(), 500);
          })
          .catch(error => {
            uploadStatus.textContent = error.message;
          });
      }

      uploadModal.addEventListener('click', event => {
        if (event.target === uploadModal) closeUploadPDFModal();
      });
    </script>
  <?php endif; ?>
</body>
</html>
