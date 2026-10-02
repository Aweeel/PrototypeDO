<?php
require_once __DIR__ . '/../../includes/config.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Ended | STI Discipline Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="flex min-h-screen items-center justify-center p-6">
        <section class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-10">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.25h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.74 3h14.88a2 2 0 0 0 1.74-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                </svg>
            </div>

            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Session ended</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Another browser recently logged into your account</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">This browser has been automatically logged out. Please sign in again to continue.</p>

            <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/modules/login/login.php" class="mt-8 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Return to sign in
            </a>
        </section>
    </main>
</body>
</html>