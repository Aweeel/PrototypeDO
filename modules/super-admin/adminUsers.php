<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is super admin
if ($_SESSION['user_role'] !== 'super_admin') {
    header('Location: /PrototypeDO/index.php');
    exit;
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
            $is_active = $_POST['is_active'] ?? 1;

            // Get old data for audit
            $oldData = fetchOne("SELECT * FROM users WHERE user_id = ?", [$user_id]);

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
                <div class="mb-6 flex items-center justify-between">
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

                    <button onclick="openAddModal()"
                        class="ml-4 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add User
                    </button>
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

                <!-- Users Table -->
                <div class="bg-white dark:bg-[#111827] rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                            <tr>
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
                    <p id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
                    <div id="paginationButtons" class="flex gap-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/users/main.js"></script>
    <script src="/PrototypeDO/assets/js/users/modals.js"></script>
    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>