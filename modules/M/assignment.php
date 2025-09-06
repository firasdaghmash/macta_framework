<?php
// modules/M/assignment.php - Process Assignment & Timer Tracking

// Initialize variables
$processes = [];
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
    error_log("Process Assignment DB Error: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error)) {
        echo json_encode(['success' => false, 'message' => $db_error]);
        exit;
    }
    
    switch ($_POST['action']) {
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
    <title>MACTA Framework - Process Assignment & Timer Tracking</title>

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

        .tab-content {
            padding: 30px;
            min-height: 70vh;
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

        .btn-success:hover:not(:disabled) {
            background: #5a54d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        .btn-danger {
            background: var(--macta-red);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #b02a2a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(214, 48, 49, 0.3);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .resource-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Enhanced Timer Widget Styles */
        .enhanced-timer-widget {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 3px solid var(--macta-green);
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 320px;
            max-width: 450px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .enhanced-timer-widget.collapsed {
            min-width: 180px;
            max-width: 180px;
        }

        .enhanced-timer-widget.collapsed .timer-content {
            display: none;
        }

        .enhanced-timer-widget.collapsed .timer-header {
            border-radius: 12px;
            padding: 10px 15px;
        }

        .enhanced-timer-widget.collapsed .timer-display {
            font-size: 18px;
            margin: 5px 0;
        }

        .enhanced-timer-widget.minimized {
            min-width: 120px;
            max-width: 120px;
            background: rgba(108, 92, 231, 0.95);
            border: 2px solid var(--macta-green);
            backdrop-filter: blur(10px);
        }

        .enhanced-timer-widget.minimized .timer-header {
            color: white;
            padding: 8px 10px;
            border-radius: 10px;
        }

        .enhanced-timer-widget.minimized .timer-content {
            display: none;
        }

        .enhanced-timer-widget.minimized .timer-display {
            color: white;
            font-size: 16px;
            font-weight: bold;
            margin: 2px 0;
        }

        .timer-header {
            background: linear-gradient(135deg, var(--macta-green), #5a54d9);
            color: white;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .timer-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .timer-controls-header {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .header-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .header-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .timer-content {
            padding: 20px;
        }

        .timer-task-info {
            text-align: center;
            margin-bottom: 15px;
        }

        .timer-task-info .task-name {
            font-weight: bold;
            color: var(--macta-green);
            margin-bottom: 5px;
        }

        .timer-task-info .task-id {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .timer-display {
            font-size: 36px;
            font-weight: bold;
            color: var(--htt-blue);
            font-family: 'Courier New', monospace;
            text-align: center;
            margin: 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .timer-display.running {
            color: var(--macta-green);
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0% { 
                opacity: 1;
                text-shadow: 0 2px 4px rgba(108, 92, 231, 0.3);
            }
            50% { 
                opacity: 0.8;
                text-shadow: 0 4px 12px rgba(108, 92, 231, 0.6);
            }
            100% { 
                opacity: 1;
                text-shadow: 0 2px 4px rgba(108, 92, 231, 0.3);
            }
        }

        .timer-main-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 15px;
        }

        .timer-main-controls .btn {
            flex: 1;
            justify-content: center;
            font-size: 13px;
            padding: 10px 16px;
        }

        .status-indicator {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--macta-green);
            border: 2px solid white;
            animation: pulse-indicator 2s infinite;
        }

        .status-indicator.stopped {
            background: #ccc;
            animation: none;
        }

        @keyframes pulse-indicator {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .enhanced-timer-widget.dragging {
            transform: rotate(3deg);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            z-index: 1001;
        }

        /* Resource Section in Timer */
        .resource-section {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 15px;
        }

        .resource-toggle {
            width: 100%;
            background: var(--htt-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .resource-toggle:hover {
            background: var(--htt-dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
        }

        .resource-form-timer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 10px;
        }

        .resource-form-timer.expanded {
            max-height: 400px;
            padding: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 12px;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            background: white;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--htt-blue);
            outline: none;
            box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.1);
        }

        .resource-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 11px;
            flex: 1;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--macta-green);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 1002;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(20px);
        }

        /* Original styles continue here... */
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

        .running-timer {
            animation: pulse 2s infinite !important;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
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

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .timer-main-controls {
                flex-direction: column;
            }

            .enhanced-timer-widget {
                min-width: 280px;
                max-width: 90vw;
                right: 10px;
                top: 10px;
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
                MACTA Framework - Process Assignment & Timer Tracking
            </h1>
            <div>
                <a href="../../index.php" class="btn btn-primary">
                    <span>←</span> Back to Framework
                </a>
            </div>
        </div>

        <div class="tab-content">
            <h2>👀 Process Visualization & Task Assignment</h2>
            
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
                <div class="loading">👆 Select a process from the dropdown above to view it here...</div>
            </div>

            <div class="grid-2">
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

                <div class="resource-form">
                    <h3>📋 Available Tasks for Assignment & Timing</h3>
                    <div id="tasks-list">
                        <div class="loading">Select a process to view available tasks</div>
                    </div>
                </div>
            </div>

            <div class="status-bar">
                <span>🎯</span> Select a process to view its visual representation and access task assignment features.
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

    <!-- Enhanced Timer Widget (initially hidden) -->
    <div id="enhanced-timer-widget" class="enhanced-timer-widget" style="display: none;">
        <div class="timer-header" id="timer-header">
            <h4>
                <span>⏱️</span>
                User Task Timer
                <div class="status-indicator" id="status-indicator"></div>
            </h4>
            <div class="timer-controls-header">
                <button class="header-btn" id="btn-minimize" title="Minimize" onclick="toggleMinimize()">−</button>
                <button class="header-btn" id="btn-collapse" title="Collapse" onclick="toggleCollapse()">↕</button>
                <button class="header-btn" id="btn-close" title="Close" onclick="closeTimer()">×</button>
            </div>
        </div>

        <div class="timer-content" id="timer-content">
            <div class="timer-task-info">
                <div class="task-name" id="task-name">No Task Selected</div>
                <div class="task-id" id="task-id-display">ID: -</div>
            </div>

            <div class="timer-display" id="timer-display">00:00:00</div>

            <div class="timer-main-controls">
                <button class="btn btn-success" id="btn-start" onclick="startEnhancedTimer()">
                    ▶️ Start
                </button>
                <button class="btn btn-danger" id="btn-stop" onclick="stopEnhancedTimer()" disabled>
                    ⏹️ Stop
                </button>
                <button class="btn btn-success" id="btn-complete" onclick="completeEnhancedTimer()" disabled>
                    ✅ Complete
                </button>
            </div>

            <!-- Resource Section -->
            <div class="resource-section">
                <button class="resource-toggle" id="resource-toggle" onclick="toggleResources()">
                    👥 Assign Resources
                    <span id="resource-arrow">▼</span>
                </button>

                <div class="resource-form-timer" id="resource-form-timer">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Resource Type:</label>
                            <select id="resource-type">
                                <option value="human">👤 Human</option>
                                <option value="machine">🤖 Machine</option>
                                <option value="both">⚡ Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cost ($):</label>
                            <input type="number" id="cost" value="50" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Processing Time (min):</label>
                            <input type="number" id="processing-time" value="2" min="1">
                        </div>
                        <div class="form-group">
                            <label>Priority:</label>
                            <select id="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Allocation Name:</label>
                        <input type="text" id="allocation-name" placeholder="e.g., Senior Analyst">
                    </div>

                    <div class="resource-actions">
                        <button class="btn btn-success btn-sm" onclick="saveResources()">
                            ✅ Save
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openResourcesPage()">
                            📋 Full Page
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast" class="toast"></div>

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
        let activeTaskName = null;
        
        // Enhanced timer state
        let timerState = {
            isRunning: false,
            startTime: null,
            elapsedTime: 0,
            interval: null,
            isCollapsed: false,
            isMinimized: false
        };

        // Dragging functionality
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };

        const timerWidget = document.getElementById('enhanced-timer-widget');
        const timerHeader = document.getElementById('timer-header');
        
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

        // Initialize dragging
        timerHeader.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);

        function startDrag(e) {
            if (e.target.closest('.header-btn')) return; // Don't drag when clicking buttons
            
            isDragging = true;
            timerWidget.classList.add('dragging');
            
            const rect = timerWidget.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            e.preventDefault();
        }

        function drag(e) {
            if (!isDragging) return;
            
            const x = e.clientX - dragOffset.x;
            const y = e.clientY - dragOffset.y;
            
            // Keep widget within viewport bounds
            const maxX = window.innerWidth - timerWidget.offsetWidth;
            const maxY = window.innerHeight - timerWidget.offsetHeight;
            
            const boundedX = Math.max(0, Math.min(x, maxX));
            const boundedY = Math.max(0, Math.min(y, maxY));
            
            timerWidget.style.left = boundedX + 'px';
            timerWidget.style.top = boundedY + 'px';
            timerWidget.style.right = 'auto';
        }

        function stopDrag() {
            isDragging = false;
            timerWidget.classList.remove('dragging');
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
                loadTimerAverages(processId);
                loadProcessTasks(processId);
                
                // Make tasks clickable for timing
                addTaskClickHandlers();
                
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
            }
        }

        // Add click handlers to tasks for timer functionality
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
                });
                
            } catch (error) {
                console.error('Failed to add task click handlers:', error);
            }
        }

        // Handle User Task clicks for timer functionality
        function handleUserTaskClick(element) {
            const taskId = element.id;
            const taskName = element.businessObject.name || element.id;
            
            // Check if there's already an active timer
            if (activeTimerSessionId) {
                showToast('You already have an active timer running. Please complete or stop the current timer first.', 'warning');
                return;
            }
            
            // Show enhanced timer widget
            showEnhancedTimerWidget(taskId, taskName);
            
            // Highlight the selected task
            highlightSelectedTask(taskId);
        }

        // Show enhanced timer widget
        function showEnhancedTimerWidget(taskId, taskName) {
            activeTaskId = taskId;
            activeTaskName = taskName;
            
            // Update task info in widget
            document.getElementById('task-name').textContent = taskName;
            document.getElementById('task-id-display').textContent = 'ID: ' + taskId;
            document.getElementById('allocation-name').value = taskName + ' Assignment';
            
            // Show the widget
            timerWidget.style.display = 'block';
            setTimeout(() => {
                timerWidget.style.opacity = '1';
                timerWidget.style.transform = 'translateX(0)';
            }, 10);
        }

        // Enhanced timer functions
        async function startEnhancedTimer() {
            if (!currentProcessId || !activeTaskId) {
                showToast('Error: Process or task not properly selected.', 'error');
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
                    timerState.isRunning = true;
                    timerState.startTime = Date.now();
                    
                    // Update UI
                    document.getElementById('btn-start').disabled = true;
                    document.getElementById('btn-stop').disabled = false;
                    document.getElementById('btn-complete').disabled = false;
                    document.getElementById('timer-display').classList.add('running');
                    document.getElementById('status-indicator').classList.remove('stopped');
                    
                    // Start timer display
                    timerState.interval = setInterval(updateEnhancedTimerDisplay, 1000);
                    updateEnhancedTimerDisplay();
                    
                    // Highlight task in viewer with running timer style
                    highlightRunningTask(activeTaskId);
                    
                    showToast('⏱️ Timer started successfully!', 'success');
                } else {
                    showToast('Failed to start timer: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Timer start error:', error);
                showToast('Error starting timer', 'error');
            }
        }

        async function stopEnhancedTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=pause_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    timerState.isRunning = false;
                    timerState.elapsedTime = Date.now() - timerState.startTime;
                    
                    // Update UI
                    document.getElementById('btn-start').disabled = false;
                    document.getElementById('btn-start').innerHTML = '▶️ Resume';
                    document.getElementById('btn-stop').disabled = true;
                    document.getElementById('timer-display').classList.remove('running');
                    document.getElementById('status-indicator').classList.add('stopped');
                    
                    // Stop timer display update
                    clearInterval(timerState.interval);
                    
                    clearTaskHighlight(activeTaskId);
                    showToast('⏹️ Timer stopped - you can resume anytime', 'warning');
                } else {
                    showToast('Failed to stop timer: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Timer stop error:', error);
            }
        }

        async function completeEnhancedTimer() {
            if (!activeTimerSessionId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=complete_timer&session_id=${activeTimerSessionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const finalTime = timerState.isRunning ? Date.now() - timerState.startTime : timerState.elapsedTime;
                    const minutes = Math.floor(finalTime / 60000);
                    const seconds = Math.floor((finalTime % 60000) / 1000);
                    
                    // Reset timer state
                    resetEnhancedTimerState();
                    clearTaskHighlight(activeTaskId);
                    
                    // Refresh timer averages
                    if (currentProcessId) {
                        loadTimerAverages(currentProcessId);
                    }
                    
                    showToast(`✅ Task completed in ${minutes}m ${seconds}s - Great work!`, 'success');
                    
                    // Auto-minimize after completion
                    setTimeout(() => {
                        if (!timerState.isMinimized) {
                            toggleMinimize();
                        }
                    }, 2000);
                } else {
                    showToast('Failed to complete timer: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Timer complete error:', error);
            }
        }

        function updateEnhancedTimerDisplay() {
            if (!timerState.isRunning) return;
            
            const elapsed = Date.now() - timerState.startTime;
            const hours = Math.floor(elapsed / 3600000);
            const minutes = Math.floor((elapsed % 3600000) / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            
            const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('timer-display').textContent = display;
        }

        function resetEnhancedTimerState() {
            timerState = {
                isRunning: false,
                startTime: null,
                elapsedTime: 0,
                interval: null,
                isCollapsed: timerState.isCollapsed,
                isMinimized: timerState.isMinimized
            };
            
            activeTimerSessionId = null;
            activeTimerStartTime = null;
            
            // Reset UI
            document.getElementById('timer-display').textContent = '00:00:00';
            document.getElementById('timer-display').classList.remove('running');
            document.getElementById('btn-start').disabled = false;
            document.getElementById('btn-start').innerHTML = '▶️ Start';
            document.getElementById('btn-stop').disabled = true;
            document.getElementById('btn-complete').disabled = true;
            document.getElementById('status-indicator').classList.add('stopped');
            
            if (timerState.interval) {
                clearInterval(timerState.interval);
            }
        }

        // Widget state functions
        function toggleCollapse() {
            timerState.isCollapsed = !timerState.isCollapsed;
            timerWidget.classList.toggle('collapsed', timerState.isCollapsed);
        }

        function toggleMinimize() {
            timerState.isMinimized = !timerState.isMinimized;
            timerWidget.classList.toggle('minimized', timerState.isMinimized);
            
            if (timerState.isMinimized) {
                // Remove collapsed state when minimizing
                timerWidget.classList.remove('collapsed');
                timerState.isCollapsed = false;
            }
        }

        function closeTimer() {
            if (timerState.isRunning) {
                if (!confirm('Timer is still running. Are you sure you want to close it?')) {
                    return;
                }
                clearInterval(timerState.interval);
            }
            
            timerWidget.style.opacity = '0';
            timerWidget.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                timerWidget.style.display = 'none';
                timerWidget.style.left = '';
                timerWidget.style.top = '';
                timerWidget.style.right = '20px';
                timerWidget.style.transform = '';
                timerWidget.style.opacity = '';
                
                // Reset states
                timerWidget.classList.remove('collapsed', 'minimized');
                timerState.isCollapsed = false;
                timerState.isMinimized = false;
                
                clearTaskHighlight(activeTaskId);
                activeTaskId = null;
                activeTaskName = null;
            }, 300);
        }

        // Resource functions
        function toggleResources() {
            const resourceForm = document.getElementById('resource-form-timer');
            const resourceArrow = document.getElementById('resource-arrow');
            
            resourceForm.classList.toggle('expanded');
            resourceArrow.innerHTML = resourceForm.classList.contains('expanded') ? '▲' : '▼';
        }

        function saveResources() {
            const resourceType = document.getElementById('resource-type').value;
            const cost = document.getElementById('cost').value;
            const processingTime = document.getElementById('processing-time').value;
            const allocationName = document.getElementById('allocation-name').value;
            const priority = document.getElementById('priority').value;
            
            showToast(`💾 Resources saved: ${allocationName} (${resourceType}, ${cost}, ${processingTime}min)`, 'success');
            
            // Auto-collapse resource form
            setTimeout(() => {
                document.getElementById('resource-form-timer').classList.remove('expanded');
                document.getElementById('resource-arrow').innerHTML = '▼';
            }, 1000);
        }

        function openResourcesPage() {
            const url = `allocation.php?task=${activeTaskId}&name=${encodeURIComponent(activeTaskName)}`;
            window.open(url, '_blank');
            showToast('📋 Opening full resources page...', 'info');
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            
            const colors = {
                success: '#00b894',
                warning: '#fdcb6e',
                error: '#d63031',
                info: '#1E88E5'
            };
            
            toast.style.background = colors[type] || colors.info;
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
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
                
                const userTasks = elements.filter(el => el.type === 'bpmn:UserTask');
                
                const container = document.getElementById('tasks-list');
                
                if (userTasks.length === 0) {
                    container.innerHTML = '<div class="loading">No user tasks found in this process</div>';
                    return;
                }
                
                let html = '';
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
                                    <button class="btn btn-primary" style="margin: 0;" onclick="event.stopPropagation(); window.open('allocation.php?task=${task.id}&name=${encodeURIComponent(name)}', '_blank')">
                                        👥 Resources
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Failed to load process tasks:', error);
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
                    activeTaskId = timer.task_id;
                    timerState.isRunning = true;
                    timerState.startTime = Date.now() - (timer.elapsed_seconds * 1000);
                    
                    // Show the enhanced timer widget
                    showEnhancedTimerWidget(timer.task_id, timer.task_id);
                    
                    // Update UI to running state
                    document.getElementById('btn-start').disabled = true;
                    document.getElementById('btn-stop').disabled = false;
                    document.getElementById('btn-complete').disabled = false;
                    document.getElementById('timer-display').classList.add('running');
                    document.getElementById('status-indicator').classList.remove('stopped');
                    
                    // Start timer display
                    timerState.interval = setInterval(updateEnhancedTimerDisplay, 1000);
                    
                    // Highlight task if in current process
                    if (currentProcessId && timer.process_id == currentProcessId) {
                        highlightRunningTask(timer.task_id);
                    }
                }
            } catch (error) {
                console.error('Failed to check active timer:', error);
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 MACTA Process Assignment initialized');
            
            // Initialize BPMN viewer
            initializeBPMNViewer();
            
            // Process selector
            document.getElementById('process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                
                if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        await loadProcessInViewer(selectedValue, xmlData);
                    }
                } else {
                    const loading = document.querySelector('#process-viewer .loading');
                    if (loading) {
                        loading.style.display = 'flex';
                        loading.innerHTML = '👆 Select a process from the dropdown above to view it here...';
                    }
                }
            });
            
            document.getElementById('btn-refresh-averages').addEventListener('click', () => {
                if (currentProcessId) {
                    loadTimerAverages(currentProcessId);
                }
            });
            
            // Check for active timer on page load
            checkActiveTimer();
            
            console.log('✅ All event listeners attached successfully!');
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (timerWidget.style.left) {
                const rect = timerWidget.getBoundingClientRect();
                if (rect.right > window.innerWidth || rect.bottom > window.innerHeight) {
                    timerWidget.style.left = '';
                    timerWidget.style.top = '';
                    timerWidget.style.right = '20px';
                }
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (timerWidget.style.display === 'none') return;
            
            if (e.key === ' ' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                if (timerState.isRunning) {
                    stopEnhancedTimer();
                } else if (activeTaskId) {
                    startEnhancedTimer();
                }
            }
            
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                completeEnhancedTimer();
            }
            
            if (e.key === 'Escape') {
                e.preventDefault();
                toggleMinimize();
            }
        });
    </script>
</body>
</html>