// ====== Cases Data from Database ======

// Initialize empty - will be loaded from database
let allCases = [];
let filteredCases = [];
let currentPage = 1;
const casesPerPage = 8;
let currentTab = 'current';

const statusColors = {
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-[#713F12] dark:text-yellow-100',
    blue: 'bg-blue-100 text-blue-800 dark:bg-[#1E3A8A] dark:text-blue-100',
    green: 'bg-green-100 text-green-800 dark:bg-[#14532D] dark:text-green-100',
    red: 'bg-red-100 text-red-800 dark:bg-[#7F1D1D] dark:text-red-100'
};