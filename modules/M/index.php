<?php
// modules/M/index.php - Master Page for Enhanced MACTA Modeling Module

// Initialize variables
$processes = [];
$projects = [];
$db_error = '';

// Database connection
try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Get all processes from database
        $stmt = $pdo->prepare("
            SELECT pm.*, p.name as project_name 
            FROM process_models pm 
            LEFT JOIN projects p ON pm.project_id = p.id 
            ORDER BY pm.updated_at DESC
        ");
        $stmt->execute();
        $processes = $stmt->fetchAll();
        
        // Get all projects for dropdown
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Master DB Error: " . $e->getMessage());
}

// Get the current tab from URL parameter, default to 'design'
$currentTab = $_GET['tab'] ?? 'design';

// Valid tabs
$validTabs = ['design', 'view', 'resources', 'simulation', 'analysis'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'design';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - Enhanced Modeling Module</title>

    <!-- MACTA Brand Colors and Enhanced Styling -->
    <style>
        :root {
            /* HTT Brand Colors */
            --htt-blue: #1E88E5;
            --htt-dark-blue: #1565C0;
            --htt-light-blue: #42A5F5;
            --htt-gray: #666666;
            --htt-dark-gray: #424242;
            --htt-light-gray: #f5f5f5;
            
            /* MACTA Brand Colors */
            --macta-orange: #ff7b54;
            --macta-red: #d63031;
            --macta-teal: #00b894;
            --macta-yellow: #fdcb6e;
            --macta-green: #6c5ce7;
            --macta-dark: #2d3436;
            --macta-light: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--htt-light-blue) 0%, var(--htt-blue) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--htt-blue) 0%, var(--htt-dark-blue) 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .macta-logo {
            width: 50px;
            height: 50px;
            background: white;
            color: var(--htt-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-secondary {
            background: var(--macta-teal);
            color: white;
        }

        .btn-secondary:hover {
            background: #00a085;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,184,148,0.3);
        }

        /* Enhanced Tab Navigation */
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid var(--macta-light);
        }

        .nav-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            border: none;
            transition: all 0.3s ease;
            color: var(--macta-dark);
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-right: 1px solid var(--macta-light);
            text-decoration: none;
        }

        .nav-tab:last-child {
            border-right: none;
        }

        .nav-tab.active {
            background: white;
            color: var(--htt-blue);
            border-bottom: 3px solid var(--htt-blue);
            font-weight: bold;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30,136,229,0.3);
        }

        .nav-tab:hover:not(.active) {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .tab-icon {
            font-size: 20px;
        }

        .tab-content {
            padding: 30px;
            min-height: 70vh;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
            font-size: 18px;
            color: var(--macta-orange);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }

            .nav-tab {
                border-right: none !important;
                border-bottom: 1px solid var(--macta-light);
                padding: 15px;
            }

            .nav-tab:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <div class="macta-logo">M</div>
                MACTA Framework - Enhanced Modeling Module
            </h1>
            <div>
                <a href="../../index.php" class="btn btn-secondary">
                    <span>←</span> Back to Framework
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="?tab=design" class="nav-tab <?= $currentTab === 'design' ? 'active' : '' ?>">
                <span class="tab-icon">🎨</span>
                <span>BPMN Design</span>
            </a>
            <a href="?tab=view" class="nav-tab <?= $currentTab === 'view' ? 'active' : '' ?>">
                <span class="tab-icon">👁️</span>
                <span>BPMN View</span>
            </a>
            <a href="?tab=resources" class="nav-tab <?= $currentTab === 'resources' ? 'active' : '' ?>">
                <span class="tab-icon">👥</span>
                <span>Resource Assignment</span>
            </a>
            <a href="?tab=simulation" class="nav-tab <?= $currentTab === 'simulation' ? 'active' : '' ?>">
                <span class="tab-icon">⚡</span>
                <span>Simulation</span>
            </a>
            <a href="?tab=analysis" class="nav-tab <?= $currentTab === 'analysis' ? 'active' : '' ?>">
                <span class="tab-icon">📊</span>
                <span>Path Analysis</span>
            </a>
        </div>

        <!-- Tab Content Area -->
        <div class="tab-content" id="tab-content-area">
            <?php if (!empty($db_error)): ?>
                <div style="background: #ffebee; border: 1px solid #e57373; border-radius: 8px; padding: 20px; margin-bottom: 20px; color: #c62828;">
                    <strong>Database Error:</strong> <?= htmlspecialchars($db_error) ?>
                </div>
            <?php endif; ?>

            <div class="loading">Loading <?= ucfirst($currentTab) ?> module...</div>
        </div>
    </div>

    <script>
        // Store PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const projects = <?= json_encode($projects) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        const currentTab = '<?= $currentTab ?>';
        
        // Load the appropriate sub-page content
        document.addEventListener('DOMContentLoaded', function() {
            loadTabContent(currentTab);
        });

        function loadTabContent(tabName) {
            const contentArea = document.getElementById('tab-content-area');
            
            // Show loading
            contentArea.innerHTML = `<div class="loading">Loading ${tabName.charAt(0).toUpperCase() + tabName.slice(1)} module...</div>`;
            
            // Load the sub-page content via fetch
            fetch(`${tabName}.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    contentArea.innerHTML = html;
                    
                    // Initialize any scripts needed for the loaded content
                    initializeTabScripts(tabName);
                })
                .catch(error => {
                    console.error('Error loading tab content:', error);
                    contentArea.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #d63031;">
                            <h3>❌ Failed to load ${tabName} module</h3>
                            <p>Error: ${error.message}</p>
                            <p>Please check if the file <code>${tabName}.php</code> exists in the modules/M directory.</p>
                        </div>
                    `;
                });
        }

        function initializeTabScripts(tabName) {
            // This function will be called after content is loaded
            // Each sub-page will have its own initialization script
            
            console.log(`Initializing scripts for ${tabName} tab`);
            
            // Dispatch a custom event that sub-pages can listen to
            const event = new CustomEvent('tabContentLoaded', {
                detail: { 
                    tabName: tabName,
                    processes: processes,
                    projects: projects,
                    dbError: dbError
                }
            });
            document.dispatchEvent(event);
        }

        console.log('🎯 MACTA Enhanced Modeling Master Page Initialized');
        console.log('📋 Current Tab:', currentTab);
        console.log('📊 Available Processes:', processes.length);
    </script>
</body>
</html>