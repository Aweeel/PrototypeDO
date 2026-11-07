document.querySelectorAll('input[type="checkbox"]').forEach((toggle) => {
    toggle.addEventListener('change', () => {
        toggle.closest('.admin-toggle')?.classList.toggle('is-enabled', toggle.checked);
    });
});

document.getElementById('maintenanceStatusButton')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const enabling = button.dataset.enabled !== '1';
    const actionLabel = enabling ? 'enable' : 'disable';
    const confirmation = enabling
        ? 'Enable system maintenance mode? Regular users will not be able to access the system.'
        : 'Disable system maintenance mode? Regular users will be able to access the system again.';

    const confirmed = await window.showConfirmDialog({
        message: confirmation,
        confirmText: enabling ? 'Enable' : 'Disable',
        confirmClass: enabling ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-600 hover:bg-gray-700'
    });
    if (!confirmed) return;

    button.disabled = true;
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'toggle_maintenance');
    formData.append('enabled', enabling ? '1' : '0');

    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        if (!response.ok) throw new Error(`Unable to ${actionLabel} maintenance mode.`);

        const data = await response.json();
        if (!data.success) throw new Error(`Unable to ${actionLabel} maintenance mode.`);

        const isEnabled = Boolean(data.enabled);
        button.dataset.enabled = isEnabled ? '1' : '0';
        const importButton = document.getElementById('importBackupButton');
        if (importButton) {
            importButton.dataset.maintenanceEnabled = isEnabled ? '1' : '0';
            if (!isEnabled) {
                importButton.disabled = true;
                importButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('backup_file')?.dispatchEvent(new Event('change'));
            }
        }
        button.setAttribute('aria-pressed', String(isEnabled));
        button.setAttribute('aria-label', isEnabled ? 'Disable maintenance mode' : 'Enable maintenance mode');
        button.className = `relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
            isEnabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
        }`;
        const knob = button.querySelector('span');
        if (knob) {
            knob.className = `pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform transition ${
                isEnabled ? 'translate-x-5' : 'translate-x-0'
            }`;
        }
        const status = button.parentElement.querySelector('p');
        if (status) status.textContent = `Current status: ${isEnabled ? 'Enabled' : 'Disabled'}`;
    } catch (error) {
        window.alert(error instanceof Error ? error.message : `Unable to ${actionLabel} maintenance mode.`);
    } finally {
        button.disabled = false;
    }
});

document.getElementById('backupForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch(window.location.href, { method: 'POST', body: new FormData(event.currentTarget) });
    if (!response.ok) return;
    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') || '';
    const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] || 'PrototypeDO_backup.sql';
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
    window.setTimeout(() => window.location.reload(), 250);
});

document.getElementById('backup_file')?.addEventListener('change', (event) => {
    const input = event.currentTarget;
    const label = document.getElementById('backupFileLabel');
    const importButton = document.getElementById('importBackupButton');
    const maintenanceEnabled = importButton?.dataset.maintenanceEnabled === '1';
    const file = input.files?.[0];
    if (label) label.textContent = file ? file.name : 'Choose .sql file';
    if (!file) {
        if (importButton) {
            importButton.disabled = true;
            importButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        const contents = String(reader.result || '');
        const statements = splitSqlStatements(contents);
        const rowsByTable = new Map();
        statements.forEach((statement) => {
            const match = statement.match(/^\s*INSERT\s+INTO\s+`([^`]+)`\s*\(([\s\S]+?)\)\s*VALUES\s*\(([\s\S]+)\)\s*$/i);
            if (!match) return;
            const columns = match[2].split(',').map((column) => column.trim().replace(/^`|`$/g, ''));
            const values = splitSqlValues(match[3]);
            if (!rowsByTable.has(match[1])) rowsByTable.set(match[1], { columns, rows: [] });
            rowsByTable.get(match[1]).rows.push(values);
        });
        const previewContent = document.getElementById('backupPreviewContent');
        if (previewContent) {
            previewContent.replaceChildren();
            if (!rowsByTable.size) {
                previewContent.textContent = 'Preview unavailable: this file contains no generated PrototypeDO data statements.';
            } else {
                const summary = document.createElement('p');
                summary.className = 'font-semibold';
                summary.textContent = `${[...rowsByTable.values()].reduce((total, table) => total + table.rows.length, 0)} records across ${rowsByTable.size} tables`;
                previewContent.appendChild(summary);
                rowsByTable.forEach((table, tableName) => {
                    const heading = document.createElement('h3');
                    heading.className = 'mt-4 font-semibold';
                    heading.textContent = `${tableName} (${table.rows.length} records)`;
                    previewContent.appendChild(heading);
                    const tableElement = document.createElement('table');
                    tableElement.className = 'mt-2 w-full min-w-[520px] text-left text-xs';
                    tableElement.innerHTML = `<thead><tr>${table.columns.map((column) => `<th class="border-b border-blue-200 px-2 py-1">${escapePreviewHtml(column)}</th>`).join('')}</tr></thead>`;
                    const body = document.createElement('tbody');
                    table.rows.forEach((row) => {
                        const tr = document.createElement('tr');
                        row.forEach((value) => {
                            const td = document.createElement('td');
                            td.className = 'max-w-[180px] truncate border-b border-blue-100 px-2 py-1';
                            td.textContent = value;
                            tr.appendChild(td);
                        });
                        body.appendChild(tr);
                    });
                    tableElement.appendChild(body);
                    const wrapper = document.createElement('div');
                    wrapper.className = 'max-h-64 overflow-auto rounded border border-blue-100 dark:border-blue-900';
                    wrapper.appendChild(tableElement);
                    previewContent.appendChild(wrapper);
                });
            }
        }
        if (importButton) {
            const canImport = maintenanceEnabled && rowsByTable.size > 0;
            importButton.disabled = !canImport;
            importButton.classList.toggle('opacity-50', !canImport);
            importButton.classList.toggle('cursor-not-allowed', !canImport);
        }
    };
    reader.readAsText(file);
});

function splitSqlStatements(contents) {
    const statements = [];
    let current = '';
    let quoted = false;
    for (let index = 0; index < contents.length; index += 1) {
        const character = contents[index];
        current += character;
        if (character === "'" && contents[index - 1] !== '\\') {
            if (quoted && contents[index + 1] === "'") {
                current += contents[index + 1];
                index += 1;
            } else {
                quoted = !quoted;
            }
        }
        if (character === ';' && !quoted) {
            statements.push(current.slice(0, -1).replace(/^--.*(?:\r\n|\r|\n)/gm, '').trim());
            current = '';
        }
    }
    return statements.filter(Boolean);
}

function splitSqlValues(values) {
    const result = [];
    let current = '';
    let quoted = false;
    for (let index = 0; index < values.length; index += 1) {
        const character = values[index];
        if (character === "'" && values[index - 1] !== '\\') {
            if (quoted && values[index + 1] === "'") {
                current += values[index + 1];
                index += 1;
            } else {
                quoted = !quoted;
            }
        }
        if (character === ',' && !quoted) {
            result.push(normalizeSqlValue(current));
            current = '';
        } else {
            current += character;
        }
    }
    result.push(normalizeSqlValue(current));
    return result;
}

function normalizeSqlValue(value) {
    const trimmed = value.trim();
    if (trimmed.toUpperCase() === 'NULL') return 'NULL';
    if (trimmed.startsWith("'") && trimmed.endsWith("'")) return trimmed.slice(1, -1).replace(/''/g, "'");
    return trimmed;
}

function escapePreviewHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));
}

document.getElementById('importBackupForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    document.getElementById('backupPreviewModal')?.classList.remove('hidden');
});

const closeBackupPreview = () => document.getElementById('backupPreviewModal')?.classList.add('hidden');
document.getElementById('closeBackupPreview')?.addEventListener('click', closeBackupPreview);
document.getElementById('cancelBackupImport')?.addEventListener('click', closeBackupPreview);
document.getElementById('confirmBackupImport')?.addEventListener('click', () => {
    const form = document.getElementById('importBackupForm');
    if (!form) return;
    const confirmation = document.createElement('input');
    confirmation.type = 'hidden';
    confirmation.name = 'confirm_restore';
    confirmation.value = '1';
    form.appendChild(confirmation);
    HTMLFormElement.prototype.submit.call(form);
});

document.addEventListener('DOMContentLoaded', () => {
    const historyCard = [...document.querySelectorAll('section')].find((section) => section.querySelector('h2')?.textContent.trim() === 'Backup history');
    const heading = historyCard?.querySelector('h2');
    if (historyCard && heading) historyCard.parentElement.insertBefore(heading, historyCard);
});
