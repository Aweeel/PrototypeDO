<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
if (($_SESSION['user_role'] ?? '') !== 'super_admin') { header('Location: ' . BASE_URL . '/index.php'); exit; }
$backupDirectory = __DIR__ . '/../../assets/backups';
if (!is_dir($backupDirectory)) { mkdir($backupDirectory, 0755, true); }
function recoverySqlIdentifier($value) { return '`' . str_replace('`', '``', (string)$value) . '`'; }
function recoverySqlLiteral($value) { if ($value === null) return 'NULL'; return "'" . str_replace("'", "''", (string)$value) . "'"; }
function createRecoverySnapshot($tables) {
    $output = "-- PrototypeDO MySQL snapshot generated " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        $columns = fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION", [$table]);
        if (!$columns) continue;
        $names = array_column($columns, 'COLUMN_NAME');
        $quotedTable = recoverySqlIdentifier($table);
        foreach (fetchAll('SELECT * FROM ' . $quotedTable) as $row) {
            $values = [];
            foreach ($names as $name) $values[] = recoverySqlLiteral($row[$name] ?? null);
            $quotedColumns = implode(', ', array_map('recoverySqlIdentifier', $names));
            $output .= 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        $output .= "\n";
    }
    return $output;
}
$message = null; $error = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'toggle_maintenance' && ($_POST['ajax'] ?? '') === '1') {
            setSystemSetting('maintenance_mode', ($_POST['enabled'] ?? '') === '1' ? 'enabled' : 'disabled');
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'enabled' => getSystemSetting('maintenance_mode') === 'enabled']);
            exit;
        } elseif ($action === 'save_maintenance') {
            setSystemSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? 'enabled' : 'disabled');
            $message = 'Maintenance mode setting saved.';
        } elseif ($action === 'download_backup') {
            $filename = 'PrototypeDO_snapshot_' . date('Ymd_His') . '.sql';
            $path = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($path, createRecoverySnapshot(['system_settings', 'users', 'students', 'offense_types', 'sanctions', 'cases', 'lost_found_items', 'audit_log']), LOCK_EX);
            $history = json_decode(getSystemSetting('backup_history', '[]'), true) ?: [];
            array_unshift($history, ['filename' => $filename, 'created_at' => date('Y-m-d H:i:s'), 'size' => filesize($path)]);
            setSystemSetting('backup_history', json_encode(array_slice($history, 0, 25)));
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
    }
} catch (Throwable $e) { $error = $e->getMessage(); }
$maintenanceEnabled = getSystemSetting('maintenance_mode', 'disabled') === 'enabled';
$history = json_decode(getSystemSetting('backup_history', '[]'), true) ?: [];
$historyPage = max(1, (int)($_GET['backup_page'] ?? 1));
$historyPerPage = 7;
$historyPages = max(1, (int)ceil(count($history) / $historyPerPage));
$historyPage = min($historyPage, $historyPages);
$historyEntries = array_slice($history, ($historyPage - 1) * $historyPerPage, $historyPerPage);
$pageTitle = 'Data Recovery'; $adminName = getFormattedUserName();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= htmlspecialchars($pageTitle) ?></title><script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={darkMode:'class'};</script></head>
<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]"><?php include __DIR__ . '/../../includes/sidebar.php'; ?><div class="flex h-screen"><div class="flex-1 overflow-y-auto ml-64"><?php include __DIR__ . '/../../includes/header.php'; ?><main class="p-8 pt-28 min-h-screen transition-colors duration-300 space-y-6"><?php if ($message): ?><div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex items-center justify-between gap-4"><h2 class="text-xl font-bold">System maintenance mode</h2><button type="button" id="maintenanceStatusButton" class="px-4 py-2.5 rounded-lg font-medium text-white <?= $maintenanceEnabled ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' ?>" data-enabled="<?= $maintenanceEnabled ? '1' : '0' ?>"><?= $maintenanceEnabled ? 'Enabled' : 'Disabled' ?></button></section>
<section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex items-center justify-between gap-4"><h2 class="text-xl font-bold">Database backup</h2><form method="post" id="backupForm"><input type="hidden" name="action" value="download_backup"><button class="px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">Generate .sql backup</button></form></section></div>
<section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700"><h2 class="text-xl font-bold mb-4">Backup history</h2><div class="overflow-x-auto"><table class="w-full" style="table-layout: fixed"><thead class="bg-gray-100 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">File</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Created</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Size</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Actions</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-slate-700"><?php foreach ($historyEntries as $backup): ?><tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50"><td class="px-6 py-4 text-sm"><?= htmlspecialchars($backup['filename'] ?? '') ?></td><td class="px-6 py-4 text-sm"><?= htmlspecialchars($backup['created_at'] ?? '') ?></td><td class="px-6 py-4 text-sm"><?= number_format(((int)($backup['size'] ?? 0)) / 1024, 1) ?> KB</td><td class="px-6 py-4"><a class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-sm" href="<?= BASE_URL ?>/assets/backups/<?= rawurlencode($backup['filename'] ?? '') ?>" download>Download</a></td></tr><?php endforeach; ?><?php if (!$history): ?><tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No backups generated yet.</td></tr><?php endif; ?></tbody></table></div></section>
</section><?php if ($historyPages > 1): ?><div class="flex items-center justify-between"><span class="text-sm text-gray-500 dark:text-gray-400">Page <?= $historyPage ?> of <?= $historyPages ?></span><div class="flex gap-2"><?php if ($historyPage > 1): ?><a class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg" href="?backup_page=<?= $historyPage - 1 ?>">Previous</a><?php endif; ?><?php if ($historyPage < $historyPages): ?><a class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg" href="?backup_page=<?= $historyPage + 1 ?>">Next</a><?php endif; ?></div></div><?php endif; ?></main></div></div><script src="<?= BASE_URL ?>/assets/js/super-admin/dataRecovery.js"></script></body></html>
