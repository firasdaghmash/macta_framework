<?php
// modules/M/index.php - Fixed Master Page for MACTA Modeling Module

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

// Handle AJAX requests for tab content
if (isset($_GET['ajax']) && $_GET['ajax'] === 'load_tab') {
    $tabToLoad = $_GET['tab_name'] ?? 'design';
    
    if (!in_array($tabToLoad, $validTabs)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid tab name']);
        exit;
    }
    
    // Check if the tab file exists
    $tabFile = __DIR__ . '/' . $tabToLoad . '.php';
    if (!file_exists($tabFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Tab file not found: ' . $tabToLoad . '.php']);
        exit;
    }
    
    // Capture the output of the tab file
    ob_start();
    include $tabFile;
    $tabContent = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'content' => $tabContent,
        'tab_name' => $tabToLoad,
        'processes' => $processes,
        'projects' => $projects,
        'db_error' => $db_error
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - Modeling Module</title>

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
            flex-direction: column;
            gap: 15px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--htt-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #ffebee;
            border: 1px solid #e57373;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            color: #c62828;
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
                MACTA Framework - Modeling Module
            </h1>
            <div>
                <a href="../../index.php" class="btn btn-secondary">
                    <span>←</span> Back to Framework
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab <?= $currentTab === 'design' ? 'active' : '' ?>" onclick="loadTab('design')" data-tab="design">
                <span class="tab-icon">🎨</span>
                <span>BPMN Design</span>
            </button>
            <button class="nav-tab <?= $currentTab === 'view' ? 'active' : '' ?>" onclick="loadTab('view')" data-tab="view">
                <span class="tab-icon">👁️</span>
                <span>BPMN View</span>
            </button>
            <button class="nav-tab <?= $currentTab === 'resources' ? 'active' : '' ?>" onclick="loadTab('resources')" data-tab="resources">
                <span class="tab-icon">👥</span>
                <span>Resource Assignment</span>
            </button>
            <button class="nav-tab <?= $currentTab === 'simulation' ? 'active' : '' ?>" onclick="loadTab('simulation')" data-tab="simulation">
                <span class="tab-icon">⚡</span>
                <span>Simulation</span>
            </button>
            <button class="nav-tab <?= $currentTab === 'analysis' ? 'active' : '' ?>" onclick="loadTab('analysis')" data-tab="analysis">
                <span class="tab-icon">📊</span>
                <span>Path Analysis</span>
            </button>
        </div>

        <!-- Tab Content Area -->
        <div class="tab-content" id="tab-content-area">
            <?php if (!empty($db_error)): ?>
                <div class="error-message">
                    <strong>Database Error:</strong> <?= htmlspecialchars($db_error) ?>
                </div>
            <?php endif; ?>

            <div class="loading">
                <div class="loading-spinner"></div>
                <div>Loading <?= ucfirst($currentTab) ?> module...</div>
            </div>
        </div>
    </div>

    <script>
        // Store PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const projects = <?= json_encode($projects) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        let currentTab = '<?= $currentTab ?>';
        
        // Load the appropriate sub-page content
        document.addEventListener('DOMContentLoaded', function() {
            console.log('MACTA Modeling Master Page Initialized');
            console.log('Current Tab:', currentTab);
            console.log('Available Processes:', processes.length);
            
            // Load initial tab content
            loadTab(currentTab);
        });

        // Load tab content via AJAX
        function loadTab(tabName) {
            if (!tabName) return;
            
            console.log('Loading tab:', tabName);
            
            const contentArea = document.getElementById('tab-content-area');
            const tabs = document.querySelectorAll('.nav-tab');
            
            // Update active tab styling
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.tab === tabName) {
                    tab.classList.add('active');
                }
            });
            
            // Show loading
            contentArea.innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <div>Loading ${tabName.charAt(0).toUpperCase() + tabName.slice(1)} module...</div>
                </div>
            `;
            
            // Load the tab content via AJAX
            fetch(`?ajax=load_tab&tab_name=${tabName}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        contentArea.innerHTML = data.content;
                        currentTab = tabName;
                        
                        // Update URL without page reload
                        const newUrl = new URL(window.location);
                        newUrl.searchParams.set('tab', tabName);
                        window.history.pushState({tab: tabName}, '', newUrl);
                        
                        // Initialize scripts for the loaded content
                        initializeTabScripts(tabName, data);
                    } else {
                        throw new Error(data.error || 'Failed to load tab content');
                    }
                })
                .catch(error => {
                    console.error('Error loading tab content:', error);
                    contentArea.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #d63031;">
                            <h3>⚠️ Failed to load ${tabName} module</h3>
                            <p><strong>Error:</strong> ${error.message}</p>
                            <p>Please check if the file <code>${tabName}.php</code> exists and is accessible.</p>
                            <button class="btn btn-secondary" onclick="loadTab('${tabName}')">
                                <span>🔄</span> Retry
                            </button>
                        </div>
                    `;
                });
        }

        // Initialize scripts for loaded tab content
        function initializeTabScripts(tabName, data) {
            console.log(`Initializing scripts for ${tabName} tab`);
            
            // First, dispatch the event so sub-pages know content is loaded
            const event = new CustomEvent('tabContentLoaded', {
                detail: { 
                    tabName: tabName,
                    processes: data.processes || processes,
                    projects: data.projects || projects,
                    dbError: data.db_error || dbError
                }
            });
            document.dispatchEvent(event);
            
            // Wait a bit for DOM to be ready, then execute scripts
            setTimeout(() => {
                // Execute any inline scripts in the loaded content
                const scripts = document.querySelectorAll('#tab-content-area script:not([src])');
                scripts.forEach(script => {
                    try {
                        // Create a new script element and execute it
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                        document.body.removeChild(newScript);
                    } catch (error) {
                        console.error('Error executing script:', error);
                    }
                });
                
                // Handle external scripts (like BPMN.js)
                const externalScripts = document.querySelectorAll('#tab-content-area script[src]');
                let scriptsLoaded = 0;
                const totalScripts = externalScripts.length;
                
                if (totalScripts === 0) {
                    // No external scripts, trigger final initialization
                    finalizeTabInitialization(tabName, data);
                } else {
                    externalScripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.src = script.src;
                        newScript.onload = function() {
                            scriptsLoaded++;
                            console.log(`External script loaded: ${script.src} (${scriptsLoaded}/${totalScripts})`);
                            
                            if (scriptsLoaded === totalScripts) {
                                // All external scripts loaded, trigger final initialization
                                setTimeout(() => {
                                    finalizeTabInitialization(tabName, data);
                                }, 200);
                            }
                        };
                        newScript.onerror = function() {
                            console.error(`Failed to load external script: ${script.src}`);
                            scriptsLoaded++;
                            if (scriptsLoaded === totalScripts) {
                                setTimeout(() => {
                                    finalizeTabInitialization(tabName, data);
                                }, 200);
                            }
                        };
                        document.head.appendChild(newScript);
                    });
                }
                
            }, 100);
            
            console.log(`${tabName} tab initialization started`);
        }
        
        // Finalize tab initialization after all scripts are loaded
        function finalizeTabInitialization(tabName, data) {
            console.log(`Finalizing initialization for ${tabName} tab`);
            
            // Trigger DOMContentLoaded-like event for the loaded content
            const finalEvent = new CustomEvent('tabScriptsReady', {
                detail: { 
                    tabName: tabName,
                    processes: data.processes || processes,
                    projects: data.projects || projects,
                    dbError: data.db_error || dbError
                }
            });
            document.dispatchEvent(finalEvent);
            
            // Tab-specific initialization
            if (tabName === 'design') {
                // Force initialization of BPMN designer if it exists
                setTimeout(() => {
                    if (typeof initializeBpmnDesigner === 'function') {
                        console.log('Manually initializing BPMN designer...');
                        initializeBpmnDesigner();
                    }
                }, 500);
            } else if (tabName === 'view') {
                // Force initialization of BPMN viewer if it exists
                setTimeout(() => {
                    if (typeof initializeBpmnViewer === 'function') {
                        console.log('Manually initializing BPMN viewer...');
                        initializeBpmnViewer();
                    }
                }, 500);
            }
            
            console.log(`${tabName} tab fully initialized`);
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.tab) {
                loadTab(event.state.tab);
            }
        });

        // Global function to switch tabs (can be called from sub-pages)
        window.switchToTab = function(tabName) {
            loadTab(tabName);
        };
    </script>
</body>
</html>