<?php

// Only show if user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

// Check if already shown/accepted this session
if (!empty($_SESSION['tos_accepted'])) {
    return;
}

// Get database connection
require_once __DIR__ . '/db_connect.php';

// Check if user has already accepted ToS in database
$pdo = getDBConnection();
if ($pdo) {
    try {
        // First, ensure the required columns exist
        try {
            $checkColSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_NAME = 'users' AND COLUMN_NAME IN ('terms_accepted_version', 'terms_accepted_date')";
            $result = $pdo->query($checkColSql)->fetchAll();
            
            if (count($result) < 2) {
                // One or both columns missing, add them
                if (!in_array('terms_accepted_version', array_column($result, 'COLUMN_NAME'))) {
                    $pdo->exec("ALTER TABLE users ADD terms_accepted_version INT DEFAULT 0");
                    error_log("Added missing terms_accepted_version column to users table");
                }
                if (!in_array('terms_accepted_date', array_column($result, 'COLUMN_NAME'))) {
                    $pdo->exec("ALTER TABLE users ADD terms_accepted_date DATETIME NULL");
                    error_log("Added missing terms_accepted_date column to users table");
                }
            }
        } catch (Exception $e) {
            error_log("Warning: Could not check/add terms columns: " . $e->getMessage());
            // Continue anyway
        }
        
        $stmt = $pdo->prepare("SELECT terms_accepted_version FROM users WHERE user_id = ?");
        
        // Explicitly bind parameter with type hint
        $stmt->bindValue(1, (int)$_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Current terms version (increment this value to force re-acceptance)
        $TERMS_VERSION = 2;
        
        // If user has accepted this version or newer, don't show modal
        if ($user && isset($user['terms_accepted_version']) && $user['terms_accepted_version'] !== null && $user['terms_accepted_version'] >= $TERMS_VERSION) {
            $_SESSION['tos_accepted'] = true;
            return;
        }
    } catch (Exception $e) {
        // Log error but continue - show modal if query fails
        error_log("Terms acceptance check error: " . $e->getMessage());
    }
}
?>

<!-- ======================== TERMS OF SERVICE MODAL ======================== -->
<div id="tosModal"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
     aria-modal="true"
     role="dialog"
     aria-labelledby="tosTitle">

    <div class="relative bg-white dark:bg-[#111827] rounded-xl shadow-2xl
                w-full max-w-2xl flex flex-col
                border border-gray-200 dark:border-slate-700
                max-h-[90vh]">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full
                            flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 id="tosTitle"
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Terms and Conditions
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        STI Discipline Office Management System &nbsp;·&nbsp;
                        Last updated: April 23, 2026
                    </p>
                </div>
            </div>
        </div>

        <!-- Scrollable Body -->
        <div id="tosBody"
             class="overflow-y-auto flex-1 px-6 py-5 text-sm text-gray-700 dark:text-gray-300
                    leading-relaxed space-y-5"
             onscroll="checkTosScroll()">

            <p>
                By accessing and using this Discipline Office Management System (DOMS),
                you agree to be bound by these Terms and Conditions. If you do not agree
                with any part of these terms, you should not use this system.
            </p>

            <!-- Terms will be loaded here dynamically -->
            <div id="termsContent"></div>

            <!-- Spacer so user must scroll to reach the bottom visually -->
            <div class="h-4"></div>
        </div>

        <script>
            // Load terms content from JSON when modal is shown
            async function loadTermsContent() {
                try {
                    const response = await fetch('/PrototypeDO/modules/shared/termsHandler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=getAllContent'
                    });
                    
                    const data = await response.json();
                    if (data.success && data.content) {
                        const termsContainer = document.getElementById('termsContent');
                        termsContainer.innerHTML = '';
                        
                        // Define the order of sections to display
                        const sectionOrder = [
                            'purpose-and-use',
                            'confidentiality-and-data-protection',
                            'user-responsibilities',
                            'compliance-with-school-policies',
                            'liability-limitation',
                            'modification-of-terms',
                            'termination',
                            'role-based-access-control',
                            'monitoring-and-audit-logging',
                            'data-retention-and-deletion',
                            'security-and-incident-response',
                            'system-availability-and-maintenance',
                            'account-management',
                            'governing-law-and-jurisdiction'
                        ];
                        
                        sectionOrder.forEach(sectionId => {
                            if (data.content[sectionId]) {
                                const sectionDiv = document.createElement('div');
                                sectionDiv.innerHTML = data.content[sectionId];
                                termsContainer.appendChild(sectionDiv);
                            }
                        });
                        
                        // After content loads, check if scroll is needed
                        // Small delay to ensure DOM is fully rendered
                        setTimeout(() => {
                            if (window.checkTosScroll) {
                                window.checkTosScroll();
                            }
                        }, 100);
                    }
                } catch (error) {
                    console.error('Error loading terms content:', error);
                    document.getElementById('termsContent').innerHTML = '<p>Error loading terms content. Please refresh the page.</p>';
                }
            }

            // Load terms when the modal becomes visible
            if (document.getElementById('tosModal')) {
                loadTermsContent();
            }
        </script>

        <!-- Scroll hint (hidden after scroll) -->
        <div id="tosScrollHint"
             class="flex items-center justify-center gap-1.5 py-2
                    text-xs text-gray-400 dark:text-gray-500
                    border-t border-gray-100 dark:border-slate-800 flex-shrink-0">
            <svg class="w-3.5 h-3.5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            Scroll down to read all terms before accepting
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 flex-shrink-0
                    flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center sm:text-left">
                You must accept these terms to continue using the system.
            </p>
            <div class="flex gap-3">
                <!-- Decline → logs the user out -->
                <a href="/PrototypeDO/modules/login/logout.php"
                   class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600
                          rounded-lg text-gray-700 dark:text-gray-300
                          hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Decline &amp; Log Out
                </a>
                <!-- Accept button — disabled until scrolled to bottom -->
                <button id="tosAcceptBtn"
                        disabled
                        class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg font-medium
                               transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none
                               hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    I Accept
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/PrototypeDO/assets/js/terms_password/terms.js"></script>

