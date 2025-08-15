<?php
// modules/M/visual_builder.php - Visual Process Builder (Final Fixed Version)
session_start();

// Check if config exists
if (!file_exists('../../config/config.php')) {
    header('Location: ../../install.php');
    exit;
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../shared/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Ensure we have a default project
function ensureDefaultProject($conn) {
    try {
        // Check if default project exists
        $stmt = $conn->prepare("SELECT id FROM projects WHERE id = 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            // Create default project
            $stmt = $conn->prepare("INSERT INTO projects (id, name, description, client_id, status, created_at, updated_at) VALUES (1, 'Default MACTA Project', 'Default project for MACTA framework processes', 1, 'active', NOW(), NOW())");
            $stmt->execute();
        }
        return 1;
    } catch (Exception $e) {
        error_log("Error ensuring default project: " . $e->getMessage());
        return null;
    }
}

// Handle process saving/loading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Set JSON content type for all responses
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'save_process':
                // Validate required fields
                if (empty($_POST['process_name'])) {
                    throw new Exception('Process name is required');
                }
                
                if (empty($_POST['bpmn_xml'])) {
                    throw new Exception('BPMN XML data is required');
                }
                
                // Ensure default project exists
                $projectId = ensureDefaultProject($conn);
                if (!$projectId) {
                    throw new Exception('Could not create or find default project');
                }
                
                // Basic XML validation
                $xml = $_POST['bpmn_xml'];
                if (strpos($xml, '<bpmn') === false && strpos($xml, '<?xml') === false) {
                    throw new Exception('Invalid BPMN XML format');
                }
                
                // Insert new process
                $stmt = $conn->prepare("
                    INSERT INTO process_models 
                    (project_id, name, description, model_data, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                
                $result = $stmt->execute([
                    $projectId,
                    trim($_POST['process_name']),
                    trim($_POST['process_description'] ?? ''),
                    $xml
                ]);
                
                if ($result) {
                    $response = [
                        'success' => true, 
                        'message' => 'Process saved successfully!', 
                        'id' => $conn->lastInsertId()
                    ];
                } else {
                    throw new Exception('Failed to save process to database');
                }
                break;
                
            case 'update_process':
                // Validate required fields
                if (empty($_POST['process_id'])) {
                    throw new Exception('Process ID is required for update');
                }
                
                if (empty($_POST['process_name'])) {
                    throw new Exception('Process name is required');
                }
                
                if (empty($_POST['bpmn_xml'])) {
                    throw new Exception('BPMN XML data is required');
                }
                
                // Basic XML validation
                $xml = $_POST['bpmn_xml'];
                if (strpos($xml, '<bpmn') === false && strpos($xml, '<?xml') === false) {
                    throw new Exception('Invalid BPMN XML format');
                }
                
                // Update existing process
                $stmt = $conn->prepare("
                    UPDATE process_models 
                    SET name = ?, description = ?, model_data = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    trim($_POST['process_name']),
                    trim($_POST['process_description'] ?? ''),
                    $xml,
                    $_POST['process_id']
                ]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Process updated successfully!'];
                } else {
                    throw new Exception('Failed to update process in database');
                }
                break;
                
            case 'load_process':
                if (empty($_POST['process_id'])) {
                    throw new Exception('Process ID is required');
                }
                
                $stmt = $conn->prepare("
                    SELECT p.*, pr.name as project_name 
                    FROM process_models p
                    LEFT JOIN projects pr ON p.project_id = pr.id
                    WHERE p.id = ?
                ");
                
                $stmt->execute([$_POST['process_id']]);
                $process = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($process) {
                    $response = ['success' => true, 'process' => $process];
                } else {
                    throw new Exception('Process not found');
                }
                break;
                
            case 'delete_process':
                if (empty($_POST['process_id'])) {
                    throw new Exception('Process ID is required');
                }
                
                $stmt = $conn->prepare("DELETE FROM process_models WHERE id = ?");
                $result = $stmt->execute([$_POST['process_id']]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Process deleted successfully!'];
                } else {
                    throw new Exception('Failed to delete process');
                }
                break;
                
            case 'get_process_list':
                $stmt = $conn->prepare("
                    SELECT p.id, p.name, p.description, p.created_at, p.updated_at,
                           pr.name as project_name,
                           CHAR_LENGTH(p.model_data) as xml_size
                    FROM process_models p
                    LEFT JOIN projects pr ON p.project_id = pr.id
                    ORDER BY COALESCE(p.updated_at, p.created_at) DESC
                ");
                
                $stmt->execute();
                $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = ['success' => true, 'processes' => $processes];
                break;
                
            default:
                throw new Exception('Invalid action specified');
        }
        
    } catch (PDOException $e) {
        error_log("Database error in visual_builder.php: " . $e->getMessage());
        $response = [
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Error in visual_builder.php: " . $e->getMessage());
        $response = [
            'success' => false, 
            'message' => $e->getMessage()
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Get existing processes for the dropdown with error handling
$existing_processes = [];
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.description, p.created_at, p.updated_at,
               pr.name as project_name
        FROM process_models p
        LEFT JOIN projects pr ON p.project_id = pr.id
        ORDER BY COALESCE(p.updated_at, p.created_at) DESC
    ");
    $stmt->execute();
    $existing_processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading processes list: " . $e->getMessage());
    // Continue with empty array - page will still work
}

// Get available projects for the dropdown
$available_projects = [];
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.status, u.username as client_name
        FROM projects p
        LEFT JOIN users u ON p.client_id = u.id
        WHERE p.status IN ('active', 'draft')
        ORDER BY p.name
    ");
    $stmt->execute();
    $available_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading projects list: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Process Builder - MACTA Framework</title>
    
    <!-- BPMN.js Styles -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.7.1/dist/assets/diagram-js.css">
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.7.1/dist/assets/bpmn-font/css/bpmn-embedded.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            position: relative;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
        }

        .breadcrumb {
            opacity: 0.9;
            margin-top: 3px;
            font-size: 14px;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .toolbar {
            background: white;
            padding: 12px 20px;
            border-bottom: 1px solid #e1e5e9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            z-index: 999;
            position: relative;
        }

        .toolbar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            background: #ff5722;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .input-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group input, .input-group select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .main-container {
            display: flex;
            height: calc(100vh - 120px);
        }

        .bpmn-container {
            flex: 1;
            position: relative;
            background: white;
        }

        #canvas {
            height: 100%;
            width: 100%;
        }

        .properties-panel {
            width: 300px;
            background: white;
            border-left: 1px solid #e1e5e9;
            overflow-y: auto;
            padding: 20px;
        }

        .properties-panel h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .element-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .element-info h4 {
            margin-bottom: 8px;
            color: #333;
        }

        .element-info p {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 8% auto;
            padding: 25px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
        }

        .modal h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 2001;
            display: none;
            max-width: 300px;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.info {
            background: #17a2b8;
        }

        .stats-bar {
            background: #e9ecef;
            padding: 8px 20px;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #ddd;
        }

        /* BPMN.js customizations */
        .djs-palette {
            background: white;
            border: 1px solid #ccc;
        }

        .djs-context-pad {
            background: white;
        }

        .bpmn-icon-start-event-none:before,
        .bpmn-icon-intermediate-event-none:before,
        .bpmn-icon-end-event-none:before {
            color: #ff6b35;
        }

        .bpmn-icon-task:before {
            color: #007bff;
        }

        .bpmn-icon-gateway-none:before {
            color: #ffc107;
        }

        @media (max-width: 1024px) {
            .properties-panel {
                width: 250px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .properties-panel {
                width: 100%;
                height: 200px;
                border-left: none;
                border-top: 1px solid #e1e5e9;
            }
            
            .bpmn-container {
                height: calc(100vh - 320px);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📊 Visual Process Builder</h1>
                <div class="breadcrumb">
                    <a href="../../index.php">MACTA Framework</a> > 
                    <a href="index.php">Modeling</a> > 
                    Visual Process Builder
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">← Back to Modeling</a>
        </div>
    </div>

    <div class="toolbar">
        <div class="toolbar-content">
            <button class="btn" onclick="newProcess()">
                🆕 New Process
            </button>
            
            <div class="input-group">
                <select id="loadProcessSelect">
                    <option value="">Load Existing Process...</option>
                    <?php foreach ($existing_processes as $process): ?>
                        <option value="<?php echo $process['id']; ?>">
                            <?php echo htmlspecialchars($process['name']); ?> 
                            <?php if ($process['project_name']): ?>
                                (<?php echo htmlspecialchars($process['project_name']); ?>)
                            <?php endif; ?>
                            - <?php echo date('M d, Y', strtotime($process['created_at'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" onclick="loadProcess()">Load</button>
            </div>

            <button class="btn btn-success" onclick="saveProcess()">
                💾 Save Process
            </button>

            <button class="btn btn-warning" onclick="exportSVG()">
                📤 Export SVG
            </button>

            <button class="btn btn-secondary" onclick="exportBPMN()">
                📋 Export BPMN
            </button>

            <button class="btn btn-secondary" onclick="validateProcess()">
                ✅ Validate
            </button>

            <div class="input-group" style="margin-left: auto;">
                <button class="btn btn-secondary" onclick="zoomIn()">🔍+</button>
                <button class="btn btn-secondary" onclick="zoomOut()">🔍-</button>
                <button class="btn btn-secondary" onclick="zoomToFit()">⚡ Fit</button>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="bpmn-container">
            <div id="canvas"></div>
        </div>

        <div class="properties-panel">
            <h3>Properties</h3>
            <div id="propertiesContent">
                <div class="element-info">
                    <h4>Welcome to BPMN Process Builder</h4>
                    <p>Click on any element in the diagram to edit its properties, or start by creating a new process.</p>
                </div>
                
                <div class="form-group">
                    <label>Quick Actions:</label>
                    <button class="btn" onclick="addStartEvent()" style="width: 100%; margin-bottom: 8px;">
                        Add Start Event
                    </button>
                    <button class="btn" onclick="addTask()" style="width: 100%; margin-bottom: 8px;">
                        Add Task
                    </button>
                    <button class="btn" onclick="addEndEvent()" style="width: 100%;">
                        Add End Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-bar">
        <span id="statsInfo">Ready - Click 'New Process' to start modeling</span>
    </div>

    <!-- Save Process Modal -->
    <div id="saveModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSaveModal()">&times;</span>
            <h3>Save Process Model</h3>
            
            <div class="form-group">
                <label for="processName">Process Name *</label>
                <input type="text" id="processName" placeholder="Enter process name..." required>
            </div>
            
            <div class="form-group">
                <label for="processDescription">Description</label>
                <textarea id="processDescription" placeholder="Describe this process..."></textarea>
            </div>
            
            <?php if (count($available_projects) > 1): ?>
            <div class="form-group">
                <label for="projectSelect">Project</label>
                <select id="projectSelect">
                    <?php foreach ($available_projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == 1 ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                            <?php if ($project['client_name']): ?>
                                (<?php echo htmlspecialchars($project['client_name']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="closeSaveModal()">Cancel</button>
                <button class="btn btn-success" onclick="confirmSave()">Save Process</button>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <!-- BPMN.js Scripts -->
    <script src="https://unpkg.com/bpmn-js@17.7.1/dist/bpmn-modeler.development.js"></script>
    
    <script>
        let bpmnModeler;
        let currentProcessId = null;
        let hasUnsavedChanges = false;

        // Initialize BPMN Modeler
        document.addEventListener('DOMContentLoaded', function() {
            initializeBPMNModeler();
        });

        function initializeBPMNModeler() {
            bpmnModeler = new BpmnJS({
                container: '#canvas',
                keyboard: {
                    bindTo: window
                }
            });

            // Event listeners
            bpmnModeler.on('commandStack.changed', function() {
                hasUnsavedChanges = true;
                updateStats();
            });

            bpmnModeler.on('selection.changed', function(e) {
                const element = e.newSelection[0];
                showElementProperties(element);
            });

            bpmnModeler.on('element.changed', function(e) {
                showElementProperties(e.element);
            });

            // Create new process by default
            newProcess();
        }

        
// Fix for the newProcess() function in visual_builder.php
// Replace the existing newProcess function with this corrected version

function newProcess() {
    if (hasUnsavedChanges && !confirm('You have unsaved changes. Continue?')) {
        return;
    }

    // Fixed: Use single quotes for the XML declaration
    const newBpmnXML = '<?xml version="1.0" encoding="UTF-8"?>' +
        '<bpmn2:definitions xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" id="sample-diagram" targetNamespace="http://bpmn.io/schema/bpmn">' +
        '  <bpmn2:process id="Process_1" isExecutable="false">' +
        '    <bpmn2:startEvent id="StartEvent_1" name="Start"/>' +
        '  </bpmn2:process>' +
        '  <bpmndi:BPMNDiagram id="BPMNDiagram_1">' +
        '    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">' +
        '      <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">' +
        '        <dc:Bounds x="173" y="102" width="36" height="36"/>' +
        '      </bpmndi:BPMNShape>' +
        '    </bpmndi:BPMNPlane>' +
        '  </bpmndi:BPMNDiagram>' +
        '</bpmn2:definitions>';

    bpmnModeler.importXML(newBpmnXML).then(() => {
        currentProcessId = null;
        hasUnsavedChanges = false;
        showNotification('New process created!', 'success');
        updateStats();
        clearProperties();
    }).catch((error) => {
        console.error('Error creating new process:', error);
        showNotification('Error creating new process!', 'error');
    });
}

        function saveProcess() {
            bpmnModeler.saveXML({ format: true }).then((result) => {
                document.getElementById('saveModal').style.display = 'block';
            }).catch((error) => {
                console.error('Error preparing save:', error);
                showNotification('Error preparing process for save!', 'error');
            });
        }

        function closeSaveModal() {
            document.getElementById('saveModal').style.display = 'none';
        }

        function confirmSave() {
            const name = document.getElementById('processName').value.trim();
            const description = document.getElementById('processDescription').value.trim();
            const projectSelect = document.getElementById('projectSelect');
            const projectId = projectSelect ? projectSelect.value : 1;
            
            if (!name) {
                showNotification('Please enter a process name!', 'error');
                return;
            }
            
            bpmnModeler.saveXML({ format: true }).then((result) => {
                const action = currentProcessId ? 'update_process' : 'save_process';
                const params = new URLSearchParams({
                    action: action,
                    process_name: name,
                    process_description: description,
                    bpmn_xml: result.xml,
                    project_id: projectId
                });

                if (currentProcessId) {
                    params.append('process_id', currentProcessId);
                }

                fetch('visual_builder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        hasUnsavedChanges = false;
                        if (!currentProcessId) {
                            currentProcessId = data.id;
                        }
                        showNotification(data.message, 'success');
                        closeSaveModal();
                        
                        // Refresh the load dropdown after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showNotification('Network error. Please try again.', 'error');
                });
            }).catch((error) => {
                showNotification('Error saving process!', 'error');
                console.error('Save error:', error);
            });
        }

        function loadProcess() {
            const selectElement = document.getElementById('loadProcessSelect');
            const processId = selectElement.value;
            
            if (!processId) {
                showNotification('Please select a process to load!', 'error');
                return;
            }
            
            if (hasUnsavedChanges && !confirm('You have unsaved changes. Continue?')) {
                return;
            }
            
            fetch('visual_builder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'load_process',
                    process_id: processId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loadProcessData(data.process);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Load error:', error);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        function loadProcessData(process) {
            bpmnModeler.importXML(process.model_data).then(() => {
                currentProcessId = process.id;
                hasUnsavedChanges = false;
                
                // Pre-fill modal fields
                document.getElementById('processName').value = process.name;
                document.getElementById('processDescription').value = process.description || '';
                
                showNotification(`Process "${process.name}" loaded successfully!`, 'success');
                updateStats();
                zoomToFit();
            }).catch((error) => {
                console.error('Load error:', error);
                showNotification('Error loading process data!', 'error');
            });
        }

        function exportSVG() {
            bpmnModeler.saveSVG().then((result) => {
                const blob = new Blob([result.svg], { type: 'image/svg+xml' });
                const url = URL.createObjectURL(blob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = 'process_diagram_' + new Date().getTime() + '.svg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showNotification('SVG exported successfully!', 'success');
            }).catch((error) => {
                showNotification('Error exporting SVG!', 'error');
                console.error('Export error:', error);
            });
        }

        function exportBPMN() {
            bpmnModeler.saveXML({ format: true }).then((result) => {
                const blob = new Blob([result.xml], { type: 'application/xml' });
                const url = URL.createObjectURL(blob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = 'process_model_' + new Date().getTime() + '.bpmn';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showNotification('BPMN file exported successfully!', 'success');
            }).catch((error) => {
                showNotification('Error exporting BPMN!', 'error');
                console.error('Export error:', error);
            });
        }

        function validateProcess() {
            try {
                const elementRegistry = bpmnModeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const startEvents = elements.filter(e => e.type === 'bpmn:StartEvent');
                const endEvents = elements.filter(e => e.type === 'bpmn:EndEvent');
                const tasks = elements.filter(e => e.type.includes('Task'));
                const gateways = elements.filter(e => e.type.includes('Gateway'));
                const flows = elements.filter(e => e.type === 'bpmn:SequenceFlow');
                
                let report = {
                    valid: true,
                    errors: [],
                    warnings: [],
                    statistics: {
                        startEvents: startEvents.length,
                        endEvents: endEvents.length,
                        tasks: tasks.length,
                        gateways: gateways.length,
                        flows: flows.length,
                        totalElements: elements.filter(e => e.type !== 'bpmn:Process' && e.type !== 'label').length
                    }
                };
                
                // Validation rules
                if (startEvents.length === 0) {
                    report.errors.push('Process must have at least one Start Event');
                    report.valid = false;
                }
                
                if (endEvents.length === 0) {
                    report.errors.push('Process must have at least one End Event');
                    report.valid = false;
                }
                
                if (startEvents.length > 1) {
                    report.warnings.push('Multiple start events detected - ensure this is intentional');
                }
                
                if (report.statistics.totalElements < 3) {
                    report.warnings.push('Process seems very simple (less than 3 elements)');
                }
                
                // Check for disconnected elements
                const disconnectedElements = elements.filter(element => {
                    if (element.type === 'bpmn:Process' || element.type === 'label') return false;
                    if (element.type === 'bpmn:StartEvent') return false; // Start events can be disconnected
                    
                    const incoming = element.incoming || [];
                    const outgoing = element.outgoing || [];
                    return incoming.length === 0 && outgoing.length === 0;
                });
                
                if (disconnectedElements.length > 0) {
                    report.warnings.push(`${disconnectedElements.length} disconnected elements found`);
                }
                
                // Show validation report
                showValidationReport(report);
                
            } catch (error) {
                showNotification('Error during validation!', 'error');
                console.error('Validation error:', error);
            }
        }

        function showValidationReport(report) {
            let message = `📊 Process Validation Report\n\n`;
            message += `Statistics:\n`;
            message += `• Total Elements: ${report.statistics.totalElements}\n`;
            message += `• Start Events: ${report.statistics.startEvents}\n`;
            message += `• End Events: ${report.statistics.endEvents}\n`;
            message += `• Tasks: ${report.statistics.tasks}\n`;
            message += `• Gateways: ${report.statistics.gateways}\n`;
            message += `• Sequence Flows: ${report.statistics.flows}\n\n`;
            
            if (report.errors.length > 0) {
                message += `❌ Errors:\n`;
                report.errors.forEach(error => message += `• ${error}\n`);
                message += `\n`;
            }
            
            if (report.warnings.length > 0) {
                message += `⚠️ Warnings:\n`;
                report.warnings.forEach(warning => message += `• ${warning}\n`);
                message += `\n`;
            }
            
            if (report.valid && report.warnings.length === 0) {
                message += `✅ Process validation passed successfully!`;
                showNotification('✅ Process validation passed!', 'success');
            } else {
                alert(message);
            }
        }

        function zoomIn() {
            const zoomScroll = bpmnModeler.get('zoomScroll');
            zoomScroll.zoom(1, { x: 0, y: 0 });
        }

        function zoomOut() {
            const zoomScroll = bpmnModeler.get('zoomScroll');
            zoomScroll.zoom(-1, { x: 0, y: 0 });
        }

        function zoomToFit() {
            const canvas = bpmnModeler.get('canvas');
            canvas.zoom('fit-viewport');
        }

        function showElementProperties(element) {
            if (!element) {
                clearProperties();
                return;
            }

            const propertiesPanel = document.getElementById('propertiesContent');
            const businessObject = element.businessObject;
            
            let html = `
                <div class="element-info">
                    <h4>${element.type.replace('bpmn:', '')}</h4>
                    <p>ID: ${element.id}</p>
                </div>
                
                <div class="form-group">
                    <label for="elementName">Name</label>
                    <input type="text" id="elementName" value="${businessObject.name || ''}" 
                           onchange="updateElementName('${element.id}', this.value)">
                </div>
                
                <div class="form-group">
                    <label for="elementDocumentation">Documentation</label>
                    <textarea id="elementDocumentation" 
                              onchange="updateElementDocumentation('${element.id}', this.value)">${getDocumentation(businessObject)}</textarea>
                </div>
            `;

            // Add element-specific properties
            if (element.type === 'bpmn:Task' || element.type === 'bpmn:UserTask') {
                html += `
                    <div class="form-group">
                        <label for="assignee">Assignee</label>
                        <input type="text" id="assignee" value="${businessObject.assignee || ''}" 
                               onchange="updateElementProperty('${element.id}', 'assignee', this.value)">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" onchange="updateElementProperty('${element.id}', 'category', this.value)">
                            <option value="">Select category...</option>
                            <option value="manual" ${businessObject.category === 'manual' ? 'selected' : ''}>Manual Task</option>
                            <option value="user" ${businessObject.category === 'user' ? 'selected' : ''}>User Task</option>
                            <option value="service" ${businessObject.category === 'service' ? 'selected' : ''}>Service Task</option>
                            <option value="script" ${businessObject.category === 'script' ? 'selected' : ''}>Script Task</option>
                        </select>
                    </div>
                `;
            }

            if (element.type === 'bpmn:ExclusiveGateway' || element.type === 'bpmn:InclusiveGateway') {
                html += `
                    <div class="form-group">
                        <label for="gatewayDirection">Gateway Direction</label>
                        <select id="gatewayDirection" onchange="updateElementProperty('${element.id}', 'gatewayDirection', this.value)">
                            <option value="Diverging" ${businessObject.gatewayDirection === 'Diverging' ? 'selected' : ''}>Diverging (Split)</option>
                            <option value="Converging" ${businessObject.gatewayDirection === 'Converging' ? 'selected' : ''}>Converging (Merge)</option>
                        </select>
                    </div>
                `;
            }

            propertiesPanel.innerHTML = html;
        }

        function clearProperties() {
            document.getElementById('propertiesContent').innerHTML = `
                <div class="element-info">
                    <h4>Welcome to BPMN Process Builder</h4>
                    <p>Click on any element in the diagram to edit its properties, or start by creating a new process.</p>
                </div>
                
                <div class="form-group">
                    <label>Quick Actions:</label>
                    <button class="btn" onclick="addStartEvent()" style="width: 100%; margin-bottom: 8px;">
                        Add Start Event
                    </button>
                    <button class="btn" onclick="addTask()" style="width: 100%; margin-bottom: 8px;">
                        Add Task
                    </button>
                    <button class="btn" onclick="addEndEvent()" style="width: 100%;">
                        Add End Event
                    </button>
                </div>
            `;
        }

        function getDocumentation(businessObject) {
            if (businessObject.documentation && businessObject.documentation.length > 0) {
                return businessObject.documentation[0].text || '';
            }
            return '';
        }

        function updateElementName(elementId, name) {
            const elementRegistry = bpmnModeler.get('elementRegistry');
            const modeling = bpmnModeler.get('modeling');
            const element = elementRegistry.get(elementId);
            
            if (element) {
                modeling.updateProperties(element, { name: name });
                hasUnsavedChanges = true;
            }
        }

        function updateElementDocumentation(elementId, documentation) {
            const elementRegistry = bpmnModeler.get('elementRegistry');
            const modeling = bpmnModeler.get('modeling');
            const moddle = bpmnModeler.get('moddle');
            const element = elementRegistry.get(elementId);
            
            if (element) {
                const docElement = moddle.create('bpmn:Documentation', { text: documentation });
                modeling.updateProperties(element, { documentation: [docElement] });
                hasUnsavedChanges = true;
            }
        }

        function updateElementProperty(elementId, property, value) {
            const elementRegistry = bpmnModeler.get('elementRegistry');
            const modeling = bpmnModeler.get('modeling');
            const element = elementRegistry.get(elementId);
            
            if (element) {
                const props = {};
                props[property] = value;
                modeling.updateProperties(element, props);
                hasUnsavedChanges = true;
            }
        }

        function addStartEvent() {
            const elementFactory = bpmnModeler.get('elementFactory');
            const canvas = bpmnModeler.get('canvas');
            const modeling = bpmnModeler.get('modeling');
            
            const startEvent = elementFactory.createShape({ type: 'bpmn:StartEvent' });
            const rootElement = canvas.getRootElement();
            
            modeling.createShape(startEvent, { x: 200, y: 200 }, rootElement);
            showNotification('Start Event added!', 'success');
        }

        function addTask() {
            const elementFactory = bpmnModeler.get('elementFactory');
            const canvas = bpmnModeler.get('canvas');
            const modeling = bpmnModeler.get('modeling');
            
            const task = elementFactory.createShape({ type: 'bpmn:Task' });
            const rootElement = canvas.getRootElement();
            
            modeling.createShape(task, { x: 350, y: 200 }, rootElement);
            showNotification('Task added!', 'success');
        }

        function addEndEvent() {
            const elementFactory = bpmnModeler.get('elementFactory');
            const canvas = bpmnModeler.get('canvas');
            const modeling = bpmnModeler.get('modeling');
            
            const endEvent = elementFactory.createShape({ type: 'bpmn:EndEvent' });
            const rootElement = canvas.getRootElement();
            
            modeling.createShape(endEvent, { x: 500, y: 200 }, rootElement);
            showNotification('End Event added!', 'success');
        }

        function updateStats() {
            try {
                const elementRegistry = bpmnModeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const shapes = elements.filter(e => e.type !== 'bpmn:Process' && e.type !== 'label');
                const connections = elements.filter(e => e.type === 'bpmn:SequenceFlow');
                
                const statsText = `Elements: ${shapes.length} | Connections: ${connections.length} | ${hasUnsavedChanges ? 'Unsaved changes' : 'Saved'}`;
                document.getElementById('statsInfo').textContent = statsText;
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 4000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        saveProcess();
                        break;
                    case 'n':
                        e.preventDefault();
                        newProcess();
                        break;
                    case 'o':
                        e.preventDefault();
                        document.getElementById('loadProcessSelect').focus();
                        break;
                    case '=':
                    case '+':
                        e.preventDefault();
                        zoomIn();
                        break;
                    case '-':
                        e.preventDefault();
                        zoomOut();
                        break;
                    case '0':
                        e.preventDefault();
                        zoomToFit();
                        break;
                }
            }
        });

        // Handle modal clicks
        window.onclick = function(event) {
            const modal = document.getElementById('saveModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Warning for unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });

        // Additional BPMN.js event handlers for better UX
        function setupAdvancedEventHandlers() {
            // Handle element creation
            bpmnModeler.on('shape.added', function(event) {
                updateStats();
            });

            // Handle element deletion
            bpmnModeler.on('shape.removed', function(event) {
                updateStats();
            });

            // Handle connection creation
            bpmnModeler.on('connection.added', function(event) {
                updateStats();
            });

            // Handle connection deletion
            bpmnModeler.on('connection.removed', function(event) {
                updateStats();
            });

            // Handle element double-click for quick editing
            bpmnModeler.on('element.dblclick', function(event) {
                const element = event.element;
                if (element.type !== 'bpmn:Process' && element.type !== 'label') {
                    // Focus on name input if properties panel is open
                    setTimeout(() => {
                        const nameInput = document.getElementById('elementName');
                        if (nameInput) {
                            nameInput.focus();
                            nameInput.select();
                        }
                    }, 100);
                }
            });
        }

        // Initialize advanced event handlers after modeler is ready
        setTimeout(() => {
            setupAdvancedEventHandlers();
        }, 1000);

    </script>
</body>
</html>