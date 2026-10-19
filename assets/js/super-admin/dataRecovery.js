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

document.addEventListener('DOMContentLoaded', () => {
    const historyCard = [...document.querySelectorAll('section')].find((section) => section.querySelector('h2')?.textContent.trim() === 'Backup history');
    const heading = historyCard?.querySelector('h2');
    if (historyCard && heading) historyCard.parentElement.insertBefore(heading, historyCard);
});
