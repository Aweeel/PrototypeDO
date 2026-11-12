<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

function finishHandbookResponse(array $payload): void
{
    echo json_encode($payload);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Your session has expired. Please sign in again.']);
    exit;
}

// Handle PDF Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'uploadPDF') {
    if (($_SESSION['user_role'] ?? '') !== 'system_manager') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit;
    }

    try {
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred']);
            exit;
        }

        $file = $_FILES['pdf_file'];
        $originalFileName = basename((string) $file['name']);
        $fileTmpName = $file['tmp_name'];

        // Validate file type
        $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
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
        $previousFile = null;
        if (file_exists($targetFile)) {
            $previousFile = [
                'filename' => basename($targetFile),
                'file_size' => filesize($targetFile),
                'sha256' => hash_file('sha256', $targetFile)
            ];
        }

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

        $newFile = [
            'original_filename' => $originalFileName,
            'saved_filename' => basename($targetFile),
            'file_size' => filesize($targetFile),
            'sha256' => hash_file('sha256', $targetFile)
        ];

        try {
            logAudit($_SESSION['user_id'], 'Student Handbook PDF Updated', 'handbook', 0, $previousFile, $newFile);
        } catch (Throwable $e) {
            error_log('PDF upload audit failed: ' . $e->getMessage());
        }

        $response = [
            'success' => true,
            'message' => 'PDF uploaded successfully',
            'file_name' => $originalFileName
        ];
        finishHandbookResponse($response);
        exit;

    } catch (Exception $e) {
        error_log("PDF Upload Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
