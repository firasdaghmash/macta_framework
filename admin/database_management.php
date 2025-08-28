<?php
// admin/database_management.php - Database Management with CRUD Operations
require_once '../config/config.php';

// Create database connection using existing config
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br><br>Please check your database configuration in config/config.php");
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $table = $_POST['table'] ?? '';
    $id = $_POST['id'] ?? '';
    
    try {
        switch ($action) {
            case 'fetch':
                $stmt = $conn->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1000");
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create':
                $fields = array_keys($_POST);
                $fields = array_filter($fields, function($field) {
                    return !in_array($field, ['action', 'table', 'id']);
                });
                
                $placeholders = ':' . implode(', :', $fields);
                $fieldList = implode(', ', $fields);
                
                $stmt = $conn->prepare("INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})");
                
                foreach ($fields as $field) {
                    $value = $_POST[$field];
                    if ($value === '') $value = null;
                    
                    // Handle password hashing
                    if ($field === 'password' && $value && strpos($value, 'HASH:') === 0) {
                        $value = password_hash(substr($value, 5), PASSWORD_DEFAULT);
                    }
                    
                    // Handle JSON fields
                    if (in_array($field, ['model_data', 'config_data', 'scenario_data', 'results_data', 'template_data', 'performance_metrics', 'modules', 'path_data', 'bottleneck_tasks', 'optimization_suggestions', 'expected_impact', 'complexity_distribution', 'seasonal_factors'])) {
                        if ($value && is_string($value)) {
                            // Validate JSON
                            $decoded = json_decode($value);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                throw new Exception("Invalid JSON in field {$field}: " . json_last_error_msg());
                            }
                        }
                    }
                    
                    $stmt->bindValue(":{$field}", $value);
                }
                
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Record created successfully']);
                break;
                
            case 'update':
                $fields = array_keys($_POST);
                $fields = array_filter($fields, function($field) {
                    return !in_array($field, ['action', 'table', 'id']);
                });
                
                $setClause = implode(' = ?, ', $fields) . ' = ?';
                
                $sql = "UPDATE {$table} SET {$setClause} WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                $values = [];
                foreach ($fields as $field) {
                    $value = $_POST[$field];
                    if ($value === '') $value = null;
                    
                    // Handle password hashing
                    if ($field === 'password' && $value && strpos($value, 'HASH:') === 0) {
                        $value = password_hash(substr($value, 5), PASSWORD_DEFAULT);
                    }
                    
                    // Handle JSON fields
                    if (in_array($field, ['model_data', 'config_data', 'scenario_data', 'results_data', 'template_data', 'performance_metrics', 'modules', 'path_data', 'bottleneck_tasks', 'optimization_suggestions', 'expected_impact', 'complexity_distribution', 'seasonal_factors'])) {
                        if ($value && is_string($value)) {
                            // Validate JSON
                            $decoded = json_decode($value);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                throw new Exception("Invalid JSON in field {$field}: " . json_last_error_msg());
                            }
                        }
                    }
                    
                    $values[] = $value;
                }
                $values[] = $id;
                
                $stmt->execute($values);
                echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
                break;
                
            case 'get_structure':
                $stmt = $conn->prepare("DESCRIBE {$table}");
                $stmt->execute();
                $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'structure' => $structure]);
                break;
                
            case 'get_count':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$table}");
                $stmt->execute();
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'count' => $count['count']]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Table configurations organized by tabs - Complete database schema
$table_configs = [
    'users_projects' => [
        'title' => 'Users & Projects Management',
        'icon' => '👥',
        'tables' => [
            'users' => ['name' => 'Users', 'icon' => '👤'],
            'projects' => ['name' => 'Projects', 'icon' => '📁'],
            'job_descriptions' => ['name' => 'Job Descriptions', 'icon' => '📝']
        ]
    ],
    'process_management' => [
        'title' => 'Process Management',
        'icon' => '🔄',
        'tables' => [
            'process_models' => ['name' => 'Process Models', 'icon' => '🗂️'],
            'process_tasks' => ['name' => 'Process Tasks', 'icon' => '📋'],
            'process_arrival_configs' => ['name' => 'Arrival Configs', 'icon' => '📅'],
            'process_path_analysis' => ['name' => 'Path Analysis', 'icon' => '🛤️'],
            'process_step_resources' => ['name' => 'Step Resources', 'icon' => '🔗']
        ]
    ],
    'resources_management' => [
        'title' => 'Resources Management',
        'icon' => '🏭',
        'tables' => [
            'resources' => ['name' => 'Resources', 'icon' => '📦'],
            'enhanced_resources' => ['name' => 'Enhanced Resources', 'icon' => '🚀'],
            'resource_allocations' => ['name' => 'Allocations', 'icon' => '📊'],
            'resource_templates' => ['name' => 'Templates', 'icon' => '📄'],
            'task_resource_assignments' => ['name' => 'Task Assignments', 'icon' => '🔗']
        ]
    ],
    'simulation_management' => [
        'title' => 'Simulation Management',
        'icon' => '🎮',
        'tables' => [
            'simulation_configs' => ['name' => 'Configurations', 'icon' => '⚙️'],
            'simulation_resources' => ['name' => 'Sim Resources', 'icon' => '🎯'],
            'simulation_results' => ['name' => 'Results', 'icon' => '📊'],
            'simulation_metrics' => ['name' => 'Metrics', 'icon' => '📈'],
            'simulation_templates' => ['name' => 'Templates', 'icon' => '📋']
        ]
    ],
    'analytics_feedback' => [
        'title' => 'Analytics & Feedback',
        'icon' => '📈',
        'tables' => [
            'metrics' => ['name' => 'Metrics', 'icon' => '📊'],
            'customer_feedback' => ['name' => 'Customer Feedback', 'icon' => '💬'],
            'dashboard_metrics_cache' => ['name' => 'Dashboard Cache', 'icon' => '⚡'],
            'optimization_recommendations' => ['name' => 'Optimization', 'icon' => '🎯']
        ]
    ],
    'timers_training' => [
        'title' => 'Timers & Training',
        'icon' => 'ⱱ️',
        'tables' => [
            'timer_sessions' => ['name' => 'Timer Sessions', 'icon' => '🕐'],
            'timer_averages' => ['name' => 'Timer Averages', 'icon' => '⏰'],
            'training_programs' => ['name' => 'Training Programs', 'icon' => '🎓'],
            'activity_log' => ['name' => 'Activity Log', 'icon' => '📝']
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - MACTA Framework</title>
    <style>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .connection-status {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 20px;
            margin-top: 15px;
            display: inline-block;
        }

        .tab-container {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }

        .tab-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .tab-nav li {
            flex: 1;
            min-width: 200px;
        }

        .tab-nav button {
            width: 100%;
            padding: 20px 15px;
            border: none;
            background: transparent;
            color: #666;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-nav button:hover {
            background: rgba(255, 107, 53, 0.1);
            color: #ff6b35;
        }

        .tab-nav button.active {
            background: white;
            color: #ff6b35;
            border-bottom-color: #ff6b35;
            font-weight: 600;
        }

        .tab-content {
            padding: 30px;
            min-height: 600px;
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .table-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }

        .table-card:hover {
            border-color: #ff6b35;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.1);
        }

        .table-card.selected {
            border-color: #ff6b35;
            background: rgba(255, 107, 53, 0.05);
        }

        .table-card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .table-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .record-count {
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .crud-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            display: none;
        }

        .crud-section.active {
            display: block;
        }

        .crud-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .crud-header h3 {
            color: #333;
            font-size: 1.5rem;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #ff6b35;
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .search-filter-section {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .data-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
            margin-top: 20px;
        }

        .table-container {
            max-height: 500px;
            overflow: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .data-table th {
            background: #ff6b35;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            word-break: break-word;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .data-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .form-modal {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .form-modal h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.1rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }

        .json-preview {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .field-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        .password-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b35;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .tab-nav {
                flex-direction: column;
            }

            .tab-nav li {
                min-width: auto;
            }

            .table-grid {
                grid-template-columns: 1fr;
            }

            .crud-header {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-group {
                justify-content: center;
            }

            .search-filter-section {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
            }

            .form-modal {
                width: 95%;
                padding: 20px;
            }
        }

        .table-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }

        .pagination button:hover {
            background: #f8f9fa;
        }

        .pagination button.active {
            background: #ff6b35;
            color: white;
            border-color: #ff6b35;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Management</h1>
            <p>Comprehensive CRUD Operations for MACTA Framework</p>
            <div class="connection-status">
                ✅ Connected to: <?php echo DB_NAME; ?> (<?php echo DB_HOST; ?>)
            </div>
        </div>

        <div class="tab-container">
            <ul class="tab-nav">
                <?php foreach ($table_configs as $tab_id => $config): ?>
                <li>
                    <button class="tab-btn <?= $tab_id === array_key_first($table_configs) ? 'active' : '' ?>" 
                            data-tab="<?= $tab_id ?>">
                        <span><?= $config['icon'] ?></span>
                        <span><?= $config['title'] ?></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php foreach ($table_configs as $tab_id => $config): ?>
        <div class="tab-content" id="<?= $tab_id ?>" <?= $tab_id !== array_key_first($table_configs) ? 'style="display:none"' : '' ?>>
            <h2 style="margin-bottom: 25px; color: #333;">
                <?= $config['icon'] ?> <?= $config['title'] ?>
            </h2>

            <div class="table-grid">
                <?php foreach ($config['tables'] as $table_name => $table_info): ?>
                <div class="table-card" data-table="<?= $table_name ?>">
                    <div class="record-count" id="count-<?= $table_name ?>">Loading...</div>
                    <div class="table-card-icon"><?= $table_info['icon'] ?></div>
                    <h3><?= $table_info['name'] ?></h3>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="crud-section" id="crud-section">
                <div class="crud-header">
                    <h3 id="crud-title">Select a table to manage</h3>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="showCreateForm()">
                            ➕ Add New Record
                        </button>
                        <button class="btn btn-success" onclick="refreshData()">
                            🔄 Refresh Data
                        </button>
                        <button class="btn btn-warning" onclick="exportData()">
                            📤 Export CSV
                        </button>
                        <button class="btn btn-secondary" onclick="showTableInfo()">
                            ℹ️ Table Info
                        </button>
                    </div>
                </div>

                <div class="search-filter-section">
                    <input type="text" class="search-box" placeholder="Search records..." onkeyup="searchTable()" id="searchInput">
                    <select class="search-box" id="columnFilter" onchange="filterByColumn()" style="flex: 0 0 200px;">
                        <option value="">All Columns</option>
                    </select>
                </div>

                <div class="data-table">
                    <div class="table-container">
                        <table id="data-table">
                            <thead id="table-head">
                                <!-- Table headers will be dynamically generated -->
                            </thead>
                            <tbody id="table-body">
                                <tr>
                                    <td colspan="100%" class="loading">
                                        Select a table from above to view and manage data
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pagination" id="pagination" style="display: none;">
                    <button onclick="changePage(-1)">« Previous</button>
                    <span id="page-info">Page 1 of 1</span>
                    <button onclick="changePage(1)">Next »</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Form Modal -->
    <div class="form-overlay" id="form-overlay">
        <div class="form-modal">
            <button class="close-btn" onclick="closeForm()">×</button>
            <h3 id="form-title">Add New Record</h3>
            <form id="record-form">
                <div id="form-fields">
                    <!-- Form fields will be dynamically generated -->
                </div>
                <div class="btn-group" style="margin-top: 25px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script>
        let currentTable = '';
        let currentData = [];
        let filteredData = [];
        let tableStructure = [];
        let editingId = null;
        let currentPage = 1;
        const recordsPerPage = 50;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing database management...');
            
            // Setup tab switching
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    
                    // Update tab buttons
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update tab content
                    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
                    document.getElementById(tabId).style.display = 'block';
                    
                    // Reset current table selection
                    resetTableSelection();
                });
            });

            // Setup table card clicks using event delegation
            document.addEventListener('click', function(e) {
                const tableCard = e.target.closest('.table-card');
                if (tableCard) {
                    const tableName = tableCard.dataset.table;
                    selectTable(tableName);
                }
            });

            // Setup form submission
            document.getElementById('record-form').addEventListener('submit', handleFormSubmission);

            // Load initial record counts
            loadAllRecordCounts();
        });

        function resetTableSelection() {
            currentTable = '';
            document.getElementById('crud-section').classList.remove('active');
            document.querySelectorAll('.table-card').forEach(card => card.classList.remove('selected'));
            document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Select a table to view and manage data</td></tr>';
            document.getElementById('table-head').innerHTML = '';
            document.getElementById('searchInput').value = '';
            document.getElementById('columnFilter').innerHTML = '<option value="">All Columns</option>';
        }

        async function loadAllRecordCounts() {
            const allTables = <?php 
                $allTables = [];
                foreach($table_configs as $config) {
                    $allTables = array_merge($allTables, array_keys($config['tables']));
                }
                echo json_encode($allTables);
            ?>;
            
            for (const tableName of allTables) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'get_count',
                            table: tableName
                        })
                    });
                    
                    const result = await response.json();
                    const countElement = document.getElementById(`count-${tableName}`);
                    
                    if (result.success && countElement) {
                        countElement.textContent = `${result.count} records`;
                        countElement.style.background = result.count > 0 ? '#17a2b8' : '#6c757d';
                    } else if (countElement) {
                        countElement.textContent = 'N/A';
                        countElement.style.background = '#dc3545';
                    }
                } catch (error) {
                    console.error(`Error loading count for ${tableName}:`, error);
                    const countElement = document.getElementById(`count-${tableName}`);
                    if (countElement) {
                        countElement.textContent = 'Error';
                        countElement.style.background = '#dc3545';
                    }
                }
            }
        }

        async function selectTable(tableName) {
            currentTable = tableName;
            currentPage = 1;
            
            // Update UI
            document.querySelectorAll('.table-card').forEach(card => card.classList.remove('selected'));
            document.querySelector(`[data-table="${tableName}"]`).classList.add('selected');
            
            document.getElementById('crud-section').classList.add('active');
            document.getElementById('crud-title').textContent = `Managing: ${tableName}`;
            
            // Clear search and filters
            document.getElementById('searchInput').value = '';
            document.getElementById('columnFilter').value = '';
            
            // Show loading
            document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Loading table structure and data...</td></tr>';
            
            try {
                await loadTableStructure();
                await loadTableData();
                setupColumnFilter();
            } catch (error) {
                showNotification('Error loading table: ' + error.message, 'error');
                console.error('Table selection error:', error);
            }
        }

        async function loadTableStructure() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_structure',
                        table: currentTable
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    tableStructure = result.structure;
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                throw new Error('Failed to load table structure: ' + error.message);
            }
        }

        async function loadTableData() {
            document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Loading data...</td></tr>';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'fetch',
                        table: currentTable
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentData = result.data;
                    filteredData = [...currentData];
                    renderTable();
                    setupPagination();
                    
                    // Update record count
                    const countElement = document.getElementById(`count-${currentTable}`);
                    if (countElement) {
                        countElement.textContent = `${result.data.length} records`;
                        countElement.style.background = result.data.length > 0 ? '#17a2b8' : '#6c757d';
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                document.getElementById('table-body').innerHTML = `<tr><td colspan="100%" class="loading">Error: ${error.message}</td></tr>`;
                throw error;
            }
        }

        function setupColumnFilter() {
            const select = document.getElementById('columnFilter');
            select.innerHTML = '<option value="">All Columns</option>';
            
            if (currentData.length > 0) {
                const columns = Object.keys(currentData[0]);
                columns.forEach(column => {
                    const option = document.createElement('option');
                    option.value = column;
                    option.textContent = column.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    select.appendChild(option);
                });
            }
        }

        function renderTable() {
            if (filteredData.length === 0) {
                document.getElementById('table-head').innerHTML = '';
                document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">No data found</td></tr>';
                return;
            }

            // Calculate pagination
            const startIndex = (currentPage - 1) * recordsPerPage;
            const endIndex = Math.min(startIndex + recordsPerPage, filteredData.length);
            const pageData = filteredData.slice(startIndex, endIndex);

            // Generate headers
            const headers = Object.keys(filteredData[0]);
            const headerHtml = headers.map(header => 
                `<th style="cursor: pointer;" onclick="sortByColumn('${header}')">${header}</th>`
            ).join('') + '<th>Actions</th>';
            document.getElementById('table-head').innerHTML = headerHtml;

            // Generate rows
            const rowsHtml = pageData.map(row => {
                const cellsHtml = headers.map(header => {
                    let value = row[header];
                    if (value === null) {
                        value = '<em style="color: #999;">NULL</em>';
                    } else if (header === 'password') {
                        value = '<em style="color: #666;">***encrypted***</em>';
                    } else if (typeof value === 'object') {
                        value = `<div class="json-preview">${JSON.stringify(value, null, 2)}</div>`;
                    } else if (String(value).length > 100) {
                        const fullValue = String(value);
                        value = `<span title="${fullValue.replace(/"/g, '&quot;')}">${fullValue.substring(0, 100)}...</span>`;
                    }
                    return `<td>${value}</td>`;
                }).join('');
                
                const actionsHtml = `
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-warning" onclick="editRecord(${row.id})" title="Edit">✏️</button>
                            <button class="btn btn-danger" onclick="deleteRecord(${row.id})" title="Delete">🗑️</button>
                        </div>
                    </td>
                `;
                
                return `<tr>${cellsHtml}${actionsHtml}</tr>`;
            }).join('');

            document.getElementById('table-body').innerHTML = rowsHtml;
        }

        function setupPagination() {
            const totalPages = Math.ceil(filteredData.length / recordsPerPage);
            const paginationElement = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                paginationElement.style.display = 'none';
                return;
            }

            paginationElement.style.display = 'flex';
            document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages} (${filteredData.length} records)`;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(filteredData.length / recordsPerPage);
            const newPage = currentPage + direction;
            
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                renderTable();
                setupPagination();
            }
        }

        function sortByColumn(column) {
            const isAsc = filteredData[0] && filteredData[1] && filteredData[0][column] <= filteredData[1][column];
            
            filteredData.sort((a, b) => {
                let aVal = a[column];
                let bVal = b[column];
                
                // Handle null values
                if (aVal === null && bVal === null) return 0;
                if (aVal === null) return isAsc ? -1 : 1;
                if (bVal === null) return isAsc ? 1 : -1;
                
                // Handle different data types
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (isAsc) {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                } else {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                }
            });
            
            currentPage = 1;
            renderTable();
            setupPagination();
        }

        function searchTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const columnFilter = document.getElementById('columnFilter').value;
            
            if (!searchTerm && !columnFilter) {
                filteredData = [...currentData];
            } else {
                filteredData = currentData.filter(row => {
                    const searchMatch = !searchTerm || Object.values(row).some(value => 
                        value !== null && String(value).toLowerCase().includes(searchTerm)
                    );
                    
                    const columnMatch = !columnFilter || (row[columnFilter] !== null && 
                        String(row[columnFilter]).toLowerCase().includes(searchTerm || ''));
                    
                    return columnFilter ? columnMatch : searchMatch;
                });
            }
            
            currentPage = 1;
            renderTable();
            setupPagination();
        }

        function filterByColumn() {
            searchTable(); // Reuse search logic
        }

        function showCreateForm() {
            editingId = null;
            document.getElementById('form-title').textContent = `Add New ${currentTable} Record`;
            generateForm();
            document.getElementById('form-overlay').style.display = 'flex';
        }

        function editRecord(id) {
            editingId = id;
            const record = currentData.find(r => r.id == id);
            document.getElementById('form-title').textContent = `Edit ${currentTable} Record #${id}`;
            generateForm(record);
            document.getElementById('form-overlay').style.display = 'flex';
        }

        function generateForm(record = null) {
            const formFields = document.getElementById('form-fields');
            formFields.innerHTML = '';

            tableStructure.forEach((field, index) => {
                // Skip auto-generated fields
                if (field.Field === 'id' || field.Field.includes('created_at') || field.Field.includes('updated_at')) {
                    return;
                }

                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'form-group';

                const label = document.createElement('label');
                label.textContent = field.Field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                const uniqueId = `field_${field.Field}_${index}_${Date.now()}`;
                label.setAttribute('for', uniqueId);

                let input;
                const fieldType = field.Type.toLowerCase();
                const isRequired = field.Null === 'NO' && !field.Default;

                // Handle password fields
                if (field.Field === 'password') {
                    if (record) {
                        const container = document.createElement('div');
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `change_password_${Date.now()}`;
                        
                        const checkboxLabel = document.createElement('label');
                        checkboxLabel.className = 'checkbox-wrapper';
                        checkboxLabel.innerHTML = `<input type="checkbox" id="${checkbox.id}"> Change Password`;
                        
                        const passwordInput = document.createElement('input');
                        passwordInput.type = 'password';
                        passwordInput.name = field.Field;
                        passwordInput.id = uniqueId;
                        passwordInput.placeholder = 'Enter new password';
                        passwordInput.disabled = true;
                        
                        checkboxLabel.querySelector('input').addEventListener('change', function() {
                            passwordInput.disabled = !this.checked;
                            passwordInput.value = '';
                        });
                        
                        container.appendChild(checkboxLabel);
                        container.appendChild(passwordInput);
                        fieldDiv.appendChild(label);
                        fieldDiv.appendChild(container);
                        formFields.appendChild(fieldDiv);
                        return;
                    } else {
                        input = document.createElement('input');
                        input.type = 'password';
                        input.placeholder = 'Enter password';
                        input.required = true;
                    }
                }
                // Handle JSON fields
                else if (fieldType.includes('json') || ['model_data', 'config_data', 'scenario_data', 'results_data', 'template_data', 'performance_metrics', 'modules', 'path_data', 'bottleneck_tasks', 'optimization_suggestions', 'expected_impact', 'complexity_distribution', 'seasonal_factors'].includes(field.Field)) {
                    input = document.createElement('textarea');
                    input.rows = 5;
                    input.placeholder = 'Enter valid JSON (e.g., {"key": "value"})';
                    
                    const hint = document.createElement('div');
                    hint.className = 'field-hint';
                    hint.textContent = 'Enter valid JSON format';
                    fieldDiv.appendChild(hint);
                }
                // Handle long text fields
                else if (fieldType.includes('text') || fieldType.includes('longtext')) {
                    input = document.createElement('textarea');
                    input.rows = 3;
                }
                // Handle enum fields
                else if (fieldType.includes('enum')) {
                    input = document.createElement('select');
                    const enumMatch = field.Type.match(/enum\((.*)\)/);
                    if (enumMatch) {
                        const enumValues = enumMatch[1].split(',').map(v => v.replace(/'/g, '').trim());
                        input.innerHTML = '<option value="">Select an option</option>' + 
                            enumValues.map(val => `<option value="${val}">${val}</option>`).join('');
                    }
                }
                // Handle date/time fields
                else if (fieldType.includes('date') && !fieldType.includes('time')) {
                    input = document.createElement('input');
                    input.type = 'date';
                }
                else if (fieldType.includes('time') && !fieldType.includes('date')) {
                    input = document.createElement('input');
                    input.type = 'time';
                }
                else if (fieldType.includes('datetime') || fieldType.includes('timestamp')) {
                    input = document.createElement('input');
                    input.type = 'datetime-local';
                }
                // Handle numeric fields
                else if (fieldType.includes('int') || fieldType.includes('decimal')) {
                    input = document.createElement('input');
                    input.type = 'number';
                    if (fieldType.includes('decimal')) {
                        input.step = '0.01';
                    }
                }
                // Default to text
                else {
                    input = document.createElement('input');
                    input.type = 'text';
                }

                input.name = field.Field;
                input.id = uniqueId;
                
                if (isRequired && field.Field !== 'password') {
                    input.required = true;
                }

                // Set values for existing records
                if (field.Field !== 'password' && record && record[field.Field] !== null) {
                    if (input.type === 'datetime-local' && record[field.Field]) {
                        const date = new Date(record[field.Field]);
                        input.value = date.toISOString().slice(0, 16);
                    } else if (fieldType.includes('json') && typeof record[field.Field] === 'object') {
                        input.value = JSON.stringify(record[field.Field], null, 2);
                    } else {
                        input.value = record[field.Field];
                    }
                } else if (field.Default && field.Default !== 'NULL') {
                    input.value = field.Default.replace(/'/g, '');
                }

                fieldDiv.appendChild(label);
                fieldDiv.appendChild(input);
                formFields.appendChild(fieldDiv);
            });
        }

        async function handleFormSubmission(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {};
            
            // Process form data
            for (let [key, value] of formData.entries()) {
                // Handle JSON fields
                if (['model_data', 'config_data', 'scenario_data', 'results_data', 'template_data', 'performance_metrics', 'modules', 'path_data', 'bottleneck_tasks', 'optimization_suggestions', 'expected_impact', 'complexity_distribution', 'seasonal_factors'].includes(key)) {
                    if (value) {
                        try {
                            JSON.parse(value); // Validate JSON
                            data[key] = value;
                        } catch (error) {
                            showNotification(`Invalid JSON in field ${key}: ${error.message}`, 'error');
                            return;
                        }
                    } else {
                        data[key] = null;
                    }
                } else if (key === 'password' && value) {
                    data[key] = 'HASH:' + value;
                } else {
                    data[key] = value || null;
                }
            }

            // Handle password updates
            if (editingId && currentTable === 'users') {
                const changePasswordCheckbox = document.querySelector('input[type="checkbox"][id^="change_password_"]');
                if (changePasswordCheckbox && !changePasswordCheckbox.checked) {
                    delete data.password;
                }
            }

            data.action = editingId ? 'update' : 'create';
            data.table = currentTable;
            if (editingId) {
                data.id = editingId;
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(data)
                });

                const result = await response.json();
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeForm();
                    await loadTableData();
                    await loadAllRecordCounts();
                } else {
                    showNotification('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }

        async function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete',
                        table: currentTable,
                        id: id
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showNotification(result.message, 'success');
                    await loadTableData();
                    await loadAllRecordCounts();
                } else {
                    showNotification('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
            }
        }

        async function refreshData() {
            if (currentTable) {
                await loadTableData();
                await loadAllRecordCounts();
                showNotification('Data refreshed successfully', 'success');
            }
        }

        function exportData() {
            if (filteredData.length === 0) {
                showNotification('No data to export', 'error');
                return;
            }

            const headers = Object.keys(filteredData[0]);
            const csvContent = [
                headers.join(','),
                ...filteredData.map(row => 
                    headers.map(header => {
                        let value = row[header];
                        if (value === null) value = '';
                        else if (typeof value === 'object') value = JSON.stringify(value);
                        else value = String(value).replace(/"/g, '""');
                        return `"${value}"`;
                    }).join(',')
                )
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${currentTable}_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showNotification(`Data exported as ${a.download}`, 'success');
        }

        function showTableInfo() {
            if (!currentTable || !tableStructure.length) {
                showNotification('Please select a table first', 'error');
                return;
            }

            let infoHtml = `<h3>Table Structure: ${currentTable}</h3><div style="max-height: 400px; overflow-y: auto;">`;
            infoHtml += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            infoHtml += '<tr style="background: #f8f9fa;"><th style="padding: 8px; border: 1px solid #ddd;">Field</th><th style="padding: 8px; border: 1px solid #ddd;">Type</th><th style="padding: 8px; border: 1px solid #ddd;">Null</th><th style="padding: 8px; border: 1px solid #ddd;">Key</th><th style="padding: 8px; border: 1px solid #ddd;">Default</th></tr>';
            
            tableStructure.forEach(field => {
                infoHtml += `<tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">${field.Field}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${field.Type}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${field.Null}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${field.Key}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${field.Default || 'None'}</td>
                </tr>`;
            });
            
            infoHtml += '</table></div>';
            
            const modal = document.createElement('div');
            modal.className = 'form-overlay';
            modal.style.display = 'flex';
            modal.innerHTML = `
                <div class="form-modal">
                    <button class="close-btn" onclick="this.closest('.form-overlay').remove()">×</button>
                    ${infoHtml}
                    <div style="text-align: center; margin-top: 20px;">
                        <button class="btn btn-secondary" onclick="this.closest('.form-overlay').remove()">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function closeForm() {
            document.getElementById('form-overlay').style.display = 'none';
            document.getElementById('record-form').reset();
            editingId = null;
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'n':
                        e.preventDefault();
                        if (currentTable) showCreateForm();
                        break;
                    case 'r':
                        e.preventDefault();
                        if (currentTable) refreshData();
                        break;
                    case 'e':
                        e.preventDefault();
                        if (currentTable) exportData();
                        break;
                }
            }
            if (e.key === 'Escape') {
                closeForm();
            }
        });

        console.log('MACTA Database Management System loaded successfully!');
    </script>
</body>
</html>
        