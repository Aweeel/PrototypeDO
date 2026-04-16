// ====== Global Variables ======

// Cases data
let allCases = [];
let filteredCases = [];

// Pagination
let currentPage = 1;
let casesPerPage = 10;

// Tab tracking
let currentTab = 'current';

// Status color mapping
const statusColors = {
    'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'green': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'red': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'blue': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'gray': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
};

function getCaseResolutionBlockReason(caseData) {
    if (!caseData) return 'Case data not found.';

    if (caseData.hasCorrectiveService && !caseData.hasCorrectiveServiceCompleted) {
        return 'Cannot mark as resolved: Community service is not complete.';
    }

    if (caseData.hasSuspensionFromClass && !caseData.hasSuspensionFromClassCompleted) {
        return 'Cannot mark as resolved: Suspension from class is not complete.';
    }

    return null;
}

function canMarkCaseResolved(caseData) {
    return !getCaseResolutionBlockReason(caseData);
}