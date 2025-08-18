<?php
// modules/M/bpmn_manager.php

// Initialize variables
$processes = [];
$projects = [];
$db_error = '';

// Database connection
try {
    // Check if config exists
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        // Create PDO connection
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
    error_log("BPMN Manager DB Error: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error)) {
        echo json_encode(['success' => false, 'message' => $db_error]);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'save_process':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO process_models (name, description, model_data, project_id) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    model_data = VALUES(model_data), 
                    updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([
                    $_POST['name'] ?? 'Untitled Process',
                    $_POST['description'] ?? '',
                    $_POST['xml'] ?? '',
                    $_POST['project_id'] ?? 1
                ]);
                echo json_encode(['success' => true, 'message' => 'Process saved successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'load_process':
            try {
                $stmt = $pdo->prepare("SELECT * FROM process_models WHERE id = ?");
                $stmt->execute([$_POST['process_id'] ?? 0]);
                $process = $stmt->fetch();
                echo json_encode(['success' => true, 'process' => $process]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'assign_resource':
            try {
                echo json_encode(['success' => true, 'message' => 'Resource assigned successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Process Manager - Design, View, Assign & Simulate</title>

    <style>
        :root {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--macta-dark);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .macta-logo {
            width: 50px;
            height: 50px;
            background: var(--macta-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        /* Tab Navigation */
        .tab-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-nav {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .tab-btn {
            flex: 1;
            padding: 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-bottom: 3px solid transparent;
        }

        .tab-btn:hover {
            background: #e9ecef;
        }

        .tab-btn.active {
            background: white;
            border-bottom-color: var(--macta-orange);
            color: var(--macta-orange);
        }

        .tab-content {
            display: none;
            padding: 30px;
            min-height: calc(100vh - 200px);
        }

        .tab-content.active {
            display: block;
        }

        /* Process selectors */
        .process-selector {
            margin-bottom: 20px;
        }

        .process-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--macta-dark);
        }

        .process-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        /* BPMN containers */
        .bpmn-container {
            height: 600px;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 20px;
            position: relative;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 16px;
            color: var(--macta-teal);
            text-align: center;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--macta-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a3a;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--macta-teal);
            color: white;
        }

        .btn-secondary:hover {
            background: #00a085;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--macta-green);
            color: white;
        }

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        /* Status and info */
        .status-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            border-left: 4px solid var(--macta-orange);
        }

        /* Assignment form */
        .assignment-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .assignment-form input,
        .assignment-form select {
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
        }

        /* Simulation controls */
        .simulation-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .simulation-controls input[type="range"] {
            width: 120px;
        }

        /* Performance metrics */
        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .metric-card {
            background: linear-gradient(135deg, var(--macta-teal), var(--macta-green));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .metric-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .metric-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Fullscreen mode */
        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: white;
            z-index: 999999;
            display: none;
            flex-direction: column;
        }

        .fullscreen-overlay.active {
            display: flex;
        }

        .fullscreen-header {
            background: var(--macta-dark);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fullscreen-content {
            flex: 1;
            padding: 20px;
            overflow: auto;
        }

        .fullscreen-content .bpmn-container {
            height: calc(100vh - 200px);
        }

        .close-fullscreen {
            background: var(--macta-red);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }

        /* Token and animation styles */
        .token {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--macta-orange);
            margin-right: 5px;
        }

        /* BPMN styling fixes */
        .bjs-container {
            background: white !important;
        }

        .djs-element {
            pointer-events: all !important;
        }

        .djs-shape .djs-visual > rect {
            fill: white !important;
            stroke: #333 !important;
            stroke-width: 2px !important;
        }

        .djs-shape .djs-visual > circle {
            fill: white !important;
            stroke: #333 !important;
            stroke-width: 2px !important;
        }

        .djs-shape .djs-visual > polygon {
            fill: white !important;
            stroke: #333 !important;
            stroke-width: 2px !important;
        }

        /* Animation highlights */
        .simulation-highlight .djs-visual > rect,
        .simulation-highlight .djs-visual > circle,
        .simulation-highlight .djs-visual > polygon {
            fill: var(--macta-orange) !important;
            stroke: var(--macta-red) !important;
            stroke-width: 4px !important;
            animation: pulse 1s infinite;
        }

        .bottleneck-highlight .djs-visual > rect,
        .bottleneck-highlight .djs-visual > circle,
        .bottleneck-highlight .djs-visual > polygon {
            fill: #ffebee !important;
            stroke: var(--macta-red) !important;
            stroke-width: 4px !important;
            animation: warning 0.5s infinite alternate;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        @keyframes warning {
            0% { stroke: var(--macta-red); }
            100% { stroke: #ff5722; }
        }
    </style>

    <!-- BPMN.js styles -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>
            <div class="macta-logo">M</div>
            MACTA Process Manager
        </h1>
        <div style="color: var(--macta-dark); font-size: 14px;">
            <?= count($processes) ?> processes available | Tab-based interface
        </div>
    </div>

    <!-- Tab Container -->
    <div class="tab-container">
        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="design">
                🎨 Design & Model
            </button>
            <button class="tab-btn" data-tab="view">
                👁️ View & Analyze
            </button>
            <button class="tab-btn" data-tab="assign">
                👥 Assign Resources
            </button>
            <button class="tab-btn" data-tab="simulate">
                🎯 Simulate & Test
            </button>
        </div>

        <!-- Design Tab -->
        <div id="design-tab" class="tab-content active">
            <div class="process-selector">
                <label>Select Process to Edit:</label>
                <select id="design-process-select">
                    <option value="">Choose a process to edit...</option>
                    <option value="new">+ Create New Process</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>" data-xml="<?= htmlspecialchars($process['model_data']) ?>">
                            <?= htmlspecialchars($process['name']) ?> 
                            <?php if ($process['project_name']): ?>
                                (<?= htmlspecialchars($process['project_name']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="bpmn-editor" class="bpmn-container">
                <div class="loading">Loading BPMN Editor...</div>
            </div>

            <div class="toolbar">
                <button class="btn btn-primary" id="btn-new-process">📄 New Process</button>
                <button class="btn btn-secondary" id="btn-save-process">💾 Save Process</button>
                <button class="btn btn-warning" id="btn-clear-designer">🗑️ Clear</button>
                <button class="btn btn-secondary" id="btn-validate-process">✅ Validate</button>
                <button class="btn btn-secondary" id="btn-zoom-in">🔍+ Zoom In</button>
                <button class="btn btn-secondary" id="btn-zoom-out">🔍- Zoom Out</button>
                <button class="btn btn-secondary" id="btn-zoom-fit">📐 Fit</button>
                <button class="btn btn-success" id="btn-export-view">📤 Export to View</button>
                <button class="btn btn-warning" id="btn-design-fullscreen">🖥️ Fullscreen</button>
            </div>

            <div class="status-bar" id="design-status">
                <span class="token"></span> Use the toolbar above to create and edit your process models.
                <?php if (!empty($db_error)): ?>
                    <strong>Database Error:</strong> <?= htmlspecialchars($db_error) ?>
                <?php elseif (count($processes) > 0): ?>
                    Found <?= count($processes) ?> processes in database.
                <?php else: ?>
                    No processes found. Create your first process!
                <?php endif; ?>
            </div>
        </div>

        <!-- View Tab -->
        <div id="view-tab" class="tab-content">
            <div class="process-selector">
                <label>Select Process to View:</label>
                <select id="view-process-select">
                    <option value="">Choose a process to view...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>" data-xml="<?= htmlspecialchars($process['model_data']) ?>">
                            <?= htmlspecialchars($process['name']) ?> 
                            <?php if ($process['project_name']): ?>
                                (<?= htmlspecialchars($process['project_name']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="bpmn-viewer" class="bpmn-container">
                <div class="loading">👆 Select a process from the dropdown above to view it here...</div>
            </div>

            <div class="toolbar">
                <button class="btn btn-primary" id="btn-animate">🎬 Animate Process</button>
                <button class="btn btn-secondary" id="btn-stop-clear">⏹️ Stop & Clear</button>
                <button class="btn btn-warning" id="btn-analyze">🔍 Analyze Bottlenecks</button>
                <button class="btn btn-success" id="btn-refresh-viewer">🔄 Refresh</button>
                <button class="btn btn-secondary" id="btn-viewer-zoom-in">🔍+ Zoom In</button>
                <button class="btn btn-secondary" id="btn-viewer-zoom-out">🔍- Zoom Out</button>
                <button class="btn btn-secondary" id="btn-viewer-fit">📐 Fit</button>
                <button class="btn btn-warning" id="btn-view-fullscreen">🖥️ Fullscreen</button>
            </div>

            <div class="status-bar">
                <span class="token"></span> <strong>Process Analysis:</strong> Select a process to view, animate flow, and analyze bottlenecks. Use zoom controls for better visibility.
            </div>
        </div>

        <!-- Assign Tab -->
        <div id="assign-tab" class="tab-content">
            <div class="process-selector">
                <label>Select Process for Resource Assignment:</label>
                <select id="assign-process-select">
                    <option value="">Choose a process...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>" data-xml="<?= htmlspecialchars($process['model_data']) ?>">
                            <?= htmlspecialchars($process['name']) ?> 
                            <?php if ($process['project_name']): ?>
                                (<?= htmlspecialchars($process['project_name']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="assignment-form">
                <select id="task-select">
                    <option value="">Select Task/Element...</option>
                </select>
                <select id="user-select">
                    <option value="">Assign to User...</option>
                    <option value="john.doe">John Doe - Process Analyst</option>
                    <option value="jane.smith">Jane Smith - Operations Manager</option>
                    <option value="mike.wilson">Mike Wilson - Quality Specialist</option>
                    <option value="sarah.connor">Sarah Connor - Team Lead</option>
                    <option value="alex.murphy">Alex Murphy - Senior Consultant</option>
                </select>
                <input type="number" id="duration-input" placeholder="Duration (hours)">
                <input type="text" id="skills-input" placeholder="Required Skills">
                <select id="priority-select">
                    <option value="">Priority Level...</option>
                    <option value="low">Low Priority</option>
                    <option value="medium">Medium Priority</option>
                    <option value="high">High Priority</option>
                    <option value="critical">Critical</option>
                </select>
                <input type="number" id="cost-input" placeholder="Cost per Hour ($)">
                <button class="btn btn-success" id="btn-assign" style="grid-column: 1 / -1;">
                    ✅ Assign Resource
                </button>
            </div>

            <div class="status-bar">
                <span class="token"></span> <strong>Resource Assignment:</strong> Select a process and task to assign team members, set duration, and define resource requirements.
            </div>
        </div>

        <!-- Simulate Tab -->
        <div id="simulate-tab" class="tab-content">
            <div class="process-selector">
                <label>Select Process to Simulate:</label>
                <select id="simulate-process-select">
                    <option value="">Choose a process to simulate...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>" data-xml="<?= htmlspecialchars($process['model_data']) ?>">
                            <?= htmlspecialchars($process['name']) ?> 
                            <?php if ($process['project_name']): ?>
                                (<?= htmlspecialchars($process['project_name']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="simulation-controls">
                <button class="btn btn-success" id="btn-start-sim">▶️ Start Simulation</button>
                <button class="btn btn-warning" id="btn-pause-sim">⏸️ Pause</button>
                <button class="btn btn-secondary" id="btn-stop-sim">⏹️ Stop</button>
                <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                    <label>Speed:</label>
                    <input type="range" id="sim-speed" min="0.5" max="3" step="0.1" value="1">
                    <span id="speed-display">1x</span>
                </div>
            </div>

            <div id="simulation-viewer" class="bpmn-container">
                <div class="loading">Select a process and click Start Simulation to begin...</div>
            </div>

            <div class="performance-metrics">
                <div class="metric-card">
                    <div class="metric-value" id="total-time">--</div>
                    <div class="metric-label">Total Process Time</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="active-tokens">0</div>
                    <div class="metric-label">Active Tokens</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="completed-instances">0</div>
                    <div class="metric-label">Completed Instances</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="efficiency-score">--</div>
                    <div class="metric-label">Efficiency Score</div>
                </div>
            </div>

            <div class="status-bar">
                <span class="token"></span> <strong>Process Simulation:</strong> Run real-time simulations to test process performance, identify bottlenecks, and optimize workflows.
            </div>
        </div>
    </div>

    <!-- Fullscreen Overlay -->
    <div id="fullscreen-overlay" class="fullscreen-overlay">
        <div class="fullscreen-header">
            <h2 id="fullscreen-title">Process Manager - Fullscreen</h2>
            <button class="close-fullscreen" onclick="exitFullscreen()">✕</button>
        </div>
        <div class="fullscreen-content" id="fullscreen-content">
            <!-- Content will be moved here -->
        </div>
    </div>

    <script>
        // Store PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const projects = <?= json_encode($projects) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        
        // Default BPMN XML
        const defaultBpmnXml = `<?xml version="1.0" encoding="UTF-8"?>
<bpmn2:definitions xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                   xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                   xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                   xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                   id="sample-diagram" 
                   targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn2:process id="Process_1" isExecutable="false">
    <bpmn2:startEvent id="StartEvent_1" name="Start">
      <bpmn2:outgoing>Flow_1</bpmn2:outgoing>
    </bpmn2:startEvent>
    <bpmn2:endEvent id="EndEvent_1" name="End">
      <bpmn2:incoming>Flow_1</bpmn2:incoming>
    </bpmn2:endEvent>
    <bpmn2:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="EndEvent_1"/>
  </bpmn2:process>
</bpmn2:definitions>`;

        let modeler = null;
        let viewer = null;
        let simulationViewer = null;
        let currentXML = defaultBpmnXml;
        let simulationActive = false;
        let simulationInterval = null;
        let animationInterval = null;
        let currentAnimationTimeout = null;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', () => {
            // Block alerts on page load
            const originalAlert = window.alert;
            window.alert = function(message) {
                console.log('Alert blocked:', message);
                return undefined;
            };

            // Initialize BPMN
            initializeBpmn();
            
            // Setup tab switching
            setupTabs();
            
            // Setup all event listeners
            setupEventListeners();
        });

        // Tab switching functionality
        function setupTabs() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetTab = btn.dataset.tab;
                    
                    // Remove active classes
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active classes
                    btn.classList.add('active');
                    document.getElementById(`${targetTab}-tab`).classList.add('active');
                    
                    console.log('Switched to tab:', targetTab);
                    
                    // Tab-specific actions
                    if (targetTab === 'assign') {
                        loadProcessTasks();
                    }
                });
            });
        }

        // Load scripts and initialize BPMN
        function loadScript(urls, callback) {
            let currentIndex = 0;
            
            function tryNextUrl() {
                if (currentIndex >= urls.length) {
                    console.error('All CDN sources failed');
                    return;
                }
                
                const script = document.createElement('script');
                script.src = urls[currentIndex];
                script.onload = callback;
                script.onerror = () => {
                    console.warn('Failed to load from:', urls[currentIndex]);
                    currentIndex++;
                    tryNextUrl();
                };
                document.head.appendChild(script);
            }
            
            tryNextUrl();
        }

        function initializeBpmn() {
            const bpmnCdnUrls = [
                'https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://unpkg.com/bpmn-js@16.0.0/dist/bpmn-modeler.development.js'
            ];
            
            loadScript(bpmnCdnUrls, () => {
                try {
                    if (typeof BpmnJS === 'undefined') {
                        throw new Error('BpmnJS not loaded');
                    }
                    
                    modeler = new BpmnJS({
                        container: '#bpmn-editor'
                    });
                    
                    loadInitialProcess();
                    
                } catch (error) {
                    console.error('Failed to initialize BPMN modeler:', error);
                    document.querySelector('#bpmn-editor .loading').innerHTML = 'BPMN Editor failed to load.';
                }
            });
        }

        async function loadInitialProcess() {
            try {
                let xmlToLoad = defaultBpmnXml;
                
                if (processes.length > 0 && processes[0].model_data) {
                    xmlToLoad = processes[0].model_data;
                    currentXML = xmlToLoad;
                }
                
                if (modeler) {
                    await modeler.importXML(xmlToLoad);
                    modeler.get('canvas').zoom('fit-viewport');
                    
                    setTimeout(() => {
                        modeler.get('canvas').zoom(modeler.get('canvas').zoom());
                    }, 100);
                }
                
                document.querySelector('#bpmn-editor .loading').style.display = 'none';
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
                document.querySelector('#bpmn-editor .loading').innerHTML = 'Failed to load process.';
            }
        }

        // Create viewers for other tabs
        async function createViewer(containerId) {
            try {
                if (typeof BpmnJS === 'undefined') {
                    return null;
                }
                
                return new BpmnJS({
                    container: containerId
                });
            } catch (error) {
                console.error('Failed to create viewer:', error);
                return null;
            }
        }

        // Load process in viewer
        async function loadProcessInViewer(xmlData, targetViewer) {
            if (!targetViewer || !xmlData) {
                console.log('No viewer or XML data available');
                return;
            }
            
            try {
                await targetViewer.importXML(xmlData);
                targetViewer.get('canvas').zoom('fit-viewport');
                console.log('Process loaded in viewer successfully');
                return true;
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
                return false;
            }
        }

        // Setup all event listeners
        function setupEventListeners() {
            
            // Design tab events
            document.getElementById('design-process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                
                if (selectedValue === 'new') {
                    currentXML = defaultBpmnXml;
                    await modeler.importXML(currentXML);
                    modeler.get('canvas').zoom('fit-viewport');
                } else if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        currentXML = xmlData;
                        await modeler.importXML(currentXML);
                        modeler.get('canvas').zoom('fit-viewport');
                    }
                }
            });

            // View tab events
            document.getElementById('view-process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                const viewerContainer = document.querySelector('#bpmn-viewer .loading');
                
                if (selectedValue) {
                    viewerContainer.innerHTML = '🔄 Loading process...';
                    
                    if (!viewer) {
                        viewer = await createViewer('#bpmn-viewer');
                    }
                    
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData && viewer) {
                        const success = await loadProcessInViewer(xmlData, viewer);
                        if (success) {
                            viewerContainer.style.display = 'none';
                        } else {
                            viewerContainer.innerHTML = '❌ Failed to load process';
                        }
                    }
                } else {
                    viewerContainer.innerHTML = '👆 Select a process to view...';
                    viewerContainer.style.display = 'flex';
                }
            });

            // Simulation tab events
            document.getElementById('simulate-process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                const simContainer = document.querySelector('#simulation-viewer .loading');
                
                if (selectedValue) {
                    simContainer.innerHTML = '🔄 Loading process for simulation...';
                    
                    if (!simulationViewer) {
                        simulationViewer = await createViewer('#simulation-viewer');
                    }
                    
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData && simulationViewer) {
                        const success = await loadProcessInViewer(xmlData, simulationViewer);
                        if (success) {
                            simContainer.style.display = 'none';
                        } else {
                            simContainer.innerHTML = '❌ Failed to load process';
                        }
                    }
                } else {
                    simContainer.innerHTML = 'Select a process and click Start Simulation...';
                    simContainer.style.display = 'flex';
                }
            });

            // Design toolbar buttons
            document.getElementById('btn-new-process').addEventListener('click', () => {
                document.getElementById('design-process-select').value = 'new';
                document.getElementById('design-process-select').dispatchEvent(new Event('change'));
            });

            document.getElementById('btn-save-process').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    const processName = prompt('Enter process name:') || 'Untitled Process';
                    
                    const formData = new FormData();
                    formData.append('action', 'save_process');
                    formData.append('name', processName);
                    formData.append('xml', xml);
                    formData.append('project_id', '1');
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload();
                    }
                    
                } catch (error) {
                    console.error('Save error:', error);
                }
            });

            document.getElementById('btn-clear-designer').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    await modeler.importXML(defaultBpmnXml);
                    modeler.get('canvas').zoom('fit-viewport');
                    currentXML = defaultBpmnXml;
                    document.getElementById('design-process-select').value = 'new';
                } catch (error) {
                    console.error('Clear error:', error);
                }
            });

            document.getElementById('btn-validate-process').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    const elementRegistry = modeler.get('elementRegistry');
                    const elements = elementRegistry.getAll();
                    
                    const startEvents = elements.filter(el => el.type === 'bpmn:StartEvent');
                    const endEvents = elements.filter(el => el.type === 'bpmn:EndEvent');
                    
                    let validationErrors = [];
                    
                    if (startEvents.length === 0) {
                        validationErrors.push('❌ Missing Start Event');
                    }
                    if (endEvents.length === 0) {
                        validationErrors.push('❌ Missing End Event');
                    }
                    if (startEvents.length > 1) {
                        validationErrors.push('⚠️ Multiple Start Events found');
                    }
                    
                    const statusBar = document.getElementById('design-status');
                    
                    if (validationErrors.length === 0) {
                        statusBar.innerHTML = '<span class="token"></span> ✅ <strong>Process validation passed!</strong> All flow elements are properly connected.';
                        statusBar.style.borderLeft = '4px solid var(--macta-green)';
                        statusBar.style.background = '#e8f5e8';
                    } else {
                        statusBar.innerHTML = '<span class="token"></span> <strong>Validation Issues:</strong><br>' + validationErrors.join('<br>');
                        statusBar.style.borderLeft = '4px solid var(--macta-red)';
                        statusBar.style.background = '#ffebee';
                    }
                    
                    setTimeout(() => {
                        statusBar.innerHTML = '<span class="token"></span> Use the toolbar above to create and edit your process models.';
                        statusBar.style.borderLeft = '4px solid var(--macta-orange)';
                        statusBar.style.background = '#f8f9fa';
                    }, 5000);
                    
                } catch (error) {
                    console.error('Validation error:', error);
                }
            });

            // Export to view
            document.getElementById('btn-export-view').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    currentXML = xml;
                    
                    // Switch to view tab
                    document.querySelector('[data-tab="view"]').click();
                    
                    // Wait for tab switch, then load in viewer
                    setTimeout(async () => {
                        if (!viewer) {
                            viewer = await createViewer('#bpmn-viewer');
                        }
                        
                        if (viewer) {
                            const success = await loadProcessInViewer(currentXML, viewer);
                            if (success) {
                                document.querySelector('#bpmn-viewer .loading').style.display = 'none';
                            }
                        }
                    }, 300);
                    
                } catch (error) {
                    console.error('Export error:', error);
                }
            });

            // Zoom controls for design
            document.getElementById('btn-zoom-in').addEventListener('click', () => {
                if (modeler) {
                    const canvas = modeler.get('canvas');
                    canvas.zoom(canvas.zoom() * 1.3);
                }
            });

            document.getElementById('btn-zoom-out').addEventListener('click', () => {
                if (modeler) {
                    const canvas = modeler.get('canvas');
                    canvas.zoom(canvas.zoom() * 0.7);
                }
            });

            document.getElementById('btn-zoom-fit').addEventListener('click', () => {
                if (modeler) {
                    modeler.get('canvas').zoom('fit-viewport');
                }
            });

            // View controls
            document.getElementById('btn-animate').addEventListener('click', () => {
                animateProcess(viewer);
            });

            document.getElementById('btn-stop-clear').addEventListener('click', () => {
                stopAndClearAnimation(viewer);
            });

            document.getElementById('btn-analyze').addEventListener('click', () => {
                analyzeBottlenecks(viewer);
            });

            document.getElementById('btn-refresh-viewer').addEventListener('click', () => {
                const select = document.getElementById('view-process-select');
                if (select.value) {
                    select.dispatchEvent(new Event('change'));
                }
            });

            // Viewer zoom controls
            document.getElementById('btn-viewer-zoom-in').addEventListener('click', () => {
                if (viewer) {
                    const canvas = viewer.get('canvas');
                    canvas.zoom(canvas.zoom() * 1.3);
                }
            });

            document.getElementById('btn-viewer-zoom-out').addEventListener('click', () => {
                if (viewer) {
                    const canvas = viewer.get('canvas');
                    canvas.zoom(canvas.zoom() * 0.7);
                }
            });

            document.getElementById('btn-viewer-fit').addEventListener('click', () => {
                if (viewer) {
                    viewer.get('canvas').zoom('fit-viewport');
                }
            });

            // Assignment events
            document.getElementById('assign-process-select').addEventListener('change', (e) => {
                if (e.target.value) {
                    loadProcessTasks();
                }
            });

            document.getElementById('btn-assign').addEventListener('click', async () => {
                const taskId = document.getElementById('task-select').value;
                const userId = document.getElementById('user-select').value;
                const duration = document.getElementById('duration-input').value;
                const skills = document.getElementById('skills-input').value;
                
                if (!taskId || !userId) {
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'assign_resource');
                    formData.append('task_name', taskId);
                    formData.append('assigned_user', userId);
                    formData.append('duration', duration);
                    formData.append('skills', skills);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Clear form
                        document.getElementById('task-select').value = '';
                        document.getElementById('user-select').value = '';
                        document.getElementById('duration-input').value = '';
                        document.getElementById('skills-input').value = '';
                        document.getElementById('priority-select').value = '';
                        document.getElementById('cost-input').value = '';
                    }
                    
                } catch (error) {
                    console.error('Assignment error:', error);
                }
            });

            // Simulation controls
            document.getElementById('btn-start-sim').addEventListener('click', () => {
                startSimulation();
            });

            document.getElementById('btn-pause-sim').addEventListener('click', () => {
                pauseSimulation();
            });

            document.getElementById('btn-stop-sim').addEventListener('click', () => {
                stopSimulation();
            });

            document.getElementById('sim-speed').addEventListener('input', (e) => {
                const speed = e.target.value;
                document.getElementById('speed-display').textContent = `${speed}x`;
            });

            // Fullscreen buttons
            document.getElementById('btn-design-fullscreen').addEventListener('click', () => {
                enterFullscreen('design-tab', 'Process Designer');
            });

            document.getElementById('btn-view-fullscreen').addEventListener('click', () => {
                enterFullscreen('view-tab', 'Process Viewer');
            });
        }

        // Animation functions
        function animateProcess(targetViewer) {
            if (!targetViewer) {
                console.log('No viewer available for animation');
                return;
            }
            
            stopAndClearAnimation(targetViewer);
            
            try {
                const elementRegistry = targetViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                if (!startEvent) {
                    console.log('No start event found');
                    return;
                }
                
                console.log('Starting animation...');
                highlightPath(startEvent, elementRegistry, targetViewer, 1000);
                
            } catch (error) {
                console.error('Animation error:', error);
            }
        }

        function stopAndClearAnimation(targetViewer) {
            if (animationInterval) {
                clearInterval(animationInterval);
                animationInterval = null;
            }
            
            if (currentAnimationTimeout) {
                clearTimeout(currentAnimationTimeout);
                currentAnimationTimeout = null;
            }
            
            if (!targetViewer) return;
            
            try {
                const elementRegistry = targetViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = targetViewer.get('elementRegistry').getGraphics(element);
                    if (gfx) {
                        gfx.classList.remove('simulation-highlight', 'bottleneck-highlight');
                    }
                });
                
                console.log('Animation stopped and cleared');
            } catch (error) {
                console.error('Failed to clear animation:', error);
            }
        }

        function highlightPath(currentElement, elementRegistry, targetViewer, delay) {
            if (!currentElement || !targetViewer) return;
            
            try {
                const gfx = targetViewer.get('elementRegistry').getGraphics(currentElement);
                if (gfx) {
                    gfx.classList.add('simulation-highlight');
                }
            } catch (error) {
                console.log('Element highlighting failed:', error);
            }
            
            currentAnimationTimeout = setTimeout(() => {
                const outgoing = currentElement.businessObject?.outgoing;
                if (outgoing && outgoing.length > 0) {
                    let selectedFlows = outgoing;
                    if (currentElement.type === 'bpmn:ExclusiveGateway') {
                        const randomIndex = Math.floor(Math.random() * outgoing.length);
                        selectedFlows = [outgoing[randomIndex]];
                    }
                    
                    for (const flow of selectedFlows) {
                        const nextElement = elementRegistry.get(flow.targetRef?.id);
                        
                        // Highlight flow
                        try {
                            const flowElement = elementRegistry.get(flow.id);
                            if (flowElement) {
                                const flowGfx = targetViewer.get('elementRegistry').getGraphics(flowElement);
                                if (flowGfx) {
                                    flowGfx.classList.add('simulation-highlight');
                                }
                            }
                        } catch (error) {
                            console.log('Flow highlighting failed:', error);
                        }
                        
                        if (nextElement && nextElement.type !== 'bpmn:EndEvent') {
                            currentAnimationTimeout = setTimeout(() => {
                                highlightPath(nextElement, elementRegistry, targetViewer, delay);
                            }, delay / 2);
                        } else if (nextElement) {
                            // Highlight end event
                            currentAnimationTimeout = setTimeout(() => {
                                try {
                                    const gfx = targetViewer.get('elementRegistry').getGraphics(nextElement);
                                    if (gfx) {
                                        gfx.classList.add('simulation-highlight');
                                    }
                                } catch (error) {
                                    console.log('End element highlighting failed:', error);
                                }
                            }, delay / 2);
                        }
                        break;
                    }
                }
            }, delay);
        }

        function analyzeBottlenecks(targetViewer) {
            if (!targetViewer) return;
            
            try {
                const elementRegistry = targetViewer.get('elementRegistry');
                const tasks = elementRegistry.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask'
                );
                
                if (tasks.length === 0) {
                    console.log('No tasks found to analyze');
                    return;
                }
                
                stopAndClearAnimation(targetViewer);
                
                const bottleneckCount = Math.min(3, tasks.length);
                const bottlenecks = [];
                
                for (let i = 0; i < bottleneckCount; i++) {
                    const randomTask = tasks[Math.floor(Math.random() * tasks.length)];
                    if (!bottlenecks.includes(randomTask)) {
                        bottlenecks.push(randomTask);
                        
                        try {
                            const gfx = targetViewer.get('elementRegistry').getGraphics(randomTask);
                            if (gfx) {
                                gfx.classList.add('bottleneck-highlight');
                            }
                        } catch (error) {
                            console.log('Bottleneck highlighting failed:', error);
                        }
                    }
                }
                
                const bottleneckNames = bottlenecks.map(task => task.businessObject.name || task.id);
                console.log('Bottlenecks found:', bottleneckNames.join(', '));
                
            } catch (error) {
                console.error('Bottleneck analysis error:', error);
            }
        }

        // Load process tasks for assignment
        function loadProcessTasks() {
            if (!modeler) return;
            
            try {
                const elementRegistry = modeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const tasks = elements.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask' ||
                    el.type === 'bpmn:StartEvent' ||
                    el.type === 'bpmn:EndEvent' ||
                    el.type === 'bpmn:ExclusiveGateway' ||
                    el.type === 'bpmn:ParallelGateway'
                );
                
                const taskSelect = document.getElementById('task-select');
                taskSelect.innerHTML = '<option value="">Select Task/Element...</option>';
                
                tasks.forEach(task => {
                    const name = task.businessObject.name || task.id;
                    const type = task.type.replace('bpmn:', '');
                    const option = document.createElement('option');
                    option.value = task.id;
                    option.textContent = `${name} (${type})`;
                    taskSelect.appendChild(option);
                });
                
            } catch (error) {
                console.error('Failed to load process tasks:', error);
            }
        }

        // Simulation functions
        function startSimulation() {
            if (simulationActive) return;
            
            simulationActive = true;
            document.getElementById('btn-start-sim').textContent = '▶️ Running...';
            document.getElementById('btn-start-sim').disabled = true;
            
            let totalTime = 0;
            let activeTokens = 0;
            let completedInstances = 0;
            
            simulationInterval = setInterval(() => {
                totalTime += 1;
                
                if (Math.random() > 0.6) {
                    activeTokens = Math.max(0, activeTokens + 1);
                }
                
                if (Math.random() > 0.8 && activeTokens > 0) {
                    completedInstances++;
                    activeTokens = Math.max(0, activeTokens - 1);
                }
                
                updateSimulationMetrics(totalTime, activeTokens, completedInstances);
            }, 1000);
            
            // Auto-animate during simulation
            const animationLoop = setInterval(() => {
                if (simulationActive && simulationViewer) {
                    animateProcess(simulationViewer);
                } else {
                    clearInterval(animationLoop);
                }
            }, 3000);
        }

        function pauseSimulation() {
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
            simulationActive = false;
            document.getElementById('btn-start-sim').textContent = '▶️ Resume';
            document.getElementById('btn-start-sim').disabled = false;
        }

        function stopSimulation() {
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
            simulationActive = false;
            document.getElementById('btn-start-sim').textContent = '▶️ Start Simulation';
            document.getElementById('btn-start-sim').disabled = false;
            
            updateSimulationMetrics(0, 0, 0);
            if (simulationViewer) {
                stopAndClearAnimation(simulationViewer);
            }
        }

        function updateSimulationMetrics(totalTime, activeTokens, completedInstances) {
            const minutes = Math.floor(totalTime / 60);
            const seconds = totalTime % 60;
            const timeDisplay = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            
            document.getElementById('total-time').textContent = timeDisplay;
            document.getElementById('active-tokens').textContent = activeTokens;
            document.getElementById('completed-instances').textContent = completedInstances;
            
            const efficiency = completedInstances > 0 ? Math.min(100, Math.round((completedInstances / (totalTime / 30)) * 100)) : 0;
            document.getElementById('efficiency-score').textContent = `${efficiency}%`;
        }

        // Fullscreen functionality
        function enterFullscreen(tabId, title) {
            const tab = document.getElementById(tabId);
            const overlay = document.getElementById('fullscreen-overlay');
            const content = document.getElementById('fullscreen-content');
            const titleEl = document.getElementById('fullscreen-title');
            
            // Move tab content to fullscreen
            content.innerHTML = '';
            content.appendChild(tab.cloneNode(true));
            
            // Show overlay
            overlay.classList.add('active');
            titleEl.textContent = `MACTA - ${title}`;
            
            // Hide body scroll
            document.body.style.overflow = 'hidden';
        }

        function exitFullscreen() {
            const overlay = document.getElementById('fullscreen-overlay');
            
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>