<?php
// modules/M/projects.php - Projects Management with CRUD Operations
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

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO projects (name, description, client_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $result = $stmt->execute([
                    trim($_POST['name']),
                    trim($_POST['description']),
                    $_POST['client_id'] ?: 1,
                    $_POST['status']
                ]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Project created successfully!', 'id' => $conn->lastInsertId()];
                } else {
                    throw new Exception('Failed to create project');
                }
                break;
                
            case 'update':
                $stmt = $conn->prepare("UPDATE projects SET name = ?, description = ?, client_id = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([
                    trim($_POST['name']),
                    trim($_POST['description']),
                    $_POST['client_id'] ?: 1,
                    $_POST['status'],
                    $_POST['id']
                ]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Project updated successfully!'];
                } else {
                    throw new Exception('Failed to update project');
                }
                break;
                
            case 'delete':
                // Check if project has associated process models
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM process_models WHERE project_id = ?");
                $stmt->execute([$_POST['id']]);
                $processCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($processCount > 0) {
                    throw new Exception("Cannot delete project. It has {$processCount} associated process models.");
                }
                
                $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Project deleted successfully!'];
                } else {
                    throw new Exception('Failed to delete project');
                }
                break;
                
            case 'get':
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as client_name,
                           (SELECT COUNT(*) FROM process_models pm WHERE pm.project_id = p.id) as process_count
                    FROM projects p
                    LEFT JOIN users u ON p.client_id = u.id
                    WHERE p.id = ?
                ");
                $stmt->execute([$_POST['id']]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($project) {
                    $response = ['success' => true, 'project' => $project];
                } else {
                    throw new Exception('Project not found');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Database error in projects.php: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("Error in projects.php: " . $e->getMessage());
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}

// Get all projects with statistics
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username as client_name,
               (SELECT COUNT(*) FROM process_models pm WHERE pm.project_id = p.id) as process_count,
               (SELECT COUNT(*) FROM job_descriptions jd WHERE jd.project_id = p.id) as job_count,
               (SELECT COUNT(*) FROM training_programs tp WHERE tp.project_id = p.id) as training_count,
               (SELECT COUNT(*) FROM metrics m WHERE m.project_id = p.id) as metrics_count
        FROM projects p
        LEFT JOIN users u ON p.client_id = u.id
        ORDER BY p.updated_at DESC, p.created_at DESC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading projects: " . $e->getMessage());
    $projects = [];
}

// Get all users for client dropdown
try {
    $stmt = $conn->prepare("SELECT id, username, email FROM users ORDER BY username");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading users: " . $e->getMessage());
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects Management - MACTA Framework</title>
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
        }

        .header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: bold;
        }

        .breadcrumb {
            opacity: 0.9;
            margin-top: 5px;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-box input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            width: 300px;
        }

        .btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .project-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #ff6b35;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .project-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .project-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .project-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }

        .meta-icon {
            font-size: 16px;
        }

        .project-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #ff6b35;
            display: block;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .project-actions {
            display: flex;
            gap: 10px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

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
            padding: 30px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            position: relative;
        }

        .modal h3 {
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            display: none;
            max-width: 300px;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-box {
                justify-content: center;
            }
            
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📋 Projects Management</h1>
                <div class="breadcrumb">
                    <a href="../../index.php">MACTA Framework</a> > 
                    <a href="index.php">Modeling</a> > 
                    Projects Management
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">← Back to Modeling</a>
        </div>
    </div>

    <div class="container">
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search projects..." onkeyup="filterProjects()">
                <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
            </div>
            <button class="btn" onclick="openCreateModal()">
                ➕ Create New Project
            </button>
        </div>

        <div class="projects-grid" id="projectsGrid">
            <?php if (empty($projects)): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Projects Found</h3>
                    <p>Create your first project to get started with the MACTA Framework.</p>
                    <button class="btn" onclick="openCreateModal()" style="margin-top: 20px;">
                        Create First Project
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <div class="project-card" data-project-name="<?php echo strtolower($project['name']); ?>">
                        <div class="project-header">
                            <div>
                                <div class="project-title"><?php echo htmlspecialchars($project['name']); ?></div>
                                <span class="project-status status-<?php echo $project['status']; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="project-description">
                            <?php echo htmlspecialchars($project['description'] ?: 'No description provided'); ?>
                        </div>

                        <div class="project-meta">
                            <div class="meta-item">
                                <span class="meta-icon">👤</span>
                                <span><?php echo htmlspecialchars($project['client_name'] ?: 'No client'); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span><?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="project-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $project['process_count']; ?></span>
                                <span class="stat-label">Processes</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $project['job_count']; ?></span>
                                <span class="stat-label">Jobs</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $project['training_count']; ?></span>
                                <span class="stat-label">Training</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $project['metrics_count']; ?></span>
                                <span class="stat-label">Metrics</span>
                            </div>
                        </div>

                        <div class="project-actions">
                            <button class="btn btn-sm" onclick="viewProject(<?php echo $project['id']; ?>)">
                                👁️ View
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="editProject(<?php echo $project['id']; ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteProject(<?php echo $project['id']; ?>)">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create/Edit Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProjectModal()">&times;</span>
            <h3 id="modalTitle">Create New Project</h3>
            
            <form id="projectForm">
                <input type="hidden" id="projectId" name="id">
                
                <div class="form-group">
                    <label for="projectName">Project Name *</label>
                    <input type="text" id="projectName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="projectDescription">Description</label>
                    <textarea id="projectDescription" name="description" placeholder="Describe this project..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="projectClient">Client</label>
                    <select id="projectClient" name="client_id">
                        <option value="">Select a client...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?> 
                                (<?php echo htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="projectStatus">Status</label>
                    <select id="projectStatus" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 30px;">
                    <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Project</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        let isEditing = false;

        function openCreateModal() {
            isEditing = false;
            document.getElementById('modalTitle').textContent = 'Create New Project';
            document.getElementById('projectForm').reset();
            document.getElementById('projectId').value = '';
            document.getElementById('projectModal').style.display = 'block';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
        }

        function editProject(id) {
            isEditing = true;
            document.getElementById('modalTitle').textContent = 'Edit Project';
            
            fetch('projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const project = data.project;
                    document.getElementById('projectId').value = project.id;
                    document.getElementById('projectName').value = project.name;
                    document.getElementById('projectDescription').value = project.description || '';
                    document.getElementById('projectClient').value = project.client_id || '';
                    document.getElementById('projectStatus').value = project.status;
                    document.getElementById('projectModal').style.display = 'block';
                } else {
                    showNotification('Error loading project: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Network error: ' + error.message, 'error');
            });
        }

        function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                fetch('projects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Network error: ' + error.message, 'error');
                });
            }
        }

        function viewProject(id) {
            // Navigate to the detailed project view page
            window.location.href = 'project_view.php?id=' + id;
        }

        function filterProjects() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const projectCards = document.querySelectorAll('.project-card');
            
            projectCards.forEach(card => {
                const projectName = card.getAttribute('data-project-name');
                if (projectName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filterProjects();
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

        // Handle form submission
        document.getElementById('projectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditing ? 'update' : 'create';
            formData.append('action', action);
            
            fetch('projects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeProjectModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Network error: ' + error.message, 'error');
            });
        });

        // Handle modal clicks
        window.onclick = function(event) {
            const modal = document.getElementById('projectModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Auto-focus on name field when modal opens
        document.getElementById('projectModal').addEventListener('transitionend', function() {
            if (this.style.display === 'block') {
                document.getElementById('projectName').focus();
            }
        });
    </script>
</body>
</html>