<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load sidebar function definitions
require_once __DIR__ . '/../includes/functions.php';

// Get current page for highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Default to Dashboard if nothing else is set
if (empty($currentPage) || $currentPage === 'index.php') {
    $currentPage = 'doDashboard.php';
}

// Determine user role (fallback to 'do')
$role = $_SESSION['user_role'] ?? 'do';

// Fetch sidebar items
$sidebarItems = get_sidebar_items($role);
?>

<aside class="w-64 flex flex-col fixed top-0 left-0 h-screen border-r border-slate-700 dark:border-gray-800
               bg-[#1E2B3B] dark:bg-[#030712] text-white transition-colors duration-300">

    <!-- Logo -->
    <div class="justify-center pt-2 flex items-center space-x-3">
        <img src="/PrototypeDO/assets/images/logos/STI-logo.png" alt="STI Logo" class="w-30 h-auto" />
    </div>

    <!-- Department Badge -->
    <div class="px-4 pt-2 justify-center flex items-center space-x-2 mx-4 mb-4">
        <span class="text-blue-900 font-bold text-xl bg-yellow-400 p-2 rounded">DO</span>
        <span class="text-white text-md px-2 py-1">Discipline Office</span>
    </div>


    <!-- Navigation -->
    <nav class="flex-1 px-3 py-1 space-y-1 overflow-y-auto">
        <?php foreach ($sidebarItems as $item):
            $isActive = basename($item['path']) === $currentPage;
            ?>
            <a href="<?= htmlspecialchars($item['path']) ?>" class="flex items-center px-3 py-2 rounded-lg transition-all duration-150 active:scale-95 hover:shadow-sm
                      <?= $isActive
                          ? 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white'
                          : 'text-gray-300 hover:bg-[#33475F] dark:hover:bg-slate-700' ?>">
                <img src="/PrototypeDO/assets/images/icons/<?= htmlspecialchars($item['icon']) ?>"
                    alt="<?= htmlspecialchars($item['label']) ?> icon" class="w-5 h-5 flex-shrink-0" />
                <span class="px-3"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Log Out Button -->
    <div class="p-4 border-t border-[#374151] dark:border-gray-800">
        <a href="/PrototypeDO/modules/login/logout.php" class="w-full flex items-center justify-center space-x-2 
                  bg-[#33475F] hover:bg-[#991B1B] 
                  dark:bg-[#111827] dark:hover:bg-[#7F1D1D] 
                  text-white font-medium py-2 px-4 rounded-lg 
                  transition-all duration-200 active:scale-95 hover:shadow-sm">
            <img src="/PrototypeDO/assets/images/icons/Log-Out-icon.png" alt="Logout icon" class="w-5 h-5" />
            <span>Log Out</span>
        </a>
    </div>

</aside>