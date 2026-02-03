<?php
// Debug file for Lost & Found access issues
// Place this file at: C:\XAMPP\htdocs\PrototypeDO\modules\do\debug_lostfound.php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Debug Info</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">🔍 Lost & Found Access Debug</h1>
        
        <div class="space-y-6">
            <!-- Session Information -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-blue-900 mb-3">📋 Session Information</h2>
                <div class="space-y-2 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Session Active:</span>
                        <span class="text-green-600"><?php echo isset($_SESSION) ? 'YES ✓' : 'NO ✗'; ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">User ID:</span>
                        <span><?php echo isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : 'NOT SET'; ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Username:</span>
                        <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'NOT SET'; ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Admin Name:</span>
                        <span><?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'NOT SET'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Role Information (CRITICAL) -->
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-yellow-900 mb-3">🎭 Role Information (MOST IMPORTANT)</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Current Role:</span>
                        <span class="text-2xl font-bold text-red-600">
                            <?php 
                            if (isset($_SESSION['role'])) {
                                echo '"' . htmlspecialchars($_SESSION['role']) . '"';
                            } else {
                                echo 'NOT SET ❌';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Role Type:</span>
                        <span><?php echo isset($_SESSION['role']) ? gettype($_SESSION['role']) : 'N/A'; ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <span class="font-semibold">Role Length:</span>
                        <span><?php echo isset($_SESSION['role']) ? strlen($_SESSION['role']) . ' characters' : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Access Check Results -->
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-green-900 mb-3">✅ Access Check Results</h2>
                <?php
                $allowed_roles = ['discipline_office', 'do', 'super_admin'];
                $current_role = $_SESSION['role'] ?? 'NONE';
                $has_access = in_array($current_role, $allowed_roles);
                ?>
                <div class="space-y-3">
                    <div class="mb-4 p-3 <?php echo $has_access ? 'bg-green-100' : 'bg-red-100'; ?> rounded">
                        <p class="text-lg font-bold <?php echo $has_access ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php if ($has_access): ?>
                                ✓ ACCESS GRANTED - You should be able to access Lost & Found
                            <?php else: ?>
                                ✗ ACCESS DENIED - This is why you're being redirected
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <p class="font-semibold mb-2">Checking role against allowed roles:</p>
                    <div class="space-y-2">
                        <?php foreach ($allowed_roles as $role): ?>
                            <?php 
                            $matches = ($current_role === $role);
                            $comparison = $current_role . ' === ' . $role;
                            ?>
                            <div class="flex items-center justify-between p-2 bg-white rounded border <?php echo $matches ? 'border-green-500' : 'border-gray-300'; ?>">
                                <code class="text-sm"><?php echo htmlspecialchars($comparison); ?></code>
                                <span class="<?php echo $matches ? 'text-green-600 font-bold' : 'text-gray-400'; ?>">
                                    <?php echo $matches ? '✓ MATCH' : '✗ No match'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- All Session Data -->
            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-purple-900 mb-3">📦 Complete Session Data</h2>
                <pre class="bg-white p-4 rounded border overflow-x-auto text-xs"><?php 
                if (isset($_SESSION) && !empty($_SESSION)) {
                    print_r($_SESSION);
                } else {
                    echo "Session is empty or not started";
                }
                ?></pre>
            </div>

            <!-- File Paths Check -->
            <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-indigo-900 mb-3">📁 File Paths Check</h2>
                <div class="space-y-2 text-sm">
                    <?php
                    $files_to_check = [
                        'lostAndFound.php' => __DIR__ . '/lostAndFound.php',
                        'lostAndFoundFunctions.php' => __DIR__ . '/../../includes/lostAndFoundFunctions.php',
                        'lostAndFoundapi.php' => __DIR__ . '/lostAndFoundapi.php',
                        'config.php' => __DIR__ . '/../../includes/config.php',
                        'auth_check.php' => __DIR__ . '/../../includes/auth_check.php'
                    ];
                    
                    foreach ($files_to_check as $name => $path):
                        $exists = file_exists($path);
                    ?>
                        <div class="grid grid-cols-3 gap-2 p-2 bg-white rounded">
                            <span class="font-semibold"><?php echo $name; ?>:</span>
                            <span class="col-span-2 text-xs break-all"><?php echo $path; ?></span>
                            <span class="<?php echo $exists ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $exists ? '✓ Exists' : '✗ Missing'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-red-900 mb-3">💡 Recommendations</h2>
                <ol class="list-decimal list-inside space-y-2 text-sm">
                    <?php if (!$has_access): ?>
                        <li class="text-red-700 font-bold">
                            Your current role "<?php echo htmlspecialchars($current_role); ?>" is NOT in the allowed roles list.
                        </li>
                        <li>Check your database to see what role is actually stored for this user</li>
                        <li>Update the role in the database OR update the authorization check in lostAndFound.php</li>
                    <?php else: ?>
                        <li class="text-green-700">Your role has access! The issue might be elsewhere:</li>
                        <li>Check if auth_check.php is redirecting you before reaching lostAndFound.php</li>
                        <li>Check browser console for JavaScript errors</li>
                        <li>Check Apache error logs for PHP errors</li>
                    <?php endif; ?>
                    <li>Make sure the database connection is working properly</li>
                    <li>Clear your browser cookies/cache and try logging in again</li>
                </ol>
            </div>

            <!-- SQL Query to Check Database -->
            <div class="bg-gray-50 border-l-4 border-gray-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-gray-900 mb-3">🔍 SQL Query to Check Your Role</h2>
                <p class="text-sm mb-2">Run this query in your database to see what role is stored:</p>
                <pre class="bg-gray-800 text-green-400 p-4 rounded text-sm overflow-x-auto">SELECT user_id, username, admin_name, role 
FROM admins 
WHERE user_id = '<?php echo isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : 'YOUR_USER_ID'; ?>';</pre>
                
                <p class="text-sm mt-4 mb-2">Or see all roles in the system:</p>
                <pre class="bg-gray-800 text-green-400 p-4 rounded text-sm overflow-x-auto">SELECT user_id, username, admin_name, role 
FROM admins 
ORDER BY role;</pre>
            </div>

            <!-- Action Links -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <h2 class="text-xl font-semibold text-blue-900 mb-3">🔗 Quick Actions</h2>
                <div class="space-y-2">
                    <a href="/PrototypeDO/modules/do/lostAndFound.php" 
                       class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-center">
                        Try Accessing Lost & Found Page
                    </a>
                    <a href="/PrototypeDO/modules/do/doDashboard.php" 
                       class="block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-center">
                        Go to Dashboard
                    </a>
                    <a href="/PrototypeDO/logout.php" 
                       class="block px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-center">
                        Logout and Re-login
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>Debug file created: <?php echo __FILE__; ?></p>
            <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>