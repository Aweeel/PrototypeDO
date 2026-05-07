// Helper function to convert snake_case or camelCase to readable label
function formatLabel(key) {
    return key
        .replace(/_/g, ' ')                          // Replace underscores with spaces
        .replace(/([a-z])([A-Z])/g, '$1 $2')         // Handle camelCase
        .replace(/\b\w/g, char => char.toUpperCase()) // Capitalize each word
        .trim();
}

// Helper function to format values for display
function formatValue(value) {
    if (value === null || value === undefined) {
        return '<span class="text-gray-400 italic">null</span>';
    }
    if (typeof value === 'boolean') {
        return `<span class="px-2 py-1 rounded text-sm font-medium ${value ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'}">${value ? 'Yes' : 'No'}</span>`;
    }
    if (Array.isArray(value)) {
        // Render each array item as its own nested card
        return value.map((item, index) => {
            if (typeof item === 'object' && item !== null) {
                let inner = `<div class="border border-gray-300 dark:border-gray-600 rounded p-3 mb-2 bg-white dark:bg-slate-800">`;
                inner += `<div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold">Item ${index + 1}</div>`;
                for (const [k, v] of Object.entries(item)) {
                    inner += `<div class="flex items-start gap-3 mb-2">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-max">${formatLabel(k)}:</span>
                        <span class="text-xs text-gray-900 dark:text-gray-100 flex-1">${formatValue(v)}</span>
                    </div>`;
                }
                inner += `</div>`;
                return inner;
            }
            return `<div class="text-xs">${escapeHtml(String(item))}</div>`;
        }).join('');
    }
    if (typeof value === 'object') {
        let inner = `<div class="border border-gray-300 dark:border-gray-600 rounded p-3 bg-white dark:bg-slate-800">`;
        for (const [k, v] of Object.entries(value)) {
            inner += `<div class="flex items-start gap-3 mb-2">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-max">${formatLabel(k)}:</span>
                <span class="text-xs text-gray-900 dark:text-gray-100 flex-1">${formatValue(v)}</span>
            </div>`;
        }
        inner += `</div>`;
        return inner;
    }

    const str = String(value);

    if (str.includes('\n') || str.length > 80) {
        // If it looks like HTML, render it instead of escaping it
        if (str.trimStart().startsWith('<')) {
            return `<div class="text-sm bg-gray-100 dark:bg-slate-900 p-2 rounded overflow-x-auto border border-gray-200 dark:border-gray-700">${str}</div>`;
        }
        return `<pre class="text-xs bg-gray-100 dark:bg-slate-900 p-2 rounded overflow-x-auto whitespace-pre-wrap">${escapeHtml(str)}</pre>`;
    }

    // Also handle short HTML strings (e.g. "<p>text</p>" under 80 chars)
    if (str.trimStart().startsWith('<')) {
        return `<div class="text-sm bg-gray-100 dark:bg-slate-900 p-2 rounded border border-gray-200 dark:border-gray-700">${str}</div>`;
    }

    return escapeHtml(str);
}

// Helper function to format a data section (old or new values)
function formatDataSection(data, bgColor) {
    if (!data || Object.keys(data).length === 0) {
        return `<p class="text-gray-500 dark:text-gray-400 italic">No data</p>`;
    }

    let html = `<div class="${bgColor} rounded-lg p-4">`;
    html += `<div class="space-y-3">`;
    
    for (const [key, value] of Object.entries(data)) {
        html += `
            <div class="flex items-start gap-4">
                <div class="min-w-max">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">${formatLabel(key)}:</label>
                </div>
                <div class="flex-1 text-sm text-gray-900 dark:text-gray-100">
                    ${formatValue(value)}
                </div>
            </div>
        `;
    }
    
    html += `</div></div>`;
    return html;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Helper function to strip HTML tags and return plain text (for CSV/PDF export)
function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

// Audit Log Detail Modal Functions

let selectedLog = null;

// Open detail modal
function openLogDetailModal(log) {
    selectedLog = log;
    
    const modal = document.getElementById('logDetailModal');
    
    // Populate header
    document.getElementById('detailLogId').textContent = `#${log.id}`;
    
    // Populate main details
    document.getElementById('detailUser').textContent = log.user || 'System';
    document.getElementById('detailRole').textContent = log.role || 'N/A';
    document.getElementById('detailAction').innerHTML = `<span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">${log.action}</span>`;
    document.getElementById('detailTimestamp').textContent = log.timestamp;
    document.getElementById('detailTable').textContent = log.table || 'N/A';
    document.getElementById('detailRecordId').textContent = log.recordId || 'N/A';
    document.getElementById('detailIpAddress').textContent = log.ipAddress || 'N/A';
    document.getElementById('detailUserAgent').textContent = log.userAgent || 'N/A';
    
    // Populate old values
    const oldValuesSection = document.getElementById('oldValuesSection');
    if (log.oldValues && log.oldValues !== 'null') {
        try {
            const oldData = JSON.parse(log.oldValues);
            oldValuesSection.innerHTML = formatDataSection(oldData, 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800');
        } catch (e) {
            oldValuesSection.innerHTML = `<p class="text-red-600 dark:text-red-400">Error parsing data: ${escapeHtml(e.message)}</p>`;
        }
    } else {
        oldValuesSection.innerHTML = `<p class="text-gray-500 dark:text-gray-400 italic">No previous values</p>`;
    }
    
    // Populate new values
    const newValuesSection = document.getElementById('newValuesSection');
    if (log.newValues && log.newValues !== 'null') {
        try {
            const newData = JSON.parse(log.newValues);
            newValuesSection.innerHTML = formatDataSection(newData, 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800');
        } catch (e) {
            newValuesSection.innerHTML = `<p class="text-red-600 dark:text-red-400">Error parsing data: ${escapeHtml(e.message)}</p>`;
        }
    } else {
        newValuesSection.innerHTML = `<p class="text-gray-500 dark:text-gray-400 italic">No new values</p>`;
    }
    
    // Show modal
    modal.classList.remove('hidden');
}

// Close detail modal
function closeLogDetailModal() {
    document.getElementById('logDetailModal').classList.add('hidden');
    selectedLog = null;
}

// Export detail as CSV
function exportDetailAsCSV() {
    if (!selectedLog) return;
    
    const timestamp = new Date().toISOString().slice(0, 10);
    const filename = `audit_log_${selectedLog.id}_${timestamp}.csv`;
    
    // Create CSV rows
    let csvContent = 'Field,Value\n';
    
    // Basic Information
    csvContent += `Log ID,${selectedLog.id}\n`;
    csvContent += `User,${escapeCSV(selectedLog.user || 'System')}\n`;
    csvContent += `Role,${escapeCSV(selectedLog.role || 'N/A')}\n`;
    csvContent += `Action,${escapeCSV(selectedLog.action)}\n`;
    csvContent += `Timestamp,${escapeCSV(selectedLog.timestamp)}\n`;
    csvContent += `Table,${escapeCSV(selectedLog.table || 'N/A')}\n`;
    csvContent += `Record ID,${escapeCSV(selectedLog.recordId || 'N/A')}\n`;
    
    // Network Information
    csvContent += `IP Address,${escapeCSV(selectedLog.ipAddress || 'N/A')}\n`;
    csvContent += `User Agent,${escapeCSV(selectedLog.userAgent || 'N/A')}\n`;
    
    // Helper to flatten value for CSV (strips HTML, stringifies objects)
    function flattenForCSV(val) {
        if (val === null || val === undefined) return '';
        if (typeof val === 'object') return JSON.stringify(val);
        const str = String(val);
        if (str.trimStart().startsWith('<')) return stripHtml(str);
        return str;
    }

    // Old Values
    csvContent += '"Previous Values",\n';
    if (selectedLog.oldValues && selectedLog.oldValues !== 'null') {
        try {
            const oldData = JSON.parse(selectedLog.oldValues);
            for (const [key, value] of Object.entries(oldData)) {
                csvContent += `"  ${formatLabel(key)}",${escapeCSV(flattenForCSV(value))}\n`;
            }
        } catch (e) {
            csvContent += `"Error parsing old values",${escapeCSV(e.message)}\n`;
        }
    } else {
        csvContent += `"  (No previous values)",\n`;
    }
    
    // New Values
    csvContent += '"New Values",\n';
    if (selectedLog.newValues && selectedLog.newValues !== 'null') {
        try {
            const newData = JSON.parse(selectedLog.newValues);
            for (const [key, value] of Object.entries(newData)) {
                csvContent += `"  ${formatLabel(key)}",${escapeCSV(flattenForCSV(value))}\n`;
            }
        } catch (e) {
            csvContent += `"Error parsing new values",${escapeCSV(e.message)}\n`;
        }
    } else {
        csvContent += `"  (No new values)",\n`;
    }
    
    // Download CSV
    downloadFile(csvContent, filename, 'text/csv');
}

// Export detail as PDF
function printDetailAsPDF() {
    if (!selectedLog) return;

    // Helper to render a value for print (strips HTML tags to plain text)
    function printValue(val) {
        if (val === null || val === undefined) return 'N/A';
        if (typeof val === 'object') return JSON.stringify(val, null, 2);
        const str = String(val);
        if (str.trimStart().startsWith('<')) return stripHtml(str);
        return str;
    }
    
    // Build HTML content for printing
    let printContent = `
        <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.4; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e40af; padding-bottom: 10px; margin-bottom: 20px;">
                <div style="font-weight: bold; color: #1e40af; font-size: 13px;">STI Discipline Office</div>
                <div style="font-size: 12px; color: #666; text-align: right;">Generated: ${new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'})} &nbsp;|&nbsp; By: ${escapeHtml(typeof ADMIN_NAME !== 'undefined' ? ADMIN_NAME : 'User')}</div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">Audit Log Details</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Log ID</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">#${selectedLog.id}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">User</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.user || 'System')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Role</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.role || 'N/A')}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Action</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;"><span style="padding: 3px 8px; background-color: #e5e7eb; color: #333; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block;">${escapeHtml(selectedLog.action)}</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Timestamp</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.timestamp)}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Table</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.table || 'N/A')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">Record ID</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.recordId || 'N/A')}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">IP Address</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.ipAddress || 'N/A')}</td>
                    </tr>
                </table>
            </div>
    `;
    
    // Old Values
    printContent += `<div style="margin-bottom: 25px;">
        <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">Previous Values</h3>`;
    
    if (selectedLog.oldValues && selectedLog.oldValues !== 'null') {
        try {
            const oldData = JSON.parse(selectedLog.oldValues);
            printContent += `<div style="background-color: #fee2e2; padding: 10px; border-radius: 4px; border-left: 4px solid #dc2626;"><table style="width: 100%; border-collapse: collapse; font-size: 12px;">`;
            for (const [key, value] of Object.entries(oldData)) {
                printContent += `<tr><td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">${escapeHtml(formatLabel(key))}</td><td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(printValue(value))}</td></tr>`;
            }
            printContent += `</table></div>`;
        } catch (e) {
            printContent += `<p>Error parsing data: ${escapeHtml(e.message)}</p>`;
        }
    } else {
        printContent += `<p><em>No previous values</em></p>`;
    }
    printContent += `</div>`;
    
    // New Values
    printContent += `<div style="margin-bottom: 25px;">
        <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">New Values</h3>`;
    
    if (selectedLog.newValues && selectedLog.newValues !== 'null') {
        try {
            const newData = JSON.parse(selectedLog.newValues);
            printContent += `<div style="background-color: #dcfce7; padding: 10px; border-radius: 4px; border-left: 4px solid #16a34a;"><table style="width: 100%; border-collapse: collapse; font-size: 12px;">`;
            for (const [key, value] of Object.entries(newData)) {
                printContent += `<tr><td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555;">${escapeHtml(formatLabel(key))}</td><td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(printValue(value))}</td></tr>`;
            }
            printContent += `</table></div>`;
        } catch (e) {
            printContent += `<p>Error parsing data: ${escapeHtml(e.message)}</p>`;
        }
    } else {
        printContent += `<p><em>No new values</em></p>`;
    }
    printContent += `</div></div>`;
    
    // Create temporary print container
    let printRoot = document.getElementById('audit-print-root');
    if (!printRoot) {
        printRoot = document.createElement('div');
        printRoot.id = 'audit-print-root';
        document.body.appendChild(printRoot);
    }
    
    // Save original content
    const originalContent = printRoot.innerHTML;
    printRoot.innerHTML = printContent;
    
    // Add temporary style to hide everything but print content during print
    const style = document.createElement('style');
    style.id = 'audit-print-styles';
    style.textContent = `
        @media print {
            body > * { display: none !important; }
            #audit-print-root { display: block !important; }
        }
    `;
    document.head.appendChild(style);
    
    // Trigger print
    setTimeout(() => {
        window.print();
        
        // Cleanup after print dialog closes
        setTimeout(() => {
            printRoot.innerHTML = originalContent;
            const styleElement = document.getElementById('audit-print-styles');
            if (styleElement) styleElement.remove();
        }, 500);
    }, 100);
}

// Helper function to escape CSV values
function escapeCSV(value) {
    if (value === null || value === undefined) {
        return '';
    }
    const stringValue = String(value);
    if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
        return `"${stringValue.replace(/"/g, '""')}"`;
    }
    return stringValue;
}

// Helper function to download files
function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

// Close modal on outside click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('logDetailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeLogDetailModal();
            }
        });
    }
});