<?php
// modules/M/resources.php - Complete Resource Management System

// Initialize variables
$resources = [];
$db_error = '';

// Database connection - EXACT SAME PATTERN as bpmn_manager.php
try {
    // Check if config exists
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        // Create PDO connection - SAME AS BPMN MANAGER
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Get all resources from database - READING FROM CORRECT TABLE
        $stmt = $pdo->prepare("SELECT * FROM simulation_resources ORDER BY updated_at DESC");
        $stmt->execute();
        $resources = $stmt->fetchAll();
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("Resources Manager DB Error: " . $e->getMessage());
}

// Resource types configuration - MATCHING YOUR SIMULATION_RESOURCES TABLE
$resource_types = [
    'human' => ['name' => 'Human Resources', 'icon' => '👥', 'color' => '#4ECDC4'],
    'equipment' => ['name' => 'Equipment & Tools', 'icon' => '🔧', 'color' => '#FF7B54'],
    'material' => ['name' => 'Materials & Supplies', 'icon' => '📦', 'color' => '#FFE66D'],
    'software' => ['name' => 'Software & Systems', 'icon' => '💻', 'color' => '#95E1D3']
];

// Skill levels for simulation_resources table
$skill_levels = [
    'beginner' => ['name' => 'Beginner', 'icon' => '🟢'],
    'intermediate' => ['name' => 'Intermediate', 'icon' => '🟡'],
    'expert' => ['name' => 'Expert', 'icon' => '🔴'],
    'specialist' => ['name' => 'Specialist', 'icon' => '⭐']
];

// Handle AJAX requests - EXACT SAME PATTERN as bpmn_manager.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!empty($db_error)) {
        echo json_encode(['success' => false, 'message' => $db_error]);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'create_resource':
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO simulation_resources (name, type, description, hourly_rate, availability_hours, skill_level) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'] ?? 'Untitled Resource',
                    $_POST['type'] ?? 'human',
                    $_POST['description'] ?? '',
                    $_POST['hourly_rate'] ?? 0,
                    $_POST['availability_hours'] ?? 8.00,
                    $_POST['skill_level'] ?? 'intermediate'
                ]);
                echo json_encode(['success' => true, 'message' => 'Resource created successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'update_resource':
            try {
                $stmt = $pdo->prepare("
                    UPDATE simulation_resources 
                    SET name = ?, type = ?, description = ?, hourly_rate = ?, availability_hours = ?, 
                        skill_level = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'] ?? 'Untitled Resource',
                    $_POST['type'] ?? 'human',
                    $_POST['description'] ?? '',
                    $_POST['hourly_rate'] ?? 0,
                    $_POST['availability_hours'] ?? 8.00,
                    $_POST['skill_level'] ?? 'intermediate',
                    $_POST['id'] ?? 0
                ]);
                echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'delete_resource':
            try {
                $stmt = $pdo->prepare("DELETE FROM simulation_resources WHERE id = ?");
                $stmt->execute([$_POST['id'] ?? 0]);
                echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_resource':
            try {
                $stmt = $pdo->prepare("SELECT * FROM simulation_resources WHERE id = ?");
                $stmt->execute([$_POST['id'] ?? 0]);
                $resource = $stmt->fetch();
                echo json_encode(['success' => true, 'resource' => $resource]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_resources':
            try {
                $where = "WHERE 1=1";
                $params = [];
                
                if (!empty($_POST['type_filter']) && $_POST['type_filter'] !== 'all') {
                    $where .= " AND type = ?";
                    $params[] = $_POST['type_filter'];
                }
                
                if (!empty($_POST['skill_filter']) && $_POST['skill_filter'] !== 'all') {
                    $where .= " AND skill_level = ?";
                    $params[] = $_POST['skill_filter'];
                }
                
                if (!empty($_POST['search'])) {
                    $where .= " AND (name LIKE ? OR description LIKE ?)";
                    $search = '%' . $_POST['search'] . '%';
                    $params[] = $search;
                    $params[] = $search;
                }
                
                $order = "ORDER BY " . ($_POST['sort_by'] ?? 'name') . " " . ($_POST['sort_dir'] ?? 'ASC');
                
                $stmt = $pdo->prepare("SELECT * FROM simulation_resources $where $order");
                $stmt->execute($params);
                $resources = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'resources' => $resources]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Create resources table if it doesn't exist - MATCHING YOUR EXACT DB SCHEMA
try {
    if (!empty($pdo)) {
        // Table already exists in your schema, no need to create
        // Just verify connection works
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'simulation_resources'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            error_log("Warning: simulation_resources table not found in database");
        }
    }
} catch (Exception $e) {
    error_log("Database check failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Management - MACTA Framework</title>
    
    <style>
        :root {
            --macta-orange: #FF7B54;
            --macta-red: #d63031;
            --macta-teal: #4ECDC4;
            --macta-yellow: #FFE66D;
            --macta-green: #95E1D3;
            --macta-purple: #6c5ce7;
            --macta-dark: #2d3436;
            --macta-light: #ddd;
            --macta-gray: #636e72;
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
            padding: 25px;
            margin-bottom: 25px;
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

        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Control Panel */
        .control-panel {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-bottom: 1px solid var(--macta-light);
        }

        .controls-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 20px;
            align-items: end;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--macta-teal);
        }

        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 16px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--macta-dark);
            text-transform: uppercase;
        }

        .filter-group select {
            padding: 12px 15px;
            border: 2px solid var(--macta-light);
            border-radius: 10px;
            font-size: 14px;
            background: white;
            min-width: 150px;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--macta-teal);
        }

        /* Type Filters */
        .type-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .type-filter {
            padding: 8px 16px;
            border: 2px solid transparent;
            border-radius: 25px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
        }

        .type-filter.active {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .type-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--macta-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,123,84,0.3);
        }

        .btn-secondary {
            background: var(--macta-teal);
            color: white;
        }

        .btn-secondary:hover {
            background: #3cb8b1;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76,205,196,0.3);
        }

        .btn-success {
            background: var(--macta-green);
            color: var(--macta-dark);
        }

        .btn-success:hover {
            background: #7dd3c0;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--macta-yellow);
            color: var(--macta-dark);
        }

        .btn-warning:hover {
            background: #ffd93d;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--macta-red);
            color: white;
        }

        .btn-danger:hover {
            background: #b71c1c;
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Resources Grid */
        .resources-content {
            padding: 25px;
        }

        .resources-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--macta-teal), var(--macta-green));
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .resource-card {
            background: white;
            border: 2px solid #f1f3f4;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: var(--macta-teal);
        }

        .resource-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--macta-teal);
        }

        .resource-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .resource-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .resource-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .resource-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--macta-dark);
            margin: 0;
        }

        .resource-type {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }

        .resource-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-unavailable {
            background: #ffebee;
            color: #c62828;
        }

        .status-maintenance {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-reserved {
            background: #e3f2fd;
            color: #1565c0;
        }

        .resource-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .resource-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .detail-value {
            font-size: 14px;
            color: var(--macta-dark);
            font-weight: 500;
        }

        .resource-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f1f3f4;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--macta-dark);
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--macta-light);
        }

        .modal-title {
            font-size: 24px;
            color: var(--macta-dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--macta-red);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--macta-dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--macta-light);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--macta-teal);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--macta-light);
        }

        /* Loading & Notifications */
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            color: var(--macta-teal);
            font-size: 16px;
        }

        .loading.active {
            display: block;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.success {
            background: var(--macta-green);
            color: var(--macta-dark);
        }

        .notification.error {
            background: var(--macta-red);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .controls-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .type-filters {
                justify-content: center;
            }

            .resources-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .resource-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>
            <div class="macta-logo">📋</div>
            Resource Management System
        </h1>
        <div>
            <a href="../" class="btn btn-secondary">
                <span>←</span> Back to Modeling
            </a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="controls-row">
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="Search resources by name, description, or specifications...">
                    <span class="search-icon">🔍</span>
                </div>

                <div class="filter-group">
                    <label>Skill Filter</label>
                    <select id="skill-filter">
                        <option value="all">All Skill Levels</option>
                        <option value="beginner">🟢 Beginner</option>
                        <option value="intermediate">🟡 Intermediate</option>
                        <option value="expert">🔴 Expert</option>
                        <option value="specialist">⭐ Specialist</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Sort By</label>
                    <select id="sort-by">
                        <option value="name">Name</option>
                        <option value="type">Type</option>
                        <option value="hourly_rate">Hourly Rate</option>
                        <option value="skill_level">Skill Level</option>
                        <option value="created_at">Date Created</option>
                        <option value="updated_at">Last Updated</option>
                    </select>
                </div>

                <button class="btn btn-primary" onclick="openCreateModal()">
                    <span>➕</span> Add Resource
                </button>
            </div>

            <!-- Type Filters -->
            <div class="type-filters">
                <div class="type-filter active" data-type="all" style="background: #f8f9fa; border-color: var(--macta-dark);">
                    <span>📋</span> All Types
                </div>
                <?php foreach ($resource_types as $key => $type): ?>
                <div class="type-filter" data-type="<?= $key ?>" style="background: <?= $type['color'] ?>20; border-color: <?= $type['color'] ?>;">
                    <span><?= $type['icon'] ?></span> <?= $type['name'] ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Resources Content -->
        <div class="resources-content">
            <!-- Stats -->
            <div class="resources-stats" id="resources-stats">
                <div class="stat-card">
                    <div class="stat-value" id="total-resources">0</div>
                    <div class="stat-label">Total Resources</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, var(--macta-green), var(--macta-yellow));">
                    <div class="stat-value" id="available-resources">0</div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, var(--macta-orange), var(--macta-red));">
                    <div class="stat-value" id="unavailable-resources">0</div>
                    <div class="stat-label">Unavailable</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, var(--macta-purple), var(--macta-dark));">
                    <div class="stat-value" id="total-cost">$0</div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>

            <!-- Loading State -->
            <div class="loading" id="loading">
                <div style="font-size: 48px; margin-bottom: 20px;">🔄</div>
                Loading resources...
            </div>

            <!-- Resources Grid -->
            <div class="resources-grid" id="resources-grid">
                <!-- Resources will be loaded here dynamically -->
            </div>

                <div class="status-bar">
                    <span class="token"></span> Use the controls above to manage your resources.
                    <?php if (!empty($db_error)): ?>
                        <strong>Database Error:</strong> <?= htmlspecialchars($db_error) ?>
                    <?php elseif (count($resources) > 0): ?>
                        Found <?= count($resources) ?> resources in database.
                    <?php else: ?>
                        No resources found. Create your first resource!
                    <?php endif; ?>
                </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal" id="resource-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">
                    <span>➕</span> Add New Resource
                </h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>

            <form id="resource-form">
                <input type="hidden" id="resource-id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="resource-name">Resource Name *</label>
                        <input type="text" id="resource-name" required placeholder="Enter resource name">
                    </div>

                    <div class="form-group">
                        <label for="resource-type">Resource Type *</label>
                        <select id="resource-type" required>
                            <option value="">Select type...</option>
                            <?php foreach ($resource_types as $key => $type): ?>
                            <option value="<?= $key ?>"><?= $type['icon'] ?> <?= $type['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="skill-level">Skill Level</label>
                        <select id="skill-level">
                            <?php foreach ($skill_levels as $key => $level): ?>
                            <option value="<?= $key ?>"><?= $level['icon'] ?> <?= $level['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hourly-rate">Hourly Rate ($)</label>
                        <input type="number" id="hourly-rate" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="availability-hours">Availability Hours</label>
                        <input type="number" id="availability-hours" step="0.5" min="0" max="24" value="8.00" placeholder="8.00">
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" placeholder="Enter resource description..."></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="save-btn">
                        <span>💾</span> Save Resource
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // Global variables
        let currentResources = [];
        let currentFilters = {
            type: 'all',
            skill: 'all',
            search: '',
            sortBy: 'name',
            sortDir: 'ASC'
        };

        // Store PHP data for JavaScript - SAME PATTERN as bpmn_manager.php
        const resources = <?= json_encode($resources) ?>;
        const resourceTypes = <?= json_encode($resource_types) ?>;
        const skillLevels = <?= json_encode($skill_levels) ?>;
        const dbError = <?= json_encode($db_error) ?>;
        
        // Check for database errors - SAME PATTERN as bpmn_manager.php
        if (dbError) {
            console.error('Database Error:', dbError);
            const grid = document.getElementById('resources-grid');
            if (grid) {
                grid.innerHTML = '<div class="empty-state"><h3>Database Error</h3><p>' + dbError + '</p></div>';
            }
        }

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            loadResources();
        });

        // Setup event listeners
        function setupEventListeners() {
            // Search input
            document.getElementById('search-input').addEventListener('input', debounce(function(e) {
                currentFilters.search = e.target.value;
                loadResources();
            }, 300));

            // Skill filter
            document.getElementById('skill-filter').addEventListener('change', function(e) {
                currentFilters.skill = e.target.value;
                loadResources();
            });

            // Sort by
            document.getElementById('sort-by').addEventListener('change', function(e) {
                currentFilters.sortBy = e.target.value;
                loadResources();
            });

            // Type filters
            document.querySelectorAll('.type-filter').forEach(filter => {
                filter.addEventListener('click', function() {
                    // Update active state
                    document.querySelectorAll('.type-filter').forEach(f => f.classList.remove('active'));
                    this.classList.add('active');

                    // Update filter
                    currentFilters.type = this.dataset.type;
                    loadResources();
                });
            });

            // Form submission
            document.getElementById('resource-form').addEventListener('submit', function(e) {
                e.preventDefault();
                saveResource();
            });

            // Modal close on background click
            document.getElementById('resource-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                } else if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    openCreateModal();
                }
            });
        }

        // Load resources with current filters
        async function loadResources() {
            showLoading(true);

            try {
                const formData = new FormData();
                formData.append('action', 'get_resources');
                formData.append('type_filter', currentFilters.type);
                formData.append('skill_filter', currentFilters.skill);
                formData.append('search', currentFilters.search);
                formData.append('sort_by', currentFilters.sortBy);
                formData.append('sort_dir', currentFilters.sortDir);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    currentResources = result.resources;
                    renderResources(result.resources);
                    updateStats(result.resources);
                } else {
                    showNotification('Failed to load resources: ' + result.message, 'error');
                }

            } catch (error) {
                console.error('Load resources error:', error);
                showNotification('Failed to load resources', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Render resources grid
        function renderResources(resources) {
            const grid = document.getElementById('resources-grid');
            const emptyState = document.getElementById('empty-state');

            if (resources.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            grid.style.display = 'grid';
            emptyState.style.display = 'none';

            grid.innerHTML = resources.map(resource => {
                const type = resourceTypes[resource.type];
                const skill = skillLevels[resource.skill_level];

                return `
                    <div class="resource-card" style="border-color: ${type.color}20;">
                        <div class="resource-card::before" style="background: ${type.color};"></div>
                        
                        <div class="resource-header">
                            <div class="resource-title">
                                <div class="resource-icon" style="background: ${type.color};">
                                    ${type.icon}
                                </div>
                                <div>
                                    <h3 class="resource-name">${escapeHtml(resource.name)}</h3>
                                    <div class="resource-type">${type.name}</div>
                                </div>
                            </div>
                            <div class="resource-status" style="background: ${type.color}20; color: ${type.color};">
                                ${skill.icon} ${skill.name}
                            </div>
                        </div>

                        ${resource.description ? `
                        <div class="resource-description">
                            ${escapeHtml(resource.description)}
                        </div>
                        ` : ''}

                        <div class="resource-details">
                            <div class="detail-item">
                                <div class="detail-label">Hourly Rate</div>
                                <div class="detail-value">${parseFloat(resource.hourly_rate || 0).toFixed(2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Availability</div>
                                <div class="detail-value">${parseFloat(resource.availability_hours || 8).toFixed(1)}h/day</div>
                            </div>
                        </div>

                        <div class="detail-item" style="margin-bottom: 15px;">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value">${formatDate(resource.updated_at)}</div>
                        </div>

                        <div class="resource-actions">
                            <button class="btn btn-small btn-warning" onclick="editResource(${resource.id})">
                                <span>✏️</span> Edit
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteResource(${resource.id})">
                                <span>🗑️</span> Delete
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }apeHtml(resource.description)}
                        </div>
                        ` : ''}

                        <div class="resource-details">
                            <div class="detail-item">
                                <div class="detail-label">Unit Cost</div>
                                <div class="detail-value">${parseFloat(resource.unit_cost || 0).toFixed(2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Last Updated</div>
                                <div class="detail-value">${formatDate(resource.updated_at)}</div>
                            </div>
                        </div>

                        ${resource.contact_info ? `
                        <div class="detail-item" style="margin-bottom: 15px;">
                            <div class="detail-label">Contact</div>
                            <div class="detail-value">${escapeHtml(resource.contact_info)}</div>
                        </div>
                        ` : ''}

                        <div class="resource-actions">
                            <button class="btn btn-small btn-warning" onclick="editResource(${resource.id})">
                                <span>✏️</span> Edit
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteResource(${resource.id})">
                                <span>🗑️</span> Delete
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Update statistics
        function updateStats(resources) {
            const total = resources.length;
            const available = resources.filter(r => r.availability_status === 'available').length;
            const unavailable = resources.filter(r => r.availability_status === 'unavailable').length;
            const totalCost = resources.reduce((sum, r) => sum + parseFloat(r.unit_cost || 0), 0);

            document.getElementById('total-resources').textContent = total;
            document.getElementById('available-resources').textContent = available;
            document.getElementById('unavailable-resources').textContent = unavailable;
            document.getElementById('total-cost').textContent = ' + totalCost.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Open create modal
        function openCreateModal() {
            document.getElementById('modal-title').innerHTML = '<span>➕</span> Add New Resource';
            document.getElementById('save-btn').innerHTML = '<span>💾</span> Save Resource';
            document.getElementById('resource-form').reset();
            document.getElementById('resource-id').value = '';
            document.getElementById('resource-modal').classList.add('active');
            document.getElementById('resource-name').focus();
        }

        // Edit resource
        async function editResource(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_resource');
                formData.append('id', id);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.resource) {
                    const resource = result.resource;

                    document.getElementById('modal-title').innerHTML = '<span>✏️</span> Edit Resource';
                    document.getElementById('save-btn').innerHTML = '<span>💾</span> Update Resource';
                    
                    document.getElementById('resource-id').value = resource.id;
                    document.getElementById('resource-name').value = resource.name;
                    document.getElementById('resource-type').value = resource.type;
                    document.getElementById('hourly-rate').value = resource.hourly_rate;
                    document.getElementById('availability-hours').value = resource.availability_hours;
                    document.getElementById('skill-level').value = resource.skill_level;
                    document.getElementById('description').value = resource.description || '';

                    document.getElementById('resource-modal').classList.add('active');
                    document.getElementById('resource-name').focus();
                } else {
                    showNotification('Failed to load resource data', 'error');
                }

            } catch (error) {
                console.error('Edit resource error:', error);
                showNotification('Failed to load resource data', 'error');
            }
        }

        // Save resource (create or update)
        async function saveResource() {
            const id = document.getElementById('resource-id').value;
            const isEdit = id !== '';

            const formData = new FormData();
            formData.append('action', isEdit ? 'update_resource' : 'create_resource');
            
            if (isEdit) {
                formData.append('id', id);
            }

            formData.append('name', document.getElementById('resource-name').value);
            formData.append('type', document.getElementById('resource-type').value);
            formData.append('hourly_rate', document.getElementById('hourly-rate').value || 0);
            formData.append('availability_hours', document.getElementById('availability-hours').value || 8.00);
            formData.append('skill_level', document.getElementById('skill-level').value);
            formData.append('description', document.getElementById('description').value);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(isEdit ? 'Resource updated successfully!' : 'Resource created successfully!', 'success');
                    closeModal();
                    loadResources();
                } else {
                    showNotification('Failed to save resource: ' + result.message, 'error');
                }

            } catch (error) {
                console.error('Save resource error:', error);
                showNotification('Failed to save resource', 'error');
            }
        }

        // Delete resource
        async function deleteResource(id) {
            const resource = currentResources.find(r => r.id == id);
            if (!resource) return;

            if (!confirm(`Are you sure you want to delete "${resource.name}"?\n\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_resource');
                formData.append('id', id);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Resource deleted successfully!', 'success');
                    loadResources();
                } else {
                    showNotification('Failed to delete resource: ' + result.message, 'error');
                }

            } catch (error) {
                console.error('Delete resource error:', error);
                showNotification('Failed to delete resource', 'error');
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('resource-modal').classList.remove('active');
        }

        // Show/hide loading
        function showLoading(show) {
            const loading = document.getElementById('loading');
            const grid = document.getElementById('resources-grid');

            if (show) {
                loading.classList.add('active');
                grid.style.opacity = '0.5';
            } else {
                loading.classList.remove('active');
                grid.style.opacity = '1';
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function debounce(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // Export functionality
        function exportResources() {
            const csvContent = generateCSV(currentResources);
            downloadCSV(csvContent, 'resources_export.csv');
        }

        function generateCSV(resources) {
            const headers = ['Name', 'Type', 'Description', 'Hourly Rate', 'Availability Hours', 'Skill Level', 'Created At'];
            const rows = resources.map(resource => [
                resource.name,
                resourceTypes[resource.type].name,
                resource.description || '',
                resource.hourly_rate || 0,
                resource.availability_hours || 8,
                skillLevels[resource.skill_level].name,
                resource.created_at
            ]);

            return [headers, ...rows].map(row => 
                row.map(field => `"${(field || '').toString().replace(/"/g, '""')}"`).join(',')
            ).join('\n');
        }

        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Add export button to header programmatically
        document.addEventListener('DOMContentLoaded', function() {
            const headerButtons = document.querySelector('.header > div');
            if (headerButtons) {
                const exportBtn = document.createElement('button');
                exportBtn.className = 'btn btn-success';
                exportBtn.innerHTML = '<span>📥</span> Export CSV';
                exportBtn.onclick = exportResources;
                exportBtn.style.marginRight = '10px';
                headerButtons.insertBefore(exportBtn, headerButtons.firstChild);
            }
        });

        console.log('🚀 Resource Management System initialized successfully');
    </script>
</body>
</html>