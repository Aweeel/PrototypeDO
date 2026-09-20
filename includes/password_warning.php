<?php

// Only show if user has default password
if (!isset($_SESSION['user_id'])) {
    return;
}

// Check for default password warning
// Don't show on user profile page or if already shown in this login session
$currentPage = basename($_SERVER['PHP_SELF']);
$showPasswordWarning = isset($_SESSION['has_default_password']) && $_SESSION['has_default_password'] === true && $currentPage !== 'userProfile.php' && !isset($_SESSION['password_warning_modal_shown']);

if (!$showPasswordWarning) {
    return;
}

?>

<!-- Default Password Warning Modal -->
<div id="passwordWarningModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111827] rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Security Alert</h2>
            </div>
            <button onclick="closePasswordWarningModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-gray-700 dark:text-gray-300">
                You are currently using the default password. <br>
                Please change your password immediately.
            </p>
        </div>
        <div class="flex gap-3 p-6 border-t border-gray-200 dark:border-slate-700">
            <button onclick="closePasswordWarningModal()" 
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                Dismiss
            </button>
            <a href="/PrototypeDO/modules/shared/userProfile.php#change-password" 
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-center">
                Change Password
            </a>
        </div>
    </div>
</div>

<script src="/PrototypeDO/assets/js/terms_password/password_warning.js"></script>
