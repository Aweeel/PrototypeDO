<?php
session_start();

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
<!-- Rest of your login page -->

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

<body class="h-screen overflow-hidden">
    <div class="flex h-full">
        <!-- Left Side -->
        <div class="w-1/2 relative flex flex-col items-center justify-center p-12 overflow-hidden bg-slate-800">

            <!-- Background Image -->
            <div class="absolute inset-0 z-0"
                style="background-image: url('../../assets/images/backgrounds/loginhalf.png'); background-size: cover; background-position: center;">
            </div>

            <div class="mb-8 relative z-10">
                <div>
                    <img src="../../assets/images/logos/STI-logo-big.png" alt="STI Logo" class="w-50 h-auto">
                </div>
            </div>
            <!-- Text and Buttons (z-10 to be on top) -->
            <div class="relative z-10 text-center">
                <!-- Title -->
                <h1 class="text-white text-2xl font-bold mb-2">Welcome to STI Discipline Office</h1>
                <h2 class="text-white text-2xl font-bold mb-8">Management System</h2>

                <!-- Description -->
                <p class="text-gray-300 max-w-md mb-12">
                    Create your account to access our comprehensive education management platform and connect with the
                    STI community.
                </p>

                <!-- Buttons -->
                <!-- Label Group -->
                <div class="flex flex-col items-center gap-4 mb-6">
                    <!-- Top Two Labels -->
                    <div class="flex gap-4">
                        <!-- School Handbook -->
                        <div class="bg-slate-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>School Handbook</span>
                        </div>

                        <!-- Student Records -->
                        <div class="bg-slate-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                            <span>Student Records</span>
                        </div>
                    </div>

                    <!-- Secure Access Label -->
                    <div class="flex items-center gap-2 bg-slate-700 text-white px-5 py-2 rounded-lg text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Secure Access</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Side - White Background with Login Form -->
        <div class="w-1/2 bg-white flex items-center justify-center p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Sign In</h2>
                <p class="text-gray-500 mb-8">Please enter your credentials to continue</p>

                <form method="POST" action="login_handler.php">
                    <!-- Email Address -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                        <input type="text" name="username" placeholder="Username" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-gray-700 text-sm font-medium">Password</label>
                            <a href="forgotPasswordEmail.php" class="text-blue-500 text-sm hover:underline">Forgot
                                password?</a>
                        </div>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Remember me & Need help -->
                    <div class="flex justify-between items-center mb-6">
                        <label class="flex items-center">
                            <input type="checkbox"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="#" class="text-blue-500 text-sm hover:underline">Need help?</a>
                    </div>

                    <!-- Sign In Button -->
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition mb-6">
                        Sign In
                    </button>

                    <!-- Contact administrator -->
                    <p class="text-center text-sm text-gray-600">
                        Don't have an account? <a href="#" class="text-blue-500 hover:underline">Contact
                            administrator</a>
                    </p>
                </form>

                <!-- System Information -->
                <div class="mt-12 pt-8 border-t border-gray-200">
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
                            <span class="text-sm text-gray-600">Last Update: 10/22/2025</span>
                            <div class="ml-auto">
                                <div class="w-2 h-2 bg-pink-500 rounded-full inline-block"></div>
                                <span class="text-sm text-gray-600 ml-2">Support: helpdesk.edu</span>
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
                        <p class="text-xs text-gray-600">If you're having trouble accessing your account, please contact
                            the IT Helpdesk at <span class="text-blue-600">support@sti.edu</span> or call <span
                                class="text-blue-600">1-800-STI-HELP</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>