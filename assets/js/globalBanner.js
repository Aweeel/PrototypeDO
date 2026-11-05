(function () {
    const banner = document.getElementById('globalAnnouncementBanner');
    const minimizeButton = document.getElementById('minimizeGlobalAnnouncement');
    const restoreButton = document.getElementById('restoreGlobalAnnouncement');
    const minimizedKey = 'globalAnnouncementMinimized';
    const dismissedKey = 'globalAnnouncementDismissed';

    if (!banner) {
        return;
    }

    const bannerEnabled = banner.dataset.bannerEnabled === '1';
    const hideRestoreButton = function () {
        if (restoreButton) {
            restoreButton.hidden = true;
            restoreButton.style.display = 'none';
        }
    };

    if (!bannerEnabled) {
        banner.hidden = true;
        banner.style.display = 'none';
        localStorage.removeItem(minimizedKey);
        localStorage.removeItem(dismissedKey);
        hideRestoreButton();
    }

    if (!minimizeButton) {
        return;
    }

    const hideBanner = function () {
        banner.hidden = true;
        banner.style.display = 'none';
        if (banner.dataset.bannerEnabled === '1' && restoreButton) {
            restoreButton.hidden = false;
            restoreButton.style.display = '';
        } else {
            hideRestoreButton();
        }
    };

    const showBanner = function () {
        banner.hidden = false;
        banner.style.display = '';
        hideRestoreButton();
    };

    minimizeButton.addEventListener('click', function () {
        hideBanner();
        localStorage.setItem(minimizedKey, '1');
        localStorage.removeItem(dismissedKey);
    });

    restoreButton?.addEventListener('click', function () {
        showBanner();
        localStorage.removeItem(minimizedKey);
        localStorage.removeItem(dismissedKey);
    });

    if (bannerEnabled && (localStorage.getItem(minimizedKey) === '1' || localStorage.getItem(dismissedKey) === '1')) {
        hideBanner();
    } else if (bannerEnabled) {
        hideRestoreButton();
    }
})();
