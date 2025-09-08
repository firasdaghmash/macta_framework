<?php
// modules/M/resources.php - MACTA Resources Management Page
header('Content-Type: text/html; charset=utf-8');

$resources = array();
$projects = array();
$project_resources = array();
$processes = array();
$db_error = '';
$selected_project = '';

if (isset($_GET['project_id'])) {
    $selected_project = $_GET['project_id'];
}

try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        
        // Handle AJAX requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            
            try {
                if ($action === 'add_resource') {
                    $stmt = $pdo->prepare("INSERT INTO enhanced_resources (name, type, hourly_cost, skill_level, availability, max_concurrent_tasks, department, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute(array(
                        $_POST['name'],
                        $_POST['type'],
                        $_POST['hourly_cost'],
                        $_POST['skill_level'],
                        isset($_POST['availability']) ? $_POST['availability'] : 100,
                        1,
                        isset($_POST['department']) ? $_POST['department'] : '',
                        isset($_POST['location']) ? $_POST['location'] : ''
                    ));
                    echo json_encode(array('success' => true, 'message' => 'Resource added successfully'));
                    
                } elseif ($action === 'update_resource') {
                    $stmt = $pdo->prepare("UPDATE enhanced_resources SET name=?, type=?, hourly_cost=?, skill_level=?, availability=?, department=?, location=? WHERE id=?");
                    $stmt->execute(array(
                        $_POST['name'],
                        $_POST['type'],
                        $_POST['hourly_cost'],
                        $_POST['skill_level'],
                        $_POST['availability'],
                        $_POST['department'],
                        $_POST['location'],
                        $_POST['id']
                    ));
                    echo json_encode(array('success' => true, 'message' => 'Resource updated successfully'));
                    
                } elseif ($action === 'delete_resource') {
                    $stmt = $pdo->prepare("DELETE FROM enhanced_resources WHERE id=?");
                    $stmt->execute(array($_POST['id']));
                    echo json_encode(array('success' => true, 'message' => 'Resource deleted successfully'));
                }
                
            } catch (Exception $e) {
                echo json_encode(array('success' => false, 'message' => $e->getMessage()));
            }
            exit;
        }
        
        // Get all global resources
        $stmt = $pdo->prepare("SELECT * FROM enhanced_resources ORDER BY name");
        $stmt->execute();
        $resources = $stmt->fetchAll();
        
        // Get all projects
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA Resources DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA - Resources Management</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #FF6B35;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .tab-button.active {
            background: white;
            border-bottom: 3px solid #FF6B35;
            color: #FF6B35;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-orange { background: #FF6B35; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-human { background: #d4edda; color: #155724; }
        .badge-machine { background: #fff3cd; color: #856404; }
        .badge-hybrid { background: #d1ecf1; color: #0c5460; }
        .badge-software { background: #f8d7da; color: #721c24; }
        
        .badge-entry { background: #f8d7da; color: #721c24; }
        .badge-intermediate { background: #fff3cd; color: #856404; }
        .badge-advanced { background: #d4edda; color: #155724; }
        .badge-expert { background: #d1ecf1; color: #0c5460; }
        
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
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .status-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>MACTA - Resources Management</h2>
            <p>Comprehensive resource management for projects and processes</p>
        </div>

        <div id="status-message" class="status-message"></div>

        <div class="tabs">
            <button class="tab-button active" onclick="showTab(event, 'global-resources')">Global Resources</button>
            <button class="tab-button" onclick="showTab(event, 'project-resources')">Project Resources</button>
            <button class="tab-button" onclick="showTab(event, 'process-resources')">Process Resources</button>
            <button class="tab-button" onclick="showTab(event, 'summary')">Summary</button>
        </div>

        <!-- Global Resources Tab -->
        <div id="global-resources" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    Global Resource Library
                    <button class="btn btn-orange" style="float: right;" onclick="openAddModal()">Add New Resource</button>
                </div>
                <div class="card-body">
                    <?php if ($db_error): ?>
                        <div class="status-message error" style="display: block;">
                            <strong>Database Error:</strong> <?php echo htmlspecialchars($db_error); ?>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Hourly Cost</th>
                                    <th>Skill Level</th>
                                    <th>Availability</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $resource): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resource['name']); ?></td>
                                        <td><span class="badge badge-<?php echo htmlspecialchars($resource['type']); ?>"><?php echo htmlspecialchars($resource['type']); ?></span></td>
                                        <td>$<?php echo number_format($resource['hourly_cost'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo htmlspecialchars($resource['skill_level']); ?>"><?php echo htmlspecialchars($resource['skill_level']); ?></span></td>
                                        <td><?php echo number_format($resource['availability'], 1); ?>%</td>
                                        <td><?php echo htmlspecialchars($resource['department']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editRes(<?php echo $resource['id']; ?>)">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteRes(<?php echo $resource['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Project Resources Tab -->
        <div id="project-resources" class="tab-content">
            <div class="card">
                <div class="card-header">Project Resource Assignment</div>
                <div class="card-body">
                    <p>Project resource assignment functionality will be implemented here.</p>
                </div>
            </div>
        </div>

        <!-- Process Resources Tab -->
        <div id="process-resources" class="tab-content">
            <div class="card">
                <div class="card-header">Process Resource Assignment</div>
                <div class="card-body">
                    <p>Process resource assignment functionality will be implemented here.</p>
                </div>
            </div>
        </div>

        <!-- Summary Tab -->
        <div id="summary" class="tab-content">
            <div class="card">
                <div class="card-header">Resource Summary</div>
                <div class="card-body">
                    <p>Total Resources: <?php echo count($resources); ?></p>
                    <p>Active Projects: <?php echo count($projects); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div id="resource-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modal-title">Add New Resource</h3>
            
            <form id="resource-form">
                <input type="hidden" id="resource-id" name="id">
                
                <div class="form-group">
                    <label>Resource Name:</label>
                    <input type="text" id="resource-name" name="name" required placeholder="e.g., CSE, CAD Designer">
                </div>
                
                <div class="form-group">
                    <label>Type:</label>
                    <select id="resource-type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="human">Human</option>
                        <option value="machine">Machine</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="software">Software</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Hourly Cost ($):</label>
                    <input type="number" id="resource-cost" name="hourly_cost" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Skill Level:</label>
                    <select id="resource-skill" name="skill_level" required>
                        <option value="">Select Skill Level</option>
                        <option value="entry">Entry</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Availability (%):</label>
                    <input type="number" id="resource-availability" name="availability" value="100" min="0" max="100" step="0.1">
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" id="resource-department" name="department" placeholder="e.g., Engineering, Design">
                </div>
                
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" id="resource-location" name="location" placeholder="e.g., Office A, Remote">
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-orange">Save Resource</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function showMessage(message, type) {
            var statusEl = document.getElementById('status-message');
            statusEl.textContent = message;
            statusEl.className = 'status-message ' + (type || 'success');
            statusEl.style.display = 'block';
            
            setTimeout(function() {
                statusEl.style.display = 'none';
            }, 5000);
        }

        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Resource';
            document.getElementById('resource-form').reset();
            document.getElementById('resource-id').value = '';
            document.getElementById('resource-modal').style.display = 'block';
        }

        function editRes(id) {
            var row = event.target.closest('tr');
            var cells = row.cells;
            
            document.getElementById('modal-title').textContent = 'Edit Resource';
            document.getElementById('resource-id').value = id;
            document.getElementById('resource-name').value = cells[0].textContent;
            document.getElementById('resource-type').value = cells[1].querySelector('.badge').textContent.toLowerCase();
            document.getElementById('resource-cost').value = cells[2].textContent.replace('$', '');
            document.getElementById('resource-skill').value = cells[3].querySelector('.badge').textContent.toLowerCase();
            document.getElementById('resource-availability').value = cells[4].textContent.replace('%', '');
            document.getElementById('resource-department').value = cells[5].textContent;
            
            document.getElementById('resource-modal').style.display = 'block';
        }

        function deleteRes(id) {
            if (confirm('Are you sure you want to delete this resource?')) {
                var formData = new FormData();
                formData.append('action', 'delete_resource');
                formData.append('id', id);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showMessage(data.message);
                        location.reload();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(function(error) {
                    showMessage('Error: ' + error.message, 'error');
                });
            }
        }

        function closeModal() {
            document.getElementById('resource-modal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('resource-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var isEdit = formData.get('id');
                formData.append('action', isEdit ? 'update_resource' : 'add_resource');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        showMessage(data.message);
                        closeModal();
                        location.reload();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(function(error) {
                    showMessage('Error: ' + error.message, 'error');
                });
            });
        });

        window.onclick = function(event) {
            var modal = document.getElementById('resource-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>
</html>