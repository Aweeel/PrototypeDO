(function () {
    'use strict';

    const config = window.REALTIME_CONFIG;
    if (!config || !config.notificationsUrl) {
        return;
    }

    let knownNotificationIds = new Set((config.initialNotificationIds || []).map(Number));
    let requestInProgress = false;

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
    }

    function renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (!notifications.length) {
            list.innerHTML = '<div class="p-6 text-center text-gray-500 dark:text-gray-400">No new notifications</div>';
            return;
        }

        list.innerHTML = `
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                ${notifications.map(notification => `
                    <div class="realtime-notification p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                         data-notification-id="${Number(notification.id)}"
                         data-related-id="${escapeHtml(notification.relatedId)}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">${escapeHtml(notification.title)}</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">${escapeHtml(notification.message).slice(0, 100)}${notification.message.length > 100 ? '...' : ''}</p>
                                <p class="text-gray-500 dark:text-gray-500 text-xs mt-1">${escapeHtml(notification.createdAt)}</p>
                            </div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-1 ml-2 flex-shrink-0"></div>
                        </div>
                    </div>
                `).join('')}
            </div>`;

        list.querySelectorAll('.realtime-notification').forEach(item => {
            item.addEventListener('click', function () {
                markAsRead(
                    Number(this.dataset.notificationId),
                    this.dataset.relatedId || ''
                );
            });
        });
    }

    async function refreshNotifications() {
        if (requestInProgress || document.hidden) return;
        requestInProgress = true;

        try {
            const response = await fetch(config.notificationsUrl, {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`Notification request failed (${response.status})`);

            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Notification request failed');

            const notifications = data.notifications || [];
            const newNotifications = notifications.filter(notification => !knownNotificationIds.has(Number(notification.id)));
            const badge = document.getElementById('notificationBadge');

            if (badge) {
                badge.textContent = data.count > 9 ? '9+' : String(data.count);
                badge.classList.toggle('hidden', data.count === 0);
            }
            renderNotifications(notifications);

            newNotifications.forEach(notification => {
                showInfoNotification(notification.title || 'You have a new notification');
            });
            knownNotificationIds = new Set(notifications.map(notification => Number(notification.id)));
        } catch (error) {
            console.error('Realtime notification refresh failed:', error);
        } finally {
            requestInProgress = false;
        }
    }

    window.addEventListener('DOMContentLoaded', function () {
        refreshNotifications();
        window.setInterval(refreshNotifications, 15000);
    });
})();
