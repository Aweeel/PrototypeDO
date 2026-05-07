<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Handle content updates - Only super admins can do this
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateContent') {
    // Only super admins can update
    if ($_SESSION['user_role'] !== 'super_admin') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit;
    }
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

        // Get or create the terms config file
        $configFile = __DIR__ . '/../../assets/json/terms_content.json';
        $termsData = [];

        if (file_exists($configFile)) {
            $termsData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $termsData = [];
            }
        }

        // Track changes for audit logging
        $changedSections = [];
        
        // Update all sections
        foreach ($sectionsData as $sectionId => $content) {
            // Store the old content for audit
            $oldContent = $termsData[$sectionId] ?? null;
            
            // Only log if content actually changed
            if ($oldContent !== $content) {
                $changedSections[] = [
                    'section_id' => $sectionId,
                    'old_content' => substr($oldContent ?? '', 0, 100),
                    'new_content' => substr($content, 0, 100)
                ];
            }

            // Update content
            $termsData[$sectionId] = $content;
        }

        // Write to file
        if (!file_put_contents($configFile, json_encode($termsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            echo json_encode(['success' => false, 'error' => 'Failed to save content']);
            exit;
        }

        // Increment the terms version to force re-acceptance
        // Connect to database and update terms_version
        require_once __DIR__ . '/../../includes/db_connect.php';
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                // Increment TERMS_VERSION in a config or get current max and increment
                // For now, we'll just reset all users' terms_accepted_version to force re-acceptance
                $pdo->exec("UPDATE users SET terms_accepted_version = 0 WHERE role != 'super_admin'");
            }
        } catch (Exception $dbError) {
            // Log but don't fail the save
            error_log("Warning: Could not update terms version in database: " . $dbError->getMessage());
        }

        // Audit log using helper function
        auditTermsUpdated($_SESSION['user_id'], count($changedSections), $changedSections);

        echo json_encode([
            'success' => true,
            'message' => 'Terms and Conditions updated successfully. All users will be required to accept the new terms on their next login.',
            'sections_updated' => count($changedSections)
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Terms Update Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle getting all content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'getAllContent') {
    try {
        $configFile = __DIR__ . '/../../assets/json/terms_content.json';
        $termsData = [];

        if (file_exists($configFile)) {
            $termsData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $termsData = [];
            }
        }

        echo json_encode([
            'success' => true,
            'content' => $termsData
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Get Terms Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
