<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? '';
            if ($notificationId) {
                $db->execute("UPDATE notifications SET read_status = TRUE WHERE id = ? AND user_id = ?", 
                    [$notificationId, $userId]);
                $response['success'] = true;
            }
            break;
            
        case 'mark_all_read':
            $db->execute("UPDATE notifications SET read_status = TRUE WHERE user_id = ?", [$userId]);
            $response['success'] = true;
            $response['message'] = 'All notifications marked as read';
            break;
            
        case 'delete_notification':
            $notificationId = $_POST['notification_id'] ?? '';
            if ($notificationId) {
                $db->execute("DELETE FROM notifications WHERE id = ? AND user_id = ?", 
                    [$notificationId, $userId]);
                $response['success'] = true;
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Get all notifications
    $notifications = $db->fetchAll("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ", [$userId]);
    
    // Get unread count
    $unreadCount = $db->fetch("
        SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = ? AND read_status = FALSE
    ", [$userId])['count'];
    
} catch (Exception $e) {
    $error = "Unable to load notifications";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PayClone</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #f9fafb 0%, #ecfeff 100%);
            color: #164e63;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(14, 165, 233, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-item {
            display: block;
            padding: 1rem 2rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.1), transparent);
            color: #0ea5e9;
            border-left-color: #0ea5e9;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #164e63;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
        }

        .notifications-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
            overflow: hidden;
        }

        .notifications-header {
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-item:hover {
            background: #f9fafb;
        }

        .notification-item.unread {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.05), transparent);
            border-left: 4px solid #0ea5e9;
        }

        .notification-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .notification-info {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.5rem;
        }

        .notification-message {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-read-btn {
            background: #e0f2fe;
            color: #0369a1;
        }

        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .notification-content {
                flex-direction: column;
            }

            .notification-actions {
                margin-left: 0;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">PayClone</div>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="send-money.php" class="nav-item">Send Money</a>
                <a href="receive-money.php" class="nav-item">Request Money</a>
                <a href="wallet.php" class="nav-item">Wallet</a>
                <a href="notifications.php" class="nav-item active">Notifications</a>
                <a href="security.php" class="nav-item">Security</a>
                <a href="?logout=1" class="nav-item">Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Notifications (<?php echo $unreadCount; ?> unread)</h1>
                <?php if ($unreadCount > 0): ?>
                    <button class="btn btn-primary" onclick="markAllRead()">Mark All Read</button>
                <?php endif; ?>
            </div>

            <div id="alert-container"></div>

            <div class="notifications-container">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <h3>No notifications yet</h3>
                        <p>You'll see important updates and transaction alerts here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo !$notification['read_status'] ? 'unread' : ''; ?>" 
                             id="notification-<?php echo $notification['id']; ?>">
                            <div class="notification-content">
                                <div class="notification-info">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notification['read_status']): ?>
                                        <button class="action-btn mark-read-btn" 
                                                onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            Mark Read
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn delete-btn" 
                                            onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mark single notification as read
        async function markAsRead(notificationId) {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            
            try {
                const response = await fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const notification = document.getElementById(`notification-${notificationId}`);
                    notification.classList.remove('unread');
                    notification.querySelector('.mark-read-btn').remove();
                    updateUnreadCount();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Mark all notifications as read
        async function markAllRead() {
            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            
            try {
                const response = await fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        }

        // Delete notification
        async function deleteNotification(notificationId) {
            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_notification');
            formData.append('notification_id', notificationId);
            
            try {
                const response = await fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const notification = document.getElementById(`notification-${notificationId}`);
                    notification.remove();
                    updateUnreadCount();
                }
            } catch (error) {
                console.error('Error deleting notification:', error);
            }
        }

        // Update unread count in title
        function updateUnreadCount() {
            const unreadItems = document.querySelectorAll('.notification-item.unread').length;
            const title = document.querySelector('.page-title');
            title.textContent = `Notifications (${unreadItems} unread)`;
            
            if (unreadItems === 0) {
                const markAllBtn = document.querySelector('.btn-primary');
                if (markAllBtn) markAllBtn.remove();
            }
        }

        // Show alert function
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 3000);
        }
    </script>
</body>
</html>
