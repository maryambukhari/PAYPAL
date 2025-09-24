<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle withdraw form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $withdrawMethod = $_POST['withdraw_method'] ?? '';
    $description = trim($_POST['description'] ?? 'Wallet withdrawal');
    
    $response = ['success' => false, 'message' => ''];
    
    // Get current wallet balance
    $wallet = $db->fetch("SELECT balance FROM wallets WHERE user_id = ?", [$userId]);
    $currentBalance = $wallet['balance'] ?? 0;
    
    if ($amount <= 0) {
        $response['message'] = 'Please enter a valid amount';
    } elseif ($amount < 10) {
        $response['message'] = 'Minimum withdrawal amount is $10.00';
    } elseif ($amount > $currentBalance) {
        $response['message'] = 'Insufficient balance for this withdrawal';
    } elseif (empty($withdrawMethod)) {
        $response['message'] = 'Please select a withdrawal method';
    } else {
        try {
            $db->beginTransaction();
            
            // Create transaction record
            $transactionId = generateTransactionId();
            $sql = "INSERT INTO transactions (transaction_id, sender_id, transaction_type, amount, currency, net_amount, status, description, payment_method) 
                    VALUES (?, ?, 'withdrawal', ?, 'USD', ?, 'completed', ?, ?)";
            
            $db->query($sql, [$transactionId, $userId, $amount, $amount, $description, $withdrawMethod]);
            
            // Update wallet balance
            $db->execute("UPDATE wallets SET balance = balance - ?, updated_at = NOW() WHERE user_id = ?", [$amount, $userId]);
            
            // Log transaction history
            $db->query("INSERT INTO transaction_history (transaction_id, user_id, action, description) VALUES (?, ?, 'completed', 'Funds withdrawn successfully')", 
                [$transactionId, $userId]);
            
            // Create notification
            $db->query("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'transaction', 'Withdrawal Processed', ?)", 
                [$userId, "Successfully withdrew " . formatCurrency($amount) . " from your wallet"]);
            
            $db->commit();
            
            $response['success'] = true;
            $response['message'] = 'Withdrawal processed successfully!';
            $response['redirect'] = 'wallet.php';
            
        } catch (Exception $e) {
            $db->rollback();
            $response['message'] = 'Failed to process withdrawal. Please try again.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Get user and wallet information
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
} catch (Exception $e) {
    $error = "Unable to load account data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds - PayClone</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #ecfeff 0%, #ffffff 50%, #f0f9ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .withdraw-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .form-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .visual-section {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .visual-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .visual-content {
            text-align: center;
            color: white;
            z-index: 2;
            position: relative;
        }

        .visual-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .visual-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .back-link {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #164e63;
            transform: translateX(-5px);
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #164e63;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #475569;
            margin-bottom: 2rem;
        }

        .current-balance {
            background: linear-gradient(135deg, #f0f9ff, #ecfeff);
            border: 1px solid #e0f2fe;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .balance-label {
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #164e63;
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
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .amount-input {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        .withdraw-methods {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .withdraw-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .withdraw-method:hover, .withdraw-method.selected {
            border-color: #0ea5e9;
            background: #f0f9ff;
        }

        .withdraw-method input[type="radio"] {
            margin-right: 1rem;
            accent-color: #0ea5e9;
        }

        .method-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .method-details h4 {
            color: #164e63;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .method-details p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
            margin-bottom: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: transparent;
            color: #164e63;
            border: 2px solid #164e63;
        }

        .btn-secondary:hover {
            background: #164e63;
            color: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .security-note {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #92400e;
        }

        @media (max-width: 768px) {
            .withdraw-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .visual-section {
                display: none;
            }

            .form-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="withdraw-container">
        <div class="form-section">
            <a href="wallet.php" class="back-link">
                ← Back to Wallet
            </a>
            
            <h1 class="form-title">Withdraw Funds</h1>
            <p class="form-subtitle">Transfer money from your PayClone wallet</p>

            <div class="current-balance">
                <div class="balance-label">Available Balance</div>
                <div class="balance-amount"><?php echo formatCurrency($wallet['balance']); ?></div>
            </div>

            <div id="alert-container"></div>

            <form id="withdrawForm">
                <div class="form-group">
                    <label for="amount" class="form-label">Amount to Withdraw</label>
                    <input type="number" id="amount" name="amount" class="form-input amount-input" 
                           placeholder="0.00" min="10" max="<?php echo $wallet['balance']; ?>" step="0.01" required>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
                        Minimum withdrawal: $10.00
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Withdrawal Method</label>
                    <div class="withdraw-methods">
                        <label class="withdraw-method">
                            <input type="radio" name="withdraw_method" value="bank" required>
                            <div class="method-icon">🏦</div>
                            <div class="method-details">
                                <h4>Bank Transfer</h4>
                                <p>Direct transfer to your bank account (1-3 business days)</p>
                            </div>
                        </label>
                        
                        <label class="withdraw-method">
                            <input type="radio" name="withdraw_method" value="paypal" required>
                            <div class="method-icon">💰</div>
                            <div class="method-details">
                                <h4>PayPal</h4>
                                <p>Transfer to your PayPal account (instant)</p>
                            </div>
                        </label>
                        
                        <label class="withdraw-method">
                            <input type="radio" name="withdraw_method" value="check" required>
                            <div class="method-icon">📄</div>
                            <div class="method-details">
                                <h4>Paper Check</h4>
                                <p>Mailed to your registered address (5-7 business days)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <input type="text" id="description" name="description" class="form-input" 
                           placeholder="Wallet withdrawal" maxlength="255">
                </div>

                <button type="submit" class="btn btn-primary" id="withdrawBtn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    Process Withdrawal
                </button>
            </form>

            <button class="btn btn-secondary" onclick="redirectTo('wallet.php')">
                Cancel
            </button>

            <div class="security-note">
                ⚠️ Withdrawals cannot be cancelled once processed. Please verify your amount and method before confirming.
            </div>
        </div>

        <div class="visual-section">
            <div class="visual-content">
                <h2>Secure Withdrawals</h2>
                <p>Transfer your funds safely to your preferred account with multiple withdrawal options.</p>
                <div style="font-size: 4rem; margin-top: 2rem;">🏦</div>
            </div>
        </div>
    </div>

    <script>
        // Withdrawal method selection
        document.querySelectorAll('.withdraw-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.withdraw-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Form submission
        document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('withdrawBtn');
            const spinner = document.getElementById('loadingSpinner');
            const alertContainer = document.getElementById('alert-container');
            
            // Show loading state
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            alertContainer.innerHTML = '';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('withdraw.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
            }
        });

        // Show alert function
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
        }

        // Redirect function
        function redirectTo(page) {
            document.body.style.opacity = '0.8';
            setTimeout(() => {
                window.location.href = page;
            }, 300);
        }

        // Amount validation
        document.getElementById('amount').addEventListener('input', function() {
            const amount = parseFloat(this.value);
            const maxAmount = <?php echo $wallet['balance']; ?>;
            const btn = document.getElementById('withdrawBtn');
            
            if (amount < 10 || amount > maxAmount) {
                this.style.borderColor = '#dc2626';
                btn.disabled = true;
            } else {
                this.style.borderColor = '#0ea5e9';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
