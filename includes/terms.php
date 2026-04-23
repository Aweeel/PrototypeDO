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

            <!-- Section -->
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    1. Purpose and Use
                </h3>
                <p>
                    This system is designed exclusively for authorized STI personnel and students
                    to manage discipline cases, records, and related communications. Users shall
                    use this system only for legitimate, authorized purposes in connection with
                    discipline office functions.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    2. Confidentiality and Data Protection
                </h3>
                <p>
                    Users acknowledge that discipline records are confidential and sensitive.
                    All users commit to maintaining the confidentiality of student information
                    and discipline case details accessed through this system, in accordance with
                    the Data Privacy Act of 2012 (RA 10173) and STI's Data Privacy Policy.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    3. User Responsibilities
                </h3>
                <ul class="list-disc list-inside space-y-1 pl-2">
                    <li>Maintain the confidentiality of your login credentials at all times.</li>
                    <li>Report any unauthorized access or suspected breach immediately.</li>
                    <li>Use the system in compliance with STI policies and applicable laws.</li>
                    <li>Never share your account or credentials with other individuals.</li>
                    <li>Update your password when prompted by the system.</li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    4. Compliance with School Policies
                </h3>
                <p>
                    All users must comply with STI's discipline policies, code of conduct, and
                    data protection guidelines as outlined in the Student Handbook. Violations
                    may result in disciplinary action and/or termination of system access.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    5. Liability Limitation
                </h3>
                <p>
                    STI shall not be liable for any indirect, incidental, special, consequential,
                    or punitive damages arising from the use or inability to use this system.
                    Users access and use the system at their own risk.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    6. Modification of Terms
                </h3>
                <p>
                    STI reserves the right to modify these terms at any time. Users may be
                    required to accept updated terms upon their next login following a material
                    change. Continued use of the system constitutes acceptance of any revised terms.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    7. Termination
                </h3>
                <p>
                    STI may suspend or terminate access to this system at any time for violation
                    of these terms, applicable law, or school policy, or for any other reason at
                    its sole discretion, without prior notice.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    8. Role-Based Access Control
                </h3>
                <p>
                    The system enforces role-based access restrictions. Administrators, teachers,
                    and students have different access levels. Users may only access information
                    relevant to their role and responsibilities. Attempting to circumvent access
                    controls or access unauthorized information is strictly prohibited and may
                    result in immediate termination of account access and disciplinary action.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    9. Monitoring and Audit Logging
                </h3>
                <p>
                    All user activities within this system are logged and monitored for security,
                    compliance, and operational purposes. This includes login times, actions taken,
                    data accessed, and modifications made. These logs may be reviewed by STI
                    administrators. By accepting these terms, you acknowledge
                    and consent to such monitoring. Access logs are retained in accordance with
                    STI's data retention policy and applicable law.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    10. Data Retention and Deletion
                </h3>
                <p>
                    Student discipline records are retained in accordance with Philippine educational
                    regulations and STI policy. Upon request or as required by law, inactive user
                    accounts and associated non-essential data may be deleted after a period determined
                    by STI. Archived records may be maintained for audit and legal compliance purposes.
                    Users should not rely on system access for permanent data retention; critical
                    information should be backed up separately.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    11. Security and Incident Response
                </h3>
                <p>
                    STI implements reasonable technical and organizational security measures to protect
                    system data. However, no system is completely secure. Users must immediately report
                    any suspected security breaches, unauthorized access, or compromised credentials to
                    the IT department or administration. In the event of a data breach, affected users
                    will be notified in accordance with applicable data protection laws. STI is not
                    liable for damages resulting from unauthorized access if the user failed to maintain
                    credential confidentiality.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    12. System Availability and Maintenance
                </h3>
                <p>
                    STI makes no guarantee regarding continuous system availability. The system may be
                    taken offline for maintenance, updates, or security purposes without advance notice.
                    Users should not rely on the system for time-critical operations. STI is not liable
                    for any losses or damages resulting from system downtime, data loss, or unavailability,
                    even if STI has been advised of the possibility of such damages.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    13. Account Management
                </h3>
                <ul class="list-disc list-inside space-y-1 pl-2">
                    <li>Each user is responsible for maintaining the security of their account credentials.</li>
                    <li>Inactive accounts may be disabled or deleted after a period specified by STI.</li>
                    <li>Users requesting account termination must do so through official STI channels.</li>
                    <li>Upon termination of employment or enrollment, system access will be revoked.</li>
                    <li>Users may not create multiple accounts or share accounts with others.</li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    14. Governing Law and Jurisdiction
                </h3>
                <p>
                    These Terms and Conditions are governed by the laws of the Republic of the Philippines
                    and comply with the Data Privacy Act of 2012 (RA 10173). Any disputes arising from the
                    use of this system shall be resolved in accordance with Philippine law and submitted to
                    the appropriate courts in the Philippines. By using this system, users consent to the
                    exclusive jurisdiction of Philippine courts.
                </p>
            </div>

            <!-- Spacer so user must scroll to reach the bottom visually -->
            <div class="h-4"></div>
        </div>

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
                        onclick="acceptTos()"
                        disabled
                        class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg font-medium
                               transition-colors disabled:opacity-40 disabled:cursor-not-allowed
                               hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    I Accept
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/PrototypeDO/assets/js/terms_password/terms.js"></script>

