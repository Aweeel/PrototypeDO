// Sample reports data
let allReports = [
    {
        id: 1,
        name: "Monthly Incident Summary",
        dateGenerated: "2023-10-15",
        format: "PDF",
        category: "disciplinary",
        fileSize: "2.4 MB"
    },
    {
        id: 2,
        name: "Behavioral Trends Analysis",
        dateGenerated: "2023-10-10",
        format: "Excel",
        category: "statistical",
        fileSize: "1.8 MB"
    },
    {
        id: 3,
        name: "Repeat Offenders Report",
        dateGenerated: "2023-10-05",
        format: "PDF",
        category: "disciplinary",
        fileSize: "3.1 MB"
    },
    {
        id: 4,
        name: "Incident Type Distribution",
        dateGenerated: "2023-09-30",
        format: "PDF",
        category: "statistical",
        fileSize: "2.2 MB"
    },
    {
        id: 5,
        name: "Resolution Time Analysis",
        dateGenerated: "2023-09-25",
        format: "Excel",
        category: "statistical",
        fileSize: "1.5 MB"
    }
];

let filteredReports = [...allReports];
let currentPage = 1;
const reportsPerPage = 8;

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadReports();
});

// Load and display reports
function loadReports() {
    renderReports();
    updatePagination();
}

// Render reports table
function renderReports() {
    const tbody = document.getElementById('reportsTableBody');
    const start = (currentPage - 1) * reportsPerPage;
    const end = start + reportsPerPage;
    const reportsToShow = filteredReports.slice(start, end);

    if (reportsToShow.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No reports found</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Try adjusting your filters or generate a new report</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = reportsToShow.map(report => `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 ${report.format === 'PDF' ? 'text-red-500' : 'text-green-500'} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">${report.name}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">${report.fileSize}</p>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                ${formatDate(report.dateGenerated)}
            </td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-medium ${
                    report.format === 'PDF' 
                        ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' 
                        : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                }">
                    ${report.format}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <button onclick="viewReport(${report.id})" 
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        View
                    </button>
                    <button onclick="downloadReport(${report.id})" 
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Download
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Update pagination
function updatePagination() {
    const totalPages = Math.ceil(filteredReports.length / reportsPerPage);
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const start = (currentPage - 1) * reportsPerPage + 1;
    const end = Math.min(currentPage * reportsPerPage, filteredReports.length);

    paginationInfo.textContent = `Showing ${start}-${end} of ${filteredReports.length} reports`;

    let buttonsHTML = '';

    // Previous button
    buttonsHTML += `
        <button onclick="changePage(${currentPage - 1})" 
            ${currentPage === 1 ? 'disabled' : ''} 
            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            Previous
        </button>
    `;

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            buttonsHTML += `
                <button onclick="changePage(${i})" 
                    class="px-4 py-2 ${i === currentPage 
                        ? 'bg-blue-600 text-white' 
                        : 'border border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700'} 
                    rounded-lg transition-colors">
                    ${i}
                </button>
            `;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            buttonsHTML += `<span class="px-2">...</span>`;
        }
    }

    // Next button
    buttonsHTML += `
        <button onclick="changePage(${currentPage + 1})" 
            ${currentPage === totalPages ? 'disabled' : ''} 
            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            Next
        </button>
    `;

    paginationButtons.innerHTML = buttonsHTML;
}

// Change page
function changePage(page) {
    const totalPages = Math.ceil(filteredReports.length / reportsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderReports();
    updatePagination();
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// View report
function viewReport(reportId) {
    const report = allReports.find(r => r.id === reportId);
    alert(`Viewing report: ${report.name}\n\nThis would open the report viewer.`);
    // In production, this would open a modal or new page to view the report
}

// Download report
function downloadReport(reportId) {
    const report = allReports.find(r => r.id === reportId);
    alert(`Downloading: ${report.name}\n\nFormat: ${report.format}\nSize: ${report.fileSize}`);
    // In production, this would trigger an actual download
}

// Generate report
function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const outputFormat = document.getElementById('outputFormat').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    // Create new report
    const newReport = {
        id: allReports.length + 1,
        name: document.getElementById('reportType').options[document.getElementById('reportType').selectedIndex].text,
        dateGenerated: new Date().toISOString().split('T')[0],
        format: outputFormat,
        category: 'disciplinary',
        fileSize: (Math.random() * 3 + 1).toFixed(1) + ' MB'
    };

    allReports.unshift(newReport);
    filteredReports = [...allReports];
    
    closeGenerateModal();
    loadReports();
    
    alert('Report generated successfully!');
}