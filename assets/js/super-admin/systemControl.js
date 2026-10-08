document.querySelectorAll('.admin-input').forEach((input) => {
    input.addEventListener('input', () => input.classList.add('is-dirty'));
});

const bannerButton = document.getElementById('bannerStatusButton');
const bannerText = document.getElementById('globalBannerText');
document.getElementById('updateBannerButton')?.addEventListener('click', async () => {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'save_banner');
    formData.append('global_banner_text', bannerText.value);
    const response = await fetch(window.location.href, { method: 'POST', body: formData });
    if (response.ok) window.location.reload();
});
bannerButton?.addEventListener('click', async () => {
    const enabled = bannerButton.dataset.enabled === '1' ? '0' : '1';
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'toggle_banner');
    formData.append('enabled', enabled);
    formData.append('global_banner_text', bannerText.value);
    const response = await fetch(window.location.href, { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) {
        window.location.reload();
    }
});