// ====== Cases Data from Database ======

// Initialize empty - will be loaded from database
let allCases = [];
let filteredCases = [];
let currentPage = 1;
const casesPerPage = 8;
let currentTab = 'current';
const tabPages = {
    current: 1,
    resolved: 1,
    archived: 1
};

// Track selected cases across pages
let selectedCaseIds = new Set();

function getPageForTab(tabName) {
    if (!tabName || !Object.prototype.hasOwnProperty.call(tabPages, tabName)) {
        return 1;
    }

    return tabPages[tabName];
}

function setPageForTab(tabName, page) {
    if (!tabName || !Object.prototype.hasOwnProperty.call(tabPages, tabName)) {
        return;
    }

    const safePage = Number.isFinite(Number(page)) ? Math.max(1, parseInt(page, 10)) : 1;
    tabPages[tabName] = safePage;
}

function syncCurrentPageWithTab() {
    currentPage = getPageForTab(currentTab);
}

function updateActiveTabPage(page) {
    setPageForTab(currentTab, page);
    syncCurrentPageWithTab();
}

const statusColors = {
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
    blue: 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
    green: 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
    red: 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'
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