<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get user and wallet information
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    
    // Get wallet transactions with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $transactions = $db->fetchAll("
        SELECT t.*, 
               sender.first_name as sender_name, sender.last_name as sender_lastname,
               receiver.first_name as receiver_name, receiver.last_name as receiver_lastname
        FROM transactions t
        LEFT JOIN users sender ON t.sender_id = sender.user_id
        LEFT JOIN users receiver ON t.receiver_id = receiver.user_id
        WHERE t.sender_id = ? OR t.receiver_id = ?
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ", [$userId, $userId, $limit, $offset]);
    
    // Get transaction count for pagination
    $totalTransactions = $db->fetch("
        SELECT COUNT(*) as count FROM transactions 
        WHERE sender_id = ? OR receiver_id = ?
    ", [$userId, $userId])['count'];
    
    $totalPages = ceil($totalTransactions / $limit);
    
    // Get spending analytics for current month
    $currentMonth = date('Y-m');
    $monthlySpending = $db->fetch("
        SELECT 
            COALESCE(SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END), 0) as sent,
            COALESCE(SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END), 0) as received
        FROM transactions 
        WHERE (sender_id = ? OR receiver_id = ?) 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?
        AND status = 'completed'
    ", [$userId, $userId, $userId, $userId, $currentMonth]);
    
} catch (Exception $e) {
    $error = "Unable to load wallet data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - PayClone</title>
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

        /* Sidebar Styles - Same as dashboard */
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

        /* Wallet Grid */
        .wallet-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .wallet-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .wallet-side {
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

        .wallet-id {
            opacity: 0.8;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .balance-label {
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
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

        /* Analytics Cards */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #f0f9ff, #ecfeff);
            border: 1px solid #e0f2fe;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #164e63;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #475569;
            font-size: 0.875rem;
        }

        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .stat-change.positive {
            color: #16a34a;
        }

        .stat-change.negative {
            color: #dc2626;
        }

        /* Transaction History */
        .transaction-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 0.5rem;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active, .filter-btn:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
            background: #f0f9ff;
        }

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
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .transaction-icon.sent {
            background: #fef2f2;
            color: #dc2626;
        }

        .transaction-icon.received {
            background: #f0fdf4;
            color: #16a34a;
        }

        .transaction-icon.deposit {
            background: #f0f9ff;
            color: #0ea5e9;
        }

        .transaction-icon.withdrawal {
            background: #fef3c7;
            color: #d97706;
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
            margin-bottom: 0.25rem;
        }

        .transaction-id {
            color: #9ca3af;
            font-size: 0.75rem;
            font-family: monospace;
        }

        .transaction-amount {
            text-align: right;
        }

        .transaction-amount-value {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .transaction-amount-value.positive {
            color: #16a34a;
        }

        .transaction-amount-value.negative {
            color: #dc2626;
        }

        .transaction-date {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .transaction-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .status-completed {
            background: #f0fdf4;
            color: #16a34a;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-failed {
            background: #fef2f2;
            color: #dc2626;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
        }

        .pagination .current {
            background: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
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

            .wallet-grid {
                grid-template-columns: 1fr;
            }

            .analytics-grid {
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

            .transaction-filters {
                justify-content: center;
            }

            .transaction-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .transaction-amount {
                text-align: left;
                width: 100%;
            }
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
                <a href="dashboard.php" class="nav-item">
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
                <a href="wallet.php" class="nav-item active">
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
                    <h1 class="page-title">My Wallet</h1>
                </div>
                <div class="top-actions">
                    <a href="add-funds.php" class="btn btn-primary">Add Funds</a>
                    <a href="withdraw.php" class="btn btn-secondary">Withdraw</a>
                </div>
            </div>

            <div class="wallet-grid">
                <div class="wallet-main">
                    <!-- Balance Card -->
                    <div class="card balance-card">
                        <div class="balance-content">
                            <div class="wallet-id">Wallet ID: <?php echo htmlspecialchars($wallet['wallet_id']); ?></div>
                            <div class="balance-label">Available Balance</div>
                            <div class="balance-amount">
                                <?php echo formatCurrency($wallet['balance']); ?>
                            </div>
                            <div class="balance-actions">
                                <a href="add-funds.php" class="balance-btn">💳 Add Funds</a>
                                <a href="withdraw.php" class="balance-btn">🏦 Withdraw</a>
                                <a href="send-money.php" class="balance-btn">💸 Send Money</a>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Transaction History</h2>
                        </div>
                        
                        <div class="transaction-filters">
                            <button class="filter-btn active" onclick="filterTransactions('all')">All</button>
                            <button class="filter-btn" onclick="filterTransactions('sent')">Sent</button>
                            <button class="filter-btn" onclick="filterTransactions('received')">Received</button>
                            <button class="filter-btn" onclick="filterTransactions('deposit')">Deposits</button>
                            <button class="filter-btn" onclick="filterTransactions('withdrawal')">Withdrawals</button>
                        </div>

                        <div class="transactions-list">
                            <?php if (empty($transactions)): ?>
                                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
                                    <h3>No transactions yet</h3>
                                    <p>Your transaction history will appear here</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    $isReceived = $transaction['receiver_id'] === $userId;
                                    $isSent = $transaction['sender_id'] === $userId;
                                    $type = $transaction['transaction_type'];
                                    
                                    if ($type === 'deposit') {
                                        $icon = '💳';
                                        $iconClass = 'deposit';
                                        $title = 'Deposit to Wallet';
                                        $description = 'Added funds to your wallet';
                                        $amountClass = 'positive';
                                        $amountPrefix = '+';
                                    } elseif ($type === 'withdrawal') {
                                        $icon = '🏦';
                                        $iconClass = 'withdrawal';
                                        $title = 'Withdrawal from Wallet';
                                        $description = 'Withdrew funds from your wallet';
                                        $amountClass = 'negative';
                                        $amountPrefix = '-';
                                    } elseif ($isReceived) {
                                        $icon = '↓';
                                        $iconClass = 'received';
                                        $otherParty = $transaction['sender_name'] . ' ' . $transaction['sender_lastname'];
                                        $title = 'Received from ' . htmlspecialchars($otherParty);
                                        $description = htmlspecialchars($transaction['description'] ?? 'Payment received');
                                        $amountClass = 'positive';
                                        $amountPrefix = '+';
                                    } else {
                                        $icon = '↑';
                                        $iconClass = 'sent';
                                        $otherParty = $transaction['receiver_name'] . ' ' . $transaction['receiver_lastname'];
                                        $title = 'Sent to ' . htmlspecialchars($otherParty);
                                        $description = htmlspecialchars($transaction['description'] ?? 'Payment sent');
                                        $amountClass = 'negative';
                                        $amountPrefix = '-';
                                    }
                                    ?>
                                    <div class="transaction-item" data-type="<?php echo $type; ?>">
                                        <div class="transaction-icon <?php echo $iconClass; ?>">
                                            <?php echo $icon; ?>
                                        </div>
                                        <div class="transaction-details">
                                            <div class="transaction-title"><?php echo $title; ?></div>
                                            <div class="transaction-desc"><?php echo $description; ?></div>
                                            <div class="transaction-id">ID: <?php echo htmlspecialchars($transaction['transaction_id']); ?></div>
                                        </div>
                                        <div class="transaction-amount">
                                            <div class="transaction-amount-value <?php echo $amountClass; ?>">
                                                <?php echo $amountPrefix . formatCurrency($transaction['amount']); ?>
                                            </div>
                                            <div class="transaction-date">
                                                <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                            </div>
                                            <div class="transaction-status status-<?php echo $transaction['status']; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>">Next →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wallet-side">
                    <!-- Monthly Analytics -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">This Month</h2>
                        </div>
                        <div class="analytics-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo formatCurrency($monthlySpending['sent']); ?></div>
                                <div class="stat-label">Total Sent</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo formatCurrency($monthlySpending['received']); ?></div>
                                <div class="stat-label">Total Received</div>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Info -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Wallet Information</h2>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Wallet Status</span>
                                <span style="color: #16a34a; font-weight: 600;">
                                    <?php echo ucfirst($wallet['status']); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Currency</span>
                                <span style="font-weight: 600;"><?php echo $wallet['currency']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Created</span>
                                <span style="font-weight: 600;">
                                    <?php echo date('M j, Y', strtotime($wallet['created_at'])); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Last Updated</span>
                                <span style="font-weight: 600;">
                                    <?php echo date('M j, Y', strtotime($wallet['updated_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Quick Actions</h2>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="add-funds.php" class="btn btn-primary">💳 Add Funds</a>
                            <a href="withdraw.php" class="btn btn-secondary">🏦 Withdraw Money</a>
                            <a href="send-money.php" class="btn btn-secondary">💸 Send Money</a>
                            <a href="transactions.php" class="btn btn-secondary">📋 View All Transactions</a>
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

        // Transaction filtering
        function filterTransactions(type) {
            const buttons = document.querySelectorAll('.filter-btn');
            const transactions = document.querySelectorAll('.transaction-item');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter transactions
            transactions.forEach(transaction => {
                if (type === 'all') {
                    transaction.style.display = 'flex';
                } else {
                    const transactionType = transaction.dataset.type;
                    if (type === 'sent' && (transactionType === 'send' || transactionType === 'withdrawal')) {
                        transaction.style.display = 'flex';
                    } else if (type === 'received' && (transactionType === 'receive' || transactionType === 'deposit')) {
                        transaction.style.display = 'flex';
                    } else if (type === transactionType) {
                        transaction.style.display = 'flex';
                    } else {
                        transaction.style.display = 'none';
                    }
                }
            });
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
    </script>
</body>
</html>
