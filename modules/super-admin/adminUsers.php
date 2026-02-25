<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is super admin
if ($_SESSION['user_role'] !== 'super_admin') {
    header('Location: /PrototypeDO/index.php');
    exit;
}

// Handle CSV Import for Teachers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred']);
            exit;
        }

        $file = $_FILES['csv_file'];
        $fileName = $file['tmp_name'];

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'csv') {
            echo json_encode(['success' => false, 'error' => 'Only CSV files are allowed']);
            exit;
        }

        // Open and parse CSV
        $handle = fopen($fileName, 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'error' => 'Could not open the CSV file']);
            exit;
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            echo json_encode(['success' => false, 'error' => 'CSV file is empty']);
            exit;
        }

        // Expected columns: first_name, last_name, middle_name, contact_number
        $imported = 0;
        $errors = [];
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to array
            $data = array_combine($header, $row);

            // Validate required fields
            if (empty($data['first_name']) || empty($data['last_name'])) {
                $errors[] = "Row skipped: Missing required fields (first_name or last_name)";
                $skipped++;
                continue;
            }

            try {
                // Auto-generate email: firstname.lastname@sti.edu
                $firstName = strtolower(str_replace(' ', '', $data['first_name']));
                $lastName = strtolower(str_replace(' ', '', $data['last_name']));
                $emailBase = $firstName . '.' . $lastName . '@sti.edu';
                $email = $emailBase;
                
                // Check if email already exists, if so add a number
                $counter = 1;
                while (fetchOne("SELECT user_id FROM users WHERE email = ?", [$email])) {
                    $email = $firstName . '.' . $lastName . $counter . '@sti.edu';
                    $counter++;
                }

                // Create user account for the teacher
                $fullName = trim($data['first_name'] . ' ' . ($data['middle_name'] ?? '') . ' ' . $data['last_name']);
                $username = $email; // Use email as username
                $defaultPassword = 'password'; // Default password for all teachers
                $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

                // Insert user account
                $userSql = "INSERT INTO users (username, password_hash, email, full_name, role, contact_number, is_active, created_at)
                            VALUES (?, ?, ?, ?, 'teacher', ?, 1, GETDATE())";
                executeQuery($userSql, [
                    $username,
                    $passwordHash,
                    $email,
                    $fullName,
                    $data['contact_number'] ?? null
                ]);

                $imported++;
            } catch (Exception $e) {
                $errors[] = "Error importing teacher: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
        exit;

    } catch (Exception $e) {
        error_log("CSV Import Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        // Get next student ID
        if ($_POST['action'] === 'getNextStudentID') {
            $prefix = '02000';
            $last = fetchValue("SELECT TOP 1 student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC", [$prefix . '%']);
            $nextNum = 1;
            if ($last) {
                $num = intval(substr($last, strlen($prefix)));
                $nextNum = $num + 1;
            }
            do {
                $studentId = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                $exists = fetchOne("SELECT student_id FROM students WHERE student_id = ?", [$studentId]);
                $nextNum++;
            } while ($exists);
            
            echo json_encode(['success' => true, 'student_id' => $studentId]);
            exit;
        }

        // Get all users
        if ($_POST['action'] === 'getUsers') {
            $search = $_POST['search'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';

            $sql = "SELECT u.user_id, u.email, u.full_name, u.role, u.contact_number, 
                           u.is_active, u.last_login, u.created_at,
                           s.student_id
                    FROM users u
                    LEFT JOIN students s ON u.user_id = s.user_id
                    WHERE 1=1";
            
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR s.student_id LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($role)) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }

            if ($status !== '') {
                $sql .= " AND is_active = ?";
                $params[] = $status === 'active' ? 1 : 0;
            }

            $sql .= " ORDER BY created_at DESC";

            $users = fetchAll($sql, $params);

            $formattedUsers = array_map(function ($user) {
                return [
                    'user_id' => $user['user_id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'contact_number' => $user['contact_number'] ?? 'N/A',
                    'student_id' => $user['student_id'] ?? null,
                    'is_active' => $user['is_active'],
                    'status' => $user['is_active'] ? 'Active' : 'Inactive',
                    'last_login' => $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never',
                    'created_at' => date('M d, Y', strtotime($user['created_at']))
                ];
            }, $users);

            echo json_encode(['success' => true, 'users' => $formattedUsers]);
            exit;
        }

        // Create new user
        if ($_POST['action'] === 'createUser') {
            // admin will not provide a username or password anymore
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            $contact_number = trim($_POST['contact_number'] ?? '');

            // default password for every new account
            $password = 'password';

            // Validate required fields
            if (empty($email) || empty($full_name) || empty($role)) {
                echo json_encode(['success' => false, 'error' => 'Email, full name and role are required']);
                exit;
            }

            // Check if email already exists
            $existingEmail = fetchOne("SELECT user_id FROM users WHERE email = ?", [$email]);
            if ($existingEmail) {
                echo json_encode(['success' => false, 'error' => 'Email already exists']);
                exit;
            }

            // generate a username using the email (kept for compatibility and uniqueness)
            $username = $email;

            // Hash default password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user record
            $sql = "INSERT INTO users (username, password_hash, email, full_name, role, contact_number, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE())";
            executeQuery($sql, [$username, $password_hash, $email, $full_name, $role, $contact_number]);

            // Get the new user ID (lookup by email since it's guaranteed unique)
            $newUserId = fetchValue("SELECT user_id FROM users WHERE email = ?", [$email]);

            // if this is a student, create a linked student record with auto‑generated ID
            if ($role === 'student') {
                $prefix = '02000';
                // find last student id with the prefix
                $last = fetchValue("SELECT TOP 1 student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC", [$prefix . '%']);
                $nextNum = 1;
                if ($last) {
                    $num = intval(substr($last, strlen($prefix)));
                    $nextNum = $num + 1;
                }
                // ensure uniqueness in a loop just in case
                do {
                    $studentId = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                    $exists = fetchOne("SELECT student_id FROM students WHERE student_id = ?", [$studentId]);
                    $nextNum++;
                } while ($exists);

                // split full name into first/last
                $nameParts = preg_split('/\s+/', $full_name);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

                $sqlStudent = "INSERT INTO students (student_id, user_id, first_name, last_name, grade_year, student_type) VALUES (?, ?, ?, ?, ?, ?)";
                executeQuery($sqlStudent, [$studentId, $newUserId, $firstName, $lastName, 'N/A', 'College']);
            }

            // Audit log
            $logData = [
                'email' => $email,
                'full_name' => $full_name,
                'role' => $role
            ];
            if (isset($studentId)) {
                $logData['student_id'] = $studentId;
            }
            auditCreate('users', $newUserId, $logData);

            $response = ['success' => true, 'message' => 'User created successfully'];
            if (isset($studentId)) {
                $response['student_id'] = $studentId;
            }
            echo json_encode($response);
            exit;
        }

        // Update user
        if ($_POST['action'] === 'updateUser') {
            $user_id = $_POST['user_id'];
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            $contact_number = trim($_POST['contact_number'] ?? '');

            // Get old data for audit
            $oldData = fetchOne("SELECT * FROM users WHERE user_id = ?", [$user_id]);

            // Get current is_active status (not changed via edit form)
            $is_active = $oldData['is_active'];

            // Check if email already exists for another user
            $existingEmail = fetchOne("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $user_id]);
            if ($existingEmail) {
                echo json_encode(['success' => false, 'error' => 'Email already exists']);
                exit;
            }

            // Update user
            $sql = "UPDATE users 
                    SET email = ?, full_name = ?, role = ?, contact_number = ?, is_active = ?, updated_at = GETDATE()
                    WHERE user_id = ?";
            
            executeQuery($sql, [$email, $full_name, $role, $contact_number, $is_active, $user_id]);

            // if becoming a student and no corresponding student record exists, create one
            if ($role === 'student') {
                $existingStudent = fetchOne("SELECT student_id FROM students WHERE user_id = ?", [$user_id]);
                if (!$existingStudent) {
                    $prefix = '02000';
                    $last = fetchValue("SELECT TOP 1 student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC", [$prefix . '%']);
                    $nextNum = 1;
                    if ($last) {
                        $num = intval(substr($last, strlen($prefix)));
                        $nextNum = $num + 1;
                    }
                    do {
                        $studentId = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                        $exists = fetchOne("SELECT student_id FROM students WHERE student_id = ?", [$studentId]);
                        $nextNum++;
                    } while ($exists);

                    $nameParts = preg_split('/\s+/', $full_name);
                    $firstName = $nameParts[0];
                    $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
                    $sqlStudent = "INSERT INTO students (student_id, user_id, first_name, last_name, grade_year, student_type) VALUES (?, ?, ?, ?, ?, ?)";
                    executeQuery($sqlStudent, [$studentId, $user_id, $firstName, $lastName, 'N/A', 'College']);
                }
            }

            // Audit log
            $newData = fetchOne("SELECT * FROM users WHERE user_id = ?", [$user_id]);
            auditUpdate('users', $user_id, sanitizeAuditData($oldData), sanitizeAuditData($newData));

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            exit;
        }

        // Reset password
        if ($_POST['action'] === 'resetPassword') {
            $user_id = $_POST['user_id'];
            $new_password = $_POST['new_password'];

            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                exit;
            }

            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password_hash = ?, updated_at = GETDATE() WHERE user_id = ?";
            executeQuery($sql, [$password_hash, $user_id]);

            // Audit log
            logAudit($_SESSION['user_id'], 'Password Reset', 'users', $user_id, null, ['action' => 'Password reset by admin']);

            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            exit;
        }

        // Toggle user status
        if ($_POST['action'] === 'toggleStatus') {
            $user_id = $_POST['user_id'];

            $currentStatus = fetchValue("SELECT is_active FROM users WHERE user_id = ?", [$user_id]);
            $newStatus = $currentStatus ? 0 : 1;

            $sql = "UPDATE users SET is_active = ?, updated_at = GETDATE() WHERE user_id = ?";
            executeQuery($sql, [$newStatus, $user_id]);

            // Audit log
            logAudit($_SESSION['user_id'], $newStatus ? 'User Activated' : 'User Deactivated', 'users', $user_id, null, [
                'old_status' => $currentStatus ? 'Active' : 'Inactive',
                'new_status' => $newStatus ? 'Active' : 'Inactive'
            ]);

            echo json_encode(['success' => true, 'message' => 'User status updated successfully', 'newStatus' => $newStatus]);
            exit;
        }

        // Delete user
        if ($_POST['action'] === 'deleteUser') {
            $user_id = $_POST['user_id'];

            // Prevent deleting self
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
                exit;
            }

            // Get user data before deletion
            $userData = fetchOne("SELECT * FROM users WHERE user_id = ?", [$user_id]);

            // Delete associated audit logs first (foreign key constraint)
            $sqlDeleteAudit = "DELETE FROM audit_log WHERE user_id = ?";
            executeQuery($sqlDeleteAudit, [$user_id]);

            // Delete user
            $sql = "DELETE FROM users WHERE user_id = ?";
            executeQuery($sql, [$user_id]);

            // Audit log
            auditDelete('users', $user_id, sanitizeAuditData($userData));

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            exit;
        }

        // Get user statistics
        if ($_POST['action'] === 'getStats') {
            $stats = [
                'total' => fetchValue("SELECT COUNT(*) FROM users"),
                'active' => fetchValue("SELECT COUNT(*) FROM users WHERE is_active = 1"),
                'super_admins' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'super_admin'"),
                'do_staff' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'discipline_office'"),
                'teachers' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'teacher'"),
                'security' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'security'"),
                'students' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'student'")
            ];

            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        }

        // Bulk set active
        if ($_POST['action'] === 'setActive') {
            $userIds = json_decode($_POST['user_ids'] ?? '[]', true);
            
            if (empty($userIds)) {
                echo json_encode(['success' => false, 'error' => 'No users selected']);
                exit;
            }

            $successCount = 0;
            $errors = [];

            foreach ($userIds as $userId) {
                try {
                    $userId = intval($userId);
                    
                    // Prevent deactivating self
                    if ($userId === (int)$_SESSION['user_id']) {
                        $errors[] = "Cannot modify your own account";
                        continue;
                    }

                    // Check if user exists
                    if (!fetchOne("SELECT user_id FROM users WHERE user_id = ?", [$userId])) {
                        $errors[] = "User ID $userId not found";
                        continue;
                    }

                    $sql = "UPDATE users SET is_active = 1, updated_at = GETDATE() WHERE user_id = ?";
                    executeQuery($sql, [$userId]);

                    // Audit log
                    logAudit($_SESSION['user_id'], 'User Activated (Bulk)', 'users', $userId, null, [
                        'action' => 'Activated via bulk action'
                    ]);

                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Error activating user $userId: " . $e->getMessage();
                }
            }

            $message = "$successCount user" . ($successCount !== 1 ? 's' : '') . " activated successfully";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " error(s) occurred";
            }

            echo json_encode(['success' => true, 'message' => $message, 'count' => $successCount]);
            exit;
        }

        // Bulk set inactive
        if ($_POST['action'] === 'setInactive') {
            $userIds = json_decode($_POST['user_ids'] ?? '[]', true);
            
            if (empty($userIds)) {
                echo json_encode(['success' => false, 'error' => 'No users selected']);
                exit;
            }

            $successCount = 0;
            $errors = [];

            foreach ($userIds as $userId) {
                try {
                    $userId = intval($userId);
                    
                    // Prevent deactivating self
                    if ($userId === (int)$_SESSION['user_id']) {
                        $errors[] = "Cannot modify your own account";
                        continue;
                    }

                    // Check if user exists
                    if (!fetchOne("SELECT user_id FROM users WHERE user_id = ?", [$userId])) {
                        $errors[] = "User ID $userId not found";
                        continue;
                    }

                    $sql = "UPDATE users SET is_active = 0, updated_at = GETDATE() WHERE user_id = ?";
                    executeQuery($sql, [$userId]);

                    // Audit log
                    logAudit($_SESSION['user_id'], 'User Deactivated (Bulk)', 'users', $userId, null, [
                        'action' => 'Deactivated via bulk action'
                    ]);

                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Error deactivating user $userId: " . $e->getMessage();
                }
            }

            $message = "$successCount user" . ($successCount !== 1 ? 's' : '') . " deactivated successfully";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " error(s) occurred";
            }

            echo json_encode(['success' => true, 'message' => $message, 'count' => $successCount]);
            exit;
        }

        // Bulk delete users
        if ($_POST['action'] === 'deleteUsers') {
            $userIds = json_decode($_POST['user_ids'] ?? '[]', true);
            
            if (empty($userIds)) {
                echo json_encode(['success' => false, 'error' => 'No users selected']);
                exit;
            }

            $successCount = 0;
            $errors = [];

            foreach ($userIds as $userId) {
                try {
                    $userId = intval($userId);
                    
                    // Prevent deleting self
                    if ($userId === (int)$_SESSION['user_id']) {
                        $errors[] = "Cannot delete your own account";
                        continue;
                    }

                    // Check if user exists
                    $userData = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
                    if (!$userData) {
                        $errors[] = "User ID $userId not found";
                        continue;
                    }

                    // Delete associated audit logs first (foreign key constraint)
                    $sqlDeleteAudit = "DELETE FROM audit_log WHERE user_id = ?";
                    executeQuery($sqlDeleteAudit, [$userId]);

                    // Delete user
                    $sql = "DELETE FROM users WHERE user_id = ?";
                    executeQuery($sql, [$userId]);

                    // Audit log
                    auditDelete('users', $userId, sanitizeAuditData($userData));

                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Error deleting user $userId: " . $e->getMessage();
                }
            }

            $message = "$successCount user" . ($successCount !== 1 ? 's' : '') . " deleted successfully";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " error(s) occurred";
            }

            echo json_encode(['success' => true, 'message' => $message, 'count' => $successCount]);
            exit;
        }

    } catch (Exception $e) {
        error_log("Users AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$pageTitle = "User Management";
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
                <!-- Top Bar -->
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search users..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
                            oninput="filterUsers()">
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="openImportTeachersModal()"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Import Teachers
                        </button>

                        <button onclick="openAddModal()"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add User
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex items-center gap-3">
                    <select id="roleFilter" onchange="filterUsers()"
                        class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="discipline_office">Discipline Office</option>
                        <option value="teacher">Teacher</option>
                        <option value="security">Security</option>
                        <option value="student">Student</option>
                    </select>

                    <select id="statusFilter" onchange="filterUsers()"
                        class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button onclick="loadUsers()"
                        class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulkActionsBar" class="hidden mb-4 bg-transparent border border-blue-400 dark:border-blue-600 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <span id="selectedCount" class="text-blue-900 dark:text-blue-300 font-semibold text-sm">0 users selected</span>
                            <div class="h-6 w-px bg-blue-300 dark:bg-blue-500"></div>
                            <div class="flex gap-2">
                                <button onclick="bulkSetActive()" class="px-3 py-1.5 border border-green-500 text-green-600 dark:text-green-400 text-xs font-medium rounded hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors">
                                    Activate
                                </button>
                                <button onclick="bulkSetInactive()" class="px-3 py-1.5 border border-yellow-500 text-yellow-600 dark:text-yellow-400 text-xs font-medium rounded hover:bg-yellow-50 dark:hover:bg-yellow-900/10 transition-colors">
                                    Deactivate
                                </button>
                                <button onclick="bulkDelete()" class="px-3 py-1.5 border border-red-500 text-red-600 dark:text-red-400 text-xs font-medium rounded hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                        <button onclick="clearSelection()" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 cursor-pointer">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody" class="bg-white dark:bg-[#111827] divide-y divide-gray-200 dark:divide-slate-700">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <p id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
                        <div class="flex items-center gap-2">
                            <label for="itemsPerPageSelect" class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                            <select id="itemsPerPageSelect" onchange="changeItemsPerPage(this.value)" 
                                class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 cursor-pointer text-sm">
                                <option value="7" selected>7</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600 dark:text-gray-400">per page</span>
                        </div>
                    </div>
                    <div id="paginationButtons" class="flex gap-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Import Teachers Modal -->
    <div id="importTeachersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Import Teachers from CSV</h3>
                <button onclick="closeImportTeachersModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">CSV Format Requirements:</h4>
                <p class="text-sm text-blue-800 dark:text-blue-400 mb-2">The CSV file must have the following columns:</p>
                <code class="text-xs bg-white dark:bg-slate-900 px-2 py-1 rounded block overflow-x-auto">
                    first_name, last_name, middle_name, contact_number
                </code>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-2">* Required fields: first_name, last_name</p>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">* Email will be auto-generated as: firstname.lastname@sti.edu</p>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">* Default password: password</p>
            </div>

            <form id="importTeachersForm" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select CSV File
                    </label>
                    <input type="file" id="teachersCsvFile" name="csv_file" accept=".csv" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400">
                </div>

                <div id="importTeachersProgress" class="hidden mb-4">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Importing teachers...</span>
                    </div>
                </div>

                <div id="importTeachersResult" class="hidden mb-4"></div>

                <div class="flex gap-3">
                    <button type="submit" id="importTeachersBtn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Upload and Import
                    </button>
                    <button type="button" onclick="closeImportTeachersModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/users/main.js"></script>
    <script src="/PrototypeDO/assets/js/users/modals.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>

    <script>
        // Teacher CSV Import Functions
        function openImportTeachersModal() {
            document.getElementById('importTeachersModal').classList.remove('hidden');
            document.getElementById('importTeachersForm').reset();
            document.getElementById('importTeachersProgress').classList.add('hidden');
            document.getElementById('importTeachersResult').classList.add('hidden');
            document.getElementById('importTeachersBtn').disabled = false;
        }

        function closeImportTeachersModal() {
            document.getElementById('importTeachersModal').classList.add('hidden');
        }

        document.getElementById('importTeachersForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('teachersCsvFile');
            if (!fileInput.files.length) {
                alert('Please select a file');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            formData.append('import_csv', true);

            document.getElementById('importTeachersProgress').classList.remove('hidden');
            document.getElementById('importTeachersResult').classList.add('hidden');
            document.getElementById('importTeachersBtn').disabled = true;

            try {
                const response = await fetch('/PrototypeDO/modules/super-admin/adminUsers.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                document.getElementById('importTeachersProgress').classList.add('hidden');

                if (result.success) {
                    const resultDiv = document.getElementById('importTeachersResult');
                    let html = `<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <h4 class="font-semibold text-green-900 dark:text-green-300 mb-2">Import Completed!</h4>
                        <p class="text-sm text-green-800 dark:text-green-400">
                            ✓ ${result.imported} teacher(s) imported successfully
                        </p>`;
                    
                    if (result.skipped > 0) {
                        html += `<p class="text-sm text-yellow-700 dark:text-yellow-400 mt-2">⚠ ${result.skipped} row(s) skipped</p>`;
                    }

                    if (result.errors && result.errors.length > 0) {
                        html += `<div class="mt-3 text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-900 p-2 rounded max-h-48 overflow-y-auto">`;
                        result.errors.forEach(err => {
                            html += `<p class="text-yellow-700 dark:text-yellow-400">• ${err}</p>`;
                        });
                        html += `</div>`;
                    }

                    html += `</div>`;
                    resultDiv.innerHTML = html;
                    resultDiv.classList.remove('hidden');

                    if (result.imported > 0) {
                        setTimeout(() => {
                            closeImportTeachersModal();
                            loadUsers();
                        }, 2000);
                    }
                } else {
                    const resultDiv = document.getElementById('importTeachersResult');
                    resultDiv.innerHTML = `<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <h4 class="font-semibold text-red-900 dark:text-red-300 mb-2">Import Failed</h4>
                        <p class="text-sm text-red-800 dark:text-red-400">${result.error}</p>
                    </div>`;
                    resultDiv.classList.remove('hidden');
                }
            } catch (error) {
                document.getElementById('importTeachersProgress').classList.add('hidden');
                const resultDiv = document.getElementById('importTeachersResult');
                resultDiv.innerHTML = `<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <h4 class="font-semibold text-red-900 dark:text-red-300 mb-2">Error</h4>
                    <p class="text-sm text-red-800 dark:text-red-400">${error.message}</p>
                </div>`;
                resultDiv.classList.remove('hidden');
            } finally {
                document.getElementById('importTeachersBtn').disabled = false;
            }
        });
    </script>
</body>
</html>