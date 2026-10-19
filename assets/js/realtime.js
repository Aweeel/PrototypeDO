(function () {
    const refreshInterval = 30000;

    function renderNotificationBadge(count) {
        const button = document.querySelector('[onclick="toggleNotificationPanel()"]');
        if (!button) return;
        const existing = button.querySelector('[data-realtime-notification-count]');
        if (existing) existing.remove();
        if (count > 0) {
            const badge = document.createElement('span');
            badge.dataset.realtimeNotificationCount = 'true';
            badge.className = 'absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center';
            badge.textContent = count > 9 ? '9+' : String(count);
            button.appendChild(badge);
        }
    }

    async function refreshNotifications() {
        try {
            const body = new FormData();
            body.append('action', 'getRealtimeState');
            const response = await fetch(`${window.APP_BASE_URL}/modules/shared/realtime.php`, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.success && Array.isArray(data.notifications)) {
                renderNotificationBadge(data.notifications.length);
            }
        } catch (error) {
            console.error('Realtime notification refresh failed:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('notificationPanel')) {
            window.setInterval(refreshNotifications, refreshInterval);
        }
    });
})();
