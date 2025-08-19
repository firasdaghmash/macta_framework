<button class="btn btn-secondary" id="btn-validate-process">
                    ✅ Validate
                </button>
                <button class="btn btn-secondary" id="btn-zoom-fit">
                    🔍 Fit to Screen
                </button>
                <button class="btn btn-success" id="btn-proceed-assign">
                    ➡️ Proceed to Assignment
                </button>
            </div>

            <div class="status-bar">
                <span class="token">💡</span> Use the toolbar above to create and edit your process models.
                <?php if (!empty($db_error)): ?>
                    <strong>Notice:</strong> <?= htmlspecialchars($db_error) ?>
                <?php elseif (is_array($processes) && count($processes) > 0): ?>
                    Found <?= count($processes) ?> processes in database.
                <?php else: ?>
                    No processes found. Create your first process!
                <?php endif; ?>
            </div>
        </div>

        <!-- Resource Assignment Tab -->
        <div class="tab-content" id="assign-tab">
            <div class="tab-header">
                <h2>
                    <span class="tab-icon">👥</span>
                    Resource Assignment Management
                </h2>
                <p>Assign resources, costs, and time requirements to specific process tasks</p>
            </div>

            <div class="assignment-panel">
                <h3>Current Process: <span id="current-process-name">Select a process in Design tab</span></h3>
                
                <div class="assignment-grid">
                    <!-- Task Selection -->
                    <div class="assignment-form">
                        <h4>📋 Task Selection</h4>
                        <div class="task-list" id="task-list">
                            <div class="loading">Load a process to see available tasks</div>
                        </div>
                    </div>

                    <!-- Resource Assignment Form -->
                    <div class="assignment-form">
                        <h4>⚙️ Resource Assignment</h4>
                        
                        <div class="form-group">
                            <label>Selected Task:</label>
                            <input type="text" id="selected-task-display" readonly placeholder="Click a task on the left">
                        </div>

                        <div class="form-group">
                            <label>Resource:</label>
                            <select id="resource-select">
                                <option value="">Select Resource...</option>
                                <?php if (is_array($resources) && count($resources) > 0): ?>
                                    <?php foreach ($resources as $resource): ?>
                                        <option value="<?= htmlspecialchars($resource['id'] ?? '') ?>" 
                                                data-cost="<?= htmlspecialchars($resource['hourly_cost'] ?? '0') ?>"
                                                data-type="<?= htmlspecialchars($resource['type'] ?? '') ?>"
                                                data-skill="<?= htmlspecialchars($resource['skill_level'] ?? '') ?>">
                                            <?= htmlspecialchars($resource['name'] ?? 'Unknown Resource') ?> 
                                            (<?= ucfirst(htmlspecialchars($resource['type'] ?? 'unknown')) ?> - $<?= htmlspecialchars($resource['hourly_cost'] ?? '0') ?>/hr)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No resources available</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Duration (minutes):</label>
                            <input type="number" id="task-duration" value="30" min="1" placeholder="30">
                        </div>

                        <div class="form-group">
                            <label>Quantity Required:</label>
                            <input type="number" id="resource-quantity" value="1" min="1" placeholder="1">
                        </div>

                        <div class="form-group">
                            <label>Complexity Level:</label>
                            <select id="complexity-level">
                                <option value="simple">Simple</option>
                                <option value="moderate" selected>Moderate</option>
                                <option value="complex">Complex</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Priority Level:</label>
                            <select id="priority-level">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <button class="btn btn-success" id="btn-assign-resource" style="width: 100%; margin-top: 15px;">
                            ✅ Assign Resource to Task
                        </button>

                        <!-- Cost Calculation Display -->
                        <div class="cost-calculation">
                            <div class="cost-value" id="task-cost-display">$0.00</div>
                            <div>Estimated Task Cost</div>
                        </div>
                    </div>
                </div>

                <!-- Resource Templates Quick Access -->
                <div class="assignment-form" style="margin-top: 20px;">
                    <h4>🚀 Quick Assignment Templates</h4>
                    <div class="toolbar">
                        <?php if (is_array($templates) && count($templates) > 0): ?>
                            <?php foreach ($templates as $template): ?>
                                <button class="btn btn-secondary template-btn" 
                                        data-template='<?= htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8') ?>'>
                                    <?= htmlspecialchars($template['name'] ?? 'Unknown Template') ?>
                                </button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No templates available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <button class="btn btn-primary" id="btn-refresh-assignments">
                    🔄 Refresh Assignments
                </button>
                <button class="btn btn-success" id="btn-proceed-simulate">
                    ➡️ Proceed to Simulation
                </button>
                <button class="btn btn-warning" id="btn-clear-assignments">
                    🗑️ Clear All Assignments
                </button>
            </div>
        </div>

        <!-- Simulation Tab -->
        <div class="tab-content" id="simulate-tab">
            <div class="tab-header">
                <h2>
                    <span class="tab-icon">⚡</span>
                    Process Simulation
                </h2>
                <p>Run advanced simulations with assigned resources and real-time metrics</p>
            </div>

            <!-- Simulation Controls -->
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
                    🔄 Reset
                </button>
                <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                    <label>Speed:</label>
                    <input type="range" id="sim-speed" min="0.5" max="3" step="0.1" value="1" style="width: 100px;">
                    <span id="speed-display">1x</span>
                </div>
            </div>

            <!-- Simulation Viewer -->
            <div id="simulation-viewer">
                <div class="loading">Configure assignments and click Start Simulation</div>
            </div>
            
            <!-- Real-time Performance Metrics -->
            <div class="performance-metrics">
                <div class="metric-card">
                    <div class="metric-value" id="total-process-time">--</div>
                    <div class="metric-label">Total Process Time</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="total-process-cost">$--</div>
                    <div class="metric-label">Total Process Cost</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="resource-utilization">--%</div>
                    <div class="metric-label">Resource Utilization</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="efficiency-score">--</div>
                    <div class="metric-label">Efficiency Score</div>
                </div>
            </div>

            <div class="toolbar">
                <button class="btn btn-success" id="btn-proceed-analyze">
                    ➡️ Proceed to Analysis
                </button>
                <button class="btn btn-secondary" id="btn-export-simulation">
                    📤 Export Results
                </button>
            </div>
        </div>

        <!-- Analysis Tab -->
        <div class="tab-content" id="analyze-tab">
            <div class="tab-header">
                <h2>
                    <span class="tab-icon">📊</span>
                    Advanced Path Analysis
                </h2>
                <p>Comprehensive analysis of process paths with optimization recommendations</p>
            </div>

            <!-- Analysis Dashboard -->
            <div class="analysis-dashboard" id="analysis-dashboard">
                <div class="loading">Run assignments and simulation to generate analysis</div>
            </div>

            <div class="toolbar">
                <button class="btn btn-primary" id="btn-generate-analysis">
                    🔍 Generate Analysis
                </button>
                <button class="btn btn-success" id="btn-export-analysis">
                    📊 Export Report
                </button>
                <button class="btn btn-warning" id="btn-optimization-suggestions">
                    🚀 Get Optimization Suggestions
                </button>
            </div>
        </div>
    </div>

    <script>
        // Store PHP data for JavaScript with comprehensive safety checks
        const processes = <?= json_encode(is_array($processes) ? $processes : []) ?>;
        const projects = <?= json_encode(is_array($projects) ? $projects : []) ?>;
        const resources = <?= json_encode(is_array($resources) ? $resources : []) ?>;
        const templates = <?= json_encode(is_array($templates) ? $templates : []) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        
        // Debug output
        console.log('MACTA Manager Data loaded:', {
            processes: processes.length,
            projects: projects.length, 
            resources: resources.length,
            templates: templates.length,
            dbError: dbError
        });
        
        // Global variables
        let modeler = null;
        let simulationViewer = null;
        let currentXML = null;
        let currentProcessId = null;
        let selectedTaskId = null;
        let taskAssignments = {};
        let isSimulating = false;
        let simulationInterval = null;
        let animationRunCount = 0;
        
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

        // Tab switching functionality
        function switchTab(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tabName}-tab`).classList.add('active');

            // Initialize specific tab content
            setTimeout(() => {
                if (tabName === 'assign') {
                    loadProcessTasks();
                    loadTaskAssignments();
                } else if (tabName === 'simulate' && simulationViewer && currentXML) {
                    loadProcessInSimulation(currentXML);
                } else if (tabName === 'analyze') {
                    if (currentProcessId) {
                        generateProcessAnalysis();
                    }
                }
            }, 100);
        }

        // Load scripts dynamically with fallback
        function loadScript(urls, callback) {
            let currentIndex = 0;
            
            function tryNextUrl() {
                if (currentIndex >= urls.length) {
                    console.error('All CDN sources failed, showing offline message');
                    document.querySelector('#bpmn-editor .loading').innerHTML = 
                        'Unable to load BPMN editor. Please check your internet connection and refresh the page.';
                    return;
                }
                
                const script = document.createElement('script');
                script.src = urls[currentIndex];
                script.onload = callback;
                script.onerror = () => {
                    console.warn('Failed to load from:', urls[currentIndex]);
                    currentIndex++;
                    tryNextUrl();
                };
                document.head.appendChild(script);
            }
            
            tryNextUrl();
        }

        // Initialize BPMN.js
        function initializeBpmn() {
            const bpmnCdnUrls = [
                'https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/bpmn-modeler.development.js',
                'https://unpkg.com/bpmn-js@16.0.0/dist/bpmn-modeler.development.js'
            ];
            
            loadScript(bpmnCdnUrls, () => {
                try {
                    if (typeof BpmnJS === 'undefined') {
                        throw new Error('BpmnJS not loaded');
                    }
                    
                    // Initialize modeler
                    modeler = new BpmnJS({
                        container: '#bpmn-editor'
                    });
                    
                    // Initialize simulation viewer
                    simulationViewer = new BpmnJS({
                        container: '#simulation-viewer'
                    });
                    
                    // Load initial process
                    loadInitialProcess();
                    
                    console.log('✅ BPMN components initialized successfully');
                    
                } catch (error) {
                    console.error('Failed to initialize BPMN:', error);
                    document.querySelector('#bpmn-editor .loading').innerHTML = 
                        'BPMN initialization failed: ' + error.message + '<br>Please refresh the page to try again.';
                }
            });
        }

        // Load initial process
        async function loadInitialProcess() {
            try {
                let xmlToLoad = defaultBpmnXml;
                
                if (processes.length > 0 && processes[0].model_data) {
                    xmlToLoad = processes[0].model_data;
                    currentProcessId = processes[0].id;
                }
                
                currentXML = xmlToLoad;
                
                if (modeler) {
                    await modeler.importXML(xmlToLoad);
                    modeler.get('canvas').zoom('fit-viewport');
                }
                
                // Hide loading indicators
                document.querySelectorAll('.loading').forEach(el => el.style.display = 'none');
                
            } catch (error) {
                console.error('Failed to load initial process:', error);
                document.querySelector('#bpmn-editor .loading').innerHTML = 
                    'Failed to load process: ' + error.message;
            }
        }

        // Load process in simulation viewer
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

        // Load process tasks for assignment
        function loadProcessTasks() {
            if (!modeler || !currentXML) {
                document.getElementById('task-list').innerHTML = '<div class="loading">No process loaded</div>';
                return;
            }
            
            try {
                const elementRegistry = modeler.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                const tasks = elements.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask' ||
                    el.type === 'bpmn:StartEvent' ||
                    el.type === 'bpmn:EndEvent'
                );
                
                const taskListContainer = document.getElementById('task-list');
                taskListContainer.innerHTML = '';
                
                if (tasks.length === 0) {
                    taskListContainer.innerHTML = '<div class="loading">No tasks found in process</div>';
                    return;
                }
                
                tasks.forEach(task => {
                    const taskItem = document.createElement('div');
                    taskItem.className = 'task-item';
                    taskItem.dataset.taskId = task.id;
                    
                    const isAssigned = taskAssignments[task.id];
                    const statusClass = isAssigned ? 'status-assigned' : 'status-unassigned';
                    const statusText = isAssigned ? 'Assigned' : 'Unassigned';
                    
                    taskItem.innerHTML = `
                        <div class="task-info">
                            <div class="task-name">${task.businessObject.name || task.id}</div>
                            <div class="task-details">${task.type.replace('bpmn:', '')}</div>
                        </div>
                        <div class="task-status ${statusClass}">${statusText}</div>
                    `;
                    
                    taskItem.addEventListener('click', () => selectTask(task));
                    taskListContainer.appendChild(taskItem);
                });
                
            } catch (error) {
                console.error('Failed to load process tasks:', error);
                document.getElementById('task-list').innerHTML = '<div class="error">Failed to load tasks</div>';
            }
        }

        // Select a task for assignment
        function selectTask(task) {
            selectedTaskId = task.id;
            
            // Update UI
            document.querySelectorAll('.task-item').forEach(item => {
                item.classList.remove('selected');
            });
            const taskElement = document.querySelector(`[data-task-id="${task.id}"]`);
            if (taskElement) {
                taskElement.classList.add('selected');
            }
            
            // Update form
            document.getElementById('selected-task-display').value = task.businessObject.name || task.id;
            
            // Load existing assignment if available
            const assignment = taskAssignments[task.id];
            if (assignment) {
                document.getElementById('resource-select').value = assignment.resource_id;
                document.getElementById('task-duration').value = assignment.duration_minutes;
                document.getElementById('resource-quantity').value = assignment.quantity_required;
                document.getElementById('complexity-level').value = assignment.complexity_level;
                document.getElementById('priority-level').value = assignment.priority_level;
                updateCostCalculation();
            }
        }

        // Update cost calculation
        function updateCostCalculation() {
            const resourceSelect = document.getElementById('resource-select');
            const duration = parseFloat(document.getElementById('task-duration').value) || 0;
            const quantity = parseFloat(document.getElementById('resource-quantity').value) || 1;
            
            if (resourceSelect.selectedIndex > 0) {
                const selectedOption = resourceSelect.options[resourceSelect.selectedIndex];
                const hourlyRate = parseFloat(selectedOption.dataset.cost) || 0;
                const totalCost = (hourlyRate * (duration / 60) * quantity).toFixed(2);
                
                document.getElementById('task-cost-display').textContent = `${totalCost}`;
            } else {
                document.getElementById('task-cost-display').textContent = '$0.00';
            }
        }

        // Assign resource to task
        async function assignResourceToTask() {
            if (!selectedTaskId) {
                alert('Please select a task first!');
                return;
            }
            
            const resourceId = document.getElementById('resource-select').value;
            if (!resourceId) {
                alert('Please select a resource!');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'assign_resource');
            formData.append('process_id', currentProcessId || 1);
            formData.append('task_id', selectedTaskId);
            formData.append('resource_id', resourceId);
            formData.append('duration', document.getElementById('task-duration').value);
            formData.append('quantity', document.getElementById('resource-quantity').value);
            formData.append('complexity', document.getElementById('complexity-level').value);
            formData.append('priority', document.getElementById('priority-level').value);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Resource assigned successfully!');
                    
                    // Update local assignments cache
                    taskAssignments[selectedTaskId] = {
                        resource_id: resourceId,
                        duration_minutes: document.getElementById('task-duration').value,
                        quantity_required: document.getElementById('resource-quantity').value,
                        complexity_level: document.getElementById('complexity-level').value,
                        priority_level: document.getElementById('priority-level').value
                    };
                    
                    // Refresh task list
                    loadProcessTasks();
                    
                } else {
                    alert('Failed to assign resource: ' + result.message);
                }
                
            } catch (error) {
                console.error('Assignment error:', error);
                alert('Failed to assign resource ❌');
            }
        }

        // Load existing task assignments
        async function loadTaskAssignments() {
            if (!currentProcessId) return;
            
            const formData = new FormData();
            formData.append('action', 'get_task_assignments');
            formData.append('process_id', currentProcessId);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    taskAssignments = {};
                    result.assignments.forEach(assignment => {
                        taskAssignments[assignment.task_id] = assignment;
                    });
                    
                    // Refresh task list to show assignment status
                    loadProcessTasks();
                }
                
            } catch (error) {
                console.error('Failed to load assignments:', error);
            }
        }

        // Generate process analysis
        async function generateProcessAnalysis() {
            if (!currentProcessId) {
                alert('Please select a process first!');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'analyze_process');
            formData.append('process_id', currentProcessId);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayAnalysisResults(result.analysis);
                } else {
                    alert('Failed to generate analysis: ' + result.message);
                }
                
            } catch (error) {
                console.error('Analysis error:', error);
                alert('Failed to generate analysis ❌');
            }
        }

        function displayAnalysisResults(analysis) {
            const dashboard = document.getElementById('analysis-dashboard');
            
            dashboard.innerHTML = `
                <div class="analysis-card critical">
                    <div class="analysis-header">
                        <span class="analysis-icon">🔴</span>
                        <span class="analysis-title">Critical Path</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.critical_path.duration} min</div>
                        <p>Total Duration</p>
                        <div class="analysis-value">${analysis.critical_path.cost}</div>
                        <p>Total Cost | ${analysis.critical_path.tasks} tasks</p>
                    </div>
                </div>

                <div class="analysis-card time">
                    <div class="analysis-header">
                        <span class="analysis-icon">⏱️</span>
                        <span class="analysis-title">Time Consuming Path</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.time_consuming.duration} min</div>
                        <p>Longest Tasks Combined</p>
                        <small>High-duration tasks identified</small>
                    </div>
                </div>

                <div class="analysis-card resource">
                    <div class="analysis-header">
                        <span class="analysis-icon">👥</span>
                        <span class="analysis-title">Resource Intensive</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.resource_intensive.total_resources}</div>
                        <p>Total Resources Required</p>
                        <small>${analysis.resource_intensive.human_tasks} human-dependent tasks</small>
                    </div>
                </div>

                <div class="analysis-card cost">
                    <div class="analysis-header">
                        <span class="analysis-icon">💰</span>
                        <span class="analysis-title">Most Costly Path</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.costly_path.total_cost.toFixed(2)}</div>
                        <p>Highest Cost Execution</p>
                        <small>Top expensive tasks identified</small>
                    </div>
                </div>

                <div class="analysis-card ideal">
                    <div class="analysis-header">
                        <span class="analysis-icon">⭐</span>
                        <span class="analysis-title">Ideal Path</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.ideal_path.automation_level.toFixed(0)}%</div>
                        <p>Automation Level</p>
                        <div class="analysis-value">${analysis.ideal_path.cost.toFixed(2)}</div>
                        <p>Optimized Cost</p>
                    </div>
                </div>

                <div class="analysis-card frequent">
                    <div class="analysis-header">
                        <span class="analysis-icon">🔄</span>
                        <span class="analysis-title">Most Frequent Path</span>
                    </div>
                    <div class="analysis-content">
                        <div class="analysis-value">${analysis.frequent_path.frequency}%</div>
                        <p>Execution Frequency</p>
                        <small>${analysis.frequent_path.tasks} standard complexity tasks</small>
                    </div>
                </div>
            `;
        }

        // Apply template to current task
        function applyTemplate(template) {
            if (!selectedTaskId) {
                alert('Please select a task first!');
                return;
            }
            
            // Find matching resource for this template
            const matchingResource = resources.find(r => 
                r.type === template.resource_type
            );
            
            if (matchingResource) {
                document.getElementById('resource-select').value = matchingResource.id;
            }
            
            document.getElementById('task-duration').value = template.default_duration || 30;
            updateCostCalculation();
        }

        // Start simulation
        function startSimulation() {
            if (!simulationViewer || !currentXML || isSimulating) return;
            
            if (Object.keys(taskAssignments).length === 0) {
                alert('Please assign resources to tasks before starting simulation!');
                switchTab('assign');
                return;
            }
            
            isSimulating = true;
            animationRunCount++;
            
            // Update UI
            document.getElementById('btn-start-simulation').textContent = '⏸️ Running...';
            document.getElementById('btn-start-simulation').disabled = true;
            
            // Start metrics calculation
            startMetricsCalculation();
            
            // Show simple demo animation
            setTimeout(() => {
                completeSimulation();
            }, 3000);
        }

        function startMetricsCalculation() {
            let totalTime = 0;
            let totalCost = 0;
            
            // Calculate totals from assignments
            Object.values(taskAssignments).forEach(assignment => {
                totalTime += parseFloat(assignment.duration_minutes) || 0;
                
                const resource = resources.find(r => r.id == assignment.resource_id);
                if (resource) {
                    const taskCost = (resource.hourly_cost * (assignment.duration_minutes / 60) * assignment.quantity_required);
                    totalCost += taskCost;
                }
            });
            
            // Update display
            document.getElementById('total-process-time').textContent = `${Math.round(totalTime)} min`;
            document.getElementById('total-process-cost').textContent = `${totalCost.toFixed(2)}`;
            
            // Calculate utilization (simplified)
            const resourceCount = Object.keys(taskAssignments).length;
            const utilization = Math.min(100, (resourceCount / 10) * 100); // Demo calculation
            document.getElementById('resource-utilization').textContent = `${utilization.toFixed(0)}%`;
            
            // Calculate efficiency score
            const efficiency = Math.max(0, 100 - (totalTime / 60) - (totalCost / 100));
            document.getElementById('efficiency-score').textContent = `${Math.max(0, efficiency).toFixed(0)}%`;
        }

        function completeSimulation() {
            isSimulating = false;
            
            document.getElementById('btn-start-simulation').textContent = '▶️ Start Simulation';
            document.getElementById('btn-start-simulation').disabled = false;
            
            alert('🎉 Simulation completed! Check the metrics and proceed to analysis.');
        }

        function stopSimulation() {
            isSimulating = false;
            
            document.getElementById('btn-start-simulation').textContent = '▶️ Start Simulation';
            document.getElementById('btn-start-simulation').disabled = false;
        }

        // Event listeners setup
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 MACTA Manager initializing...');
            
            // Initialize BPMN when page loads
            initializeBpmn();
            
            // Process selector
            const processSelect = document.getElementById('process-select');
            if (processSelect) {
                processSelect.addEventListener('change', async (e) => {
                    const selectedValue = e.target.value;
                    
                    if (selectedValue === 'new') {
                        currentXML = defaultBpmnXml;
                        currentProcessId = null;
                        if (modeler) {
                            await modeler.importXML(currentXML);
                            modeler.get('canvas').zoom('fit-viewport');
                        }
                        
                    } else if (selectedValue) {
                        const selectedOption = e.target.selectedOptions[0];
                        const xmlData = selectedOption.dataset.xml;
                        currentProcessId = selectedValue;
                        
                        if (xmlData) {
                            currentXML = xmlData;
                            if (modeler) {
                                await modeler.importXML(currentXML);
                                modeler.get('canvas').zoom('fit-viewport');
                            }
                            
                            // Update current process name
                            const processName = selectedOption.textContent;
                            document.getElementById('current-process-name').textContent = processName;
                            
                            // Load assignments for this process
                            loadTaskAssignments();
                        }
                    }
                });
            }

            // Design tab buttons
            const btnNewProcess = document.getElementById('btn-new-process');
            if (btnNewProcess) {
                btnNewProcess.addEventListener('click', () => {
                    document.getElementById('process-select').value = 'new';
                    document.getElementById('process-select').dispatchEvent(new Event('change'));
                });
            }

            const btnSaveProcess = document.getElementById('btn-save-process');
            if (btnSaveProcess) {
                btnSaveProcess.addEventListener('click', async () => {
                    if (!modeler) {
                        alert('BPMN modeler not loaded yet. Please wait and try again.');
                        return;
                    }
                    
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
                            alert('Process saved successfully! 💾');
                            currentProcessId = result.process_id;
                            currentXML = xml;
                            location.reload();
                        } else {
                            alert('Failed to save: ' + result.message);
                        }
                        
                    } catch (error) {
                        console.error('Save error:', error);
                        alert('Failed to save process ❌');
                    }
                });
            }

            const btnClearDesigner = document.getElementById('btn-clear-designer');
            if (btnClearDesigner) {
                btnClearDesigner.addEventListener('click', async () => {
                    if (!modeler) return;
                    
                    try {
                        await modeler.importXML(defaultBpmnXml);
                        modeler.get('canvas').zoom('fit-viewport');
                        currentXML = defaultBpmnXml;
                        currentProcessId = null;
                        document.getElementById('process-select').value = 'new';
                    } catch (error) {
                        console.error('Clear error:', error);
                    }
                });
            }

            const btnValidateProcess = document.getElementById('btn-validate-process');
            if (btnValidateProcess) {
                btnValidateProcess.addEventListener('click', async () => {
                    if (!modeler) {
                        alert('BPMN modeler not loaded yet.');
                        return;
                    }
                    
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
                            alert('✅ Process validation passed!\n\n- Has start event\n- Has end event');
                        } else {
                            alert('❌ Validation failed:\n\n' + validationErrors.join('\n'));
                        }
                        
                    } catch (error) {
                        console.error('Validation error:', error);
                        alert('Validation failed: ' + error.message);
                    }
                });
            }

            const btnZoomFit = document.getElementById('btn-zoom-fit');
            if (btnZoomFit) {
                btnZoomFit.addEventListener('click', () => {
                    if (modeler) {
                        modeler.get('canvas').zoom('fit-viewport');
                    }
                });
            }

            const btnProceedAssign = document.getElementById('btn-proceed-assign');
            if (btnProceedAssign) {
                btnProceedAssign.addEventListener('click', () => {
                    if (!currentXML) {
                        alert('Please create or load a process first!');
                        return;
                    }
                    switchTab('assign');
                });
            }

            // Assignment tab buttons
            const btnAssignResource = document.getElementById('btn-assign-resource');
            if (btnAssignResource) {
                btnAssignResource.addEventListener('click', assignResourceToTask);
            }
            
            const btnRefreshAssignments = document.getElementById('btn-refresh-assignments');
            if (btnRefreshAssignments) {
                btnRefreshAssignments.addEventListener('click', () => {
                    loadTaskAssignments();
                    loadProcessTasks();
                });
            }

            const btnProceedSimulate = document.getElementById('btn-proceed-simulate');
            if (btnProceedSimulate) {
                btnProceedSimulate.addEventListener('click', () => {
                    if (Object.keys(taskAssignments).length === 0) {
                        alert('Please assign resources to tasks before simulation!');
                        return;
                    }
                    switchTab('simulate');
                });
            }

            const btnClearAssignments = document.getElementById('btn-clear-assignments');
            if (btnClearAssignments) {
                btnClearAssignments.addEventListener('click', () => {
                    if (confirm('Are you sure you want to clear all assignments?')) {
                        taskAssignments = {};
                        loadProcessTasks();
                    }
                });
            }

            // Resource form changes
            const resourceSelect = document.getElementById('resource-select');
            if (resourceSelect) {
                resourceSelect.addEventListener('change', updateCostCalculation);
            }
            
            const taskDuration = document.getElementById('task-duration');
            if (taskDuration) {
                taskDuration.addEventListener('input', updateCostCalculation);
            }
            
            const resourceQuantity = document.getElementById('resource-quantity');
            if (resourceQuantity) {
                resourceQuantity.addEventListener('input', updateCostCalculation);
            }

            // Template buttons
            document.querySelectorAll('.template-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const template = JSON.parse(btn.dataset.template);
                        applyTemplate(template);
                    } catch (error) {
                        console.error('Template error:', error);
                        alert('Failed to apply template');
                    }
                });
            });

            // Simulation controls
            const btnStartSimulation = document.getElementById('btn-start-simulation');
            if (btnStartSimulation) {
                btnStartSimulation.addEventListener('click', startSimulation);
            }
            
            const btnPauseSimulation = document.getElementById('btn-pause-simulation');
            if (btnPauseSimulation) {
                btnPauseSimulation.addEventListener('click', stopSimulation);
            }
            
            const btnStopSimulation = document.getElementById('btn-stop-simulation');
            if (btnStopSimulation) {
                btnStopSimulation.addEventListener('click', stopSimulation);
            }
            
            const btnResetSimulation = document.getElementById('btn-reset-simulation');
            if (btnResetSimulation) {
                btnResetSimulation.addEventListener('click', () => {
                    stopSimulation();
                    // Reset metrics
                    document.getElementById('total-process-time').textContent = '--';
                    document.getElementById('total-process-cost').textContent = '$--';
                    document.getElementById('resource-utilization').textContent = '--%';
                    document.getElementById('efficiency-score').textContent = '--';
                });
            }

            const btnProceedAnalyze = document.getElementById('btn-proceed-analyze');
            if (btnProceedAnalyze) {
                btnProceedAnalyze.addEventListener('click', () => {
                    switchTab('analyze');
                });
            }

            // Speed control
            const simSpeed = document.getElementById('sim-speed');
            if (simSpeed) {
                simSpeed.addEventListener('input', (e) => {
                    document.getElementById('speed-display').textContent = e.target.value + 'x';
                });
            }

            // Analysis controls
            const btnGenerateAnalysis = document.getElementById('btn-generate-analysis');
            if (btnGenerateAnalysis) {
                btnGenerateAnalysis.addEventListener('click', generateProcessAnalysis);
            }
            
            const btnExportAnalysis = document.getElementById('btn-export-analysis');
            if (btnExportAnalysis) {
                btnExportAnalysis.addEventListener('click', () => {
                    // Export analysis as JSON
                    const analysisData = {
                        timestamp: new Date().toLocaleString(),
                        process_id: currentProcessId,
                        assignments: taskAssignments
                    };
                    
                    const blob = new Blob([JSON.stringify(analysisData, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'macta_process_analysis.json';
                    a.click();
                    URL.revokeObjectURL(url);
                    
                    alert('Analysis exported successfully! 📊');
                });
            }

            const btnOptimizationSuggestions = document.getElementById('btn-optimization-suggestions');
            if (btnOptimizationSuggestions) {
                btnOptimizationSuggestions.addEventListener('click', () => {
                    const suggestions = [
                        '🤖 Automation Opportunity: Automate routine tasks to reduce time by 40%',
                        '⚡ Parallel Processing: Run independent tasks simultaneously',
                        '👥 Skill-Based Routing: Match task complexity with resource expertise',
                        '📊 Predictive Analytics: Use historical data to optimize resource allocation',
                        '🔄 Process Redesign: Eliminate redundant steps and streamline workflow'
                    ];
                    
                    alert('🚀 Optimization Suggestions:\n\n• ' + suggestions.join('\n• '));
                });
            }

            console.log('✅ MACTA Manager event listeners initialized');
        });

        console.log('🚀 Fixed MACTA Process Manager script loaded');
    </script>
</body>
</html>
                    <?php
// modules/M/index.php - Fixed MACTA Process Manager

// Initialize all variables with empty defaults
$processes = [];
$projects = [];
$resources = [];
$templates = [];
$db_error = '';
$pdo = null;

// Database connection with comprehensive error handling
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
        
        // Check if basic tables exist
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'process_models'")->rowCount();
        
        if ($tableCheck > 0) {
            // Get processes - with error handling
            try {
                $stmt = $pdo->prepare("
                    SELECT pm.*, p.name as project_name 
                    FROM process_models pm 
                    LEFT JOIN projects p ON pm.project_id = p.id 
                    ORDER BY pm.updated_at DESC
                ");
                $stmt->execute();
                $result = $stmt->fetchAll();
                $processes = is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Error fetching processes: " . $e->getMessage());
                $processes = [];
            }
            
            // Get projects - with error handling
            try {
                $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
                $stmt->execute();
                $result = $stmt->fetchAll();
                $projects = is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Error fetching projects: " . $e->getMessage());
                $projects = [];
            }
            
            // Check if enhanced tables exist
            $enhancedCheck = $pdo->query("SHOW TABLES LIKE 'enhanced_resources'")->rowCount();
            
            if ($enhancedCheck > 0) {
                // Get enhanced resources
                try {
                    $stmt = $pdo->prepare("SELECT * FROM enhanced_resources ORDER BY name");
                    $stmt->execute();
                    $result = $stmt->fetchAll();
                    $resources = is_array($result) ? $result : [];
                } catch (Exception $e) {
                    error_log("Error fetching resources: " . $e->getMessage());
                    $resources = [];
                }
                
                // Get resource templates
                try {
                    $stmt = $pdo->prepare("SELECT * FROM resource_templates ORDER BY name");
                    $stmt->execute();
                    $result = $stmt->fetchAll();
                    $templates = is_array($result) ? $result : [];
                } catch (Exception $e) {
                    error_log("Error fetching templates: " . $e->getMessage());
                    $templates = [];
                }
            } else {
                // Enhanced tables don't exist - create sample data
                $resources = [
                    ['id' => 1, 'name' => 'Business Analyst', 'type' => 'human', 'hourly_cost' => 75, 'skill_level' => 'advanced'],
                    ['id' => 2, 'name' => 'Project Manager', 'type' => 'human', 'hourly_cost' => 100, 'skill_level' => 'expert'],
                    ['id' => 3, 'name' => 'AI Assistant', 'type' => 'software', 'hourly_cost' => 15, 'skill_level' => 'expert'],
                    ['id' => 4, 'name' => 'Quality Specialist', 'type' => 'human', 'hourly_cost' => 65, 'skill_level' => 'advanced']
                ];
                
                $templates = [
                    ['id' => 1, 'name' => 'Standard Review', 'resource_type' => 'human', 'default_duration' => 30, 'default_cost' => 50],
                    ['id' => 2, 'name' => 'Expert Analysis', 'resource_type' => 'human', 'default_duration' => 60, 'default_cost' => 100],
                    ['id' => 3, 'name' => 'Automated Process', 'resource_type' => 'software', 'default_duration' => 5, 'default_cost' => 10]
                ];
                
                $db_error = 'Enhanced tables not found. Using sample data. Please run the enhanced schema installation.';
            }
        } else {
            $db_error = 'Basic MACTA tables not found. Please run the main installer first.';
        }
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Manager DB Error: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error) || !$pdo) {
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
                
                $processId = $pdo->lastInsertId();
                if (!$processId) {
                    $stmt = $pdo->prepare("SELECT id FROM process_models WHERE name = ? ORDER BY updated_at DESC LIMIT 1");
                    $stmt->execute([$_POST['name'] ?? 'Untitled Process']);
                    $processId = $stmt->fetchColumn();
                }
                
                echo json_encode(['success' => true, 'message' => 'Process saved successfully', 'process_id' => $processId]);
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
                // Check if enhanced table exists
                $enhancedCheck = $pdo->query("SHOW TABLES LIKE 'task_resource_assignments'")->rowCount();
                
                if ($enhancedCheck > 0) {
                    // Save to enhanced table
                    $stmt = $pdo->prepare("
                        INSERT INTO task_resource_assignments 
                        (process_id, task_id, resource_id, quantity_required, duration_minutes, complexity_level, priority_level) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        resource_id = VALUES(resource_id),
                        quantity_required = VALUES(quantity_required),
                        duration_minutes = VALUES(duration_minutes),
                        complexity_level = VALUES(complexity_level),
                        priority_level = VALUES(priority_level),
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    $stmt->execute([
                        $_POST['process_id'] ?? 0,
                        $_POST['task_id'] ?? '',
                        $_POST['resource_id'] ?? 0,
                        $_POST['quantity'] ?? 1,
                        $_POST['duration'] ?? 30,
                        $_POST['complexity'] ?? 'moderate',
                        $_POST['priority'] ?? 'normal'
                    ]);
                } else {
                    // Use basic table as fallback
                    $stmt = $pdo->prepare("
                        INSERT INTO process_step_resources 
                        (process_id, step_id, resource_id, quantity_required) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        resource_id = VALUES(resource_id),
                        quantity_required = VALUES(quantity_required)
                    ");
                    $stmt->execute([
                        $_POST['process_id'] ?? 0,
                        $_POST['task_id'] ?? '',
                        $_POST['resource_id'] ?? 0,
                        $_POST['quantity'] ?? 1
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Resource assigned successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_task_assignments':
            try {
                $enhancedCheck = $pdo->query("SHOW TABLES LIKE 'task_resource_assignments'")->rowCount();
                
                if ($enhancedCheck > 0) {
                    $stmt = $pdo->prepare("
                        SELECT tra.*, er.name as resource_name, er.hourly_cost, er.type as resource_type
                        FROM task_resource_assignments tra
                        LEFT JOIN enhanced_resources er ON tra.resource_id = er.id
                        WHERE tra.process_id = ?
                    ");
                } else {
                    $stmt = $pdo->prepare("
                        SELECT psr.*, r.name as resource_name, 50 as hourly_cost, 'human' as resource_type
                        FROM process_step_resources psr
                        LEFT JOIN resources r ON psr.resource_id = r.id
                        WHERE psr.process_id = ?
                    ");
                }
                
                $stmt->execute([$_POST['process_id'] ?? 0]);
                $assignments = $stmt->fetchAll() ?: [];
                echo json_encode(['success' => true, 'assignments' => $assignments]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'analyze_process':
            try {
                // Return sample analysis for demo
                $analysis = [
                    'critical_path' => ['duration' => 90, 'cost' => 150, 'tasks' => 3],
                    'time_consuming' => ['duration' => 120, 'tasks' => []],
                    'resource_intensive' => ['total_resources' => 5, 'human_tasks' => 3, 'duration' => 85],
                    'costly_path' => ['total_cost' => 200, 'most_expensive' => []],
                    'ideal_path' => ['automation_level' => 40, 'duration' => 30, 'cost' => 50],
                    'frequent_path' => ['frequency' => 70, 'duration' => 60, 'tasks' => 2]
                ];
                
                echo json_encode(['success' => true, 'analysis' => $analysis]);
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
    <title>MACTA Process Manager - Complete Solution</title>

    <style>
        :root {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--macta-dark);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .macta-logo {
            width: 50px;
            height: 50px;
            background: var(--macta-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .tab-navigation {
            display: flex;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .tab-button {
            flex: 1;
            padding: 20px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: var(--macta-dark);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-right: 1px solid var(--macta-light);
        }

        .tab-button:last-child {
            border-right: none;
        }

        .tab-button.active {
            background: var(--macta-orange);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,123,84,0.3);
        }

        .tab-button:hover:not(.active) {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .tab-icon {
            font-size: 20px;
        }

        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-content {
            display: none;
            padding: 25px;
            min-height: 70vh;
        }

        .tab-content.active {
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

        #bpmn-editor, #simulation-viewer {
            height: var(--box-height);
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            background: #fafafa;
            margin-bottom: 20px;
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
            background: var(--macta-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a3a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,123,84,0.3);
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

        .status-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            border-left: 4px solid var(--macta-orange);
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

        .assignment-panel {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid var(--macta-light);
        }

        .assignment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .assignment-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--macta-dark);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--macta-orange);
            box-shadow: 0 0 0 3px rgba(255,123,84,0.1);
        }

        .task-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--macta-light);
            border-radius: 10px;
            background: white;
        }

        .task-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-item:hover {
            background: #f8f9fa;
        }

        .task-item.selected {
            background: var(--macta-orange);
            color: white;
        }

        .task-info {
            flex: 1;
        }

        .task-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .task-details {
            font-size: 12px;
            opacity: 0.8;
        }

        .task-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-assigned {
            background: #e8f5e8;
            color: var(--macta-green);
        }

        .status-unassigned {
            background: #ffebee;
            color: var(--macta-red);
        }

        .cost-calculation {
            background: linear-gradient(135deg, var(--macta-teal), var(--macta-green));
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: center;
        }

        .cost-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

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

        .analysis-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .analysis-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid;
        }

        .analysis-card.critical { border-left-color: var(--macta-red); }
        .analysis-card.time { border-left-color: var(--macta-yellow); }
        .analysis-card.resource { border-left-color: var(--macta-green); }
        .analysis-card.cost { border-left-color: var(--macta-orange); }
        .analysis-card.ideal { border-left-color: var(--macta-teal); }
        .analysis-card.frequent { border-left-color: #6c5ce7; }

        .analysis-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .analysis-icon {
            font-size: 24px;
        }

        .analysis-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--macta-dark);
        }

        .analysis-content {
            color: #666;
        }

        .analysis-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--macta-dark);
            margin: 10px 0;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
            font-size: 18px;
            color: var(--macta-orange);
        }

        .error-message {
            background: #ffebee;
            color: var(--macta-red);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--macta-red);
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .tab-navigation {
                flex-direction: column;
            }

            .tab-button {
                border-right: none !important;
                border-bottom: 1px solid var(--macta-light);
                padding: 15px;
            }

            .tab-button:last-child {
                border-bottom: none;
            }

            .assignment-grid {
                grid-template-columns: 1fr;
            }

            .analysis-dashboard {
                grid-template-columns: 1fr;
            }

            .performance-metrics {
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
    <div class="header">
        <h1>
            <div class="macta-logo">M</div>
            MACTA Process Manager - Complete Solution
        </h1>
        <div>
            <a href="../" class="btn btn-secondary">
                <span>←</span> Back to Framework
            </a>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-button active" onclick="switchTab('design')" data-tab="design">
            <span class="tab-icon">🎨</span>
            <span>Design</span>
        </button>
        <button class="tab-button" onclick="switchTab('assign')" data-tab="assign">
            <span class="tab-icon">👥</span>
            <span>Assign Resources</span>
        </button>
        <button class="tab-button" onclick="switchTab('simulate')" data-tab="simulate">
            <span class="tab-icon">⚡</span>
            <span>Simulate</span>
        </button>
        <button class="tab-button" onclick="switchTab('analyze')" data-tab="analyze">
            <span class="tab-icon">📊</span>
            <span>Analyze</span>
        </button>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Design Tab -->
        <div class="tab-content active" id="design-tab">
            <div class="tab-header">
                <h2>
                    <span class="tab-icon">🎨</span>
                    Process Design & Modeling
                </h2>
                <p>Create and edit business process models using BPMN 2.0 standard</p>
            </div>

            <!-- Process Selector -->
            <div class="process-selector">
                <select id="process-select">
                    <option value="">Select a Process...</option>
                    <option value="new">+ Create New Process</option>
                    <?php if (is_array($processes) && count($processes) > 0): ?>
                        <?php foreach ($processes as $process): ?>
                            <option value="<?= htmlspecialchars($process['id'] ?? '') ?>" 
                                    data-xml="<?= htmlspecialchars($process['model_data'] ?? '') ?>">
                                <?= htmlspecialchars($process['name'] ?? 'Untitled Process') ?> 
                                <?php if (!empty($process['project_name'])): ?>
                                    (<?= htmlspecialchars($process['project_name']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- BPMN Editor -->
            <div id="bpmn-editor">
                <div class="loading">Loading BPMN Editor...</div>
            </div>

            <!-- Design Toolbar -->
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
                <button class="btn btn-secondary" i