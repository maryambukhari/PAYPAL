<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle add funds form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $description = trim($_POST['description'] ?? 'Wallet deposit');
    
    $response = ['success' => false, 'message' => ''];
    
    if ($amount <= 0) {
        $response['message'] = 'Please enter a valid amount';
    } elseif ($amount < 1) {
        $response['message'] = 'Minimum deposit amount is $1.00';
    } elseif ($amount > 10000) {
        $response['message'] = 'Maximum deposit amount is $10,000.00';
    } elseif (empty($paymentMethod)) {
        $response['message'] = 'Please select a payment method';
    } else {
        try {
            $db->beginTransaction();
            
            // Create transaction record
            $transactionId = generateTransactionId();
            $sql = "INSERT INTO transactions (transaction_id, receiver_id, transaction_type, amount, currency, net_amount, status, description, payment_method) 
                    VALUES (?, ?, 'deposit', ?, 'USD', ?, 'completed', ?, ?)";
            
            $db->query($sql, [$transactionId, $userId, $amount, $amount, $description, $paymentMethod]);
            
            // Update wallet balance
            $db->execute("UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?", [$amount, $userId]);
            
            // Log transaction history
            $db->query("INSERT INTO transaction_history (transaction_id, user_id, action, description) VALUES (?, ?, 'completed', 'Funds added successfully')", 
                [$transactionId, $userId]);
            
            // Create notification
            $db->query("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'transaction', 'Funds Added', ?)", 
                [$userId, "Successfully added " . formatCurrency($amount) . " to your wallet"]);
            
            $db->commit();
            
            $response['success'] = true;
            $response['message'] = 'Funds added successfully!';
            $response['redirect'] = 'wallet.php';
            
        } catch (Exception $e) {
            $db->rollback();
            $response['message'] = 'Failed to add funds. Please try again.';
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
    <title>Add Funds - PayClone</title>
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

        .add-funds-container {
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
            right: -50%;
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

        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .quick-amount {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .quick-amount:hover, .quick-amount.active {
            border-color: #0ea5e9;
            background: #f0f9ff;
            color: #0ea5e9;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover, .payment-method.selected {
            border-color: #0ea5e9;
            background: #f0f9ff;
        }

        .payment-method input[type="radio"] {
            margin-right: 1rem;
            accent-color: #0ea5e9;
        }

        .payment-icon {
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

        .payment-details h4 {
            color: #164e63;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .payment-details p {
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
