<?php
require_once 'db.php';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    $response = ['success' => false, 'message' => ''];
    
    if (empty($email)) {
        $response['message'] = 'Please enter your email address';
    } elseif (!validateEmail($email)) {
        $response['message'] = 'Please enter a valid email address';
    } else {
        try {
            // Check if user exists
            $user = $db->fetch("SELECT user_id, email FROM users WHERE email = ?", [$email]);
            
            if ($user) {
                // Generate reset token
                $token = generateSecureToken();
                
                // Delete existing tokens
                $db->execute("DELETE FROM password_resets WHERE user_id = ?", [$user['user_id']]);
                
                // Insert new token
                $sql = "INSERT INTO password_resets (user_id, token, expires_at) 
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
                $db->query($sql, [$user['user_id'], $token]);
                
                // Log security event
                logSecurityEvent($user['user_id'], 'password_reset_requested');
                
                $response['success'] = true;
                $response['message'] = 'Password reset instructions have been sent to your email address.';
            } else {
                // Don't reveal if email exists or not for security
                $response['success'] = true;
                $response['message'] = 'If an account with this email exists, password reset instructions have been sent.';
            }
        } catch (Exception $e) {
            $response['message'] = 'An error occurred. Please try again.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PayClone</title>
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

        .reset-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #164e63;
            text-decoration: none;
            margin-bottom: 2rem;
            display: block;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .reset-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
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
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
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

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
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

        .back-link {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <a href="index.php" class="logo">PayClone</a>
        
        <div class="reset-icon">🔑</div>
        
        <h1 class="form-title">Forgot Password?</h1>
        <p class="form-subtitle">
            No worries! Enter your email address and we'll send you instructions to reset your password.
        </p>

        <div id="alert-container"></div>

        <form id="resetForm">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" required 
                       placeholder="Enter your email address">
            </div>

            <button type="submit" class="btn btn-primary" id="resetBtn">
                <span class="loading-spinner" id="loadingSpinner"></span>
                Send Reset Instructions
            </button>
        </form>

        <button class="btn btn-secondary" onclick="redirectTo('login.php')">
            Back to Login
        </button>

        <a href="signup.php" class="back-link">
            Don't have an account? Sign up
        </a>
    </div>

    <script>
        // Form submission
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('resetBtn');
            const spinner = document.getElementById('loadingSpinner');
            const alertContainer = document.getElementById('alert-container');
            
            // Show loading state
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            alertContainer.innerHTML = '';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('forgot-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    document.getElementById('resetForm').style.display = 'none';
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
    </script>
</body>
</html>
