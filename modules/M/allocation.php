<?php
// modules/M/allocation.php - Resource Allocation Management

// Initialize variables
$processes = [];
$resource_allocations = [];
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
    error_log("Resource Allocation DB Error: " . $e->getMessage());
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

        case 'delete_resource_allocation':
            try {
                $stmt = $pdo->prepare("DELETE FROM resource_allocations WHERE id = ? AND created_by = ?");
                $stmt->execute([$_POST['allocation_id'] ?? 0, $_SESSION['user_id'] ?? 1]);
                echo json_encode(['success' => true, 'message' => 'Resource allocation deleted successfully']);
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
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get URL parameters for pre-filling form
$preselected_task = $_GET['task'] ?? '';
$preselected_name = $_GET['name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - Resource Allocation Management</title>

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

        .allocations-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .allocation-item {
            background: white;
            border: 1px solid var(--macta-light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
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

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--macta-red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .resource-templates {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid var(--macta-green);
        }

        .template-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            border: 1px solid var(--macta-light);
            transition: all 0.3s ease;
        }

        .template-item:hover {
            border-color: var(--macta-green);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .cost-calculator {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid var(--macta-yellow);
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <div class="macta-logo">M</div>
                MACTA Framework - Resource Allocation Management
            </h1>
            <div style="display: flex; gap: 10px;">
                <a href="assignment.php" class="btn btn-primary">
                    <span>⏱️</span> Assignment & Timer
                </a>
                <a href="../../index.php" class="btn btn-primary">
                    <span>←</span> Back to Framework
                </a>
            </div>
        </div>

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
                        <input type="text" id="task-id" placeholder="e.g., Task_1, StartEvent_1" value="<?= htmlspecialchars($preselected_task) ?>">
                    </div>

                    <div class="form-group">
                        <label>Allocation Name:</label>
                        <input type="text" id="allocation-name" placeholder="e.g., Senior Analyst Assignment" 
                               value="<?= $preselected_name ? htmlspecialchars($preselected_name) . ' Assignment' : '' ?>">
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

                    <div class="toolbar">
                        <button class="btn btn-success" id="btn-save-allocation">
                            ✅ Save Resource Allocation
                        </button>
                        <button class="btn btn-warning" id="btn-apply-all">
                            ⭐ Apply to All Tasks
                        </button>
                        <button class="btn btn-primary" id="btn-load-averages">
                            📊 Load Timer Averages
                        </button>
                    </div>

                    <div class="cost-calculator">
                        <h4>💰 Cost Calculator</h4>
                        <div id="cost-calculation">
                            <strong>Total Cost:</strong> <span id="total-cost">$0.00</span><br>
                            <strong>Hourly Rate:</strong> <span id="hourly-rate">$0.00/hour</span><br>
                            <strong>Daily Rate:</strong> <span id="daily-rate">$0.00/day</span>
                        </div>
                    </div>
                </div>

                <div class="resource-form">
                    <h3>📊 Current Allocations</h3>
                    <div id="allocations-list" class="allocations-list">
                        <div class="loading">Select a process to view its resource allocations</div>
                    </div>

                    <div class="resource-templates">
                        <h4>🎯 Quick Templates</h4>
                        <div class="template-item" onclick="applyTemplate('analyst', 75, 45, 'human')">
                            <strong>👤 Senior Analyst</strong> - $75, 45min
                        </div>
                        <div class="template-item" onclick="applyTemplate('developer', 90, 60, 'human')">
                            <strong>💻 Senior Developer</strong> - $90, 60min
                        </div>
                        <div class="template-item" onclick="applyTemplate('manager', 120, 30, 'human')">
                            <strong>👔 Project Manager</strong> - $120, 30min
                        </div>
                        <div class="template-item" onclick="applyTemplate('automation', 200, 5, 'machine')">
                            <strong>🤖 Automated Process</strong> - $200, 5min
                        </div>
                    </div>
                </div>
            </div>

            <div class="status-bar">
                <span>🎯</span> Manage resource allocations for process tasks and calculate associated costs.
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

    <script>
        // Global variables
        let currentProcessId = null;
        
        // PHP data for JavaScript
        const processes = <?= json_encode($processes) ?>;
        const dbError = <?= json_encode($db_error) ?>;

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
                    
                    // Reset to default values
                    document.getElementById('resource-type').value = 'human';
                    document.getElementById('cost').value = '50';
                    document.getElementById('processing-time').value = '30';
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
            let totalCost = 0;
            let totalTime = 0;
            
            allocations.forEach(allocation => {
                const resourceIcon = allocation.resource_type === 'human' ? '👤' : 
                                   allocation.resource_type === 'machine' ? '🤖' : '⚡';
                
                totalCost += parseFloat(allocation.cost);
                totalTime += parseInt(allocation.processing_time);
                
                html += `
                    <div class="allocation-item">
                        <button class="delete-btn" onclick="deleteAllocation(${allocation.id})" title="Delete allocation">×</button>
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
            
            // Add summary
            html += `
                <div class="allocation-item" style="background: #e8f5e8; border-color: var(--macta-green);">
                    <div class="allocation-header">
                        <strong>📊 Process Summary</strong>
                        <span style="color: #666;">${allocations.length} allocations</span>
                    </div>
                    <div class="allocation-details">
                        <div><strong>Total Cost:</strong> ${totalCost.toFixed(2)}</div>
                        <div><strong>Total Time:</strong> ${totalTime} min</div>
                        <div><strong>Avg Cost/Task:</strong> ${(totalCost / allocations.length).toFixed(2)}</div>
                        <div><strong>Avg Time/Task:</strong> ${Math.round(totalTime / allocations.length)} min</div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        }

        async function deleteAllocation(allocationId) {
            if (!confirm('Are you sure you want to delete this resource allocation?')) {
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_resource_allocation&allocation_id=${allocationId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Resource allocation deleted successfully!');
                    if (currentProcessId) {
                        loadResourceAllocations(currentProcessId);
                    }
                } else {
                    alert('❌ Failed to delete allocation: ' + result.message);
                }
            } catch (error) {
                console.error('Delete allocation error:', error);
                alert('❌ Error deleting allocation');
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
                
                if (result.success && result.averages.length > 0) {
                    // Show averages in a modal or alert
                    let message = 'Timer Averages for this Process:\n\n';
                    result.averages.forEach(avg => {
                        const minutes = Math.floor(avg.average_duration / 60);
                        const seconds = avg.average_duration % 60;
                        message += `${avg.task_id}: ${minutes}m ${seconds}s (${avg.session_count} sessions)\n`;
                    });
                    alert(message);
                } else {
                    alert('No timer averages found for this process. Start timing tasks to build historical data.');
                }
            } catch (error) {
                console.error('Load timer averages error:', error);
                alert('❌ Error loading timer averages');
            }
        }

        // Load average processing time for a specific task
        async function loadAverageForTask(processId, taskId) {
            if (!processId || !taskId) return;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_timer_averages&process_id=${processId}`
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
                        
                        // Update cost calculation
                        updateCostCalculation();
                    }
                }
            } catch (error) {
                console.error('Failed to load average for task:', error);
            }
        }

        // Apply quick template
        function applyTemplate(templateType, cost, time, resourceType) {
            document.getElementById('cost').value = cost;
            document.getElementById('processing-time').value = time;
            document.getElementById('resource-type').value = resourceType;
            
            // Update allocation name if task is selected
            const taskId = document.getElementById('task-id').value;
            if (taskId) {
                const templateNames = {
                    'analyst': 'Senior Analyst Assignment',
                    'developer': 'Senior Developer Assignment',
                    'manager': 'Project Manager Assignment',
                    'automation': 'Automated Process Assignment'
                };
                document.getElementById('allocation-name').value = templateNames[templateType] || 'Resource Assignment';
            }
            
            // Update cost calculation
            updateCostCalculation();
            
            // Visual feedback
            const templates = document.querySelectorAll('.template-item');
            templates.forEach(t => t.style.backgroundColor = '');
            event.target.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                event.target.style.backgroundColor = '';
            }, 2000);
        }

        // Update cost calculation
        function updateCostCalculation() {
            const cost = parseFloat(document.getElementById('cost').value) || 0;
            const time = parseInt(document.getElementById('processing-time').value) || 0;
            
            const totalCost = cost;
            const hourlyRate = time > 0 ? (cost / (time / 60)) : 0;
            const dailyRate = hourlyRate * 8;
            
            document.getElementById('total-cost').textContent = `${totalCost.toFixed(2)}`;
            document.getElementById('hourly-rate').textContent = `${hourlyRate.toFixed(2)}/hour`;
            document.getElementById('daily-rate').textContent = `${dailyRate.toFixed(2)}/day`;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 MACTA Resource Allocation initialized');
            
            // Process selection
            document.getElementById('allocation-process-select').addEventListener('change', (e) => {
                const processId = e.target.value;
                currentProcessId = processId;
                if (processId) {
                    loadResourceAllocations(processId);
                }
            });
            
            // Task selection
            document.getElementById('task-dropdown').addEventListener('change', (e) => {
                const selectedTaskId = e.target.value;
                if (selectedTaskId) {
                    document.getElementById('task-id').value = selectedTaskId;
                    
                    // Auto-fill allocation name
                    const selectedOption = e.target.selectedOptions[0];
                    const taskName = selectedOption.textContent.split(' (')[0].replace(/^[🟢🔴💎➕📋👤] /, '');
                    document.getElementById('allocation-name').value = `${taskName} Assignment`;
                    
                    // Load average time if available and process is selected
                    if (currentProcessId) {
                        loadAverageForTask(currentProcessId, selectedTaskId);
                    }
                }
            });
            
            // Task ID input change
            document.getElementById('task-id').addEventListener('change', (e) => {
                if (currentProcessId && e.target.value) {
                    loadAverageForTask(currentProcessId, e.target.value);
                }
            });
            
            // Cost and time inputs
            document.getElementById('cost').addEventListener('input', updateCostCalculation);
            document.getElementById('processing-time').addEventListener('input', updateCostCalculation);
            
            // Button events
            document.getElementById('btn-save-allocation').addEventListener('click', saveResourceAllocation);
            
            document.getElementById('btn-apply-all').addEventListener('click', () => {
                if (confirm('Apply current resource settings to all tasks in the selected process?')) {
                    alert('🚧 Apply to all functionality will be implemented');
                }
            });
            
            document.getElementById('btn-load-averages').addEventListener('click', () => {
                if (currentProcessId) {
                    loadTimerAverages(currentProcessId);
                } else {
                    alert('Please select a process first.');
                }
            });
            
            // Pre-fill form if URL parameters are present
            <?php if ($preselected_task): ?>
            document.getElementById('task-id').value = '<?= htmlspecialchars($preselected_task) ?>';
            <?php endif; ?>
            
            <?php if ($preselected_name): ?>
            document.getElementById('allocation-name').value = '<?= htmlspecialchars($preselected_name) ?> Assignment';
            <?php endif; ?>
            
            // Initial cost calculation
            updateCostCalculation();
            
            console.log('✅ All event listeners attached successfully!');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        saveResourceAllocation();
                        break;
                }
            }
        });
    </script>
</body>
</html>