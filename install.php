<?php
// install.php - Installation and Setup Script
session_start();

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('Application is already installed. Delete config/installed.lock to reinstall.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database Configuration
            $_SESSION['db_config'] = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'dbname' => $_POST['db_name'] ?? 'macta_framework',
                'username' => $_POST['db_username'] ?? 'root',
                'password' => $_POST['db_password'] ?? ''
            ];
            
            // Test database connection
            try {
                $pdo = new PDO(
                    "mysql:host={$_SESSION['db_config']['host']};dbname={$_SESSION['db_config']['dbname']}",
                    $_SESSION['db_config']['username'],
                    $_SESSION['db_config']['password']
                );
                
                // Create tables
                require_once 'config/database.php';
                createTables($pdo);
                
                header('Location: install.php?step=2');
                exit;
            } catch (PDOException $e) {
                $error = "Database connection failed: " . $e->getMessage();
            }
            break;
            
        case 2:
            // Admin User Creation
            try {
                $pdo = new PDO(
                    "mysql:host={$_SESSION['db_config']['host']};dbname={$_SESSION['db_config']['dbname']}",
                    $_SESSION['db_config']['username'],
                    $_SESSION['db_config']['password']
                );
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                $hashedPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
                
                $stmt->execute([
                    $_POST['admin_username'],
                    $_POST['admin_email'],
                    $hashedPassword
                ]);
                
                // Write configuration file
                $configContent = "<?php\n";
                $configContent .= "// Auto-generated configuration file\n";
                $configContent .= "define('DB_HOST', '{$_SESSION['db_config']['host']}');\n";
                $configContent .= "define('DB_NAME', '{$_SESSION['db_config']['dbname']}');\n";
                $configContent .= "define('DB_USER', '{$_SESSION['db_config']['username']}');\n";
                $configContent .= "define('DB_PASS', '{$_SESSION['db_config']['password']}');\n";
                $configContent .= "define('APP_NAME', 'MACTA Framework');\n";
                $configContent .= "define('APP_VERSION', '1.0.0');\n";
                $configContent .= "?>";
                
                file_put_contents('config/config.php', $configContent);
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                header('Location: install.php?step=3');
                exit;
            } catch (PDOException $e) {
                $error = "Admin creation failed: " . $e->getMessage();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .install-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #4CAF50;
            color: white;
        }
        .step.completed {
            background: #2196F3;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            border-color: #4CAF50;
            outline: none;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #45a049;
        }
        .error {
            background: #f44336;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1>MACTA Framework</h1>
            <p>Modeling - Analysis - Customization - Training - Assessment</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h2>Database Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" value="macta_framework" required>
                </div>
                <div class="form-group">
                    <label for="db_username">Database Username:</label>
                    <input type="text" id="db_username" name="db_username" value="root" required>
                </div>
                <div class="form-group">
                    <label for="db_password">Database Password:</label>
                    <input type="password" id="db_password" name="db_password">
                </div>
                <button type="submit" class="btn">Test Connection & Create Tables</button>
            </form>

        <?php elseif ($step == 2): ?>
            <h2>Create Admin Account</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="admin_username">Admin Username:</label>
                    <input type="text" id="admin_username" name="admin_username" required>
                </div>
                <div class="form-group">
                    <label for="admin_email">Admin Email:</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">Admin Password:</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                <button type="submit" class="btn">Create Admin & Complete Installation</button>
            </form>

        <?php elseif ($step == 3): ?>
            <div class="success">
                <h2>Installation Complete!</h2>
                <p>Your MACTA Framework has been successfully installed.</p>
                <p><strong>Next steps:</strong></p>
                <ul>
                    <li>Access your application at <a href="index.php">index.php</a></li>
                    <li>Login with your admin credentials</li>
                    <li>Start building your MACTA modules</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>