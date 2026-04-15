<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Only super admins can access this
if ($_SESSION['user_role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

// Handle PDF Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'uploadPDF') {
    try {
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred']);
            exit;
        }

        $file = $_FILES['pdf_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];

        // Validate file type
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($fileTmpName);

        if ($fileExtension !== 'pdf' || strpos($mimeType, 'pdf') === false) {
            echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed']);
            exit;
        }

        // Validate file size (max 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'File size exceeds 50MB limit']);
            exit;
        }

        // Define target directory and file path
        $targetDir = __DIR__ . '/../../assets/PDF/';
        $targetFile = $targetDir . 'STI_TER_HANDBOOK.pdf';

        // Create backup of old PDF if it exists
        if (file_exists($targetFile)) {
            $backupDir = $targetDir . 'backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $timestamp = date('YmdHis');
            $backupFile = $backupDir . 'STI_TER_HANDBOOK_backup_' . $timestamp . '.pdf';
            copy($targetFile, $backupFile);
        }

        // Move uploaded file to target location
        if (!move_uploaded_file($fileTmpName, $targetFile)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save PDF file']);
            exit;
        }

        // Audit log
        logAudit($_SESSION['user_id'], 'Student Handbook PDF Updated', 'handbook', 0, null, [
            'action' => 'PDF file uploaded',
            'file_name' => $fileName,
            'file_size' => $file['size']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'PDF uploaded successfully',
            'file_name' => $fileName
        ]);
        exit;

    } catch (Exception $e) {
        error_log("PDF Upload Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle content updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateContent') {
    try {
        // Support both single section and multiple sections
        $sectionsData = [];
        
        // Check if we're updating multiple sections (new inline editing approach)
        if (isset($_POST['sections'])) {
            $sectionsData = json_decode($_POST['sections'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'error' => 'Invalid sections JSON']);
                exit;
            }
        } else {
            // Single section update (legacy)
            $sectionId = $_POST['section_id'] ?? null;
            $content = $_POST['content'] ?? '';

            if (empty($sectionId) || empty($content)) {
                echo json_encode(['success' => false, 'error' => 'Section ID and content are required']);
                exit;
            }
            
            $sectionsData[$sectionId] = $content;
        }

        if (empty($sectionsData)) {
            echo json_encode(['success' => false, 'error' => 'No sections to update']);
            exit;
        }

        // Get or create the handbook config file
        $configFile = __DIR__ . '/../../assets/handbook_content.json';
        $handbookData = [];

        if (file_exists($configFile)) {
            $handbookData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $handbookData = [];
            }
        }

        // Track changes for audit logging
        $changedSections = [];
        
        // Update all sections
        foreach ($sectionsData as $sectionId => $content) {
            // Store the old content for audit
            $oldContent = $handbookData[$sectionId] ?? null;
            
            // Only log if content actually changed
            if ($oldContent !== $content) {
                $changedSections[] = [
                    'section_id' => $sectionId,
                    'old_content' => substr($oldContent ?? '', 0, 100),
                    'new_content' => substr($content, 0, 100)
                ];
            }

            // Update content
            $handbookData[$sectionId] = $content;
        }

        // Write to file
        if (!file_put_contents($configFile, json_encode($handbookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            echo json_encode(['success' => false, 'error' => 'Failed to save content']);
            exit;
        }
        
        // Log audit once with all changes instead of per-section (much faster)
        if (!empty($changedSections)) {
            logAudit($_SESSION['user_id'], 'Student Handbook Content Updated', 'handbook', 0, null, [
                'total_sections_updated' => count($changedSections),
                'sections' => $changedSections
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Content updated successfully',
            'sections_updated' => array_keys($sectionsData)
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Content Update Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle getting all handbook content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'getAllContent') {
    try {
        $configFile = __DIR__ . '/../../assets/handbook_content.json';
        $handbookData = [];

        if (file_exists($configFile)) {
            $handbookData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $handbookData = [];
            }
        }

        echo json_encode([
            'success' => true,
            'content' => $handbookData
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Get All Content Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle getting handbook content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'getContent') {
    try {
        $sectionId = $_POST['section_id'] ?? null;

        if (empty($sectionId)) {
            echo json_encode(['success' => false, 'error' => 'Section ID is required']);
            exit;
        }

        $configFile = __DIR__ . '/../../assets/handbook_content.json';
        $handbookData = [];

        if (file_exists($configFile)) {
            $handbookData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $handbookData = [];
            }
        }

        $content = $handbookData[$sectionId] ?? '';

        echo json_encode([
            'success' => true,
            'content' => $content
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Get Content Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
