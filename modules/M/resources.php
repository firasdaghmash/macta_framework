<?php
// modules/M/resources.php - MACTA Resources Management (Final Fixed Version)

// Function to parse tasks from BPMN XML
function parseTasksFromXML($xml_content) {
    $tasks = array();
    
    try {
        // Clean up the XML content
        $xml_content = trim($xml_content);
        
        // Load XML
        $xml = new SimpleXMLElement($xml_content);
        
        // Register BPMN namespace if present
        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces['bpmn']) || isset($namespaces['bpmn2'])) {
            $ns = isset($namespaces['bpmn']) ? 'bpmn' : 'bpmn2';
            $xml->registerXPathNamespace('bpmn', $namespaces[$ns]);
            
            // Find all task elements with namespace
            $taskElements = $xml->xpath('//bpmn:task | //bpmn:userTask | //bpmn:serviceTask | //bpmn:scriptTask | //bpmn:manualTask');
        } else {
            // Find all task elements without namespace
            $taskElements = $xml->xpath('//task | //userTask | //serviceTask | //scriptTask | //manualTask');
        }
        
        if ($taskElements) {
            foreach ($taskElements as $task) {
                $taskId = (string)$task['id'];
                $taskName = (string)$task['name'];
                
                // If no name, use ID or create a readable name
                if (empty($taskName)) {
                    $taskName = !empty($taskId) ? $taskId : 'Unnamed Task';
                }
                
                if (!empty($taskId)) {
                    $tasks[] = array(
                        'task_id' => $taskId,
                        'task_name' => $taskName
                    );
                }
            }
        }
        
        // If no tasks found with standard BPMN elements, try to find any elements with 'task' in the name
        if (empty($tasks)) {
            $allElements = $xml->xpath('//*[contains(local-name(), "task") or contains(local-name(), "Task")]');
            if ($allElements) {
                foreach ($allElements as $element) {
                    $taskId = (string)$element['id'];
                    $taskName = (string)$element['name'];
                    
                    if (empty($taskName)) {
                        $taskName = !empty($taskId) ? $taskId : ucfirst($element->getName());
                    }
                    
                    if (!empty($taskId)) {
                        $tasks[] = array(
                            'task_id' => $taskId,
                            'task_name' => $taskName
                        );
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        // If XML parsing fails, return empty array to fall back to other methods
        error_log("XML parsing error: " . $e->getMessage());
    }
    
    return $tasks;
}

// Handle AJAX requests first before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        if (file_exists('../../config/config.php')) {
            require_once '../../config/config.php';
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ));
            
            if ($_GET['action'] === 'get_tasks') {
                $process_id = isset($_GET['process_id']) ? $_GET['process_id'] : '';
                if ($process_id) {
                    $tasks = array();
                    
                    // First try to get tasks from the process_tasks table (correct MACTA schema)
                    try {
                        $stmt = $pdo->prepare("SELECT task_id, task_name FROM process_tasks WHERE process_id = ? ORDER BY task_name");
                        $stmt->execute(array($process_id));
                        $tasks = $stmt->fetchAll();
                    } catch (Exception $e) {
                        // Table might not exist, continue to next attempt
                    }
                    
                    // If no tasks found, try to get the process XML/BPMN definition
                    if (empty($tasks)) {
                        try {
                            $stmt = $pdo->prepare("SELECT model_data FROM process_models WHERE id = ?");
                            $stmt->execute(array($process_id));
                            $process = $stmt->fetch();
                            
                            if ($process && !empty($process['model_data'])) {
                                // Parse XML to extract tasks
                                $tasks = parseTasksFromXML($process['model_data']);
                            }
                        } catch (Exception $e) {
                            // Continue to fallback methods
                        }
                    }
                    
                    // If no tasks from database or XML, try alternative table structures
                    if (empty($tasks)) {
                        try {
                            $stmt = $pdo->prepare("SELECT DISTINCT id as task_id, name as task_name FROM tasks WHERE process_id = ? ORDER BY name");
                            $stmt->execute(array($process_id));
                            $tasks = $stmt->fetchAll();
                        } catch (Exception $e) {
                            // Table might not exist, continue to fallback
                        }
                    }
                    
                    // If still no tasks, create some sample tasks based on process_id
                    if (empty($tasks)) {
                        $tasks = array(
                            array('task_id' => 'TASK_' . $process_id . '_001', 'task_name' => 'Process Task 001'),
                            array('task_id' => 'TASK_' . $process_id . '_002', 'task_name' => 'Process Task 002'),
                            array('task_id' => 'TASK_' . $process_id . '_003', 'task_name' => 'Process Task 003'),
                            array('task_id' => 'TASK_' . $process_id . '_004', 'task_name' => 'Process Task 004'),
                            array('task_id' => 'TASK_' . $process_id . '_005', 'task_name' => 'Process Task 005')
                        );
                    }
                    
                    echo json_encode($tasks);
                } else {
                    echo json_encode(array());
                }
                exit;
            }
            
            if ($_GET['action'] === 'get_processes') {
                $project_id = isset($_GET['project_id']) ? $_GET['project_id'] : '';
                if ($project_id) {
                    $stmt = $pdo->prepare("SELECT id, name FROM process_models WHERE project_id = ? ORDER BY name");
                    $stmt->execute(array($project_id));
                    $processes_filtered = $stmt->fetchAll();
                    echo json_encode($processes_filtered);
                } else {
                    echo json_encode(array());
                }
                exit;
            }
            
            if ($_GET['action'] === 'get_timer_averages') {
                $process_id = isset($_GET['process_id']) ? $_GET['process_id'] : '';
                if ($process_id) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT task_id, average_duration, session_count, is_overridden, override_value 
                            FROM timer_averages 
                            WHERE process_id = ?
                        ");
                        $stmt->execute(array($process_id));
                        $averages = $stmt->fetchAll();
                        echo json_encode(array('success' => true, 'averages' => $averages));
                    } catch (Exception $e) {
                        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
                    }
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Process ID required'));
                }
                exit;
            }
            
        } else {
            echo json_encode(array('error' => 'Database configuration not found'));
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
        exit;
    }
}

// Set HTML content type for regular page requests
header('Content-Type: text/html; charset=utf-8');

$resources = array();
$projects = array();
$processes = array();
$project_resources = array();
$process_resources = array();
$tasks = array();
$db_error = '';
$selected_project = '';
$selected_process = '';
$selected_project_for_process = '';
$active_tab = 'global-resources';

// Get URL parameters
if (isset($_GET['project_id'])) {
    $selected_project = $_GET['project_id'];
}

if (isset($_GET['process_id'])) {
    $selected_process = $_GET['process_id'];
}

if (isset($_GET['process_project_id'])) {
    $selected_project_for_process = $_GET['process_project_id'];
}

if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
}

try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        
        // Create tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_resources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            resource_id INT NOT NULL,
            quantity_required DECIMAL(10,2) DEFAULT 1.0,
            assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active',
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (resource_id) REFERENCES enhanced_resources(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (project_id, resource_id)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS process_resources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            process_id INT NOT NULL,
            task_id VARCHAR(100) NOT NULL,
            resource_id INT NOT NULL,
            quantity_required DECIMAL(10,2) DEFAULT 1.0,
            duration_minutes INT DEFAULT 60,
            complexity_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
            priority_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active',
            FOREIGN KEY (process_id) REFERENCES process_models(id) ON DELETE CASCADE,
            FOREIGN KEY (resource_id) REFERENCES enhanced_resources(id) ON DELETE CASCADE
        )");
        
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            
            try {
                if ($action === 'add_resource') {
                    $stmt = $pdo->prepare("INSERT INTO enhanced_resources (name, type, hourly_cost, skill_level, availability, max_concurrent_tasks, department, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute(array(
                        $_POST['name'],
                        $_POST['type'],
                        $_POST['hourly_cost'],
                        $_POST['skill_level'],
                        isset($_POST['availability']) ? $_POST['availability'] : 100,
                        1,
                        isset($_POST['department']) ? $_POST['department'] : '',
                        isset($_POST['location']) ? $_POST['location'] : ''
                    ));
                    echo json_encode(array('success' => true, 'message' => 'Resource added successfully'));
                    
                } elseif ($action === 'update_resource') {
                    $stmt = $pdo->prepare("UPDATE enhanced_resources SET name=?, type=?, hourly_cost=?, skill_level=?, availability=?, department=?, location=? WHERE id=?");
                    $stmt->execute(array(
                        $_POST['name'],
                        $_POST['type'],
                        $_POST['hourly_cost'],
                        $_POST['skill_level'],
                        $_POST['availability'],
                        $_POST['department'],
                        $_POST['location'],
                        $_POST['id']
                    ));
                    echo json_encode(array('success' => true, 'message' => 'Resource updated successfully'));
                    
                } elseif ($action === 'delete_resource') {
                    $stmt = $pdo->prepare("DELETE FROM enhanced_resources WHERE id=?");
                    $stmt->execute(array($_POST['id']));
                    echo json_encode(array('success' => true, 'message' => 'Resource deleted successfully'));
                    
                } elseif ($action === 'assign_to_project') {
                    $stmt = $pdo->prepare("INSERT INTO project_resources (project_id, resource_id, quantity_required) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity_required = VALUES(quantity_required), status = 'active'");
                    $stmt->execute(array($_POST['project_id'], $_POST['resource_id'], $_POST['quantity_required']));
                    echo json_encode(array('success' => true, 'message' => 'Resource assigned to project successfully'));
                    
                } elseif ($action === 'assign_to_process') {
                    $stmt = $pdo->prepare("INSERT INTO process_resources (process_id, task_id, resource_id, quantity_required, duration_minutes, complexity_level, priority_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute(array(
                        $_POST['process_id'], 
                        $_POST['task_id'], 
                        $_POST['resource_id'], 
                        $_POST['quantity_required'],
                        $_POST['duration_minutes'],
                        $_POST['complexity_level'],
                        $_POST['priority_level']
                    ));
                    echo json_encode(array('success' => true, 'message' => 'Resource assigned to process successfully'));
                    
                } elseif ($action === 'remove_assignment') {
                    $assignment_id = $_POST['assignment_id'];
                    $assignment_type = $_POST['assignment_type'];
                    
                    if ($assignment_type === 'project') {
                        $stmt = $pdo->prepare("DELETE FROM project_resources WHERE id=?");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM process_resources WHERE id=?");
                    }
                    $stmt->execute(array($assignment_id));
                    echo json_encode(array('success' => true, 'message' => 'Assignment removed successfully'));
                }
                
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
        }
        
        // Get all global resources
        $stmt = $pdo->prepare("SELECT * FROM enhanced_resources ORDER BY name");
        $stmt->execute();
        $resources = $stmt->fetchAll();
        
        // Get all projects
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        // Get all processes
        $stmt = $pdo->prepare("SELECT * FROM process_models ORDER BY name");
        $stmt->execute();
        $processes = $stmt->fetchAll();
        
        // Get processes filtered by project for process tab
        $processes_for_selected_project = array();
        if ($selected_project_for_process || $selected_process) {
            $project_for_process = $selected_project_for_process ?: $selected_process;
            // Get project ID from process if only process is selected
            if ($selected_process && !$selected_project_for_process) {
                $stmt = $pdo->prepare("SELECT project_id FROM process_models WHERE id = ?");
                $stmt->execute(array($selected_process));
                $process_info = $stmt->fetch();
                if ($process_info) {
                    $selected_project_for_process = $process_info['project_id'];
                }
            }
            
            if ($selected_project_for_process) {
                $stmt = $pdo->prepare("SELECT id, name FROM process_models WHERE project_id = ? ORDER BY name");
                $stmt->execute(array($selected_project_for_process));
                $processes_for_selected_project = $stmt->fetchAll();
            }
        }
        
        // Get project resources if project is selected
        if ($selected_project) {
            $stmt = $pdo->prepare("
                SELECT pr.*, er.name as resource_name, er.type, er.hourly_cost, er.skill_level, p.name as project_name
                FROM project_resources pr 
                JOIN enhanced_resources er ON pr.resource_id = er.id 
                JOIN projects p ON pr.project_id = p.id
                WHERE pr.project_id = ? AND pr.status = 'active'
                ORDER BY er.name
            ");
            $stmt->execute(array($selected_project));
            $project_resources = $stmt->fetchAll();
        }
        
        // Get process resources if process is selected
        if ($selected_process) {
            $stmt = $pdo->prepare("
                SELECT pr.*, er.name as resource_name, er.type, er.hourly_cost, er.skill_level, pm.name as process_name
                FROM process_resources pr 
                JOIN enhanced_resources er ON pr.resource_id = er.id 
                JOIN process_models pm ON pr.process_id = pm.id
                WHERE pr.process_id = ? AND pr.status = 'active'
                ORDER BY pr.task_id, er.name
            ");
            $stmt->execute(array($selected_process));
            $process_resources = $stmt->fetchAll();
        }
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Resources DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA - Resources Management</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #FF6B35;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .tab-button.active {
            background: white;
            border-bottom: 3px solid #FF6B35;
            color: #FF6B35;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-orange { background: #FF6B35; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-human { background: #d4edda; color: #155724; }
        .badge-machine { background: #fff3cd; color: #856404; }
        .badge-hybrid { background: #d1ecf1; color: #0c5460; }
        .badge-software { background: #f8d7da; color: #721c24; }
        
        .badge-entry { background: #f8d7da; color: #721c24; }
        .badge-intermediate { background: #fff3cd; color: #856404; }
        .badge-advanced { background: #d4edda; color: #155724; }
        .badge-expert { background: #d1ecf1; color: #0c5460; }
        
        .badge-low { background: #d4edda; color: #155724; }
        .badge-medium { background: #fff3cd; color: #856404; }
        .badge-high { background: #f8d7da; color: #721c24; }
        .badge-critical { background: #dc3545; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .status-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .selector-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .selector-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }
        
        .selector-item {
            flex: 1;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #FF6B35;
            font-size: 24px;
        }
        
        .summary-card p {
            margin: 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>MACTA - Resources Management</h2>
            <p>Comprehensive resource management for projects and processes</p>
        </div>

        <div id="status-message" class="status-message"></div>

        <div class="tabs">
            <button class="tab-button <?php echo ($active_tab === 'global-resources') ? 'active' : ''; ?>" onclick="showTab(event, 'global-resources')">Global Resources</button>
            <button class="tab-button <?php echo ($active_tab === 'project-resources') ? 'active' : ''; ?>" onclick="showTab(event, 'project-resources')">Project Resources</button>
            <button class="tab-button <?php echo ($active_tab === 'process-resources') ? 'active' : ''; ?>" onclick="showTab(event, 'process-resources')">Process Resources</button>
            <button class="tab-button <?php echo ($active_tab === 'summary') ? 'active' : ''; ?>" onclick="showTab(event, 'summary')">Summary</button>
        </div>

        <!-- Global Resources Tab -->
        <div id="global-resources" class="tab-content <?php echo ($active_tab === 'global-resources') ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <span>Global Resource Library</span>
                    <button class="btn btn-orange" onclick="openAddModal()">Add New Resource</button>
                </div>
                <div class="card-body">
                    <?php if ($db_error): ?>
                        <div class="status-message error" style="display: block;">
                            <strong>Database Error:</strong> <?php echo htmlspecialchars($db_error); ?>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Hourly Cost</th>
                                    <th>Skill Level</th>
                                    <th>Availability</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $resource): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resource['name']); ?></td>
                                        <td><span class="badge badge-<?php echo htmlspecialchars($resource['type']); ?>"><?php echo htmlspecialchars($resource['type']); ?></span></td>
                                        <td>$<?php echo number_format($resource['hourly_cost'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo htmlspecialchars($resource['skill_level']); ?>"><?php echo htmlspecialchars($resource['skill_level']); ?></span></td>
                                        <td><?php echo number_format($resource['availability'], 1); ?>%</td>
                                        <td><?php echo htmlspecialchars($resource['department']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editRes(<?php echo $resource['id']; ?>)">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteRes(<?php echo $resource['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Project Resources Tab -->
        <div id="project-resources" class="tab-content <?php echo ($active_tab === 'project-resources') ? 'active' : ''; ?>">
            <div class="selector-group">
                <label><strong>Select Project:</strong></label>
                <select id="project-selector" onchange="loadProjectResources()">
                    <option value="">Choose a project...</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo ($selected_project == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($selected_project): ?>
                <!-- Project Summary Section -->
                <div class="summary-grid">
                    <?php
                    // Calculate project-specific metrics
                    $project_resource_count = count($project_resources);
                    $project_total_cost = 0;
                    $project_total_quantity = 0;
                    $project_resource_types = array();
                    
                    foreach ($project_resources as $pr) {
                        $project_total_cost += $pr['hourly_cost'] * $pr['quantity_required'];
                        $project_total_quantity += $pr['quantity_required'];
                        $project_resource_types[$pr['type']] = ($project_resource_types[$pr['type']] ?? 0) + 1;
                    }
                    
                    // Get project name
                    $project_name = '';
                    foreach ($projects as $project) {
                        if ($project['id'] == $selected_project) {
                            $project_name = $project['name'];
                            break;
                        }
                    }
                    ?>
                    <div class="summary-card">
                        <h3><?php echo $project_resource_count; ?></h3>
                        <p>Resources Assigned</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo number_format($project_total_quantity, 1); ?></h3>
                        <p>Total Quantity</p>
                    </div>
                    <div class="summary-card">
                        <h3>$<?php echo number_format($project_total_cost, 2); ?></h3>
                        <p>Total Hourly Cost</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo count($project_resource_types); ?></h3>
                        <p>Resource Types Used</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <span>Project Resource Assignments - <?php echo htmlspecialchars($project_name); ?></span>
                        <button class="btn btn-orange" onclick="openProjectModal()">Assign Resource to Project</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($project_resources)): ?>
                            <p>No resources assigned to this project yet.</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Resource Name</th>
                                        <th>Type</th>
                                        <th>Skill Level</th>
                                        <th>Hourly Cost</th>
                                        <th>Quantity Required</th>
                                        <th>Assigned Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($project_resources as $pr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pr['resource_name']); ?></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($pr['type']); ?>"><?php echo htmlspecialchars($pr['type']); ?></span></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($pr['skill_level']); ?>"><?php echo htmlspecialchars($pr['skill_level']); ?></span></td>
                                            <td>$<?php echo number_format($pr['hourly_cost'], 2); ?></td>
                                            <td><?php echo number_format($pr['quantity_required'], 1); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($pr['assigned_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" onclick="removeAssignment(<?php echo $pr['id']; ?>, 'project')">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p>Please select a project to view and manage its resource assignments.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Process Resources Tab -->
        <div id="process-resources" class="tab-content <?php echo ($active_tab === 'process-resources') ? 'active' : ''; ?>">
            <div class="selector-group">
                <div class="selector-row">
                    <div class="selector-item">
                        <label><strong>Select Project:</strong></label>
                        <select id="process-project-selector" onchange="loadProcessesByProject()">
                            <option value="">Choose a project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo ($selected_project_for_process == $project['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="selector-item">
                        <label><strong>Select Process:</strong></label>
                        <select id="process-selector" onchange="loadProcessResources()">
                            <option value="">Choose a process...</option>
                            <?php foreach ($processes_for_selected_project as $process): ?>
                                <option value="<?php echo $process['id']; ?>" <?php echo ($selected_process == $process['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($process['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php if ($selected_process): ?>
                <!-- Process Summary Section -->
                <div class="summary-grid">
                    <?php
                    // Calculate process-specific metrics
                    $process_resource_count = count($process_resources);
                    $process_total_duration = 0;
                    $process_total_quantity = 0;
                    $process_task_count = 0;
                    $process_complexity_levels = array();
                    $process_priority_levels = array();
                    $unique_tasks = array();
                    
                    foreach ($process_resources as $pr) {
                        $process_total_duration += $pr['duration_minutes'];
                        $process_total_quantity += $pr['quantity_required'];
                        $process_complexity_levels[$pr['complexity_level']] = ($process_complexity_levels[$pr['complexity_level']] ?? 0) + 1;
                        $process_priority_levels[$pr['priority_level']] = ($process_priority_levels[$pr['priority_level']] ?? 0) + 1;
                        $unique_tasks[$pr['task_id']] = true;
                    }
                    $process_task_count = count($unique_tasks);
                    
                    // Get process name
                    $process_name = '';
                    foreach ($processes_for_selected_project as $process) {
                        if ($process['id'] == $selected_process) {
                            $process_name = $process['name'];
                            break;
                        }
                    }
                    ?>
                    <div class="summary-card">
                        <h3><?php echo $process_resource_count; ?></h3>
                        <p>Resource Assignments</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo $process_task_count; ?></h3>
                        <p>Tasks with Resources</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo number_format($process_total_duration / 60, 1); ?>h</h3>
                        <p>Total Duration</p>
                    </div>
                    <div class="summary-card">
                        <h3><?php echo number_format($process_total_quantity, 1); ?></h3>
                        <p>Total Quantity</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <span>Process Resource Assignments - <?php echo htmlspecialchars($process_name); ?></span>
                        <button class="btn btn-orange" onclick="openProcessModal()">Assign Resource to Process</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($process_resources)): ?>
                            <p>No resources assigned to this process yet.</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Task ID</th>
                                        <th>Resource Name</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Duration</th>
                                        <th>Complexity</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($process_resources as $pr): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pr['task_id']); ?></td>
                                            <td><?php echo htmlspecialchars($pr['resource_name']); ?></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($pr['type']); ?>"><?php echo htmlspecialchars($pr['type']); ?></span></td>
                                            <td><?php echo number_format($pr['quantity_required'], 1); ?></td>
                                            <td><?php echo $pr['duration_minutes']; ?> min</td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($pr['complexity_level']); ?>"><?php echo htmlspecialchars($pr['complexity_level']); ?></span></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($pr['priority_level']); ?>"><?php echo htmlspecialchars($pr['priority_level']); ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" onclick="removeAssignment(<?php echo $pr['id']; ?>, 'process')">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p>Please select a project and process to view and manage resource assignments.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Tab -->
        <div id="summary" class="tab-content <?php echo ($active_tab === 'summary') ? 'active' : ''; ?>">
            <div class="summary-grid">
                <div class="summary-card">
                    <h3><?php echo count($resources); ?></h3>
                    <p>Total Resources</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo count($projects); ?></h3>
                    <p>Active Projects</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo count($processes); ?></h3>
                    <p>Available Processes</p>
                </div>
                <?php
                // Calculate total project assignments
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM project_resources WHERE status = 'active'");
                $stmt->execute();
                $project_assignments = $stmt->fetch()['total'];
                ?>
                <div class="summary-card">
                    <h3><?php echo $project_assignments; ?></h3>
                    <p>Project Assignments</p>
                </div>
                <?php
                // Calculate total process assignments
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM process_resources WHERE status = 'active'");
                $stmt->execute();
                $process_assignments = $stmt->fetch()['total'];
                ?>
                <div class="summary-card">
                    <h3><?php echo $process_assignments; ?></h3>
                    <p>Process Assignments</p>
                </div>
                <?php
                // Calculate total cost per hour
                $stmt = $pdo->prepare("SELECT SUM(hourly_cost) as total_cost FROM enhanced_resources");
                $stmt->execute();
                $total_cost = $stmt->fetch()['total_cost'];
                ?>
                <div class="summary-card">
                    <h3>$<?php echo number_format($total_cost, 2); ?></h3>
                    <p>Total Hourly Cost</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Resource Type Distribution</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM enhanced_resources GROUP BY type");
                    $stmt->execute();
                    $type_distribution = $stmt->fetchAll();
                    ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Resource Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($type_distribution as $type): ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo htmlspecialchars($type['type']); ?>"><?php echo htmlspecialchars($type['type']); ?></span></td>
                                    <td><?php echo $type['count']; ?></td>
                                    <td><?php echo number_format(($type['count'] / count($resources)) * 100, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Resource Modal -->
    <div id="resource-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('resource-modal')">&times;</span>
            <h3 id="modal-title">Add New Resource</h3>
            
            <form id="resource-form">
                <input type="hidden" id="resource-id" name="id">
                
                <div class="form-group">
                    <label>Resource Name:</label>
                    <input type="text" id="resource-name" name="name" required placeholder="e.g., CSE, CAD Designer">
                </div>
                
                <div class="form-group">
                    <label>Type:</label>
                    <select id="resource-type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="human">Human</option>
                        <option value="machine">Machine</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="software">Software</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Hourly Cost ($):</label>
                    <input type="number" id="resource-cost" name="hourly_cost" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Skill Level:</label>
                    <select id="resource-skill" name="skill_level" required>
                        <option value="">Select Skill Level</option>
                        <option value="entry">Entry</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Availability (%):</label>
                    <input type="number" id="resource-availability" name="availability" value="100" min="0" max="100" step="0.1">
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" id="resource-department" name="department" placeholder="e.g., Engineering, Design">
                </div>
                
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" id="resource-location" name="location" placeholder="e.g., Building A, Remote">
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('resource-modal')">Cancel</button>
                    <button type="submit" class="btn btn-orange">Save Resource</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Project Assignment Modal -->
    <div id="project-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('project-modal')">&times;</span>
            <h3>Assign Resource to Project</h3>
            
            <div class="form-group">
                <label>Select Project:</label>
                <select id="modal-project" required>
                    <option value="">Choose a project...</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo ($selected_project == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Resource:</label>
                <select id="modal-resource" required>
                    <option value="">Choose a resource...</option>
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['name']); ?> (<?php echo ucfirst($resource['type']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity Required:</label>
                <input type="number" id="modal-quantity" value="1" step="0.1" min="0.1" required>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" onclick="closeModal('project-modal')">Cancel</button>
                <button class="btn btn-orange" onclick="assignToProject()">Assign Resource</button>
            </div>
        </div>
    </div>

    <!-- Process Assignment Modal -->
    <div id="process-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('process-modal')">&times;</span>
            <h3>Assign Resource to Process</h3>
            
            <div class="form-group">
                <label>Select Process:</label>
                <select id="modal-process" onchange="loadTasksForProcess()" required>
                    <option value="">Choose a process...</option>
                    <?php foreach ($processes_for_selected_project as $process): ?>
                        <option value="<?php echo $process['id']; ?>" <?php echo ($selected_process == $process['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($process['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Task:</label>
                <select id="modal-task" required>
                    <option value="">Choose a task...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Resource:</label>
                <select id="modal-process-resource" required>
                    <option value="">Choose a resource...</option>
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['name']); ?> (<?php echo ucfirst($resource['type']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity Required:</label>
                <input type="number" id="modal-process-quantity" value="1" step="0.1" min="0.1" required>
            </div>
            
            <div class="form-group">
                <label>Duration (minutes):</label>
                <input type="number" id="modal-duration" value="60" min="1" required>
                <small style="color: #666; font-size: 12px;">Auto-populated from task averages when task is selected. You can edit this value.</small>
            </div>
            
            <div class="form-group">
                <label>Complexity Level:</label>
                <select id="modal-complexity" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Priority Level:</label>
                <select id="modal-priority" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" onclick="closeModal('process-modal')">Cancel</button>
                <button class="btn btn-orange" onclick="assignToProcess()">Assign Resource</button>
            </div>
        </div>
    </div>

    <script>
        // Get current tab from URL or default
        var currentTab = '<?php echo $active_tab; ?>';
        
        function showTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
            currentTab = tabName;
        }

        function buildURL(params) {
            var url = window.location.pathname;
            var urlParams = new URLSearchParams();
            
            // Always include current tab
            urlParams.set('tab', currentTab);
            
            // Add other parameters
            if (params) {
                for (var key in params) {
                    if (params[key]) {
                        urlParams.set(key, params[key]);
                    }
                }
            }
            
            return url + '?' + urlParams.toString();
        }

        function showMessage(message, type) {
            var messageDiv = document.getElementById('status-message');
            messageDiv.textContent = message;
            messageDiv.className = 'status-message ' + (type || 'success');
            messageDiv.style.display = 'block';
            
            setTimeout(function() {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Resource';
            document.getElementById('resource-form').reset();
            document.getElementById('resource-id').value = '';
            openModal('resource-modal');
        }

        function openProjectModal() {
            // Set the selected project in the modal
            var projectSelector = document.getElementById('project-selector');
            var modalProject = document.getElementById('modal-project');
            modalProject.value = projectSelector.value;
            openModal('project-modal');
        }

        function openProcessModal() {
            // Set the selected process in the modal and load tasks
            var processSelector = document.getElementById('process-selector');
            var modalProcess = document.getElementById('modal-process');
            modalProcess.value = processSelector.value;
            
            // Load tasks for the selected process
            if (processSelector.value) {
                loadTasksForProcess();
            }
            
            openModal('process-modal');
        }

        function editRes(id) {
            var row = event.target.closest('tr');
            var cells = row.getElementsByTagName('td');
            
            document.getElementById('modal-title').textContent = 'Edit Resource';
            document.getElementById('resource-id').value = id;
            document.getElementById('resource-name').value = cells[0].textContent;
            document.getElementById('resource-type').value = cells[1].querySelector('.badge').textContent.toLowerCase();
            document.getElementById('resource-cost').value = cells[2].textContent.replace('$', '').replace(',', '');
            document.getElementById('resource-skill').value = cells[3].querySelector('.badge').textContent.toLowerCase();
            document.getElementById('resource-availability').value = cells[4].textContent.replace('%', '');
            document.getElementById('resource-department').value = cells[5].textContent;
            
            openModal('resource-modal');
        }

        function deleteRes(id) {
            if (confirm('Are you sure you want to delete this resource?')) {
                var formData = new FormData();
                formData.append('action', 'delete_resource');
                formData.append('id', id);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showMessage(data.message);
                        window.location.href = buildURL();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(function(error) {
                    showMessage('Error: ' + error.message, 'error');
                });
            }
        }

        function loadProjectResources() {
            var projectId = document.getElementById('project-selector').value;
            window.location.href = buildURL({project_id: projectId});
        }

        function loadProcessesByProject() {
            var projectId = document.getElementById('process-project-selector').value;
            var processSelector = document.getElementById('process-selector');
            
            if (projectId) {
                fetch(window.location.pathname + '?action=get_processes&project_id=' + projectId)
                .then(function(response) {
                    return response.json();
                })
                .then(function(processes) {
                    processSelector.innerHTML = '<option value="">Choose a process...</option>';
                    processes.forEach(function(process) {
                        var option = document.createElement('option');
                        option.value = process.id;
                        option.textContent = process.name;
                        processSelector.appendChild(option);
                    });
                    processSelector.disabled = false;
                })
                .catch(function(error) {
                    showMessage('Error loading processes: ' + error.message, 'error');
                });
            } else {
                processSelector.innerHTML = '<option value="">Choose a process...</option>';
                processSelector.disabled = true;
            }
        }

        function loadProcessResources() {
            var processId = document.getElementById('process-selector').value;
            var projectId = document.getElementById('process-project-selector').value;
            window.location.href = buildURL({process_project_id: projectId, process_id: processId});
        }

        function loadTasksForProcess() {
            var processId = document.getElementById('modal-process').value;
            var taskSelector = document.getElementById('modal-task');
            
            if (processId) {
                fetch(window.location.pathname + '?action=get_tasks&process_id=' + processId)
                .then(function(response) {
                    return response.json();
                })
                .then(function(tasks) {
                    taskSelector.innerHTML = '<option value="">Choose a task...</option>';
                    tasks.forEach(function(task) {
                        var option = document.createElement('option');
                        option.value = task.task_id;
                        option.textContent = task.task_name || task.task_id;
                        taskSelector.appendChild(option);
                    });
                    taskSelector.disabled = false;
                    
                    // Remove any existing event listeners to prevent duplicates
                    var newTaskSelector = taskSelector.cloneNode(true);
                    taskSelector.parentNode.replaceChild(newTaskSelector, taskSelector);
                    
                    // Add event listener for task selection to auto-populate duration
                    newTaskSelector.addEventListener('change', function() {
                        if (this.value) {
                            loadTaskDuration(processId, this.value);
                        } else {
                            // Reset to default if no task selected
                            document.getElementById('modal-duration').value = 60;
                        }
                    });
                    
                    // If a task is already selected, load its duration
                    if (newTaskSelector.value) {
                        loadTaskDuration(processId, newTaskSelector.value);
                    }
                })
                .catch(function(error) {
                    showMessage('Error loading tasks: ' + error.message, 'error');
                });
            } else {
                taskSelector.innerHTML = '<option value="">Choose a task...</option>';
                taskSelector.disabled = true;
                // Reset duration to default
                document.getElementById('modal-duration').value = 60;
            }
        }

        function loadTaskDuration(processId, taskId) {
            if (!processId || !taskId) return;
            
            fetch(window.location.pathname + '?action=get_timer_averages&process_id=' + processId)
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (result.success && result.averages) {
                    var taskAverage = result.averages.find(function(avg) {
                        return avg.task_id === taskId;
                    });
                    
                    if (taskAverage) {
                        // Convert seconds to minutes and populate the duration field
                        var minutes = Math.floor(taskAverage.average_duration / 60);
                        document.getElementById('modal-duration').value = minutes;
                    } else {
                        // If no average found, keep the default value
                        document.getElementById('modal-duration').value = 60;
                    }
                }
            })
            .catch(function(error) {
                console.error('Error loading task duration:', error);
                // Keep default value on error
                document.getElementById('modal-duration').value = 60;
            });
        }

        function assignToProject() {
            var projectId = document.getElementById('modal-project').value;
            var resourceId = document.getElementById('modal-resource').value;
            var quantity = document.getElementById('modal-quantity').value;
            
            if (!projectId || !resourceId || !quantity) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'assign_to_project');
            formData.append('project_id', projectId);
            formData.append('resource_id', resourceId);
            formData.append('quantity_required', quantity);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(function(text) {
                try {
                    var data = JSON.parse(text);
                    if (data.success) {
                        showMessage(data.message);
                        closeModal('project-modal');
                        window.location.href = buildURL({project_id: projectId});
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    showMessage('Server response error. Check console for details.', 'error');
                }
            })
            .catch(function(error) {
                console.error('Fetch error:', error);
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function assignToProcess() {
            var processId = document.getElementById('modal-process').value;
            var taskId = document.getElementById('modal-task').value;
            var resourceId = document.getElementById('modal-process-resource').value;
            var quantity = document.getElementById('modal-process-quantity').value;
            var duration = document.getElementById('modal-duration').value;
            var complexity = document.getElementById('modal-complexity').value;
            var priority = document.getElementById('modal-priority').value;
            
            if (!processId || !taskId || !resourceId || !quantity || !duration) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'assign_to_process');
            formData.append('process_id', processId);
            formData.append('task_id', taskId);
            formData.append('resource_id', resourceId);
            formData.append('quantity_required', quantity);
            formData.append('duration_minutes', duration);
            formData.append('complexity_level', complexity);
            formData.append('priority_level', priority);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(function(text) {
                try {
                    var data = JSON.parse(text);
                    if (data.success) {
                        showMessage(data.message);
                        closeModal('process-modal');
                        var projectId = document.getElementById('process-project-selector').value;
                        window.location.href = buildURL({process_project_id: projectId, process_id: processId});
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    showMessage('Server response error. Check console for details.', 'error');
                }
            })
            .catch(function(error) {
                console.error('Fetch error:', error);
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function removeAssignment(assignmentId, assignmentType) {
            if (confirm('Are you sure you want to remove this resource assignment?')) {
                var formData = new FormData();
                formData.append('action', 'remove_assignment');
                formData.append('assignment_id', assignmentId);
                formData.append('assignment_type', assignmentType);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(function(text) {
                    try {
                        var data = JSON.parse(text);
                        if (data.success) {
                            showMessage(data.message);
                            location.reload();
                        } else {
                            showMessage(data.message, 'error');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        showMessage('Server response error. Check console for details.', 'error');
                    }
                })
                .catch(function(error) {
                    console.error('Fetch error:', error);
                    showMessage('Network error: ' + error.message, 'error');
                });
            }
        }

        // Initialize process dropdown on page load if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Resource form submission
            document.getElementById('resource-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var isEdit = formData.get('id');
                formData.append('action', isEdit ? 'update_resource' : 'add_resource');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showMessage(data.message);
                        closeModal('resource-modal');
                        window.location.href = buildURL();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(function(error) {
                    showMessage('Error: ' + error.message, 'error');
                });
            });

            // Load tasks if process is already selected
            var modalProcess = document.getElementById('modal-process');
            if (modalProcess && modalProcess.value) {
                loadTasksForProcess();
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modals = document.getElementsByClassName('modal');
            for (var i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        };
    </script>
</body>
</html>

