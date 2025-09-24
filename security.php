<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $response['message'] = 'Please fill in all fields';
            } elseif ($newPassword !== $confirmPassword) {
                $response['message'] = 'New passwords do not match';
            } elseif (strlen($newPassword) < 8) {
                $response['message'] = 'Password must be at least 8 characters long';
            } else {
                // Verify current password
                $user = $db->fetch("SELECT password_hash FROM users WHERE user_id = ?", [$userId]);
                if ($user && password_verify($currentPassword, $user['password_hash'])) {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->execute("UPDATE users SET password_hash = ? WHERE user_id = ?", [$newPasswordHash, $userId]);
                    logSecurityEvent($userId, 'password_change');
                    $response['success'] = true;
                    $response['message'] = 'Password changed successfully';
                } else {
                    $response['message'] = 'Current password is incorrect';
                }
            }
            break;
            
        case 'toggle_2fa':
            $enable = $_POST['enable_2fa'] === '1';
            $secret = $enable ? generateSecureToken(16) : null;
            $db->execute("UPDATE users SET two_factor_enabled = ?, two_factor_secret = ? WHERE user_id = ?", 
                [$enable, $secret, $userId]);
            logSecurityEvent($userId, $enable ? '2fa_enabled' : '2fa_disabled');
            $response['success'] = true;
            $response['message'] = $enable ? '2FA enabled successfully' : '2FA disabled successfully';
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Get user information
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    
    // Get security logs
    $securityLogs = $db->fetchAll("
        SELECT * FROM security_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ", [$userId]);
    
} catch (Exception $e) {
    $error = "Unable to load security data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - PayClone</title>
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

        .security-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.1);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #164e63;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #164e63;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #d1d5db;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white;
        }

        .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .security-info h4 {
            color: #164e63;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .security-info p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: #d1d5db;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: #0ea5e9;
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-switch.active::before {
            transform: translateX(30px);
        }

        .log-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-info h4 {
            color: #164e63;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .log-info p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .log-time {
            color: #6b7280;
            font-size: 0.75rem;
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

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .security-grid {
                grid-template-columns: 1fr;
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
                <a href="security.php" class="nav-item active">Security</a>
                <a href="?logout=1" class="nav-item">Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Security Settings</h1>
            </div>

            <div class="security-grid">
                <div class="card">
                    <h2 class="card-title">Password & Authentication</h2>
                    
                    <div id="alert-container"></div>

                    <!-- Change Password -->
                    <form id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="card-title">Security Features</h2>
                    
                    <!-- Two-Factor Authentication -->
                    <div class="security-item">
                        <div class="security-info">
                            <h4>Two-Factor Authentication</h4>
                            <p>Add an extra layer of security to your account</p>
                        </div>
                        <div class="toggle-switch <?php echo $user['two_factor_enabled'] ? 'active' : ''; ?>" 
                             onclick="toggle2FA(<?php echo $user['two_factor_enabled'] ? '0' : '1'; ?>)">
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="security-item">
                        <div class="security-info">
                            <h4>Account Status</h4>
                            <p>Your account is <?php echo ucfirst($user['account_status']); ?></p>
                        </div>
                        <span class="btn btn-success" style="font-size: 0.875rem;">
                            <?php echo ucfirst($user['account_status']); ?>
                        </span>
                    </div>

                    <!-- Email Verification -->
                    <div class="security-item">
                        <div class="security-info">
                            <h4>Email Verification</h4>
                            <p>Your email is <?php echo $user['email_verified'] ? 'verified' : 'not verified'; ?></p>
                        </div>
                        <span class="btn <?php echo $user['email_verified'] ? 'btn-success' : 'btn-danger'; ?>" style="font-size: 0.875rem;">
                            <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                        </span>
                    </div>
                </div>

                <div class="card" style="grid-column: 1 / -1;">
                    <h2 class="card-title">Security Activity Log</h2>
                    
                    <div class="security-logs">
                        <?php if (empty($securityLogs)): ?>
                            <div style="text-align: center; color: #6b7280; padding: 2rem;">
                                No security activity recorded
                            </div>
                        <?php else: ?>
                            <?php foreach ($securityLogs as $log): ?>
                                <div class="log-item">
                                    <div class="log-info">
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $log['event_type'])); ?></h4>
                                        <p>IP: <?php echo htmlspecialchars($log['ip_address']); ?></p>
                                    </div>
                                    <div class="log-time">
                                        <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Password form submission
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('security.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    this.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        // Toggle 2FA
        async function toggle2FA(enable) {
            const formData = new FormData();
            formData.append('action', 'toggle_2fa');
            formData.append('enable_2fa', enable);
            
            try {
                const response = await fetch('security.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
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
            }, 5000);
        }
    </script>
</body>
</html>
