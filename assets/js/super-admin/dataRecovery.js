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
    button.disabled = true;
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Maintenance mode could not be updated.');
        button.dataset.enabled = data.enabled ? '1' : '0';
        button.setAttribute('aria-pressed', data.enabled ? 'true' : 'false');
        button.setAttribute('aria-label', data.enabled ? 'Disable maintenance mode' : 'Enable maintenance mode');
        button.querySelector('span').classList.toggle('translate-x-5', data.enabled);
        button.querySelector('span').classList.toggle('translate-x-0', !data.enabled);
        button.classList.toggle('bg-blue-600', data.enabled);
        button.classList.toggle('bg-gray-300', !data.enabled);
        button.classList.toggle('dark:bg-gray-600', !data.enabled);
        document.getElementById('maintenanceStatusText').textContent = `Current status: ${data.enabled ? 'Enabled' : 'Disabled'}`;
    } catch (error) {
        showErrorNotification(error.message);
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

document.addEventListener('DOMContentLoaded', () => {
    const historyCard = [...document.querySelectorAll('section')].find((section) => section.querySelector('h2')?.textContent.trim() === 'Backup history');
    const heading = historyCard?.querySelector('h2');
    if (historyCard && heading) historyCard.parentElement.insertBefore(heading, historyCard);
});
