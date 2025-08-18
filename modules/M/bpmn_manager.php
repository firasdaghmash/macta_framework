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
                // Here you would save to process_step_resources table
                // For now, just return success
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

    <!-- MACTA Brand Colors and Layout -->
    <style>
        :root {
            --macta-orange: #ff7b54;
            --macta-red: #d63031;
            --macta-teal: #00b894;
            --macta-yellow: #fdcb6e;
            --macta-green: #6c5ce7;
            --macta-dark: #2d3436;
            --macta-light: #ddd;
            --box-height: 600px;
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

        .nav-tabs {
            display: flex;
            gap: 10px;
        }

        .nav-tab {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            background: var(--macta-light);
            color: var(--macta-dark);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            background: var(--macta-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,123,84,0.3);
        }

        .nav-tab:hover:not(.active) {
            background: #ccc;
            transform: translateY(-1px);
        }

        .main-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: none;
        }

        .panel {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            transition: all 0.3s ease;
        }

        .panel.collapsed {
            max-height: 80px;
            overflow: hidden;
            padding: 15px 20px;
        }

        .panel.expanded {
            max-height: none;
            min-height: 600px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 10px 0;
        }

        .panel-header h2 {
            color: var(--macta-dark);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .panel-toggle {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s ease;
            color: var(--macta-dark);
        }

        .panel-toggle.expanded {
            transform: rotate(180deg);
        }

        .panel-content {
            display: none;
            flex-direction: column;
            height: 600px;
        }

        .panel-content.active {
            display: flex;
        }

        .panel-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        #bpmn-editor, #bpmn-viewer {
            height: var(--box-height);
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            flex: 1;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--macta-light);
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
        }

        .btn-primary {
            background: var(--macta-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a3a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,123,84,0.3);
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

        .btn-success {
            background: var(--macta-green);
            color: white;
        }

        .btn-success:hover {
            background: #5b4ec7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108,92,231,0.3);
        }

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        .btn-warning:hover {
            background: #f0b95e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253,203,110,0.3);
        }

        .status-bar {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
            border-left: 4px solid var(--macta-orange);
        }

        .process-selector {
            margin-bottom: 15px;
        }

        .process-selector select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .metric-card {
            background: linear-gradient(135deg, var(--macta-teal), var(--macta-green));
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .assignment-panel {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .assignment-panel.active {
            display: block;
        }

        .assignment-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .assignment-form input, .assignment-form select {
            padding: 8px;
            border: 1px solid var(--macta-light);
            border-radius: 5px;
        }

        .simulation-controls {
            display: none;
            gap: 10px;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .simulation-controls.active {
            display: flex;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
            font-size: 18px;
            color: var(--macta-orange);
        }

        /* BPMN.js diagram fixes - prevent transform errors */
        .bjs-container {
            background: white !important;
        }
        
        .djs-element {
            pointer-events: all !important;
        }
        
        .djs-shape, .djs-connection {
            fill: none !important;
            stroke: #333 !important;
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
        
        /* Fix BPMN.js display issues */
        .bjs-container {
            background: white !important;
            position: relative !important;
        }
        
        .djs-container {
            position: relative !important;
            overflow: hidden !important;
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
        
        /* Fix missing element shapes */
        .djs-shape[data-element-id*="Task"] .djs-visual {
            visibility: visible !important;
            display: block !important;
        }
        
        .djs-shape[data-element-id*="Event"] .djs-visual {
            visibility: visible !important;
            display: block !important;
        }
        
        .djs-shape[data-element-id*="Gateway"] .djs-visual {
            visibility: visible !important;
            display: block !important;
        }
        
        /* Fix transform matrix issues completely */
        .djs-element[transform*="NaN"] {
            transform: translate(0px, 0px) !important;
        }
        
        .djs-group[transform*="NaN"] {
            transform: translate(0px, 0px) !important;
        }
        
        /* Prevent CSS transform errors */
        .djs-visual {
            transform: none !important;
        }
        
        .djs-hit {
            visibility: hidden !important;
        }
        
        /* Force display of BPMN elements */
        .bjs-container .djs-visual rect,
        .bjs-container .djs-visual circle,
        .bjs-container .djs-visual polygon,
        .bjs-container .djs-visual path {
            display: block !important;
            visibility: visible !important;
        }
        
        /* Task styling */
        .djs-shape[data-element-id*="Task"] .djs-visual > rect {
            fill: #f8f9fa !important;
            stroke: var(--macta-teal) !important;
        }
        
        /* Start event styling */
        .djs-shape[data-element-id*="Start"] .djs-visual > circle {
            fill: #e8f5e8 !important;
            stroke: var(--macta-green) !important;
            stroke-width: 3px !important;
        }
        
        /* End event styling */
        .djs-shape[data-element-id*="End"] .djs-visual > circle {
            fill: #ffe8e8 !important;
            stroke: var(--macta-red) !important;
            stroke-width: 3px !important;
        }
        
        /* Gateway styling */
        .djs-shape[data-element-id*="Gateway"] .djs-visual > polygon {
            fill: #fff8e1 !important;
            stroke: var(--macta-yellow) !important;
        }
        
        /* Connection styling */
        .djs-connection .djs-visual > path {
            stroke: #666 !important;
            stroke-width: 2px !important;
            fill: none !important;
        }
        
        /* Labels */
        .djs-label {
            font-family: 'Segoe UI', sans-serif !important;
            font-size: 12px !important;
            fill: #333 !important;
        }
        
        /* Highlighted elements during simulation */
        .simulation-highlight .djs-visual > rect,
        .simulation-highlight .djs-visual > circle,
        .simulation-highlight .djs-visual > polygon {
            fill: var(--macta-orange) !important;
            stroke: var(--macta-red) !important;
            stroke-width: 4px !important;
            animation: pulse 1s infinite;
        }
        
        .simulation-highlight .djs-visual > path {
            stroke: var(--macta-orange) !important;
            stroke-width: 4px !important;
            animation: flow 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        @keyframes flow {
            0% { stroke-dasharray: 10 5; stroke-dashoffset: 0; }
            100% { stroke-dasharray: 10 5; stroke-dashoffset: -15; }
        }
        
        /* Bottleneck highlighting */
        .bottleneck-highlight .djs-visual > rect,
        .bottleneck-highlight .djs-visual > circle,
        .bottleneck-highlight .djs-visual > polygon {
            fill: #ffebee !important;
            stroke: var(--macta-red) !important;
            stroke-width: 4px !important;
            animation: warning 0.5s infinite alternate;
        }
        
        /* Enhanced full screen mode with higher z-index */
        .panel.fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 999999 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            background: white !important;
            box-shadow: none !important;
        }
        
        .panel.fullscreen .panel-content {
            height: calc(100vh - 120px) !important;
            overflow: auto !important;
            padding: 20px !important;
        }
        
        .panel.fullscreen #bpmn-editor,
        .panel.fullscreen #bpmn-viewer,
        .panel.fullscreen #simulation-viewer {
            height: calc(100vh - 250px) !important;
            width: 100% !important;
        }
        
        .fullscreen-controls {
            position: fixed !important;
            top: 15px !important;
            right: 15px !important;
            z-index: 1000000 !important;
        }
        
        .btn-close-fullscreen {
            background: var(--macta-red) !important;
            color: white !important;
            font-size: 20px !important;
            padding: 12px 16px !important;
            border-radius: 50% !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        }
        
        .btn-close-fullscreen:hover {
            background: #b71c1c !important;
            transform: scale(1.1) !important;
        }
        .validation-error .djs-visual > rect,
        .validation-error .djs-visual > circle,
        .validation-error .djs-visual > polygon {
            fill: #ffebee !important;
            stroke: var(--macta-red) !important;
            stroke-width: 4px !important;
            animation: validation-pulse 1s infinite;
        }
        
        @keyframes validation-pulse {
            0% { stroke: var(--macta-red); }
            50% { stroke: #ff5722; }
            100% { stroke: var(--macta-red); }
        }

        .legend {
            font-size: 12px;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>

    <!-- BPMN.js styles from multiple CDN sources for reliability -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
    
    <!-- Fallback CDN -->
    <style>
        @import url('https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/assets/diagram-js.css');
        @import url('https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css');
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>
            <div class="macta-logo">M</div>
            MACTA Process Manager
        </h1>
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="togglePanel('design')">Design</button>
            <button class="nav-tab" onclick="togglePanel('view')">View</button>
            <button class="nav-tab" onclick="togglePanel('assign')">Assign</button>
            <button class="nav-tab" onclick="togglePanel('simulate')">Simulate</button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Design Panel -->
        <div class="panel expanded" id="design-panel">
            <div class="panel-header" onclick="togglePanel('design')">
                <h2>
                    <div class="panel-icon" style="background: var(--macta-orange);">🎨</div>
                    Process Design & Modeling
                </h2>
                <button class="panel-toggle expanded">▼</button>
            </div>
            
            <div class="panel-content active">
                <!-- Process Selector -->
                <div class="process-selector">
                    <select id="process-select">
                        <option value="">Select a Process...</option>
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
                    <button class="btn btn-warning" id="btn-assign-fullscreen">
                        🖥️ Full Screen
                    </button>
                </div>

                <!-- BPMN Editor -->
                <div id="bpmn-editor">
                    <div class="loading">Loading BPMN Editor...</div>
                </div>

                <!-- Design Toolbar -->
                <div class="toolbar">
                    <button class="btn btn-primary" id="btn-new-process">
                        📄 New Process
                    </button>
                    <button class="btn btn-secondary" id="btn-save-process">
                        💾 Save Process
                    </button>
                    <button class="btn btn-warning" id="btn-clear-designer">
                        🗑️ Clear Designer
                    </button>
                    <button class="btn btn-secondary" id="btn-validate-process">
                        ✅ Validate
                    </button>
                    <button class="btn btn-secondary" id="btn-zoom-in">
                        🔍+ Zoom In
                    </button>
                    <button class="btn btn-secondary" id="btn-zoom-out">
                        🔍- Zoom Out
                    </button>
                    <button class="btn btn-secondary" id="btn-zoom-fit">
                        📐 Fit to Screen
                    </button>
                    <button class="btn btn-success" id="btn-export-xml">
                        📤 Export to Viewer
                    </button>
                    <button class="btn btn-warning" id="btn-design-fullscreen">
                        🖥️ Full Screen
                    </button>
                </div>

                <div class="status-bar">
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
        </div>

        <!-- View Panel -->
        <div class="panel collapsed" id="view-panel">
            <div class="panel-header" onclick="togglePanel('view')">
                <h2>
                    <div class="panel-icon" style="background: var(--macta-teal);">👁️</div>
                    Process View & Analysis
                </h2>
                <button class="panel-toggle">▼</button>
            </div>
            
            <div class="panel-content" style="display: none;">
                <!-- Debug: This should show when panel is expanded -->
                <div style="background: #e8f5e8; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                    <strong>🔧 Debug:</strong> If you can see this message, the panel content is working!
                </div>
                
                <!-- Process Selector for Viewer -->
                <div class="process-selector" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: var(--macta-dark);">
                        Select Process to View:
                    </label>
                    <select id="viewer-process-select" style="width: 100%; padding: 12px; border: 2px solid var(--macta-teal); border-radius: 8px; font-size: 14px; background: white;">
                        <option value="">Choose a process to view...</option>
                        <?php if (!empty($processes)): ?>
                            <?php foreach ($processes as $process): ?>
                                <option value="<?= $process['id'] ?>" data-xml="<?= htmlspecialchars($process['model_data']) ?>">
                                    <?= htmlspecialchars($process['name']) ?> 
                                    <?php if ($process['project_name']): ?>
                                        (<?= htmlspecialchars($process['project_name']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No processes found in database</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- BPMN Viewer -->
                <div id="bpmn-viewer" style="height: 650px; border: 2px solid var(--macta-light); border-radius: 10px; background: #f8f9fa;">
                    <div class="loading" style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 16px; color: var(--macta-teal);">
                        👆 Select a process from the dropdown above to view it here...
                    </div>
                </div>

                <div class="toolbar">
                    <button class="btn btn-primary" id="btn-animate-path">
                        🎬 Animate Process
                    </button>
                    <button class="btn btn-secondary" id="btn-clear-highlights">
                        ⏹️ Stop & Clear
                    </button>
                    <button class="btn btn-warning" id="btn-analyze-bottlenecks">
                        🔍 Analyze Bottlenecks
                    </button>
                    <button class="btn btn-success" id="btn-refresh-viewer">
                        🔄 Refresh Viewer
                    </button>
                    <button class="btn btn-secondary" id="btn-viewer-zoom-in">
                        🔍+ Zoom In
                    </button>
                    <button class="btn btn-secondary" id="btn-viewer-zoom-out">
                        🔍- Zoom Out
                    </button>
                    <button class="btn btn-secondary" id="btn-viewer-zoom-fit">
                        📐 Fit to Screen
                    </button>
                    <button class="btn btn-warning" id="btn-viewer-fullscreen">
                        🖥️ Full Screen
                    </button>
                </div>

                <div class="legend">
                    <strong>🎯 Process Analysis Features:</strong><br>
                    <span class="token"></span> <strong>Select Process:</strong> Choose any process from your database (<?= count($processes) ?> available)<br>
                    <span class="token"></span> <strong>Animate:</strong> Shows process flow step by step<br>
                    <span class="token"></span> <strong>Analyze:</strong> Identifies potential bottlenecks<br>
                    <span class="token"></span> <strong>Refresh:</strong> Reload the current process in viewer
                </div>
            </div>
        </div>

        <!-- Assign Panel -->
        <div class="panel collapsed" id="assign-panel">
            <div class="panel-header" onclick="togglePanel('assign')">
                <h2>
                    <div class="panel-icon" style="background: var(--macta-yellow);">👥</div>
                    Resource Assignment
                </h2>
                <button class="panel-toggle">▼</button>
            </div>
            
            <div class="panel-content">
                <div style="padding: 20px;">
                    <h4>Assign Resources to Process Tasks</h4>
                    <div class="assignment-form">
                        <select id="task-name">
                            <option value="">Select Task...</option>
                        </select>
                        <select id="assigned-user">
                            <option value="">Select User...</option>
                            <option value="john.doe">John Doe - Process Analyst</option>
                            <option value="jane.smith">Jane Smith - Operations Manager</option>
                            <option value="mike.wilson">Mike Wilson - Quality Specialist</option>
                            <option value="sarah.connor">Sarah Connor - Team Lead</option>
                            <option value="alex.murphy">Alex Murphy - Senior Consultant</option>
                        </select>
                        <input type="number" placeholder="Duration (hours)" id="task-duration">
                        <input type="text" placeholder="Required Skills" id="task-skills">
                        <select id="priority-level">
                            <option value="">Priority Level...</option>
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                            <option value="critical">Critical</option>
                        </select>
                        <input type="number" placeholder="Cost per Hour ($)" id="task-cost">
                        <button class="btn btn-success" id="btn-assign-resource" style="grid-column: 1 / -1;">
                            ✅ Assign Resource
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulate Panel -->
        <div class="panel collapsed" id="simulate-panel">
            <div class="panel-header" onclick="togglePanel('simulate')">
                <h2>
                    <div class="panel-icon" style="background: var(--macta-green);">🎯</div>
                    Process Simulation
                </h2>
                <button class="panel-toggle">▼</button>
            </div>
            
            <div class="panel-content">
                <div class="simulation-controls" style="display: flex; margin-bottom: 15px;">
                    <button class="btn btn-success" id="btn-start-simulation">
                        ▶️ Start Simulation
                    </button>
                    <button class="btn btn-warning" id="btn-pause-simulation">
                        ⏸️ Pause
                    </button>
                    <button class="btn btn-secondary" id="btn-stop-simulation">
                        ⏹️ Stop
                    </button>
                    <button class="btn btn-warning" id="btn-simulate-fullscreen">
                        🖥️ Full Screen
                    </button>
                    <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                        <label>Speed:</label>
                        <input type="range" id="sim-speed" min="0.5" max="3" step="0.1" value="1" style="width: 100px;">
                        <span id="speed-display">1x</span>
                    </div>
                </div>

                <!-- Simulation Viewer -->
                <div id="simulation-viewer" style="height: 550px; border: 2px solid var(--macta-light); border-radius: 10px; margin-bottom: 15px;">
                    <div class="loading">Click Start Simulation to begin...</div>
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

                <div class="legend">
                    <strong>🎯 Simulation Features:</strong><br>
                    <span class="token"></span> <strong>Real-time:</strong> Live process execution monitoring<br>
                    <span class="token"></span> <strong>Metrics:</strong> Performance indicators and efficiency scores<br>
                    <span class="token"></span> <strong>Speed Control:</strong> Adjust simulation speed from 0.5x to 3x<br>
                    <span class="token"></span> <strong>Analysis:</strong> Bottleneck detection and optimization suggestions
                </div>
            </div>
        </div>
    </div>

    <!-- BPMN.js scripts from CDN with fallback -->
    <script>
        // Store PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const projects = <?= json_encode($projects) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        
        // Check for database errors
        if (dbError) {
            console.error('Database Error:', dbError);
            document.querySelector('#bpmn-editor .loading').innerHTML = 'Database connection failed: ' + dbError;
            document.querySelector('#bpmn-viewer .loading').innerHTML = 'Database connection failed: ' + dbError;
        }
        
        // Fallback BPMN XML for when no process is selected
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
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="StartEvent_1_di" bpmnElement="StartEvent_1">
        <dc:Bounds x="150" y="200" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
        <dc:Bounds x="300" y="200" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
        <di:waypoint x="186" y="218"/>
        <di:waypoint x="300" y="218"/>
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn2:definitions>`;

        let modeler = null;
        let viewer = null;
        let simulationViewer = null;
        let currentXML = defaultBpmnXml;
        let simulationActive = false;
        let simulationInterval = null;
        let animationInterval = null;
        let currentAnimationTimeout = null;

        // Load scripts dynamically with multiple fallback options
        function loadScript(urls, callback) {
            let currentIndex = 0;
            
            function tryNextUrl() {
                if (currentIndex >= urls.length) {
                    console.error('All CDN sources failed');
                    document.querySelector('#bpmn-editor .loading').innerHTML = 'Failed to load BPMN libraries from all sources. Please check your internet connection.';
                    document.querySelector('#bpmn-viewer .loading').innerHTML = 'Failed to load BPMN libraries from all sources. Please check your internet connection.';
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

        // Initialize BPMN.js with fallback CDNs
        function initializeBpmn() {
            const bpmnCdnUrls = [
                'https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://unpkg.com/bpmn-js@16.0.0/dist/bpmn-modeler.development.js',
                'https://unpkg.com/bpmn-js@15.0.0/dist/bpmn-modeler.development.js'
            ];
            
            loadScript(bpmnCdnUrls, () => {
                try {
                    // Check if BpmnJS is available
                    if (typeof BpmnJS === 'undefined') {
                        throw new Error('BpmnJS not loaded');
                    }
                    
                    modeler = new BpmnJS({
                        container: '#bpmn-editor'
                    });
                    
                    // Load initial process
                    loadInitialProcess();
                    
                    // Initialize viewer
                    initializeViewer();
                    
                } catch (error) {
                    console.error('Failed to initialize BPMN modeler:', error);
                    document.querySelector('#bpmn-editor .loading').innerHTML = 'BPMN Editor initialization failed: ' + error.message;
                    
                    // Try simplified initialization
                    initializeSimplifiedEditor();
                }
            });
        }

        // Simplified editor as fallback
        function initializeSimplifiedEditor() {
            document.querySelector('#bpmn-editor .loading').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <h3>Simplified Process Editor</h3>
                    <p>Advanced BPMN editor failed to load. You can still:</p>
                    <button onclick="window.open('../index.php', '_blank')" class="btn btn-primary" style="margin: 10px;">
                        Open Visual Process Builder
                    </button>
                    <br>
                    <textarea id="xml-editor" placeholder="Paste BPMN XML here..." style="width: 100%; height: 200px; margin-top: 10px;"></textarea>
                </div>
            `;
            
            // Enable basic XML editing
            setupXmlEditor();
        }

        function setupXmlEditor() {
            const xmlEditor = document.getElementById('xml-editor');
            if (xmlEditor) {
                xmlEditor.value = currentXML;
                xmlEditor.addEventListener('change', () => {
                    currentXML = xmlEditor.value;
                    if (viewer) {
                        loadProcessInViewer(currentXML);
                    }
                });
            }
        }

        // Initialize viewer with dedicated simulation viewer
        function initializeViewer() {
            try {
                if (typeof BpmnJS !== 'undefined') {
                    viewer = new BpmnJS({
                        container: '#bpmn-viewer'
                    });
                    
                    // Also create simulation viewer
                    simulationViewer = new BpmnJS({
                        container: '#simulation-viewer'
                    });
                    
                    loadProcessInViewer(currentXML);
                } else {
                    // Fallback viewer
                    initializeSimplifiedViewer();
                }
                
            } catch (error) {
                console.error('Failed to initialize BPMN viewer:', error);
                initializeSimplifiedViewer();
            }
        }

        // Simplified viewer as fallback
        function initializeSimplifiedViewer() {
            document.querySelector('#bpmn-viewer .loading').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <h3>Process XML Viewer</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: left; max-height: 400px; overflow-y: auto;">
                        <pre id="xml-display" style="font-size: 12px; margin: 0;">${escapeHtml(currentXML)}</pre>
                    </div>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        BPMN viewer failed to load. Showing XML content instead.
                    </p>
                </div>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load initial process with better error handling
        async function loadInitialProcess() {
            try {
                let xmlToLoad = defaultBpmnXml;
                
                if (processes.length > 0 && processes[0].model_data) {
                    // Load first process from database
                    xmlToLoad = processes[0].model_data;
                    currentXML = xmlToLoad;
                    console.log('Loading process:', processes[0].name);
                }
                
                if (modeler) {
                    await modeler.importXML(xmlToLoad);
                    
                    // Fix canvas display issues
                    const canvas = modeler.get('canvas');
                    canvas.zoom('fit-viewport');
                    
                    // Force re-render to fix missing elements
                    setTimeout(() => {
                        canvas.zoom(canvas.zoom());
                    }, 100);
                    
                    console.log('Process loaded successfully in editor');
                }
                
                // Hide loading indicator
                const editorLoading = document.querySelector('#bpmn-editor .loading');
                if (editorLoading) {
                    editorLoading.style.display = 'none';
                }
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
                
                // Try loading default XML
                try {
                    if (modeler) {
                        await modeler.importXML(defaultBpmnXml);
                        const canvas = modeler.get('canvas');
                        canvas.zoom('fit-viewport');
                        
                        // Force re-render
                        setTimeout(() => {
                            canvas.zoom(canvas.zoom());
                        }, 100);
                    }
                } catch (fallbackError) {
                    console.error('Failed to load fallback process:', fallbackError);
                    initializeSimplifiedEditor();
                }
                
                const editorLoading = document.querySelector('#bpmn-editor .loading');
                if (editorLoading) {
                    editorLoading.style.display = 'none';
                }
            }
        }

        // Load process in viewer with better error handling
        async function loadProcessInViewer(xml) {
            if (!viewer) {
                console.log('Viewer not initialized yet');
                return;
            }
            
            try {
                await viewer.importXML(xml);
                viewer.get('canvas').zoom('fit-viewport');
                
                // Hide loading indicator
                const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                if (viewerLoading) {
                    viewerLoading.style.display = 'none';
                }
                
                console.log('Process loaded successfully in viewer');
                
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
                const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                if (viewerLoading) {
                    viewerLoading.innerHTML = 'Failed to load process in viewer: ' + error.message;
                }
            }
        }

        // Panel toggle functionality
        function togglePanel(panelName) {
            const panels = ['design', 'view', 'assign', 'simulate'];
            
            console.log('Toggling panel:', panelName);
            
            // Update header tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            panels.forEach(name => {
                const panel = document.getElementById(`${name}-panel`);
                const toggle = panel.querySelector('.panel-toggle');
                const content = panel.querySelector('.panel-content');
                
                if (name === panelName) {
                    // Expand clicked panel
                    panel.classList.remove('collapsed');
                    panel.classList.add('expanded');
                    toggle.classList.add('expanded');
                    
                    // Force show content
                    if (content) {
                        content.style.display = 'flex';
                        console.log(`Panel ${name} content made visible`);
                    }
                    
                    // Update header tab
                    const headerTab = document.querySelector(`[onclick="togglePanel('${name}')"]`);
                    if (headerTab) {
                        headerTab.classList.add('active');
                    }
                    
                    // Special handling for different panels
                    if (name === 'view') {
                        console.log('View panel opened, setting up viewer...');
                        // Don't auto-export, let user select from dropdown
                    }
                    
                    if (name === 'simulate') {
                        // Load current process into simulation
                        setTimeout(async () => {
                            if (modeler) {
                                try {
                                    const { xml } = await modeler.saveXML({ format: true });
                                    currentXML = xml;
                                    console.log('Auto-exporting current process to simulation');
                                } catch (error) {
                                    console.error('Failed to export current process:', error);
                                }
                            }
                            
                            // Force load process in simulation viewer
                            if (simulationViewer && currentXML) {
                                try {
                                    await simulationViewer.importXML(currentXML);
                                    simulationViewer.get('canvas').zoom('fit-viewport');
                                    
                                    // Hide loading indicator
                                    const simLoading = document.querySelector('#simulation-viewer .loading');
                                    if (simLoading) {
                                        simLoading.style.display = 'none';
                                    }
                                    
                                    console.log('Process loaded in simulation viewer successfully');
                                } catch (error) {
                                    console.error('Failed to load process in simulation viewer:', error);
                                }
                            }
                        }, 400);
                    }
                    
                    if (name === 'assign') {
                        // Load process tasks into assignment dropdown
                        setTimeout(() => {
                            loadProcessTasks();
                        }, 300);
                    }
                    
                } else {
                    // Collapse other panels
                    panel.classList.remove('expanded');
                    panel.classList.add('collapsed');
                    toggle.classList.remove('expanded');
                    
                    // Hide content
                    if (content) {
                        content.style.display = 'none';
                    }
                }
            });
            
            // Re-fit canvas when panel is opened
            setTimeout(() => {
                if (panelName === 'design' && modeler) {
                    modeler.get('canvas').zoom('fit-viewport');
                } else if ((panelName === 'view') && viewer) {
                    viewer.get('canvas').zoom('fit-viewport');
                } else if ((panelName === 'simulate') && simulationViewer) {
                    simulationViewer.get('canvas').zoom('fit-viewport');
                }
            }, 600);
        }

        // Dedicated function to export and load to viewer
        async function exportAndLoadToViewer() {
            console.log('Starting export and load to viewer...');
            
            // Block any alerts that might be triggered during export
            const tempAlert = window.alert;
            window.alert = () => {};
            
            try {
                // Step 1: Export current process from modeler
                if (modeler) {
                    try {
                        const { xml } = await modeler.saveXML({ format: true });
                        currentXML = xml;
                        console.log('Current XML exported successfully');
                    } catch (error) {
                        console.error('Failed to export current process:', error);
                        return;
                    }
                }
                
                // Step 2: Wait for viewer panel to be ready
                setTimeout(async () => {
                    console.log('Loading process into viewer...');
                    
                    // Step 3: Re-initialize viewer to avoid cached issues
                    if (typeof BpmnJS !== 'undefined') {
                        try {
                            // Destroy existing viewer if it exists
                            if (viewer) {
                                try {
                                    viewer.destroy();
                                } catch (e) {
                                    console.warn('Error destroying viewer:', e);
                                }
                            }
                            
                            // Create fresh viewer instance
                            viewer = new BpmnJS({
                                container: '#bpmn-viewer'
                            });
                            console.log('Fresh viewer initialized');
                        } catch (error) {
                            console.error('Failed to initialize viewer:', error);
                            return;
                        }
                    }
                    
                    // Step 4: Load process in viewer with comprehensive error handling
                    if (viewer && currentXML) {
                        try {
                            // Import new XML
                            await viewer.importXML(currentXML);
                            
                            // Safe zoom with multiple fallbacks
                            const canvas = viewer.get('canvas');
                            try {
                                canvas.zoom('fit-viewport');
                            } catch (zoomError1) {
                                console.warn('Fit viewport failed, trying zoom(1):', zoomError1);
                                try {
                                    canvas.zoom(1);
                                } catch (zoomError2) {
                                    console.warn('Zoom(1) failed, using setZoom:', zoomError2);
                                    try {
                                        canvas.setZoom(1);
                                    } catch (zoomError3) {
                                        console.warn('All zoom methods failed:', zoomError3);
                                    }
                                }
                            }
                            
                            // Hide loading indicator
                            const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                            if (viewerLoading) {
                                viewerLoading.style.display = 'none';
                            }
                            
                            console.log('Process loaded in viewer successfully!');
                            
                        } catch (error) {
                            console.error('Failed to load process in viewer:', error);
                            const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                            if (viewerLoading) {
                                viewerLoading.innerHTML = 'Failed to load process: ' + error.message;
                            }
                        }
                    } else {
                        console.error('Viewer not available or no XML to load');
                        const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                        if (viewerLoading) {
                            viewerLoading.innerHTML = 'Viewer initialization failed or no process to load';
                        }
                    }
                }, 600);
                
            } finally {
                // Restore alert function after a delay
                setTimeout(() => {
                    window.alert = tempAlert;
                }, 1000);
            }
        }

        // Direct viewer loading function with better error handling
        async function loadProcessInViewerDirectly(xmlData) {
            console.log('Starting direct viewer load...');
            console.log('XML data length:', xmlData ? xmlData.length : 'No data');
            
            try {
                // Always recreate viewer to avoid cached transform issues
                if (viewer) {
                    try {
                        viewer.destroy();
                        console.log('Previous viewer destroyed');
                    } catch (e) {
                        console.warn('Error destroying viewer:', e);
                    }
                }
                
                // Clear the container completely
                const container = document.getElementById('bpmn-viewer');
                container.innerHTML = '<div class="loading" style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 16px; color: var(--macta-teal);">🔄 Loading process...</div>';
                
                // Wait a moment for DOM to clear
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Create completely fresh viewer instance
                if (typeof BpmnJS !== 'undefined') {
                    try {
                        console.log('Creating fresh viewer instance...');
                        viewer = new BpmnJS({
                            container: '#bpmn-viewer',
                            // Add configuration to prevent transform errors
                            canvas: {
                                deferUpdate: false
                            }
                        });
                        console.log('Fresh viewer initialized for direct loading');
                    } catch (error) {
                        console.error('Failed to initialize viewer:', error);
                        const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                        if (viewerLoading) {
                            viewerLoading.style.display = 'flex';
                            viewerLoading.innerHTML = '❌ Failed to initialize viewer: ' + error.message;
                        }
                        return;
                    }
                }
                
                // Load the process with better error handling
                if (viewer && xmlData) {
                    try {
                        console.log('Importing XML into fresh viewer...');
                        await viewer.importXML(xmlData);
                        console.log('XML imported successfully');
                        
                        // Wait for rendering to complete
                        await new Promise(resolve => setTimeout(resolve, 200));
                        
                        // Safe zoom with multiple fallbacks
                        const canvas = viewer.get('canvas');
                        try {
                            canvas.zoom('fit-viewport');
                            console.log('Zoom fit-viewport successful');
                        } catch (zoomError1) {
                            console.warn('Fit viewport failed, trying zoom(1)');
                            try {
                                canvas.zoom(1);
                                console.log('Zoom(1) successful');
                            } catch (zoomError2) {
                                console.warn('Zoom(1) failed, trying manual zoom');
                                try {
                                    canvas.viewbox({ x: 0, y: 0, width: 800, height: 600 });
                                    console.log('Manual viewbox set');
                                } catch (zoomError3) {
                                    console.warn('All zoom methods failed, continuing without zoom');
                                }
                            }
                        }
                        
                        // Hide loading indicator
                        const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                        if (viewerLoading) {
                            viewerLoading.style.display = 'none';
                        }
                        
                        console.log('✅ Process loaded directly in viewer successfully!');
                        
                    } catch (importError) {
                        console.error('Failed to import XML in viewer:', importError);
                        const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                        if (viewerLoading) {
                            viewerLoading.style.display = 'flex';
                            viewerLoading.innerHTML = `❌ Failed to load process: ${importError.message}`;
                        }
                    }
                } else {
                    const errorMsg = !viewer ? 'Viewer not available' : 'No XML data provided';
                    console.error(errorMsg);
                    const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                    if (viewerLoading) {
                        viewerLoading.style.display = 'flex';
                        viewerLoading.innerHTML = `❌ ${errorMsg}`;
                    }
                }
                
            } catch (error) {
                console.error('Error in direct viewer loading:', error);
                const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                if (viewerLoading) {
                    viewerLoading.style.display = 'flex';
                    viewerLoading.innerHTML = `❌ Error loading process: ${error.message}`;
                }
            }
        }
        // Full screen functionality with better debugging
        function toggleFullScreen(panelId) {
            console.log('toggleFullScreen called for:', panelId);
            
            const panel = document.getElementById(panelId);
            if (!panel) {
                console.error('Panel not found:', panelId);
                return;
            }
            
            const isFullScreen = panel.classList.contains('fullscreen');
            console.log('Current fullscreen state:', isFullScreen);
            
            if (isFullScreen) {
                // Exit full screen
                panel.classList.remove('fullscreen');
                
                // Remove close button
                const closeBtn = panel.querySelector('.fullscreen-controls');
                if (closeBtn) {
                    closeBtn.remove();
                }
                
                // Reset body overflow
                document.body.style.overflow = '';
                
                console.log('✅ Exited full screen for', panelId);
            } else {
                // Enter full screen
                panel.classList.add('fullscreen');
                
                // Hide body scrollbar
                document.body.style.overflow = 'hidden';
                
                // Add close button
                const closeBtn = document.createElement('div');
                closeBtn.className = 'fullscreen-controls';
                closeBtn.innerHTML = `
                    <button class="btn btn-close-fullscreen" onclick="toggleFullScreen('${panelId}')" 
                            style="background: var(--macta-red) !important; color: white !important; 
                                   font-size: 18px !important; padding: 10px 15px !important; 
                                   border-radius: 50% !important; border: none; cursor: pointer;">
                        ✕
                    </button>
                `;
                panel.appendChild(closeBtn);
                
                console.log('✅ Entered full screen for', panelId);
                
                // Re-fit canvas after full screen with delay
                setTimeout(() => {
                    try {
                        if (panelId === 'design-panel' && modeler) {
                            modeler.get('canvas').zoom('fit-viewport');
                            console.log('Designer canvas refitted for fullscreen');
                        } else if (panelId === 'view-panel' && viewer) {
                            viewer.get('canvas').zoom('fit-viewport');
                            console.log('Viewer canvas refitted for fullscreen');
                        } else if (panelId === 'simulate-panel' && simulationViewer) {
                            simulationViewer.get('canvas').zoom('fit-viewport');
                            console.log('Simulation canvas refitted for fullscreen');
                        }
                    } catch (error) {
                        console.error('Error refitting canvas for fullscreen:', error);
                    }
                }, 500);
            }
        }
        function loadProcessTasks() {
            if (!modeler) return;
            
            try {
                const elementRegistry = modeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Filter and sort elements by type
                const tasks = elements.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask' ||
                    el.type === 'bpmn:StartEvent' ||
                    el.type === 'bpmn:EndEvent' ||
                    el.type === 'bpmn:ExclusiveGateway' ||
                    el.type === 'bpmn:ParallelGateway'
                );
                
                // Sort by type
                tasks.sort((a, b) => {
                    const order = {
                        'bpmn:StartEvent': 1,
                        'bpmn:Task': 2,
                        'bpmn:UserTask': 3,
                        'bpmn:ServiceTask': 4,
                        'bpmn:ExclusiveGateway': 5,
                        'bpmn:ParallelGateway': 6,
                        'bpmn:EndEvent': 7
                    };
                    return (order[a.type] || 999) - (order[b.type] || 999);
                });
                
                // Update task dropdown
                const taskSelect = document.getElementById('task-name');
                if (taskSelect && taskSelect.tagName === 'INPUT') {
                    // Convert input to select
                    const newSelect = document.createElement('select');
                    newSelect.id = 'task-name';
                    newSelect.innerHTML = '<option value="">Select Task...</option>';
                    
                    tasks.forEach(task => {
                        const name = task.businessObject.name || task.id;
                        const type = task.type.replace('bpmn:', '');
                        const option = document.createElement('option');
                        option.value = task.id;
                        option.textContent = `${name} (${type})`;
                        newSelect.appendChild(option);
                    });
                    
                    taskSelect.parentNode.replaceChild(newSelect, taskSelect);
                } else if (taskSelect && taskSelect.tagName === 'SELECT') {
                    // Update existing select
                    taskSelect.innerHTML = '<option value="">Select Task...</option>';
                    
                    tasks.forEach(task => {
                        const name = task.businessObject.name || task.id;
                        const type = task.type.replace('bpmn:', '');
                        const option = document.createElement('option');
                        option.value = task.id;
                        option.textContent = `${name} (${type})`;
                        taskSelect.appendChild(option);
                    });
                }
                
                console.log(`Loaded ${tasks.length} process elements for assignment`);
                
            } catch (error) {
                console.error('Failed to load process tasks:', error);
            }
        }

        // Process selector functionality
        document.getElementById('process-select').addEventListener('change', async (e) => {
            const selectedValue = e.target.value;
            
            if (selectedValue === 'new') {
                currentXML = defaultBpmnXml;
                await modeler.importXML(currentXML);
                
                // Fix display after loading
                const canvas = modeler.get('canvas');
                canvas.zoom('fit-viewport');
                setTimeout(() => {
                    canvas.zoom(canvas.zoom());
                }, 100);
                
            } else if (selectedValue) {
                // Load selected process from database
                const selectedOption = e.target.selectedOptions[0];
                const xmlData = selectedOption.dataset.xml;
                
                if (xmlData) {
                    currentXML = xmlData;
                    await modeler.importXML(currentXML);
                    
                    // Fix display after loading
                    const canvas = modeler.get('canvas');
                    canvas.zoom('fit-viewport');
                    setTimeout(() => {
                        canvas.zoom(canvas.zoom());
                    }, 100);
                    
                    console.log('Process loaded:', selectedOption.textContent);
                }
            }
        });

        // Button event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize BPMN when page loads
            initializeBpmn();
            
            // New process button
            document.getElementById('btn-new-process').addEventListener('click', () => {
                document.getElementById('process-select').value = 'new';
                document.getElementById('process-select').dispatchEvent(new Event('change'));
            });

            // Save process button
            document.getElementById('btn-save-process').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    const processName = prompt('Enter process name:') || 'Untitled Process';
                    
                    const formData = new FormData();
                    formData.append('action', 'save_process');
                    formData.append('name', processName);
                    formData.append('xml', xml);
                    formData.append('project_id', '1'); // Default project
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload(); // Reload to update process list
                    }
                    
                } catch (error) {
                    console.error('Save error:', error);
                }
            });

        // Clear designer button
        document.getElementById('btn-clear-designer').addEventListener('click', async () => {
            if (!modeler) return;
            
            // Remove confirmation dialog - just clear directly
            try {
                await modeler.importXML(defaultBpmnXml);
                modeler.get('canvas').zoom('fit-viewport');
                currentXML = defaultBpmnXml;
                
                // Reset process selector
                document.getElementById('process-select').value = 'new';
                
                console.log('Designer cleared successfully');
            } catch (error) {
                console.error('Clear designer error:', error);
            }
        });
            document.getElementById('btn-export-xml').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    currentXML = xml;
                    await loadProcessInViewer(currentXML);
                    alert('Process exported to viewer! 📤');
                } catch (error) {
                    console.error('Export error:', error);
                    alert('Failed to export process ❌');
                }
            });

            // Animate process button
            document.getElementById('btn-animate-path').addEventListener('click', () => {
                animateProcessPath();
            });

            // Clear highlights button
            document.getElementById('btn-clear-highlights').addEventListener('click', () => {
                clearViewerHighlights();
            });

            // Analyze bottlenecks button
            document.getElementById('btn-analyze-bottlenecks').addEventListener('click', () => {
                analyzeBottlenecks();
            });

            // Validate process button
            document.getElementById('btn-validate-process').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    
                    // Basic BPMN validation
                    const elementRegistry = modeler.get('elementRegistry');
                    const elements = elementRegistry.getAll();
                    
                    const startEvents = elements.filter(el => el.type === 'bpmn:StartEvent');
                    const endEvents = elements.filter(el => el.type === 'bpmn:EndEvent');
                    const tasks = elements.filter(el => el.type.includes('Task'));
                    
                    let validationErrors = [];
                    let unconnectedElements = [];
                    
                    if (startEvents.length === 0) {
                        validationErrors.push('❌ Missing Start Event');
                    }
                    if (endEvents.length === 0) {
                        validationErrors.push('❌ Missing End Event');
                    }
                    if (startEvents.length > 1) {
                        validationErrors.push('⚠️ Multiple Start Events found');
                    }
                    
                    // Check for unconnected BPMN flow elements only (exclude labels, lanes, participants, etc.)
                    const flowElements = elements.filter(el => {
                        return el.type === 'bpmn:StartEvent' ||
                               el.type === 'bpmn:EndEvent' ||
                               el.type === 'bpmn:Task' ||
                               el.type === 'bpmn:UserTask' ||
                               el.type === 'bpmn:ServiceTask' ||
                               el.type === 'bpmn:ScriptTask' ||
                               el.type === 'bpmn:BusinessRuleTask' ||
                               el.type === 'bpmn:SendTask' ||
                               el.type === 'bpmn:ReceiveTask' ||
                               el.type === 'bpmn:ManualTask' ||
                               el.type === 'bpmn:ExclusiveGateway' ||
                               el.type === 'bpmn:ParallelGateway' ||
                               el.type === 'bpmn:InclusiveGateway' ||
                               el.type === 'bpmn:EventBasedGateway' ||
                               el.type === 'bpmn:IntermediateCatchEvent' ||
                               el.type === 'bpmn:IntermediateThrowEvent' ||
                               el.type === 'bpmn:SubProcess';
                    });
                    
                    const unconnected = flowElements.filter(el => {
                        const bo = el.businessObject;
                        const hasIncoming = bo.incoming && bo.incoming.length > 0;
                        const hasOutgoing = bo.outgoing && bo.outgoing.length > 0;
                        
                        if (el.type === 'bpmn:StartEvent') return !hasOutgoing;
                        if (el.type === 'bpmn:EndEvent') return !hasIncoming;
                        return !hasIncoming || !hasOutgoing;
                    });
                    
                    // Clear previous highlights
                    elements.forEach(element => {
                        const gfx = modeler.get('elementRegistry').getGraphics(element);
                        if (gfx) {
                            gfx.classList.remove('validation-error');
                        }
                    });
                    
                    // Highlight unconnected elements
                    unconnected.forEach(element => {
                        const gfx = modeler.get('elementRegistry').getGraphics(element);
                        if (gfx) {
                            gfx.classList.add('validation-error');
                        }
                        unconnectedElements.push({
                            id: element.id,
                            name: element.businessObject.name || element.id,
                            type: element.type.replace('bpmn:', '')
                        });
                    });
                    
                    if (unconnected.length > 0) {
                        validationErrors.push(`⚠️ ${unconnected.length} unconnected element(s) (highlighted in red)`);
                    }
                    
                    if (validationErrors.length === 0) {
                        // Show success feedback
                        const statusBar = document.querySelector('.status-bar');
                        statusBar.innerHTML = '<span class="token"></span> ✅ <strong>Process validation passed!</strong> All flow elements are properly connected.';
                        statusBar.style.borderLeft = '4px solid var(--macta-green)';
                        statusBar.style.background = '#e8f5e8';
                        
                        console.log('✅ Process validation passed');
                    } else {
                        // Show detailed validation errors
                        let detailedErrors = validationErrors.join('<br>');
                        
                        if (unconnectedElements.length > 0) {
                            detailedErrors += '<br><br><strong>Unconnected Flow Elements:</strong><br>';
                            unconnectedElements.forEach(el => {
                                detailedErrors += `• <strong>${el.name}</strong> (${el.type})<br>`;
                            });
                            detailedErrors += '<br><em>💡 Fix: Connect these elements with sequence flows (arrows)</em>';
                        }
                        
                        const statusBar = document.querySelector('.status-bar');
                        statusBar.innerHTML = `<span class="token"></span> <strong>Validation Issues Found:</strong><br>${detailedErrors}`;
                        statusBar.style.borderLeft = '4px solid var(--macta-red)';
                        statusBar.style.background = '#ffebee';
                        statusBar.style.maxHeight = 'none';
                        statusBar.style.padding = '15px';
                        
                        console.log('Validation errors:', validationErrors.join('\n'));
                        console.log('Unconnected flow elements:', unconnectedElements);
                    }
                    
                    // Reset status bar after 10 seconds
                    setTimeout(() => {
                        const statusBar = document.querySelector('.status-bar');
                        statusBar.innerHTML = '<span class="token"></span> Use the toolbar above to create and edit your process models.';
                        statusBar.style.borderLeft = '4px solid var(--macta-orange)';
                        statusBar.style.background = '#f8f9fa';
                        statusBar.style.maxHeight = '';
                        statusBar.style.padding = '10px 15px';
                        
                        // Clear highlights
                        elements.forEach(element => {
                            const gfx = modeler.get('elementRegistry').getGraphics(element);
                            if (gfx) {
                                gfx.classList.remove('validation-error');
                            }
                        });
                    }, 10000);
                    
                } catch (error) {
                    console.error('Validation error:', error);
                }
            });

            // Viewer process selector functionality
            document.getElementById('viewer-process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                console.log('Viewer process selector changed:', selectedValue);
                
                if (selectedValue) {
                    // Show loading state
                    const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                    if (viewerLoading) {
                        viewerLoading.style.display = 'flex';
                        viewerLoading.innerHTML = '🔄 Loading process...';
                    }
                    
                    // Load selected process directly in viewer
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        await loadProcessInViewerDirectly(xmlData);
                        console.log('Process loaded in viewer:', selectedOption.textContent);
                    } else {
                        console.error('No XML data found for selected process');
                        if (viewerLoading) {
                            viewerLoading.innerHTML = '❌ No process data found';
                        }
                    }
                } else {
                    // Clear viewer
                    const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                    if (viewerLoading) {
                        viewerLoading.style.display = 'flex';
                        viewerLoading.innerHTML = '👆 Select a process from the dropdown above to view it here...';
                    }
                    
                    // Clear viewer content if it exists
                    if (viewer) {
                        try {
                            viewer.clear();
                        } catch (e) {
                            console.warn('Error clearing viewer:', e);
                        }
                    }
                }
            });

            // Refresh viewer button
            document.getElementById('btn-refresh-viewer').addEventListener('click', async () => {
                const selectedValue = document.getElementById('viewer-process-select').value;
                console.log('Refresh viewer clicked, selected:', selectedValue);
                
                if (selectedValue) {
                    const selectedOption = document.getElementById('viewer-process-select').selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        const viewerLoading = document.querySelector('#bpmn-viewer .loading');
                        if (viewerLoading) {
                            viewerLoading.style.display = 'flex';
                            viewerLoading.innerHTML = '🔄 Refreshing...';
                        }
                        
                        await loadProcessInViewerDirectly(xmlData);
                        console.log('Viewer refreshed');
                    }
                } else {
                    console.log('No process selected to refresh');
                    alert('Please select a process first');
                }
            });
            document.getElementById('btn-zoom-in').addEventListener('click', () => {
                if (modeler) {
                    const canvas = modeler.get('canvas');
                    canvas.zoom(canvas.zoom() * 1.2);
                }
            });

            document.getElementById('btn-zoom-out').addEventListener('click', () => {
                if (modeler) {
                    const canvas = modeler.get('canvas');
                    canvas.zoom(canvas.zoom() * 0.8);
                }
            });

            document.getElementById('btn-zoom-fit').addEventListener('click', () => {
                if (modeler) {
                    modeler.get('canvas').zoom('fit-viewport');
                }
            });
            document.getElementById('btn-assign-resource').addEventListener('click', async () => {
                const taskName = document.getElementById('task-name').value;
                const assignedUser = document.getElementById('assigned-user').value;
                const duration = document.getElementById('task-duration').value;
                const skills = document.getElementById('task-skills').value;
                
                if (!taskName || !assignedUser) {
                    alert('Please fill in task name and assigned user! 📝');
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'assign_resource');
                    formData.append('task_name', taskName);
                    formData.append('assigned_user', assignedUser);
                    formData.append('duration', duration);
                    formData.append('skills', skills);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(`✅ Resource assigned successfully!\n\nTask: ${taskName}\nAssigned to: ${assignedUser}\nDuration: ${duration} hours\nRequired Skills: ${skills}`);
                        
                        // Clear form
                        document.getElementById('task-name').value = '';
                        document.getElementById('assigned-user').value = '';
                        document.getElementById('task-duration').value = '';
                        document.getElementById('task-skills').value = '';
                    } else {
                        alert('Failed to assign resource: ' + result.message);
                    }
                    
                } catch (error) {
                    console.error('Assignment error:', error);
                    alert('Failed to assign resource ❌');
                }
            });

            // Simulation controls
            document.getElementById('btn-start-simulation').addEventListener('click', () => {
                startSimulation();
            });

            document.getElementById('btn-pause-simulation').addEventListener('click', () => {
                pauseSimulation();
            });

            document.getElementById('btn-stop-simulation').addEventListener('click', () => {
                stopSimulation();
            });
        });

        // Animation functions with improved styling
        function clearViewerHighlights() {
            if (!viewer) return;
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = viewer.get('elementRegistry').getGraphics(element);
                    if (gfx) {
                        gfx.classList.remove('simulation-highlight', 'bottleneck-highlight');
                    }
                });
                
                console.log('Cleared all highlights');
            } catch (error) {
                console.error('Failed to clear highlights:', error);
            }
        }

        async function animateProcessPath() {
            if (!viewer) {
                return;
            }
            
            clearViewerHighlights();
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Find start event
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                if (!startEvent) {
                    return;
                }
                
                console.log('Starting animation from:', startEvent.id);
                await highlightPath(startEvent, elementRegistry, 1200);
                
            } catch (error) {
                console.error('Animation error:', error);
            }
        }

        // Animation functions with proper stopping
        function clearViewerHighlights() {
            console.log('Stopping animation and clearing highlights...');
            
            // Stop any running animation
            if (animationInterval) {
                clearInterval(animationInterval);
                animationInterval = null;
            }
            
            if (currentAnimationTimeout) {
                clearTimeout(currentAnimationTimeout);
                currentAnimationTimeout = null;
            }
            
            if (!viewer) {
                console.log('No viewer available for clearing highlights');
                return;
            }
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = viewer.get('elementRegistry').getGraphics(element);
                    if (gfx) {
                        gfx.classList.remove('simulation-highlight', 'bottleneck-highlight');
                    }
                });
                
                console.log('✅ Animation stopped and all highlights cleared');
            } catch (error) {
                console.error('Failed to clear highlights:', error);
            }
        }

        async function animateProcessPath() {
            console.log('Starting process animation...');
            
            // Clear any existing animation first
            clearViewerHighlights();
            
            if (!viewer) {
                console.log('No viewer available for animation');
                return;
            }
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Find start event
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                if (!startEvent) {
                    console.log('No start event found in the process');
                    return;
                }
                
                console.log('Starting animation from:', startEvent.id);
                await highlightPath(startEvent, elementRegistry, 1000);
                
            } catch (error) {
                console.error('Animation error:', error);
            }
        }

        async function highlightPath(currentElement, elementRegistry, delay) {
            if (!currentElement || !viewer) return;
            
            console.log('Highlighting element:', currentElement.id, currentElement.type);
            
            // Highlight current element
            try {
                const gfx = viewer.get('elementRegistry').getGraphics(currentElement);
                if (gfx) {
                    gfx.classList.add('simulation-highlight');
                    
                    // Update active tokens counter
                    const activeTokens = document.querySelectorAll('.simulation-highlight').length;
                    document.getElementById('active-tokens').textContent = activeTokens;
                }
            } catch (error) {
                console.log('Element highlighting failed:', currentElement.id, error);
            }
            
            // Use timeout for delay that can be cancelled
            currentAnimationTimeout = setTimeout(async () => {
                // Find next element - improved path selection
                const outgoing = currentElement.businessObject?.outgoing;
                if (outgoing && outgoing.length > 0) {
                    
                    // For gateways, randomly choose a path to simulate different scenarios
                    let selectedFlows = outgoing;
                    if (currentElement.type === 'bpmn:ExclusiveGateway') {
                        // Randomly select one path for exclusive gateways
                        const randomIndex = Math.floor(Math.random() * outgoing.length);
                        selectedFlows = [outgoing[randomIndex]];
                        console.log(`Gateway decision: taking path ${randomIndex + 1} of ${outgoing.length}`);
                    }
                    
                    for (const flow of selectedFlows) {
                        const nextElement = elementRegistry.get(flow.targetRef?.id);
                        
                        // Highlight the flow
                        try {
                            const flowElement = elementRegistry.get(flow.id);
                            if (flowElement) {
                                const flowGfx = viewer.get('elementRegistry').getGraphics(flowElement);
                                if (flowGfx) {
                                    flowGfx.classList.add('simulation-highlight');
                                }
                            }
                        } catch (error) {
                            console.log('Flow highlighting failed:', flow.id, error);
                        }
                        
                        currentAnimationTimeout = setTimeout(async () => {
                            if (nextElement && nextElement.type !== 'bpmn:EndEvent') {
                                await highlightPath(nextElement, elementRegistry, delay);
                            } else if (nextElement) {
                                // Highlight end event
                                try {
                                    const gfx = viewer.get('elementRegistry').getGraphics(nextElement);
                                    if (gfx) {
                                        gfx.classList.add('simulation-highlight');
                                        
                                        // Increment completed instances
                                        const completed = parseInt(document.getElementById('completed-instances').textContent) + 1;
                                        document.getElementById('completed-instances').textContent = completed;
                                        
                                        console.log('Process instance completed');
                                    }
                                } catch (error) {
                                    console.log('End element highlighting failed:', nextElement.id, error);
                                }
                            }
                        }, delay / 2);
                        
                        // Only process first selected flow to avoid infinite loops
                        break;
                    }
                }
            }, delay);
        }

        function analyzeBottlenecks() {
            if (!viewer) {
                return;
            }
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const tasks = elementRegistry.filter(el => el.type === 'bpmn:Task' || el.type === 'bpmn:UserTask' || el.type === 'bpmn:ServiceTask');
                
                if (tasks.length === 0) {
                    return;
                }
                
                clearViewerHighlights();
                
                // Analyze multiple potential bottlenecks
                const bottleneckCount = Math.min(3, tasks.length);
                const bottlenecks = [];
                
                for (let i = 0; i < bottleneckCount; i++) {
                    const randomTask = tasks[Math.floor(Math.random() * tasks.length)];
                    if (!bottlenecks.includes(randomTask)) {
                        bottlenecks.push(randomTask);
                        
                        // Highlight bottleneck
                        try {
                            const gfx = viewer.get('elementRegistry').getGraphics(randomTask);
                            if (gfx) {
                                gfx.classList.add('bottleneck-highlight');
                            }
                        } catch (error) {
                            console.log('Bottleneck highlighting failed:', randomTask.id, error);
                        }
                    }
                }
                
                const bottleneckNames = bottlenecks.map(task => task.businessObject.name || task.id).join('\n• ');
                
                console.log(`⚠️ Potential Bottlenecks Detected:\n\n• ${bottleneckNames}\n\nRecommendations:\n• Review resource allocation\n• Consider parallel processing\n• Implement automation\n• Optimize task duration\n• Add additional resources`);
                
            } catch (error) {
                console.error('Bottleneck analysis error:', error);
            }
        }

        // Enhanced simulation functions
        function startSimulation() {
            if (simulationActive) return;
            
            // Load current process into simulation viewer
            if (simulationViewer && currentXML) {
                simulationViewer.importXML(currentXML).then(() => {
                    simulationViewer.get('canvas').zoom('fit-viewport');
                    document.querySelector('#simulation-viewer .loading').style.display = 'none';
                }).catch(err => {
                    console.error('Failed to load process in simulation viewer:', err);
                });
            }
            
            simulationActive = true;
            document.getElementById('btn-start-simulation').textContent = '▶️ Running...';
            document.getElementById('btn-start-simulation').disabled = true;
            
            // Initialize metrics
            let totalTime = 0;
            let activeTokens = 0;
            let completedInstances = 0;
            
            console.log('Starting enhanced simulation...');
            
            // Update metrics every second
            simulationInterval = setInterval(() => {
                totalTime += 1;
                
                // Simulate token flow
                if (Math.random() > 0.6) {
                    activeTokens = Math.max(0, activeTokens + 1);
                }
                
                if (Math.random() > 0.8 && activeTokens > 0) {
                    completedInstances++;
                    activeTokens = Math.max(0, activeTokens - 1);
                }
                
                updateSimulationMetrics(totalTime, activeTokens, completedInstances);
            }, 1000);
            
            // Auto-animate process during simulation using simulation viewer
            let animationStep = 0;
            const animationInterval = setInterval(() => {
                if (simulationActive && simulationViewer) {
                    // Clear previous highlights periodically
                    if (animationStep % 4 === 0) {
                        clearSimulationHighlights();
                    }
                    
                    // Start new animation sequence
                    setTimeout(() => {
                        if (simulationActive) {
                            animateSimulationPath();
                        }
                    }, 500);
                    
                    animationStep++;
                } else {
                    clearInterval(animationInterval);
                }
            }, 3500);
            
            // Simulate process performance analysis
            setTimeout(() => {
                if (simulationActive) {
                    console.log('Running performance analysis...');
                    simulatePerformanceData();
                }
            }, 5000);
        }

        // Animation functions for simulation viewer
        function clearSimulationHighlights() {
            if (!simulationViewer) return;
            
            try {
                const elementRegistry = simulationViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = simulationViewer.get('elementRegistry').getGraphics(element);
                    if (gfx) {
                        gfx.classList.remove('simulation-highlight', 'bottleneck-highlight');
                    }
                });
                
                console.log('Cleared simulation highlights');
            } catch (error) {
                console.error('Failed to clear simulation highlights:', error);
            }
        }

        async function animateSimulationPath() {
            if (!simulationViewer) return;
            
            try {
                const elementRegistry = simulationViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Find start event
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                if (!startEvent) {
                    console.log('No start event found for simulation');
                    return;
                }
                
                await highlightSimulationPath(startEvent, elementRegistry, 1000);
                
            } catch (error) {
                console.error('Simulation animation error:', error);
            }
        }

        async function highlightSimulationPath(currentElement, elementRegistry, delay) {
            if (!currentElement || !simulationViewer) return;
            
            // Highlight current element
            try {
                const gfx = simulationViewer.get('elementRegistry').getGraphics(currentElement);
                if (gfx) {
                    gfx.classList.add('simulation-highlight');
                }
            } catch (error) {
                console.log('Simulation element highlighting failed:', currentElement.id, error);
            }
            
            await new Promise(resolve => setTimeout(resolve, delay));
            
            // Continue path logic similar to main animation but for simulation viewer
            const outgoing = currentElement.businessObject?.outgoing;
            if (outgoing && outgoing.length > 0) {
                let selectedFlows = outgoing;
                if (currentElement.type === 'bpmn:ExclusiveGateway') {
                    const randomIndex = Math.floor(Math.random() * outgoing.length);
                    selectedFlows = [outgoing[randomIndex]];
                }
                
                for (const flow of selectedFlows) {
                    const nextElement = elementRegistry.get(flow.targetRef?.id);
                    
                    if (nextElement && nextElement.type !== 'bpmn:EndEvent') {
                        await highlightSimulationPath(nextElement, elementRegistry, delay);
                    } else if (nextElement) {
                        // Highlight end event
                        try {
                            const gfx = simulationViewer.get('elementRegistry').getGraphics(nextElement);
                            if (gfx) {
                                gfx.classList.add('simulation-highlight');
                            }
                        } catch (error) {
                            console.log('Simulation end element highlighting failed:', nextElement.id, error);
                        }
                    }
                    break;
                }
            }
        }

        function simulatePerformanceData() {
            // Simulate realistic process metrics
            const processes = [
                'Customer Inquiry Processing',
                'Document Verification',
                'Approval Workflow',
                'Payment Processing',
                'Order Fulfillment'
            ];
            
            const randomProcess = processes[Math.floor(Math.random() * processes.length)];
            const avgTime = Math.floor(Math.random() * 300) + 60; // 60-360 seconds
            const efficiency = Math.floor(Math.random() * 40) + 60; // 60-100%
            
            console.log(`Performance Analysis: ${randomProcess} - Avg Time: ${avgTime}s, Efficiency: ${efficiency}%`);
            
            // Update efficiency score
            document.getElementById('efficiency-score').textContent = `${efficiency}%`;
        }

        function pauseSimulation() {
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
            simulationActive = false;
            document.getElementById('btn-start-simulation').textContent = '▶️ Resume';
            document.getElementById('btn-start-simulation').disabled = false;
            
            console.log('Simulation paused');
        }

        function stopSimulation() {
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
            simulationActive = false;
            document.getElementById('btn-start-simulation').textContent = '▶️ Start Simulation';
            document.getElementById('btn-start-simulation').disabled = false;
            
            // Reset metrics
            updateSimulationMetrics(0, 0, 0);
            clearSimulationHighlights();
            
            console.log('Simulation stopped and reset');
        }

        function updateSimulationMetrics(totalTime, activeTokens, completedInstances) {
            // Format time display
            const minutes = Math.floor(totalTime / 60);
            const seconds = totalTime % 60;
            const timeDisplay = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            
            document.getElementById('total-time').textContent = timeDisplay;
            document.getElementById('active-tokens').textContent = activeTokens;
            document.getElementById('completed-instances').textContent = completedInstances;
            
            // Calculate dynamic efficiency score
            if (totalTime > 0 && completedInstances > 0) {
                const efficiency = Math.min(100, Math.round((completedInstances / (totalTime / 30)) * 100));
                document.getElementById('efficiency-score').textContent = `${efficiency}%`;
            }
            
            // Update metric card colors based on performance
            const efficiencyCard = document.querySelector('.metric-card:last-child');
            if (efficiencyCard) {
                const efficiencyValue = parseInt(document.getElementById('efficiency-score').textContent);
                if (efficiencyValue >= 80) {
                    efficiencyCard.style.background = 'linear-gradient(135deg, var(--macta-green), var(--macta-teal))';
                } else if (efficiencyValue >= 60) {
                    efficiencyCard.style.background = 'linear-gradient(135deg, var(--macta-yellow), var(--macta-orange))';
                } else {
                    efficiencyCard.style.background = 'linear-gradient(135deg, var(--macta-red), var(--macta-orange))';
                }
            }
        }
    </script>
</body>
</html>