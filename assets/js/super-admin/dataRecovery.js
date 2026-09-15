document.querySelectorAll('input[type="checkbox"]').forEach((toggle) => {
    toggle.addEventListener('change', () => {
        toggle.closest('.admin-toggle')?.classList.toggle('is-enabled', toggle.checked);
    });
});

document.getElementById('maintenanceStatusButton')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const enabled = button.dataset.enabled === '1' ? '0' : '1';
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'toggle_maintenance');
    formData.append('enabled', enabled);
    const response = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) {
        button.dataset.enabled = data.enabled ? '1' : '0';
        button.textContent = data.enabled ? 'Enabled' : 'Disabled';
        button.classList.toggle('bg-green-600', data.enabled);
        button.classList.toggle('hover:bg-green-700', data.enabled);
        button.classList.toggle('bg-red-600', !data.enabled);
        button.classList.toggle('hover:bg-red-700', !data.enabled);
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

document.addEventListener('DOMContentLoaded', () => {
    const historyCard = [...document.querySelectorAll('section')].find((section) => section.querySelector('h2')?.textContent.trim() === 'Backup history');
    const heading = historyCard?.querySelector('h2');
    if (historyCard && heading) historyCard.parentElement.insertBefore(heading, historyCard);
});
