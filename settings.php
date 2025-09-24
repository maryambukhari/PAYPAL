<?php
session_start();
$user = [
    'first_name' => 'Demo',
    'last_name' => 'User', 
    'email' => 'demo@example.com',
    'phone' => '+1234567890'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PayClone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            /* Made container more visible with explicit positioning */
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 2px solid #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .nav-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        
        .nav-tab {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-tab.active {
            background: #2980b9;
        }
        
        .section {
            display: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .section.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Settings</h1>
            <p>Manage your account information and preferences</p>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showSection('profile')">Profile</button>
            <button class="nav-tab" onclick="showSection('security')">Security</button>
            <button class="nav-tab" onclick="showSection('preferences')">Preferences</button>
        </div>

        <!-- Profile Section -->
        <div id="profile" class="section active">
            <h2>Profile Information</h2>
            <form method="POST">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo $user['phone']; ?>">
                </div>
                
                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>

        <!-- Security Section -->
        <div id="security" class="section">
            <h2>Password & Security</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn">Change Password</button>
            </form>
        </div>

        <!-- Preferences Section -->
        <div id="preferences" class="section">
            <h2>Notification Preferences</h2>
            <form method="POST">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_notifications" checked> Email Notifications
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="transaction_alerts" checked> Transaction Alerts
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="security_alerts" checked> Security Alerts
                    </label>
                </div>
                
                <button type="submit" class="btn">Save Preferences</button>
            </form>
        </div>

        <div class="back-link">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>

    <script>
        console.log('[v0] Settings page loaded');
        
        function showSection(sectionId) {
            console.log('[v0] Showing section:', sectionId);
            
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Remove active from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
