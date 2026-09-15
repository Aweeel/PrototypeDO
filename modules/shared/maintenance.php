<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/login/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white flex items-center justify-center p-6">
    <main class="max-w-xl text-center">
        <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-amber-400 text-4xl text-slate-950">!</div>
        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">System maintenance</p>
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Under Scheduled Maintenance</h1>
        <p class="mt-6 text-lg leading-8 text-slate-300">The system is temporarily unavailable while scheduled improvements are being applied. Please check back shortly.</p>
        <a href="<?= BASE_URL ?>/modules/login/logout.php" class="mt-8 inline-flex rounded-lg bg-white px-5 py-3 font-semibold text-slate-950 transition hover:bg-amber-200">Sign out</a>
    </main>
</body>
</html>