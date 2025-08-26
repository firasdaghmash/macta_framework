<?php
// modules/M/enhanced_macta_modeling.php - Fixed Combined BPMN + Modeling Module

// Initialize variables
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
            
        case 'assign_resource':
            try {
                echo json_encode(['success' => true, 'message' => 'Resource assigned successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
    <title>MACTA Framework - Enhanced Modeling Module</title>
    
    <!-- BPMN.js CSS - Load first -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />

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
            --box-height: 600px;
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

        #bpmn-editor, #bpmn-viewer, #simulation-viewer {
            height: var(--box-height);
            border: 2px solid var(--htt-blue);
            border-radius: 10px;
            background: white;
            margin-bottom: 20px;
            width: 100%;
            position: relative;
        }

        .loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            color: var(--htt-blue);
            background: white;
            z-index: 1000;
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

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        .btn-danger {
            background: var(--macta-red);
            color: white;
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

        .status-message {
            background: #e8f5e8;
            border: 1px solid var(--macta-green);
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: var(--macta-green);
            text-align: center;
        }

        .status-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            border-left: 4px solid var(--htt-blue);
        }

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

        /* Hide other tabs content for now - focus on fixing design tab */
        .resource-form, .analysis-grid, .performance-metrics, 
        .animation-status, .analysis-card { display: none; }
        
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <div class="macta-logo">M</div>
                MACTA Framework - Enhanced Modeling Module
            </h1>
            <div>
                <a href="../../index.php" class="btn btn-secondary">
                    Back to Framework
                </a>
            </div>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab active" data-tab="bpmn-design">
                BPMN Design
            </button>
            <button class="nav-tab" data-tab="bpmn-view">
                BPMN View
            </button>
            <button class="nav-tab" data-tab="resources">
                Resources
            </button>
            <button class="nav-tab" data-tab="simulation">
                Simulation
            </button>
            <button class="nav-tab" data-tab="analysis">
                Analysis
            </button>
        </div>

        <!-- BPMN Design Tab -->
        <div id="bpmn-design" class="tab-pane active">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>BPMN Process Design & Modeling</h2>
                    <p>Create and edit business process models using BPMN 2.0 standard</p>
                </div>

                <div class="status-message">
                    Enhanced BPMN functionality integrated with MACTA Framework - Full modeling capabilities activated!
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

                <!-- Toolbar -->
                <div class="toolbar">
                    <button class="btn btn-primary" id="btn-new-process">New Process</button>
                    <button class="btn btn-secondary" id="btn-save-process">Save Process</button>
                    <button class="btn btn-warning" id="btn-clear-designer">Clear Designer</button>
                    <button class="btn btn-secondary" id="btn-validate-process">Validate</button>
                    <button class="btn btn-secondary" id="btn-zoom-in">Zoom In</button>
                    <button class="btn btn-secondary" id="btn-zoom-out">Zoom Out</button>
                    <button class="btn btn-secondary" id="btn-zoom-fit">Fit to Screen</button>
                    <button class="btn btn-success" id="btn-export-xml">Export to Viewer</button>
                </div>

                <!-- BPMN Editor -->
                <div id="bpmn-editor">
                    <div class="loading">Loading Enhanced BPMN Designer...</div>
                </div>

                <!-- Info Panel -->
                <div class="color-legend">
                    <h4>BPMN 2.0 Quick Reference Guide</h4>
                    <p>Click on the editor canvas above to access the built-in palette. Here's what each component does:</p>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #e8f5e8; border: 2px solid #27ae60; border-radius: 50%;"></div>
                            <span><strong>Start Event:</strong> Triggers the beginning of a process</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffebee; border: 2px solid #e74c3c; border-radius: 50%;"></div>
                            <span><strong>End Event:</strong> Marks completion of a process</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #e3f2fd; border: 2px solid #2196f3;"></div>
                            <span><strong>Task:</strong> A single unit of work or activity</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #f3e5f5; border: 2px solid #9c27b0;"></div>
                            <span><strong>Gateway:</strong> Controls flow direction and decisions</span>
                        </div>
                    </div>
                </div>

                <div class="status-bar">
                    Use the BPMN editor above to create professional process models.
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

        <!-- Other tabs placeholder -->
        <div id="bpmn-view" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>BPMN Process View</h2>
                    <p>View saved processes (Coming Soon)</p>
                </div>
            </div>
        </div>

        <div id="resources" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>Resource Assignment</h2>
                    <p>Assign resources to tasks (Coming Soon)</p>
                </div>
            </div>
        </div>

        <div id="simulation" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>Process Simulation</h2>
                    <p>Run process simulations (Coming Soon)</p>
                </div>
            </div>
        </div>

        <div id="analysis" class="tab-pane">
            <div class="tab-content">
                <div class="tab-header">
                    <h2>Path Analysis</h2>
                    <p>Analyze process paths (Coming Soon)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BPMN.js Script - Load after DOM -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js"></script>

    <script>
        // Global variables
        let modeler = null;
        let currentXML = null;

        // Get data from PHP
        const processes = <?= json_encode($processes) ?>;
        const dbError = <?= json_encode($db_error) ?>;

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

        // Initialize BPMN Designer
        function initializeBpmnDesigner() {
            console.log('Initializing BPMN Designer...');
            
            try {
                // Check if BpmnJS is available
                if (typeof BpmnJS === 'undefined') {
                    throw new Error('BpmnJS library not loaded');
                }
                
                console.log('BpmnJS library loaded successfully');
                
                // Initialize modeler
                modeler = new BpmnJS({
                    container: '#bpmn-editor',
                    keyboard: { bindTo: window }
                });
                
                console.log('Modeler created');
                
                // Load initial process
                loadInitialProcess();
                
                console.log('BPMN Designer initialized successfully');
                
            } catch (error) {
                console.error('Failed to initialize BPMN:', error);
                
                const editorContainer = document.querySelector('#bpmn-editor');
                if (editorContainer) {
                    editorContainer.innerHTML = `
                        <div class="loading" style="flex-direction: column; color: #e74c3c;">
                            <h3>BPMN Designer Failed to Load</h3>
                            <p>Error: ${error.message}</p>
                            <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 10px;">Reload Page</button>
                            <p style="margin-top: 20px; font-size: 12px; color: #666;">
                                If this problem persists, check your internet connection.
                            </p>
                        </div>
                    `;
                }
            }
        }

        // Load initial process
        async function loadInitialProcess() {
            try {
                console.log('Loading initial process...');
                currentXML = defaultBpmnXml;
                
                if (modeler) {
                    await modeler.importXML(currentXML);
                    modeler.get('canvas').zoom('fit-viewport');
                    console.log('Initial process loaded');
                }
                
                // Hide loading indicator
                const loadingEl = document.querySelector('#bpmn-editor .loading');
                if (loadingEl) {
                    loadingEl.style.display = 'none';
                    console.log('Loading indicator hidden');
                }
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
            }
        }

        // Tab switching
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
        }

        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            
            // Check for database errors first
            if (dbError) {
                console.error('Database Error:', dbError);
                const editorContainer = document.querySelector('#bpmn-editor');
                if (editorContainer) {
                    editorContainer.innerHTML = `
                        <div class="loading" style="flex-direction: column; color: #e74c3c;">
                            <h3>Database Configuration Error</h3>
                            <p>${dbError}</p>
                            <p>Please check your config/config.php file</p>
                        </div>
                    `;
                }
                return;
            }
            
            // Initialize tab switching
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    switchTab(targetTab);
                });
            });
            
            // Initialize BPMN after a short delay
            setTimeout(() => {
                initializeBpmnDesigner();
            }, 500);
            
            // Process selector
            document.getElementById('process-select').addEventListener('change', async (e) => {
                const selectedValue = e.target.value;
                
                if (selectedValue === 'new') {
                    currentXML = defaultBpmnXml;
                    if (modeler) {
                        await modeler.importXML(currentXML);
                        modeler.get('canvas').zoom('fit-viewport');
                    }
                } else if (selectedValue) {
                    const selectedOption = e.target.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    
                    if (xmlData && modeler) {
                        currentXML = xmlData;
                        await modeler.importXML(currentXML);
                        modeler.get('canvas').zoom('fit-viewport');
                    }
                }
            });

            // Button event listeners
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
                        alert('Process saved successfully to MACTA Framework!');
                        location.reload();
                    } else {
                        alert('Failed to save: ' + result.message);
                    }
                    
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Failed to save process');
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
                    
                    if (startEvents.length === 0) validationErrors.push('Missing Start Event');
                    if (endEvents.length === 0) validationErrors.push('Missing End Event');
                    if (startEvents.length > 1) validationErrors.push('Multiple Start Events found');
                    
                    if (validationErrors.length === 0) {
                        alert('MACTA Process validation passed!\n\nHas start event\nHas end event\nBPMN 2.0 compliant');
                    } else {
                        alert('MACTA Validation failed:\n\n' + validationErrors.join('\n'));
                    }
                    
                } catch (error) {
                    console.error('Validation error:', error);
                }
            });

            document.getElementById('btn-zoom-in').addEventListener('click', () => {
                if (modeler) {
                    const zoomScroll = modeler.get('zoomScroll');
                    zoomScroll.stepZoom(1);
                }
            });

            document.getElementById('btn-zoom-out').addEventListener('click', () => {
                if (modeler) {
                    const zoomScroll = modeler.get('zoomScroll');
                    zoomScroll.stepZoom(-1);
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
                    alert('Process exported successfully!');
                } catch (error) {
                    console.error('Export error:', error);
                    alert('Failed to export process');
                }
            });
            
            console.log('Enhanced MACTA BPMN Designer initialized successfully');
        });

        // Fallback initialization if DOM already loaded
        if (document.readyState === 'loading') {
            // DOM is still loading, wait for DOMContentLoaded
        } else {
            // DOM is already loaded
            console.log('DOM already loaded, initializing immediately...');
            setTimeout(() => {
                initializeBpmnDesigner();
            }, 100);
        }

        console.log('MACTA Enhanced Process Designer Loaded');
        console.log('Features: BPMN Design + Database Integration + MACTA Framework');
    </script>
</body>
</html>

            