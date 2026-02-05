<?php
//searchLostAndFound.php
// Don't require auth_check for public search
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/lostAndFoundFunctions.php';

// Page metadata
$pageTitle = "Search Lost & Found";
$categories = getCategories();

// Handle search
$searchResults = [];
$hasSearched = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $hasSearched = true;
    $searchTerm = trim($_GET['search']);
    $category = !empty($_GET['category']) ? $_GET['category'] : null;
    
    try {
        $searchResults = searchLostItems($searchTerm, $category);
    } catch (Exception $e) {
        $errorMessage = "Search error: " . $e->getMessage();
        error_log("Lost & Found Search Error: " . $e->getMessage());
    }
}
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

        // Restore dark mode
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

<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="flex h-screen">
        <div class="flex-1 overflow-y-auto ml-64">
            <!-- Fixed Header -->
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <!-- Page Content -->
            <main class="p-8 pt-28 min-h-screen transition-colors duration-300">
                
                <?php if ($errorMessage): ?>
                    <!-- Error Message -->
                    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-6 mb-8">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl mr-4 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Error</h4>
                                <p class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($errorMessage); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Search Section -->
                <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-8 mb-8">
                    <div class="text-center mb-8">
                        <i class="fas fa-search text-6xl text-blue-600 dark:text-blue-400 mb-4"></i>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Lost Something?</h2>
                        <p class="text-gray-600 dark:text-gray-400">Search our database to see if your item has been found</p>
                    </div>

                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-search mr-2"></i>Search for your item
                                </label>
                                <input type="text" 
                                       name="search" 
                                       required
                                       placeholder="e.g., blue backpack, calculator, water bottle..."
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 text-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-filter mr-2"></i>Category (Optional)
                                </label>
                                <select name="category" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 text-lg">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" 
                                class="w-full px-6 py-3 bg-blue-600 text-white text-lg font-semibold rounded-lg hover:bg-blue-700 dark:hover:bg-blue-500 transition shadow-md transform hover:-translate-y-0.5">
                            <i class="fas fa-search mr-2"></i>Search Now
                        </button>
                    </form>
                </div>

                <!-- Search Results -->
                <?php if ($hasSearched && !$errorMessage): ?>
                    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm p-8">
                        <?php if (empty($searchResults)): ?>
                            <!-- No Results -->
                            <div class="text-center py-12">
                                <i class="fas fa-inbox text-6xl text-gray-400 dark:text-gray-600 mb-4"></i>
                                <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">No Matching Items Found</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">
                                    We couldn't find any items matching "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"
                                </p>
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 max-w-2xl mx-auto">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                        <i class="fas fa-lightbulb text-blue-600 dark:text-blue-400 mr-2"></i>
                                        What to do next:
                                    </h4>
                                    <ul class="text-left text-gray-700 dark:text-gray-300 space-y-2">
                                        <li><i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>Try different keywords or descriptions</li>
                                        <li><i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>Check back later - new items are added regularly</li>
                                        <li><i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>Visit the Discipline Office during office hours</li>
                                        <li><i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>Report your lost item to security personnel</li>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Results Found -->
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                                    Found <?php echo count($searchResults); ?> Matching Item<?php echo count($searchResults) !== 1 ? 's' : ''; ?>!
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Items matching "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"
                                </p>
                            </div>

                            <!-- Important Notice -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-6 mb-6">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-2xl mr-4 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Important:</h4>
                                        <p class="text-gray-700 dark:text-gray-300">
                                            If you found your item below, please visit the <strong>Discipline Office</strong> with valid <strong>proof of ownership</strong> 
                                            (receipt, photos, unique identifiers) to claim your item.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Results Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($searchResults as $item): ?>
                                    <div class="bg-gray-50 dark:bg-[#0F1623] border border-gray-200 dark:border-slate-700 rounded-lg p-6 hover:shadow-lg transition">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </h4>
                                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                    <?php echo htmlspecialchars($item['category']); ?>
                                                </span>
                                            </div>
                                            <i class="fas fa-box-open text-3xl text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        
                                        <div class="space-y-2 text-sm mb-4">
                                            <div class="flex items-center text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-calendar w-5 text-gray-500 dark:text-gray-400"></i>
                                                <span>Date found: <strong><?php echo date('M d, Y', strtotime($item['date_found'])); ?></strong></span>
                                            </div>
                                            <div class="flex items-center text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-info-circle w-5 text-gray-500 dark:text-gray-400"></i>
                                                <span>ID: <strong><?php echo htmlspecialchars($item['item_id']); ?></strong></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Footer Instructions -->
                            <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    <i class="fas fa-hand-holding text-blue-600 dark:text-blue-400 mr-2"></i>
                                    How to Claim Your Item:
                                </h4>
                                <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                    <li>Note the <strong>Item ID</strong> shown above</li>
                                    <li>Prepare <strong>proof of ownership</strong> (receipt, photos, serial number, etc.)</li>
                                    <li>Visit the <strong>Discipline Office</strong> during office hours (Mon-Fri, 8:00 AM - 5:00 PM)</li>
                                    <li>Present your valid <strong>School ID</strong> and proof of ownership</li>
                                    <li>Sign the claim form to retrieve your item</li>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="/PrototypeDO/assets/js/protect_pages.js"></script>
</body>
</html>