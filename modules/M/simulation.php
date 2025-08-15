<?php
// modules/M/simulation.php - Process Simulation Engine
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'load_process_for_simulation':
                $processId = $_POST['process_id'];
                $stmt = $conn->prepare("SELECT * FROM process_models WHERE id = ?");
                $stmt->execute([$processId]);
                $process = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($process) {
                    // Parse BPMN to extract elements
                    $elements = extractBPMNElements($process['model_data']);
                    echo json_encode(['success' => true, 'process' => $process, 'elements' => $elements]);
                } else {
                    throw new Exception('Process not found');
                }
                break;
                
            case 'save_simulation_config':
                $processId = $_POST['process_id'];
                $config = $_POST['config'];
                
                // Save or update simulation configuration
                $stmt = $conn->prepare("
                    INSERT INTO simulation_configs (process_id, config_data, created_at, updated_at) 
                    VALUES (?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE config_data = ?, updated_at = NOW()
                ");
                $stmt->execute([$processId, $config, $config]);
                
                echo json_encode(['success' => true, 'message' => 'Simulation configuration saved']);
                break;
                
            case 'run_simulation':
                $processId = $_POST['process_id'];
                $scenarios = json_decode($_POST['scenarios'], true);
                
                // Run simulation with different scenarios
                $results = runProcessSimulation($conn, $processId, $scenarios);
                
                // Save simulation results
                $stmt = $conn->prepare("
                    INSERT INTO simulation_results 
                    (process_id, scenario_data, results_data, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $processId, 
                    $_POST['scenarios'], 
                    json_encode($results)
                ]);
                
                echo json_encode(['success' => true, 'results' => $results]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get available processes for simulation
$stmt = $conn->prepare("
    SELECT pm.*, p.name as project_name 
    FROM process_models pm
    LEFT JOIN projects p ON pm.project_id = p.id
    ORDER BY pm.updated_at DESC
");
$stmt->execute();
$processes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to extract BPMN elements
function extractBPMNElements($bpmnXml) {
    $elements = [];
    
    // Simple XML parsing to extract process elements
    try {
        $xml = new SimpleXMLElement($bpmnXml);
        $xml->registerXPathNamespace('bpmn2', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        
        // Extract different types of elements
        $tasks = $xml->xpath('//bpmn2:task | //bpmn2:userTask | //bpmn2:serviceTask | //bpmn2:manualTask');
        $gateways = $xml->xpath('//bpmn2:exclusiveGateway | //bpmn2:parallelGateway | //bpmn2:inclusiveGateway');
        $events = $xml->xpath('//bpmn2:startEvent | //bpmn2:endEvent | //bpmn2:intermediateThrowEvent | //bpmn2:intermediateCatchEvent');
        
        foreach ($tasks as $task) {
            $elements[] = [
                'id' => (string)$task['id'],
                'name' => (string)$task['name'] ?: 'Unnamed Task',
                'type' => 'task',
                'elementType' => $task->getName()
            ];
        }
        
        foreach ($gateways as $gateway) {
            $elements[] = [
                'id' => (string)$gateway['id'],
                'name' => (string)$gateway['name'] ?: 'Gateway',
                'type' => 'gateway',
                'elementType' => $gateway->getName()
            ];
        }
        
        foreach ($events as $event) {
            $elements[] = [
                'id' => (string)$event['id'],
                'name' => (string)$event['name'] ?: 'Event',
                'type' => 'event',
                'elementType' => $event->getName()
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error parsing BPMN XML: " . $e->getMessage());
    }
    
    return $elements;
}

// Function to run process simulation
function runProcessSimulation($conn, $processId, $scenarios) {
    $results = [];
    
    foreach ($scenarios as $scenarioName => $scenario) {
        $scenarioResults = [
            'name' => $scenarioName,
            'totalTime' => 0,
            'totalCost' => 0,
            'bottlenecks' => [],
            'resourceUtilization' => [],
            'steps' => []
        ];
        
        // Simulate each step in the scenario
        foreach ($scenario['steps'] as $stepId => $stepConfig) {
            $stepResult = simulateStep($stepConfig);
            $scenarioResults['steps'][$stepId] = $stepResult;
            $scenarioResults['totalTime'] += $stepResult['duration'];
            $scenarioResults['totalCost'] += $stepResult['cost'];
            
            // Check for bottlenecks
            if ($stepResult['utilization'] > 0.8) {
                $scenarioResults['bottlenecks'][] = [
                    'stepId' => $stepId,
                    'name' => $stepConfig['name'],
                    'utilization' => $stepResult['utilization'],
                    'waitTime' => $stepResult['waitTime']
                ];
            }
        }
        
        $results[] = $scenarioResults;
    }
    
    return $results;
}

// Function to simulate individual step
function simulateStep($stepConfig) {
    $baseTime = $stepConfig['duration'] ?? 60; // Default 60 minutes
    $resources = $stepConfig['resources'] ?? 1;
    $complexity = $stepConfig['complexity'] ?? 1;
    
    // Apply variability and complexity factors
    $actualDuration = $baseTime * $complexity * (0.8 + (rand(0, 40) / 100)); // ±20% variability
    $cost = ($stepConfig['hourlyRate'] ?? 50) * ($actualDuration / 60) * $resources;
    $utilization = min(1.0, $resources * 0.7 + (rand(0, 30) / 100)); // Random utilization
    $waitTime = $utilization > 0.8 ? $actualDuration * 0.2 : 0; // Wait time if overutilized
    
    return [
        'duration' => round($actualDuration, 2),
        'cost' => round($cost, 2),
        'utilization' => round($utilization, 2),
        'waitTime' => round($waitTime, 2),
        'efficiency' => round((1 - ($waitTime / $actualDuration)) * 100, 1)
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Simulation - MACTA Framework</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .simulation-container {
            display: flex;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        .process-selector {
            width: 300px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .simulation-workspace {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .process-item {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .process-item:hover {
            background: #f8f9fa;
            border-color: var(--primary-color);
        }
        
        .process-item.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .element-config {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .element-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .element-type {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .config-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .config-group {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .simulation-controls {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .scenario-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .scenario-tab {
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .scenario-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .results-container {
            margin-top: 30px;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .result-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        
        .metric-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .bottleneck-item {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
        }
        
        .chart-container {
            width: 100%;
            height: 300px;
            margin-top: 20px;
        }
        
        input[type="number"], input[type="text"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > 
                <a href="index.php">Process Modeling</a> > 
                Process Simulation
            </div>
        </div>
    </div>

    <div class="container">
        <div class="simulation-container">
            <!-- Process Selector -->
            <div class="process-selector">
                <h3>Select Process</h3>
                <div id="processList">
                    <?php foreach ($processes as $process): ?>
                        <div class="process-item" data-process-id="<?php echo $process['id']; ?>">
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($process['name']); ?></div>
                            <div style="font-size: 12px; color: #666;">
                                <?php echo $process['project_name'] ? htmlspecialchars($process['project_name']) : 'No Project'; ?>
                            </div>
                            <div style="font-size: 11px; color: #999;">
                                Updated: <?php echo date('M d, Y', strtotime($process['updated_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Simulation Workspace -->
            <div class="simulation-workspace">
                <div id="welcomeMessage">
                    <h2>Process Simulation Engine</h2>
                    <p>Select a process from the left panel to begin configuring simulation parameters.</p>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>🎯 Simulation Features:</h4>
                        <ul>
                            <li><strong>Resource Assignment:</strong> Assign human resources, equipment, and costs to each step</li>
                            <li><strong>Time Estimation:</strong> Set duration, complexity factors, and variability</li>
                            <li><strong>Scenario Planning:</strong> Create multiple scenarios (Current State, Optimized, Future State)</li>
                            <li><strong>Bottleneck Detection:</strong> Identify resource constraints and process inefficiencies</li>
                            <li><strong>Cost Analysis:</strong> Calculate total process costs and resource utilization</li>
                            <li><strong>Performance Metrics:</strong> Track cycle time, throughput, and efficiency</li>
                        </ul>
                    </div>
                </div>

                <div id="simulationContent" class="hidden">
                    <!-- Scenario Selection -->
                    <div class="scenario-tabs">
                        <div class="scenario-tab active" data-scenario="current">Current State</div>
                        <div class="scenario-tab" data-scenario="optimized">Optimized</div>
                        <div class="scenario-tab" data-scenario="future">Future State</div>
                    </div>

                    <!-- Process Configuration -->
                    <div id="processConfig">
                        <h3 id="processTitle">Process Configuration</h3>
                        <div id="elementsContainer"></div>
                    </div>

                    <!-- Simulation Controls -->
                    <div class="simulation-controls">
                        <h4>Simulation Parameters</h4>
                        <div class="config-row">
                            <div>
                                <label>Number of Iterations:</label>
                                <input type="number" id="iterations" value="100" min="10" max="1000">
                            </div>
                            <div>
                                <label>Time Unit:</label>
                                <select id="timeUnit">
                                    <option value="minutes">Minutes</option>
                                    <option value="hours">Hours</option>
                                    <option value="days">Days</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <button class="btn btn-primary" onclick="runSimulation()">▶️ Run Simulation</button>
                            <button class="btn btn-secondary" onclick="saveConfiguration()">💾 Save Configuration</button>
                            <button class="btn btn-secondary" onclick="exportResults()">📊 Export Results</button>
                        </div>
                    </div>

                    <!-- Results Container -->
                    <div id="resultsContainer" class="results-container hidden">
                        <h3>Simulation Results</h3>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentProcess = null;
        let currentScenario = 'current';
        let processElements = [];
        let simulationConfig = {
            current: {},
            optimized: {},
            future: {}
        };

        // Process selection
        document.querySelectorAll('.process-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.process-item').forEach(p => p.classList.remove('selected'));
                item.classList.add('selected');
                loadProcessForSimulation(item.dataset.processId);
            });
        });

        // Scenario tab switching
        document.querySelectorAll('.scenario-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.scenario-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentScenario = tab.dataset.scenario;
                updateConfigurationUI();
            });
        });

        function loadProcessForSimulation(processId) {
            fetch('simulation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'load_process_for_simulation',
                    process_id: processId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentProcess = data.process;
                    processElements = data.elements;
                    
                    document.getElementById('welcomeMessage').classList.add('hidden');
                    document.getElementById('simulationContent').classList.remove('hidden');
                    document.getElementById('processTitle').textContent = data.process.name;
                    
                    initializeSimulationConfig();
                    renderElementConfiguration();
                } else {
                    alert('Error loading process: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }

        function initializeSimulationConfig() {
            // Initialize default configurations for all scenarios
            ['current', 'optimized', 'future'].forEach(scenario => {
                simulationConfig[scenario] = { steps: {} };
                
                processElements.forEach(element => {
                    if (element.type === 'task') {
                        simulationConfig[scenario].steps[element.id] = {
                            name: element.name,
                            duration: scenario === 'optimized' ? 45 : scenario === 'future' ? 30 : 60,
                            resources: scenario === 'future' ? 2 : 1,
                            hourlyRate: 50,
                            complexity: scenario === 'optimized' ? 0.8 : 1,
                            skillLevel: scenario === 'future' ? 'expert' : 'intermediate'
                        };
                    }
                });
            });
        }

        function renderElementConfiguration() {
            const container = document.getElementById('elementsContainer');
            container.innerHTML = '';

            processElements.forEach(element => {
                if (element.type === 'task') {
                    const elementDiv = document.createElement('div');
                    elementDiv.className = 'element-config';
                    elementDiv.innerHTML = `
                        <div class="element-header">
                            <h4>${element.name}</h4>
                            <span class="element-type">${element.elementType}</span>
                        </div>
                        <div class="config-row">
                            <div>
                                <label>Duration (${document.getElementById('timeUnit').value}):</label>
                                <input type="number" id="duration_${element.id}" 
                                       value="${simulationConfig[currentScenario].steps[element.id]?.duration || 60}"
                                       onchange="updateElementConfig('${element.id}', 'duration', this.value)">
                            </div>
                            <div>
                                <label>Resources Required:</label>
                                <input type="number" id="resources_${element.id}" 
                                       value="${simulationConfig[currentScenario].steps[element.id]?.resources || 1}"
                                       onchange="updateElementConfig('${element.id}', 'resources', this.value)">
                            </div>
                        </div>
                        <div class="config-row">
                            <div>
                                <label>Hourly Rate ($):</label>
                                <input type="number" id="hourlyRate_${element.id}" 
                                       value="${simulationConfig[currentScenario].steps[element.id]?.hourlyRate || 50}"
                                       onchange="updateElementConfig('${element.id}', 'hourlyRate', this.value)">
                            </div>
                            <div>
                                <label>Complexity Factor:</label>
                                <input type="number" id="complexity_${element.id}" step="0.1" min="0.1" max="3"
                                       value="${simulationConfig[currentScenario].steps[element.id]?.complexity || 1}"
                                       onchange="updateElementConfig('${element.id}', 'complexity', this.value)">
                            </div>
                        </div>
                        <div class="config-row">
                            <div>
                                <label>Skill Level Required:</label>
                                <select id="skillLevel_${element.id}" 
                                        onchange="updateElementConfig('${element.id}', 'skillLevel', this.value)">
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate" selected>Intermediate</option>
                                    <option value="expert">Expert</option>
                                </select>
                            </div>
                            <div>
                                <label>Equipment/Tools:</label>
                                <input type="text" id="equipment_${element.id}" placeholder="e.g., Computer, Software"
                                       value="${simulationConfig[currentScenario].steps[element.id]?.equipment || ''}"
                                       onchange="updateElementConfig('${element.id}', 'equipment', this.value)">
                            </div>
                        </div>
                    `;
                    container.appendChild(elementDiv);
                }
            });
        }

        function updateElementConfig(elementId, property, value) {
            if (!simulationConfig[currentScenario].steps[elementId]) {
                simulationConfig[currentScenario].steps[elementId] = {};
            }
            simulationConfig[currentScenario].steps[elementId][property] = 
                property === 'skillLevel' || property === 'equipment' ? value : parseFloat(value);
        }

        function updateConfigurationUI() {
            processElements.forEach(element => {
                if (element.type === 'task' && simulationConfig[currentScenario].steps[element.id]) {
                    const config = simulationConfig[currentScenario].steps[element.id];
                    
                    const durationInput = document.getElementById(`duration_${element.id}`);
                    const resourcesInput = document.getElementById(`resources_${element.id}`);
                    const hourlyRateInput = document.getElementById(`hourlyRate_${element.id}`);
                    const complexityInput = document.getElementById(`complexity_${element.id}`);
                    const skillLevelSelect = document.getElementById(`skillLevel_${element.id}`);
                    const equipmentInput = document.getElementById(`equipment_${element.id}`);
                    
                    if (durationInput) durationInput.value = config.duration || 60;
                    if (resourcesInput) resourcesInput.value = config.resources || 1;
                    if (hourlyRateInput) hourlyRateInput.value = config.hourlyRate || 50;
                    if (complexityInput) complexityInput.value = config.complexity || 1;
                    if (skillLevelSelect) skillLevelSelect.value = config.skillLevel || 'intermediate';
                    if (equipmentInput) equipmentInput.value = config.equipment || '';
                }
            });
        }

        function runSimulation() {
            if (!currentProcess) {
                alert('Please select a process first');
                return;
            }

            const iterations = document.getElementById('iterations').value;
            
            fetch('simulation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'run_simulation',
                    process_id: currentProcess.id,
                    scenarios: JSON.stringify(simulationConfig),
                    iterations: iterations
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.results);
                } else {
                    alert('Simulation error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }

        function displayResults(results) {
            const container = document.getElementById('resultsContainer');
            const content = document.getElementById('resultsContent');
            
            container.classList.remove('hidden');
            
            let html = '<div class="results-grid">';
            
            results.forEach(result => {
                html += `
                    <div class="result-card">
                        <h4>${result.name} Scenario</h4>
                        <div class="metric-row">
                            <span>Total Time:</span>
                            <span><strong>${result.totalTime.toFixed(1)} min</strong></span>
                        </div>
                        <div class="metric-row">
                            <span>Total Cost:</span>
                            <span><strong>$${result.totalCost.toFixed(2)}</strong></span>
                        </div>
                        <div class="metric-row">
                            <span>Bottlenecks:</span>
                            <span><strong>${result.bottlenecks.length}</strong></span>
                        </div>
                        
                        ${result.bottlenecks.length > 0 ? `
                            <h5 style="margin-top: 15px; color: #856404;">⚠️ Identified Bottlenecks:</h5>
                            ${result.bottlenecks.map(bottleneck => `
                                <div class="bottleneck-item">
                                    <strong>${bottleneck.name}</strong><br>
                                    Utilization: ${(bottleneck.utilization * 100).toFixed(1)}%<br>
                                    Wait Time: ${bottleneck.waitTime.toFixed(1)} min
                                </div>
                            `).join('')}
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Add comparison chart
            html += `
                <div class="chart-container">
                    <canvas id="comparisonChart"></canvas>
                </div>
            `;
            
            content.innerHTML = html;
            
            // Create comparison chart
            createComparisonChart(results);
        }

        function createComparisonChart(results) {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: results.map(r => r.name),
                    datasets: [
                        {
                            label: 'Total Time (min)',
                            data: results.map(r => r.totalTime),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Total Cost ($)',
                            data: results.map(r => r.totalCost),
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Time (minutes)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cost ($)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Scenario Comparison: Time vs Cost'
                        }
                    }
                }
            });
        }

        function saveConfiguration() {
            if (!currentProcess) {
                alert('Please select a process first');
                return;
            }

            fetch('simulation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_simulation_config',
                    process_id: currentProcess.id,
                    config: JSON.stringify(simulationConfig)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Configuration saved successfully!');
                } else {
                    alert('Error saving configuration: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }

        function exportResults() {
            // Create downloadable report
            const results = document.getElementById('resultsContent').innerHTML;
            if (!results) {
                alert('No simulation results to export. Please run a simulation first.');
                return;
            }

            const reportContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Process Simulation Report - ${currentProcess ? currentProcess.name : 'Unknown'}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 40px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .metric-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                        .result-card { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; }
                        .bottleneck-item { background: #fff3cd; padding: 8px; margin: 8px 0; border-radius: 4px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Process Simulation Report</h1>
                        <h2>${currentProcess ? currentProcess.name : 'Unknown Process'}</h2>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                    ${results}
                </body>
                </html>
            `;

            const blob = new Blob([reportContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `simulation_report_${currentProcess ? currentProcess.name.replace(/[^a-z0-9]/gi, '_') : 'unknown'}_${new Date().getTime()}.html`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Auto-save configuration when user makes changes
        let autoSaveTimeout;
        function scheduleAutoSave() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                if (currentProcess) {
                    saveConfiguration();
                }
            }, 5000); // Auto-save after 5 seconds of inactivity
        }

        // Add event listeners for auto-save
        document.addEventListener('change', (e) => {
            if (e.target.id && e.target.id.includes('_')) {
                scheduleAutoSave();
            }
        });
    </script>
</body>
</html>