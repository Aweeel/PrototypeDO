<?php
//lostAndFound.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/lostAndFoundFunctions.php';

// FIXED: Use user_role instead of role
if (!in_array($_SESSION['user_role'], ['discipline_office', 'super_admin'])) {
    header('Location: /PrototypeDO/index.php');
    exit;
}

$pageTitle = "Lost & Found Management";
$adminName = getFormattedUserName();

// Get statistics
$stats = getLostFoundStats();
$categories = getCategories();

// Get filter parameters
$filterStatus = $_GET['status'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$highlightItemId = $_GET['highlightItemId'] ?? '';
$view = ($_GET['view'] ?? 'active') === 'archived' ? 'archived' : 'active';

// Server-side pagination setup
$filters = [];
if ($filterStatus) $filters['status'] = $filterStatus;
if ($filterCategory) $filters['category'] = $filterCategory;
if ($searchTerm) $filters['search'] = $searchTerm;
if ($view === 'archived') $filters['archived'] = true;

$items = getLostFoundItems($filters);
$totalItems = is_array($items) ? count($items) : 0;
$perPage = 10;
$pageParam = $_GET['page'] ?? null;
if (is_scalar($pageParam) && is_numeric($pageParam)) {
    $lfCurrentPage = max(1, (int)$pageParam);
} else {
    $lfCurrentPage = 1;
}
$totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 1;
if ($lfCurrentPage > $totalPages) {
    $lfCurrentPage = $totalPages;
}

if (!empty($highlightItemId) && $totalItems > 0) {
    foreach ($items as $index => $candidateItem) {
        if ((string) $candidateItem['item_id'] === (string) $highlightItemId) {
            $lfCurrentPage = (int) floor($index / $perPage) + 1;
            break;
        }
    }
    if ($lfCurrentPage > $totalPages) {
        $lfCurrentPage = $totalPages;
    }
}

$startIndex = max(0, ($lfCurrentPage - 1) * $perPage);
$itemsToShow = $totalItems > 0 ? array_slice($items, $startIndex, $perPage) : [];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - <?php echo htmlspecialchars($pageTitle); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

            <main class="p-8 pt-28 min-h-screen">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-6 transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Unclaimed</p>
                                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $stats['unclaimed']; ?></p>
                            </div>
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                <i class="fas fa-exclamation-circle text-yellow-600 dark:text-yellow-400 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-6 transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Claimed</p>
                                <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $stats['claimed']; ?></p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    

                    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-6 transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Last 7 Days</p>
                                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo $stats['recent']; ?></p>
                            </div>
                            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Tabs -->
                <div class="flex gap-2 mt-6">
                    <a href="?view=active"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition <?php echo $view === 'active' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-[#111827] border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'; ?>">
                        <i class="fas fa-box-open mr-2"></i>Active Items
                    </a>
                    <a href="?view=archived"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition <?php echo $view === 'archived' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-[#111827] border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'; ?>">
                        <i class="fas fa-box-archive mr-2"></i>Archived (<?php echo $stats['archived'] ?? 0; ?>)
                    </a>
                </div>

                <!-- Main Content -->
                <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm mt-6">
                    <!-- Header with Actions -->
                    <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fas fa-box-open mr-2 text-blue-600 dark:text-blue-400"></i>
                                <?php echo $view === 'archived' ? 'Archived Items' : 'Lost & Found Items'; ?>
                            </h2>
                            <?php if ($view === 'active'): ?>
                                <button onclick="openAddModal()" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-500 transition shadow-md">
                                    <i class="fas fa-plus mr-2"></i>Add Item
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Filters -->
                        <form method="GET" action="" id="lostFoundFilterForm" class="grid grid-cols-1 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] gap-4 items-end">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                            <div>
                                <input type="text" 
                                       name="search" 
                                       id="lostFoundSearchFilter"
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       placeholder="Search items..."
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                            </div>
                            <div>
                                <select name="status" 
                                        id="lostFoundStatusFilter"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                                    <option value="">All Statuses</option>
                                    <option value="Unclaimed" <?php echo $filterStatus === 'Unclaimed' ? 'selected' : ''; ?>>Unclaimed</option>
                                    <option value="Claimed" <?php echo $filterStatus === 'Claimed' ? 'selected' : ''; ?>>Claimed</option>
                                </select>
                            </div>
                            <div>
                                <select name="category" 
                                        id="lostFoundCategoryFilter"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex justify-end">
                                <a href="?view=<?php echo htmlspecialchars($view); ?>"
                                   class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Reload page">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-[#0F1623] border-b border-gray-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location Found</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date Found</th>
                                    <?php if ($view === 'archived'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Archived On</th>
                                    <?php else: ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                <?php if ($totalItems === 0): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-4xl mb-4"></i>
                                            <p class="text-lg"><?php echo $view === 'archived' ? 'No archived items' : 'No items found'; ?></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($itemsToShow as $item): ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-[#0F1623] transition <?php echo (!empty($highlightItemId) && (string) $item['item_id'] === (string) $highlightItemId) ? 'bg-blue-100 dark:bg-blue-900/20 ring-2 ring-blue-500 shadow-sm' : ''; ?>"
                                                data-item-id="<?php echo htmlspecialchars($item['item_id']); ?>"
                                                data-item-name="<?php echo htmlspecialchars(strtolower($item['item_name'])); ?>"
                                                data-category="<?php echo htmlspecialchars(strtolower($item['category'])); ?>"
                                                data-status="<?php echo htmlspecialchars(strtolower($item['status'])); ?>"
                                                data-location="<?php echo htmlspecialchars(strtolower($item['found_location'])); ?>"
                                                data-date-found="<?php echo htmlspecialchars($item['date_found']); ?>">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                <?php echo htmlspecialchars($item['item_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                <div class="font-medium"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <?php if ($item['finder_name']): ?>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Finder: <?php echo htmlspecialchars($item['finder_name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                    <?php echo htmlspecialchars($item['category']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                <?php echo htmlspecialchars($item['found_location']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                <?php echo date('M d, Y', strtotime($item['date_found'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if ($view === 'archived'): ?>
                                                    <span class="text-gray-700 dark:text-gray-300">
                                                        <?php echo !empty($item['archived_at']) ? date('M d, Y', strtotime($item['archived_at'])) : '—'; ?>
                                                    </span>
                                                <?php elseif ($item['status'] === 'Claimed'): ?>
                                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                                        <i class="fas fa-check-circle mr-1"></i>Claimed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                                        <i class="fas fa-clock mr-1"></i>Unclaimed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex gap-2">
                                                    <button onclick="viewItem('<?php echo $item['item_id']; ?>')" 
                                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($view === 'archived'): ?>
                                                        <button onclick="restoreItem('<?php echo $item['item_id']; ?>')" 
                                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" 
                                                                title="Restore">
                                                            <i class="fas fa-box-open"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button onclick="editItem('<?php echo $item['item_id']; ?>')" 
                                                                class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300" 
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($item['status'] === 'Unclaimed'): ?>
                                                            <button onclick="markClaimed('<?php echo $item['item_id']; ?>')" 
                                                                    class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300" 
                                                                    title="Mark as Claimed">
                                                                <i class="fas fa-hand-holding"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button onclick="markUnclaimed('<?php echo $item['item_id']; ?>')" 
                                                                    class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300" 
                                                                    title="Mark as Unclaimed">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button onclick="archiveItemPrompt('<?php echo $item['item_id']; ?>')" 
                                                                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" 
                                                                title="Archive">
                                                            <i class="fas fa-box-archive"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="lostFoundNoResultsRow" class="hidden">
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-filter text-4xl mb-4"></i>
                                            <p class="text-lg">No items match the selected filters</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                        <!-- Pagination (moved outside the table card) -->
                        <div class="mt-4 flex items-center justify-between px-6 py-2">
                            <div id="paginationInfo" class="text-sm text-gray-600 dark:text-gray-400">
                                Showing <?php echo $startIndex + 1; ?>-<?php echo min($startIndex + $perPage, $totalItems); ?> of <?php echo $totalItems; ?> items
                            </div>
                            <div id="paginationButtons" class="flex gap-2 text-sm">
                                <?php
                                $maxButtons = 7;
                                $queryParams = $_GET;
                                $queryParams['view'] = $view;

                                $buildUrl = function($p) use ($queryParams) {
                                    $qp = $queryParams;
                                    $qp['page'] = (int)$p;
                                    return '?' . http_build_query($qp);
                                };

                                $btnBase = 'px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 min-w-[44px] text-center inline-flex items-center justify-center';
                                $active = 'px-3 py-2 rounded-lg bg-blue-600 text-white font-semibold min-w-[44px] text-center inline-flex items-center justify-center';
                                $disabledClass = 'opacity-50 cursor-not-allowed';

                                // Previous (always rendered)
                                if ($lfCurrentPage > 1) {
                                    echo '<button onclick="window.location.href=\'' . $buildUrl($lfCurrentPage - 1) . '\'" class="' . $btnBase . '">« Prev</button>';
                                } else {
                                    echo '<span class="' . $btnBase . ' ' . $disabledClass . '" aria-disabled="true">« Prev</span>';
                                }

                                if ($totalPages <= $maxButtons) {
                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        if ($i == $lfCurrentPage) echo '<span class="' . $active . '">' . $i . '</span>';
                                        else echo '<button onclick="window.location.href=\'' . $buildUrl($i) . '\'" class="' . $btnBase . '">' . $i . '</button>';
                                    }
                                } else {
                                    $innerCount = $maxButtons - 2;
                                    $start = max(2, $lfCurrentPage - floor($innerCount / 2));
                                    $end = min($totalPages - 1, $start + $innerCount - 1);
                                    if ($end - $start + 1 < $innerCount) {
                                        $start = max(2, $end - $innerCount + 1);
                                    }

                                    // First
                                    if (1 == $lfCurrentPage) echo '<span class="' . $active . '">1</span>';
                                    else echo '<button onclick="window.location.href=\'' . $buildUrl(1) . '\'" class="' . $btnBase . '">1</button>';

                                    if ($start > 2) echo '<span class="' . $btnBase . ' ' . $disabledClass . '">&hellip;</span>';

                                    for ($i = $start; $i <= $end; $i++) {
                                        if ($i == $lfCurrentPage) echo '<span class="' . $active . '">' . $i . '</span>';
                                        else echo '<button onclick="window.location.href=\'' . $buildUrl($i) . '\'" class="' . $btnBase . '">' . $i . '</button>';
                                    }

                                    if ($end < $totalPages - 1) echo '<span class="' . $btnBase . ' ' . $disabledClass . '">&hellip;</span>';

                                    // Last
                                    if ($totalPages == $lfCurrentPage) echo '<span class="' . $active . '">' . $totalPages . '</span>';
                                    else echo '<button onclick="window.location.href=\'' . $buildUrl($totalPages) . '\'" class="' . $btnBase . '">' . $totalPages . '</button>';
                                }

                                // Next (always rendered)
                                if ($lfCurrentPage < $totalPages) {
                                    echo '<button onclick="window.location.href=\'' . $buildUrl($lfCurrentPage + 1) . '\'" class="' . $btnBase . '">Next »</button>';
                                } else {
                                    echo '<span class="' . $btnBase . ' ' . $disabledClass . '" aria-disabled="true">Next »</span>';
                                }
                                ?>
                            </div>
                        </div>
            </main>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    <i class="fas fa-plus-circle mr-2 text-blue-600 dark:text-blue-400"></i>
                    Add Lost & Found Item
                </h3>
            </div>
            <form id="addItemForm" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item Name *</label>
                        <input type="text" name="item_name" required 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category *</label>
                        <!-- Custom Dropdown -->
                        <div id="customCategoryDropdown" class="relative">
                            <button type="button" id="categoryDropdownBtn" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 text-left flex items-center justify-between hover:border-gray-400 dark:hover:border-slate-500">
                                <span id="categoryDropdownDisplay">Select Category</span>
                                <i class="fas fa-chevron-down text-sm"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div id="categoryDropdownMenu" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-[#1F2937] border border-gray-300 dark:border-slate-600 rounded-lg shadow-lg z-10 max-h-64 overflow-y-auto">
                                <div class="p-2 space-y-1">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="category-option flex items-center justify-between px-3 py-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer group" data-value="<?php echo $cat; ?>" data-is-default="true">
                                            <span><?php echo $cat; ?></span>
                                            <button type="button" class="delete-category-x ml-2 px-2 py-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded opacity-0 group-hover:opacity-100 transition" onclick="deleteDefaultCategory(event, '<?php echo $cat; ?>')" title="Remove">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="category-option add-new-cat flex items-center px-3 py-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-t border-gray-200 dark:border-slate-600 mt-2 pt-3 text-blue-600 dark:text-blue-400" data-value="__add_new__">
                                        <i class="fas fa-plus mr-2"></i>Add New Category
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Hidden select for form submission -->
                        <select name="category" id="categorySelect" required class="hidden">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                            <option value="__add_new__">+ Add New Category</option>
                        </select>
                    </div>
                    <div id="newCategoryDiv" class="hidden col-span-2 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Category Name *</label>
                            <input type="text" id="newCategoryInput" placeholder="Enter new category name..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description (Optional)</label>
                            <input type="text" id="newCategoryDescription" placeholder="Brief description of this category..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="addCategoryBtn" onclick="createNewCategory()"
                                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-plus mr-1"></i>Create Category
                            </button>
                            <button type="button" onclick="cancelNewCategory()"
                                    class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition whitespace-nowrap">
                                <i class="fas fa-times mr-1"></i>Cancel
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Found *</label>
                        <input type="date" name="date_found" required value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Found</label>
                        <input type="time" name="time_found"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location Found *</label>
                        <input type="text" name="location" required placeholder="e.g., Cafeteria, Room 301"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Finder Name</label>
                        <input type="text" name="finder_name" placeholder="Optional"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Finder Student ID</label>
                        <input type="text" name="finder_student_id" placeholder="Optional"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea name="description" rows="3" placeholder="Detailed description of the item..."
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item Picture</label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition" id="imageDropZone">
                            <input type="file" name="item_image" id="itemImageInput" accept="image/*" class="hidden">
                            <div id="imagePreview" class="hidden">
                                <img id="previewImg" src="" alt="Preview" class="max-h-48 mx-auto rounded-lg mb-4">
                                <button type="button" onclick="clearImagePreview()" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800">Remove Image</button>
                            </div>
                            <div id="imagePrompt">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-gray-500 mb-2"></i>
                                <p class="text-gray-600 dark:text-gray-400">Drag and drop an image, or click to select</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PNG, JPG, WebP up to 5MB</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Add Item
                    </button>
                    <button type="button" onclick="closeAddModal()" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Delete Confirmation Modal -->
    <div id="categoryDeleteModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-[#1F2937] rounded-lg shadow-xl p-6 w-96">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Confirm Delete</h3>
            </div>
            <p class="text-gray-700 dark:text-gray-300 mb-6">
                Remove category "<span id="deleteCategoryName" class="font-semibold"></span>"? This cannot be undone.
            </p>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeCategoryDeleteModal()"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                    Cancel
                </button>
                <button type="button" id="confirmDeleteBtn" onclick="executeCategoryDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash-alt mr-2"></i>Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Category Cannot Delete Modal -->
    <div id="categoryCannotDeleteModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-[#1F2937] rounded-lg shadow-xl p-6 w-96">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-ban text-red-500 text-xl"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Cannot Delete Category</h3>
            </div>
            <p class="text-gray-700 dark:text-gray-300 mb-4">
                The category "<span id="cannotDeleteCategoryName" class="font-semibold"></span>" has <span id="itemCountSpan" class="font-semibold text-red-600">0</span> item(s) assigned to it.
            </p>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">
                Please remove or reassign these items to a different category before deleting this category.
            </p>
            <div class="flex justify-end">
                <button type="button" onclick="closeCannotDeleteModal()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
    <script src="/PrototypeDO/assets/js/lostAndFound.js"></script>
</body>
</html>