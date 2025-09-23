<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get user information
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    
    // Get wallet information
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    
    // Get recent transactions
    $recentTransactions = $db->fetchAll("
        SELECT t.*, 
               sender.first_name as sender_name, sender.last_name as sender_lastname,
               receiver.first_name as receiver_name, receiver.last_name as receiver_lastname
        FROM transactions t
        LEFT JOIN users sender ON t.sender_id = sender.user_id
        LEFT JOIN users receiver ON t.receiver_id = receiver.user_id
        WHERE t.sender_id = ? OR t.receiver_id = ?
        ORDER BY t.created_at DESC
        LIMIT 10
    ", [$userId, $userId]);
    
    // Get notifications
    $notifications = $db->fetchAll("
        SELECT * FROM notifications 
        WHERE user_id = ? AND read_status = FALSE 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$userId]);
    
} catch (Exception $e) {
    $error = "Unable to load dashboard data";
}

// Handle logout
if (isset($_GET['logout'])) {
    logSecurityEvent($userId, 'logout');
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PayClone</title>
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

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(14, 165, 233, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
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

        .user-info {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .user-name {
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.25rem;
        }

        .user-email {
            color: #6b7280;
            font-size: 0.875rem;
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

        .nav-item i {
            width: 20px;
            margin-right: 1rem;
        }

        /* Main Content */
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

        .top-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #164e63;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-panel {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #164e63;
        }

        /* Balance Card */
        .balance-card {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }

        .balance-content {
            position: relative;
            z-index: 2;
        }

        .balance-label {
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .balance-actions {
            display: flex;
            gap: 1rem;
        }

        .balance-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .balance-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-card:hover {
            border-color: #0ea5e9;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.15);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .action-title {
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.5rem;
        }

        .action-desc {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Transactions */
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .transaction-icon.sent {
            background: #fef2f2;
            color: #dc2626;
        }

        .transaction-icon.received {
            background: #f0fdf4;
            color: #16a34a;
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-title {
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.25rem;
        }

        .transaction-desc {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .transaction-amount {
            font-weight: 600;
            text-align: right;
        }

        .transaction-amount.positive {
            color: #16a34a;
        }

        .transaction-amount.negative {
            color: #dc2626;
        }

        .transaction-date {
            color: #6b7280;
            font-size: 0.75rem;
            text-align: right;
        }

        /* Notifications */
        .notification-item {
            padding: 1rem;
            border-left: 3px solid #0ea5e9;
            background: #f0f9ff;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .notification-title {
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            color: #475569;
            font-size: 0.875rem;
        }

        .notification-time {
            color: #6b7280;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }

        /* Mobile Responsive */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #164e63;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .top-actions {
                justify-content: center;
            }

            .balance-amount {
                font-size: 2rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Loading States */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #6b7280;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #0ea5e9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">PayClone</div>
            </div>

            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>

            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <i>📊</i> Dashboard
                </a>
                <a href="send-money.php" class="nav-item">
                    <i>💸</i> Send Money
                </a>
                <a href="receive-money.php" class="nav-item">
                    <i>💰</i> Request Money
                </a>
                <a href="transactions.php" class="nav-item">
                    <i>📋</i> Transactions
                </a>
                <a href="wallet.php" class="nav-item">
                    <i>👛</i> Wallet
                </a>
                <a href="settings.php" class="nav-item">
                    <i>⚙️</i> Settings
                </a>
                <a href="?logout=1" class="nav-item">
                    <i>🚪</i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                    <h1 class="page-title">Dashboard</h1>
                </div>
                <div class="top-actions">
                    <a href="send-money.php" class="btn btn-primary">Send Money</a>
                    <a href="receive-money.php" class="btn btn-secondary">Request Money</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="main-panel">
                    <!-- Balance Card -->
                    <div class="card balance-card">
                        <div class="balance-content">
                            <div class="balance-label">Available Balance</div>
                            <div class="balance-amount">
                                <?php echo formatCurrency($wallet['balance'] ?? 0); ?>
                            </div>
                            <div class="balance-actions">
                                <a href="add-funds.php" class="balance-btn">Add Funds</a>
                                <a href="withdraw.php" class="balance-btn">Withdraw</a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Quick Actions</h2>
                        </div>
                        <div class="quick-actions">
                            <div class="action-card" onclick="redirectTo('send-money.php')">
                                <div class="action-icon">💸</div>
                                <div class="action-title">Send Money</div>
                                <div class="action-desc">Transfer funds instantly</div>
                            </div>
                            <div class="action-card" onclick="redirectTo('receive-money.php')">
                                <div class="action-icon">💰</div>
                                <div class="action-title">Request Money</div>
                                <div class="action-desc">Request payment from others</div>
                            </div>
                            <div class="action-card" onclick="redirectTo('add-funds.php')">
                                <div class="action-icon">💳</div>
                                <div class="action-title">Add Funds</div>
                                <div class="action-desc">Top up your wallet</div>
                            </div>
                            <div class="action-card" onclick="redirectTo('transactions.php')">
                                <div class="action-icon">📋</div>
                                <div class="action-title">View History</div>
                                <div class="action-desc">Check all transactions</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent Transactions</h2>
                            <a href="transactions.php" class="btn btn-secondary">View All</a>
                        </div>
                        <div class="transactions-list">
                            <?php if (empty($recentTransactions)): ?>
                                <div class="loading">
                                    <span>No transactions yet. Start by sending or receiving money!</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <?php
                                    $isReceived = $transaction['receiver_id'] === $userId;
                                    $otherParty = $isReceived ? 
                                        ($transaction['sender_name'] . ' ' . $transaction['sender_lastname']) : 
                                        ($transaction['receiver_name'] . ' ' . $transaction['receiver_lastname']);
                                    ?>
                                    <div class="transaction-item">
                                        <div class="transaction-icon <?php echo $isReceived ? 'received' : 'sent'; ?>">
                                            <?php echo $isReceived ? '↓' : '↑'; ?>
                                        </div>
                                        <div class="transaction-details">
                                            <div class="transaction-title">
                                                <?php echo $isReceived ? 'Received from' : 'Sent to'; ?> <?php echo htmlspecialchars($otherParty); ?>
                                            </div>
                                            <div class="transaction-desc">
                                                <?php echo htmlspecialchars($transaction['description'] ?? 'Payment'); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="transaction-amount <?php echo $isReceived ? 'positive' : 'negative'; ?>">
                                                <?php echo ($isReceived ? '+' : '-') . formatCurrency($transaction['amount']); ?>
                                            </div>
                                            <div class="transaction-date">
                                                <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="side-panel">
                    <!-- Account Overview -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Account Overview</h2>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Account Status</span>
                                <span style="color: #16a34a; font-weight: 600;">
                                    <?php echo ucfirst($user['account_status']); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Email Verified</span>
                                <span style="color: <?php echo $user['email_verified'] ? '#16a34a' : '#dc2626'; ?>; font-weight: 600;">
                                    <?php echo $user['email_verified'] ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>2FA Enabled</span>
                                <span style="color: <?php echo $user['two_factor_enabled'] ? '#16a34a' : '#dc2626'; ?>; font-weight: 600;">
                                    <?php echo $user['two_factor_enabled'] ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Member Since</span>
                                <span style="font-weight: 600;">
                                    <?php echo date('M Y', strtotime($user['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Notifications</h2>
                        </div>
                        <div class="notifications-list">
                            <?php if (empty($notifications)): ?>
                                <div style="text-align: center; color: #6b7280; padding: 1rem;">
                                    No new notifications
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="notification-time">
                                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });

        // Redirect function
        function redirectTo(page) {
            document.body.style.opacity = '0.8';
            setTimeout(() => {
                window.location.href = page;
            }, 300);
        }

        // Auto-refresh balance every 30 seconds
        setInterval(function() {
            // You can add AJAX call here to refresh balance
        }, 30000);

        // Add loading states for action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
        });
    </script>
</body>
</html>
