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

// Function to get revision badge class based on revision reference
function getRevisionBadgeClass($revisionRef) {
    if (empty($revisionRef)) {
        return 'badge-default';
    }
    
    $revisionRef = strtoupper($revisionRef);
    
    if (strpos($revisionRef, 'REV-001') !== false) {
        return 'badge-rev-001';
    } elseif (strpos($revisionRef, 'REV-002') !== false) {
        return 'badge-rev-002';
    } elseif (strpos($revisionRef, 'REV-003') !== false) {
        return 'badge-rev-003';
    } elseif (strpos($revisionRef, 'SIM') !== false) {
        return 'badge-sim';
    } else {
        return 'badge-default';
    }
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
            
            if ($_GET['action'] === 'get_assignment') {
                $type = isset($_GET['type']) ? $_GET['type'] : '';
                $id = isset($_GET['id']) ? $_GET['id'] : '';
                
                if ($type === 'project' && $id) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT pr.*, p.name as project_name, r.name as resource_name
                            FROM project_resources pr
                            LEFT JOIN projects p ON pr.project_id = p.id
                            LEFT JOIN enhanced_resources r ON pr.resource_id = r.id
                            WHERE pr.id = ?
                        ");
                        $stmt->execute(array($id));
                        $assignment = $stmt->fetch();
                        
                        if ($assignment) {
                            echo json_encode(array('success' => true, 'assignment' => $assignment));
                        } else {
                            echo json_encode(array('success' => false, 'message' => 'Assignment not found'));
                        }
                    } catch (Exception $e) {
                        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
                    }
                } elseif ($type === 'process' && $id) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT pr.*, pm.name as process_name, r.name as resource_name
                            FROM process_resources pr
                            LEFT JOIN process_models pm ON pr.process_id = pm.id
                            LEFT JOIN enhanced_resources r ON pr.resource_id = r.id
                            WHERE pr.id = ?
                        ");
                        $stmt->execute(array($id));
                        $assignment = $stmt->fetch();
                        
                        if ($assignment) {
                            echo json_encode(array('success' => true, 'assignment' => $assignment));
                        } else {
                            echo json_encode(array('success' => false, 'message' => 'Assignment not found'));
                        }
                    } catch (Exception $e) {
                        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
                    }
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Invalid parameters'));
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

// Handle POST requests for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if (file_exists('../../config/config.php')) {
            require_once '../../config/config.php';
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ));
            
            if ($_POST['action'] === 'update_project_assignment') {
                $assignmentId = $_POST['assignment_id'];
                $projectId = $_POST['project_id'];
                $resourceId = $_POST['resource_id'];
                $quantity = $_POST['quantity_required'];
                $notes = $_POST['notes'] ?? '';
                
                $stmt = $pdo->prepare("
                    UPDATE project_resources 
                    SET project_id = ?, resource_id = ?, quantity_required = ?, notes = ?, updated_by = 'system', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute(array($projectId, $resourceId, $quantity, $notes, $assignmentId));
                
                echo json_encode(array('success' => true, 'message' => 'Project assignment updated successfully'));
                exit;
            }
            
            if ($_POST['action'] === 'update_process_assignment') {
                $assignmentId = $_POST['assignment_id'];
                $processId = $_POST['process_id'];
                $taskId = $_POST['task_id'];
                $resourceId = $_POST['resource_id'];
                $quantity = $_POST['quantity_required'];
                $duration = $_POST['duration_minutes'];
                $complexity = $_POST['complexity_level'];
                $priority = $_POST['priority_level'];
                $revisionRef = $_POST['revision_ref'] ?? 'REV-001';
                $revisionNotes = $_POST['revision_notes'] ?? '';
                
                $stmt = $pdo->prepare("
                    UPDATE process_resources 
                    SET process_id = ?, task_id = ?, resource_id = ?, quantity_required = ?, 
                        duration_minutes = ?, complexity_level = ?, priority_level = ?, 
                        revision_ref = ?, revision_notes = ?, updated_by = 'system', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute(array($processId, $taskId, $resourceId, $quantity, $duration, $complexity, $priority, $revisionRef, $revisionNotes, $assignmentId));
                
                echo json_encode(array('success' => true, 'message' => 'Process assignment updated successfully'));
                exit;
            }
            
        } else {
            echo json_encode(array('success' => false, 'message' => 'Database configuration not found'));
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
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
        
        // Add missing columns for revision tracking if they don't exist
        try {
            $pdo->exec("ALTER TABLE process_resources ADD COLUMN revision_ref VARCHAR(50) DEFAULT 'REV-001'");
        } catch (PDOException $e) {
            // Column already exists, ignore error
        }
        
        try {
            $pdo->exec("ALTER TABLE process_resources ADD COLUMN revision_notes TEXT");
        } catch (PDOException $e) {
            // Column already exists, ignore error
        }
        
        
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
                    $stmt = $pdo->prepare("INSERT INTO process_resources (process_id, task_id, resource_id, quantity_required, duration_minutes, complexity_level, priority_level, revision_ref, revision_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute(array(
                        $_POST['process_id'], 
                        $_POST['task_id'], 
                        $_POST['resource_id'], 
                        $_POST['quantity_required'],
                        $_POST['duration_minutes'],
                        $_POST['complexity_level'],
                        $_POST['priority_level'],
                        $_POST['revision_ref'] ?? 'REV-001',
                        $_POST['revision_notes'] ?? ''
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
                    
                } elseif ($action === 'mass_delete_assignments') {
                    $assignment_ids = json_decode($_POST['assignment_ids'], true);
                    
                    if (!is_array($assignment_ids) || empty($assignment_ids)) {
                        echo json_encode(array('success' => false, 'message' => 'No assignment IDs provided'));
                        exit;
                    }
                    
                    // Validate assignment IDs are numeric
                    foreach ($assignment_ids as $id) {
                        if (!is_numeric($id)) {
                            echo json_encode(array('success' => false, 'message' => 'Invalid assignment ID format'));
                            exit;
                        }
                    }
                    
                    // Create placeholders for IN clause
                    $placeholders = str_repeat('?,', count($assignment_ids) - 1) . '?';
                    
                    // Delete from process_resources table
                    $stmt = $pdo->prepare("DELETE FROM process_resources WHERE id IN ($placeholders)");
                    $stmt->execute($assignment_ids);
                    
                    $deleted_count = $stmt->rowCount();
                    
                    echo json_encode(array(
                        'success' => true, 
                        'message' => "Successfully deleted $deleted_count assignment(s)",
                        'deleted_count' => $deleted_count
                    ));
                }
                
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
        }
        
        // Handle get_summary_data AJAX request
        if (isset($_GET['action']) && $_GET['action'] === 'get_summary_data') {
            $project_id = isset($_GET['project_id']) ? $_GET['project_id'] : null;
            
            try {
                $summary_data = array();
                
                if ($project_id) {
                    // Project-specific summary
                    $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute(array($project_id));
                    $project = $stmt->fetch();
                    $summary_data['project_name'] = $project ? $project['name'] : 'Unknown Project';
                    
                    // Project resource assignments
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM project_resources WHERE project_id = ? AND status = 'active'");
                    $stmt->execute(array($project_id));
                    $summary_data['project_assignments'] = $stmt->fetch()['count'];
                    
                    // Process assignments for this project
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM process_resources pr 
                        JOIN process_models pm ON pr.process_id = pm.id 
                        WHERE pm.project_id = ? AND pr.status = 'active'
                    ");
                    $stmt->execute(array($project_id));
                    $summary_data['process_assignments'] = $stmt->fetch()['count'];
                    
                    // Total cost for project resources
                    $stmt = $pdo->prepare("
                        SELECT SUM(er.hourly_cost * pr.quantity_required) as total_cost
                        FROM project_resources pr
                        JOIN enhanced_resources er ON pr.resource_id = er.id
                        WHERE pr.project_id = ? AND pr.status = 'active'
                    ");
                    $stmt->execute(array($project_id));
                    $summary_data['project_cost'] = $stmt->fetch()['total_cost'] ?: 0;
                    
                    // Process cost for this project
                    $stmt = $pdo->prepare("
                        SELECT SUM(er.hourly_cost * pr.quantity_required * (pr.duration_minutes/60)) as total_cost
                        FROM process_resources pr
                        JOIN enhanced_resources er ON pr.resource_id = er.id
                        JOIN process_models pm ON pr.process_id = pm.id
                        WHERE pm.project_id = ? AND pr.status = 'active'
                    ");
                    $stmt->execute(array($project_id));
                    $summary_data['process_cost'] = $stmt->fetch()['total_cost'] ?: 0;
                    
                } else {
                    // Overall system summary (existing data)
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enhanced_resources");
                    $stmt->execute();
                    $summary_data['total_resources'] = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
                    $stmt->execute();
                    $summary_data['active_projects'] = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM process_models");
                    $stmt->execute();
                    $summary_data['available_processes'] = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM project_resources WHERE status = 'active'");
                    $stmt->execute();
                    $summary_data['project_assignments'] = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM process_resources WHERE status = 'active'");
                    $stmt->execute();
                    $summary_data['process_assignments'] = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT SUM(hourly_cost) as total_cost FROM enhanced_resources");
                    $stmt->execute();
                    $summary_data['total_hourly_cost'] = $stmt->fetch()['total_cost'] ?: 0;
                }
                
                echo json_encode(array('success' => true, 'data' => $summary_data));
                
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
        }
        
        // Handle get_process_xml AJAX request
        if (isset($_GET['action']) && $_GET['action'] === 'get_process_xml') {
            $process_id = isset($_GET['process_id']) ? $_GET['process_id'] : null;
            
            try {
                if ($process_id) {
                    $stmt = $pdo->prepare("SELECT model_data, name FROM process_models WHERE id = ?");
                    $stmt->execute(array($process_id));
                    $process = $stmt->fetch();
                    
                    if ($process && !empty($process['model_data'])) {
                        echo json_encode(array(
                            'success' => true, 
                            'xml' => $process['model_data'],
                            'name' => $process['name']
                        ));
                    } else {
                        echo json_encode(array('success' => false, 'message' => 'Process XML not found'));
                    }
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Process ID required'));
                }
                
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
        }
        
        // Handle get_process_assignments AJAX request
        if (isset($_GET['action']) && $_GET['action'] === 'get_process_assignments') {
            $process_id = isset($_GET['process_id']) ? $_GET['process_id'] : null;
            
            try {
                if ($process_id) {
                    $stmt = $pdo->prepare("
                        SELECT pr.*, er.name as resource_name
                        FROM process_resources pr
                        JOIN enhanced_resources er ON pr.resource_id = er.id
                        WHERE pr.process_id = ? AND pr.status = 'active'
                        ORDER BY pr.task_id, pr.id DESC
                    ");
                    $stmt->execute(array($process_id));
                    $assignments = $stmt->fetchAll();
                    
                    echo json_encode(array('success' => true, 'assignments' => $assignments));
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Process ID required'));
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
                SELECT pr.id, pr.process_id, pr.task_id, pr.resource_id, pr.quantity_required, 
                       pr.duration_minutes, pr.complexity_level, pr.priority_level, pr.assigned_date, 
                       pr.status, pr.revision_ref, pr.revision_notes,
                       er.name as resource_name, er.type, er.hourly_cost, er.skill_level, 
                       pm.name as process_name, pm.model_data
                FROM process_resources pr 
                JOIN enhanced_resources er ON pr.resource_id = er.id 
                JOIN process_models pm ON pr.process_id = pm.id
                WHERE pr.process_id = ? AND pr.status = 'active'
                ORDER BY pr.task_id, pr.id DESC
            ");
            $stmt->execute(array($selected_process));
            $process_resources = $stmt->fetchAll();
            
            // Debug: Log the retrieved data
            error_log("DEBUG: Retrieved " . count($process_resources) . " process resources");
            foreach ($process_resources as $index => $pr) {
                error_log("DEBUG: Assignment $index - ID: " . $pr['id'] . ", Task: " . $pr['task_id'] . ", Rev: " . ($pr['revision_ref'] ?? 'NULL'));
            }
            
            // Extract task names from BPMN XML for each resource assignment
            foreach ($process_resources as &$pr) {
                $pr['task_name'] = extractTaskNameFromBPMN($pr['model_data'], $pr['task_id']);
            }
        }
        
        // Get all unique Rev/Ref# values for dynamic filtering
        $revision_refs = array();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT DISTINCT revision_ref FROM process_resources WHERE revision_ref IS NOT NULL AND revision_ref != '' ORDER BY revision_ref");
                $stmt->execute();
                $revision_refs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                error_log("Error fetching revision refs: " . $e->getMessage());
            }
        }
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Resources DB Error: " . $e->getMessage());
}

// Function to extract task name from BPMN XML
function extractTaskNameFromBPMN($xmlData, $taskId) {
    if (empty($xmlData) || empty($taskId)) {
        return $taskId; // Return task ID if no XML data
    }
    
    try {
        // Clean XML data
        $xmlData = trim($xmlData);
        if (strpos($xmlData, '<?xml') !== 0) {
            $xmlData = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlData;
        }
        
        // Parse XML
        $xml = simplexml_load_string($xmlData);
        if ($xml === false) {
            return $taskId;
        }
        
        // Register namespaces for BPMN
        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('bpmn', $namespaces['']);
        }
        if (isset($namespaces['bpmn2'])) {
            $xml->registerXPathNamespace('bpmn', $namespaces['bpmn2']);
        }
        
        // Search for the task with matching ID
        $xpaths = [
            "//bpmn:userTask[@id='$taskId']",
            "//bpmn:serviceTask[@id='$taskId']",
            "//bpmn:scriptTask[@id='$taskId']",
            "//bpmn:manualTask[@id='$taskId']",
            "//bpmn:task[@id='$taskId']",
            "//*[@id='$taskId']" // Fallback for any element with the ID
        ];
        
        foreach ($xpaths as $xpath) {
            $elements = $xml->xpath($xpath);
            if (!empty($elements)) {
                $element = $elements[0];
                
                // Try to get name attribute
                $name = (string)$element['name'];
                if (!empty($name)) {
                    return $name;
                }
                
                // Try to get text content
                $text = trim((string)$element);
                if (!empty($text)) {
                    return $text;
                }
            }
        }
        
        // If no name found, try without namespaces
        $dom = new DOMDocument();
        $dom->loadXML($xmlData);
        $xpath = new DOMXPath($dom);
        
        $elements = $xpath->query("//*[@id='$taskId']");
        if ($elements->length > 0) {
            $element = $elements->item(0);
            $name = $element->getAttribute('name');
            if (!empty($name)) {
                return $name;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error parsing BPMN XML for task $taskId: " . $e->getMessage());
    }
    
    // Return a more user-friendly version of the task ID
    $friendlyName = str_replace(['Activity_', 'Task_', '_'], ['', '', ' '], $taskId);
    $friendlyName = ucwords(trim($friendlyName));
    return !empty($friendlyName) ? $friendlyName : $taskId;
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
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        /* Revision-specific badge colors to match diagram */
        .badge-rev-001 { background: #e3f2fd; color: #1976d2; border: 1px solid #1976d2; }
        .badge-rev-002 { background: #f3e5f5; color: #7b1fa2; border: 1px solid #7b1fa2; }
        .badge-rev-003 { background: #e8f5e8; color: #388e3c; border: 1px solid #388e3c; }
        .badge-sim { background: #fff3e0; color: #f57c00; border: 1px solid #f57c00; }
        .badge-default { background: #f5f5f5; color: #666; border: 1px solid #999; }
        
        /* Enhanced Table Styles */
        .enhanced-table {
            position: relative;
        }
        
        .table-controls {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }
        
        .table-info {
            color: #666;
            font-size: 12px;
            margin-left: auto;
        }
        
        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px !important;
        }
        
        .sortable-header:hover {
            background: #e9ecef !important;
        }
        
        .sort-indicator {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
        }
        
        .sort-asc::after {
            content: '▲';
        }
        
        .sort-desc::after {
            content: '▼';
        }
        
        .table-row-hidden {
            display: none !important;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
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
            margin: 2% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            cursor: move;
        }
        
        .modal-header {
            cursor: move;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            user-select: none;
        }
        
        .modal-header h3 {
            margin: 0;
            display: inline-block;
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
        
        /* BPMN Viewer Styles */
        .process-viewer {
            height: 500px;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #fafafa;
            margin-bottom: 20px;
            position: relative;
            overflow: auto;
        }
        
        .process-viewer .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 16px;
            color: #666;
            background: #f9f9f9;
        }
        
        .bpmn-viewer-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        /* Task highlighting for assignments */
        .djs-element.assigned-task .djs-visual > :first-child {
            stroke: #28a745 !important;
            stroke-width: 3px !important;
        }
        
        .djs-element.assigned-task-rev1 .djs-visual > :first-child {
            stroke: #007bff !important;
            stroke-width: 3px !important;
            fill: rgba(0, 123, 255, 0.1) !important;
        }
        
        .djs-element.assigned-task-rev2 .djs-visual > :first-child {
            stroke: #dc3545 !important;
            stroke-width: 3px !important;
            fill: rgba(220, 53, 69, 0.1) !important;
        }
        
        .djs-element.assigned-task-sim .djs-visual > :first-child {
            stroke: #ffc107 !important;
            stroke-width: 3px !important;
            fill: rgba(255, 193, 7, 0.1) !important;
        }
        
        .djs-element.clickable-task {
            cursor: pointer;
        }
        
        .djs-element.clickable-task:hover .djs-visual > :first-child {
            stroke: #ff6b35 !important;
            stroke-width: 2px !important;
        }
    </style>
    
    <!-- Include BPMN.js for process visualization -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
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
                        <div class="enhanced-table">
                            <div class="table-controls">
                                <input type="text" class="search-box" placeholder="Search resources..." onkeyup="filterTable('global-resources-table', this.value)">
                                <select class="filter-select" onchange="filterTableByColumn('global-resources-table', 2, this.value)">
                                    <option value="">All Types</option>
                                    <option value="human">Human</option>
                                    <option value="machine">Machine</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="software">Software</option>
                                </select>
                                <select class="filter-select" onchange="filterTableByColumn('global-resources-table', 4, this.value)">
                                    <option value="">All Skill Levels</option>
                                    <option value="entry">Entry</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                    <option value="expert">Expert</option>
                                </select>
                                <div class="table-info">
                                    <span id="global-resources-count"><?php echo count($resources); ?></span> resources
                                </div>
                            </div>
                            <table id="global-resources-table">
                                <thead>
                                    <tr>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 0)">Name <span class="sort-indicator"></span></th>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 1)">Description <span class="sort-indicator"></span></th>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 2)">Type <span class="sort-indicator"></span></th>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 3)">Cost/Hour <span class="sort-indicator"></span></th>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 4)">Skill Level <span class="sort-indicator"></span></th>
                                        <th class="sortable-header" onclick="sortTable('global-resources-table', 5)">Availability <span class="sort-indicator"></span></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resources as $resource): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($resource['name']); ?></td>
                                            <td><?php echo htmlspecialchars($resource['description'] ?? ''); ?></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($resource['type']); ?>"><?php echo htmlspecialchars($resource['type']); ?></span></td>
                                            <td>$<?php echo number_format($resource['hourly_cost'], 2); ?></td>
                                            <td><span class="badge badge-<?php echo htmlspecialchars($resource['skill_level']); ?>"><?php echo htmlspecialchars($resource['skill_level']); ?></span></td>
                                            <td><?php echo number_format($resource['availability'], 1); ?>%</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editRes(<?php echo $resource['id']; ?>)">Edit</button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteRes(<?php echo $resource['id']; ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div id="global-resources-no-results" class="no-results" style="display: none;">
                                No resources match your search criteria.
                            </div>
                        </div>
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
                            <div class="enhanced-table">
                                <div class="table-controls">
                                    <input type="text" class="search-box" placeholder="Search project resources..." onkeyup="filterTable('project-resources-table', this.value)">
                                    <select class="filter-select" onchange="filterTableByColumn('project-resources-table', 1, this.value)">
                                        <option value="">All Types</option>
                                        <option value="human">Human</option>
                                        <option value="machine">Machine</option>
                                        <option value="hybrid">Hybrid</option>
                                        <option value="software">Software</option>
                                    </select>
                                    <div class="table-info">
                                        <span id="project-resources-count"><?php echo count($project_resources); ?></span> assignments
                                    </div>
                                </div>
                                <table id="project-resources-table">
                                    <thead>
                                        <tr>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 0)">Resource Name <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 1)">Type <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 2)">Skill Level <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 3)">Hourly Cost <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 4)">Quantity Required <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('project-resources-table', 5)">Assigned Date <span class="sort-indicator"></span></th>
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
                                                <button class="btn btn-sm btn-primary" onclick="editProjectAssignment(<?php echo $pr['id']; ?>)">Edit</button>
                                                <button class="btn btn-sm btn-danger" onclick="removeAssignment(<?php echo $pr['id']; ?>, 'project')">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div id="project-resources-no-results" class="no-results" style="display: none;">
                                No project resources match your search criteria.
                            </div>
                        </div>
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
            
            <!-- Rev/Ref# Legend -->
            <?php if ($selected_process): ?>
                <div class="card">
                    <div class="card-header">
                        <strong>Assignment Legend</strong>
                        <small style="float: right; color: #666;">Click legend items to highlight specific assignments</small>
                    </div>
                    <div class="card-body" style="padding: 15px;">
                        <div id="rev-ref-legend" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
                            <div class="legend-item" onclick="highlightRevRef('')" style="cursor: pointer; padding: 8px 12px; border: 2px solid #28a745; background: rgba(40, 167, 69, 0.1); border-radius: 5px; font-size: 12px; font-weight: bold;">
                                <span style="color: #28a745;">■</span> All Assignments
                            </div>
                            <div id="legend-loading" style="color: #666; font-style: italic;">Loading assignment data...</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- BPMN Process Viewer -->
            <?php if ($selected_process): ?>
                <div class="card">
                    <div class="card-header">
                        <strong>Process Visualization - <?php echo htmlspecialchars($processes_for_selected_project[array_search($selected_process, array_column($processes_for_selected_project, 'id'))]['name'] ?? 'Selected Process'); ?></strong>
                        <small style="float: right; color: #666;">Click on tasks to assign resources</small>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div id="process-bpmn-viewer" class="process-viewer">
                            <div class="loading">Loading process visualization...</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
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
                            <div class="enhanced-table">
                                <div class="table-controls">
                                    <input type="text" class="search-box" placeholder="Search process resources..." onkeyup="filterTable('process-resources-table', this.value)">
                                    <select class="filter-select" onchange="filterTableByColumn('process-resources-table', 2, this.value)">
                                        <option value="">All Types</option>
                                        <option value="human">Human</option>
                                        <option value="machine">Machine</option>
                                        <option value="hybrid">Hybrid</option>
                                        <option value="software">Software</option>
                                    </select>
                                    <select class="filter-select" onchange="filterTableByColumn('process-resources-table', 7, this.value)">
                                        <option value="">All Revisions</option>
                                        <?php foreach ($revision_refs as $ref): ?>
                                            <option value="<?php echo htmlspecialchars($ref); ?>"><?php echo htmlspecialchars($ref); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-secondary" onclick="refreshProcessResources()" title="Refresh data from database">
                                        🔄 Refresh
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="retrieveAssignments()" title="Retrieve latest assignments from database">
                                        📥 Retrieve Assignments
                                    </button>
                                    <button id="mass-delete-btn" class="btn btn-sm btn-danger" onclick="massDeleteAssignments()" style="display: none;" title="Delete selected assignments">
                                        🗑️ Delete Selected (<span id="selected-count">0</span>)
                                    </button>
                                    <div class="table-info">
                                        <span id="process-resources-count"><?php echo count($process_resources); ?></span> assignments
                                    </div>
                                </div>
                                <table id="process-resources-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-assignments" onchange="toggleSelectAll()" title="Select all assignments">
                                            </th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 1)">Task <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 2)">Resource Name <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 3)">Type <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 4)">Quantity <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 5)">Duration <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 6)">Complexity <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 7)">Priority <span class="sort-indicator"></span></th>
                                            <th class="sortable-header" onclick="sortTable('process-resources-table', 8)">Rev/Ref# <span class="sort-indicator"></span></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                <tbody id="process-resources-tbody">
                                    <!-- Table data will be loaded dynamically via AJAX to match diagram data -->
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 20px; color: #666;">
                                            Loading assignments...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div id="process-resources-no-results" class="no-results" style="display: none;">
                                No process resources match your search criteria.
                            </div>
                        </div>
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
            <div class="selector-group">
                <label for="summary-project-selector">Summary Scope:</label>
                <select id="summary-project-selector" onchange="updateSummaryData()">
                    <option value="">Overall System Summary</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="summary-content">
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

    <!-- Edit Project Assignment Modal -->
    <div id="edit-project-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('edit-project-modal')">&times;</span>
            <h3>Edit Project Assignment</h3>
            
            <input type="hidden" id="edit-project-assignment-id">
            
            <div class="form-group">
                <label>Project:</label>
                <select id="edit-modal-project" required>
                    <option value="">Choose a project...</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Resource:</label>
                <select id="edit-modal-resource" required>
                    <option value="">Choose a resource...</option>
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['name']); ?> (<?php echo ucfirst($resource['type']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity Required:</label>
                <input type="number" id="edit-modal-quantity" value="1" step="0.1" min="0.1" required>
            </div>
            
            <div class="form-group">
                <label>Notes (optional):</label>
                <textarea id="edit-modal-notes" placeholder="Additional notes about this assignment..." rows="2"></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" onclick="closeModal('edit-project-modal')">Cancel</button>
                <button class="btn btn-orange" onclick="updateProjectAssignment()">Update Assignment</button>
            </div>
        </div>
    </div>

    <!-- Process Assignment Modal -->
    <div id="process-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Resource to Process</h3>
                <span class="close" onclick="closeModal('process-modal')">&times;</span>
            </div>
            
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
            
            <div class="form-group" style="display: none;">
                <label>Task Name:</label>
                <input type="text" id="modal-task-name" readonly style="background-color: #f8f9fa; color: #666;">
                <small style="color: #666; font-size: 12px;">Auto-filled when task is selected</small>
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
            
            <div class="form-group">
                <label>Revision/Reference #:</label>
                <input type="text" id="modal-revision-ref" value="REV-001" placeholder="e.g., REV-001, SIM-2024-01" required>
                <small style="color: #666; font-size: 12px;">Use this to group assignments for simulation comparisons</small>
            </div>
            
            <div class="form-group">
                <label>Notes (optional):</label>
                <textarea id="modal-revision-notes" placeholder="Additional notes about this assignment or revision..." rows="2"></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" onclick="closeModal('process-modal')">Cancel</button>
                <button class="btn btn-orange" onclick="assignToProcess()">Assign Resource</button>
            </div>
        </div>
    </div>

    <!-- Edit Process Assignment Modal -->
    <div id="edit-process-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('edit-process-modal')">&times;</span>
            <h3>Edit Process Assignment</h3>
            
            <input type="hidden" id="edit-process-assignment-id">
            
            <div class="form-group">
                <label>Process:</label>
                <select id="edit-modal-process" onchange="loadTasksForEditProcess()" required>
                    <option value="">Choose a process...</option>
                    <?php foreach ($processes_for_selected_project as $process): ?>
                        <option value="<?php echo $process['id']; ?>">
                            <?php echo htmlspecialchars($process['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Task:</label>
                <select id="edit-modal-task" required>
                    <option value="">Choose a task...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Resource:</label>
                <select id="edit-modal-process-resource" required>
                    <option value="">Choose a resource...</option>
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['name']); ?> (<?php echo ucfirst($resource['type']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity Required:</label>
                <input type="number" id="edit-modal-process-quantity" value="1" step="0.1" min="0.1" required>
            </div>
            
            <div class="form-group">
                <label>Duration (minutes):</label>
                <input type="number" id="edit-modal-duration" value="60" min="1" required>
                <small style="color: #666; font-size: 12px;">Auto-populated from task averages when task is selected. You can edit this value.</small>
            </div>
            
            <div class="form-group">
                <label>Complexity Level:</label>
                <select id="edit-modal-complexity" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Priority Level:</label>
                <select id="edit-modal-priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Revision/Reference #:</label>
                <input type="text" id="edit-modal-revision-ref" value="REV-001" placeholder="e.g., REV-001, SIM-2024-01" required>
                <small style="color: #666; font-size: 12px;">Use this to group assignments for simulation comparisons</small>
            </div>
            
            <div class="form-group">
                <label>Notes (optional):</label>
                <textarea id="edit-modal-revision-notes" placeholder="Additional notes about this assignment or revision..." rows="2"></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" onclick="closeModal('edit-process-modal')">Cancel</button>
                <button class="btn btn-orange" onclick="updateProcessAssignment()">Update Assignment</button>
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
            // Clear all modal fields first
            clearProcessModalFields();
            
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
        
        function clearProcessModalFields() {
            // Clear all form fields with null checks
            const modalTask = document.getElementById('modal-task');
            const modalTaskName = document.getElementById('modal-task-name');
            const modalProcessResource = document.getElementById('modal-process-resource');
            const modalProcessQuantity = document.getElementById('modal-process-quantity');
            const modalDuration = document.getElementById('modal-duration');
            const modalComplexity = document.getElementById('modal-complexity');
            const modalPriority = document.getElementById('modal-priority');
            const modalRevRef = document.getElementById('modal-rev-ref');
            
            if (modalTask) modalTask.innerHTML = '<option value="">Choose a task...</option>';
            if (modalTaskName) modalTaskName.value = '';
            if (modalProcessResource) modalProcessResource.value = '';
            if (modalProcessQuantity) modalProcessQuantity.value = '1';
            if (modalDuration) modalDuration.value = '60';
            if (modalComplexity) modalComplexity.value = '';
            if (modalPriority) modalPriority.value = '';
            if (modalRevRef) modalRevRef.value = 'REV-001';
        }

        function editRes(id) {
            var row = event.target.closest('tr');
            if (!row) {
                console.error('Could not find table row');
                return;
            }
            
            var cells = row.getElementsByTagName('td');
            if (cells.length === 0) {
                console.error('Could not find table cells');
                return;
            }
            
            // Get elements with null checks
            const modalTitle = document.getElementById('modal-title');
            const resourceId = document.getElementById('resource-id');
            const resourceName = document.getElementById('resource-name');
            const resourceType = document.getElementById('resource-type');
            const resourceCost = document.getElementById('resource-cost');
            const resourceSkill = document.getElementById('resource-skill');
            const resourceAvailability = document.getElementById('resource-availability');
            const resourceDepartment = document.getElementById('resource-department');
            
            // Set values with null checks
            if (modalTitle) modalTitle.textContent = 'Edit Resource';
            if (resourceId) resourceId.value = id;
            if (resourceName && cells[0]) resourceName.value = cells[0].textContent;
            if (resourceType && cells[1]) {
                const badge = cells[1].querySelector('.badge');
                if (badge) resourceType.value = badge.textContent.toLowerCase();
            }
            if (resourceCost && cells[2]) resourceCost.value = cells[2].textContent.replace('$', '').replace(',', '');
            if (resourceSkill && cells[3]) {
                const badge = cells[3].querySelector('.badge');
                if (badge) resourceSkill.value = badge.textContent.toLowerCase();
            }
            if (resourceAvailability && cells[4]) resourceAvailability.value = cells[4].textContent.replace('%', '');
            if (resourceDepartment && cells[5]) resourceDepartment.value = cells[5].textContent;
            
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

        function loadTasksForProcess(processId, callback) {
            // If no processId provided, get it from modal
            if (!processId) {
                processId = document.getElementById('modal-process').value;
            }
            
            var taskSelector = document.getElementById('modal-task');
            var taskNameField = document.getElementById('modal-task-name');
            
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
                        // Show task name if available, otherwise show task ID
                        option.textContent = task.task_name || task.task_id;
                        taskSelector.appendChild(option);
                    });
                    taskSelector.disabled = false;
                    
                    // Remove any existing event listeners to prevent duplicates
                    var newTaskSelector = taskSelector.cloneNode(true);
                    taskSelector.parentNode.replaceChild(newTaskSelector, taskSelector);
                    
                    // Add event listener for task selection to auto-populate duration and task name
                    newTaskSelector.addEventListener('change', function() {
                        if (this.value) {
                            // Update task name field
                            var selectedOption = this.options[this.selectedIndex];
                            if (taskNameField && selectedOption) {
                                taskNameField.value = selectedOption.textContent;
                            }
                            
                            loadTaskDuration(processId, this.value);
                        } else {
                            // Reset to default if no task selected
                            document.getElementById('modal-duration').value = 60;
                            if (taskNameField) {
                                taskNameField.value = '';
                            }
                        }
                    });
                    
                    // If a task is already selected, load its duration
                    if (newTaskSelector.value) {
                        loadTaskDuration(processId, newTaskSelector.value);
                    }
                    
                    // Execute callback if provided
                    if (callback && typeof callback === 'function') {
                        callback();
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
                if (taskNameField) {
                    taskNameField.value = '';
                }
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
            console.log('assignToProcess() called');
            
            // Get elements with null checks
            const processElement = document.getElementById('modal-process');
            const taskElement = document.getElementById('modal-task');
            const resourceElement = document.getElementById('modal-process-resource');
            const quantityElement = document.getElementById('modal-process-quantity');
            const durationElement = document.getElementById('modal-duration');
            const complexityElement = document.getElementById('modal-complexity');
            const priorityElement = document.getElementById('modal-priority');
            const revisionRefElement = document.getElementById('modal-revision-ref');
            const revisionNotesElement = document.getElementById('modal-revision-notes');
            
            // Check if elements exist before reading values
            if (!processElement || !taskElement || !resourceElement || !quantityElement || !durationElement) {
                console.error('Required form elements not found');
                console.error('Elements found:', {
                    processElement: !!processElement,
                    taskElement: !!taskElement,
                    resourceElement: !!resourceElement,
                    quantityElement: !!quantityElement,
                    durationElement: !!durationElement
                });
                showMessage('Form elements not found. Please try again.', 'error');
                return;
            }
            
            // Debug: Check if revision elements are found
            console.log('Revision elements check:', {
                revisionRefElement: !!revisionRefElement,
                revisionNotesElement: !!revisionNotesElement,
                revisionRefValue: revisionRefElement ? revisionRefElement.value : 'ELEMENT_NOT_FOUND',
                revisionNotesValue: revisionNotesElement ? revisionNotesElement.value : 'ELEMENT_NOT_FOUND'
            });
            
            var processId = processElement.value;
            var taskId = taskElement.value;
            var resourceId = resourceElement.value;
            var quantity = quantityElement.value;
            var duration = durationElement.value;
            var complexity = complexityElement ? complexityElement.value : '';
            var priority = priorityElement ? priorityElement.value : '';
            var revisionRef = revisionRefElement ? revisionRefElement.value : 'REV-001';
            var revisionNotes = revisionNotesElement ? revisionNotesElement.value : '';
            
            console.log('Assignment data:', {
                processId, taskId, resourceId, quantity, duration, complexity, priority, revisionRef, revisionNotes
            });
            
            if (!processId || !taskId || !resourceId || !quantity || !duration) {
                console.error('Required fields missing:', {
                    processId: !!processId,
                    taskId: !!taskId,
                    resourceId: !!resourceId,
                    quantity: !!quantity,
                    duration: !!duration
                });
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
            formData.append('revision_ref', revisionRef);
            formData.append('revision_notes', revisionNotes);
            
            console.log('Sending AJAX request with FormData:', {
                action: 'assign_to_process',
                process_id: processId,
                task_id: taskId,
                resource_id: resourceId,
                quantity_required: quantity,
                duration_minutes: duration,
                complexity_level: complexity,
                priority_level: priority,
                revision_ref: revisionRef,
                revision_notes: revisionNotes
            });
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(function(text) {
                console.log('Raw response text:', text);
                try {
                    var data = JSON.parse(text);
                    console.log('Parsed response data:', data);
                    if (data.success) {
                        showMessage(data.message, 'success');
                        closeModal('process-modal');
                        var projectId = document.getElementById('process-project-selector').value;
                        console.log('Redirecting to:', buildURL({process_project_id: projectId, process_id: processId}));
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

        // Edit functionality for project assignments
        function editProjectAssignment(assignmentId) {
            // Fetch assignment data and populate edit modal
            fetch(window.location.pathname + '?action=get_assignment&type=project&id=' + assignmentId)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    var assignment = data.assignment;
                    document.getElementById('edit-project-assignment-id').value = assignment.id;
                    document.getElementById('edit-modal-project').value = assignment.project_id;
                    document.getElementById('edit-modal-resource').value = assignment.resource_id;
                    document.getElementById('edit-modal-quantity').value = assignment.quantity_required;
                    document.getElementById('edit-modal-notes').value = assignment.notes || '';
                    openModal('edit-project-modal');
                } else {
                    showMessage('Error loading assignment data: ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                showMessage('Error loading assignment: ' + error.message, 'error');
            });
        }

        function updateProjectAssignment() {
            var assignmentId = document.getElementById('edit-project-assignment-id').value;
            var projectId = document.getElementById('edit-modal-project').value;
            var resourceId = document.getElementById('edit-modal-resource').value;
            var quantity = document.getElementById('edit-modal-quantity').value;
            var notes = document.getElementById('edit-modal-notes').value;
            
            if (!projectId || !resourceId || !quantity) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'update_project_assignment');
            formData.append('assignment_id', assignmentId);
            formData.append('project_id', projectId);
            formData.append('resource_id', resourceId);
            formData.append('quantity_required', quantity);
            formData.append('notes', notes);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.text();
            })
            .then(function(text) {
                try {
                    var data = JSON.parse(text);
                    if (data.success) {
                        showMessage(data.message);
                        closeModal('edit-project-modal');
                        window.location.href = buildURL({project_id: projectId});
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showMessage('Server response error. Check console for details.', 'error');
                }
            })
            .catch(function(error) {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        // Edit functionality for process assignments
        function editProcessAssignment(assignmentId) {
            // Fetch assignment data and populate edit modal
            fetch(window.location.pathname + '?action=get_assignment&type=process&id=' + assignmentId)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    var assignment = data.assignment;
                    document.getElementById('edit-process-assignment-id').value = assignment.id;
                    document.getElementById('edit-modal-process').value = assignment.process_id;
                    document.getElementById('edit-modal-process-resource').value = assignment.resource_id;
                    document.getElementById('edit-modal-process-quantity').value = assignment.quantity_required;
                    document.getElementById('edit-modal-duration').value = assignment.duration_minutes;
                    document.getElementById('edit-modal-complexity').value = assignment.complexity_level;
                    document.getElementById('edit-modal-priority').value = assignment.priority_level;
                    document.getElementById('edit-modal-revision-ref').value = assignment.revision_ref || 'REV-001';
                    document.getElementById('edit-modal-revision-notes').value = assignment.revision_notes || '';
                    
                    // Load tasks for the process and then select the task
                    loadTasksForEditProcess().then(function() {
                        document.getElementById('edit-modal-task').value = assignment.task_id;
                    });
                    
                    openModal('edit-process-modal');
                } else {
                    showMessage('Error loading assignment data: ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                showMessage('Error loading assignment: ' + error.message, 'error');
            });
        }

        function loadTasksForEditProcess() {
            return new Promise(function(resolve, reject) {
                var processId = document.getElementById('edit-modal-process').value;
                var taskSelector = document.getElementById('edit-modal-task');
                
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
                        
                        // Add event listener for task selection to auto-populate duration
                        taskSelector.addEventListener('change', function() {
                            if (this.value) {
                                loadTaskDurationForEdit(processId, this.value);
                            }
                        });
                        
                        resolve();
                    })
                    .catch(function(error) {
                        reject(error);
                    });
                } else {
                    taskSelector.innerHTML = '<option value="">Choose a task...</option>';
                    taskSelector.disabled = true;
                    resolve();
                }
            });
        }

        function loadTaskDurationForEdit(processId, taskId) {
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
                        var minutes = Math.floor(taskAverage.average_duration / 60);
                        document.getElementById('edit-modal-duration').value = minutes;
                    }
                }
            })
            .catch(function(error) {
                console.error('Error loading task duration:', error);
            });
        }

        function updateProcessAssignment() {
            var assignmentId = document.getElementById('edit-process-assignment-id').value;
            var processId = document.getElementById('edit-modal-process').value;
            var taskId = document.getElementById('edit-modal-task').value;
            var resourceId = document.getElementById('edit-modal-process-resource').value;
            var quantity = document.getElementById('edit-modal-process-quantity').value;
            var duration = document.getElementById('edit-modal-duration').value;
            var complexity = document.getElementById('edit-modal-complexity').value;
            var priority = document.getElementById('edit-modal-priority').value;
            var revisionRef = document.getElementById('edit-modal-revision-ref').value;
            var revisionNotes = document.getElementById('edit-modal-revision-notes').value;
            
            if (!processId || !taskId || !resourceId || !quantity || !duration) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'update_process_assignment');
            formData.append('assignment_id', assignmentId);
            formData.append('process_id', processId);
            formData.append('task_id', taskId);
            formData.append('resource_id', resourceId);
            formData.append('quantity_required', quantity);
            formData.append('duration_minutes', duration);
            formData.append('complexity_level', complexity);
            formData.append('priority_level', priority);
            formData.append('revision_ref', revisionRef);
            formData.append('revision_notes', revisionNotes);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.text();
            })
            .then(function(text) {
                try {
                    var data = JSON.parse(text);
                    if (data.success) {
                        showMessage(data.message);
                        closeModal('edit-process-modal');
                        var projectId = document.getElementById('process-project-selector').value;
                        window.location.href = buildURL({process_project_id: projectId, process_id: processId});
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showMessage('Server response error. Check console for details.', 'error');
                }
            })
            .catch(function(error) {
                showMessage('Network error: ' + error.message, 'error');
            });
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
                        showMessage(isEdit ? 'Resource updated successfully!' : 'Resource added successfully!', 'success');
                        closeModal('resource-modal');
                        
                        // Refresh the page to show updated resources
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage('Error: ' + data.message, 'error');
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

        // Table Enhancement Functions
        function filterTable(tableId, searchValue) {
            var table = document.getElementById(tableId);
            var tbody = table.getElementsByTagName('tbody')[0];
            var rows = tbody.getElementsByTagName('tr');
            var visibleCount = 0;
            
            searchValue = searchValue.toLowerCase();
            
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var cells = row.getElementsByTagName('td');
                var found = false;
                
                for (var j = 0; j < cells.length; j++) {
                    var cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(searchValue) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found || searchValue === '') {
                    row.classList.remove('table-row-hidden');
                    visibleCount++;
                } else {
                    row.classList.add('table-row-hidden');
                }
            }
            
            // Update count
            var countElement = document.getElementById(tableId.replace('-table', '-count'));
            if (countElement) {
                countElement.textContent = visibleCount;
            }
            
            // Show/hide no results message
            var noResultsElement = document.getElementById(tableId.replace('-table', '-no-results'));
            if (noResultsElement) {
                noResultsElement.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }
        
        function filterTableByColumn(tableId, columnIndex, filterValue) {
            var table = document.getElementById(tableId);
            var tbody = table.getElementsByTagName('tbody')[0];
            var rows = tbody.getElementsByTagName('tr');
            var visibleCount = 0;
            
            filterValue = filterValue.toLowerCase();
            
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var cells = row.getElementsByTagName('td');
                
                if (cells[columnIndex]) {
                    var cellText = cells[columnIndex].textContent || cells[columnIndex].innerText;
                    cellText = cellText.toLowerCase();
                    
                    if (filterValue === '' || cellText.indexOf(filterValue) > -1) {
                        row.classList.remove('table-row-hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('table-row-hidden');
                    }
                }
            }
            
            // Update count
            var countElement = document.getElementById(tableId.replace('-table', '-count'));
            if (countElement) {
                countElement.textContent = visibleCount;
            }
            
            // Show/hide no results message
            var noResultsElement = document.getElementById(tableId.replace('-table', '-no-results'));
            if (noResultsElement) {
                noResultsElement.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }
        
        function sortTable(tableId, columnIndex) {
            var table = document.getElementById(tableId);
            var tbody = table.getElementsByTagName('tbody')[0];
            var rows = Array.from(tbody.getElementsByTagName('tr'));
            var header = table.getElementsByTagName('thead')[0].getElementsByTagName('tr')[0].getElementsByTagName('th')[columnIndex];
            
            // Determine sort direction
            var isAsc = !header.classList.contains('sort-asc');
            
            // Clear all sort indicators
            var headers = table.getElementsByTagName('thead')[0].getElementsByTagName('th');
            for (var i = 0; i < headers.length; i++) {
                headers[i].classList.remove('sort-asc', 'sort-desc');
            }
            
            // Set current sort indicator
            header.classList.add(isAsc ? 'sort-asc' : 'sort-desc');
            
            // Sort rows
            rows.sort(function(a, b) {
                var aText = a.getElementsByTagName('td')[columnIndex].textContent || a.getElementsByTagName('td')[columnIndex].innerText;
                var bText = b.getElementsByTagName('td')[columnIndex].textContent || b.getElementsByTagName('td')[columnIndex].innerText;
                
                // Try to parse as numbers
                var aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
                var bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? aNum - bNum : bNum - aNum;
                } else {
                    return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
                }
            });
            
            // Reorder rows in table
            for (var i = 0; i < rows.length; i++) {
                tbody.appendChild(rows[i]);
            }
        }
        
        // Update summary data based on selected project
        function updateSummaryData() {
            var projectId = document.getElementById('summary-project-selector').value;
            var summaryContent = document.getElementById('summary-content');
            
            // Show loading state
            summaryContent.innerHTML = '<div style="text-align: center; padding: 40px;">Loading summary data...</div>';
            
            // Fetch summary data
            var url = window.location.href.split('?')[0] + '?action=get_summary_data';
            if (projectId) {
                url += '&project_id=' + projectId;
            }
            
            fetch(url)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    var html = '';
                    
                    if (projectId) {
                        // Project-specific summary
                        html = '<div class="summary-grid">' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.project_assignments + '</h3>' +
                                '<p>Project Assignments</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.process_assignments + '</h3>' +
                                '<p>Process Assignments</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>$' + parseFloat(data.data.project_cost || 0).toFixed(2) + '</h3>' +
                                '<p>Project Resource Cost/Hour</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>$' + parseFloat(data.data.process_cost || 0).toFixed(2) + '</h3>' +
                                '<p>Process Total Cost</p>' +
                            '</div>' +
                        '</div>' +
                        '<div class="card">' +
                            '<div class="card-header">Project Summary: ' + data.data.project_name + '</div>' +
                            '<div class="card-body">' +
                                '<p>This summary shows resource allocation and costs specific to the selected project.</p>' +
                                '<ul>' +
                                    '<li><strong>Project Assignments:</strong> Direct resource assignments to the project</li>' +
                                    '<li><strong>Process Assignments:</strong> Resource assignments to processes within this project</li>' +
                                    '<li><strong>Project Resource Cost:</strong> Hourly cost of directly assigned resources</li>' +
                                    '<li><strong>Process Total Cost:</strong> Total cost including duration for process assignments</li>' +
                                '</ul>' +
                            '</div>' +
                        '</div>';
                    } else {
                        // Overall system summary
                        html = '<div class="summary-grid">' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.total_resources + '</h3>' +
                                '<p>Total Resources</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.active_projects + '</h3>' +
                                '<p>Active Projects</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.available_processes + '</h3>' +
                                '<p>Available Processes</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.project_assignments + '</h3>' +
                                '<p>Project Assignments</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>' + data.data.process_assignments + '</h3>' +
                                '<p>Process Assignments</p>' +
                            '</div>' +
                            '<div class="summary-card">' +
                                '<h3>$' + parseFloat(data.data.total_hourly_cost || 0).toFixed(2) + '</h3>' +
                                '<p>Total Hourly Cost</p>' +
                            '</div>' +
                        '</div>' +
                        '<div class="card">' +
                            '<div class="card-header">Resource Type Distribution</div>' +
                            '<div class="card-body">' +
                                '<p>Overall system resource distribution and assignment statistics.</p>' +
                            '</div>' +
                        '</div>';
                    }
                    
                    summaryContent.innerHTML = html;
                } else {
                    summaryContent.innerHTML = '<div class="status-message error">Error loading summary data: ' + data.message + '</div>';
                }
            })
            .catch(function(error) {
                summaryContent.innerHTML = '<div class="status-message error">Network error loading summary data.</div>';
            });
        }
    </script>
    
    <!-- BPMN.js script -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js"></script>
    
    <script>
        // BPMN Viewer variables
        let processBpmnViewer = null;
        let currentProcessData = null;
        let processAssignments = [];
        
        // Initialize BPMN Viewer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeBPMNViewer();
            
            // Load process if one is already selected
            const processSelector = document.getElementById('process-selector');
            if (processSelector && processSelector.value) {
                loadProcessInBPMNViewer(processSelector.value);
            }
        });
        
        // Initialize BPMN Viewer
        function initializeBPMNViewer() {
            try {
                processBpmnViewer = new BpmnJS({
                    container: '#process-bpmn-viewer'
                });
                console.log('✅ BPMN Viewer initialized successfully');
            } catch (error) {
                console.error('Failed to initialize BPMN Viewer:', error);
                const loading = document.querySelector('#process-bpmn-viewer .loading');
                if (loading) {
                    loading.innerHTML = 'BPMN Viewer initialization failed: ' + error.message;
                }
            }
        }
        
        // Load process in BPMN viewer
        async function loadProcessInBPMNViewer(processId) {
            if (!processBpmnViewer || !processId) return;
            
            try {
                // Fetch process data
                const response = await fetch(window.location.href.split('?')[0] + '?action=get_process_xml&process_id=' + processId);
                const data = await response.json();
                
                if (data.success && data.xml) {
                    await processBpmnViewer.importXML(data.xml);
                    processBpmnViewer.get('canvas').zoom('fit-viewport');
                    
                    currentProcessData = data;
                    
                    // Hide loading indicator
                    const loading = document.querySelector('#process-bpmn-viewer .loading');
                    if (loading) {
                        loading.style.display = 'none';
                    }
                    
                    // Load assignments and add click handlers
                    await loadProcessAssignments(processId);
                    addTaskClickHandlers();
                    highlightAssignedTasks();
                    
                } else {
                    throw new Error(data.message || 'Failed to load process XML');
                }
                
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
                const loading = document.querySelector('#process-bpmn-viewer .loading');
                if (loading) {
                    loading.innerHTML = 'Failed to load process: ' + error.message;
                }
            }
        }
        
        // Load process assignments for highlighting
        async function loadProcessAssignments(processId) {
            try {
                const response = await fetch(window.location.href.split('?')[0] + '?action=get_process_assignments&process_id=' + processId);
                const data = await response.json();
                
                if (data.success) {
                    processAssignments = data.assignments || [];
                    // Also populate the table with the same data
                    populateProcessResourcesTable(processAssignments);
                } else {
                    processAssignments = [];
                    populateProcessResourcesTable([]);
                }
            } catch (error) {
                console.error('Failed to load process assignments:', error);
                processAssignments = [];
                populateProcessResourcesTable([]);
            }
        }
        
        // Populate process resources table using same data as diagram
        function populateProcessResourcesTable(assignments) {
            const tbody = document.getElementById('process-resources-tbody');
            if (!tbody) return;
            
            if (!assignments || assignments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #666;">No assignments found.</td></tr>';
                const countElement = document.getElementById('process-resources-count');
                if (countElement) countElement.textContent = '0';
                return;
            }
            
            console.log('Populating table with assignments:', assignments.length);
            
            let html = '';
            assignments.forEach(function(assignment, index) {
                console.log(`Processing assignment ${index + 1}:`, {
                    id: assignment.id,
                    task_id: assignment.task_id,
                    resource_name: assignment.resource_name,
                    revision_ref: assignment.revision_ref,
                    duration_minutes: assignment.duration_minutes
                });
                
                const revRef = assignment.revision_ref || 'REV-001';
                const badgeClass = getRevisionBadgeClass(revRef);
                
                // Get task name from BPMN data or use task_id as fallback
                const taskName = getTaskNameFromBpmn(assignment.task_id) || assignment.task_id;
                
                html += `
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="assignment-checkbox" value="${assignment.id}" onchange="updateMassDeleteButton()">
                        </td>
                        <td>
                            <div style="font-weight: bold; color: #333; font-size: 14px;">${escapeHtml(taskName)}</div>
                            <div style="font-size: 11px; color: #666;">Task ID: ${escapeHtml(assignment.task_id)}</div>
                            <div style="font-size: 10px; color: #999;">Assignment #${assignment.id}</div>
                        </td>
                        <td>${escapeHtml(assignment.resource_name)}</td>
                        <td><span class="badge badge-${escapeHtml(assignment.type || 'human')}">${escapeHtml(assignment.type || 'human')}</span></td>
                        <td>${parseFloat(assignment.quantity_required || 1).toFixed(1)}</td>
                        <td>${assignment.duration_minutes || 0} min</td>
                        <td><span class="badge badge-${escapeHtml(assignment.complexity_level || 'medium')}">${escapeHtml(assignment.complexity_level || 'medium')}</span></td>
                        <td><span class="badge badge-${escapeHtml(assignment.priority_level || 'medium')}">${escapeHtml(assignment.priority_level || 'medium')}</span></td>
                        <td>
                            <span class="badge ${badgeClass}">
                                ${escapeHtml(revRef)}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editProcessAssignment(${assignment.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="removeAssignment(${assignment.id}, 'process')">Remove</button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            
            // Update assignment count
            const countElement = document.getElementById('process-resources-count');
            if (countElement) {
                countElement.textContent = assignments.length;
            }
            
            // Reset mass delete controls
            const selectAllCheckbox = document.getElementById('select-all-assignments');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            updateMassDeleteButton();
            
            console.log('Table populated successfully with', assignments.length, 'assignments');
        }
        
        // Get task name from BPMN diagram data
        function getTaskNameFromBpmn(taskId) {
            // Check if processBpmnViewer exists and is properly initialized
            if (typeof processBpmnViewer === 'undefined' || !processBpmnViewer) {
                console.log('BPMN viewer not available for task name extraction');
                return null;
            }
            
            try {
                const elementRegistry = processBpmnViewer.get('elementRegistry');
                if (!elementRegistry) {
                    console.log('Element registry not available in BPMN viewer');
                    return null;
                }
                
                const element = elementRegistry.get(taskId);
                
                if (element && element.businessObject) {
                    const taskName = element.businessObject.name;
                    if (taskName && taskName.trim()) {
                        return taskName.trim();
                    }
                }
            } catch (error) {
                console.log('Could not get task name from BPMN for', taskId, ':', error.message);
            }
            
            return null;
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // JavaScript version of PHP getRevisionBadgeClass function
        function getRevisionBadgeClass(revisionRef) {
            if (!revisionRef) return 'badge-default';
            
            const ref = revisionRef.toUpperCase();
            if (ref.includes('REV-001')) return 'badge-rev-001';
            if (ref.includes('REV-002')) return 'badge-rev-002';
            if (ref.includes('REV-003')) return 'badge-rev-003';
            if (ref.includes('SIM')) return 'badge-sim';
            return 'badge-default';
        }
        
        // Add click handlers to tasks for resource assignment
        function addTaskClickHandlers() {
            if (!processBpmnViewer) return;
            
            const canvas = processBpmnViewer.get('canvas');
            const elementRegistry = processBpmnViewer.get('elementRegistry');
            
            // Get all task elements
            const tasks = elementRegistry.filter(function(element) {
                return element.type && (
                    element.type.includes('Task') || 
                    element.type === 'bpmn:UserTask' ||
                    element.type === 'bpmn:ServiceTask' ||
                    element.type === 'bpmn:ScriptTask' ||
                    element.type === 'bpmn:ManualTask'
                );
            });
            
            // Add click handlers and styling
            tasks.forEach(function(task) {
                const gfx = canvas.getGraphics(task);
                if (gfx) {
                    // Add clickable styling
                    gfx.classList.add('clickable-task');
                    
                    // Add click event
                    gfx.addEventListener('click', function(event) {
                        event.stopPropagation();
                        handleTaskClick(task);
                    });
                }
            });
        }
        
        // Handle task click for resource assignment
        function handleTaskClick(task) {
            console.log('Task clicked:', task.id, task.businessObject);
            
            const taskId = task.id;
            // Try multiple ways to get task name
            let taskName = task.businessObject.name || 
                          task.businessObject.$attrs?.name || 
                          task.businessObject.get?.('name') ||
                          task.id;
            
            console.log('Extracted task info:', { id: taskId, name: taskName, businessObject: task.businessObject });
            
            // Store the clicked task info globally for use after modal loads
            window.clickedTaskInfo = { id: taskId, name: taskName };
            
            // Open process assignment modal with pre-selected task
            openProcessModal();
            
            // Pre-select the clicked task and fill task name with longer delay
            setTimeout(function() {
                const taskSelect = document.getElementById('modal-task');
                const taskNameField = document.getElementById('modal-task-name');
                
                console.log('Modal elements found:', { taskSelect: !!taskSelect, taskNameField: !!taskNameField });
                console.log('Using stored task info:', window.clickedTaskInfo);
                
                if (taskSelect && window.clickedTaskInfo) {
                    // First, directly add the clicked task to ensure it exists
                    console.log('Adding clicked task directly to dropdown:', window.clickedTaskInfo);
                    
                    // Clear existing options and add the clicked task first
                    taskSelect.innerHTML = '<option value="">Choose a task...</option>';
                    
                    // Add the clicked task
                    const clickedOption = document.createElement('option');
                    clickedOption.value = window.clickedTaskInfo.id;
                    clickedOption.textContent = window.clickedTaskInfo.name;
                    taskSelect.appendChild(clickedOption);
                    
                    // Now load other tasks from database
                    const processId = document.getElementById('process-selector').value;
                    console.log('Current process ID:', processId);
                    
                    if (processId) {
                        // Load additional tasks from database
                        fetch(window.location.pathname + '?action=get_tasks&process_id=' + processId)
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(tasks) {
                            console.log('Database tasks loaded:', tasks);
                            
                            // Add database tasks (avoid duplicates)
                            tasks.forEach(function(task) {
                                // Check if task already exists
                                let exists = false;
                                for (let i = 0; i < taskSelect.options.length; i++) {
                                    if (taskSelect.options[i].value === task.task_id) {
                                        exists = true;
                                        break;
                                    }
                                }
                                
                                if (!exists) {
                                    const option = document.createElement('option');
                                    option.value = task.task_id;
                                    option.textContent = task.task_name || task.task_id;
                                    taskSelect.appendChild(option);
                                }
                            });
                            
                            // Now select the clicked task
                            taskSelect.value = window.clickedTaskInfo.id;
                            console.log('Task selected:', taskSelect.value);
                            
                            // Fill task name field (even though it's hidden)
                            if (taskNameField) {
                                taskNameField.value = window.clickedTaskInfo.name;
                            }
                            
                            // Trigger task selection to load duration
                            const event = new Event('change');
                            taskSelect.dispatchEvent(event);
                            
                            // Clear the stored info
                            window.clickedTaskInfo = null;
                        })
                        .catch(function(error) {
                            console.error('Error loading database tasks:', error);
                            // Even if database loading fails, we still have the clicked task
                            taskSelect.value = window.clickedTaskInfo.id;
                            console.log('Using clicked task only:', taskSelect.value);
                            
                            if (taskNameField) {
                                taskNameField.value = window.clickedTaskInfo.name;
                            }
                            
                            window.clickedTaskInfo = null;
                        });
                    } else {
                        // No process ID, just use the clicked task
                        taskSelect.value = window.clickedTaskInfo.id;
                        console.log('No process ID, using clicked task only:', taskSelect.value);
                        
                        if (taskNameField) {
                            taskNameField.value = window.clickedTaskInfo.name;
                        }
                        
                        window.clickedTaskInfo = null;
                    }
                } else {
                    console.error('Modal elements not found or no clicked task info');
                }
            }, 1200); // Increased timeout further
        }
        
        // Highlight assigned tasks with colors based on Rev/Ref#
        function highlightAssignedTasks() {
            if (!processBpmnViewer || !processAssignments) return;
            
            const canvas = processBpmnViewer.get('canvas');
            const elementRegistry = processBpmnViewer.get('elementRegistry');
            
            // Group assignments by task_id
            const taskAssignments = {};
            processAssignments.forEach(function(assignment) {
                if (!taskAssignments[assignment.task_id]) {
                    taskAssignments[assignment.task_id] = [];
                }
                taskAssignments[assignment.task_id].push(assignment);
            });
            
            // Apply highlighting to each assigned task
            Object.keys(taskAssignments).forEach(function(taskId) {
                const element = elementRegistry.get(taskId);
                if (element) {
                    const gfx = canvas.getGraphics(element);
                    if (gfx) {
                        // Remove existing assignment classes
                        gfx.classList.remove('assigned-task', 'assigned-task-rev1', 'assigned-task-rev2', 'assigned-task-sim');
                        
                        // Get unique Rev/Ref# values for this task
                        const revRefs = [...new Set(taskAssignments[taskId].map(a => a.revision_ref))];
                        
                        if (revRefs.length > 0) {
                            // Apply color based on Rev/Ref# pattern
                            if (revRefs.some(ref => ref && ref.includes('REV-001'))) {
                                gfx.classList.add('assigned-task-rev1');
                            } else if (revRefs.some(ref => ref && ref.includes('REV-002'))) {
                                gfx.classList.add('assigned-task-rev2');
                            } else if (revRefs.some(ref => ref && ref.includes('SIM'))) {
                                gfx.classList.add('assigned-task-sim');
                            } else {
                                gfx.classList.add('assigned-task');
                            }
                        }
                    }
                }
            });
            
            // Update legend after highlighting
            updateRevRefLegend();
        }
        
        // Update Rev/Ref# legend with available revisions
        function updateRevRefLegend() {
            if (!processAssignments) return;
            
            const legendContainer = document.getElementById('rev-ref-legend');
            const loadingElement = document.getElementById('legend-loading');
            
            if (!legendContainer) return;
            
            // Get unique Rev/Ref# values
            const revRefs = [...new Set(processAssignments.map(a => a.revision_ref).filter(ref => ref))];
            
            // Clear loading message
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            // Remove existing legend items (except "All Assignments")
            const existingItems = legendContainer.querySelectorAll('.legend-item:not(:first-child)');
            existingItems.forEach(item => item.remove());
            
            // Add legend items for each Rev/Ref#
            revRefs.forEach(function(revRef) {
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.onclick = function() { highlightRevRef(revRef); };
                legendItem.style.cssText = 'cursor: pointer; padding: 8px 12px; border: 2px solid #ccc; border-radius: 5px; font-size: 12px; font-weight: bold; transition: all 0.2s;';
                
                // Set color based on Rev/Ref# pattern
                let color = '#28a745'; // default green
                let bgColor = 'rgba(40, 167, 69, 0.1)';
                
                if (revRef.includes('REV-001')) {
                    color = '#007bff';
                    bgColor = 'rgba(0, 123, 255, 0.1)';
                } else if (revRef.includes('REV-002')) {
                    color = '#dc3545';
                    bgColor = 'rgba(220, 53, 69, 0.1)';
                } else if (revRef.includes('SIM')) {
                    color = '#ffc107';
                    bgColor = 'rgba(255, 193, 7, 0.1)';
                }
                
                legendItem.style.borderColor = color;
                legendItem.style.backgroundColor = bgColor;
                
                legendItem.innerHTML = '<span style="color: ' + color + ';">■</span> ' + revRef;
                
                legendContainer.appendChild(legendItem);
            });
            
            // Show message if no assignments
            if (revRefs.length === 0) {
                const noAssignments = document.createElement('div');
                noAssignments.style.cssText = 'color: #666; font-style: italic;';
                noAssignments.textContent = 'No assignments found for this process';
                legendContainer.appendChild(noAssignments);
            }
        }
        
        // Highlight tasks for specific Rev/Ref# or show all
        function highlightRevRef(targetRevRef) {
            if (!processBpmnViewer || !processAssignments) return;
            
            const canvas = processBpmnViewer.get('canvas');
            const elementRegistry = processBpmnViewer.get('elementRegistry');
            
            // Update legend item styling
            const legendItems = document.querySelectorAll('.legend-item');
            legendItems.forEach(function(item) {
                item.style.opacity = '0.5';
                item.style.transform = 'scale(0.95)';
            });
            
            // Highlight selected legend item
            const selectedItem = Array.from(legendItems).find(item => 
                (targetRevRef === '' && item.textContent.includes('All Assignments')) ||
                (targetRevRef !== '' && item.textContent.includes(targetRevRef))
            );
            if (selectedItem) {
                selectedItem.style.opacity = '1';
                selectedItem.style.transform = 'scale(1)';
            }
            
            // Group assignments by task_id
            const taskAssignments = {};
            processAssignments.forEach(function(assignment) {
                if (!taskAssignments[assignment.task_id]) {
                    taskAssignments[assignment.task_id] = [];
                }
                taskAssignments[assignment.task_id].push(assignment);
            });
            
            // Apply highlighting to tasks
            Object.keys(taskAssignments).forEach(function(taskId) {
                const element = elementRegistry.get(taskId);
                if (element) {
                    const gfx = canvas.getGraphics(element);
                    if (gfx) {
                        // Remove all assignment classes
                        gfx.classList.remove('assigned-task', 'assigned-task-rev1', 'assigned-task-rev2', 'assigned-task-sim');
                        
                        const taskRevRefs = taskAssignments[taskId].map(a => a.revision_ref);
                        
                        if (targetRevRef === '') {
                            // Show all assignments (original behavior)
                            const revRefs = [...new Set(taskRevRefs)];
                            if (revRefs.length > 0) {
                                if (revRefs.some(ref => ref && ref.includes('REV-001'))) {
                                    gfx.classList.add('assigned-task-rev1');
                                } else if (revRefs.some(ref => ref && ref.includes('REV-002'))) {
                                    gfx.classList.add('assigned-task-rev2');
                                } else if (revRefs.some(ref => ref && ref.includes('SIM'))) {
                                    gfx.classList.add('assigned-task-sim');
                                } else {
                                    gfx.classList.add('assigned-task');
                                }
                            }
                        } else {
                            // Show only specific Rev/Ref#
                            if (taskRevRefs.includes(targetRevRef)) {
                                if (targetRevRef.includes('REV-001')) {
                                    gfx.classList.add('assigned-task-rev1');
                                } else if (targetRevRef.includes('REV-002')) {
                                    gfx.classList.add('assigned-task-rev2');
                                } else if (targetRevRef.includes('SIM')) {
                                    gfx.classList.add('assigned-task-sim');
                                } else {
                                    gfx.classList.add('assigned-task');
                                }
                            }
                            // Tasks without the target Rev/Ref# remain uncolored
                        }
                    }
                }
            });
            
            // Filter table rows based on selected revision
            filterTableByRevision(targetRevRef);
        }
        
        // Filter table rows by revision reference
        function filterTableByRevision(targetRevRef) {
            const table = document.getElementById('process-resources-table');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(function(row) {
                const revCell = row.querySelector('td:nth-child(8)'); // Rev/Ref# column
                if (revCell) {
                    const revText = revCell.textContent.trim();
                    
                    if (targetRevRef === '' || revText.includes(targetRevRef)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Update the assignment count
            const countElement = document.getElementById('process-resources-count');
            if (countElement) {
                countElement.textContent = visibleCount;
            }
            
            // Update the revision filter dropdown to match
            const revisionFilter = document.querySelector('select[onchange*="filterTableByColumn"]');
            if (revisionFilter) {
                if (targetRevRef === '') {
                    revisionFilter.value = '';
                } else {
                    // Find matching option
                    const options = revisionFilter.querySelectorAll('option');
                    options.forEach(option => {
                        if (option.value === targetRevRef) {
                            revisionFilter.value = targetRevRef;
                        }
                    });
                }
            }
        }
        
        // Refresh process resources data from database
        function refreshProcessResources() {
            const processSelector = document.getElementById('process-selector');
            if (!processSelector || !processSelector.value) {
                showMessage('Please select a process first', 'error');
                return;
            }
            
            console.log('Refreshing process resources data...');
            
            // Show loading indicator
            const tbody = document.getElementById('process-resources-tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #666;">Refreshing data...</td></tr>';
            }
            
            // Reload data using same AJAX source as diagram
            loadProcessAssignments(processSelector.value);
        }
        
        // Retrieve assignments with detailed debugging information
        async function retrieveAssignments() {
            const processSelector = document.getElementById('process-selector');
            if (!processSelector || !processSelector.value) {
                showMessage('Please select a process first', 'error');
                return;
            }
            
            console.log('=== RETRIEVE ASSIGNMENTS DEBUG ===');
            console.log('Process ID:', processSelector.value);
            
            // Show loading indicator
            const tbody = document.getElementById('process-resources-tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #666;">Retrieving assignments from database...</td></tr>';
            }
            
            try {
                const url = window.location.href.split('?')[0] + '?action=get_process_assignments&process_id=' + processSelector.value + '&debug=1&timestamp=' + Date.now();
                console.log('Fetching from URL:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const data = await response.json();
                console.log('Raw response data:', data);
                
                if (data.success) {
                    const assignments = data.assignments || [];
                    console.log('Number of assignments retrieved:', assignments.length);
                    
                    // Log each assignment in detail
                    assignments.forEach((assignment, index) => {
                        console.log(`Assignment ${index + 1}:`, {
                            id: assignment.id,
                            task_id: assignment.task_id,
                            resource_name: assignment.resource_name,
                            revision_ref: assignment.revision_ref,
                            duration_minutes: assignment.duration_minutes,
                            assigned_date: assignment.assigned_date
                        });
                    });
                    
                    // Update global variable and populate table
                    processAssignments = assignments;
                    populateProcessResourcesTable(assignments);
                    
                    // Show success message with count
                    showMessage(`Successfully retrieved ${assignments.length} assignments from database`, 'success');
                    
                    // Also update diagram highlighting
                    if (typeof updateProcessHighlighting === 'function') {
                        updateProcessHighlighting();
                    }
                    
                } else {
                    console.error('Failed to retrieve assignments:', data.message);
                    showMessage('Failed to retrieve assignments: ' + (data.message || 'Unknown error'), 'error');
                    
                    // Show empty table
                    populateProcessResourcesTable([]);
                }
                
            } catch (error) {
                console.error('Error retrieving assignments:', error);
                showMessage('Network error retrieving assignments: ' + error.message, 'error');
                
                // Show error in table
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #d32f2f;">Error retrieving assignments. Check console for details.</td></tr>';
                }
            }
            
            console.log('=== END RETRIEVE ASSIGNMENTS DEBUG ===');
        }
        
        // Mass delete functionality
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('select-all-assignments');
            const assignmentCheckboxes = document.querySelectorAll('.assignment-checkbox');
            
            assignmentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateMassDeleteButton();
        }
        
        function updateMassDeleteButton() {
            const selectedCheckboxes = document.querySelectorAll('.assignment-checkbox:checked');
            const massDeleteBtn = document.getElementById('mass-delete-btn');
            const selectedCount = document.getElementById('selected-count');
            const selectAllCheckbox = document.getElementById('select-all-assignments');
            const allCheckboxes = document.querySelectorAll('.assignment-checkbox');
            
            if (selectedCheckboxes.length > 0) {
                massDeleteBtn.style.display = 'inline-block';
                selectedCount.textContent = selectedCheckboxes.length;
            } else {
                massDeleteBtn.style.display = 'none';
            }
            
            // Update select all checkbox state
            if (selectedCheckboxes.length === allCheckboxes.length && allCheckboxes.length > 0) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (selectedCheckboxes.length > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }
        
        async function massDeleteAssignments() {
            const selectedCheckboxes = document.querySelectorAll('.assignment-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                showMessage('No assignments selected for deletion', 'error');
                return;
            }
            
            const assignmentIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            const confirmMessage = `Are you sure you want to delete ${assignmentIds.length} selected assignment(s)?\n\nThis action cannot be undone.`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            try {
                console.log('Deleting assignments:', assignmentIds);
                
                const formData = new FormData();
                formData.append('action', 'mass_delete_assignments');
                formData.append('assignment_ids', JSON.stringify(assignmentIds));
                
                const response = await fetch(window.location.href.split('?')[0], {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(`Successfully deleted ${assignmentIds.length} assignment(s)`, 'success');
                    
                    // Refresh the table and diagram
                    const processSelector = document.getElementById('process-selector');
                    if (processSelector && processSelector.value) {
                        await loadProcessAssignments(processSelector.value);
                        highlightAssignedTasks();
                    }
                    
                    // Reset checkboxes
                    document.getElementById('select-all-assignments').checked = false;
                    updateMassDeleteButton();
                    
                } else {
                    showMessage('Failed to delete assignments: ' + (data.message || 'Unknown error'), 'error');
                }
                
            } catch (error) {
                console.error('Mass delete error:', error);
                showMessage('Error deleting assignments: ' + error.message, 'error');
            }
        }
        
        // Override the existing loadProcessResources function to also load BPMN viewer
        const originalLoadProcessResources = window.loadProcessResources;
        window.loadProcessResources = function() {
            if (originalLoadProcessResources) {
                originalLoadProcessResources();
            }
            
            // Load BPMN viewer for selected process
            const processSelector = document.getElementById('process-selector');
            if (processSelector && processSelector.value) {
                loadProcessInBPMNViewer(processSelector.value);
            }
        };
        
        // Modal drag functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, checking for selected process...');
            
            // Initialize assignments for selected process
            const processSelector = document.getElementById('process-selector');
            if (processSelector && processSelector.value) {
                console.log('Found selected process on page load:', processSelector.value);
                
                // Wait a bit for BPMN viewer to initialize, then load assignments
                setTimeout(function() {
                    console.log('Loading assignments for selected process...');
                    loadProcessAssignments(processSelector.value);
                }, 1000);
            } else {
                console.log('No process selected on page load');
            }
            
            const modals = document.querySelectorAll('.modal-content');
            
            modals.forEach(function(modal) {
                let isDragging = false;
                let currentX;
                let currentY;
                let initialX;
                let initialY;
                let xOffset = 0;
                let yOffset = 0;
                
                const header = modal.querySelector('.modal-header');
                if (header) {
                    header.addEventListener('mousedown', dragStart);
                }
                
                function dragStart(e) {
                    if (e.target.classList.contains('close')) return;
                    
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;
                    
                    if (e.target === header || header.contains(e.target)) {
                        isDragging = true;
                        modal.style.position = 'fixed';
                        modal.style.margin = '0';
                    }
                }
                
                document.addEventListener('mousemove', drag);
                document.addEventListener('mouseup', dragEnd);
                
                function drag(e) {
                    if (isDragging) {
                        e.preventDefault();
                        currentX = e.clientX - initialX;
                        currentY = e.clientY - initialY;
                        
                        xOffset = currentX;
                        yOffset = currentY;
                        
                        // Keep modal within viewport bounds
                        const rect = modal.getBoundingClientRect();
                        const maxX = window.innerWidth - rect.width;
                        const maxY = window.innerHeight - rect.height;
                        
                        xOffset = Math.max(0, Math.min(xOffset, maxX));
                        yOffset = Math.max(0, Math.min(yOffset, maxY));
                        
                        modal.style.transform = 'translate(' + xOffset + 'px, ' + yOffset + 'px)';
                    }
                }
                
                function dragEnd(e) {
                    initialX = currentX;
                    initialY = currentY;
                    isDragging = false;
                }
            });
        });
    </script>
</body>
</html>

