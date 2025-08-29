<?php
// modules/M/resource_allocation.php - Task Resource Allocation

// Initialize variables
$processes = array();
$db_error = '';
$pdo = null;

// Database connection
try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get all processes from database
        $stmt = $pdo->prepare("
            SELECT pm.*, p.name as project_name 
            FROM process_models pm 
            LEFT JOIN projects p ON pm.project_id = p.id 
            ORDER BY pm.updated_at DESC
        ");
        $stmt->execute();
        $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create resource allocation table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS resource_allocations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                process_id INT NOT NULL,
                task_id VARCHAR(255) NOT NULL,
                allocation_name VARCHAR(255),
                resource_type ENUM('human', 'machine', 'both') DEFAULT 'human',
                cost DECIMAL(10,2) DEFAULT 0,
                processing_time INT DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_task (process_id, task_id)
            ) ENGINE=InnoDB
        ");
        
    } else {
        $db_error = 'Database configuration not found.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error) || !$pdo) {
        echo json_encode(array('success' => false, 'message' => 'Database error'));
        exit;
    }
    
    switch ($_POST['action']) {
        case 'save_allocation':
            try {
                $process_id = isset($_POST['process_id']) ? (int)$_POST['process_id'] : 0;
                $task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
                $allocation_name = isset($_POST['allocation_name']) ? $_POST['allocation_name'] : 'Default Allocation';
                $resource_type = isset($_POST['resource_type']) ? $_POST['resource_type'] : 'human';
                $cost = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
                $processing_time = isset($_POST['processing_time']) ? (int)$_POST['processing_time'] : 0;
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                
                $stmt = $pdo->prepare("
                    INSERT INTO resource_allocations 
                    (process_id, task_id, allocation_name, resource_type, cost, processing_time, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    allocation_name = VALUES(allocation_name),
                    resource_type = VALUES(resource_type),
                    cost = VALUES(cost),
                    processing_time = VALUES(processing_time),
                    notes = VALUES(notes)
                ");
                $stmt->execute(array($process_id, $task_id, $allocation_name, $resource_type, $cost, $processing_time, $notes));
                
                echo json_encode(array('success' => true));
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
            
        case 'load_allocations':
            try {
                $process_id = isset($_POST['process_id']) ? (int)$_POST['process_id'] : 0;
                
                $stmt = $pdo->prepare("
                    SELECT * FROM resource_allocations 
                    WHERE process_id = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->execute(array($process_id));
                $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(array('success' => true, 'allocations' => $allocations));
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
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
    <title>MACTA Framework - Resource Allocation</title>
    
    <style>
        :root {
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
            max-width: 1200px;
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

        .main-content {
            padding: 30px;
        }

        .process-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .process-selector h2 {
            margin-bottom: 15px;
            color: var(--htt-blue);
        }

        .process-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .viewer-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
            height: 600px;
        }

        .process-viewer {
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #fafafa;
            position: relative;
            overflow: auto;
        }

        .resource-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .resource-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--macta-light);
        }

        .resource-form h3 {
            margin-bottom: 15px;
            color: var(--htt-blue);
            font-size: 18px;
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
            padding: 10px;
            border: 2px solid var(--macta-light);
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--htt-blue);
            outline: none;
            box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
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

        .btn-primary {
            background: var(--htt-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--htt-dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30,136,229,0.3);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .allocations-list {
            max-height: 250px;
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
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            font-size: 12px;
            color: #666;
        }

        .task-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .task-item {
            background: white;
            border: 1px solid var(--macta-light);
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .task-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: var(--htt-blue);
        }

        .task-item.selected {
            border-color: var(--macta-green);
            background: #e8f5e8;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 150px;
            font-size: 14px;
            color: var(--macta-orange);
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

        @media (max-width: 1000px) {
            .viewer-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .process-viewer {
                height: 400px;
                margin-bottom: 20px;
            }
            
            .resource-panel {
                flex-direction: row;
                overflow-x: auto;
            }
            
            .resource-form {
                min-width: 350px;
            }
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <div class="macta-logo">M</div>
                MACTA Framework - Resource Allocation
            </h1>
            <div>
                <a href="process_timer.php" class="btn btn-primary" style="margin-right: 10px;">Process Timer</a>
                <a href="../../index.php" class="btn btn-primary">Back to Framework</a>
            </div>
        </div>

        <div class="main-content">
            <div class="process-selector">
                <h2>Process Selection</h2>
                <select id="process-select">
                    <option value="">Select a Process...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?php echo $process['id']; ?>" data-xml="<?php echo htmlspecialchars($process['model_data']); ?>">
                            <?php echo htmlspecialchars($process['name']); ?>
                            <?php if ($process['project_name']): ?>
                                (<?php echo htmlspecialchars($process['project_name']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="viewer-container">
                <div id="process-viewer" class="process-viewer">
                    <div class="loading">Select a process to view it here...</div>
                </div>

                <div class="resource-panel">
                    <!-- Resource Assignment Form -->
                    <div class="resource-form">
                        <h3>Assign Resources</h3>
                        
                        <div class="form-group">
                            <label>Selected Task:</label>
                            <input type="text" id="selected-task" readonly placeholder="Click on a task in the diagram">
                        </div>
                        
                        <div class="form-group">
                            <label>Allocation Name:</label>
                            <input type="text" id="allocation-name" placeholder="e.g., Senior Analyst Assignment">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Resource Type:</label>
                                <select id="resource-type">
                                    <option value="human">Human Resource</option>
                                    <option value="machine">Machine/Equipment</option>
                                    <option value="both">Human + Machine</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cost ($):</label>
                                <input type="number" id="cost" value="50" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Processing Time (minutes):</label>
                            <input type="number" id="processing-time" value="30" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label>Notes:</label>
                            <textarea id="notes" rows="3" placeholder="Additional notes about this resource allocation..."></textarea>
                        </div>
                        
                        <button id="btn-save" class="btn btn-success" disabled onclick="saveAllocation()">
                            Save Resource Allocation
                        </button>
                        
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">
                            <strong>Available Tasks:</strong>
                            <div class="task-list" id="all-tasks-list">
                                <div class="loading" style="height: 60px; font-size: 11px;">Select a process to view tasks</div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Allocations -->
                    <div class="resource-form">
                        <h3>Current Allocations</h3>
                        <div class="allocations-list" id="allocations-list">
                            <div class="loading" style="height: 100px; font-size: 12px;">Select a process to view allocations</div>
                        </div>
                        <button class="btn btn-primary" style="width: 100%; margin-top: 10px;" onclick="refreshAllocations()">
                            Refresh Allocations
                        </button>
                    </div>
                </div>
            </div>

            <div class="status-bar">
                <span id="status-message">
                    <?php if (!empty($db_error)): ?>
                        Database Error: <?php echo htmlspecialchars($db_error); ?>
                    <?php elseif (count($processes) > 0): ?>
                        Found <?php echo count($processes); ?> processes. Select a process and click on tasks to assign resources.
                    <?php else: ?>
                        No processes found. Please create a process first using the modeling module.
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js"></script>
    <script>
        let processViewer = null;
        let currentProcessId = null;
        let currentTaskId = null;

        // Initialize BPMN Viewer
        function initializeBPMNViewer() {
            try {
                processViewer = new BpmnJS({
                    container: '#process-viewer'
                });
                console.log('BPMN Viewer initialized');
            } catch (error) {
                console.error('Failed to initialize BPMN Viewer:', error);
            }
        }

        // Load process
        async function loadProcess(processId, xml) {
            if (!processViewer || !xml) return;
            
            try {
                await processViewer.importXML(xml);
                processViewer.get('canvas').zoom('fit-viewport');
                
                currentProcessId = processId;
                
                document.querySelector('#process-viewer .loading').style.display = 'none';
                
                addTaskClickHandlers();
                loadAllTasks();
                loadAllocations();
                
            } catch (error) {
                console.error('Failed to load process:', error);
            }
        }

        // Add click handlers
        function addTaskClickHandlers() {
            if (!processViewer) return;
            
            try {
                const eventBus = processViewer.get('eventBus');
                
                eventBus.on('element.click', function(event) {
                    const element = event.element;
                    
                    if (element.type === 'bpmn:Task' || 
                        element.type === 'bpmn:UserTask' ||
                        element.type === 'bpmn:ServiceTask' ||
                        element.type === 'bpmn:StartEvent' ||
                        element.type === 'bpmn:EndEvent' ||
                        element.type === 'bpmn:ExclusiveGateway' ||
                        element.type === 'bpmn:ParallelGateway') {
                        
                        selectTask(element.id, element.businessObject.name || element.id, element.type);
                    }
                });
                
            } catch (error) {
                console.error('Failed to add task click handlers:', error);
            }
        }

        // Load all tasks
        function loadAllTasks() {
            if (!processViewer) return;
            
            try {
                const elementRegistry = processViewer.get('elementRegistry');
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
                
                const container = document.getElementById('all-tasks-list');
                
                if (tasks.length === 0) {
                    container.innerHTML = '<div class="loading" style="height: 40px; font-size: 11px;">No tasks found</div>';
                } else {
                    let html = '';
                    tasks.forEach(task => {
                        const name = task.businessObject.name || task.id;
                        const type = task.type.replace('bpmn:', '');
                        const icon = getTaskIcon(type);
                        
                        html += '<div class="task-item" onclick="selectTask(\'' + task.id + '\', \'' + name + '\', \'' + task.type + '\')">' +
                               '<div><strong>' + icon + ' ' + name + '</strong></div>' +
                               '<div style="font-size: 10px; color: #999;">' + type + ' | ID: ' + task.id + '</div>' +
                               '</div>';
                    });
                    container.innerHTML = html;
                }
                
            } catch (error) {
                console.error('Failed to load tasks:', error);
            }
        }

        // Get task icon
        function getTaskIcon(type) {
            const icons = {
                'StartEvent': '🟢',
                'EndEvent': '🔴',
                'ExclusiveGateway': '💎',
                'ParallelGateway': '➕',
                'UserTask': '👤',
                'ServiceTask': '🔧',
                'Task': '📋'
            };
            return icons[type] || '📋';
        }

        // Select task
        function selectTask(taskId, taskName, taskType) {
            currentTaskId = taskId;
            
            document.getElementById('selected-task').value = taskName + ' (' + taskType.replace('bpmn:', '') + ')';
            document.getElementById('allocation-name').value = taskName + ' Assignment';
            document.getElementById('btn-save').disabled = false;
            
            // Update task item selection
            document.querySelectorAll('.task-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.target.closest('.task-item').classList.add('selected');
            
            updateStatus('Task selected: ' + taskName);
        }

        // Save allocation
        async function saveAllocation() {
            if (!currentProcessId || !currentTaskId) return;
            
            const allocationName = document.getElementById('allocation-name').value;
            const resourceType = document.getElementById('resource-type').value;
            const cost = document.getElementById('cost').value;
            const processingTime = document.getElementById('processing-time').value;
            const notes = document.getElementById('notes').value;
            
            if (!allocationName.trim()) {
                alert('Please enter an allocation name.');
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=save_allocation' + 
                          '&process_id=' + currentProcessId +
                          '&task_id=' + encodeURIComponent(currentTaskId) +
                          '&allocation_name=' + encodeURIComponent(allocationName) +
                          '&resource_type=' + resourceType +
                          '&cost=' + cost +
                          '&processing_time=' + processingTime +
                          '&notes=' + encodeURIComponent(notes)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateStatus('Resource allocation saved successfully');
                    loadAllocations();
                    
                    // Clear form
                    document.getElementById('selected-task').value = '';
                    document.getElementById('allocation-name').value = '';
                    document.getElementById('notes').value = '';
                    document.getElementById('btn-save').disabled = true;
                    currentTaskId = null;
                    
                    // Clear task selection
                    document.querySelectorAll('.task-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                } else {
                    alert('Failed to save allocation: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Save allocation error:', error);
                alert('Error saving allocation');
            }
        }

        // Load allocations
        async function loadAllocations() {
            if (!currentProcessId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=load_allocations&process_id=' + currentProcessId
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayAllocations(result.allocations);
                }
            } catch (error) {
                console.error('Load allocations error:', error);
            }
        }

        // Display allocations
        function displayAllocations(allocations) {
            const container = document.getElementById('allocations-list');
            
            if (allocations.length === 0) {
                container.innerHTML = '<div class="loading" style="height: 60px; font-size: 12px;">No allocations found</div>';
                return;
            }
            
            let html = '';
            allocations.forEach(allocation => {
                const resourceIcon = allocation.resource_type === 'human' ? '👤' : 
                                   allocation.resource_type === 'machine' ? '🤖' : '⚡';
                
                html += '<div class="allocation-item">' +
                        '<div class="allocation-header">' +
                        '<strong>' + resourceIcon + ' ' + allocation.allocation_name + '</strong>' +
                        '<span style="color: #666; font-size: 11px;">' + allocation.task_id + '</span>' +
                        '</div>' +
                        '<div class="allocation-details">' +
                        '<div><strong>Type:</strong> ' + allocation.resource_type + '</div>' +
                        '<div><strong>Cost:</strong>  + allocation.cost + '</div>' +
                        '<div><strong>Time:</strong> ' + allocation.processing_time + ' min</div>' +
                        '<div><strong>Created:</strong> ' + new Date(allocation.created_at).toLocaleDateString() + '</div>' +
                        '</div>';
                
                if (allocation.notes) {
                    html += '<div style="margin-top: 8px; font-style: italic; color: #666; font-size: 12px;">' + allocation.notes + '</div>';
                }
                
                html += '</div>';
            });
            
            container.innerHTML = html;
        }

        // Refresh allocations
        function refreshAllocations() {
            if (currentProcessId) {
                loadAllocations();
                updateStatus('Allocations refreshed');
            } else {
                updateStatus('Please select a process first');
            }
        }

        function updateStatus(message) {
            document.getElementById('status-message').textContent = message;
        }

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeBPMNViewer();
            
            document.getElementById('process-select').addEventListener('change', function(e) {
                const selectedValue = e.target.value;
                
                if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData) {
                        loadProcess(selectedValue, xmlData);
                    }
                }
            });
        });
    </script>
</body>
</html>