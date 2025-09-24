<?php
session_start();
require_once 'db.php';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    $response = ['success' => false, 'message' => ''];
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all required fields';
    } elseif (!validateEmail($email)) {
        $response['message'] = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $response['message'] = 'Passwords do not match';
    } elseif (!$terms) {
        $response['message'] = 'Please accept the terms and conditions';
    } else {
        try {
            // Check if email already exists (case insensitive)
            $existingUser = $db->fetch("SELECT id FROM users WHERE LOWER(email) = LOWER(?)", [$email]);
            
            if ($existingUser) {
                $response['message'] = 'An account with this email already exists';
            } else {
                // Create user
                $db->beginTransaction();
                
                $userId = generateUserId();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $verificationToken = generateSecureToken();
                
                // Generate unique username
                $baseUsername = strtolower($firstName . $lastName);
                $username = $baseUsername . rand(1000, 9999);
                
                // Ensure username is unique
                while ($db->fetch("SELECT id FROM users WHERE username = ?", [$username])) {
                    $username = $baseUsername . rand(1000, 9999);
                }
                
                // Insert user with 'active' status for immediate login capability
                $sql = "INSERT INTO users (user_id, first_name, last_name, email, username, password_hash, account_status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";
                
                $db->query($sql, [$userId, $firstName, $lastName, $email, $username, $passwordHash]);
                
                // Create wallet
                $walletId = generateWalletId();
                $walletSql = "INSERT INTO wallets (user_id, wallet_id, balance) VALUES (?, ?, 0.00)";
                $db->query($walletSql, [$userId, $walletId]);
                
                // Create email verification token (optional for future use)
                $tokenSql = "INSERT INTO email_verifications (user_id, token, expires_at) 
                            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
                $db->query($tokenSql, [$userId, $verificationToken]);
                
                $db->commit();
                
                // Log security event
                try {
                    logSecurityEvent($userId, 'signup');
                } catch (Exception $e) {
                    // Log failed but don't break signup
                }
                
                $response['success'] = true;
                $response['message'] = 'Account created successfully! You can now login with your credentials.';
            }
        } catch (Exception $e) {
            $db->rollback();
            $response['message'] = 'Registration failed. Please try again.';
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
    <title>Sign Up - PayClone</title>
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

        .signup-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 700px;
        }

        .signup-visual {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .signup-visual::before {
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

        .signup-form {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: #dc2626; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #059669; width: 100%; }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #0ea5e9;
            margin-top: 2px;
        }

        .checkbox-label {
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .checkbox-label a {
            color: #0ea5e9;
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
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
            .signup-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .signup-visual {
                display: none;
            }

            .signup-form {
                padding: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
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

    <div class="signup-container">
        <div class="signup-visual">
            <div class="visual-content">
                <h2>Join PayClone</h2>
                <p>Start your journey with secure digital payments. Create your account in minutes and experience the future of finance.</p>
                <div style="font-size: 4rem; margin-top: 2rem;">🚀</div>
            </div>
        </div>

        <div class="signup-form">
            <a href="index.php" class="logo">PayClone</a>
            
            <h1 class="form-title">Create Account</h1>
            <p class="form-subtitle">Join millions who trust PayClone for secure payments</p>

            <div id="alert-container"></div>

            <form id="signupForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText">Enter a password</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" class="checkbox" required>
                    <label for="terms" class="checkbox-label">
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="signupBtn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    Create Account
                </button>
            </form>

            <div class="divider">
                <span>Already have an account?</span>
            </div>

            <button class="btn btn-secondary" onclick="redirectTo('login.php')">
                Sign In
            </button>
        </div>
    </div>

    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let text = 'Weak';
            let className = 'strength-weak';
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    text = 'Weak';
                    className = 'strength-weak';
                    break;
                case 2:
                case 3:
                    text = 'Fair';
                    className = 'strength-fair';
                    break;
                case 4:
                    text = 'Good';
                    className = 'strength-good';
                    break;
                case 5:
                    text = 'Strong';
                    className = 'strength-strong';
                    break;
            }
            
            strengthFill.className = 'strength-fill ' + className;
            strengthText.textContent = text;
        });

        // Form submission
        document.getElementById('signupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('signupBtn');
            const spinner = document.getElementById('loadingSpinner');
            const alertContainer = document.getElementById('alert-container');
            
            // Show loading state
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            alertContainer.innerHTML = '';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('signup.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
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

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    </script>
</body>
</html>
