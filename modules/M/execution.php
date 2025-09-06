<?php
// modules/M/execution.php - MACTA Flowable Execution Sub-page
header('Content-Type: text/html; charset=utf-8');

// Initialize variables for database connection
$processes = [];
$projects = [];
$process_instances = [];
$deployments = [];
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
        
        // Create process_instances table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS process_instances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                process_model_id INT NOT NULL,
                instance_key VARCHAR(255) NOT NULL,
                flowable_instance_id VARCHAR(255),
                status ENUM('running', 'completed', 'suspended', 'terminated') DEFAULT 'running',
                start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_time TIMESTAMP NULL,
                variables JSON,
                created_by VARCHAR(100) DEFAULT 'system',
                FOREIGN KEY (process_model_id) REFERENCES process_models(id) ON DELETE CASCADE
            )
        ");
        
        // Create deployments table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS process_deployments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                process_model_id INT NOT NULL,
                deployment_id VARCHAR(255),
                flowable_process_key VARCHAR(255),
                status ENUM('deployed', 'undeployed', 'failed') DEFAULT 'deployed',
                deployed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (process_model_id) REFERENCES process_models(id) ON DELETE CASCADE
            )
        ");
        
        // Get process instances
        $stmt = $pdo->prepare("
            SELECT pi.*, pm.name as process_name, p.name as project_name
            FROM process_instances pi
            LEFT JOIN process_models pm ON pi.process_model_id = pm.id
            LEFT JOIN projects p ON pm.project_id = p.id
            ORDER BY pi.start_time DESC
            LIMIT 50
        ");
        $stmt->execute();
        $process_instances = $stmt->fetchAll();
        
        // Get deployments
        $stmt = $pdo->prepare("
            SELECT pd.*, pm.name as process_name
            FROM process_deployments pd
            LEFT JOIN process_models pm ON pd.process_model_id = pm.id
            ORDER BY pd.deployed_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $deployments = $stmt->fetchAll();
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Execution DB Error: " . $e->getMessage());
}

// Flowable REST API Configuration (you'll need to configure this)
$FLOWABLE_CONFIG = [
    'base_url' => 'http://localhost:8080/flowable-rest/service',
    'username' => 'admin',
    'password' => 'test',
    'enabled' => false // Set to true when Flowable server is running
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if (!empty($db_error)) {
            echo json_encode(['success' => false, 'message' => $db_error]);
            exit;
        }
        
        switch ($_POST['action']) {
            case 'deploy_process':
                $processId = $_POST['process_id'] ?? 0;
                
                // Get process model
                $stmt = $pdo->prepare("SELECT * FROM process_models WHERE id = ?");
                $stmt->execute([$processId]);
                $process = $stmt->fetch();
                
                if (!$process) {
                    echo json_encode(['success' => false, 'message' => 'Process not found']);
                    exit;
                }
                
                if ($FLOWABLE_CONFIG['enabled']) {
                    // Deploy to actual Flowable server
                    $deployment_result = deployToFlowable($process, $FLOWABLE_CONFIG);
                    if (!$deployment_result['success']) {
                        echo json_encode($deployment_result);
                        exit;
                    }
                    $deployment_id = $deployment_result['deployment_id'];
                    $process_key = $deployment_result['process_key'];
                } else {
                    // Simulate deployment
                    $deployment_id = 'sim_' . uniqid();
                    $process_key = 'process_' . $processId;
                }
                
                // Record deployment
                $stmt = $pdo->prepare("
                    INSERT INTO process_deployments (process_model_id, deployment_id, flowable_process_key, status)
                    VALUES (?, ?, ?, 'deployed')
                ");
                $stmt->execute([$processId, $deployment_id, $process_key]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Process deployed successfully',
                    'deployment_id' => $deployment_id
                ]);
                break;
                
            case 'start_instance':
                $processId = $_POST['process_id'] ?? 0;
                $variables = $_POST['variables'] ?? '{}';
                
                // Get deployment info
                $stmt = $pdo->prepare("
                    SELECT pd.* FROM process_deployments pd 
                    WHERE pd.process_model_id = ? AND pd.status = 'deployed'
                    ORDER BY pd.deployed_at DESC LIMIT 1
                ");
                $stmt->execute([$processId]);
                $deployment = $stmt->fetch();
                
                if (!$deployment) {
                    echo json_encode(['success' => false, 'message' => 'Process not deployed']);
                    exit;
                }
                
                if ($FLOWABLE_CONFIG['enabled']) {
                    // Start instance in Flowable
                    $instance_result = startFlowableInstance($deployment['flowable_process_key'], $variables, $FLOWABLE_CONFIG);
                    if (!$instance_result['success']) {
                        echo json_encode($instance_result);
                        exit;
                    }
                    $instance_id = $instance_result['instance_id'];
                } else {
                    // Simulate instance start
                    $instance_id = 'sim_inst_' . uniqid();
                }
                
                // Record instance
                $instance_key = uniqid('inst_');
                $stmt = $pdo->prepare("
                    INSERT INTO process_instances (process_model_id, instance_key, flowable_instance_id, status, variables)
                    VALUES (?, ?, ?, 'running', ?)
                ");
                $stmt->execute([$processId, $instance_key, $instance_id, $variables]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Process instance started',
                    'instance_key' => $instance_key
                ]);
                break;
                
            case 'get_instance_status':
                $instanceId = $_POST['instance_id'] ?? 0;
                
                $stmt = $pdo->prepare("SELECT * FROM process_instances WHERE id = ?");
                $stmt->execute([$instanceId]);
                $instance = $stmt->fetch();
                
                if (!$instance) {
                    echo json_encode(['success' => false, 'message' => 'Instance not found']);
                    exit;
                }
                
                if ($FLOWABLE_CONFIG['enabled'] && $instance['flowable_instance_id']) {
                    // Get status from Flowable
                    $status = getFlowableInstanceStatus($instance['flowable_instance_id'], $FLOWABLE_CONFIG);
                    
                    // Update local status
                    $stmt = $pdo->prepare("UPDATE process_instances SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $instanceId]);
                    $instance['status'] = $status;
                }
                
                echo json_encode(['success' => true, 'instance' => $instance]);
                break;
                
            case 'terminate_instance':
                $instanceId = $_POST['instance_id'] ?? 0;
                
                $stmt = $pdo->prepare("SELECT * FROM process_instances WHERE id = ?");
                $stmt->execute([$instanceId]);
                $instance = $stmt->fetch();
                
                if (!$instance) {
                    echo json_encode(['success' => false, 'message' => 'Instance not found']);
                    exit;
                }
                
                if ($FLOWABLE_CONFIG['enabled'] && $instance['flowable_instance_id']) {
                    // Terminate in Flowable
                    $result = terminateFlowableInstance($instance['flowable_instance_id'], $FLOWABLE_CONFIG);
                    if (!$result['success']) {
                        echo json_encode($result);
                        exit;
                    }
                }
                
                // Update local status
                $stmt = $pdo->prepare("
                    UPDATE process_instances 
                    SET status = 'terminated', end_time = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$instanceId]);
                
                echo json_encode(['success' => true, 'message' => 'Instance terminated']);
                break;
                
            case 'get_processes_by_project':
                $stmt = $pdo->prepare("SELECT * FROM process_models WHERE project_id = ? ORDER BY name");
                $stmt->execute([$_POST['project_id'] ?? 1]);
                $processes = $stmt->fetchAll();
                echo json_encode(['success' => true, 'processes' => $processes]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Flowable API functions (these would integrate with actual Flowable REST API)
function deployToFlowable($process, $config) {
    // This is a placeholder - you'd implement actual Flowable REST API calls here
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/repository/deployments');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
    
    // Prepare multipart form data with BPMN file
    $postData = [
        'deployment-name' => 'MACTA_' . $process['name'],
        'deployment-key' => 'macta_' . $process['id'],
        'file' => new CURLFile('data://application/xml;base64,' . base64_encode($process['model_data']), 'application/xml', $process['name'] . '.bpmn20.xml')
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'deployment_id' => $result['id'],
            'process_key' => $result['deployedProcessDefinitions'][0]['key'] ?? 'unknown'
        ];
    } else {
        return ['success' => false, 'message' => 'Deployment failed: ' . $response];
    }
}

function startFlowableInstance($processKey, $variables, $config) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/runtime/process-instances');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $postData = json_encode([
        'processDefinitionKey' => $processKey,
        'variables' => json_decode($variables, true) ?: []
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'instance_id' => $result['id']
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to start instance: ' . $response];
    }
}

function getFlowableInstanceStatus($instanceId, $config) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/runtime/process-instances/' . $instanceId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['ended'] ? 'completed' : 'running';
    } elseif ($httpCode === 404) {
        return 'completed'; // Instance not found, probably completed
    }
    
    return 'unknown';
}

function terminateFlowableInstance($instanceId, $config) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/runtime/process-instances/' . $instanceId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 204) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to terminate: ' . $response];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Flowable Execution Engine</title>
    
    <style>
        /* Enhanced Execution Engine Styles */
        :root {
            --primary-color: #1E88E5;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --dark-color: #2c3e50;
            --border-color: #bdc3c7;
            --macta-orange: #FF6B35;
            --running-color: #2ecc71;
            --completed-color: #95a5a6;
            --terminated-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .tab-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .tab-header p {
            margin: 0;
            opacity: 0.9;
        }

        .status-message {
            background: linear-gradient(135deg, #e8f5e8, #d5f4e6);
            border: 2px solid var(--success-color);
            border-radius: 10px;
            padding: 15px;
            margin: 20px;
            color: var(--success-color);
            text-align: center;
            font-weight: bold;
        }

        .flowable-status {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid var(--warning-color);
            border-radius: 10px;
            padding: 15px;
            margin: 20px;
            color: #856404;
            text-align: center;
        }

        .execution-panel {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .selector-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .selector-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .selector-group select, .selector-group input, .selector-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            box-sizing: border-box;
        }

        .selector-group select:focus, .selector-group input:focus, .selector-group textarea:focus {
            border-color: var(--macta-orange);
            outline: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-warning { background: var(--warning-color); color: white; }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-info { background: var(--info-color); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-macta { background: var(--macta-orange); color: white; }

        .instances-table, .deployments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .instances-table th, .instances-table td,
        .deployments-table th, .deployments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .instances-table th, .deployments-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: var(--dark-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-running { background: #d5f4e6; color: var(--running-color); }
        .status-completed { background: #ecf0f1; color: var(--completed-color); }
        .status-terminated { background: #fadbd8; color: var(--terminated-color); }
        .status-suspended { background: #fff3cd; color: var(--warning-color); }
        .status-deployed { background: #d5f4e6; color: var(--running-color); }
        .status-failed { background: #fadbd8; color: var(--terminated-color); }

        .json-editor {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            min-height: 100px;
            background: #f8f9fa;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--macta-orange);
        }

        .stat-label {
            color: var(--dark-color);
            margin-top: 5px;
        }

        .process-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--macta-orange);
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .refresh-btn {
            float: right;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .selector-row, .panel-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .instances-table, .deployments-table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tab-header">
            <h2>⚙️ MACTA - Flowable Execution Engine</h2>
            <p>Deploy and execute business processes using Flowable BPMN Engine - Module M</p>
        </div>

        <?php if (!$FLOWABLE_CONFIG['enabled']): ?>
        <div class="flowable-status">
            ⚠️ Flowable Server Connection: <strong>SIMULATION MODE</strong><br>
            Configure Flowable server settings in the code to enable real execution
        </div>
        <?php else: ?>
        <div class="status-message">
            ✅ Connected to Flowable Server at <?= htmlspecialchars($FLOWABLE_CONFIG['base_url']) ?>
        </div>
        <?php endif; ?>

        <!-- Process Selection and Deployment Panel -->
        <div class="execution-panel">
            <h3>📋 Process Deployment</h3>
            <div class="selector-row">
                <div class="selector-group">
                    <label>🏢 Project:</label>
                    <select id="project-select">
                        <option value="">Select Project...</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= htmlspecialchars($project['id']) ?>">
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="selector-group">
                    <label>⚙️ Process Model:</label>
                    <select id="process-select" disabled>
                        <option value="">Select a project first...</option>
                    </select>
                </div>
            </div>
            
            <div id="process-info" class="process-info" style="display: none;">
                <h4>Selected Process Information</h4>
                <div id="process-details"></div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" id="btn-deploy-process" disabled>🚀 Deploy Process</button>
                <button class="btn btn-success" id="btn-start-instance" disabled>▶️ Start Instance</button>
                <button class="btn btn-info" id="btn-refresh-data">🔄 Refresh Data</button>
            </div>
        </div>

        <!-- Process Execution Panel -->
        <div class="panel-grid">
            <!-- Start Instance Panel -->
            <div class="execution-panel">
                <h3>▶️ Start Process Instance</h3>
                <div class="selector-group">
                    <label>Process Variables (JSON):</label>
                    <textarea id="process-variables" class="json-editor" placeholder='{"variable1": "value1", "variable2": "value2"}'>{}</textarea>
                </div>
                <button class="btn btn-macta" id="btn-start-with-vars" disabled>🎯 Start with Variables</button>
            </div>

            <!-- Quick Stats Panel -->
            <div class="execution-panel">
                <h3>📊 Execution Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="stat-deployments"><?= count($deployments) ?></div>
                        <div class="stat-label">Deployments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-instances"><?= count($process_instances) ?></div>
                        <div class="stat-label">Total Instances</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-running"><?= count(array_filter($process_instances, fn($i) => $i['status'] === 'running')) ?></div>
                        <div class="stat-label">Running</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-completed"><?= count(array_filter($process_instances, fn($i) => $i['status'] === 'completed')) ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Instances Table -->
        <div class="execution-panel">
            <h3>🏃‍♂️ Running Process Instances</h3>
            <button class="btn btn-secondary refresh-btn" onclick="refreshInstances()">🔄 Refresh</button>
            <div style="clear: both;"></div>
            
            <?php if (empty($process_instances)): ?>
            <div class="empty-state">
                <h4>No Process Instances Found</h4>
                <p>Deploy and start some processes to see them here.</p>
            </div>
            <?php else: ?>
            <table class="instances-table">
                <thead>
                    <tr>
                        <th>Instance Key</th>
                        <th>Process</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($process_instances as $instance): ?>
                    <tr>
                        <td><?= htmlspecialchars($instance['instance_key']) ?></td>
                        <td><?= htmlspecialchars($instance['process_name']) ?></td>
                        <td><?= htmlspecialchars($instance['project_name']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $instance['status'] ?>">
                                <?= $instance['status'] ?>
                            </span>
                        </td>
                        <td><?= date('M j, H:i', strtotime($instance['start_time'])) ?></td>
                        <td>
                            <?php 
                            $end_time = $instance['end_time'] ? strtotime($instance['end_time']) : time();
                            $start_time = strtotime($instance['start_time']);
                            $duration = $end_time - $start_time;
                            
                            if ($duration < 60) {
                                echo $duration . 's';
                            } elseif ($duration < 3600) {
                                echo round($duration / 60) . 'm';
                            } else {
                                echo round($duration / 3600, 1) . 'h';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="showInstanceDetails(<?= $instance['id'] ?>)">👁️ View</button>
                            <?php if ($instance['status'] === 'running'): ?>
                            <button class="btn btn-danger btn-sm" onclick="terminateInstance(<?= $instance['id'] ?>)">⏹️ Stop</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Deployments Table -->
        <div class="execution-panel">
            <h3>🚀 Process Deployments</h3>
            
            <?php if (empty($deployments)): ?>
            <div class="empty-state">
                <h4>No Deployments Found</h4>
                <p>Deploy some processes to see them here.</p>
            </div>
            <?php else: ?>
            <table class="deployments-table">
                <thead>
                    <tr>
                        <th>Process Name</th>
                        <th>Deployment ID</th>
                        <th>Process Key</th>
                        <th>Status</th>
                        <th>Deployed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deployments as $deployment): ?>
                    <tr>
                        <td><?= htmlspecialchars($deployment['process_name']) ?></td>
                        <td><code><?= htmlspecialchars(substr($deployment['deployment_id'], 0, 20)) ?>...</code></td>
                        <td><code><?= htmlspecialchars($deployment['flowable_process_key']) ?></code></td>
                        <td>
                            <span class="status-badge status-<?= $deployment['status'] ?>">
                                <?= $deployment['status'] ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y H:i', strtotime($deployment['deployed_at'])) ?></td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="startInstanceFromDeployment(<?= $deployment['process_model_id'] ?>)">▶️ Start</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Status Bar -->
        <div style="background: #f8f9fa; padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px; color: var(--dark-color); font-weight: 500;">
                <span>📊 Status:</span>
                <span id="status-text">Ready</span>
            </div>
            <div>
                <span id="current-process">No process selected</span>
                <span style="margin-left: 20px;">Server: <?= $FLOWABLE_CONFIG['enabled'] ? 'Connected' : 'Simulation' ?></span>
            </div>
        </div>
    </div>

    <!-- Instance Details Modal -->
    <div id="instanceModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 12px; width: 80%; max-width: 600px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <h3>Process Instance Details</h3>
                <button onclick="hideInstanceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div id="instance-details-content">
                <!-- Instance details will be loaded here -->
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="hideInstanceModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentProjectId = null;
        let currentProcessId = null;
        let currentProcessName = '';
        let processes = <?= json_encode($processes) ?>;
        let projects = <?= json_encode($projects) ?>;

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            updateButtonStates();
            console.log('MACTA Flowable Execution Engine initialized');
        });

        // Setup event listeners
        function setupEventListeners() {
            // Project selection
            document.getElementById('project-select').addEventListener('change', function() {
                const projectId = this.value;
                if (projectId) {
                    currentProjectId = projectId;
                    loadProcessesByProject(projectId);
                } else {
                    currentProjectId = null;
                    const processSelect = document.getElementById('process-select');
                    processSelect.innerHTML = '<option value="">Select a project first...</option>';
                    processSelect.disabled = true;
                    hideProcessInfo();
                }
                updateButtonStates();
            });

            // Process selection
            document.getElementById('process-select').addEventListener('change', function() {
                const processId = this.value;
                if (processId) {
                    currentProcessId = processId;
                    const selectedProcess = processes.find(p => p.id == processId);
                    if (selectedProcess) {
                        currentProcessName = selectedProcess.name;
                        showProcessInfo(selectedProcess);
                    }
                } else {
                    currentProcessId = null;
                    currentProcessName = '';
                    hideProcessInfo();
                }
                updateButtonStates();
            });

            // Button events
            document.getElementById('btn-deploy-process').addEventListener('click', deployProcess);
            document.getElementById('btn-start-instance').addEventListener('click', startInstance);
            document.getElementById('btn-start-with-vars').addEventListener('click', startInstanceWithVariables);
            document.getElementById('btn-refresh-data').addEventListener('click', refreshData);
        }

        // Load processes by project
        async function loadProcessesByProject(projectId) {
            try {
                updateStatus('Loading processes...');
                
                const formData = new FormData();
                formData.append('action', 'get_processes_by_project');
                formData.append('project_id', projectId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    populateProcessSelector(result.processes);
                    processes = result.processes;
                    updateStatus(`Loaded ${result.processes.length} processes`);
                } else {
                    console.error('Failed to load processes:', result.message);
                    updateStatus('Failed to load processes');
                }
                
            } catch (error) {
                console.error('Error loading processes:', error);
                updateStatus('Error loading processes');
            }
        }

        // Populate process selector
        function populateProcessSelector(processes) {
            const processSelect = document.getElementById('process-select');
            processSelect.innerHTML = '<option value="">Select Process...</option>';
            processSelect.disabled = false;
            
            processes.forEach(process => {
                const option = document.createElement('option');
                option.value = process.id;
                option.textContent = process.name;
                processSelect.appendChild(option);
            });
        }

        // Show process information
        function showProcessInfo(process) {
            const infoDiv = document.getElementById('process-info');
            const detailsDiv = document.getElementById('process-details');
            
            detailsDiv.innerHTML = `
                <p><strong>Name:</strong> ${escapeHtml(process.name)}</p>
                <p><strong>Description:</strong> ${escapeHtml(process.description || 'No description')}</p>
                <p><strong>Last Updated:</strong> ${new Date(process.updated_at).toLocaleString()}</p>
                <p><strong>Model Size:</strong> ${process.model_data ? (process.model_data.length + ' characters') : 'No model data'}</p>
            `;
            
            infoDiv.style.display = 'block';
            document.getElementById('current-process').textContent = process.name;
        }

        // Hide process information
        function hideProcessInfo() {
            document.getElementById('process-info').style.display = 'none';
            document.getElementById('current-process').textContent = 'No process selected';
        }

        // Deploy process
        async function deployProcess() {
            if (!currentProcessId) {
                alert('Please select a process to deploy');
                return;
            }

            const confirmed = confirm(`Deploy process "${currentProcessName}" to Flowable engine?`);
            if (!confirmed) return;

            try {
                updateStatus('Deploying process...');
                
                const formData = new FormData();
                formData.append('action', 'deploy_process');
                formData.append('process_id', currentProcessId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Process "${currentProcessName}" deployed successfully!\nDeployment ID: ${result.deployment_id}`);
                    updateStatus('Process deployed successfully');
                    refreshData();
                } else {
                    alert('Deployment failed: ' + result.message);
                    updateStatus('Deployment failed');
                }
                
            } catch (error) {
                console.error('Deploy error:', error);
                alert('Failed to deploy process');
                updateStatus('Deploy error occurred');
            }
        }

        // Start instance
        async function startInstance() {
            if (!currentProcessId) {
                alert('Please select a process to start');
                return;
            }

            await startInstanceWithVariables('{}');
        }

        // Start instance with variables
        async function startInstanceWithVariables(variablesOverride = null) {
            if (!currentProcessId) {
                alert('Please select a process to start');
                return;
            }

            try {
                const variables = variablesOverride || document.getElementById('process-variables').value;
                
                // Validate JSON
                JSON.parse(variables);
                
                updateStatus('Starting process instance...');
                
                const formData = new FormData();
                formData.append('action', 'start_instance');
                formData.append('process_id', currentProcessId);
                formData.append('variables', variables);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Process instance started successfully!\nInstance Key: ${result.instance_key}`);
                    updateStatus('Process instance started');
                    refreshData();
                } else {
                    alert('Failed to start instance: ' + result.message);
                    updateStatus('Instance start failed');
                }
                
            } catch (error) {
                if (error instanceof SyntaxError) {
                    alert('Invalid JSON in process variables. Please check the format.');
                } else {
                    console.error('Start instance error:', error);
                    alert('Failed to start process instance');
                    updateStatus('Instance start error');
                }
            }
        }

        // Start instance from deployment
        function startInstanceFromDeployment(processModelId) {
            currentProcessId = processModelId;
            startInstance();
        }

        // Show instance details
        async function showInstanceDetails(instanceId) {
            try {
                updateStatus('Loading instance details...');
                
                const formData = new FormData();
                formData.append('action', 'get_instance_status');
                formData.append('instance_id', instanceId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const instance = result.instance;
                    const content = `
                        <p><strong>Instance Key:</strong> <code>${escapeHtml(instance.instance_key)}</code></p>
                        <p><strong>Flowable Instance ID:</strong> <code>${escapeHtml(instance.flowable_instance_id || 'N/A')}</code></p>
                        <p><strong>Status:</strong> <span class="status-badge status-${instance.status}">${instance.status}</span></p>
                        <p><strong>Started:</strong> ${new Date(instance.start_time).toLocaleString()}</p>
                        ${instance.end_time ? `<p><strong>Ended:</strong> ${new Date(instance.end_time).toLocaleString()}</p>` : ''}
                        <p><strong>Variables:</strong></p>
                        <textarea readonly style="width: 100%; height: 120px; font-family: monospace; font-size: 12px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px;">${JSON.stringify(JSON.parse(instance.variables || '{}'), null, 2)}</textarea>
                    `;
                    
                    document.getElementById('instance-details-content').innerHTML = content;
                    document.getElementById('instanceModal').style.display = 'block';
                    updateStatus('Instance details loaded');
                } else {
                    alert('Failed to load instance details: ' + result.message);
                    updateStatus('Failed to load instance details');
                }
                
            } catch (error) {
                console.error('Show instance details error:', error);
                alert('Failed to load instance details');
                updateStatus('Instance details error');
            }
        }

        // Hide instance modal
        function hideInstanceModal() {
            document.getElementById('instanceModal').style.display = 'none';
        }

        // Terminate instance
        async function terminateInstance(instanceId) {
            const confirmed = confirm('Are you sure you want to terminate this process instance?');
            if (!confirmed) return;

            try {
                updateStatus('Terminating instance...');
                
                const formData = new FormData();
                formData.append('action', 'terminate_instance');
                formData.append('instance_id', instanceId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Process instance terminated successfully');
                    updateStatus('Instance terminated');
                    refreshData();
                } else {
                    alert('Failed to terminate instance: ' + result.message);
                    updateStatus('Termination failed');
                }
                
            } catch (error) {
                console.error('Terminate instance error:', error);
                alert('Failed to terminate process instance');
                updateStatus('Termination error');
            }
        }

        // Refresh instances table
        function refreshInstances() {
            location.reload();
        }

        // Refresh all data
        function refreshData() {
            updateStatus('Refreshing data...');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Update button states
        function updateButtonStates() {
            const hasProject = currentProjectId !== null;
            const hasProcess = currentProcessId !== null;
            
            document.getElementById('btn-deploy-process').disabled = !hasProcess;
            document.getElementById('btn-start-instance').disabled = !hasProcess;
            document.getElementById('btn-start-with-vars').disabled = !hasProcess;
        }

        // Update status
        function updateStatus(message) {
            const statusEl = document.getElementById('status-text');
            if (statusEl) {
                statusEl.textContent = message;
            }
            console.log('Status:', message);
        }

        // Utility function to escape HTML
        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('instanceModal');
            if (event.target === modal) {
                hideInstanceModal();
            }
        });

        // Add some CSS for smaller buttons
        const style = document.createElement('style');
        style.textContent = `
            .btn-sm {
                padding: 6px 10px !important;
                font-size: 12px !important;
                margin-right: 5px;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>