<?php
// modules/M/resources.php - Resource Assignment Sub-page
header('Content-Type: text/html; charset=utf-8');

// Handle AJAX requests for resource assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'assign_resource':
                // Simulate resource assignment (in real implementation, save to database)
                echo json_encode(['success' => true, 'message' => 'Resource assigned successfully']);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<div class="tab-header">
    <h2>
        <span class="tab-icon">👥</span>
        Advanced Resource Assignment
    </h2>
    <p>Assign resources, roles, and responsibilities to process steps with detailed analysis</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div class="resource-form">
        <h3>📋 Task Resource Configuration</h3>
        
        <div class="form-group">
            <label>Select Task:</label>
            <select id="task-name">
                <option value="">Select Task...</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Resource Type:</label>
            <select id="resourceType">
                <option value="human">👤 Human Resource</option>
                <option value="machine">🤖 Machine/Equipment</option>
                <option value="hybrid">⚡ Hybrid (Human + Machine)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Assigned User:</label>
            <select id="assigned-user">
                <option value="">Select User...</option>
                <option value="john.doe">John Doe - Process Analyst</option>
                <option value="jane.smith">Jane Smith - Operations Manager</option>
                <option value="mike.wilson">Mike Wilson - Quality Specialist</option>
                <option value="sarah.connor">Sarah Connor - Team Lead</option>
                <option value="alex.murphy">Alex Murphy - Senior Consultant</option>
            </select>
        </div>

        <div class="form-group">
            <label>Duration (minutes):</label>
            <input type="number" id="task-duration" value="30" min="1">
        </div>

        <div class="form-group">
            <label>Number of Resources:</label>
            <input type="number" id="resource-count" value="1" min="1">
        </div>

        <div class="form-group">
            <label>Hourly Cost ($):</label>
            <input type="number" id="hourly-cost" value="50" min="0" step="0.01">
        </div>

        <div class="form-group">
            <label>Skill Level:</label>
            <select id="skill-level">
                <option value="entry">Entry Level</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="expert">Expert</option>
            </select>
        </div>

        <div class="form-group">
            <label>Priority Level:</label>
            <select id="priority-level">
                <option value="">Priority Level...</option>
                <option value="low">Low Priority</option>
                <option value="medium">Medium Priority</option>
                <option value="high">High Priority</option>
                <option value="critical">Critical</option>
            </select>
        </div>

        <div class="form-group">
            <label>Required Skills:</label>
            <input type="text" id="task-skills" placeholder="e.g., BPMN, Analysis, Communication">
        </div>

        <button class="btn btn-success" id="btn-assign-resource">
            ✅ Assign Resource
        </button>
        <button class="btn btn-warning" id="btn-set-default">
            ⭐ Set as Default
        </button>
    </div>

    <div class="resource-form">
        <h3>📊 Resource Analytics & Templates</h3>
        
        <div class="form-group">
            <label>Resource Template:</label>
            <select id="resource-template">
                <option value="">Select Template</option>
                <option value="analyst">Business Analyst</option>
                <option value="developer">Software Developer</option>
                <option value="manager">Project Manager</option>
                <option value="automation">Automation System</option>
            </select>
        </div>

        <div style="background: white; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <h4>💰 Current Task Calculation:</h4>
            <div style="margin: 10px 0;">
                <strong>Total Cost:</strong> $<span id="total-cost">25.00</span>
            </div>
            <div style="margin: 10px 0;">
                <strong>Resource Utilization:</strong> <span id="utilization">100%</span>
            </div>
            <div style="margin: 10px 0;">
                <strong>Estimated Queue Time:</strong> <span id="queue-time">5 min</span>
            </div>
        </div>

        <h4>📈 Arrival Rate Configuration</h4>
        <div class="form-group">
            <label>Arrival Rate (per hour):</label>
            <input type="number" id="arrival-rate" value="2" min="0.1" step="0.1">
        </div>

        <div class="form-group">
            <label>Process Type:</label>
            <select id="process-type">
                <option value="standard">Standard Process</option>
                <option value="priority">Priority Process</option>
                <option value="batch">Batch Process</option>
                <option value="adhoc">Ad-hoc Process</option>
            </select>
        </div>

        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 15px;">
            <h4>🎯 Optimization Suggestions:</h4>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Consider automation for repetitive tasks</li>
                <li>Implement parallel processing where possible</li>
                <li>Balance resource allocation across peak hours</li>
                <li>Use skill-based routing for complex tasks</li>
            </ul>
        </div>
    </div>
</div>

<!-- Resource Assignment History -->
<div class="resource-history" style="margin-top: 30px;">
    <h3>📋 Resource Assignment History</h3>
    <div class="history-table">
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
            <thead style="background: var(--htt-blue); color: white;">
                <tr>
                    <th style="padding: 12px; text-align: left;">Task</th>
                    <th style="padding: 12px; text-align: left;">Assigned To</th>
                    <th style="padding: 12px; text-align: left;">Duration</th>
                    <th style="padding: 12px; text-align: left;">Cost</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                </tr>
            </thead>
            <tbody id="assignment-history">
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #666;">
                        No resource assignments yet. Start by assigning resources to tasks above.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Resource Assignment Styles */
.resource-form {
    background: var(--htt-light-gray);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid var(--htt-gray);
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

.form-group input, .form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--macta-light);
    border-radius: 8px;
    font-size: 14px;
    background: white;
}

.form-group input:focus, .form-group select:focus {
    border-color: var(--htt-blue);
    outline: none;
    box-shadow: 0 0 0 2px rgba(30,136,229,0.2);
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

.resource-history {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid var(--macta-light);
}

.history-table table {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.history-table tbody tr {
    border-bottom: 1px solid #eee;
}

.history-table tbody tr:hover {
    background-color: #f8f9fa;
}

.history-table td {
    padding: 12px;
}

@media (max-width: 768px) {
    .tab-content > div:first-of-type {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Resources sub-page specific JavaScript
let assignmentHistory = [];

// Load process tasks from current XML
function loadProcessTasks() {
    // Try to get process XML from sessionStorage or from a global variable
    const currentXML = sessionStorage.getItem('currentProcessXML');
    
    if (currentXML) {
        try {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(currentXML, 'text/xml');
            
            // Extract tasks from BPMN XML
            const tasks = xmlDoc.getElementsByTagName('*');
            const taskSelect = document.getElementById('task-name');
            
            if (taskSelect) {
                // Clear existing options except first
                taskSelect.innerHTML = '<option value="">Select Task...</option>';
                
                for (let task of tasks) {
                    if (task.tagName.includes('task') || 
                        task.tagName.includes('startEvent') || 
                        task.tagName.includes('endEvent')) {
                        
                        const name = task.getAttribute('name') || task.getAttribute('id') || 'Unnamed Task';
                        const taskType = task.tagName.replace('bpmn2:', '').replace('bpmn:', '');
                        
                        const option = document.createElement('option');
                        option.value = task.getAttribute('id') || name;
                        option.textContent = `${name} (${taskType})`;
                        taskSelect.appendChild(option);
                    }
                }
            }
            
        } catch (error) {
            console.error('Failed to parse BPMN XML:', error);
        }
    } else {
        console.log('No current process XML available');
    }
}

function applyResourceSettings() {
    const duration = parseFloat(document.getElementById('task-duration').value) || 30;
    const cost = parseFloat(document.getElementById('hourly-cost').value) || 50;
    const resources = parseInt(document.getElementById('resource-count').value) || 1;
    const arrivalRate = parseFloat(document.getElementById('arrival-rate').value) || 2;
    
    // Calculate total cost
    const totalCost = (cost * resources * duration / 60).toFixed(2);
    document.getElementById('total-cost').textContent = totalCost;

    // Calculate utilization
    const serviceRate = 60 / duration;
    const utilization = Math.min((arrivalRate / serviceRate * 100), 100).toFixed(0);
    document.getElementById('utilization').textContent = utilization + '%';

    // Calculate queue time
    const queueTime = utilization > 80 ? Math.round(duration * 0.3) : Math.round(duration * 0.1);
    document.getElementById('queue-time').textContent = queueTime + ' min';

    console.log('Resource settings applied:', { duration, cost, resources, totalCost });
}

function loadResourceTemplate() {
    const templateName = document.getElementById('resource-template').value;
    const templates = {
        'analyst': { duration: 45, cost: 75, resources: 1, skillLevel: 'advanced' },
        'developer': { duration: 120, cost: 85, resources: 1, skillLevel: 'expert' },
        'manager': { duration: 30, cost: 100, resources: 1, skillLevel: 'expert' },
        'automation': { duration: 5, cost: 10, resources: 1, skillLevel: 'expert' }
    };

    if (templates[templateName]) {
        const template = templates[templateName];
        document.getElementById('task-duration').value = template.duration;
        document.getElementById('hourly-cost').value = template.cost;
        document.getElementById('resource-count').value = template.resources;
        document.getElementById('skill-level').value = template.skillLevel;
        
        applyResourceSettings();
        console.log('Template loaded:', templateName);
    }
}

function addToAssignmentHistory(assignment) {
    assignmentHistory.push({
        ...assignment,
        id: Date.now(),
        timestamp: new Date().toLocaleString()
    });
    
    updateHistoryTable();
}

function updateHistoryTable() {
    const tbody = document.getElementById('assignment-history');
    if (!tbody) return;
    
    if (assignmentHistory.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="padding: 20px; text-align: center; color: #666;">
                    No resource assignments yet. Start by assigning resources to tasks above.
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = '';
    
    assignmentHistory.slice(-10).reverse().forEach(assignment => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${assignment.taskName}</td>
            <td>${assignment.assignedUser}</td>
            <td>${assignment.duration} min</td>
            <td>$${assignment.totalCost}</td>
            <td><span style="color: var(--macta-green); font-weight: bold;">✅ Assigned</span></td>
        `;
        tbody.appendChild(row);
    });
}

// Event listeners
document.addEventListener('tabContentLoaded', function(e) {
    if (e.detail.tabName === 'resources') {
        console.log('👥 Resources tab content loaded');
        
        // Load process tasks
        loadProcessTasks();
        
        // Attach event listeners
        attachResourceEventListeners();
        
        // Initial calculation
        applyResourceSettings();
    }
});

function attachResourceEventListeners() {
    // Resource template loading
    document.getElementById('resource-template')?.addEventListener('change', loadResourceTemplate);

    // Real-time resource calculation
    ['task-duration', 'hourly-cost', 'resource-count', 'arrival-rate'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', applyResourceSettings);
    });

    // Assign resource button
    document.getElementById('btn-assign-resource')?.addEventListener('click', async () => {
        const taskName = document.getElementById('task-name').value;
        const assignedUser = document.getElementById('assigned-user').value;
        const duration = document.getElementById('task-duration').value;
        const skills = document.getElementById('task-skills').value;
        const totalCost = document.getElementById('total-cost').textContent;
        
        if (!taskName || !assignedUser) {
            alert('Please fill in task name and assigned user! 📋');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'assign_resource');
            formData.append('task_name', taskName);
            formData.append('assigned_user', assignedUser);
            formData.append('duration', duration);
            formData.append('skills', skills);
            
            const response = await fetch('resources.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ MACTA Resource assigned successfully!\n\nTask: ${taskName}\nAssigned to: ${assignedUser}\nDuration: ${duration} minutes\nSkills: ${skills}`);
                
                // Add to history
                addToAssignmentHistory({
                    taskName: document.getElementById('task-name').options[document.getElementById('task-name').selectedIndex].text,
                    assignedUser: document.getElementById('assigned-user').options[document.getElementById('assigned-user').selectedIndex].text,
                    duration: duration,
                    totalCost: totalCost
                });
                
                // Clear form
                document.getElementById('task-name').value = '';
                document.getElementById('assigned-user').value = '';
                document.getElementById('task-skills').value = '';
            } else {
                alert('Failed to assign resource: ' + result.message);
            }
            
        } catch (error) {
            console.error('Assignment error:', error);
            alert('Failed to assign resource ❌');
        }
    });

    document.getElementById('btn-set-default')?.addEventListener('click', function() {
        const template = {
            duration: document.getElementById('task-duration').value,
            cost: document.getElementById('hourly-cost').value,
            resources: document.getElementById('resource-count').value,
            skillLevel: document.getElementById('skill-level').value,
            resourceType: document.getElementById('resourceType').value
        };
        
        localStorage.setItem('mactaDefaultResourceTemplate', JSON.stringify(template));
        alert('⭐ MACTA settings saved as default template!');
    });
}

console.log('👥 Resources sub-page script loaded');