<?php
session_start();
require_once 'db.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $response = ['success' => false, 'message' => ''];
    
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields';
    } else {
        try {
            $sql = "SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND account_status IN ('active', 'pending')";
            $user = $db->fetch($sql, [$email]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $response['message'] = 'Account temporarily locked. Please try again later.';
                } else {
                    if ($user['account_status'] === 'pending') {
                        $db->execute("UPDATE users SET account_status = 'active' WHERE id = ?", [$user['id']]);
                    }
                    
                    // Reset failed attempts and update last login
                    $db->execute("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?", [$user['id']]);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    try {
                        logSecurityEvent($user['user_id'], 'login');
                    } catch (Exception $e) {
                        // Log security event failed, but don't break login
                    }
                    
                    $response['success'] = true;
                    $response['redirect'] = 'dashboard.php';
                }
            } else {
                // Increment failed attempts
                if ($user) {
                    $attempts = $user['failed_login_attempts'] + 1;
                    $lockUntil = null;
                    
                    if ($attempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    }
                    
                    $db->execute("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?", 
                        [$attempts, $lockUntil, $user['id']]);
                    
                    try {
                        logSecurityEvent($user['user_id'], 'failed_login');
                    } catch (Exception $e) {
                        // Log security event failed, but don't break the process
                    }
                }
                
                $response['message'] = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $response['message'] = 'Login failed. Please try again.';
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
    <title>Login - PayClone</title>
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

        .login-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .login-form {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-visual {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-visual::before {
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

        .form-input.error {
            border-color: #dc2626;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #0ea5e9;
        }

        .checkbox-label {
            color: #475569;
            font-size: 0.9rem;
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

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: #475569;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #d1d5db;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
        }

        .form-links {
            text-align: center;
            margin-top: 1rem;
        }

        .form-links a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
        }

        .form-links a:hover {
            text-decoration: underline;
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

        .back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: #164e63;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-home:hover {
            color: #0ea5e9;
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .login-visual {
                display: none;
            }

            .login-form {
                padding: 2rem;
            }

            .back-home {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        ← Back to Home
    </a>

    <div class="login-container">
        <div class="login-form">
            <a href="index.php" class="logo">PayClone</a>
            
            <h1 class="form-title">Welcome Back</h1>
            <p class="form-subtitle">Sign in to your account to continue</p>

            <div id="alert-container"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" class="checkbox">
                    <label for="remember" class="checkbox-label">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    Sign In
                </button>
            </form>

            <div class="form-links">
                <a href="forgot-password.php">Forgot your password?</a>
            </div>

            <div class="divider">
                <span>Don't have an account?</span>
            </div>

            <button class="btn btn-secondary" onclick="redirectTo('signup.php')">
                Create Account
            </button>
        </div>

        <div class="login-visual">
            <div class="visual-content">
                <h2>Secure Access</h2>
                <p>Your financial security is our top priority. Login with confidence knowing your data is protected.</p>
                <div style="font-size: 4rem; margin-top: 2rem;">🔐</div>
            </div>
        </div>
    </div>

    <script>
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            const spinner = document.getElementById('loadingSpinner');
            const alertContainer = document.getElementById('alert-container');
            
            // Show loading state
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            alertContainer.innerHTML = '';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
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

        // Input validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            input.addEventListener('input', function() {
                this.classList.remove('error');
            });
        });
    </script>
</body>
</html>
