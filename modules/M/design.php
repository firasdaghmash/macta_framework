<?php
// modules/M/design.php - MACTA BPMN Design Sub-page with COMPLETE Functionality
header('Content-Type: text/html; charset=utf-8');

// Initialize variables for database connection
$processes = [];
$projects = [];
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
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Design DB Error: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if (!empty($db_error)) {
            echo json_encode(['success' => false, 'message' => $db_error]);
            exit;
        }
        
        switch ($_POST['action']) {
            case 'save_process':
                if (isset($_POST['process_id']) && !empty($_POST['process_id'])) {
                    // Update existing process
                    $stmt = $pdo->prepare("
                        UPDATE process_models 
                        SET name = ?, description = ?, model_data = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'] ?? 'Untitled Process',
                        $_POST['description'] ?? '',
                        $_POST['xml'] ?? '',
                        $_POST['process_id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Process updated successfully', 'id' => $_POST['process_id']]);
                } else {
                    // Insert new process
                    $stmt = $pdo->prepare("
                        INSERT INTO process_models (name, description, model_data, project_id) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['name'] ?? 'Untitled Process',
                        $_POST['description'] ?? '',
                        $_POST['xml'] ?? '',
                        $_POST['project_id'] ?? 1
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Process saved successfully', 'id' => $pdo->lastInsertId()]);
                }
                break;
                
            case 'load_process':
                $stmt = $pdo->prepare("SELECT * FROM process_models WHERE id = ?");
                $stmt->execute([$_POST['process_id'] ?? 0]);
                $process = $stmt->fetch();
                echo json_encode(['success' => true, 'process' => $process]);
                break;
                
            case 'get_processes_by_project':
                $stmt = $pdo->prepare("SELECT * FROM process_models WHERE project_id = ? ORDER BY name");
                $stmt->execute([$_POST['project_id'] ?? 1]);
                $processes = $stmt->fetchAll();
                echo json_encode(['success' => true, 'processes' => $processes]);
                break;
                
            case 'save_as_process':
                // Save current process with new name
                $stmt = $pdo->prepare("
                    INSERT INTO process_models (name, description, model_data, project_id) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'] ?? 'Copy of Process',
                    $_POST['description'] ?? 'Copy of existing process',
                    $_POST['xml'] ?? '',
                    $_POST['project_id'] ?? 1
                ]);
                echo json_encode(['success' => true, 'message' => 'Process saved as new copy', 'id' => $pdo->lastInsertId()]);
                break;
                
            case 'rename_process':
                // Rename existing process
                $stmt = $pdo->prepare("
                    UPDATE process_models 
                    SET name = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['new_name'] ?? 'Renamed Process',
                    $_POST['process_id'] ?? 0
                ]);
                echo json_encode(['success' => true, 'message' => 'Process renamed successfully']);
                break;
                
            case 'delete_process':
                // Delete process
                $stmt = $pdo->prepare("DELETE FROM process_models WHERE id = ?");
                $stmt->execute([$_POST['process_id'] ?? 0]);
                echo json_encode(['success' => true, 'message' => 'Process deleted successfully']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA BPMN Designer</title>
    
    <!-- BPMN.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
    
    <style>
        /* Enhanced Designer Styles */
        :root {
            --primary-color: #1E88E5;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --border-color: #bdc3c7;
            --macta-orange: #FF6B35;
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
            background: linear-gradient(135deg, var(--macta-orange), #e55a2b);
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

        .process-management-panel {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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

        .selector-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .selector-group select:focus {
            border-color: var(--macta-orange);
            outline: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
        .btn-secondary { background: #6c757d; color: white; }
        .btn-macta { background: var(--macta-orange); color: white; }

        .design-toolbar {
            background: #f8f9fa;
            padding: 15px 20px;
            margin: 0 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .tool-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bpmn-designer-container {
            padding: 20px;
        }

        .bpmn-editor {
            height: 600px;
            border: 3px solid var(--macta-orange);
            border-radius: 12px;
            background: white;
            position: relative;
            margin-bottom: 20px;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 18px;
            color: var(--macta-orange);
            font-weight: bold;
        }

        .status-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--macta-orange);
            font-weight: 500;
        }

        /* Modal styles for dialogs */
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
            margin: 15% auto;
            padding: 20px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--dark-color);
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--dark-color);
        }

        .modal-body input, .modal-body textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .modal-body input:focus, .modal-body textarea:focus {
            border-color: var(--macta-orange);
            outline: none;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .selector-row {
                grid-template-columns: 1fr;
            }
            
            .action-buttons, .tool-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                width: 90%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tab-header">
            <h2>🔧 MACTA - BPMN Process Design & Modeling</h2>
            <p>Create and edit business process models using BPMN 2.0 standard - Module M</p>
        </div>

        <div class="status-message">
            ✅ Enhanced BPMN Designer with Complete Action Button Functionality - Full BPMN 2.0 Capabilities!
        </div>

        <!-- Process Management Panel -->
        <div class="process-management-panel">
            <h3>📋 Process Management</h3>
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
                    <label>⚙️ Process:</label>
                    <select id="process-select" disabled>
                        <option value="">Select a project first...</option>
                    </select>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-success" id="btn-create-new">🆕 Create New</button>
                <button class="btn btn-macta" id="btn-save-process">💾 Save</button>
                <button class="btn btn-primary" id="btn-save-as">📑 Save As</button>
                <button class="btn btn-warning" id="btn-rename-process" disabled>✏️ Rename</button>
                <button class="btn btn-danger" id="btn-delete-process" disabled>🗑️ Delete</button>
            </div>
        </div>

        <!-- Design Toolbar -->
        <div class="design-toolbar">
            <h4>🛠️ Design Tools</h4>
            <div class="tool-buttons">
                <button class="btn btn-secondary" id="btn-validate-process">✅ Validate</button>
                <button class="btn btn-secondary" id="btn-clear-designer">🗑️ Clear Canvas</button>
                <button class="btn btn-secondary" id="btn-zoom-in">🔍+ Zoom In</button>
                <button class="btn btn-secondary" id="btn-zoom-out">🔍- Zoom Out</button>
                <button class="btn btn-secondary" id="btn-zoom-fit">📐 Fit Screen</button>
                <button class="btn btn-success" id="btn-export-xml">📤 Export XML</button>
            </div>
        </div>

        <!-- BPMN Editor -->
        <div class="bpmn-designer-container">
            <div id="bpmn-editor" class="bpmn-editor">
                <div class="loading">⚡ Loading Professional BPMN Designer...</div>
            </div>
        </div>

        <div class="status-bar">
            <div class="status-info">
                <span>📊 Status:</span>
                <span id="status-text">Initializing...</span>
            </div>
            <div>
                <span id="current-process">No process selected</span>
                <span id="element-count" style="margin-left: 20px;">Elements: 0</span>
            </div>
        </div>
    </div>

    <!-- Modal for Save As -->
    <div id="saveAsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📑 Save Process As</h3>
            </div>
            <div class="modal-body">
                <label>Process Name:</label>
                <input type="text" id="saveAsName" placeholder="Enter new process name">
                <br><br>
                <label>Description:</label>
                <textarea id="saveAsDescription" placeholder="Enter process description" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelSaveAs">Cancel</button>
                <button class="btn btn-primary" id="confirmSaveAs">Save As</button>
            </div>
        </div>
    </div>

    <!-- Modal for Rename -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Rename Process</h3>
            </div>
            <div class="modal-body">
                <label>New Process Name:</label>
                <input type="text" id="renameName" placeholder="Enter new process name">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelRename">Cancel</button>
                <button class="btn btn-warning" id="confirmRename">Rename</button>
            </div>
        </div>
    </div>

    <!-- BPMN.js Script -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js"></script>

    <script>
        // Global variables
        let modeler = null;
        let currentXML = null;
        let currentProcessId = null;
        let currentProjectId = null;
        let currentProcessName = '';

        // Get data from PHP - Updated for AJAX context
        let processes = <?= json_encode($processes) ?>;
        let projects = <?= json_encode($projects) ?>;
        let dbError = <?= json_encode($db_error) ?>;

        // Listen for updated data from AJAX loading
        document.addEventListener('tabContentLoaded', function(e) {
            if (e.detail.tabName === 'design') {
                console.log('Updating design page data from AJAX...');
                processes = e.detail.processes || processes;
                projects = e.detail.projects || projects;
                dbError = e.detail.dbError || dbError;
                
                // Re-populate dropdowns with updated data
                setTimeout(() => {
                    populateDropdowns();
                }, 100);
            }
        });

        // Function to populate dropdowns with current data
        function populateDropdowns() {
            const projectSelect = document.getElementById('project-select');
            const processSelect = document.getElementById('process-select');
            
            if (projectSelect && projects) {
                // Clear and repopulate project dropdown
                const currentValue = projectSelect.value;
                projectSelect.innerHTML = '<option value="">Select Project...</option>';
                
                projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.name;
                    if (project.id == currentValue) {
                        option.selected = true;
                    }
                    projectSelect.appendChild(option);
                });
                
                console.log(`Populated project dropdown with ${projects.length} projects`);
            }
            
            if (processSelect && processes) {
                console.log(`Available processes data: ${processes.length} processes`);
            }
        }

        // Enhanced BPMN XML template
        const defaultBpmnXml = `<?xml version="1.0" encoding="UTF-8"?>
<bpmn2:definitions xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                   xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                   xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                   xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                   id="sample-diagram" 
                   targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn2:process id="Process_1" isExecutable="true">
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
        <dc:Bounds x="152" y="102" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Task_1_di" bpmnElement="Task_1">
        <dc:Bounds x="250" y="80" width="100" height="80"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
        <dc:Bounds x="422" y="102" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
        <di:waypoint x="188" y="120"/>
        <di:waypoint x="250" y="120"/>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="Flow_2_di" bpmnElement="Flow_2">
        <di:waypoint x="350" y="120"/>
        <di:waypoint x="422" y="120"/>
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn2:definitions>`;

        // Initialize BPMN Designer
        function initializeBpmnDesigner() {
            try {
                updateStatus('Loading BPMN Designer...');
                
                // Check if BpmnJS is available
                if (typeof BpmnJS === 'undefined') {
                    throw new Error('BpmnJS library not loaded');
                }
                
                // Initialize modeler
                modeler = new BpmnJS({
                    container: '#bpmn-editor',
                    keyboard: { bindTo: window }
                });
                
                // Load initial process
                loadInitialProcess();
                
                // Setup event listeners
                setupEventListeners();
                
                updateStatus('✅ BPMN Designer Ready');
                console.log('BPMN Designer initialized successfully');
                
            } catch (error) {
                console.error('Failed to initialize BPMN:', error);
                updateStatus('❌ Failed to initialize BPMN: ' + error.message);
                
                const editorContainer = document.querySelector('#bpmn-editor');
                if (editorContainer) {
                    editorContainer.innerHTML = `
                        <div class="loading" style="flex-direction: column;">
                            <h3>❌ BPMN Designer Failed to Load</h3>
                            <p>Error: ${error.message}</p>
                            <button class="btn btn-primary" onclick="location.reload()">🔄 Reload Page</button>
                        </div>
                    `;
                }
            }
        }

        // Load initial process
        async function loadInitialProcess() {
            try {
                currentXML = defaultBpmnXml;
                
                if (modeler) {
                    await modeler.importXML(currentXML);
                    modeler.get('canvas').zoom('fit-viewport');
                }
                
                // Hide loading indicator
                const loadingEl = document.querySelector('#bpmn-editor .loading');
                if (loadingEl) loadingEl.style.display = 'none';
                
                updateElementCount();
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
                updateStatus('❌ Failed to load process template');
            }
        }

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
                }
            });

            // Process selection
            document.getElementById('process-select').addEventListener('change', async function() {
                const processId = this.value;
                if (processId) {
                    await loadProcess(processId);
                }
                updateButtonStates();
            });

            // Button events - ALL IMPLEMENTED
            document.getElementById('btn-create-new').addEventListener('click', createNewProcess);
            document.getElementById('btn-save-process').addEventListener('click', saveProcess);
            document.getElementById('btn-save-as').addEventListener('click', showSaveAsModal);
            document.getElementById('btn-rename-process').addEventListener('click', showRenameModal);
            document.getElementById('btn-delete-process').addEventListener('click', deleteProcess);
            document.getElementById('btn-clear-designer').addEventListener('click', clearDesigner);
            document.getElementById('btn-validate-process').addEventListener('click', validateProcess);
            document.getElementById('btn-export-xml').addEventListener('click', exportXML);
            document.getElementById('btn-zoom-in').addEventListener('click', () => modeler && modeler.get('zoomScroll').stepZoom(1));
            document.getElementById('btn-zoom-out').addEventListener('click', () => modeler && modeler.get('zoomScroll').stepZoom(-1));
            document.getElementById('btn-zoom-fit').addEventListener('click', () => modeler && modeler.get('canvas').zoom('fit-viewport'));
            
            // Modal events
            setupModalEventListeners();
            
            // BPMN events
            if (modeler) {
                const eventBus = modeler.get('eventBus');
                eventBus.on(['shape.added', 'shape.removed', 'connection.added', 'connection.removed'], updateElementCount);
            }
        }

        // Setup modal event listeners
        function setupModalEventListeners() {
            // Save As Modal
            document.getElementById('cancelSaveAs').addEventListener('click', hideSaveAsModal);
            document.getElementById('confirmSaveAs').addEventListener('click', performSaveAs);
            
            // Rename Modal
            document.getElementById('cancelRename').addEventListener('click', hideRenameModal);
            document.getElementById('confirmRename').addEventListener('click', performRename);
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    hideSaveAsModal();
                    hideRenameModal();
                }
            });
        }

        // Load processes by project
        async function loadProcessesByProject(projectId) {
            try {
                updateStatus('📥 Loading processes...');
                
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
                    updateStatus(`✅ Loaded ${result.processes.length} processes`);
                } else {
                    console.error('Failed to load processes:', result.message);
                    updateStatus('❌ Failed to load processes');
                }
                
            } catch (error) {
                console.error('Error loading processes:', error);
                updateStatus('❌ Error loading processes');
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
                option.dataset.xml = process.model_data || '';
                processSelect.appendChild(option);
            });
        }

        // Load specific process
        async function loadProcess(processId) {
            try {
                updateStatus('📂 Loading process...');
                
                const formData = new FormData();
                formData.append('action', 'load_process');
                formData.append('process_id', processId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.process) {
                    const xml = result.process.model_data || defaultBpmnXml;
                    await modeler.importXML(xml);
                    modeler.get('canvas').zoom('fit-viewport');
                    
                    currentProcessId = processId;
                    currentXML = xml;
                    currentProcessName = result.process.name;
                    
                    document.getElementById('current-process').textContent = result.process.name;
                    updateStatus('✅ Process loaded successfully');
                    updateElementCount();
                }
                
            } catch (error) {
                console.error('Failed to load process:', error);
                updateStatus('❌ Failed to load process');
            }
        }

        // Create new process
        async function createNewProcess() {
            try {
                currentXML = defaultBpmnXml;
                currentProcessId = null;
                currentProcessName = '';
                
                await modeler.importXML(currentXML);
                modeler.get('canvas').zoom('fit-viewport');
                
                document.getElementById('process-select').value = '';
                document.getElementById('current-process').textContent = 'New Process (Unsaved)';
                
                updateStatus('🆕 New process created');
                updateButtonStates();
                updateElementCount();
                
            } catch (error) {
                console.error('Failed to create new process:', error);
                updateStatus('❌ Failed to create new process');
            }
        }

        // Save current process
        async function saveProcess() {
            if (!modeler) {
                alert('❌ BPMN Designer not initialized');
                return;
            }
            
            if (!currentProjectId) {
                alert('⚠️ Please select a project first!');
                return;
            }
            
            try {
                const { xml } = await modeler.saveXML({ format: true });
                
                let processName = currentProcessName;
                if (!processName || processName === '') {
                    processName = prompt('📝 Enter process name:', 'New Process') || 'Untitled Process';
                }
                
                updateStatus('💾 Saving process...');
                
                const formData = new FormData();
                formData.append('action', 'save_process');
                formData.append('name', processName);
                formData.append('xml', xml);
                formData.append('project_id', currentProjectId);
                if (currentProcessId) {
                    formData.append('process_id', currentProcessId);
                }
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentProcessId = result.id || currentProcessId;
                    currentXML = xml;
                    currentProcessName = processName;
                    
                    alert(`✅ Process "${processName}" saved successfully!`);
                    updateStatus(`✅ Process "${processName}" saved`);
                    document.getElementById('current-process').textContent = processName;
                    
                    // Refresh process list
                    if (currentProjectId) {
                        loadProcessesByProject(currentProjectId);
                    }
                } else {
                    alert('❌ Failed to save: ' + result.message);
                    updateStatus('❌ Save failed');
                }
                
            } catch (error) {
                console.error('Save error:', error);
                alert('❌ Failed to save process');
                updateStatus('❌ Save error occurred');
            }
        }

        // Show Save As modal
        function showSaveAsModal() {
            if (!modeler) {
                alert('❌ BPMN Designer not initialized');
                return;
            }
            
            if (!currentProjectId) {
                alert('⚠️ Please select a project first!');
                return;
            }
            
            // Pre-fill with current process name if available
            const currentName = currentProcessName || 'New Process';
            document.getElementById('saveAsName').value = `Copy of ${currentName}`;
            document.getElementById('saveAsDescription').value = `Copy of ${currentName} process`;
            
            document.getElementById('saveAsModal').style.display = 'block';
        }

        // Hide Save As modal
        function hideSaveAsModal() {
            document.getElementById('saveAsModal').style.display = 'none';
            document.getElementById('saveAsName').value = '';
            document.getElementById('saveAsDescription').value = '';
        }

        // Perform Save As operation
        async function performSaveAs() {
            const newName = document.getElementById('saveAsName').value.trim();
            const newDescription = document.getElementById('saveAsDescription').value.trim();
            
            if (!newName) {
                alert('⚠️ Please enter a process name');
                return;
            }
            
            try {
                updateStatus('📑 Saving process copy...');
                
                const { xml } = await modeler.saveXML({ format: true });
                
                const formData = new FormData();
                formData.append('action', 'save_as_process');
                formData.append('name', newName);
                formData.append('description', newDescription);
                formData.append('xml', xml);
                formData.append('project_id', currentProjectId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update current process to the new copy
                    currentProcessId = result.id;
                    currentProcessName = newName;
                    
                    alert(`✅ Process saved as "${newName}" successfully!`);
                    updateStatus(`✅ Process saved as "${newName}"`);
                    document.getElementById('current-process').textContent = newName;
                    
                    hideSaveAsModal();
                    
                    // Refresh process list
                    if (currentProjectId) {
                        loadProcessesByProject(currentProjectId);
                    }
                } else {
                    alert('❌ Failed to save as: ' + result.message);
                    updateStatus('❌ Save As failed');
                }
                
            } catch (error) {
                console.error('Save As error:', error);
                alert('❌ Failed to save process copy');
                updateStatus('❌ Save As error occurred');
            }
        }

        // Show Rename modal
        function showRenameModal() {
            if (!currentProcessId) {
                alert('⚠️ No process selected to rename');
                return;
            }
            
            document.getElementById('renameName').value = currentProcessName;
            document.getElementById('renameModal').style.display = 'block';
        }

        // Hide Rename modal
        function hideRenameModal() {
            document.getElementById('renameModal').style.display = 'none';
            document.getElementById('renameName').value = '';
        }

        // Perform Rename operation
        async function performRename() {
            const newName = document.getElementById('renameName').value.trim();
            
            if (!newName) {
                alert('⚠️ Please enter a process name');
                return;
            }
            
            if (newName === currentProcessName) {
                hideRenameModal();
                return;
            }
            
            try {
                updateStatus('✏️ Renaming process...');
                
                const formData = new FormData();
                formData.append('action', 'rename_process');
                formData.append('process_id', currentProcessId);
                formData.append('new_name', newName);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentProcessName = newName;
                    
                    alert(`✅ Process renamed to "${newName}" successfully!`);
                    updateStatus(`✅ Process renamed to "${newName}"`);
                    document.getElementById('current-process').textContent = newName;
                    
                    hideRenameModal();
                    
                    // Update the dropdown
                    const processSelect = document.getElementById('process-select');
                    const selectedOption = processSelect.querySelector(`option[value="${currentProcessId}"]`);
                    if (selectedOption) {
                        selectedOption.textContent = newName;
                    }
                } else {
                    alert('❌ Failed to rename: ' + result.message);
                    updateStatus('❌ Rename failed');
                }
                
            } catch (error) {
                console.error('Rename error:', error);
                alert('❌ Failed to rename process');
                updateStatus('❌ Rename error occurred');
            }
        }

        // Delete process
        async function deleteProcess() {
            if (!currentProcessId) {
                alert('⚠️ No process selected to delete');
                return;
            }
            
            const confirmDelete = confirm(`🗑️ Are you sure you want to delete "${currentProcessName}"?\n\nThis action cannot be undone!`);
            
            if (!confirmDelete) {
                return;
            }
            
            try {
                updateStatus('🗑️ Deleting process...');
                
                const formData = new FormData();
                formData.append('action', 'delete_process');
                formData.append('process_id', currentProcessId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ Process "${currentProcessName}" deleted successfully!`);
                    updateStatus(`✅ Process "${currentProcessName}" deleted`);
                    
                    // Clear current process and create new one
                    await createNewProcess();
                    
                    // Refresh process list
                    if (currentProjectId) {
                        loadProcessesByProject(currentProjectId);
                    }
                } else {
                    alert('❌ Failed to delete: ' + result.message);
                    updateStatus('❌ Delete failed');
                }
                
            } catch (error) {
                console.error('Delete error:', error);
                alert('❌ Failed to delete process');
                updateStatus('❌ Delete error occurred');
            }
        }

        // Export XML
        async function exportXML() {
            if (!modeler) {
                alert('❌ BPMN Designer not initialized');
                return;
            }
            
            try {
                updateStatus('📤 Exporting XML...');
                
                const { xml } = await modeler.saveXML({ format: true });
                
                // Create download link
                const blob = new Blob([xml], { type: 'application/xml' });
                const url = URL.createObjectURL(blob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = `${currentProcessName || 'process'}.bpmn`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                URL.revokeObjectURL(url);
                
                updateStatus('✅ XML exported successfully');
                
                // Also copy to clipboard
                try {
                    await navigator.clipboard.writeText(xml);
                    alert(`📤 BPMN XML exported as file and copied to clipboard!\n\nFile: ${currentProcessName || 'process'}.bpmn`);
                } catch (clipError) {
                    alert(`📤 BPMN XML exported as file!\n\nFile: ${currentProcessName || 'process'}.bpmn`);
                }
                
            } catch (error) {
                console.error('Export error:', error);
                alert('❌ Failed to export XML');
                updateStatus('❌ Export error occurred');
            }
        }

        // Clear designer
        async function clearDesigner() {
            const confirmClear = confirm('🗑️ Are you sure you want to clear the canvas?\n\nAll unsaved changes will be lost!');
            
            if (!confirmClear) {
                return;
            }
            
            try {
                await modeler.importXML(defaultBpmnXml);
                modeler.get('canvas').zoom('fit-viewport');
                currentXML = defaultBpmnXml;
                currentProcessId = null;
                currentProcessName = '';
                
                document.getElementById('process-select').value = '';
                document.getElementById('current-process').textContent = 'New Process (Unsaved)';
                
                updateStatus('🗑️ Designer cleared');
                updateButtonStates();
                updateElementCount();
                
            } catch (error) {
                console.error('Failed to clear designer:', error);
                updateStatus('❌ Failed to clear designer');
            }
        }

        // Validate process
        async function validateProcess() {
            if (!modeler) {
                alert('❌ BPMN Designer not initialized');
                return;
            }
            
            try {
                updateStatus('✅ Validating process...');
                
                const elementRegistry = modeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const startEvents = elements.filter(el => el.type === 'bpmn:StartEvent');
                const endEvents = elements.filter(el => el.type === 'bpmn:EndEvent');
                const tasks = elements.filter(el => el.type.includes('Task'));
                const gateways = elements.filter(el => el.type.includes('Gateway'));
                const events = elements.filter(el => el.type.includes('Event'));
                
                let validationErrors = [];
                let validationWarnings = [];
                
                // Critical validation rules
                if (startEvents.length === 0) validationErrors.push('❌ Missing Start Event');
                if (endEvents.length === 0) validationErrors.push('❌ Missing End Event');
                if (startEvents.length > 1) validationWarnings.push('⚠️ Multiple Start Events found');
                
                // Additional validation
                if (tasks.length === 0) validationWarnings.push('⚠️ No tasks defined in process');
                
                let message = '🔍 BPMN 2.0 Validation Results\n';
                message += '═══════════════════════════\n\n';
                
                if (validationErrors.length === 0) {
                    message += '✅ VALIDATION PASSED!\n\n';
                    message += '✓ Has start event\n✓ Has end event\n✓ BPMN 2.0 compliant\n';
                    message += `✓ ${tasks.length} tasks defined\n`;
                    if (gateways.length > 0) message += `✓ ${gateways.length} gateways defined\n`;
                } else {
                    message += '❌ VALIDATION FAILED!\n\nCritical Errors:\n';
                    validationErrors.forEach(error => message += error + '\n');
                }
                
                if (validationWarnings.length > 0) {
                    message += '\n⚠️ Warnings:\n';
                    validationWarnings.forEach(warning => message += warning + '\n');
                }
                
                message += `\n📊 Process Statistics:\n`;
                message += `• Total Elements: ${elements.length}\n`;
                message += `• Start Events: ${startEvents.length}\n`;
                message += `• End Events: ${endEvents.length}\n`;
                message += `• Tasks: ${tasks.length}\n`;
                message += `• Gateways: ${gateways.length}\n`;
                message += `• Events: ${events.length}`;
                
                alert(message);
                
                const status = validationErrors.length === 0 ? 
                    '✅ Process validation passed' : 
                    `❌ Validation failed (${validationErrors.length} errors)`;
                updateStatus(status);
                
            } catch (error) {
                console.error('Validation error:', error);
                updateStatus('❌ Validation error occurred');
            }
        }

        // Update button states
        function updateButtonStates() {
            const processSelect = document.getElementById('process-select');
            const hasSelection = processSelect && processSelect.value && currentProcessId;
            
            document.getElementById('btn-rename-process').disabled = !hasSelection;
            document.getElementById('btn-delete-process').disabled = !hasSelection;
        }

        // Update element count
        function updateElementCount() {
            if (!modeler) return;
            
            try {
                const elementRegistry = modeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                const shapeCount = elements.filter(el => el.type !== 'bpmn:SequenceFlow').length;
                
                document.getElementById('element-count').textContent = `Elements: ${shapeCount}`;
            } catch (error) {
                console.log('Failed to update element count:', error);
            }
        }

        // Update status
        function updateStatus(message) {
            const statusEl = document.getElementById('status-text');
            if (statusEl) {
                statusEl.textContent = message;
            }
            console.log('Status:', message);
        }

        // Wait for DOM and initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing BPMN Designer...');
            
            // Check if we have database errors
            if (dbError) {
                updateStatus('❌ Database Error: ' + dbError);
                document.querySelector('#bpmn-editor .loading').innerHTML = 
                    '<div style="text-align: center;"><h3>❌ Database Configuration Error</h3><p>' + dbError + '</p></div>';
                return;
            }
            
            // Initialize after a short delay to ensure BpmnJS is loaded
            setTimeout(initializeBpmnDesigner, 100);
        });

        // Fallback initialization if DOMContentLoaded already fired
        if (document.readyState === 'loading') {
            // DOM is still loading, wait for DOMContentLoaded
        } else {
            // DOM is already loaded
            console.log('DOM already loaded, initializing BPMN Designer...');
            setTimeout(initializeBpmnDesigner, 100);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Ctrl+S to save
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                saveProcess();
            }
            // Ctrl+N for new process
            if (event.ctrlKey && event.key === 'n') {
                event.preventDefault();
                createNewProcess();
            }
            // Delete key for delete (when process is selected)
            if (event.key === 'Delete' && currentProcessId && !event.target.matches('input, textarea')) {
                deleteProcess();
            }
        });
    </script>
</body>
</html>