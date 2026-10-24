document.querySelectorAll('.admin-input').forEach((input) => {
    input.addEventListener('input', () => input.classList.add('is-dirty'));
});

const bannerButton = document.getElementById('bannerStatusButton');
const bannerText = document.getElementById('globalBannerText');
document.getElementById('updateBannerButton')?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopPropagation();

    const button = event.currentTarget;
    const confirmed = await window.showConfirmDialog({
        message: 'Update the global announcement?',
        confirmText: 'Update',
        confirmClass: 'bg-blue-600 hover:bg-blue-700'
    });
    if (!confirmed) return;

    button.disabled = true;
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'save_banner');
    formData.append('global_banner_text', bannerText.value);
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        if (!response.ok) throw new Error('Unable to update the global announcement.');
        window.location.reload();
    } catch (error) {
        button.disabled = false;
        showNotification(error instanceof Error ? error.message : 'Unable to update the global announcement.', 'error');
    }
});
bannerButton?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopPropagation();

    const enabled = bannerButton.dataset.enabled === '1' ? '0' : '1';
    const confirmed = await window.showConfirmDialog({
        message: enabled === '1' ? 'Enable the global announcement?' : 'Disable the global announcement?',
        confirmText: enabled === '1' ? 'Enable' : 'Disable',
        confirmClass: enabled === '1' ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'
    });
    if (!confirmed) return;

    bannerButton.disabled = true;
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'toggle_banner');
    formData.append('enabled', enabled);
    formData.append('global_banner_text', bannerText.value);
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        if (!response.ok) throw new Error('Unable to update the global announcement status.');
        const data = await response.json();
        if (!data.success) throw new Error('Unable to update the global announcement status.');
        window.location.reload();
    } catch (error) {
        bannerButton.disabled = false;
        showNotification(error instanceof Error ? error.message : 'Unable to update the global announcement status.', 'error');
    }
});