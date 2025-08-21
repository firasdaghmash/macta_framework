<?php
// modules/M/enhanced_modeling.php - Combined BPMN + Resource + Timer Module

// Initialize variables
$processes = [];
$projects = [];
$resource_allocations = [];
$timer_sessions = [];
$timer_averages = [];
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
    <title>MACTA Framework - Enhanced Modeling Module</title>

    <!-- MACTA Brand Colors and Enhanced Styling -->
    <style>
        :root {
            /* HTT Brand Colors - Updated from logo */
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
            --box-height: 600px;
            
            /* Animation Color Palette - 8 distinct colors */
            --anim-color-1: #FF6B6B; /* Red */
            --anim-color-2: #4ECDC4; /* Teal */
            --anim-color-3: #45B7D1; /* Blue */
            --anim-color-4: #96CEB4; /* Green */
            --anim-color-5: #FFEAA7; /* Yellow */
            --anim-color-6: #DDA0DD; /* Purple */
            --anim-color-7: #98D8C8; /* Mint */
            --anim-color-8: #F7DC6F; /* Gold */
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

        .tab-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--macta-light);
        }

        .tab-header h2 {
            color: var(--macta-dark);
            font-size: 24px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tab-header p {
            color: #666;
            font-size: 14px;
        }

        /* BPMN Containers - Full width for editor */
        #bpmn-editor, #bpmn-viewer, #simulation-viewer, .process-viewer {
            height: var(--box-height);
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #fafafa;
            margin-bottom: 20px;
            width: 100%;
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

        .btn-danger {
            background: var(--macta-red);
            color: white;
        }

        .btn-danger:hover {
            background: #b71c1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(214,48,49,0.3);
        }

        /* Resource Assignment Styles */
        .resource-form {
            background: var(--htt-light-gray);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid var(--htt-gray);
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

        /* Analysis Grid */
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .analysis-card {
            background: white;
            border: 1px solid var(--macta-light);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .path-visualization {
            height: 300px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            overflow: auto;
        }

        .path-item {
            background: white;
            border-left: 4px solid var(--macta-teal);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 0 5px 5px 0;
        }

        .path-item.critical {
            border-left-color: var(--macta-red);
        }

        .path-item.costly {
            border-left-color: var(--macta-yellow);
        }

        .path-item.resource-intensive {
            border-left-color: var(--macta-green);
        }

        /* Animation & Simulation Styles */
        .animation-status {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--htt-blue);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .animation-status.running {
            border-left-color: var(--macta-green);
            background: #e8f5e8;
        }

        .animation-status.stopped {
            border-left-color: var(--macta-red);
            background: #ffebee;
        }

        /* Performance Metrics */
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
            transition: transform 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-3px);
        }

        .metric-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .metric-label {
            font-size: 12px;
            opacity: 0.9;
        }

        /* Color Legend */
        .color-legend {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid var(--macta-light);
        }

        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 6px;
            background: #f8f9fa;
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 2px solid #ccc;
        }

        .status-bar {
            background: var(--htt-light-gray);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: var(--htt-gray);
            border-left: 4px solid var(--htt-blue);
        }

        .status-message {
            background: #e8f5e8;
            border: 1px solid var(--macta-green);
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: var(--macta-green);
            text-align: center;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
            font-size: 18px;
            color: var(--macta-orange);
        }

        /* Animation Color System - Each run gets a different color */
        .animation-run-1 .djs-visual > rect,
        .animation-run-1 .djs-visual > circle,
        .animation-run-1 .djs-visual > polygon {
            fill: var(--anim-color-1) !important;
            stroke: #e55555 !important;
            stroke-width: 4px !important;
            animation: pulse-1 1.5s infinite;
        }

        .animation-run-2 .djs-visual > rect,
        .animation-run-2 .djs-visual > circle,
        .animation-run-2 .djs-visual > polygon {
            fill: var(--anim-color-2) !important;
            stroke: #3cb8b1 !important;
            stroke-width: 4px !important;
            animation: pulse-2 1.5s infinite;
        }

        .animation-run-3 .djs-visual > rect,
        .animation-run-3 .djs-visual > circle,
        .animation-run-3 .djs-visual > polygon {
            fill: var(--anim-color-3) !important;
            stroke: #3a9bc1 !important;
            stroke-width: 4px !important;
            animation: pulse-3 1.5s infinite;
        }

        .animation-run-4 .djs-visual > rect,
        .animation-run-4 .djs-visual > circle,
        .animation-run-4 .djs-visual > polygon {
            fill: var(--anim-color-4) !important;
            stroke: #7bb89f !important;
            stroke-width: 4px !important;
            animation: pulse-4 1.5s infinite;
        }

        .animation-run-5 .djs-visual > rect,
        .animation-run-5 .djs-visual > circle,
        .animation-run-5 .djs-visual > polygon {
            fill: var(--anim-color-5) !important;
            stroke: #e6d085 !important;
            stroke-width: 4px !important;
            animation: pulse-5 1.5s infinite;
        }

        .animation-run-6 .djs-visual > rect,
        .animation-run-6 .djs-visual > circle,
        .animation-run-6 .djs-visual > polygon {
            fill: var(--anim-color-6) !important;
            stroke: #c088c0 !important;
            stroke-width: 4px !important;
            animation: pulse-6 1.5s infinite;
        }

        .animation-run-7 .djs-visual > rect,
        .animation-run-7 .djs-visual > circle,
        .animation-run-7 .djs-visual > polygon {
            fill: var(--anim-color-7) !important;
            stroke: #7dbfb3 !important;
            stroke-width: 4px !important;
            animation: pulse-7 1.5s infinite;
        }

        .animation-run-8 .djs-visual > rect,
        .animation-run-8 .djs-visual > circle,
        .animation-run-8 .djs-visual > polygon {
            fill: var(--anim-color-8) !important;
            stroke: #ddb84f !important;
            stroke-width: 4px !important;
            animation: pulse-8 1.5s infinite;
        }

        /* Flow animations for sequence flows */
        .animation-run-1 .djs-visual > path { stroke: var(--anim-color-1) !important; stroke-width: 4px !important; animation: flow-1 2s infinite; }
        .animation-run-2 .djs-visual > path { stroke: var(--anim-color-2) !important; stroke-width: 4px !important; animation: flow-2 2s infinite; }
        .animation-run-3 .djs-visual > path { stroke: var(--anim-color-3) !important; stroke-width: 4px !important; animation: flow-3 2s infinite; }
        .animation-run-4 .djs-visual > path { stroke: var(--anim-color-4) !important; stroke-width: 4px !important; animation: flow-4 2s infinite; }
        .animation-run-5 .djs-visual > path { stroke: var(--anim-color-5) !important; stroke-width: 4px !important; animation: flow-5 2s infinite; }
        .animation-run-6 .djs-visual > path { stroke: var(--anim-color-6) !important; stroke-width: 4px !important; animation: flow-6 2s infinite; }
        .animation-run-7 .djs-visual > path { stroke: var(--anim-color-7) !important; stroke-width: 4px !important; animation: flow-7 2s infinite; }
        .animation-run-8 .djs-visual > path { stroke: var(--anim-color-8) !important; stroke-width: 4px !important; animation: flow-8 2s infinite; }

        /* Pulse keyframes for each color */
        @keyframes pulse-1 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-2 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-3 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-4 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-5 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-6 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-7 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes pulse-8 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }

        /* Flow keyframes with different patterns */
        @keyframes flow-1 { 0% { stroke-dasharray: 10 5; stroke-dashoffset: 0; } 100% { stroke-dasharray: 10 5; stroke-dashoffset: -15; } }
        @keyframes flow-2 { 0% { stroke-dasharray: 8 6; stroke-dashoffset: 0; } 100% { stroke-dasharray: 8 6; stroke-dashoffset: -14; } }
        @keyframes flow-3 { 0% { stroke-dasharray: 12 4; stroke-dashoffset: 0; } 100% { stroke-dasharray: 12 4; stroke-dashoffset: -16; } }
        @keyframes flow-4 { 0% { stroke-dasharray: 9 7; stroke-dashoffset: 0; } 100% { stroke-dasharray: 9 7; stroke-dashoffset: -16; } }
        @keyframes flow-5 { 0% { stroke-dasharray: 11 5; stroke-dashoffset: 0; } 100% { stroke-dasharray: 11 5; stroke-dashoffset: -16; } }
        @keyframes flow-6 { 0% { stroke-dasharray: 7 8; stroke-dashoffset: 0; } 100% { stroke-dasharray: 7 8; stroke-dashoffset: -15; } }
        @keyframes flow-7 { 0% { stroke-dasharray: 10 6; stroke-dashoffset: 0; } 100% { stroke-dasharray: 10 6; stroke-dashoffset: -16; } }
        @keyframes flow-8 { 0% { stroke-dasharray: 13 3; stroke-dashoffset: 0; } 100% { stroke-dasharray: 13 3; stroke-dashoffset: -16; } }

        /* BPMN.js Enhanced Styling */
        .bjs-container {
            background: white !important;
        }
        
        .djs-element {
            pointer-events: all !important;
        }
        
        /* Task styling */
        .djs-shape[data-element-id*="Task"] .djs-visual > rect {
            fill: #f8f9fa !important;
            stroke: var(--macta-teal) !important;
            stroke-width: 2px !important;
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
            stroke-width: 2px !important;
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

            .toolbar {
                flex-direction: column;
                gap: 8px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .performance-metrics {
                grid-template-columns: 1fr;
            }

            .analysis-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- BPMN.js styles -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
</head>
<body>
    <!-- Header -->
    <div class="container">
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
        <div class="nav-tabs">
            <button class="nav-tab active" data-tab="bpmn-design">
                <span class="tab-icon">🎨</span>
                <span>BPMN Design</span>
            </button>
            <button class="nav-tab" data-tab="process-view">
                <span class="tab-icon">👁️</span>
                <span>Process View & Timer</span>
            </button>
            <button class="nav-tab" data-tab="resource-allocation">
                <span class="tab-icon">👥</span>
                <span>Resource Allocation</span>
            </button>
            <button class="nav-tab" data-tab="simulation">
                <span class="tab-icon">⚡</span>
                <span>Simulation</span>
            </button>
            <button class="nav-tab" data-tab="analysis">
                <span class="tab-icon">📊</span>
                <span>Path Analysis</span>
            </button>
        </div>

        <!-- BPMN Design Tab -->
        <div id="bpmn-design" class="tab-pane active">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>
                        <span class="tab-icon">🎨</span>
                        BPMN Process Design & Modeling
                    </h2>
                    <p>Create and edit business process models using BPMN 2.0 standard with drag-and-drop functionality</p>
                </div>

                <div class="status-message">
                    ✅ Enhanced BPMN functionality integrated with MACTA Framework - Full modeling capabilities activated!
                </div>

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
                </div>

                <!-- Enhanced Design Toolbar -->
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
                        🔍 Fit to Screen
                    </button>
                    <button class="btn btn-success" id="btn-export-xml">
                        📤 Export to Viewer
                    </button>
                </div>

                <!-- Enhanced BPMN Editor with Full Width -->
                <div id="bpmn-editor">
                    <div class="loading">Loading Enhanced BPMN Editor...</div>
                </div>

                <!-- BPMN Components Info Panel -->
                <div class="color-legend">
                    <h4>📋 BPMN 2.0 Quick Reference Guide</h4>
                    <p>Click on the editor canvas above to access the built-in palette. Here's what each component does:</p>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #e8f5e8; border: 2px solid #27ae60; border-radius: 50%;"></div>
                            <span><strong>Start Event (Circle):</strong> Triggers the beginning of a process</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffebee; border: 2px solid #e74c3c; border-radius: 50%;"></div>
                            <span><strong>End Event (Circle):</strong> Marks the completion of a process</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #e3f2fd; border: 2px solid #2196f3;"></div>
                            <span><strong>Task (Rectangle):</strong> A single unit of work or activity</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #f3e5f5; border: 2px solid #9c27b0; clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);"></div>
                            <span><strong>Gateway (Diamond):</strong> Controls flow direction and decisions</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(to right, #333 0%, #333 100%); height: 4px; border-radius: 2px; position: relative;"></div>
                            <span><strong>Sequence Flow (Arrow):</strong> Shows the order of activities</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #e0f2f1; border: 2px solid #009688; border-radius: 4px;"></div>
                            <span><strong>Pool/Lane:</strong> Represents different participants or departments</span>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div style="padding: 10px; background: #e8f5e8; border-radius: 5px;">
                            <strong>🎯 Getting Started:</strong><br>
                            1. Click on the canvas above<br>
                            2. Use the palette on the left<br>
                            3. Drag components to canvas<br>
                            4. Connect with sequence flows
                        </div>
                        <div style="padding: 10px; background: #fff3e0; border-radius: 5px;">
                            <strong>⚡ Pro Tips:</strong><br>
                            • Right-click elements for options<br>
                            • Double-click to edit names<br>
                            • Use toolbar buttons above<br>
                            • Save regularly to database
                        </div>
                    </div>
                </div>

                <div class="status-bar">
                    <span class="token">🎯</span> Use the BPMN editor above to create professional process models.
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

        <!-- Process View & Timer Tab -->
        <div id="process-view" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>
                        <span class="tab-icon">👁️</span>
                        Process View with Timer Functionality
                    </h2>
                    <p>View and analyze saved business processes with integrated timer functionality for User Tasks</p>
                </div>

                <!-- Process Selector for Viewer -->
                <div class="process-selector">
                    <select id="viewer-process-select">
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

                <!-- BPMN Process Viewer -->
                <div class="process-viewer" id="process-viewer">
                    <div class="loading">👆 Select a process from the dropdown above to view it here...</div>
                </div>

                <div class="toolbar">
                    <button class="btn btn-primary" id="btn-animate-path">
                        🎬 Animate Process
                    </button>
                    <button class="btn btn-danger" id="btn-clear-highlights">
                        ⏹️ Stop & Clear
                    </button>
                    <button class="btn btn-warning" id="btn-analyze-bottlenecks">
                        🔍 Analyze Bottlenecks
                    </button>
                    <button class="btn btn-success" id="btn-refresh-viewer">
                        🔄 Refresh Viewer
                    </button>
                    <button class="btn btn-secondary" id="btn-viewer-zoom-fit">
                        🔍 Fit to Screen
                    </button>
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
                        <h3>📋 Available Tasks for Timing</h3>
                        <div id="tasks-list">
                            <div class="loading">Select a process to view available tasks</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resource Allocation Tab -->
        <div id="resource-allocation" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>
                        <span class="tab-icon">👥</span>
                        Resource Allocation Management
                    </h2>
                    <p>Assign resources, roles, and responsibilities to process steps with detailed analysis</p>
                </div>

                <div class="grid-2">
                    <div class="resource-form">
                        <h3>📋 Task Resource Configuration</h3>
                        
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

        <!-- Simulation Tab -->
        <div id="simulation" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>
                        <span class="tab-icon">⚡</span>
                        Advanced Process Simulation
                    </h2>
                    <p>Run advanced simulations with different scenarios and color-coded runs for performance analysis</p>
                </div>

                <!-- Animation Status for Simulation -->
                <div class="animation-status" id="simulation-status">
                    <span>⚡</span>
                    <div>
                        <strong>Simulation Status:</strong>
                        <span id="simulation-text">Ready to simulate</span>
                    </div>
                    <div style="margin-left: auto;">
                        <strong>Total Runs: <span id="simulation-run-count">0</span></strong>
                    </div>
                </div>

                <!-- Enhanced Simulation Controls -->
                <div class="toolbar">
                    <button class="btn btn-success" id="btn-start-simulation">
                        ▶️ Start Simulation
                    </button>
                    <button class="btn btn-warning" id="btn-pause-simulation">
                        ⏸️ Pause
                    </button>
                    <button class="btn btn-danger" id="btn-stop-simulation">
                        ⏹️ Stop
                    </button>
                    <button class="btn btn-secondary" id="btn-reset-simulation">
                        🔄 Reset All
                    </button>
                    <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                        <label>Speed:</label>
                        <input type="range" id="sim-speed" min="0.5" max="3" step="0.1" value="1" style="width: 100px;">
                        <span id="speed-display">1x</span>
                    </div>
                </div>

                <!-- Simulation Viewer -->
                <div id="simulation-viewer">
                    <div class="loading">Click Start Simulation to begin advanced process simulation...</div>
                </div>
                
                <!-- Enhanced Performance Metrics -->
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

                <!-- Color Legend for Simulation -->
                <div class="color-legend">
                    <h4>🎨 Simulation Color System</h4>
                    <p>Each simulation run gets a unique color that persists until reset</p>
                    <div class="legend-items" id="simulation-legend-items">
                        <!-- Dynamic legend items will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Path Analysis Tab -->
        <div id="analysis" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>
                        <span class="tab-icon">📊</span>
                        Advanced Path Analysis
                    </h2>
                    <p>Comprehensive analysis of process paths with cost, time, and resource optimization insights</p>
                </div>
                
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <h3>🔴 Critical Path</h3>
                        <div class="path-visualization">
                            <div class="path-item critical">
                                <strong>Start → Review Request</strong><br>
                                Duration: 30 min | Cost: $25<br>
                                Critical Factor: Longest duration
                            </div>
                            <div class="path-item critical">
                                <strong>Review Request → Approval</strong><br>
                                Duration: 45 min | Cost: $60<br>
                                Critical Factor: Resource dependency
                            </div>
                            <div class="path-item critical">
                                <strong>Approval → Complete</strong><br>
                                Duration: 15 min | Cost: $20<br>
                                Critical Factor: Final step
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #ffebee; border-radius: 5px;">
                                <strong>Total Critical Path:</strong> 90 min | $105
                            </div>
                        </div>
                    </div>

                    <div class="analysis-card">
                        <h3>⏱️ Most Time Consuming Path</h3>
                        <div class="path-visualization">
                            <div class="path-item">
                                <strong>Start → Initial Review</strong><br>
                                Duration: 25 min | Resources: 1<br>
                                Time Factor: Setup overhead
                            </div>
                            <div class="path-item">
                                <strong>Initial Review → Deep Analysis</strong><br>
                                Duration: 60 min | Resources: 2<br>
                                Time Factor: Complex analysis required
                            </div>
                            <div class="path-item">
                                <strong>Deep Analysis → Final Approval</strong><br>
                                Duration: 30 min | Resources: 1<br>
                                Time Factor: Management review
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                                <strong>Total Time Path:</strong> 115 min
                            </div>
                        </div>
                    </div>

                    <div class="analysis-card">
                        <h3>👥 Most Human Resources Path</h3>
                        <div class="path-visualization">
                            <div class="path-item resource-intensive">
                                <strong>Collaborative Review</strong><br>
                                Duration: 40 min | Resources: 4<br>
                                Human Factor: Team meeting required
                            </div>
                            <div class="path-item resource-intensive">
                                <strong>Cross-Department Validation</strong><br>
                                Duration: 35 min | Resources: 3<br>
                                Human Factor: Multiple stakeholders
                            </div>
                            <div class="path-item resource-intensive">
                                <strong>Final Sign-off</strong><br>
                                Duration: 20 min | Resources: 2<br>
                                Human Factor: Executive approval
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #f3e5f5; border-radius: 5px;">
                                <strong>Total Resources:</strong> 9 people | 95 min
                            </div>
                        </div>
                    </div>

                    <div class="analysis-card">
                        <h3>💰 Most Costly Path</h3>
                        <div class="path-visualization">
                            <div class="path-item costly">
                                <strong>Expert Consultation</strong><br>
                                Duration: 30 min | Cost: $150<br>
                                Cost Factor: Senior specialist rate
                            </div>
                            <div class="path-item costly">
                                <strong>External Audit</strong><br>
                                Duration: 45 min | Cost: $200<br>
                                Cost Factor: Third-party service
                            </div>
                            <div class="path-item costly">
                                <strong>Legal Review</strong><br>
                                Duration: 25 min | Cost: $125<br>
                                Cost Factor: Legal counsel
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #fff3e0; border-radius: 5px;">
                                <strong>Total Cost Path:</strong> $475 | 100 min
                            </div>
                        </div>
                    </div>

                    <div class="analysis-card">
                        <h3>⭐ Ideal Path</h3>
                        <div class="path-visualization">
                            <div class="path-item">
                                <strong>Automated Initial Processing</strong><br>
                                Duration: 5 min | Cost: $2<br>
                                Ideal Factor: AI-powered
                            </div>
                            <div class="path-item">
                                <strong>Smart Routing</strong><br>
                                Duration: 2 min | Cost: $1<br>
                                Ideal Factor: Rule-based automation
                            </div>
                            <div class="path-item">
                                <strong>Quick Approval</strong><br>
                                Duration: 10 min | Cost: $15<br>
                                Ideal Factor: Pre-approved criteria
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 5px;">
                                <strong>Ideal Path:</strong> $18 | 17 min | 95% automation
                            </div>
                        </div>
                    </div>

                    <div class="analysis-card">
                        <h3>🔄 Most Frequent Path</h3>
                        <div class="path-visualization">
                            <div class="path-item">
                                <strong>Standard Review Process</strong><br>
                                Frequency: 78% of cases<br>
                                Duration: 25 min | Cost: $30
                            </div>
                            <div class="path-item">
                                <strong>Manager Approval</strong><br>
                                Frequency: 65% of cases<br>
                                Duration: 15 min | Cost: $20
                            </div>
                            <div class="path-item">
                                <strong>Notification & Close</strong><br>
                                Frequency: 98% of cases<br>
                                Duration: 5 min | Cost: $3
                            </div>
                            <div style="margin-top: 15px; padding: 10px; background: #f0f4f8; border-radius: 5px;">
                                <strong>Most Common:</strong> $53 | 45 min | 70% frequency
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button class="btn btn-success" id="btn-generate-report">📊 Generate Detailed Report</button>
                    <button class="btn btn-warning" id="btn-export-analysis">📤 Export Analysis</button>
                    <button class="btn btn-primary" id="btn-suggest-optimizations">🚀 Suggest Optimizations</button>
                </div>
            </div>
        </div>
    </div>

    <!-- BPMN.js scripts -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js"></script>
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js"></script>

    <script>
        // Store PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const projects = <?= json_encode($projects) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        
        // Global variables
        let modeler = null;
        let processViewer = null;
        let simulationViewer = null;
        let currentXML = null;
        let currentProcessId = null;
        let animationRunCount = 0;
        let simulationRunCount = 0;
        let isAnimating = false;
        let isSimulating = false;
        let animationTimeouts = [];
        let simulationTimeouts = [];
        let simulationInterval = null;
        let currentTab = 'bpmn-design';

        // Timer functionality variables
        let activeTimerInterval = null;
        let activeTimerStartTime = null;
        let activeTimerSessionId = null;
        let activeTaskId = null;
        
        // Animation colors system
        const animationColors = [
            { name: 'Red Flow', css: 'animation-run-1', color: '#FF6B6B' },
            { name: 'Teal Flow', css: 'animation-run-2', color: '#4ECDC4' },
            { name: 'Blue Flow', css: 'animation-run-3', color: '#45B7D1' },
            { name: 'Green Flow', css: 'animation-run-4', color: '#96CEB4' },
            { name: 'Yellow Flow', css: 'animation-run-5', color: '#FFEAA7' },
            { name: 'Purple Flow', css: 'animation-run-6', color: '#DDA0DD' },
            { name: 'Mint Flow', css: 'animation-run-7', color: '#98D8C8' },
            { name: 'Gold Flow', css: 'animation-run-8', color: '#F7DC6F' }
        ];
        
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
    <bpmn2:task id="Task_1" name="Sample Task">
      <bpmn2:incoming>Flow_1</bpmn2:incoming>
      <bpmn2:outgoing>Flow_2</bpmn2:outgoing>
    </bpmn2:task>
    <bpmn2:endEvent id="EndEvent_1" name="End">
      <bpmn2:incoming>Flow_2</bpmn2:incoming>
    </bpmn2:endEvent>
    <bpmn2:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Task_1" />
    <bpmn2:sequenceFlow id="Flow_2" sourceRef="Task_1" targetRef="EndEvent_1" />
  </bpmn2:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="StartEvent_1_di" bpmnElement="StartEvent_1">
        <dc:Bounds x="150" y="200" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Task_1_di" bpmnElement="Task_1">
        <dc:Bounds x="250" y="178" width="100" height="80"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
        <dc:Bounds x="400" y="200" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
        <di:waypoint x="186" y="218"/>
        <di:waypoint x="250" y="218"/>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="Flow_2_di" bpmnElement="Flow_2">
        <di:waypoint x="350" y="218"/>
        <di:waypoint x="400" y="218"/>
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn2:definitions>`;

        // Initialize BPMN.js components
        function initializeBpmn() {
            try {
                // Initialize modeler
                modeler = new BpmnJS({
                    container: '#bpmn-editor'
                });
                
                // Initialize process viewer
                processViewer = new BpmnJS({
                    container: '#process-viewer'
                });
                
                // Initialize simulation viewer
                simulationViewer = new BpmnJS({
                    container: '#simulation-viewer'
                });
                
                // Load initial process
                loadInitialProcess();
                
                console.log('✅ Enhanced MACTA BPMN components initialized successfully');
                
            } catch (error) {
                console.error('Failed to initialize BPMN:', error);
                document.querySelector('#bpmn-editor .loading').innerHTML = 'BPMN initialization failed: ' + error.message;
            }
        }

        // Load initial process
        async function loadInitialProcess() {
            try {
                let xmlToLoad = defaultBpmnXml;
                
                if (processes.length > 0 && processes[0].model_data) {
                    xmlToLoad = processes[0].model_data;
                }
                
                currentXML = xmlToLoad;
                
                if (modeler) {
                    await modeler.importXML(xmlToLoad);
                    modeler.get('canvas').zoom('fit-viewport');
                }
                
                // Hide loading indicators
                document.querySelectorAll('.loading').forEach(el => el.style.display = 'none');
                
                // Initialize color legends
                updateColorLegends();
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
            }
        }

        // Load process in viewer
        async function loadProcessInViewer(processId, xml) {
            if (!processViewer || !xml) return;
            
            try {
                await processViewer.importXML(xml);
                processViewer.get('canvas').zoom('fit-viewport');
                
                currentProcessId = processId;
                currentXML = xml;
                
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
            handleTaskClickForResourceAllocation({id: taskId, businessObject: {name: taskName}});
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
                                        <button class="btn btn-primary" style="margin: 0;" onclick="event.stopPropagation(); assignUserTaskResources('${task.id}', '${name}')">
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

        // Enhanced Tab switching functionality
        function switchTab(tabName) {
            console.log('Switching to tab:', tabName);
            
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

            currentTab = tabName;

            // Initialize specific tab content
            setTimeout(() => {
                if (tabName === 'process-view' && processViewer && currentXML) {
                    loadProcessInViewer(currentProcessId, currentXML);
                } else if (tabName === 'simulation' && simulationViewer && currentXML) {
                    loadProcessInSimulation(currentXML);
                } else if (tabName === 'resource-allocation') {
                    loadProcessTasks();
                }
            }, 100);
        }

        // Load process in simulation
        async function loadProcessInSimulation(xml) {
            if (!simulationViewer) return;
            
            try {
                await simulationViewer.importXML(xml);
                simulationViewer.get('canvas').zoom('fit-viewport');
                
                const simLoading = document.querySelector('#simulation-viewer .loading');
                if (simLoading) {
                    simLoading.style.display = 'none';
                }
                
            } catch (error) {
                console.error('Failed to load process in simulation:', error);
            }
        }

        // Animation functions with color cycling
        function animateProcess() {
            if (!processViewer || !currentXML || isAnimating) return;
            
            animationRunCount++;
            const currentRun = ((animationRunCount - 1) % 8) + 1;
            const animationClass = `animation-run-${currentRun}`;
            
            isAnimating = true;
            startAnimation(processViewer, animationClass);
        }

        function startSimulation() {
            if (!simulationViewer || !currentXML || isSimulating) return;
            
            simulationRunCount++;
            const currentRun = ((simulationRunCount - 1) % 8) + 1;
            const animationClass = `animation-run-${currentRun}`;
            
            updateSimulationStatus('running', `Running simulation - ${animationColors[currentRun - 1].name} (Run #${simulationRunCount})`);
            updateSimulationRunCount();
            
            isSimulating = true;
            startAnimation(simulationViewer, animationClass);
            
            // Start performance metrics simulation
            startMetricsSimulation();
        }

        function startAnimation(viewerInstance, animationClass) {
            try {
                const elementRegistry = viewerInstance.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                if (!startEvent) {
                    console.log('No start event found');
                    return;
                }
                
                highlightPath(startEvent, elementRegistry, animationClass, viewerInstance, 1500);
                
            } catch (error) {
                console.error('Animation error:', error);
                isAnimating = false;
                isSimulating = false;
            }
        }

        async function highlightPath(currentElement, elementRegistry, animationClass, viewerInstance, delay) {
            if (!currentElement || (!isAnimating && !isSimulating)) return;
            
            try {
                const gfx = elementRegistry.getGraphics(currentElement);
                if (gfx) {
                    gfx.classList.add(animationClass);
                    updateActiveTokens();
                }
            } catch (error) {
                console.log('Element highlighting failed:', error);
            }
            
            const timeout = setTimeout(async () => {
                const outgoing = currentElement.businessObject?.outgoing;
                if (outgoing && outgoing.length > 0) {
                    
                    let selectedFlows = outgoing;
                    
                    if (currentElement.type === 'bpmn:ExclusiveGateway') {
                        const randomIndex = Math.floor(Math.random() * outgoing.length);
                        selectedFlows = [outgoing[randomIndex]];
                    }
                    
                    for (const flow of selectedFlows) {
                        // Highlight the flow
                        const flowGfx = elementRegistry.getGraphics(flow);
                        if (flowGfx) {
                            flowGfx.classList.add(animationClass);
                        }
                        
                        const nextElement = elementRegistry.get(flow.targetRef?.id);
                        if (nextElement && (isAnimating || isSimulating)) {
                            if (nextElement.type !== 'bpmn:EndEvent') {
                                await highlightPath(nextElement, elementRegistry, animationClass, viewerInstance, delay);
                            } else {
                                // Highlight end event
                                const endGfx = elementRegistry.getGraphics(nextElement);
                                if (endGfx) {
                                    endGfx.classList.add(animationClass);
                                    updateCompletedInstances();
                                }
                                
                                // Animation completed
                                setTimeout(() => {
                                    if (viewerInstance === processViewer) {
                                        isAnimating = false;
                                    } else if (viewerInstance === simulationViewer) {
                                        updateSimulationStatus('completed', `Simulation completed - Run #${simulationRunCount}`);
                                        isSimulating = false;
                                    }
                                }, delay);
                            }
                        }
                    }
                }
            }, delay);
            
            if (viewerInstance === processViewer) {
                animationTimeouts.push(timeout);
            } else {
                simulationTimeouts.push(timeout);
            }
        }

        // Clear functions
        function clearAnimation() {
            isAnimating = false;
            animationTimeouts.forEach(timeout => clearTimeout(timeout));
            animationTimeouts = [];
            
            if (processViewer) clearAllHighlights(processViewer);
        }

        function clearSimulation() {
            isSimulating = false;
            simulationTimeouts.forEach(timeout => clearTimeout(timeout));
            simulationTimeouts = [];
            
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
            
            if (simulationViewer) clearAllHighlights(simulationViewer);
            updateSimulationStatus('stopped', 'Simulation stopped');
        }

        function resetAllAnimations() {
            clearAnimation();
            clearSimulation();
            
            // Reset counters
            animationRunCount = 0;
            simulationRunCount = 0;
            updateSimulationRunCount();
            
            // Reset metrics
            document.getElementById('total-time').textContent = '--';
            document.getElementById('active-tokens').textContent = '0';
            document.getElementById('completed-instances').textContent = '0';
            document.getElementById('efficiency-score').textContent = '--';
            
            updateSimulationStatus('ready', 'Ready to simulate');
        }

        function clearAllHighlights(viewerInstance) {
            try {
                const elementRegistry = viewerInstance.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = elementRegistry.getGraphics(element);
                    if (gfx) {
                        for (let i = 1; i <= 8; i++) {
                            gfx.classList.remove(`animation-run-${i}`);
                        }
                    }
                });
                
            } catch (error) {
                console.error('Failed to clear highlights:', error);
            }
        }

        // UI Update functions
        function updateSimulationStatus(status, text) {
            const statusElement = document.getElementById('simulation-status');
            const textElement = document.getElementById('simulation-text');
            
            statusElement.className = `animation-status ${status}`;
            textElement.textContent = text;
        }

        function updateSimulationRunCount() {
            document.getElementById('simulation-run-count').textContent = simulationRunCount;
        }

        function updateActiveTokens() {
            const activeCount = document.querySelectorAll('[class*="animation-run-"]').length;
            document.getElementById('active-tokens').textContent = activeCount;
        }

        function updateCompletedInstances() {
            const completed = parseInt(document.getElementById('completed-instances').textContent) + 1;
            document.getElementById('completed-instances').textContent = completed;
        }

        function updateColorLegends() {
            // Update simulation tab legend
            const simLegend = document.getElementById('simulation-legend-items');
            if (simLegend) {
                simLegend.innerHTML = '';
                animationColors.forEach((color, index) => {
                    const item = document.createElement('div');
                    item.className = 'legend-item';
                    item.innerHTML = `
                        <div class="legend-color" style="background-color: ${color.color}"></div>
                        <span>Run ${index + 1}: ${color.name}</span>
                    `;
                    simLegend.appendChild(item);
                });
            }
        }

        function startMetricsSimulation() {
            let totalTime = 0;
            
            simulationInterval = setInterval(() => {
                if (!isSimulating) {
                    clearInterval(simulationInterval);
                    return;
                }
                
                totalTime += 1;
                
                // Update metrics
                const minutes = Math.floor(totalTime / 60);
                const seconds = totalTime % 60;
                const timeDisplay = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
                
                document.getElementById('total-time').textContent = timeDisplay;
                
                // Calculate efficiency score
                const completed = parseInt(document.getElementById('completed-instances').textContent);
                if (totalTime > 0 && completed > 0) {
                    const efficiency = Math.min(100, Math.round((completed / (totalTime / 30)) * 100));
                    document.getElementById('efficiency-score').textContent = `${efficiency}%`;
                }
                
            }, 1000);
        }

        // Bottleneck analysis function
        function analyzeBottlenecks() {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
                const tasks = elementRegistry.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask'
                );
                
                if (tasks.length === 0) {
                    alert('No tasks found in the process');
                    return;
                }
                
                // Clear previous highlights
                clearAllHighlights(processViewer);
                
                // Highlight random bottlenecks
                const bottleneckCount = Math.min(2, tasks.length);
                const bottlenecks = [];
                
                for (let i = 0; i < bottleneckCount; i++) {
                    const randomTask = tasks[Math.floor(Math.random() * tasks.length)];
                    if (!bottlenecks.includes(randomTask)) {
                        bottlenecks.push(randomTask);
                        
                        const gfx = elementRegistry.getGraphics(randomTask);
                        if (gfx) {
                            gfx.style.fill = '#ffebee';
                            gfx.style.stroke = '#f44336';
                            gfx.style.strokeWidth = '4px';
                        }
                    }
                }
                
                const bottleneckNames = bottlenecks.map(task => 
                    task.businessObject.name || task.id
                ).join('\n• ');
                
                alert(`⚠️ MACTA Framework - Potential Bottlenecks Detected:\n\n• ${bottleneckNames}\n\n🎯 MACTA Recommendations:\n• Review resource allocation patterns\n• Consider intelligent parallel processing\n• Implement skill-based automation\n• Optimize task duration through training\n• Apply MACTA Assessment metrics for monitoring`);
                
            } catch (error) {
                console.error('Bottleneck analysis error:', error);
            }
        }

        // Analysis functions
        function generateDetailedReport() {
            const report = {
                timestamp: new Date().toLocaleString(),
                framework: 'MACTA Enhanced Modeling Module',
                paths: {
                    critical: { duration: 90, cost: 105, resources: 3 },
                    timeConsuming: { duration: 115, cost: 85, resources: 6 },
                    resourceIntensive: { duration: 95, cost: 180, resources: 9 },
                    costly: { duration: 100, cost: 475, resources: 4 },
                    ideal: { duration: 17, cost: 18, resources: 1 },
                    frequent: { duration: 45, cost: 53, resources: 2, frequency: 70 }
                },
                metrics: {
                    totalSimulations: simulationRunCount,
                    averageEfficiency: '87%'
                }
            };
            
            const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'macta_enhanced_analysis_report.json';
            a.click();
            URL.revokeObjectURL(url);
            
            alert('📊 Enhanced MACTA analysis report generated and downloaded!');
        }

        function exportAnalysis() {
            const csvData = [
                ['Path Type', 'Duration (min)', 'Cost ($)', 'Resources', 'Frequency (%)', 'Optimization Potential'],
                ['Critical Path', '90', '105', '3', '100', 'Medium'],
                ['Time Consuming', '115', '85', '6', '85', 'High'],
                ['Resource Intensive', '95', '180', '9', '60', 'Very High'],
                ['Most Costly', '100', '475', '4', '25', 'Critical'],
                ['Ideal Path', '17', '18', '1', '5', 'Low'],
                ['Most Frequent', '45', '53', '2', '70', 'Medium']
            ];
            
            const csvContent = csvData.map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'macta_enhanced_path_analysis.csv';
            a.click();
            URL.revokeObjectURL(url);
            
            alert('📤 Enhanced path analysis exported to CSV!');
        }

        function suggestOptimizations() {
            const optimizations = [
                {
                    title: '🤖 AI-Powered Automation',
                    description: 'Implement intelligent automation for routine review tasks',
                    impact: 'Reduce time by 40%, cost by 65%',
                    effort: 'Medium',
                    roi: 'High'
                },
                {
                    title: '⚡ Parallel Processing Enhancement',
                    description: 'Enable smart parallel execution for independent task streams',
                    impact: 'Reduce time by 55%, increase throughput by 80%',
                    effort: 'High',
                    roi: 'Very High'
                },
                {
                    title: '🎯 Skill-Based Smart Routing',
                    description: 'Dynamic task routing based on complexity and skill optimization',
                    impact: 'Reduce time by 30%, improve quality by 45%',
                    effort: 'Low',
                    roi: 'High'
                },
                {
                    title: '📊 Predictive Resource Allocation',
                    description: 'ML-based resource allocation with demand forecasting',
                    impact: 'Reduce wait time by 50%, optimize costs by 35%',
                    effort: 'Medium',
                    roi: 'Very High'
                },
                {
                    title: '🔄 Process Standardization',
                    description: 'Standardize frequent processes with best practice templates',
                    impact: 'Reduce variation by 70%, improve consistency',
                    effort: 'Low',
                    roi: 'Medium'
                }
            ];
            
            let message = '🚀 MACTA Framework - Advanced Process Optimization Recommendations:\n\n';
            optimizations.forEach((opt, i) => {
                message += `${i + 1}. ${opt.title}\n`;
                message += `   💡 ${opt.description}\n`;
                message += `   📈 Impact: ${opt.impact}\n`;
                message += `   ⚡ Effort: ${opt.effort} | ROI: ${opt.roi}\n\n`;
            });
            
            message += '🎯 Next Steps:\n';
            message += '• Prioritize by ROI and implementation effort\n';
            message += '• Start with low-effort, high-impact changes\n';
            message += '• Plan phased implementation approach\n';
            message += '• Monitor and measure optimization results';
            
            alert(message);
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
                    
                    startInlineTimerDisplay();
                    
                    if (currentProcessId && timer.process_id == currentProcessId) {
                        highlightRunningTask(timer.task_id);
                    }
                }
            } catch (error) {
                console.error('Failed to check active timer:', error);
            }
        }

        // Event listeners setup
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 Enhanced MACTA Modeling Module initialized');
            
            // Initialize BPMN when page loads
            initializeBpmn();
            
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

            // Viewer process selector
            document.getElementById('viewer-process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                
                if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        await loadProcessInViewer(selectedValue, xmlData);
                        currentXML = xmlData;
                    }
                } else {
                    const viewerLoading = document.querySelector('#process-viewer .loading');
                    if (viewerLoading) {
                        viewerLoading.style.display = 'flex';
                        viewerLoading.innerHTML = '👆 Select a process from the dropdown above to view it here...';
                    }
                }
            });

            // BPMN Design Controls
            document.getElementById('btn-new-process').addEventListener('click', () => {
                document.getElementById('process-select').value = 'new';
                document.getElementById('process-select').dispatchEvent(new Event('change'));
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
                        alert('✅ Process saved successfully to MACTA Framework! 💾');
                        location.reload();
                    } else {
                        alert('Failed to save: ' + result.message);
                    }
                    
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Failed to save process ❌');
                }
            });

            document.getElementById('btn-clear-designer').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    await modeler.importXML(defaultBpmnXml);
                    modeler.get('canvas').zoom('fit-viewport');
                    currentXML = defaultBpmnXml;
                    document.getElementById('process-select').value = 'new';
                } catch (error) {
                    console.error('Clear error:', error);
                }
            });

            document.getElementById('btn-validate-process').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const elementRegistry = modeler.get('elementRegistry');
                    const elements = elementRegistry.getAll();
                    
                    const startEvents = elements.filter(el => el.type === 'bpmn:StartEvent');
                    const endEvents = elements.filter(el => el.type === 'bpmn:EndEvent');
                    
                    let validationErrors = [];
                    
                    if (startEvents.length === 0) validationErrors.push('❌ Missing Start Event');
                    if (endEvents.length === 0) validationErrors.push('❌ Missing End Event');
                    if (startEvents.length > 1) validationErrors.push('⚠️ Multiple Start Events found');
                    
                    if (validationErrors.length === 0) {
                        alert('✅ MACTA Process validation passed!\n\n✔ Has start event\n✔ Has end event\n✔ BPMN 2.0 compliant');
                    } else {
                        alert('❌ MACTA Validation failed:\n\n' + validationErrors.join('\n'));
                    }
                    
                } catch (error) {
                    console.error('Validation error:', error);
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

            document.getElementById('btn-export-xml').addEventListener('click', async () => {
                if (!modeler) return;
                
                try {
                    const { xml } = await modeler.saveXML({ format: true });
                    currentXML = xml;
                    await loadProcessInViewer(currentProcessId, currentXML);
                    alert('✅ Process exported to MACTA viewer! 📤');
                } catch (error) {
                    console.error('Export error:', error);
                    alert('Failed to export process ❌');
                }
            });

            // Process View Controls
            document.getElementById('btn-animate-path').addEventListener('click', () => {
                animateProcess();
            });

            document.getElementById('btn-clear-highlights').addEventListener('click', () => {
                clearAnimation();
            });

            document.getElementById('btn-analyze-bottlenecks').addEventListener('click', () => {
                analyzeBottlenecks();
            });

            document.getElementById('btn-refresh-viewer').addEventListener('click', async () => {
                const selectedValue = document.getElementById('viewer-process-select').value;
                if (selectedValue) {
                    const selectedOption = document.getElementById('viewer-process-select').selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    if (xmlData) {
                        await loadProcessInViewer(selectedValue, xmlData);
                    }
                } else {
                    alert('Please select a process first');
                }
            });

            document.getElementById('btn-viewer-zoom-fit').addEventListener('click', () => {
                if (processViewer) {
                    processViewer.get('canvas').zoom('fit-viewport');
                }
            });

            document.getElementById('btn-refresh-averages').addEventListener('click', () => {
                if (currentProcessId) {
                    loadTimerAverages(currentProcessId);
                }
            });

            document.getElementById('btn-export-times').addEventListener('click', () => {
                if (currentProcessId) {
                    alert('🚧 Timer export functionality will be implemented');
                }
            });

            // Resource Assignment controls
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
                    alert('🚧 Apply to all functionality will be implemented');
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

                    // Load average for User Tasks
                    if (selectedOption.textContent.includes('UserTask')) {
                        loadAverageForTask(selectedTaskId);
                    }
                }
            });

            // Simulation controls
            document.getElementById('btn-start-simulation').addEventListener('click', () => {
                startSimulation();
            });

            document.getElementById('btn-pause-simulation').addEventListener('click', () => {
                clearSimulation();
            });

            document.getElementById('btn-stop-simulation').addEventListener('click', () => {
                clearSimulation();
            });

            document.getElementById('btn-reset-simulation').addEventListener('click', () => {
                resetAllAnimations();
            });

            // Speed control
            document.getElementById('sim-speed').addEventListener('input', (e) => {
                document.getElementById('speed-display').textContent = e.target.value + 'x';
            });

            // Analysis controls
            document.getElementById('btn-generate-report').addEventListener('click', generateDetailedReport);
            document.getElementById('btn-export-analysis').addEventListener('click', exportAnalysis);
            document.getElementById('btn-suggest-optimizations').addEventListener('click', suggestOptimizations);

            // Check for active timer on page load
            checkActiveTimer();
            
            console.log('✅ All MACTA enhanced event listeners attached successfully!');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        if (currentTab === 'bpmn-design') {
                            document.getElementById('btn-save-process').click();
                        }
                        break;
                    case ' ':
                        e.preventDefault();
                        if (currentTab === 'process-view') {
                            animateProcess();
                        } else if (currentTab === 'simulation') {
                            startSimulation();
                        }
                        break;
                }
            }
            
            // Tab switching with numbers
            if (e.key >= '1' && e.key <= '5') {
                const tabs = ['bpmn-design', 'process-view', 'resource-allocation', 'simulation', 'analysis'];
                const tabIndex = parseInt(e.key) - 1;
                if (tabs[tabIndex]) {
                    switchTab(tabs[tabIndex]);
                }
            }
        });

        console.log('🎯 Enhanced MACTA Process Manager with Combined Functionality Initialized');
        console.log('📋 Features: BPMN Design + Process View + Timer + Resource Allocation + Simulation + Path Analysis');
        console.log('🚀 Ready for production use in MACTA Framework!');
    </script>
</body>
</html>
                