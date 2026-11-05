document.querySelectorAll('.admin-input').forEach((input) => {
    input.addEventListener('input', () => input.classList.add('is-dirty'));
});


const bannerToggle = document.getElementById('bannerStatusToggle');
const bannerText = document.getElementById('globalBannerText');
const bannerStatusLabel = document.getElementById('bannerStatusLabel');
const globalBanner = document.getElementById('globalAnnouncementBanner');
const restoreBannerButton = document.getElementById('restoreGlobalAnnouncement');

const updateBannerVisibility = (visible) => {
    if (!globalBanner) return;
    const bannerContent = globalBanner.querySelector('span');
    if (bannerContent) {
        bannerContent.textContent = bannerText.value;
    }
    globalBanner.dataset.bannerEnabled = visible ? '1' : '0';
    globalBanner.hidden = !visible;
    globalBanner.style.display = visible ? '' : 'none';
    if (restoreBannerButton) {
        restoreBannerButton.hidden = !visible;
        restoreBannerButton.style.display = 'none';
    }
};

bannerToggle?.addEventListener('change', async () => {
    const enabled = bannerToggle.checked ? '1' : '0';
    bannerText.disabled = bannerToggle.checked;
    bannerStatusLabel.textContent = bannerToggle.checked ? 'On' : 'Off';
    bannerToggle.disabled = true;

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
        bannerToggle.checked = data.enabled === true;
        bannerText.disabled = bannerToggle.checked;
        bannerStatusLabel.textContent = bannerToggle.checked ? 'On' : 'Off';
        if (bannerToggle.checked) {
            localStorage.removeItem('globalAnnouncementMinimized');
            localStorage.removeItem('globalAnnouncementDismissed');
        } else {
            localStorage.removeItem('globalAnnouncementMinimized');
            localStorage.setItem('globalAnnouncementDismissed', '1');
        }
        updateBannerVisibility(bannerToggle.checked && bannerText.value.trim() !== '');
        bannerToggle.disabled = false;
    } catch (error) {
        bannerToggle.checked = !bannerToggle.checked;
        bannerText.disabled = bannerToggle.checked;
        bannerStatusLabel.textContent = bannerToggle.checked ? 'On' : 'Off';
        updateBannerVisibility(bannerToggle.checked && bannerText.value.trim() !== '');
        bannerToggle.disabled = false;
        showNotification(error instanceof Error ? error.message : 'Unable to update the global announcement status.', 'error');
    }
});