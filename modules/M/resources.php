<?php
// modules/M/resources.php - Resource Management for Process Simulation
session_start();

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
            case 'add_resource':
                $stmt = $conn->prepare("
                    INSERT INTO simulation_resources 
                    (name, type, hourly_rate, availability_hours, skill_level, description) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['hourly_rate'],
                    $_POST['availability_hours'],
                    $_POST['skill_level'],
                    $_POST['description']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Resource added successfully']);
                } else {
                    throw new Exception('Failed to add resource');
                }
                break;
                
            case 'update_resource':
                $stmt = $conn->prepare("
                    UPDATE simulation_resources 
                    SET name = ?, type = ?, hourly_rate = ?, availability_hours = ?, 
                        skill_level = ?, description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['hourly_rate'],
                    $_POST['availability_hours'],
                    $_POST['skill_level'],
                    $_POST['description'],
                    $_POST['id']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
                } else {
                    throw new Exception('Failed to update resource');
                }
                break;
                
            case 'delete_resource':
                $stmt = $conn->prepare("DELETE FROM simulation_resources WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
                } else {
                    throw new Exception('Failed to delete resource');
                }
                break;
                
            case 'get_resource_analytics':
                $analytics = getResourceAnalytics($conn);
                echo json_encode(['success' => true, 'analytics' => $analytics]);
                break;
                
            case 'import_resources':
                $imported = importResourcesFromTemplate($_POST['template']);
                echo json_encode(['success' => true, 'imported' => $imported]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all resources
$stmt = $conn->prepare("SELECT * FROM simulation_resources ORDER BY type, name");
$stmt->execute();
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get resource statistics
$stmt = $conn->prepare("
    SELECT 
        type,
        COUNT(*) as count,
        AVG(hourly_rate) as avg_rate,
        AVG(availability_hours) as avg_availability
    FROM simulation_resources 
    GROUP BY type
");
$stmt->execute();
$statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getResourceAnalytics($conn) {
    $analytics = [];
    
    // Cost analysis by type
    $stmt = $conn->prepare("
        SELECT type, SUM(hourly_rate * availability_hours) as total_daily_cost
        FROM simulation_resources 
        GROUP BY type
    ");
    $stmt->execute();
    $analytics['costByType'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Skill distribution
    $stmt = $conn->prepare("
        SELECT skill_level, COUNT(*) as count
        FROM simulation_resources 
        GROUP BY skill_level
    ");
    $stmt->execute();
    $analytics['skillDistribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Most expensive resources
    $stmt = $conn->prepare("
        SELECT name, type, hourly_rate
        FROM simulation_resources 
        ORDER BY hourly_rate DESC
        LIMIT 5
    ");
    $stmt->execute();
    $analytics['expensiveResources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $analytics;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Management - MACTA Framework</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .resources-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        .resources-main {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .resources-sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .resource-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.2s;
        }
        
        .resource-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .resource-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .resource-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .type-human { background: #e3f2fd; color: #1976d2; }
        .type-equipment { background: #f3e5f5; color: #7b1fa2; }
        .type-software { background: #e8f5e8; color: #388e3c; }
        .type-material { background: #fff3e0; color: #f57c00; }
        
        .skill-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .skill-beginner { background: #ffebee; color: #c62828; }
        .skill-intermediate { background: #fff8e1; color: #ef6c00; }
        .skill-expert { background: #e8f5e8; color: #2e7d32; }
        .skill-specialist { background: #e1f5fe; color: #0277bd; }
        
        .resource-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .detail-item {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .detail-value {
            font-weight: bold;
            font-size: 16px;
            color: var(--primary-color);
        }
        
        .detail-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .resource-actions {
            margin-top: 10px;
            display: flex;
            gap: 5px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .analytics-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .chart-container {
            height: 200px;
            margin: 15px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
        }
        
        input, select, textarea {
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
        }
        
        .btn-icon {
            padding: 6px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > 
                <a href="index.php">Process Modeling</a> > 
                Resource Management
            </div>
            <div>
                <button class="btn btn-primary" onclick="openResourceModal()">➕ Add Resource</button>
                <button class="btn btn-secondary" onclick="exportResources()">📊 Export</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="resources-container">
            <!-- Main Resources Area -->
            <div class="resources-main">
                <h2>🛠️ Resource Management</h2>
                
                <!-- Filters -->
                <div class="filters">
                    <label>Filter by Type:</label>
                    <select id="typeFilter" onchange="filterResources()">
                        <option value="">All Types</option>
                        <option value="human">Human</option>
                        <option value="equipment">Equipment</option>
                        <option value="software">Software</option>
                        <option value="material">Material</option>
                    </select>
                    
                    <label>Filter by Skill:</label>
                    <select id="skillFilter" onchange="filterResources()">
                        <option value="">All Skills</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="expert">Expert</option>
                        <option value="specialist">Specialist</option>
                    </select>
                    
                    <label>Search:</label>
                    <input type="text" id="searchInput" placeholder="Search resources..." onkeyup="filterResources()">
                </div>

                <!-- Resources List -->
                <div id="resourcesList">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-card" data-type="<?php echo $resource['type']; ?>" data-skill="<?php echo $resource['skill_level']; ?>">
                            <div class="resource-header">
                                <h4><?php echo htmlspecialchars($resource['name']); ?></h4>
                                <div>
                                    <span class="resource-type type-<?php echo $resource['type']; ?>">
                                        <?php echo ucfirst($resource['type']); ?>
                                    </span>
                                    <span class="skill-badge skill-<?php echo $resource['skill_level']; ?>">
                                        <?php echo ucfirst($resource['skill_level']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($resource['description']): ?>
                                <p style="margin: 10px 0; font-size: 14px; color: #6c757d;">
                                    <?php echo htmlspecialchars($resource['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="resource-details">
                                <div class="detail-item">
                                    <div class="detail-value">$<?php echo number_format($resource['hourly_rate'], 2); ?></div>
                                    <div class="detail-label">Hourly Rate</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $resource['availability_hours']; ?>h</div>
                                    <div class="detail-label">Daily Availability</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value">$<?php echo number_format($resource['hourly_rate'] * $resource['availability_hours'], 2); ?></div>
                                    <div class="detail-label">Daily Cost</div>
                                </div>
                            </div>
                            
                            <div class="resource-actions">
                                <button class="btn btn-sm btn-secondary btn-icon" onclick="editResource(<?php echo htmlspecialchars(json_encode($resource)); ?>)">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-sm btn-danger btn-icon" onclick="deleteResource(<?php echo $resource['id']; ?>)">
                                    🗑️ Delete
                                </button>
                                <button class="btn btn-sm btn-primary btn-icon" onclick="assignToProcess(<?php echo $resource['id']; ?>)">
                                    🔗 Assign
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="resources-sidebar">
                <h3>📊 Analytics</h3>
                
                <!-- Statistics Overview -->
                <div class="analytics-card">
                    <h4>Resource Overview</h4>
                    <div class="stats-grid">
                        <?php foreach ($statistics as $stat): ?>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stat['count']; ?></div>
                                <div class="stat-label"><?php echo ucfirst($stat['type']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cost Distribution Chart -->
                <div class="analytics-card">
                    <h4>Cost Distribution</h4>
                    <div class="chart-container">
                        <canvas id="costChart"></canvas>
                    </div>
                </div>

                <!-- Skill Level Distribution -->
                <div class="analytics-card">
                    <h4>Skill Distribution</h4>
                    <div class="chart-container">
                        <canvas id="skillChart"></canvas>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="analytics-card">
                    <h4>Quick Actions</h4>
                    <button class="btn btn-sm btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="loadResourceTemplate('consulting')">
                        📋 Load Consulting Template
                    </button>
                    <button class="btn btn-sm btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="loadResourceTemplate('manufacturing')">
                        🏭 Load Manufacturing Template
                    </button>
                    <button class="btn btn-sm btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="loadResourceTemplate('software')">
                        💻 Load Software Team Template
                    </button>
                    <button class="btn btn-sm btn-secondary" style="width: 100%;" onclick="optimizeResources()">
                        ⚡ Optimize Resources
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Modal -->
    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Resource</h3>
            <form id="resourceForm">
                <input type="hidden" id="resourceId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="resourceName">Name *</label>
                        <input type="text" id="resourceName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="resourceType">Type *</label>
                        <select id="resourceType" required>
                            <option value="">Select Type</option>
                            <option value="human">Human</option>
                            <option value="equipment">Equipment</option>
                            <option value="software">Software</option>
                            <option value="material">Material</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="hourlyRate">Hourly Rate ($) *</label>
                        <input type="number" id="hourlyRate" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="availabilityHours">Availability (hours/day) *</label>
                        <input type="number" id="availabilityHours" step="0.5" min="0" max="24" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="skillLevel">Skill Level</label>
                        <select id="skillLevel">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="expert">Expert</option>
                            <option value="specialist">Specialist</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="resourceDescription">Description</label>
                        <textarea id="resourceDescription" rows="3"></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeResourceModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Resource</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEditId = null;

        // Load analytics charts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadResourceAnalytics();
        });

        function openResourceModal(resource = null) {
            const modal = document.getElementById('resourceModal');
            const title = document.getElementById('modalTitle');
            
            if (resource) {
                title.textContent = 'Edit Resource';
                fillResourceForm(resource);
                currentEditId = resource.id;
            } else {
                title.textContent = 'Add New Resource';
                document.getElementById('resourceForm').reset();
                currentEditId = null;
            }
            
            modal.style.display = 'block';
        }

        function closeResourceModal() {
            document.getElementById('resourceModal').style.display = 'none';
            currentEditId = null;
        }

        function fillResourceForm(resource) {
            document.getElementById('resourceId').value = resource.id;
            document.getElementById('resourceName').value = resource.name;
            document.getElementById('resourceType').value = resource.type;
            document.getElementById('hourlyRate').value = resource.hourly_rate;
            document.getElementById('availabilityHours').value = resource.availability_hours;
            document.getElementById('skillLevel').value = resource.skill_level;
            document.getElementById('resourceDescription').value = resource.description || '';
        }

        function editResource(resource) {
            openResourceModal(resource);
        }

        function deleteResource(id) {
            if (!confirm('Are you sure you want to delete this resource?')) {
                return;
            }

            fetch('resources.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'delete_resource',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }

        // Handle form submission
        document.getElementById('resourceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', currentEditId ? 'update_resource' : 'add_resource');
            formData.append('name', document.getElementById('resourceName').value);
            formData.append('type', document.getElementById('resourceType').value);
            formData.append('hourly_rate', document.getElementById('hourlyRate').value);
            formData.append('availability_hours', document.getElementById('availabilityHours').value);
            formData.append('skill_level', document.getElementById('skillLevel').value);
            formData.append('description', document.getElementById('resourceDescription').value);
            
            if (currentEditId) {
                formData.append('id', currentEditId);
            }

            fetch('resources.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeResourceModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        });

        function filterResources() {
            const typeFilter = document.getElementById('typeFilter').value;
            const skillFilter = document.getElementById('skillFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            const cards = document.querySelectorAll('.resource-card');
            
            cards.forEach(card => {
                const type = card.dataset.type;
                const skill = card.dataset.skill;
                const name = card.querySelector('h4').textContent.toLowerCase();
                const description = card.querySelector('p')?.textContent.toLowerCase() || '';
                
                let show = true;
                
                if (typeFilter && type !== typeFilter) show = false;
                if (skillFilter && skill !== skillFilter) show = false;
                if (searchTerm && !name.includes(searchTerm) && !description.includes(searchTerm)) show = false;
                
                card.style.display = show ? 'block' : 'none';
            });
        }

        function loadResourceAnalytics() {
            fetch('resources.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_resource_analytics' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    createCostChart(data.analytics.costByType);
                    createSkillChart(data.analytics.skillDistribution);
                }
            })
            .catch(error => console.error('Error loading analytics:', error));
        }

        function createCostChart(costData) {
            const ctx = document.getElementById('costChart').getContext('2d');
            
            const labels = costData.map(item => item.type.charAt(0).toUpperCase() + item.type.slice(1));
            const data = costData.map(item => parseFloat(item.total_daily_cost));
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': 
         + context.parsed.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }

        function createSkillChart(skillData) {
            const ctx = document.getElementById('skillChart').getContext('2d');
            
            const labels = skillData.map(item => item.skill_level.charAt(0).toUpperCase() + item.skill_level.slice(1));
            const data = skillData.map(item => parseInt(item.count));
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Resources',
                        data: data,
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(23, 162, 184, 0.7)'
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function loadResourceTemplate(templateType) {
            const templates = {
                consulting: [
                    { name: 'Senior Consultant', type: 'human', rate: 150, hours: 8, skill: 'expert' },
                    { name: 'Business Analyst', type: 'human', rate: 95, hours: 8, skill: 'expert' },
                    { name: 'Project Coordinator', type: 'human', rate: 65, hours: 8, skill: 'intermediate' },
                    { name: 'Research Assistant', type: 'human', rate: 45, hours: 8, skill: 'beginner' }
                ],
                manufacturing: [
                    { name: 'Production Manager', type: 'human', rate: 85, hours: 8, skill: 'expert' },
                    { name: 'Quality Engineer', type: 'human', rate: 75, hours: 8, skill: 'expert' },
                    { name: 'Machine Operator', type: 'human', rate: 35, hours: 8, skill: 'intermediate' },
                    { name: 'Assembly Line', type: 'equipment', rate: 25, hours: 16, skill: 'intermediate' },
                    { name: 'Quality Control System', type: 'equipment', rate: 15, hours: 24, skill: 'expert' }
                ],
                software: [
                    { name: 'Senior Developer', type: 'human', rate: 120, hours: 8, skill: 'expert' },
                    { name: 'Frontend Developer', type: 'human', rate: 90, hours: 8, skill: 'expert' },
                    { name: 'Backend Developer', type: 'human', rate: 95, hours: 8, skill: 'expert' },
                    { name: 'DevOps Engineer', type: 'human', rate: 110, hours: 8, skill: 'expert' },
                    { name: 'QA Tester', type: 'human', rate: 65, hours: 8, skill: 'intermediate' },
                    { name: 'Development Environment', type: 'software', rate: 20, hours: 24, skill: 'intermediate' }
                ]
            };

            if (!templates[templateType]) {
                alert('Template not found');
                return;
            }

            if (!confirm(`This will add ${templates[templateType].length} resources from the ${templateType} template. Continue?`)) {
                return;
            }

            let added = 0;
            const resources = templates[templateType];
            
            function addNextResource(index) {
                if (index >= resources.length) {
                    alert(`Successfully added ${added} resources from ${templateType} template!`);
                    location.reload();
                    return;
                }

                const resource = resources[index];
                const formData = new FormData();
                formData.append('action', 'add_resource');
                formData.append('name', resource.name);
                formData.append('type', resource.type);
                formData.append('hourly_rate', resource.rate);
                formData.append('availability_hours', resource.hours);
                formData.append('skill_level', resource.skill);
                formData.append('description', `Added from ${templateType} template`);

                fetch('resources.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        added++;
                    }
                    addNextResource(index + 1);
                })
                .catch(error => {
                    console.error('Error adding resource:', error);
                    addNextResource(index + 1);
                });
            }

            addNextResource(0);
        }

        function assignToProcess(resourceId) {
            // This would typically open a modal to select process and step
            alert('Resource assignment feature will be integrated with the process builder. Resource ID: ' + resourceId);
        }

        function optimizeResources() {
            // Simple optimization suggestions
            const suggestions = [
                '💡 Consider cross-training intermediate resources to expert level',
                '💡 Equipment with low utilization could be shared across processes',
                '💡 Software licenses might be optimized based on actual usage',
                '💡 High-cost expert resources could mentor junior staff for efficiency',
                '💡 Automated tools could replace some manual tasks'
            ];

            alert('Resource Optimization Suggestions:\n\n' + suggestions.join('\n'));
        }

        function exportResources() {
            // Create CSV export of all resources
            const resources = [];
            document.querySelectorAll('.resource-card').forEach(card => {
                if (card.style.display !== 'none') {
                    const name = card.querySelector('h4').textContent;
                    const type = card.querySelector('.resource-type').textContent;
                    const skill = card.querySelector('.skill-badge').textContent;
                    const details = card.querySelectorAll('.detail-value');
                    
                    resources.push({
                        name: name,
                        type: type,
                        skill: skill,
                        hourlyRate: details[0].textContent,
                        availability: details[1].textContent,
                        dailyCost: details[2].textContent
                    });
                }
            });

            let csv = 'Name,Type,Skill Level,Hourly Rate,Daily Availability,Daily Cost\n';
            resources.forEach(resource => {
                csv += `"${resource.name}","${resource.type}","${resource.skill}","${resource.hourlyRate}","${resource.availability}","${resource.dailyCost}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `resources_export_${new Date().getTime()}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resourceModal');
            if (event.target === modal) {
                closeResourceModal();
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'n':
                        e.preventDefault();
                        openResourceModal();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.getElementById('searchInput').focus();
                        break;
                }
            }
            if (e.key === 'Escape') {
                closeResourceModal();
            }
        });
    </script>
</body>
</html>