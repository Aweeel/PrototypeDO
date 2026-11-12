<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

if (($_SESSION['user_role'] ?? '') !== 'system_manager') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$modalMessage = null;
$modalError = null;
$archivePreview = [];
$previewRequested = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive_preview');
$settingKeys = [
    'archive_after_days',
    'sy_start_date', 'sy_end_date', 'first_semester_start', 'first_semester_end',
    'second_semester_start', 'second_semester_end'
];

function validateAcademicTermDates(array $values, bool $requireSemesters = true): array
{
    $keys = ['sy_start_date', 'sy_end_date'];
    if ($requireSemesters) {
        $keys = array_merge($keys, [
            'first_semester_start', 'first_semester_end',
            'second_semester_start', 'second_semester_end'
        ]);
    }

    $dates = [];
    foreach ($keys as $key) {
        $value = trim((string)($values[$key] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($value === '' || !$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('Please enter valid dates for the academic term settings.');
        }
        $dates[$key] = $date;
    }

    if ($dates['sy_end_date'] < $dates['sy_start_date']) {
        throw new RuntimeException('SY end must be on or after SY start.');
    }

    if ($requireSemesters) {
        foreach (['first_semester', 'second_semester'] as $semester) {
            $start = $dates[$semester . '_start'];
            $end = $dates[$semester . '_end'];
            if ($end < $start) {
                throw new RuntimeException(ucfirst(str_replace('_', ' ', $semester)) . ' end must be on or after its start.');
            }
            if ($start < $dates['sy_start_date'] || $end > $dates['sy_end_date']) {
                throw new RuntimeException(ucfirst(str_replace('_', ' ', $semester)) . ' dates must be within SY start and SY end.');
            }
        }
        if ($dates['first_semester_start'] <= $dates['second_semester_end']
            && $dates['second_semester_start'] <= $dates['first_semester_end']) {
            throw new RuntimeException('The semester date ranges must not overlap.');
        }
    }

    return $dates;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === '1' && ($_POST['action'] ?? '') === 'toggle_banner') {
        $bannerText = trim($_POST['global_banner_text'] ?? '');
        if (strlen($bannerText) > 250) {
            throw new RuntimeException('Global announcement must not exceed 250 characters.');
        }
        $bannerEnabled = ($_POST['enabled'] ?? '') === '1' ? 'enabled' : 'disabled';
        setSystemSetting('global_banner_text', $bannerText);
        setSystemSetting('global_banner_enabled', $bannerEnabled);
        $persistedBannerEnabled = getSystemSetting('global_banner_enabled', 'disabled');
        if ($persistedBannerEnabled !== $bannerEnabled) {
            throw new RuntimeException('Unable to persist the global announcement status.');
        }
        logAudit($_SESSION['user_id'] ?? null, 'Global Banner Toggled', 'system_settings', null, null, [
            'setting' => 'global_banner_enabled',
            'value' => $bannerEnabled,
            'text_length' => strlen($bannerText)
        ]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'enabled' => $persistedBannerEnabled === 'enabled']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_settings') {
            $termDates = validateAcademicTermDates($_POST);
            $changedSettings = [];
            foreach ($settingKeys as $key) {
                $value = $_POST[$key] ?? '';
                if ($key === 'archive_after_days') {
                    $value = max(0, (int)$value);
                }
                setSystemSetting($key, $value);
                $changedSettings[$key] = $value;
            }
            logAudit($_SESSION['user_id'] ?? null, 'System Settings Updated', 'system_settings', null, null, $changedSettings);
            $modalMessage = 'Academic and announcement settings saved.';
        } elseif ($action === 'archive_preview') {
            $termDates = validateAcademicTermDates([
                'sy_start_date' => getSystemSetting('sy_start_date', ''),
                'sy_end_date' => getSystemSetting('sy_end_date', '')
            ], false);
            $archivePreview = fetchAll(
                "SELECT c.case_id, c.status, c.date_reported, c.resolved_date,
                        CONCAT_WS(' ', s.first_name, NULLIF(s.middle_name, ''), s.last_name) AS student_name
                 FROM cases c
                 LEFT JOIN students s ON s.student_id = c.student_id
                 WHERE c.is_archived = 0
                   AND c.date_reported IS NOT NULL
                   AND (c.date_reported < ? OR c.date_reported > ?)
                 ORDER BY c.date_reported ASC
                 LIMIT 100",
                [$termDates['sy_start_date']->format('Y-m-d'), $termDates['sy_end_date']->format('Y-m-d')]
            );
            $modalMessage = 'Dry run found ' . count($archivePreview) . ' eligible records' . (count($archivePreview) === 100 ? ' (showing the first 100).' : '.');
        } elseif ($action === 'archive_now') {
            $termDates = validateAcademicTermDates([
                'sy_start_date' => getSystemSetting('sy_start_date', ''),
                'sy_end_date' => getSystemSetting('sy_end_date', '')
            ], false);
            $stmt = executeQuery(
                "UPDATE cases
                 SET is_archived = 1, archived_at = NOW()
                 WHERE is_archived = 0
                   AND date_reported IS NOT NULL
                   AND (date_reported < ? OR date_reported > ?)",
                [$termDates['sy_start_date']->format('Y-m-d'), $termDates['sy_end_date']->format('Y-m-d')]
            );
            $archivedCount = $stmt->rowCount();
            logAudit($_SESSION['user_id'] ?? null, 'Cases Bulk Archived', 'cases', null, null, [
                'archived_count' => $archivedCount,
                'sy_start_date' => $termDates['sy_start_date']->format('Y-m-d'),
                'sy_end_date' => $termDates['sy_end_date']->format('Y-m-d')
            ]);
            $modalMessage = $archivedCount . ' case(s) were archived successfully.';
        }
    }
} catch (Throwable $e) {
    $modalError = $e->getMessage();
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
    <script>(function(){if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark');}})();</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <style>
        input[type="date"] {
            color-scheme: light;
        }

        .dark input[type="date"] {
            color-scheme: dark;
        }

        @media (max-width: 639px) {
            input[type="date"],
            input[type="number"],
            input[type="text"] {
                min-height: 3.25rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#1F2937] text-gray-900 dark:text-gray-100 transition-colors duration-300 antialiased [scrollbar-gutter:stable]">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="flex h-screen overflow-hidden">
<div class="flex-1 overflow-y-auto ml-0 md:ml-64 transition-all duration-300">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<main class="p-4 pt-20 md:p-8 md:pt-28 min-h-screen transition-colors duration-300 space-y-6">
    <?php if ($previewRequested): ?><div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"><div class="max-h-[85vh] w-full max-w-4xl overflow-auto rounded-lg bg-white dark:bg-[#111827] shadow-xl"><div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700 p-6"><h2 class="text-xl font-bold">Archive term preview</h2><a href="<?= BASE_URL ?>/modules/super-admin/systemControl.php" aria-label="Close preview" class="text-2xl text-gray-400">&times;</a></div><div class="overflow-x-auto p-6"><table class="w-full" style="table-layout: fixed"><thead class="bg-gray-100 dark:bg-slate-800"><tr><th class="px-4 py-3 text-left text-xs uppercase">Case</th><th class="px-4 py-3 text-left text-xs uppercase">Student</th><th class="px-4 py-3 text-left text-xs uppercase">Status</th><th class="px-4 py-3 text-left text-xs uppercase">Reported</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-slate-700"><?php if ($archivePreview): ?><?php foreach ($archivePreview as $record): ?><tr><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['case_id']) ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['student_name'] ?? 'Unknown') ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['status']) ?></td><td class="px-4 py-3 text-sm"><?= htmlspecialchars($record['date_reported']) ?></td></tr><?php endforeach; ?><?php else: ?><tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No eligible records found.</td></tr><?php endif; ?></tbody></table></div><div class="flex justify-end border-t border-gray-200 dark:border-slate-700 p-6"><a class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg" href="<?= BASE_URL ?>/modules/super-admin/systemControl.php">Close preview</a></div></div></div><?php endif; ?>
    <style>#bannerStatusToggle + span::after { background-color: #fff; border-radius: 9999px; }</style>
    <form method="post" class="space-y-4 sm:space-y-6" data-confirm-form data-confirm-message="Save academic term management settings?" data-confirm-text="Save settings" data-confirm-class="bg-blue-600 hover:bg-blue-700">
        <input type="hidden" name="action" value="save_settings">
        <section class="bg-white dark:bg-[#111827] p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700"><h2 class="text-xl font-bold mb-6">Academic term management</h2><div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8"><div class="space-y-4"><div class="grid grid-cols-2 gap-4"><?php foreach (['sy_start_date'=>'SY start','sy_end_date'=>'SY end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>" required></label><?php endforeach; ?></div>        <div class="grid grid-cols-2 gap-4"><?php foreach (['first_semester_start'=>'1st semester start','first_semester_end'=>'1st semester end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>" required></label><?php endforeach; ?></div>        <div class="grid grid-cols-2 gap-4"><?php foreach (['second_semester_start'=>'2nd semester start','second_semester_end'=>'2nd semester end'] as $key => $label): ?><label class="block text-sm font-semibold"><?= $label ?><input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="date" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key]) ?>" required></label><?php endforeach; ?></div></div><div class="flex flex-col justify-between gap-4"><label class="block text-sm font-semibold">Auto-archive resolved cases after semester end (days)<input class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none" type="number" min="0" name="archive_after_days" value="<?= htmlspecialchars($settings['archive_after_days']) ?>"></label><button class="self-start px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Save settings</button></div></div></section>
    </form>
    <section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 space-y-4"><h2 class="text-xl font-bold flex items-center gap-2">Global announcements <span class="relative group inline-flex"><span class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-bold text-gray-500 cursor-help">i</span><span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-64 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-xs font-normal text-white shadow-lg group-hover:block">Set a message that appears as a banner across the system.</span></span></h2><div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center"><input class="flex-1 min-w-0 w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-slate-800 dark:disabled:text-slate-500" id="globalBannerText" name="global_banner_text" value="<?= htmlspecialchars($settings['global_banner_text']) ?>" maxlength="250" placeholder="Announcement text" <?= $settings['global_banner_enabled'] === 'enabled' ? 'disabled' : '' ?>><label class="inline-flex shrink-0 cursor-pointer items-center gap-3" aria-label="Toggle global announcement"><input type="checkbox" id="bannerStatusToggle" class="peer sr-only" <?= $settings['global_banner_enabled'] === 'enabled' ? 'checked' : '' ?>><span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full bg-white after:transition-transform after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-5 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 dark:bg-slate-600" aria-hidden="true"></span><span id="bannerStatusLabel" class="text-sm font-medium"><?= $settings['global_banner_enabled'] === 'enabled' ? 'On' : 'Off' ?></span></label></div></section>
    <section class="bg-white dark:bg-[#111827] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"><h2 class="text-xl font-bold">Manual Archive Term</h2><div class="flex gap-3"><form method="post"><input type="hidden" name="action" value="archive_preview"><button class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">Archive preview</button></form><form method="post" data-confirm-form data-confirm-message="Archive term?" data-confirm-text="Archive" data-confirm-class="bg-amber-600 hover:bg-amber-700"><input type="hidden" name="action" value="archive_now"><button class="px-4 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700">Archive term now</button></form></div></section>
</main></div></div>
<script src="<?= BASE_URL ?>/assets/js/super-admin/systemControl.js?v=20261105-4"></script>
</body><?php if ($modalError || ($modalMessage && !$previewRequested)): ?><script>document.addEventListener('DOMContentLoaded', () => showNotification(<?= json_encode($modalMessage ?: $modalError) ?>, <?= json_encode($modalMessage ? 'success' : 'error') ?>));</script><?php endif; ?>
</html>
