function openSystemMetrics() {
    const modal = document.getElementById('systemMetricsModal');
    const content = document.getElementById('systemMetricsContent');
    modal.classList.remove('hidden');
    content.innerHTML = '<p class="text-gray-500">Loading metrics...</p>';
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'getSystemMetrics');
    fetch(window.location.href, { method: 'POST', body: formData })
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) throw new Error(data.error || 'Unable to load metrics');
            const metrics = data.metrics;
            content.innerHTML = `
                <div class="col-span-full grid gap-4 md:grid-cols-3">
                    ${metricCard('Database Size', `${metrics.databaseSize ?? 'Unavailable'} MB`)}
                    ${metricCard('Active User Sessions', metrics.activeSessions ?? 0)}
                    ${metricCard('Audit Events Today', metrics.auditEventsToday ?? 0)}
                </div>
                <section class="md:col-span-2"><h3 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Failed Login Attempts</h3>${failedTable(metrics.failedLogins || [])}</section>
                <section><h3 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Peak Usage Hours</h3>${peakTable(metrics.peakHours || [])}</section>`;
        })
        .catch((error) => { content.innerHTML = `<p class="text-red-600">${escapeHtml(error.message)}</p>`; });
}

function closeSystemMetrics() { document.getElementById('systemMetricsModal').classList.add('hidden'); }
function metricCard(label, value) { return `<div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 p-4"><p class="text-sm text-gray-500">${label}</p><strong class="mt-1 block text-2xl text-gray-900 dark:text-gray-100">${value}</strong></div>`; }
function failedTable(rows) { return `<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Username</th><th class="p-2">IP</th><th class="p-2">Timestamp</th></tr></thead><tbody>${rows.map((row) => `<tr class="border-b border-gray-100 dark:border-slate-700"><td class="p-2">${escapeHtml(row.attempted_username || 'Unknown')}</td><td class="p-2">${escapeHtml(row.ip_address || 'Unknown')}</td><td class="p-2">${escapeHtml(row.timestamp || '')}</td></tr>`).join('') || '<tr><td colspan="3" class="p-2 text-gray-500">No failed attempts.</td></tr>'}</tbody></table></div>`; }
function peakTable(rows) { return `<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Hour</th><th class="p-2">Events</th></tr></thead><tbody>${rows.map((row) => `<tr class="border-b border-gray-100 dark:border-slate-700"><td class="p-2">${String(row.hour_of_day).padStart(2, '0')}:00</td><td class="p-2">${row.activity_count}</td></tr>`).join('') || '<tr><td colspan="2" class="p-2 text-gray-500">No activity data.</td></tr>'}</tbody></table></div>`; }
function escapeHtml(value) { return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]); }
