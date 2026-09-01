<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Page metadata
$pageTitle = "User Profile";
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;

if (!$userId) {
    header('Location: /PrototypeDO/modules/login/login.php');
    exit;
}

// Get database connection
$pdo = getDBConnection();

// Fetch user information
$user = null;
$studentInfo = null;

if ($pdo && $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is a student, fetch additional student info
    if ($userRole === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Set admin name for header
$adminName = getFormattedUserName();

// Handle profile update
$message = '';
$messageType = '';
$passwordMessage = '';
$passwordMessageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        $passwordMessage = "Please fill in all password fields.";
        $passwordMessageType = "error";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordMessage = "New passwords do not match.";
        $passwordMessageType = "error";
    } elseif (strlen($newPassword) < 8) {
        $passwordMessage = "Password must be at least 8 characters long.";
        $passwordMessageType = "error";
    } else {
        // Verify current password
        if (password_verify($currentPassword, $user['password_hash'])) {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = ?, updated_at = GETDATE()
                    WHERE user_id = ?
                ");
                $stmt->execute([$hashedPassword, $userId]);
                
                // Clear the default password warning flag
                $_SESSION['has_default_password'] = false;
                
                $passwordMessage = "Password changed successfully!";
                $passwordMessageType = "success";
            } catch (Exception $e) {
                $passwordMessage = "Error updating password: " . $e->getMessage();
                $passwordMessageType = "error";
            }
        } else {
            $passwordMessage = "Current password is incorrect.";
            $passwordMessageType = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'change_password')) {
    $contactNumber = $_POST['contact_number'] ?? '';
    
    if ($contactNumber !== '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET contact_number = ?, updated_at = GETDATE()
                WHERE user_id = ?
            ");
            $stmt->execute([$contactNumber, $userId]);
            
            // Update user object
            $user['contact_number'] = $contactNumber;
            
            $message = "Profile updated successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    }
}

// Get user initials for avatar
$initials = '';
if ($user) {
    $names = explode(' ', $user['full_name']);
    $initials = strtoupper(substr($names[0], 0, 1));
    if (isset($names[1])) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - PrototypeDO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/PrototypeDO/assets/js/globals.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased min-h-screen">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" class="fixed inset-0 z-30 bg-gray-900/50 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

    <!-- Container for Alert Toasts -->
    <div class="fixed top-16 sm:top-20 right-4 sm:right-6 z-50 flex flex-col space-y-3 max-w-[calc(100vw-2rem)] sm:max-w-sm pointer-events-none">
        <!-- Message Alert Toast -->
        <?php if ($message): ?>
            <div id="messageAlert" class="pointer-events-auto p-4 rounded-lg shadow-lg border animation-slide-in <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border-green-400 dark:bg-green-900/80 dark:text-green-200 dark:border-green-600' : 'bg-red-100 text-red-700 border-red-400 dark:bg-red-900/80 dark:text-red-200 dark:border-red-600'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Password Change Alert Toast -->
        <?php if ($passwordMessage): ?>
            <div id="passwordAlert" class="pointer-events-auto p-4 rounded-lg shadow-lg border animation-slide-in <?php echo $passwordMessageType === 'success' ? 'bg-green-100 text-green-700 border-green-400 dark:bg-green-900/80 dark:text-green-200 dark:border-green-600' : 'bg-red-100 text-red-700 border-red-400 dark:bg-red-900/80 dark:text-red-200 dark:border-red-600'; ?>">
                <?php echo htmlspecialchars($passwordMessage); ?>
            </div>
            <?php if ($passwordMessageType === 'success'): ?>
            <script>
                // Close the password warning modal if it exists
                const modal = document.getElementById('passwordWarningModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Main Content Wrapper -->
    <main class="lg:ml-64 pt-20 pb-8 px-4 sm:px-6 lg:px-8 transition-all duration-300">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Profile Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left space-y-4 sm:space-y-0 sm:space-x-6">
                    <!-- Avatar -->
                    <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                        <span class="text-2xl sm:text-3xl font-bold text-white"><?php echo htmlspecialchars($initials); ?></span>
                    </div>

                    <!-- Basic Info -->
                    <div class="flex-1 min-w-0 w-full">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2 truncate">
                            <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>
                        </h1>
                        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mb-1">
                            <span class="font-semibold">Role:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $userRole))); ?>
                        </p>
                        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 truncate">
                            <span class="font-semibold">Username:</span> <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Profile</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Full Name
                        </label>
                        <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                            <?php echo htmlspecialchars($user['full_name'] ?? ''); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                        <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-all text-sm sm:text-base">
                            <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Contact Number *
                        </label>
                        <input type="tel" name="contact_number" 
                               value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm sm:text-base" />
                    </div>

                    <!-- Student-specific fields -->
                    <?php if ($userRole === 'student' && $studentInfo): ?>
                        <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">Student Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        First Name
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['first_name'] ?? ''); ?>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Last Name
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['last_name'] ?? ''); ?>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Student ID
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-all text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['student_id'] ?? ''); ?>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Grade/Year
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['grade_year'] ?? ''); ?>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Section
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['section'] ?? ''); ?>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Status
                                    </label>
                                    <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                        <?php echo htmlspecialchars($studentInfo['status'] ?? 'Good Standing'); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($studentInfo['guardian_name'])): ?>
                                <div class="pt-4 border-t border-gray-300 dark:border-gray-600 mt-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm sm:text-base">Guardian Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                Guardian Name
                                            </label>
                                            <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                                <?php echo htmlspecialchars($studentInfo['guardian_name'] ?? ''); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                Guardian Contact
                                            </label>
                                            <p class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white break-words text-sm sm:text-base">
                                                <?php echo htmlspecialchars($studentInfo['guardian_contact'] ?? ''); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pt-6 border-t border-gray-300 dark:border-gray-600 flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                        <button type="submit" 
                                class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition text-center text-sm sm:text-base">
                            Save Changes
                        </button>
                        <a href="javascript:history.back()" 
                           class="w-full sm:w-auto px-6 py-2.5 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-semibold transition text-center text-sm sm:text-base">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6">Change Password</h2>
                
                <form method="POST" class="space-y-4" id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Current Password *
                        </label>
                        <input type="password" id="currentPassword" name="current_password" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base" />
                        <p id="currentPasswordMsg" class="text-xs mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            New Password *
                        </label>
                        <input type="password" id="newPassword" name="new_password" 
                               required
                               minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 8 characters required</p>
                        <p id="newPasswordMsg" class="text-xs mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Confirm Password *
                        </label>
                        <input type="password" id="confirmPassword" name="confirm_password" 
                               required
                               minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base" />
                        <p id="confirmPasswordMsg" class="text-xs mt-1"></p>
                    </div>

                    <div class="pt-6 border-t border-gray-300 dark:border-gray-600 flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                        <button type="submit" id="submitBtn"
                                class="w-full sm:w-auto px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed text-center text-sm sm:text-base">
                            Change Password
                        </button>
                        <button type="reset" 
                                class="w-full sm:w-auto px-6 py-2.5 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-semibold transition text-center text-sm sm:text-base">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6">Account Information</h2>
                
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1 gap-1">
                        <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm sm:text-base">Account Status</span>
                        <div>
                            <span class="inline-block px-3 py-1 <?php echo $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?> rounded-full text-xs sm:text-sm font-bold">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1 gap-1">
                        <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm sm:text-base">Member Since</span>
                        <span class="text-gray-900 dark:text-white text-sm sm:text-base">
                            <?php echo date('F j, Y', strtotime($user['created_at'] ?? '')); ?>
                        </span>
                    </div>

                    <?php if (!empty($user['last_login'])): ?>
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1 gap-1">
                            <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm sm:text-base">Last Login</span>
                            <span class="text-gray-900 dark:text-white text-sm sm:text-base">
                                <?php echo date('F j, Y H:i', strtotime($user['last_login'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Hamburger / Mobile Sidebar Toggle Handler
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar') || document.querySelector('aside');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            
            // Query for common toggle button selectors across headers
            const sidebarToggle = document.getElementById('sidebarToggle') || 
                                  document.querySelector('[data-drawer-toggle="sidebar"]') ||
                                  document.querySelector('header button');

            function toggleSidebar() {
                if (sidebar) {
                    sidebar.classList.toggle('-translate-x-full');
                }
                if (sidebarBackdrop) {
                    sidebarBackdrop.classList.toggle('hidden');
                }
            }

            function closeSidebar() {
                if (sidebar) {
                    sidebar.classList.add('-translate-x-full');
                }
                if (sidebarBackdrop) {
                    sidebarBackdrop.classList.add('hidden');
                }
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeSidebar);
            }
        });

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

        // Password validation logic
        const currentPasswordInput = document.getElementById('currentPassword');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');

        let currentPasswordValid = false;
        let newPasswordValid = false;
        let confirmPasswordValid = false;

        // Validate current password
        if (currentPasswordInput) {
            currentPasswordInput.addEventListener('input', async function() {
                if (this.value.length === 0) {
                    this.style.borderColor = '';
                    document.getElementById('currentPasswordMsg').textContent = '';
                    currentPasswordValid = false;
                } else {
                    this.style.borderColor = '#eab308'; // Loading state
                    
                    try {
                        const response = await fetch('/PrototypeDO/includes/validate_password.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                current_password: this.value
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.valid) {
                            this.style.borderColor = '#22c55e';
                            document.getElementById('currentPasswordMsg').textContent = 'Password is correct';
                            document.getElementById('currentPasswordMsg').className = 'text-xs text-green-600 dark:text-green-400 mt-1';
                            currentPasswordValid = true;
                        } else {
                            this.style.borderColor = '#ef4444';
                            document.getElementById('currentPasswordMsg').textContent = 'Password is incorrect';
                            document.getElementById('currentPasswordMsg').className = 'text-xs text-red-600 dark:text-red-400 mt-1';
                            currentPasswordValid = false;
                        }
                    } catch (error) {
                        console.error('Error validating password:', error);
                        this.style.borderColor = '#ef4444';
                        document.getElementById('currentPasswordMsg').textContent = 'Error validating password';
                        document.getElementById('currentPasswordMsg').className = 'text-xs text-red-600 dark:text-red-400 mt-1';
                        currentPasswordValid = false;
                    }
                }
                updateSubmitButton();
            });
        }

        // Validate new password
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                if (this.value.length === 0) {
                    this.style.borderColor = '';
                    document.getElementById('newPasswordMsg').textContent = '';
                    newPasswordValid = false;
                } else if (this.value.length < 8) {
                    this.style.borderColor = '#ef4444';
                    document.getElementById('newPasswordMsg').textContent = `${this.value.length}/8 characters`;
                    document.getElementById('newPasswordMsg').className = 'text-xs text-red-600 dark:text-red-400 mt-1';
                    newPasswordValid = false;
                } else {
                    this.style.borderColor = '#22c55e';
                    document.getElementById('newPasswordMsg').textContent = 'Password meets requirements';
                    document.getElementById('newPasswordMsg').className = 'text-xs text-green-600 dark:text-green-400 mt-1';
                    newPasswordValid = true;
                }
                if (confirmPasswordInput && confirmPasswordInput.value) {
                    confirmPasswordInput.dispatchEvent(new Event('input'));
                }
                updateSubmitButton();
            });
        }

        // Validate confirm password
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value.length === 0) {
                    this.style.borderColor = '';
                    document.getElementById('confirmPasswordMsg').textContent = '';
                    confirmPasswordValid = false;
                } else if (newPasswordInput.value === '') {
                    this.style.borderColor = '';
                    document.getElementById('confirmPasswordMsg').textContent = '';
                    confirmPasswordValid = false;
                } else if (this.value !== newPasswordInput.value) {
                    this.style.borderColor = '#ef4444';
                    document.getElementById('confirmPasswordMsg').textContent = 'Passwords do not match';
                    document.getElementById('confirmPasswordMsg').className = 'text-xs text-red-600 dark:text-red-400 mt-1';
                    confirmPasswordValid = false;
                } else {
                    this.style.borderColor = '#22c55e';
                    document.getElementById('confirmPasswordMsg').textContent = 'Passwords match';
                    document.getElementById('confirmPasswordMsg').className = 'text-xs text-green-600 dark:text-green-400 mt-1';
                    confirmPasswordValid = true;
                }
                updateSubmitButton();
            });
        }

        function updateSubmitButton() {
            if (submitBtn) {
                if (currentPasswordValid && newPasswordValid && confirmPasswordValid) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                }
            }
        }

        // Auto-hide success messages after 3 seconds
        const messageAlert = document.getElementById('messageAlert');
        if (messageAlert && messageAlert.classList.contains('bg-green-100')) {
            setTimeout(() => {
                messageAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                messageAlert.style.opacity = '0';
                messageAlert.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    messageAlert.style.display = 'none';
                }, 500);
            }, 3000);
        }

        const passwordAlert = document.getElementById('passwordAlert');
        if (passwordAlert && passwordAlert.classList.contains('bg-green-100')) {
            setTimeout(() => {
                passwordAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                passwordAlert.style.opacity = '0';
                passwordAlert.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    passwordAlert.style.display = 'none';
                }, 500);
            }, 3000);
        }
    </script>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .animation-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</body>
</html>