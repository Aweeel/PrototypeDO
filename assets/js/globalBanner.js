(function () {
    const banner = document.getElementById('globalAnnouncementBanner');
    const closeButton = document.getElementById('closeGlobalAnnouncement');
    const dismissedKey = 'globalAnnouncementDismissed';

    if (!banner || !closeButton) {
        return;
    }

    const hideBanner = function () {
        banner.hidden = true;
        banner.style.display = 'none';
    };

    if (localStorage.getItem(dismissedKey) === '1') {
        hideBanner();
        return;
    }

    closeButton.addEventListener('click', function () {
        hideBanner();
        localStorage.setItem(dismissedKey, '1');
    });
})();
