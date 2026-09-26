<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$message = null;
$error = null;
$archivePreview = [];
$settingKeys = [
    'archive_after_days',
    'sy_start_date', 'sy_end_date', 'first_semester_start', 'first_semester_end',
    'second_semester_start', 'second_semester_end'
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === '1' && ($_POST['action'] ?? '') === 'save_banner') {
        setSystemSetting('global_banner_text', trim($_POST['global_banner_text'] ?? ''));
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === '1' && ($_POST['action'] ?? '') === 'toggle_banner') {
        setSystemSetting('global_banner_enabled', ($_POST['enabled'] ?? '') === '1' ? 'enabled' : 'disabled');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'enabled' => getSystemSetting('global_banner_enabled') === 'enabled']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_settings') {
            foreach ($settingKeys as $key) {
                $value = $_POST[$key] ?? '';
                if ($key === 'archive_after_days') {
                    $value = max(0, (int)$value);
                }
                setSystemSetting($key, $value);
            }
            $message = 'Academic and announcement settings saved.';
        } elseif ($action === 'archive_preview') {
            $days = (int)getSystemSetting('archive_after_days', 30);
            $archivePreview = fetchAll("SELECT c.case_id, c.status, c.resolved_date, CONCAT_WS(' ', s.first_name, NULLIF(s.middle_name, ''), s.last_name) AS student_name FROM cases c LEFT JOIN students s ON s.student_id = c.student_id WHERE c.is_archived = 0 AND c.status IN ('Resolved', 'Dismissed') AND c.resolved_date IS NOT NULL AND c.resolved_date <= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY) ORDER BY c.resolved_date ASC LIMIT 100", [$days]);
            $message = 'Dry run found ' . count($archivePreview) . ' eligible records' . (count($archivePreview) === 100 ? ' (showing the first 100).' : '.');
        } elseif ($action === 'archive_now') {
            $stmt = executeQuery("UPDATE cases SET is_archived = 1, archived_at = NOW() WHERE is_archived = 0 AND status IN ('Resolved', 'Dismissed') AND resolved_date IS NOT NULL AND resolved_date <= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)", [(int)getSystemSetting('archive_after_days', 30)]);
            $message = $stmt->rowCount() . ' cases were soft-archived.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$settings = [];
foreach ($settingKeys as $key) {
    $settings[$key] = getSystemSetting($key, '');
}
$settings['global_banner_enabled'] = getSystemSetting('global_banner_enabled', 'disabled');
$settings['global_banner_text'] = getSystemSetting('global_banner_text', '');
$pageTitle = 'Academic & System Settings';
$adminName = getFormattedUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI Discipline Office - <?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="flex h-screen">
<div class="flex-1 overflow-y-auto ml-64">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<main class="p-8 pt-28 min-h-screen transition-colors duration-300 space-y-6">
    <?php if ($message): ?><div class="admin-alert admin-alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert admin-alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($archivePreview): ?><div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"><div class="max-h-[85vh] w-full max-w-4xl overflow-auto rounded-lg bg-white dark:bg-[#111827] shadow-xl"><div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700 p-6"><h2 class="text-xl font-bold">Archive term preview</h2><a href="<?= BASE_URL ?>/modules/super-admin/systemControl.php" aria-label="Close preview" class="text-2xl text-gray-400">&times;</a></div><div class="overflow-x-auto p-6"><table class="w-full" style="table-layout: fixed"><thead class="bg-gray-100 dark:bg-slate-800"><tr><th class="px-4 py-3 text-left text-xs uppercase">Case</th><th class="px-4 py-3 text-left text-xs uppercase">Student</th><th class="px-4 py-3 text-left text-xs uppercase">Status</th><th class="px-4 py-3 text-left text-xs uppercase">Resolved</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-slate-700"><?php foreach ($archivePreview as $record): ?><tr><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['case_id']) ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['student_name'] ?? 'Unknown') ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['status']) ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['resolved_date']) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="flex justify-end gap-3 border-t border-gray-200 dark:border-slate-700 p-6"><a class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg" href="<?= BASE_URL ?>/modules/super-admin/systemControl.php">Cancel</a><form method="post"><input type="hidden" name="action" value="archive_now"><button class="px-4 py-2.5 bg-amber-600 text-white rounded-lg font-medium">Soft-archive records</button></form></div></div></div><?php endif; ?>
    <form method="post" class="space-y-6">
        <input type="hidden" name="action" value="save_settings">
        <section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700"><h2 class="text-xl font-bold mb-6">Academic term management</h2><div class="grid grid-cols-1 lg:grid-cols-2 gap-8"><div class="space-y-4"><div class="grid grid-cols-2 gap-4"><?php foreach (['sy_start_date'=>'SY start','sy_end_date'=>'SY end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>"></label><?php endforeach; ?></div><div class="grid grid-cols-2 gap-4"><?php foreach (['first_semester_start'=>'1st semester start','first_semester_end'=>'1st semester end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>"></label><?php endforeach; ?></div><div class="grid grid-cols-2 gap-4"><?php foreach (['second_semester_start'=>'2nd semester start','second_semester_end'=>'2nd semester end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>"></label><?php endforeach; ?></div></div><div class="flex flex-col justify-between"><label class="block text-sm font-semibold">Auto-archive resolved cases after semester end (days)<input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="number" min="0" name="archive_after_days" value="<?= htmlspecialchars($settings['archive_after_days']) ?>"></label><button class="self-start px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Save settings</button></div></div></section>
    </form>
    <section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 space-y-4"><h2 class="text-xl font-bold">Global announcements and banners</h2><div class="flex flex-col lg:flex-row gap-3 items-center"><input class="flex-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" id="globalBannerText" name="global_banner_text" value="<?= htmlspecialchars($settings['global_banner_text']) ?>" maxlength="500" placeholder="Announcement text"><button type="button" id="bannerStatusButton" class="px-4 py-2.5 rounded-lg font-medium text-white <?= $settings['global_banner_enabled'] === 'enabled' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' ?>" data-enabled="<?= $settings['global_banner_enabled'] === 'enabled' ? '1' : '0' ?>"><?= $settings['global_banner_enabled'] === 'enabled' ? 'Enabled' : 'Disabled' ?></button><button type="button" id="updateBannerButton" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">Update Announcement</button></div></section>
    <section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"><h2 class="text-xl font-bold">Manual Archive Term</h2><div class="flex gap-3"><form method="post"><input type="hidden" name="action" value="archive_preview"><button class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">Dry-run preview</button></form><form method="post" onsubmit="return confirm('Soft-archive eligible resolved cases now?')"><input type="hidden" name="action" value="archive_now"><button class="px-4 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700">Archive term now</button></form></div></section>
</main></div></div>
<script src="<?= BASE_URL ?>/assets/js/super-admin/systemControl.js"></script>
</body></html>
