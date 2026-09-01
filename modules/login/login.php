<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'do';
    
    if ($role === 'super_admin' || $role === 'discipline_office' || $role === 'do') {
        header('Location: /PrototypeDO/modules/do/doDashboard.php');
    } elseif ($role === 'student') {
        header('Location: /PrototypeDO/modules/student/studentDashboard.php');
    } elseif ($role === 'teacher' || $role === 'security') {
        header('Location: /PrototypeDO/modules/teacher/teacherDashboard.php');
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .decorative-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="min-h-screen lg:h-screen lg:overflow-hidden">
    <div class="flex flex-col lg:flex-row h-full min-h-screen lg:min-h-0">
        
        <!-- Left Side (Hidden on screens below lg) -->
        <div class="hidden lg:flex lg:w-1/2 relative flex-col items-center justify-center p-12 overflow-hidden bg-slate-800">

            <!-- Background Image -->
            <div class="absolute inset-0 z-0"
                style="background-image: url('../../assets/images/backgrounds/loginhalf.png'); background-size: cover; background-position: center;">
            </div>

            <div class="mb-8 relative z-10">
                <div>
                    <!-- Original logo size preserved -->
                    <img src="../../assets/images/logos/doms-logo.png" alt="DOMS Logo" class="w-50 h-auto">
                </div>
            </div>

            <!-- Text and Buttons (z-10 to be on top) -->
            <div class="relative z-10 text-center">
                <!-- Title -->
                <h1 class="text-white text-2xl font-bold mb-2">Welcome to STI Discipline Office</h1>
                <h2 class="text-white text-2xl font-bold mb-8">Management System</h2>

                <!-- Description -->
                <p class="text-gray-300 max-w-md mb-12">
                    Login to your account to access our comprehensive education management platform and connect with the
                    STI community.
                </p>

                <!-- Label Group -->
                <div class="flex flex-col items-center gap-4 mb-6">
                    <!-- Top Two Labels -->
                    <div class="flex gap-4">
                        <!-- School Handbook -->
                        <div class="bg-slate-700/80 backdrop-blur-sm text-white px-6 py-3 rounded-lg flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>School Handbook</span>
                        </div>

                        <!-- Student Records -->
                        <div class="bg-slate-700/80 backdrop-blur-sm text-white px-6 py-3 rounded-lg flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                            <span>Student Records</span>
                        </div>
                    </div>

                    <!-- Secure Access Label -->
                    <div class="flex items-center gap-2 bg-slate-700/80 backdrop-blur-sm text-white px-5 py-2 rounded-lg text-sm">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Secure Access</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Side - Styled Background on mobile, white on desktop -->
        <div class="w-full lg:w-1/2 bg-gradient-to-b from-slate-100 via-slate-50 to-white lg:bg-none lg:bg-white flex flex-col justify-center items-center p-6 sm:p-10 lg:p-12 min-h-screen lg:min-h-0 overflow-y-auto">
            
            <!-- Mobile Header Logo & Styled Card Banner (Only visible on screens below lg) -->
            <div class="lg:hidden mb-6 text-center w-full max-w-md bg-white/80 backdrop-blur-sm p-6 rounded-2xl shadow-sm border border-slate-200/60">
                <img src="../../assets/images/logos/doms-logo.png" alt="DOMS Logo" class="w-48 h-auto mx-auto mb-3">
                <h1 class="text-gray-800 text-xl font-bold">STI Discipline Office</h1>
                <p class="text-gray-500 text-xs">Management System</p>
            </div>

            <!-- Login Form Card Container -->
            <div class="w-full max-w-md bg-white lg:bg-transparent p-6 sm:p-8 lg:p-0 rounded-2xl lg:rounded-none shadow-sm lg:shadow-none border border-slate-200/60 lg:border-none">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Sign In</h2>
                <p class="text-gray-500 mb-8">Please enter your credentials to continue</p>

                <!-- Error Message Alert -->
                <?php
                $error = $_GET['error'] ?? null;
                if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-red-800 mb-1">Login Failed</h4>
                            <p class="text-sm text-red-700">
                                <?php
                                if ($error === 'invalid') {
                                    echo 'Wrong username or password. Please try again.';
                                } elseif ($error === 'empty') {
                                    echo 'Please enter both username and password.';
                                } else {
                                    echo 'An error occurred during login. Please try again.';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login_handler.php">
                    <!-- Email Address -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                        <input type="text" name="username" placeholder="nameid@sti.edu" required
                            value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-gray-700 text-sm font-medium">Password</label>
                        </div>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Remember me & Need help -->
                    <div class="flex justify-between items-center mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember_me"
                                <?php echo isset($_GET['remember_me']) ? 'checked' : ''; ?>
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="forgotPasswordEmail.php" class="text-blue-500 text-sm hover:underline">Forgot password?</a>
                    </div>

                    <!-- Sign In Button -->
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition mb-6 shadow-sm hover:shadow">
                        Sign In
                    </button>
                </form>

                <!-- System Information (Hidden on screens smaller than lg) -->
                <div class="hidden lg:block mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">System Information</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">System Online</span>
                            <div class="ml-auto">
                                <div class="w-2 h-2 bg-purple-500 rounded-full inline-block"></div>
                                <span class="text-sm text-gray-600 ml-2">Version 0.0.1</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Last Update: 11/15/2025</span>
                            <div class="ml-auto">
                                <div class="w-2 h-2 bg-pink-500 rounded-full inline-block"></div>
                                <span class="text-sm text-gray-600 ml-2">Support: STIhelp.edu</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Need assistance -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-1">Need assistance?</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">If you're having trouble accessing your account, please contact
                            the IT Helpdesk at <span class="text-blue-600 font-medium">support@sti.edu</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>