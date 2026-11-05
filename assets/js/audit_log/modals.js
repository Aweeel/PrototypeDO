// Helper function to convert snake_case or camelCase to readable label
function formatLabel(key) {
    const labels = {
        id: 'ID',
        user_id: 'User ID',
        case_id: 'Case ID',
        student_id: 'Student ID',
        sanction_id: 'Sanction ID',
        record_id: 'Record ID',
        day_number: 'Day Number',
        ip_address: 'IP Address',
        user_agent: 'User Agent',
        html_content: 'HTML Content',
        pdf_path: 'PDF Path',
        url: 'URL'
    };

    if (labels[key]) return labels[key];

    return key
        .replace(/_/g, ' ')                          // Replace underscores with spaces
        .replace(/([a-z])([A-Z])/g, '$1 $2')         // Handle camelCase
        .replace(/\b\w/g, char => char.toUpperCase()) // Capitalize each word
        .trim();
}

function formatValueLabel(key, value) {
    if (value === null || value === undefined) return value;

    const valueLabels = {
        role: {
            do: 'Discipline Office',
            discipline_office: 'Discipline Office',
            super_admin: 'Super Admin',
            admin: 'Administrator',
            teacher: 'Teacher',
            student: 'Student'
        },
        status: {
            active: 'Active',
            inactive: 'Inactive',
            archived: 'Archived',
            resolved: 'Resolved',
            on_going: 'On Going',
            pending: 'Pending'
        }
    };
    const normalizedKey = key.toLowerCase();
    const normalizedValue = String(value).toLowerCase();

    if (valueLabels[normalizedKey] && valueLabels[normalizedKey][normalizedValue]) {
        return valueLabels[normalizedKey][normalizedValue];
    }

    if (['is_active', 'is_archived'].includes(normalizedKey) && ['0', '1'].includes(normalizedValue)) {
        const enabled = normalizedValue === '1';
        return normalizedKey === 'is_archived'
            ? (enabled ? 'Archived' : 'Not Archived')
            : (enabled ? 'Active' : 'Inactive');
    }

    return value;
}

function formatTableLabel(table) {
    const tableLabels = {
        users: 'Users',
        students: 'Students',
        cases: 'Cases',
        case_sanctions: 'Case Sanctions',
        case_checkins: 'Case Check-Ins',
        lost_found_items: 'Lost & Found Items',
        lost_found_categories: 'Lost & Found Categories',
        calendar_events: 'Calendar Events',
        community_service_submissions: 'Community Service Submissions',
        handbook: 'Student Handbook',
        system_settings: 'System Settings',
        system_backup: 'System Backup'
    };

    return tableLabels[table] || formatLabel(table);
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
                    inner += `<div class="grid grid-cols-[minmax(120px,160px)_minmax(0,1fr)] gap-3 items-start mb-2">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">${formatLabel(k)}:</span>
                        <span class="min-w-0 text-xs text-gray-900 dark:text-gray-100">${formatValue(v)}</span>
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
            inner += `<div class="grid grid-cols-[minmax(120px,160px)_minmax(0,1fr)] gap-3 items-start mb-2">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">${formatLabel(k)}:</span>
                <span class="min-w-0 text-xs text-gray-900 dark:text-gray-100">${formatValue(v)}</span>
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
            <div class="grid grid-cols-[minmax(140px,180px)_minmax(0,1fr)] gap-4 items-start">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">${formatLabel(key)}:</label>
                </div>
                <div class="min-w-0 text-sm text-gray-900 dark:text-gray-100">
                    ${formatValue(formatValueLabel(key, value))}
                </div>
            </div>
        `;
    }
    
    html += `</div></div>`;
    return html;
}

function formatHandbookAuditSection(data, bgColor) {
    if (!data || !Array.isArray(data.sections)) return formatDataSection(data, bgColor);

    return `<div class="${bgColor} rounded-lg p-4"><div class="space-y-3">${data.sections.map(section => {
        const content = auditPlainText(section.content ?? section.new_content ?? section.old_content);
        return `<div class="rounded border border-gray-200 dark:border-slate-600 p-3">
            <div class="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">${escapeHtml(formatLabel(section.section_id))}</div>
            <div class="whitespace-pre-wrap break-words text-sm leading-6">${escapeHtml(content) || '<span class="text-gray-400 italic">No content</span>'}</div>
        </div>`;
    }).join('')}</div></div>`;
}

function auditPlainText(value) {
    const element = document.createElement('div');
    element.innerHTML = String(value ?? '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/(p|div|li|h[1-6]|tr)>/gi, '\n');
    return (element.textContent || '')
        .replace(/\u00a0/g, ' ')
        .replace(/\r\n?/g, '\n')
        .replace(/[ \t\f\v]+/g, ' ')
        .replace(/ *\n */g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
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

function getAuditValueEntries(data) {
    if (!data || typeof data !== 'object') return [];
    if (Array.isArray(data.sections)) {
        return data.sections.map(section => [
            section.section_id || 'Section',
            section.content ?? ''
        ]);
    }
    return Object.entries(data);
}

function getHandbookSections(data, side) {
    if (!data || !Array.isArray(data.sections)) return new Map();
    return new Map(data.sections.map(section => [
        String(section.section_id || ''),
        auditPlainText(side === 'old' && section.old_content !== undefined
            ? section.old_content
            : side === 'new' && section.new_content !== undefined
                ? section.new_content
                : section.content)
    ]));
}

function getHandbookComparisonSources(oldData, newData) {
    const hasLegacyContent = newData && Array.isArray(newData.sections) &&
        newData.sections.some(section => section.old_content !== undefined || section.new_content !== undefined);
    if ((!oldData || !Array.isArray(oldData.sections)) && hasLegacyContent) {
        return [newData, newData];
    }
    return [oldData, newData];
}

function tokenizeAuditText(text) {
    return String(text ?? '').match(/\n|[ \t]+|[\p{L}\p{N}_]+|[^\s\p{L}\p{N}_]/gu) || [];
}

function isWordToken(token) {
    return /^[\p{L}\p{N}_]+$/u.test(token);
}

function isMinorCharacterEdit(oldText, newText) {
    if (oldText.length > 80 || newText.length > 80) return false;
    const row = Array.from({ length: newText.length + 1 }, (_, index) => index);
    for (let oldIndex = 1; oldIndex <= oldText.length; oldIndex += 1) {
        let diagonal = row[0];
        row[0] = oldIndex;
        for (let newIndex = 1; newIndex <= newText.length; newIndex += 1) {
            const previous = row[newIndex];
            row[newIndex] = oldText[oldIndex - 1] === newText[newIndex - 1]
                ? diagonal
                : Math.min(diagonal, row[newIndex], row[newIndex - 1]) + 1;
            diagonal = previous;
        }
    }
    return row[newText.length] <= 1;
}

function diffCharacterRuns(oldText, newText) {
    const oldTokens = Array.from(oldText);
    const newTokens = Array.from(newText);
    const rows = Array.from({ length: oldTokens.length + 1 }, () => new Uint32Array(newTokens.length + 1));
    for (let oldIndex = oldTokens.length - 1; oldIndex >= 0; oldIndex -= 1) {
        for (let newIndex = newTokens.length - 1; newIndex >= 0; newIndex -= 1) {
            rows[oldIndex][newIndex] = oldTokens[oldIndex] === newTokens[newIndex]
                ? rows[oldIndex + 1][newIndex + 1] + 1
                : Math.max(rows[oldIndex + 1][newIndex], rows[oldIndex][newIndex + 1]);
        }
    }

    return buildAuditOperations(oldTokens, newTokens, rows);
}

function buildAuditOperations(oldTokens, newTokens, rows) {
    const operations = [];
    let oldIndex = 0;
    let newIndex = 0;
    const append = (type, text) => {
        if (!text) return;
        const previous = operations[operations.length - 1];
        if (previous && previous.type === type) previous.text += text;
        else operations.push({ type, text });
    };

    while (oldIndex < oldTokens.length || newIndex < newTokens.length) {
        if (oldIndex < oldTokens.length && newIndex < newTokens.length &&
            oldTokens[oldIndex] === newTokens[newIndex]) {
            append('equal', oldTokens[oldIndex]);
            oldIndex += 1;
            newIndex += 1;
        } else if (newIndex < newTokens.length &&
            (oldIndex === oldTokens.length || rows[oldIndex][newIndex + 1] >= rows[oldIndex + 1][newIndex])) {
            append('insert', newTokens[newIndex]);
            newIndex += 1;
        } else {
            append('delete', oldTokens[oldIndex]);
            oldIndex += 1;
        }
    }
    return operations;
}

function myersAuditOperations(oldTokens, newTokens) {
    const maxDistance = oldTokens.length + newTokens.length;
    let frontier = new Map([[1, 0]]);
    const trace = [];
    let endDistance = 0;

    for (let distance = 0; distance <= maxDistance; distance += 1) {
        let reachedEnd = false;
        for (let diagonal = -distance; diagonal <= distance; diagonal += 2) {
            let oldIndex;
            if (diagonal === -distance ||
                (diagonal !== distance && (frontier.get(diagonal - 1) || 0) < (frontier.get(diagonal + 1) || 0))) {
                oldIndex = frontier.get(diagonal + 1) || 0;
            } else {
                oldIndex = (frontier.get(diagonal - 1) || 0) + 1;
            }
            let newIndex = oldIndex - diagonal;
            while (oldIndex < oldTokens.length && newIndex < newTokens.length &&
                oldTokens[oldIndex] === newTokens[newIndex]) {
                oldIndex += 1;
                newIndex += 1;
            }
            frontier.set(diagonal, oldIndex);
            if (oldIndex >= oldTokens.length && newIndex >= newTokens.length) {
                endDistance = distance;
                reachedEnd = true;
                break;
            }
        }
        trace.push(new Map(frontier));
        if (reachedEnd) break;
    }

    const reversed = [];
    let oldIndex = oldTokens.length;
    let newIndex = newTokens.length;
    for (let distance = endDistance; distance > 0; distance -= 1) {
        const diagonal = oldIndex - newIndex;
        const previous = trace[distance - 1];
        const previousDiagonal = diagonal === -distance ||
            (diagonal !== distance && (previous.get(diagonal - 1) || 0) < (previous.get(diagonal + 1) || 0))
            ? diagonal + 1
            : diagonal - 1;
        const previousOldIndex = previous.get(previousDiagonal) || 0;
        const previousNewIndex = previousOldIndex - previousDiagonal;

        while (oldIndex > previousOldIndex && newIndex > previousNewIndex) {
            reversed.push({ type: 'equal', text: oldTokens[oldIndex - 1] });
            oldIndex -= 1;
            newIndex -= 1;
        }
        if (oldIndex === previousOldIndex) {
            reversed.push({ type: 'insert', text: newTokens[newIndex - 1] });
            newIndex -= 1;
        } else {
            reversed.push({ type: 'delete', text: oldTokens[oldIndex - 1] });
            oldIndex -= 1;
        }
    }
    while (oldIndex > 0 && newIndex > 0) {
        reversed.push({ type: 'equal', text: oldTokens[oldIndex - 1] });
        oldIndex -= 1;
        newIndex -= 1;
    }
    while (oldIndex > 0) {
        reversed.push({ type: 'delete', text: oldTokens[oldIndex - 1] });
        oldIndex -= 1;
    }
    while (newIndex > 0) {
        reversed.push({ type: 'insert', text: newTokens[newIndex - 1] });
        newIndex -= 1;
    }

    return reversed.reverse().reduce((result, operation) => {
        const previous = result[result.length - 1];
        if (previous && previous.type === operation.type) previous.text += operation.text;
        else result.push(operation);
        return result;
    }, []);
}

function refineAuditOperations(operations) {
    const refined = [];
    for (let index = 0; index < operations.length; index += 1) {
        const operation = operations[index];
        const next = operations[index + 1];
        if ((operation.type === 'delete' && next && next.type === 'insert') ||
            (operation.type === 'insert' && next && next.type === 'delete')) {
            const oldOperation = operation.type === 'delete' ? operation : next;
            const newOperation = operation.type === 'insert' ? operation : next;
            const oldTokens = oldOperation.text.match(/\S+/g) || [];
            const newTokens = newOperation.text.match(/\S+/g) || [];
            const shouldUseCharacters = oldTokens.length === 1 && newTokens.length === 1 &&
                (!isWordToken(oldTokens[0]) || !isWordToken(newTokens[0]) ||
                    isMinorCharacterEdit(oldTokens[0], newTokens[0]));
            if (shouldUseCharacters) {
                refined.push(...diffCharacterRuns(oldOperation.text, newOperation.text));
            } else {
                refined.push(operation, next);
            }
            index += 1;
        } else {
            refined.push(operation);
        }
    }
    return refined;
}

function diffAuditText(oldText, newText) {
    oldText = auditPlainText(oldText);
    newText = auditPlainText(newText);
    if (oldText === newText) return oldText ? [{ type: 'equal', text: oldText }] : [];
    const oldTokens = tokenizeAuditText(oldText);
    const newTokens = tokenizeAuditText(newText);
    return refineAuditOperations(myersAuditOperations(oldTokens, newTokens));
}

function renderAuditDiff(operations) {
    return operations.map(operation => {
        if (operation.type === 'context') {
            return `<span class="text-gray-500 dark:text-gray-400">${escapeHtml(operation.text)}</span>`;
        }
        const className = operation.type === 'delete'
            ? 'bg-red-200 text-red-900 dark:bg-red-900/50 dark:text-red-100 line-through decoration-red-700'
            : operation.type === 'insert'
                ? 'bg-green-200 text-green-900 dark:bg-green-900/50 dark:text-green-100'
                : 'text-gray-800 dark:text-gray-100';
        return `<span class="${className}">${escapeHtml(operation.text)}</span>`;
    }).join('');
}

function getAuditDiffContext(operations, limit = 180) {
    if (operations.length <= limit) return operations;
    const changed = operations
        .map((operation, index) => operation.type === 'equal' ? -1 : index)
        .filter(index => index >= 0);
    if (!changed.length) return operations.slice(0, limit);
    const ranges = [];
    changed.forEach(index => {
        const range = [Math.max(0, index - 25), Math.min(operations.length, index + 26)];
        const previous = ranges[ranges.length - 1];
        if (previous && range[0] <= previous[1]) previous[1] = range[1];
        else ranges.push(range);
    });

    return ranges.reduce((result, [start, end], rangeIndex) => {
        if (rangeIndex > 0) result.push({ type: 'context', text: ' ... ' });
        if (start > 0 && rangeIndex === 0) result.push({ type: 'context', text: '... ' });
        result.push(...operations.slice(start, end));
        if (end < operations.length && rangeIndex === ranges.length - 1) {
            result.push({ type: 'context', text: ' ...' });
        }
        return result;
    }, []);
}

function formatHandbookComparison(oldData, newData) {
    const [oldSource, newSource] = getHandbookComparisonSources(oldData, newData);
    const oldSections = getHandbookSections(oldSource, 'old');
    const newSections = getHandbookSections(newSource, 'new');
    const sectionIds = [...new Set([...oldSections.keys(), ...newSections.keys()])];
    if (!sectionIds.length) return '<p class="text-gray-500 dark:text-gray-400 italic">No section changes</p>';

    return `<div class="space-y-5">${sectionIds.map(sectionId => {
        const oldText = oldSections.get(sectionId) || '';
        const newText = newSections.get(sectionId) || '';
        const operations = diffAuditText(oldText, newText);
        const hasChanges = operations.some(operation => operation.type !== 'equal');
        const isLong = operations.length > 180;
        const comparison = hasChanges ? renderAuditDiff(getAuditDiffContext(operations)) :
            '<span class="text-gray-500 dark:text-gray-400 italic">No changes</span>';

        return `<section class="rounded-lg border border-gray-200 dark:border-slate-600 overflow-hidden">
            <h4 class="px-4 py-3 bg-gray-50 dark:bg-slate-800 text-sm font-semibold text-gray-900 dark:text-gray-100">${escapeHtml(formatLabel(sectionId))}</h4>
            <div class="p-4">
                <div class="whitespace-pre-wrap break-words text-sm leading-6">${comparison}</div>
                ${isLong ? `<details class="mt-3"><summary class="cursor-pointer text-xs text-blue-600 dark:text-blue-400">View complete comparison</summary><div class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">${renderAuditDiff(operations)}</div></details>` : ''}
            </div>
        </section>`;
    }).join('')}</div>`;
}

function getHandbookComparisonRows(oldData, newData) {
    const [oldSource, newSource] = getHandbookComparisonSources(oldData, newData);
    const oldSections = getHandbookSections(oldSource, 'old');
    const newSections = getHandbookSections(newSource, 'new');
    const sectionIds = [...new Set([...oldSections.keys(), ...newSections.keys()])];

    return sectionIds.map(sectionId => {
        const operations = diffAuditText(oldSections.get(sectionId) || '', newSections.get(sectionId) || '');
        return {
            sectionId,
            comparison: operations.map(operation => operation.type === 'delete'
                ? `[REMOVED] ${operation.text} [/REMOVED]`
                : operation.type === 'insert' ? `[ADDED] ${operation.text} [/ADDED]` : operation.text).join(''),
            previous: operations.map(operation =>
                operation.type === 'insert' ? '' : operation.type === 'delete' ? `[REMOVED] ${operation.text}` : operation.text
            ).join(''),
            updated: operations.map(operation =>
                operation.type === 'delete' ? '' : operation.type === 'insert' ? `[ADDED] ${operation.text}` : operation.text
            ).join('')
        };
    });
}

function parseAuditValues(values) {
    if (values === null || values === undefined || values === '' || values === 'null') {
        return null;
    }

    if (typeof values !== 'string') {
        return values;
    }

    let parsed = JSON.parse(values);
    // Some legacy entries contain a JSON string encoded inside JSON.
    if (typeof parsed === 'string') {
        parsed = JSON.parse(parsed);
    }
    return parsed;
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
    document.getElementById('detailRole').textContent = formatValueLabel('role', log.role || 'N/A');
    document.getElementById('detailAction').innerHTML = `<span class="px-2 py-1 text-xs font-semibold rounded-full ${log.actionColor}">${escapeHtml(log.action)}</span>`;
    document.getElementById('detailTimestamp').textContent = log.timestamp;
    document.getElementById('detailTable').textContent = log.table ? formatTableLabel(log.table) : 'N/A';
    document.getElementById('detailRecordId').textContent =
        log.table === 'handbook' && String(log.recordId) === '0' ? 'N/A' : (log.recordId || 'N/A');
    document.getElementById('detailIpAddress').textContent = log.ipAddress || 'N/A';
    document.getElementById('detailUserAgent').textContent = log.userAgent || 'N/A';
    
    // Populate old values
    const oldValuesBlock = document.getElementById('oldValuesBlock');
    const oldValuesSection = document.getElementById('oldValuesSection');
    const newValuesBlock = document.getElementById('newValuesBlock');
    const oldValuesTitle = document.getElementById('oldValuesTitle');
    try {
        const oldData = parseAuditValues(log.oldValues);
        const newData = parseAuditValues(log.newValues);
        const isHandbookContent = log.table === 'handbook' && log.action === 'Student Handbook Content Updated';
        if (isHandbookContent) {
            oldValuesTitle.textContent = 'Content Comparison';
            oldValuesBlock.classList.remove('hidden');
            newValuesBlock.classList.add('hidden');
            oldValuesSection.innerHTML = formatHandbookComparison(oldData, newData);
        } else if (oldData !== null && typeof oldData === 'object' && Object.keys(oldData).length > 0) {
            oldValuesTitle.textContent = 'Previous Values';
            newValuesBlock.classList.remove('hidden');
            oldValuesBlock.classList.remove('hidden');
            oldValuesSection.innerHTML = formatHandbookAuditSection(oldData, 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800');
        } else {
            oldValuesTitle.textContent = 'Previous Values';
            newValuesBlock.classList.remove('hidden');
            oldValuesBlock.classList.add('hidden');
        }
    } catch (e) {
        oldValuesBlock.classList.remove('hidden');
        oldValuesSection.innerHTML = `<p class="text-red-600 dark:text-red-400">Unable to parse data: ${escapeHtml(e.message)}</p>`;
    }
    
    // Populate new values
    const newValuesSection = document.getElementById('newValuesSection');
    try {
        const isHandbookContent = log.table === 'handbook' && log.action === 'Student Handbook Content Updated';
        const newData = parseAuditValues(log.newValues);
        if (!isHandbookContent && newData !== null && typeof newData === 'object') {
            newValuesSection.innerHTML = formatHandbookAuditSection(newData, 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800');
        } else if (!isHandbookContent) {
            newValuesSection.innerHTML = `<p class="text-gray-500 dark:text-gray-400 italic">No new values</p>`;
        }
    } catch (e) {
        newValuesSection.innerHTML = `<p class="text-red-600 dark:text-red-400">Unable to parse data: ${escapeHtml(e.message)}</p>`;
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
    csvContent += `Record ID,${escapeCSV(selectedLog.table === 'handbook' && String(selectedLog.recordId) === '0' ? 'N/A' : (selectedLog.recordId || 'N/A'))}\n`;
    
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

    const isHandbookContent = selectedLog.table === 'handbook' && selectedLog.action === 'Student Handbook Content Updated';
    const oldData = parseAuditValues(selectedLog.oldValues);
    const newData = parseAuditValues(selectedLog.newValues);

    // Old Values
    csvContent += '"Content Comparison",\n';
    if (isHandbookContent) {
        for (const row of getHandbookComparisonRows(oldData, newData)) {
            csvContent += `"  ${escapeCSV(formatLabel(row.sectionId))}",${escapeCSV(row.comparison)}\n`;
        }
        csvContent += '"Previous Content (historical)",\n';
        for (const row of getHandbookComparisonRows(oldData, newData)) {
            csvContent += `"  ${escapeCSV(formatLabel(row.sectionId))}",${escapeCSV(row.previous.replace(/\[REMOVED\]\s*|\[\/REMOVED\]/g, ''))}\n`;
        }
        csvContent += '"Updated Content (historical)",\n';
        for (const row of getHandbookComparisonRows(oldData, newData)) {
            csvContent += `"  ${escapeCSV(formatLabel(row.sectionId))}",${escapeCSV(row.updated.replace(/\[ADDED\]\s*|\[\/ADDED\]/g, ''))}\n`;
        }
    } else if (selectedLog.oldValues && selectedLog.oldValues !== 'null') {
        try {
            for (const [key, value] of getAuditValueEntries(oldData)) {
                csvContent += `"  ${formatLabel(key)}",${escapeCSV(flattenForCSV(formatValueLabel(key, value)))}\n`;
            }
        } catch (e) {
            csvContent += `"Error parsing old values",${escapeCSV(e.message)}\n`;
        }
    } else {
        csvContent = csvContent.replace('"Previous Values",\n', '');
    }
    
    // New Values
    if (!isHandbookContent && selectedLog.newValues && selectedLog.newValues !== 'null') {
        csvContent += '"New Values",\n';
        try {
            for (const [key, value] of getAuditValueEntries(newData)) {
                csvContent += `"  ${formatLabel(key)}",${escapeCSV(flattenForCSV(formatValueLabel(key, value)))}\n`;
            }
        } catch (e) {
            csvContent += `"Error parsing new values",${escapeCSV(e.message)}\n`;
        }
    }
    
    // Download CSV
    downloadFile(csvContent, filename, 'text/csv');
}

// Export detail as PDF
function printDetailAsPDF() {
    if (!selectedLog) return;

    // Render values using the same data source as the detail modal.
    function printValue(val) {
        if (val === null || val === undefined) return 'N/A';
        if (typeof val === 'object') {
            return JSON.stringify(val, function(key, value) {
                if (typeof value === 'string' && /<[^>]+>/.test(value)) {
                    return stripHtml(value).replace(/\s+/g, ' ').trim();
                }
                return value;
            }, 2);
        }
        const str = String(val);
        if (/<[^>]+>/.test(str)) return stripHtml(str).replace(/\s+/g, ' ').trim();
        return str;
    }

    function printDataRows(data) {
        if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
            return '<tr><td colspan="2" style="padding: 6px;"><em>No data</em></td></tr>';
        }

        return getAuditValueEntries(data).map(([key, value]) => `
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 30%; color: #555; vertical-align: top;">${escapeHtml(formatLabel(key))}</td>
                <td style="padding: 6px; border-bottom: 1px solid #ddd; white-space: pre-wrap; overflow-wrap: anywhere; vertical-align: top;">${escapeHtml(printValue(formatValueLabel(key, value)))}</td>
            </tr>
        `).join('');
    }

    let oldData;
    let newData;
    try {
        oldData = parseAuditValues(selectedLog.oldValues);
    } catch (e) {
        oldData = null;
    }
    try {
        newData = parseAuditValues(selectedLog.newValues);
    } catch (e) {
        newData = null;
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
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed;">
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 25%; color: #555;">Log ID</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">#${selectedLog.id}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; width: 25%; color: #555;">User</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.user || 'System')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">Role</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.role || 'N/A')}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">Action</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;"><span style="padding: 3px 8px; background-color: #e5e7eb; color: #333; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block;">${escapeHtml(selectedLog.action)}</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">Timestamp</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.timestamp)}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">Table</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.table || 'N/A')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">Record ID</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.table === 'handbook' && String(selectedLog.recordId) === '0' ? 'N/A' : (selectedLog.recordId || 'N/A'))}</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">IP Address</td>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd;">${escapeHtml(selectedLog.ipAddress || 'N/A')}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px; border-bottom: 1px solid #ddd; font-weight: bold; color: #555;">User Agent</td>
                        <td colspan="3" style="padding: 6px; border-bottom: 1px solid #ddd; overflow-wrap: anywhere;">${escapeHtml(selectedLog.userAgent || 'N/A')}</td>
                    </tr>
                </table>
            </div>
    `;
    
    const isHandbookContent = selectedLog.table === 'handbook' && selectedLog.action === 'Student Handbook Content Updated';
    if (isHandbookContent) {
        const comparisonRows = getHandbookComparisonRows(oldData, newData).map(row => `
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; vertical-align: top;">
                    <strong>${escapeHtml(formatLabel(row.sectionId))}</strong><br>
                    <span style="white-space: pre-wrap; overflow-wrap: anywhere;">${row.comparison
                        ? escapeHtml(row.comparison)
                            .replace(/\[REMOVED\] /g, '<span style="background:#fecaca;color:#991b1b;text-decoration:line-through;">[REMOVED] ')
                            .replace(/ \[\/REMOVED\]/g, '</span>')
                            .replace(/\[ADDED\] /g, '<span style="background:#bbf7d0;color:#166534;">[ADDED] ')
                            .replace(/ \[\/ADDED\]/g, '</span>')
                        : '<em>No changes</em>'}</span>
                </td>
            </tr>`).join('');
        printContent += `<div style="margin-bottom: 25px;">
            <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">Content Comparison</h3>
            <div style="padding: 10px; border: 1px solid #d1d5db; border-radius: 4px;"><table style="width: 100%; border-collapse: collapse; font-size: 12px;">${comparisonRows}</table></div>
        </div></div>`;
    } else if (oldData && typeof oldData === 'object' && Object.keys(oldData).length > 0) {
        printContent += `<div style="margin-bottom: 25px;">
            <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">Previous Values</h3>
            <div style="background-color: #fee2e2; padding: 10px; border-radius: 4px; border-left: 4px solid #dc2626;"><table style="width: 100%; border-collapse: collapse; font-size: 12px;">${printDataRows(oldData)}</table></div>
        </div>`;
    }
    
    if (!isHandbookContent) {
        printContent += `<div style="margin-bottom: 25px;">
            <h3 style="font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 10px 0;">New Values</h3>
            <div style="background-color: #dcfce7; padding: 10px; border-radius: 4px; border-left: 4px solid #16a34a;"><table style="width: 100%; border-collapse: collapse; font-size: 12px;">${printDataRows(newData)}</table></div>
        </div></div>`;
    } else {
        printContent += '</div>';
    }
    
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