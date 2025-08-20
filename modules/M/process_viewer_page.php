<?php
// modules/M/process_viewer.php - Process Viewer with Resource Allocation & Timer

// Initialize variables
$processes = [];
$resource_allocations = [];
$timer_sessions = [];
$timer_averages = [];
$db_error = '';

// Database connection using existing pattern
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
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("Process Viewer DB Error: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error)) {
        echo json_encode(['success' => false, 'message' => $db_error]);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'save_resource_allocation':
            try {
                // First, try to insert into resource_allocations table
                $stmt = $pdo->prepare("
                    INSERT INTO resource_allocations 
                    (process_id, task_id, allocation_name, resource_type, cost, processing_time, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    allocation_name = VALUES(allocation_name),
                    resource_type = VALUES(resource_type),
                    cost = VALUES(cost),
                    processing_time = VALUES(processing_time),
                    notes = VALUES(notes),
                    updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([
                    $_POST['process_id'] ?? 0,
                    $_POST['task_id'] ?? '',
                    $_POST['allocation_name'] ?? 'Default Allocation',
                    $_POST['resource_type'] ?? 'human',
                    $_POST['cost'] ?? 0,
                    $_POST['processing_time'] ?? 0,
                    $_POST['notes'] ?? '',
                    $_SESSION['user_id'] ?? 1
                ]);

                // Also insert/update in task_resource_assignments for compatibility with existing system
                $resourceId = $_POST['resource_id'] ?? 1; // Default resource if not specified
                $stmt2 = $pdo->prepare("
                    INSERT INTO task_resource_assignments 
                    (process_id, task_id, resource_id, duration_minutes, complexity_level, priority_level) 
                    VALUES (?, ?, ?, ?, 'moderate', 'normal')
                    ON DUPLICATE KEY UPDATE 
                    duration_minutes = VALUES(duration_minutes),
                    updated_at = CURRENT_TIMESTAMP
                ");
                $stmt2->execute([
                    $_POST['process_id'] ?? 0,
                    $_POST['task_id'] ?? '',
                    $resourceId,
                    $_POST['processing_time'] ?? 0
                ]);

                echo json_encode(['success' => true, 'message' => 'Resource allocation saved successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'load_resource_allocations':
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM resource_allocations 
                    WHERE process_id = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_POST['process_id'] ?? 0]);
                $allocations = $stmt->fetchAll();
                echo json_encode(['success' => true, 'allocations' => $allocations]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'start_timer':
            try {
                // Check if user has active timer
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as active_count 
                    FROM timer_sessions 
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->execute([$_SESSION['user_id'] ?? 1]);
                $result = $stmt->fetch();
                
                if ($result['active_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'You already have an active timer. Please complete or pause it first.']);
                    exit;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO timer_sessions 
                    (user_id, process_id, task_id, start_time, status) 
                    VALUES (?, ?, ?, NOW(), 'active')
                ");
                $stmt->execute([
                    $_SESSION['user_id'] ?? 1,
                    $_POST['process_id'] ?? 0,
                    $_POST['task_id'] ?? ''
                ]);
                
                echo json_encode(['success' => true, 'session_id' => $pdo->lastInsertId(), 'message' => 'Timer started successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'pause_timer':
            try {
                $stmt = $pdo->prepare("
                    UPDATE timer_sessions 
                    SET status = 'paused', updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([
                    $_POST['session_id'] ?? 0,
                    $_SESSION['user_id'] ?? 1
                ]);
                echo json_encode(['success' => true, 'message' => 'Timer paused successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'complete_timer':
            try {
                $stmt = $pdo->prepare("
                    UPDATE timer_sessions 
                    SET status = 'completed', 
                        end_time = NOW(),
                        total_duration = TIMESTAMPDIFF(SECOND, start_time, NOW()) - pause_duration,
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([
                    $_POST['session_id'] ?? 0,
                    $_SESSION['user_id'] ?? 1
                ]);
                
                // Update timer averages
                $stmt = $pdo->prepare("
                    SELECT process_id, task_id, total_duration 
                    FROM timer_sessions 
                    WHERE id = ?
                ");
                $stmt->execute([$_POST['session_id'] ?? 0]);
                $session = $stmt->fetch();
                
                if ($session) {
                    // Calculate new average
                    $stmt = $pdo->prepare("
                        SELECT AVG(total_duration) as avg_duration, COUNT(*) as session_count 
                        FROM timer_sessions 
                        WHERE process_id = ? AND task_id = ? AND status = 'completed'
                    ");
                    $stmt->execute([$session['process_id'], $session['task_id']]);
                    $avg_data = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO timer_averages 
                        (process_id, task_id, average_duration, session_count, last_calculated) 
                        VALUES (?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        average_duration = VALUES(average_duration),
                        session_count = VALUES(session_count),
                        last_calculated = NOW()
                    ");
                    $stmt->execute([
                        $session['process_id'],
                        $session['task_id'],
                        round($avg_data['avg_duration']),
                        $avg_data['session_count']
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Timer completed and average updated']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_timer_averages':
            try {
                $stmt = $pdo->prepare("
                    SELECT task_id, average_duration, session_count, is_overridden, override_value 
                    FROM timer_averages 
                    WHERE process_id = ?
                ");
                $stmt->execute([$_POST['process_id'] ?? 0]);
                $averages = $stmt->fetchAll();
                echo json_encode(['success' => true, 'averages' => $averages]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_active_timer':
            try {
                $stmt = $pdo->prepare("
                    SELECT id, process_id, task_id, start_time, 
                           TIMESTAMPDIFF(SECOND, start_time, NOW()) as elapsed_seconds
                    FROM timer_sessions 
                    WHERE user_id = ? AND status = 'active'
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['user_id'] ?? 1]);
                $timer = $stmt->fetch();
                echo json_encode(['success' => true, 'timer' => $timer]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - Process Viewer with Resource Allocation & Timer</title>

    <style>
        :root {
            /* MACTA Brand Colors */
            --htt-blue: #1E88E5;
            --htt-dark-blue: #1565C0;
            --htt-light-blue: #42A5F5;
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

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .process-viewer {
            height: 500px;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #fafafa;
            margin-bottom: 20px;
            position: relative;
            overflow: auto;
        }

        .process-selector {
            margin-bottom: 20px;
        }

        .process-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--htt-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--htt-dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,136,229,0.3);
        }

        .btn-success {
            background: var(--macta-green);
            color: white;
        }

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        .btn-danger {
            background: var(--macta-red);
            color: white;
        }

        .resource-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--htt-blue);
            outline: none;
            box-shadow: 0 0 0 2px rgba(30,136,229,0.2);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .timer-widget {
            background: white;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .timer-display {
            font-size: 48px;
            font-weight: bold;
            color: var(--htt-blue);
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }

        .timer-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .task-item {
            background: white;
            border: 1px solid var(--macta-light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .task-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--htt-blue);
        }

        .task-item.active-timer {
            border-color: var(--macta-green);
            background: #e8f5e8;
        }

        .user-task-item {
            border-left: 4px solid var(--macta-green);
        }

        .user-task-item:hover {
            border-left-color: var(--macta-green);
            background: #e8f5e8;
        }

        .other-task-item {
            border-left: 4px solid var(--htt-blue);
        }

        .other-task-item:hover {
            border-left-color: var(--htt-blue);
            background: #e3f2fd;
        }

        .running-timer {
            animation: pulse 2s infinite !important;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .allocations-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .allocation-item {
            background: white;
            border: 1px solid var(--macta-light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .allocation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .allocation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 14px;
        }

        .status-bar {
            background: var(--macta-light);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: var(--macta-dark);
            border-left: 4px solid var(--htt-blue);
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            font-size: 18px;
            color: var(--macta-orange);
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                border-right: none !important;
                border-bottom: 1px solid var(--macta-light);
            }
        }
    </style>

    <!-- Include BPMN.js for process visualization -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <div class="macta-logo">M</div>
                MACTA Framework - Process Viewer with Resource Allocation & Timer
            </h1>
            <div>
                <a href="../../index.php" class="btn btn-primary">
                    <span>←</span> Back to Framework
                </a>
            </div>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab active" data-tab="process-view">
                <span class="tab-icon">👁️</span>
                <span>Process View</span>
            </button>
            <button class="nav-tab" data-tab="resource-allocation">
                <span class="tab-icon">👥</span>
                <span>Resource Allocation</span>
            </button>
            <button class="nav-tab" data-tab="timer-tracking">
                <span class="tab-icon">⏱️</span>
                <span>Timer Tracking</span>
            </button>
        </div>

        <!-- Process View Tab -->
        <div id="process-view" class="tab-pane active">
            <div class="tab-content">
                <h2>📊 Process Visualization & Selection</h2>
                
                <!-- Process Selector -->
                <div class="process-selector">
                    <select id="process-select">
                        <option value="">Select a Process...</option>
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

                <!-- Process Viewer -->
                <div id="process-viewer" class="process-viewer">
                    <div class="resource-form">
                        <h3>📈 Timer Averages & Performance</h3>
                        <div id="timer-averages-list">
                            <div class="loading">Select a process to view timer averages</div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>🎯 Quick Actions</h4>
                            <button class="btn btn-primary" id="btn-refresh-averages">
                                🔄 Refresh Averages
                            </button>
                            <button class="btn btn-warning" id="btn-export-times">
                                📤 Export Times
                            </button>
                        </div>
                    </div>
                </div>

                <div class="resource-form">
                    <h3>📋 Available Tasks for Timing</h3>
                    <div id="tasks-list">
                        <div class="loading">Select a process to view available tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BPMN.js script -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js"></script>

    <script>
        // Global variables
        let processViewer = null;
        let currentProcessId = null;
        let currentProcessXML = null;
        let activeTimerInterval = null;
        let activeTimerStartTime = null;
        let activeTimerSessionId = null;
        let activeTaskId = null;
        
        // PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const dbError = <?= json_encode($db_error) ?>;

        // Initialize BPMN Viewer
        function initializeBPMNViewer() {
            try {
                processViewer = new BpmnJS({
                    container: '#process-viewer'
                });
                console.log('✅ BPMN Viewer initialized successfully');
            } catch (error) {
                console.error('Failed to initialize BPMN Viewer:', error);
                document.querySelector('#process-viewer .loading').innerHTML = 'BPMN Viewer initialization failed: ' + error.message;
            }
        }

        // Load process in viewer
        async function loadProcessInViewer(processId, xml) {
            if (!processViewer || !xml) return;
            
            try {
                await processViewer.importXML(xml);
                processViewer.get('canvas').zoom('fit-viewport');
                
                currentProcessId = processId;
                currentProcessXML = xml;
                
                // Hide loading indicator
                const loading = document.querySelector('#process-viewer .loading');
                if (loading) {
                    loading.style.display = 'none';
                }
                
                // Load related data
                loadResourceAllocations(processId);
                loadTimerAverages(processId);
                loadProcessTasks(processId);
                
                // Make tasks clickable for timing
                addTaskClickHandlers();
                
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
            }
        }

        // Add click handlers to tasks for timer functionality and resource allocation
        function addTaskClickHandlers() {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const eventBus = processViewer.get('eventBus');
                
                eventBus.on('element.click', function(event) {
                    const element = event.element;
                    
                    // Handle User Tasks for timer functionality (only User Tasks can be timed)
                    if (element.type === 'bpmn:UserTask') {
                        handleUserTaskClick(element);
                    }
                    // Handle all other tasks/events for resource allocation
                    else if (element.type === 'bpmn:Task' || 
                             element.type === 'bpmn:ServiceTask' ||
                             element.type === 'bpmn:StartEvent' ||
                             element.type === 'bpmn:EndEvent' ||
                             element.type === 'bpmn:ExclusiveGateway' ||
                             element.type === 'bpmn:ParallelGateway') {
                        handleTaskClickForResourceAllocation(element);
                    }
                });
                
            } catch (error) {
                console.error('Failed to add task click handlers:', error);
            }
        }

        // Handle User Task clicks for timer functionality
        function handleUserTaskClick(element) {
            const taskId = element.id;
            const taskName = element.businessObject.name || element.id;
            
            if (activeTimerSessionId) {
                return; // Silently return if timer already active
            }
            
            // Show timer controls in current view
            showInlineTimerControls(taskId, taskName);
            
            // Highlight the selected task
            highlightSelectedTask(taskId);
        }

        // Handle other tasks/events for resource allocation
        function handleTaskClickForResourceAllocation(element) {
            const taskId = element.id;
            const taskName = element.businessObject.name || element.id;
            const taskType = element.type.replace('bpmn:', '');
            
            // Fill resource allocation form and switch to that tab
            document.getElementById('task-id').value = taskId;
            document.getElementById('allocation-name').value = `${taskName} Assignment`;
            
            // Switch to resource allocation tab
            switchTab('resource-allocation');
            
            // Highlight the form for better UX
            const form = document.querySelector('.resource-form');
            form.style.border = '3px solid var(--htt-blue)';
            setTimeout(() => {
                form.style.border = '';
            }, 2000);
        }

        // Handle User Task resource allocation (with average loading)
        function handleUserTaskResourceAllocation(element) {
            const taskId = element.id;
            const taskName = element.businessObject.name || element.id;
            
            // Fill resource allocation form
            document.getElementById('task-id').value = taskId;
            document.getElementById('allocation-name').value = `${taskName} Assignment`;
            
            // Load average processing time if available
            loadAverageForTask(taskId);
            
            // Switch to resource allocation tab
            switchTab('resource-allocation');
            
            // Highlight the form for better UX
            const form = document.querySelector('.resource-form');
            form.style.border = '3px solid var(--macta-green)';
            setTimeout(() => {
                form.style.border = '';
            }, 2000);
        }

        // Show inline timer controls in the current view
        function showInlineTimerControls(taskId, taskName) {
            activeTaskId = taskId;
            
            // Create or update inline timer widget
            let timerWidget = document.getElementById('inline-timer-widget');
            if (!timerWidget) {
                timerWidget = document.createElement('div');
                timerWidget.id = 'inline-timer-widget';
                timerWidget.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border: 3px solid var(--macta-green);
                    border-radius: 15px;
                    padding: 20px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    z-index: 1000;
                    min-width: 300px;
                    text-align: center;
                `;
                document.body.appendChild(timerWidget);
            }
            
            timerWidget.innerHTML = `
                <h4 style="margin: 0 0 10px 0; color: var(--macta-green);">⏱️ User Task Timer</h4>
                <div style="font-weight: bold; margin-bottom: 5px;">${taskName}</div>
                <div style="font-size: 12px; color: #666; margin-bottom: 15px;">ID: ${taskId}</div>
                <div id="inline-timer-display" style="font-size: 36px; font-weight: bold; color: var(--htt-blue); font-family: 'Courier New', monospace; margin: 15px 0;">00:00:00</div>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button id="inline-btn-start" class="btn btn-success" onclick="startInlineTimer()">
                        ▶️ Start
                    </button>
                    <button id="inline-btn-pause" class="btn btn-warning" onclick="pauseInlineTimer()" disabled>
                        ⏸️ Pause
                    </button>
                    <button id="inline-btn-complete" class="btn btn-danger" onclick="completeInlineTimer()" disabled>
                        ✅ Complete
                    </button>
                    <button id="inline-btn-resources" class="btn btn-primary" onclick="assignUserTaskResources('${taskId}', '${taskName}')">
                        👥 Resources
                    </button>
                    <button id="inline-btn-close" class="btn btn-secondary" onclick="closeInlineTimer()">
                        ❌ Close
                    </button>
                </div>
            `;
            
            // Show the widget with animation
            timerWidget.style.transform = 'translateX(100%)';
            setTimeout(() => {
                timerWidget.style.transition = 'transform 0.3s ease';
                timerWidget.style.transform = 'translateX(0)';
            }, 10);
        }

        // Close inline timer widget
        function closeInlineTimer() {
            const timerWidget = document.getElementById('inline-timer-widget');
            if (timerWidget) {
                timerWidget.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    timerWidget.remove();
                }, 300);
            }
            
            // Clear task highlighting
            if (activeTaskId) {
                clearTaskHighlight(activeTaskId);
                activeTaskId = null;
            }
        }

        function resetInlineTimerUI() {
            activeTimerSessionId = null;
            activeTimerStartTime = null;
            activeTaskId = null;
        }

        // Inline timer functions
        async function startInlineTimer() {
            if (!currentProcessId || !activeTaskId) {
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=start_timer&process_id=${currentProcessId}&task_id=${activeTaskId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    activeTimerSessionId = result.session_id;
                    activeTimerStartTime = new Date();
                    
                    // Update inline UI
                    document.getElementById('inline-btn-start').disabled = true;
                    document.getElementById('inline-btn-pause').disabled = false;
                    document.getElementById('inline-btn-complete').disabled = false;
                    
                    // Start timer display
                    startInlineTimerDisplay();
                    
                    // Highlight task in viewer with running timer style
                    highlightRunningTask(activeTaskId);
                }
            } catch (error) {
                console.error('Timer start error:', error);
            }
        }

        async function pauseInlineTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=pause_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    stopInlineTimerDisplay();
                    clearTaskHighlight(activeTaskId);
                    resetInlineTimerUI();
                    closeInlineTimer();
                }
            } catch (error) {
                console.error('Timer pause error:', error);
            }
        }

        async function completeInlineTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=complete_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    stopInlineTimerDisplay();
                    clearTaskHighlight(activeTaskId);
                    resetInlineTimerUI();
                    closeInlineTimer();
                    
                    // Refresh timer averages
                    if (currentProcessId) {
                        loadTimerAverages(currentProcessId);
                    }
                }
            } catch (error) {
                console.error('Timer complete error:', error);
            }
        }

        // Inline timer display functions
        function startInlineTimerDisplay() {
            activeTimerInterval = setInterval(() => {
                if (activeTimerStartTime) {
                    const elapsed = new Date() - activeTimerStartTime;
                    const hours = Math.floor(elapsed / 3600000);
                    const minutes = Math.floor((elapsed % 3600000) / 60000);
                    const seconds = Math.floor((elapsed % 60000) / 1000);
                    
                    const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    const inlineDisplay = document.getElementById('inline-timer-display');
                    if (inlineDisplay) {
                        inlineDisplay.textContent = display;
                    }
                    
                    // Also update main timer display if on that tab
                    const mainDisplay = document.getElementById('timer-display');
                    if (mainDisplay) {
                        mainDisplay.textContent = display;
                    }
                }
            }, 1000);
        }

        function stopInlineTimerDisplay() {
            if (activeTimerInterval) {
                clearInterval(activeTimerInterval);
                activeTimerInterval = null;
            }
        }

        // Assign resources for User Task (with average loading)
        function assignUserTaskResources(taskId, taskName) {
            handleUserTaskResourceAllocation({id: taskId, businessObject: {name: taskName}});
        }

        // Load average processing time for a specific task
        async function loadAverageForTask(taskId) {
            if (!currentProcessId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_timer_averages&process_id=${currentProcessId}`
                });
                
                const result = await response.json();
                
                if (result.success && result.averages) {
                    const taskAverage = result.averages.find(avg => avg.task_id === taskId);
                    if (taskAverage) {
                        const minutes = Math.floor(taskAverage.average_duration / 60);
                        document.getElementById('processing-time').value = minutes;
                        
                        // Add visual indication that this is loaded from average
                        const processingTimeInput = document.getElementById('processing-time');
                        processingTimeInput.style.backgroundColor = '#e8f5e8';
                        processingTimeInput.title = `Loaded from average (${taskAverage.session_count} sessions)`;
                        
                        setTimeout(() => {
                            processingTimeInput.style.backgroundColor = '';
                        }, 3000);
                    }
                }
            } catch (error) {
                console.error('Failed to load average for task:', error);
            }
        }

        // Timer functions
        async function startTimer() {
            if (!currentProcessId || !activeTaskId) {
                alert('Please select a process and task first.');
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=start_timer&process_id=${currentProcessId}&task_id=${activeTaskId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    activeTimerSessionId = result.session_id;
                    activeTimerStartTime = new Date();
                    
                    // Update UI
                    document.getElementById('btn-start-timer').disabled = true;
                    document.getElementById('btn-pause-timer').disabled = false;
                    document.getElementById('btn-complete-timer').disabled = false;
                    
                    // Start timer display
                    startTimerDisplay();
                    
                    // Highlight task in viewer
                    highlightActiveTask(activeTaskId);
                    
                    alert('✅ Timer started successfully!');
                } else {
                    alert('❌ Failed to start timer: ' + result.message);
                }
            } catch (error) {
                console.error('Timer start error:', error);
                alert('❌ Error starting timer');
            }
        }

        async function pauseTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=pause_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    stopTimerDisplay();
                    clearTaskHighlight(activeTaskId);
                    resetTimerUI();
                    alert('⏸️ Timer paused successfully!');
                } else {
                    alert('❌ Failed to pause timer: ' + result.message);
                }
            } catch (error) {
                console.error('Timer pause error:', error);
            }
        }

        async function completeTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=complete_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    stopTimerDisplay();
                    clearTaskHighlight(activeTaskId);
                    resetTimerUI();
                    
                    // Refresh timer averages
                    if (currentProcessId) {
                        loadTimerAverages(currentProcessId);
                    }
                    
                    alert('✅ Timer completed and averages updated!');
                } else {
                    alert('❌ Failed to complete timer: ' + result.message);
                }
            } catch (error) {
                console.error('Timer complete error:', error);
            }
        }

        // Timer display functions
        function startTimerDisplay() {
            activeTimerInterval = setInterval(() => {
                if (activeTimerStartTime) {
                    const elapsed = new Date() - activeTimerStartTime;
                    const hours = Math.floor(elapsed / 3600000);
                    const minutes = Math.floor((elapsed % 3600000) / 60000);
                    const seconds = Math.floor((elapsed % 60000) / 1000);
                    
                    const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    document.getElementById('timer-display').textContent = display;
                }
            }, 1000);
        }

        function stopTimerDisplay() {
            if (activeTimerInterval) {
                clearInterval(activeTimerInterval);
                activeTimerInterval = null;
            }
        }

        function resetTimerUI() {
            activeTimerSessionId = null;
            activeTimerStartTime = null;
            activeTaskId = null;
            
            document.getElementById('timer-display').textContent = '00:00:00';
            document.getElementById('timer-task-name').textContent = 'No active timer';
            document.getElementById('timer-process-name').textContent = '-';
            
            document.getElementById('btn-start-timer').disabled = true;
            document.getElementById('btn-pause-timer').disabled = true;
            document.getElementById('btn-complete-timer').disabled = true;
        }

        // Visual highlighting functions
        function highlightSelectedTask(taskId) {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const element = elementRegistry.get(taskId);
                
                if (element) {
                    const gfx = elementRegistry.getGraphics(element);
                    if (gfx) {
                        gfx.style.stroke = '#1E88E5';
                        gfx.style.strokeWidth = '3px';
                        gfx.style.fill = '#e3f2fd';
                    }
                }
            } catch (error) {
                console.error('Failed to highlight selected task:', error);
            }
        }

        function highlightRunningTask(taskId) {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const element = elementRegistry.get(taskId);
                
                if (element) {
                    const gfx = elementRegistry.getGraphics(element);
                    if (gfx) {
                        gfx.style.stroke = '#00b894';
                        gfx.style.strokeWidth = '4px';
                        gfx.style.fill = '#e8f5e8';
                        gfx.style.animation = 'pulse 2s infinite';
                        gfx.classList.add('running-timer');
                    }
                }
            } catch (error) {
                console.error('Failed to highlight running task:', error);
            }
        }

        function highlightActiveTask(taskId) {
            highlightRunningTask(taskId); // Use the running task highlight
        }

        function clearTaskHighlight(taskId) {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const element = elementRegistry.get(taskId);
                
                if (element) {
                    const gfx = elementRegistry.getGraphics(element);
                    if (gfx) {
                        gfx.style.stroke = '';
                        gfx.style.strokeWidth = '';
                        gfx.style.fill = '';
                        gfx.style.animation = '';
                        gfx.classList.remove('running-timer');
                    }
                }
            } catch (error) {
                console.error('Failed to clear task highlight:', error);
            }
        }

        // Resource allocation functions
        async function saveResourceAllocation() {
            const processId = document.getElementById('allocation-process-select').value;
            const taskId = document.getElementById('task-id').value;
            const allocationName = document.getElementById('allocation-name').value;
            const resourceType = document.getElementById('resource-type').value;
            const cost = document.getElementById('cost').value;
            const processingTime = document.getElementById('processing-time').value;
            const notes = document.getElementById('notes').value;
            
            if (!processId || !taskId) {
                alert('Please select a process and enter a task ID.');
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=save_resource_allocation&process_id=${processId}&task_id=${taskId}&allocation_name=${encodeURIComponent(allocationName)}&resource_type=${resourceType}&cost=${cost}&processing_time=${processingTime}&notes=${encodeURIComponent(notes)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Resource allocation saved successfully!');
                    loadResourceAllocations(processId);
                    
                    // Clear form
                    document.getElementById('task-id').value = '';
                    document.getElementById('allocation-name').value = '';
                    document.getElementById('notes').value = '';
                } else {
                    alert('❌ Failed to save allocation: ' + result.message);
                }
            } catch (error) {
                console.error('Save allocation error:', error);
                alert('❌ Error saving allocation');
            }
        }

        async function loadResourceAllocations(processId) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=load_resource_allocations&process_id=${processId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayResourceAllocations(result.allocations);
                } else {
                    console.error('Failed to load allocations:', result.message);
                }
            } catch (error) {
                console.error('Load allocations error:', error);
            }
        }

        function displayResourceAllocations(allocations) {
            const container = document.getElementById('allocations-list');
            
            if (allocations.length === 0) {
                container.innerHTML = '<div class="loading">No resource allocations found for this process</div>';
                return;
            }
            
            let html = '';
            allocations.forEach(allocation => {
                const resourceIcon = allocation.resource_type === 'human' ? '👤' : 
                                   allocation.resource_type === 'machine' ? '🤖' : '⚡';
                
                html += `
                    <div class="allocation-item">
                        <div class="allocation-header">
                            <strong>${resourceIcon} ${allocation.allocation_name || 'Unnamed Allocation'}</strong>
                            <span style="color: #666;">Task: ${allocation.task_id}</span>
                        </div>
                        <div class="allocation-details">
                            <div><strong>Type:</strong> ${allocation.resource_type}</div>
                            <div><strong>Cost:</strong> ${allocation.cost}</div>
                            <div><strong>Time:</strong> ${allocation.processing_time} min</div>
                            <div><strong>Created:</strong> ${new Date(allocation.created_at).toLocaleDateString()}</div>
                        </div>
                        ${allocation.notes ? `<div style="margin-top: 10px; font-style: italic; color: #666;">${allocation.notes}</div>` : ''}
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function loadTimerAverages(processId) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_timer_averages&process_id=${processId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayTimerAverages(result.averages);
                } else {
                    console.error('Failed to load timer averages:', result.message);
                }
            } catch (error) {
                console.error('Load timer averages error:', error);
            }
        }

        function displayTimerAverages(averages) {
            const container = document.getElementById('timer-averages-list');
            
            if (averages.length === 0) {
                container.innerHTML = '<div class="loading">No timer data available for this process</div>';
                return;
            }
            
            let html = '';
            averages.forEach(avg => {
                const minutes = Math.floor(avg.average_duration / 60);
                const seconds = avg.average_duration % 60;
                const timeDisplay = `${minutes}m ${seconds}s`;
                
                // Get task name from the process viewer if available
                let taskName = avg.task_id;
                if (processViewer) {
                    try {
                        const elementRegistry = processViewer.get('elementRegistry');
                        const element = elementRegistry.get(avg.task_id);
                        if (element && element.businessObject.name) {
                            taskName = element.businessObject.name;
                        }
                    } catch (error) {
                        // Keep default task_id as name if element not found
                    }
                }
                
                html += `
                    <div class="allocation-item">
                        <div class="allocation-header">
                            <div>
                                <strong>⏱️ ${taskName}</strong><br>
                                <small style="color: #666; font-size: 12px;">ID: ${avg.task_id}</small>
                            </div>
                            <span style="color: #666;">${avg.session_count} sessions</span>
                        </div>
                        <div class="allocation-details">
                            <div><strong>Average Time:</strong> ${timeDisplay}</div>
                            <div><strong>Status:</strong> ${avg.is_overridden ? 'Overridden' : 'Calculated'}</div>
                            ${avg.override_value ? `<div><strong>Override:</strong> ${Math.floor(avg.override_value / 60)}m ${avg.override_value % 60}s</div>` : ''}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function loadProcessTasks(processId) {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Separate User Tasks (for timing) and other tasks (for resource allocation)
                const userTasks = elements.filter(el => el.type === 'bpmn:UserTask');
                const otherTasks = elements.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:ServiceTask' ||
                    el.type === 'bpmn:StartEvent' ||
                    el.type === 'bpmn:EndEvent' ||
                    el.type === 'bpmn:ExclusiveGateway' ||
                    el.type === 'bpmn:ParallelGateway'
                );
                
                const container = document.getElementById('tasks-list');
                
                if (userTasks.length === 0 && otherTasks.length === 0) {
                    container.innerHTML = '<div class="loading">No tasks found in this process</div>';
                    return;
                }
                
                let html = '';
                
                // User Tasks section (for timing)
                if (userTasks.length > 0) {
                    html += '<h4 style="color: var(--macta-green); margin-bottom: 15px;">⏱️ User Tasks (Click to Start Timer)</h4>';
                    userTasks.forEach(task => {
                        const name = task.businessObject.name || task.id;
                        html += `
                            <div class="task-item user-task-item" onclick="handleUserTaskClick({id: '${task.id}', businessObject: {name: '${name}'}})">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>👤 ${name}</strong><br>
                                        <small style="color: #666;">User Task | ID: ${task.id}</small>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button class="btn btn-success" style="margin: 0;">
                                            ⏱️ Start Timer
                                        </button>
                                        <button class="btn btn-primary" style="margin: 0;" onclick="event.stopPropagation(); handleUserTaskResourceAllocation({id: '${task.id}', businessObject: {name: '${name}'}})">
                                            👥 Resources
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                // Other Tasks section (for resource allocation)
                if (otherTasks.length > 0) {
                    html += '<h4 style="color: var(--htt-blue); margin-bottom: 15px; margin-top: 25px;">📋 Other Tasks & Events (Click to Assign Resources)</h4>';
                    otherTasks.forEach(task => {
                        const name = task.businessObject.name || task.id;
                        const type = task.type.replace('bpmn:', '');
                        const icon = type === 'StartEvent' ? '🟢' : 
                                   type === 'EndEvent' ? '🔴' : 
                                   type === 'ExclusiveGateway' ? '💎' :
                                   type === 'ParallelGateway' ? '➕' :
                                   '📋';
                        
                        html += `
                            <div class="task-item other-task-item" onclick="handleTaskClickForResourceAllocation({id: '${task.id}', businessObject: {name: '${name}'}, type: '${task.type}'})">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>${icon} ${name}</strong><br>
                                        <small style="color: #666;">Type: ${type} | ID: ${task.id}</small>
                                    </div>
                                    <button class="btn btn-primary" style="margin: 0;">
                                        👥 Assign Resources
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                
                container.innerHTML = html;
                
                // Also populate the resource allocation dropdown
                populateTaskDropdown(elements);
                
            } catch (error) {
                console.error('Failed to load process tasks:', error);
            }
        }

        // Populate the task dropdown in resource allocation tab
        function populateTaskDropdown(elements) {
            const taskSelect = document.getElementById('task-dropdown');
            if (!taskSelect) return;
            
            let html = '<option value="">Select Task for Resource Allocation...</option>';
            
            elements.forEach(element => {
                if (element.type === 'bpmn:Task' || 
                    element.type === 'bpmn:UserTask' || 
                    element.type === 'bpmn:ServiceTask' ||
                    element.type === 'bpmn:StartEvent' ||
                    element.type === 'bpmn:EndEvent' ||
                    element.type === 'bpmn:ExclusiveGateway' ||
                    element.type === 'bpmn:ParallelGateway') {
                    
                    const name = element.businessObject.name || element.id;
                    const type = element.type.replace('bpmn:', '');
                    const icon = type === 'UserTask' ? '👤' :
                               type === 'StartEvent' ? '🟢' : 
                               type === 'EndEvent' ? '🔴' : 
                               type === 'ExclusiveGateway' ? '💎' :
                               type === 'ParallelGateway' ? '➕' :
                               '📋';
                    
                    html += `<option value="${element.id}">${icon} ${name} (${type})</option>`;
                }
            });
            
            taskSelect.innerHTML = html;
        }

        // Tab switching
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.nav-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            const targetTabButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (targetTabButton) {
                targetTabButton.classList.add('active');
            }

            // Update tab content
            document.querySelectorAll('.tab-pane').forEach(content => {
                content.classList.remove('active');
            });
            const targetPane = document.getElementById(tabName);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        }

        // Check for active timer on load
        async function checkActiveTimer() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_active_timer'
                });
                
                const result = await response.json();
                
                if (result.success && result.timer) {
                    const timer = result.timer;
                    activeTimerSessionId = timer.id;
                    activeTimerStartTime = new Date(Date.now() - (timer.elapsed_seconds * 1000));
                    activeTaskId = timer.task_id;
                    
                    // Update UI
                    document.getElementById('timer-task-name').textContent = timer.task_id;
                    document.getElementById('btn-pause-timer').disabled = false;
                    document.getElementById('btn-complete-timer').disabled = false;
                    
                    startTimerDisplay();
                    
                    if (currentProcessId && timer.process_id == currentProcessId) {
                        highlightActiveTask(timer.task_id);
                    }
                }
            } catch (error) {
                console.error('Failed to check active timer:', error);
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 MACTA Process Viewer with Resource Allocation & Timer initialized');
            
            // Initialize BPMN viewer
            initializeBPMNViewer();
            
            // Tab switching
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    switchTab(targetTab);
                });
            });
            
            // Process selector
            document.getElementById('process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                
                if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        await loadProcessInViewer(selectedValue, xmlData);
                        
                        // Update allocation process selector
                        document.getElementById('allocation-process-select').value = selectedValue;
                    }
                } else {
                    const loading = document.querySelector('#process-viewer .loading');
                    if (loading) {
                        loading.style.display = 'flex';
                        loading.innerHTML = '👆 Select a process from the dropdown above to view it here...';
                    }
                }
            });
            
            // Resource allocation events
            document.getElementById('allocation-process-select').addEventListener('change', (e) => {
                const processId = e.target.value;
                if (processId) {
                    loadResourceAllocations(processId);
                    loadTimerAverages(processId);
                }
            });
            
            document.getElementById('btn-save-allocation').addEventListener('click', saveResourceAllocation);
            
            document.getElementById('btn-apply-all').addEventListener('click', () => {
                if (confirm('Apply current resource settings to all tasks in the process?')) {
                    // TODO: Implement apply to all functionality
                    alert('🚧 Apply to all functionality will be implemented');
                }
            });
            
            // Timer events
            document.getElementById('btn-start-timer').addEventListener('click', startTimer);
            document.getElementById('btn-pause-timer').addEventListener('click', pauseTimer);
            document.getElementById('btn-complete-timer').addEventListener('click', completeTimer);
            
            document.getElementById('btn-refresh-averages').addEventListener('click', () => {
                if (currentProcessId) {
                    loadTimerAverages(currentProcessId);
                }
            });
            
            // Task dropdown change event
            document.getElementById('task-dropdown').addEventListener('change', (e) => {
                const selectedTaskId = e.target.value;
                if (selectedTaskId) {
                    document.getElementById('task-id').value = selectedTaskId;
                    
                    // Auto-fill allocation name
                    const selectedOption = e.target.selectedOptions[0];
                    const taskName = selectedOption.textContent.split(' (')[0].replace(/^[🟢🔴💎➕📋👤] /, '');
                    document.getElementById('allocation-name').value = `${taskName} Assignment`;
                }
            });
            
            // Check for active timer on page load
            checkActiveTimer();
            
            console.log('✅ All event listeners attached successfully!');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        if (activeTaskId && !activeTimerSessionId) {
                            startTimer();
                        } else if (activeTimerSessionId) {
                            pauseTimer();
                        }
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (activeTimerSessionId) {
                            completeTimer();
                        }
                        break;
                }
            }
            
            // Tab switching with numbers
            if (e.key >= '1' && e.key <= '3') {
                const tabs = ['process-view', 'resource-allocation', 'timer-tracking'];
                const tabIndex = parseInt(e.key) - 1;
                if (tabs[tabIndex]) {
                    switchTab(tabs[tabIndex]);
                }
            }
        });
    </script>
</body>
</html>loading">👆 Select a process from the dropdown above to view it here...</div>
                </div>

                <div class="status-bar">
                    <span>🎯</span> Select a process to view its visual representation and access resource allocation features.
                    <?php if (!empty($db_error)): ?>
                        <strong>Database Error:</strong> <?= htmlspecialchars($db_error) ?>
                    <?php elseif (count($processes) > 0): ?>
                        Found <?= count($processes) ?> processes in database.
                    <?php else: ?>
                        No processes found. Please create a process first using the modeling module.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resource Allocation Tab -->
        <div id="resource-allocation" class="tab-pane">
            <div class="tab-content">
                <h2>👥 Resource Allocation Management</h2>

                <div class="grid-2">
                    <div class="resource-form">
                        <h3>📋 Assign Resources to Tasks</h3>
                        
                        <div class="form-group">
                            <label>Process:</label>
                            <select id="allocation-process-select">
                                <option value="">Select Process...</option>
                                <?php foreach ($processes as $process): ?>
                                    <option value="<?= $process['id'] ?>">
                                        <?= htmlspecialchars($process['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Task:</label>
                            <select id="task-dropdown">
                                <option value="">Select Task for Resource Allocation...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Task ID (or select from dropdown):</label>
                            <input type="text" id="task-id" placeholder="e.g., Task_1, StartEvent_1">
                        </div>

                        <div class="form-group">
                            <label>Allocation Name:</label>
                            <input type="text" id="allocation-name" placeholder="e.g., Senior Analyst Assignment">
                        </div>

                        <div class="form-group">
                            <label>Resource Type:</label>
                            <select id="resource-type">
                                <option value="human">👤 Human Resource</option>
                                <option value="machine">🤖 Machine/Equipment</option>
                                <option value="both">⚡ Human + Machine</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cost ($):</label>
                            <input type="number" id="cost" value="50" min="0" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Processing Time (minutes):</label>
                            <input type="number" id="processing-time" value="30" min="1" placeholder="Will auto-load average for User Tasks">
                        </div>

                        <div class="form-group">
                            <label>Notes:</label>
                            <textarea id="notes" rows="3" placeholder="Additional notes about this resource allocation..."></textarea>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-success" id="btn-save-allocation">
                                ✅ Save Resource Allocation
                            </button>
                            <button class="btn btn-warning" id="btn-apply-all">
                                ⭐ Apply to All Tasks
                            </button>
                        </div>
                    </div>

                    <div class="resource-form">
                        <h3>📊 Current Allocations</h3>
                        <div id="allocations-list" class="allocations-list">
                            <div class="loading">Select a process to view its resource allocations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timer Tracking Tab -->
        <div id="timer-tracking" class="tab-pane">
            <div class="tab-content">
                <h2>⏱️ Task Timer & Performance Tracking</h2>

                <div class="grid-2">
                    <div class="timer-widget">
                        <h3>🕐 Active Timer</h3>
                        <div id="timer-display" class="timer-display">00:00:00</div>
                        <div id="timer-task-info">
                            <strong>Task:</strong> <span id="timer-task-name">No active timer</span><br>
                            <strong>Process:</strong> <span id="timer-process-name">-</span>
                        </div>
                        <div class="timer-controls">
                            <button class="btn btn-success" id="btn-start-timer" disabled>
                                ▶️ Start Timer
                            </button>
                            <button class="btn btn-warning" id="btn-pause-timer" disabled>
                                ⏸️ Pause
                            </button>
                            <button class="btn btn-danger" id="btn-complete-timer" disabled>
                                ✅ Complete
                            </button>
                        </div>
                    </div>

                    <div class="