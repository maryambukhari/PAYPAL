<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

try {
    // Get user information
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    
    // Get filter parameters
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build query based on filters
    $whereConditions = ["(t.sender_id = ? OR t.receiver_id = ?)"];
    $params = [$userId, $userId];
    
    if ($filter !== 'all') {
        $whereConditions[] = "t.transaction_type = ?";
        $params[] = $filter;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(t.description LIKE ? OR t.transaction_id LIKE ? OR u1.email LIKE ? OR u2.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get transactions with user details
    $transactions = $db->fetchAll("
        SELECT t.*, 
               u1.first_name as sender_first_name, u1.last_name as sender_last_name, u1.email as sender_email,
               u2.first_name as receiver_first_name, u2.last_name as receiver_last_name, u2.email as receiver_email
        FROM transactions t
        LEFT JOIN users u1 ON t.sender_id = u1.user_id
        LEFT JOIN users u2 ON t.receiver_id = u2.user_id
        WHERE $whereClause
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Get total count for pagination
    $totalCount = $db->fetch("
        SELECT COUNT(*) as count
        FROM transactions t
        LEFT JOIN users u1 ON t.sender_id = u1.user_id
        LEFT JOIN users u2 ON t.receiver_id = u2.user_id
        WHERE $whereClause
    ", $params)['count'];
    
    $totalPages = ceil($totalCount / $limit);
    
} catch (Exception $e) {
    $error = "Unable to load transaction data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - PayClone</title>
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

        /* Sidebar Styles - Same as other pages */
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

        /* Filters */
        .filters-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
            margin-bottom: 2rem;
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: #164e63;
            font-size: 0.875rem;
        }

        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            min-width: 150px;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        /* Transactions */
        .transactions-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
            overflow: hidden;
        }

        .transactions-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #164e63;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.3s ease;
        }

        .transaction-item:hover {
            background: #f9fafb;
        }

        .transaction-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .transaction-icon.sent {
            background: linear-gradient(135deg, #fef3c7, #f59e0b);
        }

        .transaction-icon.received {
            background: linear-gradient(135deg, #d1fae5, #10b981);
        }

        .transaction-icon.pending {
            background: linear-gradient(135deg, #e0e7ff, #6366f1);
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

        .transaction-date {
            color: #9ca3af;
            font-size: 0.75rem;
        }

        .transaction-amount {
            text-align: right;
        }

        .amount-value {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .amount-value.positive {
            color: #10b981;
        }

        .amount-value.negative {
            color: #f59e0b;
        }

        .transaction-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            color: #6b7280;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            background: #f3f4f6;
            border-color: #0ea5e9;
            color: #0ea5e9;
        }

        .pagination-btn.active {
            background: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
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

            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .filter-select, .filter-input {
                min-width: auto;
                width: 100%;
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
                <a href="transactions.php" class="nav-item active">
                    <i>📋</i> Transactions
                </a>
                <a href="wallet.php" class="nav-item">
                    <i>👛</i> Wallet
                </a>
                <a href="security.php" class="nav-item">
                    <i>🔒</i> Security
                </a>
                <a href="notifications.php" class="nav-item">
                    <i>🔔</i> Notifications
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
                    <h1 class="page-title">Transaction History</h1>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card">
                <form method="GET" action="transactions.php">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label class="filter-label">Filter by Type</label>
                            <select name="filter" class="filter-select">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                <option value="send" <?php echo $filter === 'send' ? 'selected' : ''; ?>>Sent Money</option>
                                <option value="receive" <?php echo $filter === 'receive' ? 'selected' : ''; ?>>Received Money</option>
                                <option value="deposit" <?php echo $filter === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                                <option value="withdrawal" <?php echo $filter === 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Search transactions..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                </form>
            </div>

            <!-- Transactions -->
            <div class="transactions-card">
                <div class="transactions-header">
                    <?php echo number_format($totalCount); ?> Transaction<?php echo $totalCount !== 1 ? 's' : ''; ?> Found
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No transactions found</h3>
                        <p>Your transaction history will appear here</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php
                        $isSender = $transaction['sender_id'] === $userId;
                        $isReceiver = $transaction['receiver_id'] === $userId;
                        $otherUser = $isSender ? 
                            $transaction['receiver_first_name'] . ' ' . $transaction['receiver_last_name'] :
                            $transaction['sender_first_name'] . ' ' . $transaction['sender_last_name'];
                        
                        $iconClass = '';
                        $titleText = '';
                        $amountClass = '';
                        $amountPrefix = '';
                        
                        if ($transaction['transaction_type'] === 'send' && $isSender) {
                            $iconClass = 'sent';
                            $titleText = "Sent to " . $otherUser;
                            $amountClass = 'negative';
                            $amountPrefix = '-';
                        } elseif ($transaction['transaction_type'] === 'send' && $isReceiver) {
                            $iconClass = 'received';
                            $titleText = "Received from " . $otherUser;
                            $amountClass = 'positive';
                            $amountPrefix = '+';
                        } elseif ($transaction['transaction_type'] === 'receive') {
                            if ($transaction['status'] === 'pending') {
                                $iconClass = 'pending';
                                $titleText = $isSender ? "Payment request to " . $otherUser : "Payment request from " . $otherUser;
                            } else {
                                $iconClass = $isSender ? 'received' : 'sent';
                                $titleText = $isSender ? "Received from " . $otherUser : "Sent to " . $otherUser;
                                $amountClass = $isSender ? 'positive' : 'negative';
                                $amountPrefix = $isSender ? '+' : '-';
                            }
                        } elseif ($transaction['transaction_type'] === 'deposit') {
                            $iconClass = 'received';
                            $titleText = "Deposit to Wallet";
                            $amountClass = 'positive';
                            $amountPrefix = '+';
                        } elseif ($transaction['transaction_type'] === 'withdrawal') {
                            $iconClass = 'sent';
                            $titleText = "Withdrawal from Wallet";
                            $amountClass = 'negative';
                            $amountPrefix = '-';
                        }
                        
                        $statusClass = 'status-' . $transaction['status'];
                        ?>
                        <div class="transaction-item">
                            <div class="transaction-icon <?php echo $iconClass; ?>">
                                <?php
                                switch ($transaction['transaction_type']) {
                                    case 'send': echo $isSender ? '💸' : '💰'; break;
                                    case 'receive': echo $transaction['status'] === 'pending' ? '⏳' : ($isSender ? '💰' : '💸'); break;
                                    case 'deposit': echo '⬇️'; break;
                                    case 'withdrawal': echo '⬆️'; break;
                                    default: echo '💳'; break;
                                }
                                ?>
                            </div>
                            
                            <div class="transaction-details">
                                <div class="transaction-title"><?php echo htmlspecialchars($titleText); ?></div>
                                <div class="transaction-desc"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                <div class="transaction-date">
                                    <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?> • 
                                    ID: <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                                </div>
                            </div>
                            
                            <div class="transaction-amount">
                                <div class="amount-value <?php echo $amountClass; ?>">
                                    <?php echo $amountPrefix . formatCurrency($transaction['amount']); ?>
                                </div>
                                <div class="transaction-status <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-btn">← Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-btn">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
    </script>
</body>
</html>
