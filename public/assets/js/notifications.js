/**
 * Real-time Notification System
 */

class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.updateInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.createNotificationBell();
        this.fetchNotifications();
        this.startPolling();
    }

    createNotificationBell() {
        const bellHTML = `
            <div class="notification-bell" id="notificationBell">
                <button class="btn btn-link position-relative" onclick="notificationManager.toggleDropdown()">
                    <i class="fas fa-bell fs-5"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </button>

                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h6>Bildirimler</h6>
                        <button class="btn btn-sm btn-link" onclick="notificationManager.markAllAsRead()">
                            Tümünü Okundu İşaretle
                        </button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="text-center py-4">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <p class="mt-2 mb-0">Yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .notification-bell {
                    position: relative;
                }
                .notification-bell .btn {
                    color: #4a5568;
                }
                .notification-badge {
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: #ef4444;
                    color: white;
                    border-radius: 10px;
                    padding: 2px 6px;
                    font-size: 0.7rem;
                    font-weight: 600;
                }
                .notification-dropdown {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    width: 350px;
                    max-height: 500px;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                    z-index: 1000;
                    margin-top: 10px;
                }
                .notification-header {
                    padding: 15px 20px;
                    border-bottom: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .notification-header h6 {
                    margin: 0;
                    font-weight: 600;
                }
                .notification-list {
                    max-height: 400px;
                    overflow-y: auto;
                }
                .notification-item {
                    padding: 15px 20px;
                    border-bottom: 1px solid #f1f5f9;
                    cursor: pointer;
                    transition: background 0.2s;
                }
                .notification-item:hover {
                    background: #f8fafc;
                }
                .notification-item.unread {
                    background: #eff6ff;
                }
                .notification-item-title {
                    font-weight: 600;
                    font-size: 0.9rem;
                    color: #2d3748;
                    margin-bottom: 5px;
                }
                .notification-item-message {
                    font-size: 0.85rem;
                    color: #718096;
                    margin-bottom: 5px;
                }
                .notification-item-time {
                    font-size: 0.75rem;
                    color: #a0aec0;
                }
                .notification-empty {
                    padding: 40px 20px;
                    text-align: center;
                    color: #a0aec0;
                }
            </style>
        `;

        // Insert bell into navbar
        const navbarEnd = document.querySelector('.navbar .ms-auto, .navbar .navbar-nav');
        if (navbarEnd) {
            const bellContainer = document.createElement('div');
            bellContainer.innerHTML = bellHTML;
            navbarEnd.prepend(bellContainer.firstElementChild);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const bell = document.getElementById('notificationBell');
            const dropdown = document.getElementById('notificationDropdown');
            if (bell && !bell.contains(e.target) && dropdown) {
                dropdown.style.display = 'none';
            }
        });
    }

    async fetchNotifications() {
        try {
            const response = await fetch('/api/notifications.php?action=get&limit=10');
            const data = await response.json();

            if (data.success) {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.updateUI();
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    }

    updateUI() {
        // Update badge
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Update list
        const list = document.getElementById('notificationList');
        if (list) {
            if (this.notifications.length === 0) {
                list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash fa-2x mb-2"></i><p>Bildirim yok</p></div>';
            } else {
                list.innerHTML = this.notifications.map(n => this.renderNotification(n)).join('');
            }
        }
    }

    renderNotification(notification) {
        const timeAgo = this.getTimeAgo(notification.created_at);
        const unreadClass = notification.is_read === '0' ? 'unread' : '';

        return `
            <div class="notification-item ${unreadClass}" onclick="notificationManager.handleClick(${notification.id}, '${notification.link}')">
                <div class="notification-item-title">
                    ${this.getIcon(notification.type)} ${notification.title}
                </div>
                <div class="notification-item-message">${notification.message}</div>
                <div class="notification-item-time">${timeAgo}</div>
            </div>
        `;
    }

    getIcon(type) {
        const icons = {
            'appointment': '<i class="fas fa-calendar-check text-primary"></i>',
            'message': '<i class="fas fa-envelope text-info"></i>',
            'payment': '<i class="fas fa-credit-card text-success"></i>',
            'system': '<i class="fas fa-info-circle text-warning"></i>'
        };
        return icons[type] || icons['system'];
    }

    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Az önce';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' dakika önce';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' saat önce';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' gün önce';

        return date.toLocaleDateString('tr-TR');
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    }

    async handleClick(notificationId, link) {
        // Mark as read
        await this.markAsRead(notificationId);

        // Navigate to link
        if (link) {
            window.location.href = link;
        }
    }

    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);

            await fetch('/api/notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });

            // Refresh notifications
            await this.fetchNotifications();
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            await fetch('/api/notifications.php?action=mark_all_read', {
                method: 'POST'
            });

            // Refresh notifications
            await this.fetchNotifications();
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    }

    startPolling() {
        setInterval(() => {
            this.fetchNotifications();
        }, this.updateInterval);
    }
}

// Initialize notification manager when page loads
let notificationManager;
document.addEventListener('DOMContentLoaded', () => {
    notificationManager = new NotificationManager();
});
