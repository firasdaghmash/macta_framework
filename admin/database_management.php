<?php
// admin/database_management.php - Database Management with CRUD Operations
require_once '../config/database.php';

// Use the Database class from config
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed. Please check your configuration.");
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
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Table configurations organized by tabs
$table_configs = [
    'users_projects' => [
        'title' => 'Users & Projects Management',
        'icon' => '👥',
        'tables' => [
            'users' => ['name' => 'Users', 'icon' => '👤'],
            'projects' => ['name' => 'Projects', 'icon' => '📁']
        ]
    ],
    'process_management' => [
        'title' => 'Process Management',
        'icon' => '🔄',
        'tables' => [
            'process_arrival_configs' => ['name' => 'Arrival Configs', 'icon' => '📅'],
            'process_models' => ['name' => 'Process Models', 'icon' => '🏗️'],
            'process_path_analysis' => ['name' => 'Path Analysis', 'icon' => '🛤️'],
            'process_step_resources' => ['name' => 'Step Resources', 'icon' => '🔗'],
            'process_tasks' => ['name' => 'Process Tasks', 'icon' => '📋']
        ]
    ],
    'resources_management' => [
        'title' => 'Resources Management',
        'icon' => '🏭',
        'tables' => [
            'resources' => ['name' => 'Resources', 'icon' => '📦'],
            'resource_allocations' => ['name' => 'Allocations', 'icon' => '📊'],
            'resource_templates' => ['name' => 'Templates', 'icon' => '📄'],
            'enhanced_resources' => ['name' => 'Enhanced Resources', 'icon' => '🚀']
        ]
    ],
    'simulation_management' => [
        'title' => 'Simulation Management',
        'icon' => '🎮',
        'tables' => [
            'simulation_configs' => ['name' => 'Configurations', 'icon' => '⚙️'],
            'simulation_metrics' => ['name' => 'Metrics', 'icon' => '📈'],
            'simulation_resources' => ['name' => 'Sim Resources', 'icon' => '🎯'],
            'simulation_results' => ['name' => 'Results', 'icon' => '📊'],
            'simulation_templates' => ['name' => 'Templates', 'icon' => '📋']
        ]
    ],
    'timers_activities' => [
        'title' => 'Timers & Activities Management',
        'icon' => '⏱️',
        'tables' => [
            'timer_averages' => ['name' => 'Timer Averages', 'icon' => '⏰'],
            'timer_sessions' => ['name' => 'Timer Sessions', 'icon' => '🕐'],
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

        .data-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
            margin-top: 20px;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
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
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .data-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
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
            max-width: 600px;
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

        .record-count {
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .search-box {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            margin: 10px 0;
            width: 300px;
            font-size: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #ff6b35;
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

            .search-box {
                width: 100%;
            }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Management</h1>
            <p>Comprehensive CRUD Operations for MACTA Framework</p>
            <div class="connection-status">
                ✅ Connected to: MACTA Framework Database
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
                    <div class="table-card-icon"><?= $table_info['icon'] ?></div>
                    <h3><?= $table_info['name'] ?></h3>
                    <div class="record-count" id="count-<?= $table_name ?>">Loading...</div>
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
                            📤 Export Data
                        </button>
                    </div>
                </div>

                <input type="text" class="search-box" placeholder="Search records..." onkeyup="searchTable()" id="searchInput">

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
                    <button type="button" class="btn" onclick="closeForm()" 
                            style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTable = '';
        let currentData = [];
        let tableStructure = [];
        let editingId = null;

        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, setting up event listeners...');
            
            // Tab switching
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    console.log('Tab clicked:', tabId);
                    
                    // Update tab buttons
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update tab content
                    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
                    document.getElementById(tabId).style.display = 'block';
                    
                    // Reset current table selection
                    currentTable = '';
                    document.getElementById('crud-section').classList.remove('active');
                    document.querySelectorAll('.table-card').forEach(card => card.classList.remove('selected'));
                });
            });

            // Table card selection - attach to all cards
            document.querySelectorAll('.table-card').forEach(card => {
                console.log('Attaching click to card:', card.dataset.table);
                card.addEventListener('click', function() {
                    const tableName = this.dataset.table;
                    console.log('Table card clicked:', tableName);
                    selectTable(tableName);
                });
            });

            // Load initial counts
            loadAllRecordCounts();
            
            console.log('All event listeners attached successfully!');
        });

        async function loadAllRecordCounts() {
            const tables = <?= json_encode(array_merge(...array_column($table_configs, 'tables'))) ?>;
            
            for (const [tableName, tableInfo] of Object.entries(tables)) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'fetch',
                            table: tableName
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        const countElement = document.getElementById(`count-${tableName}`);
                        if (countElement) {
                            countElement.textContent = `${result.data.length} records`;
                        }
                    } else {
                        // Table doesn't exist - mark as unavailable
                        const countElement = document.getElementById(`count-${tableName}`);
                        if (countElement) {
                            countElement.textContent = 'Table not found';
                            countElement.style.background = '#dc3545';
                        }
                        console.warn(`Table ${tableName} not found:`, result.message);
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
            
            // Update UI
            document.querySelectorAll('.table-card').forEach(card => card.classList.remove('selected'));
            document.querySelector(`[data-table="${tableName}"]`).classList.add('selected');
            
            document.getElementById('crud-section').classList.add('active');
            document.getElementById('crud-title').textContent = `Managing: ${tableName}`;
            
            // Clear search
            document.getElementById('searchInput').value = '';
            
            // Show loading message
            document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Loading table structure...</td></tr>';
            
            try {
                // Load table structure and data
                await loadTableStructure();
                await loadTableData();
            } catch (error) {
                showMessage('Error loading table: ' + error.message, 'error');
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
                    console.log('Table structure loaded for:', currentTable, tableStructure);
                } else {
                    showMessage('Error loading table structure: ' + result.message, 'error');
                    console.error('Structure error:', result.message);
                }
            } catch (error) {
                showMessage('Error loading table structure: ' + error.message, 'error');
                console.error('Structure fetch error:', error);
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
                console.log('Data fetch result for', currentTable, ':', result);
                
                if (result.success) {
                    currentData = result.data;
                    renderTable();
                    
                    // Update record count
                    const countElement = document.getElementById(`count-${currentTable}`);
                    if (countElement) {
                        countElement.textContent = `${result.data.length} records`;
                        countElement.style.background = '#17a2b8'; // Reset to normal color
                    }
                } else {
                    showMessage('Error loading data: ' + result.message, 'error');
                    document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Error loading data: ' + result.message + '</td></tr>';
                }
            } catch (error) {
                showMessage('Error loading data: ' + error.message, 'error');
                console.error('Data fetch error:', error);
                document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">Error: ' + error.message + '</td></tr>';
            }
        }

        function renderTable() {
            if (currentData.length === 0) {
                document.getElementById('table-head').innerHTML = '';
                document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="loading">No data found</td></tr>';
                return;
            }

            // Generate table headers
            const headers = Object.keys(currentData[0]);
            const headerHtml = headers.map(header => `<th>${header}</th>`).join('') + '<th>Actions</th>';
            document.getElementById('table-head').innerHTML = headerHtml;

            // Generate table rows
            const rowsHtml = currentData.map(row => {
                const cellsHtml = headers.map(header => {
                    let value = row[header];
                    if (value === null) value = '<em>NULL</em>';
                    else if (header === 'password') value = '<em>***encrypted***</em>'; // Hide password
                    else if (typeof value === 'object') value = '<div class="json-preview">' + JSON.stringify(value, null, 2) + '</div>';
                    else if (String(value).length > 100) value = String(value).substring(0, 100) + '...';
                    return `<td>${value}</td>`;
                }).join('');
                
                const actionsHtml = `
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-warning" onclick="editRecord(${row.id})">✏️ Edit</button>
                            <button class="btn btn-danger" onclick="deleteRecord(${row.id})">🗑️ Delete</button>
                        </div>
                    </td>
                `;
                
                return `<tr>${cellsHtml}${actionsHtml}</tr>`;
            }).join('');

            document.getElementById('table-body').innerHTML = rowsHtml;
        }

        function searchTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const tableRows = document.querySelectorAll('#table-body tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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
                if (field.Field === 'id' || field.Field.includes('created_at') || field.Field.includes('updated_at')) {
                    return; // Skip auto-generated fields
                }

                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'form-group';

                const label = document.createElement('label');
                label.textContent = field.Field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Create unique IDs to avoid duplicates
                const uniqueId = `field_${field.Field}_${index}_${Date.now()}`;
                label.setAttribute('for', uniqueId);

                let input;
                const fieldType = field.Type.toLowerCase();
                const isRequired = field.Null === 'NO';

                // Special handling for password fields
                if (field.Field === 'password') {
                    if (record) {
                        // Editing existing user - show password change option
                        const passwordContainer = document.createElement('div');
                        
                        const changePasswordCheckbox = document.createElement('input');
                        changePasswordCheckbox.type = 'checkbox';
                        changePasswordCheckbox.id = `change_password_${Date.now()}`;
                        changePasswordCheckbox.style.marginRight = '8px';
                        
                        const checkboxLabel = document.createElement('label');
                        checkboxLabel.textContent = 'Change Password';
                        checkboxLabel.style.fontWeight = 'normal';
                        checkboxLabel.style.cursor = 'pointer';
                        checkboxLabel.setAttribute('for', changePasswordCheckbox.id);
                        checkboxLabel.prepend(changePasswordCheckbox);
                        
                        const passwordInput = document.createElement('input');
                        passwordInput.type = 'password';
                        passwordInput.name = field.Field;
                        passwordInput.id = uniqueId;
                        passwordInput.placeholder = 'Enter new password (leave blank to keep current)';
                        passwordInput.style.marginTop = '8px';
                        passwordInput.disabled = true;
                        
                        changePasswordCheckbox.addEventListener('change', function() {
                            passwordInput.disabled = !this.checked;
                            if (!this.checked) {
                                passwordInput.value = '';
                            }
                        });
                        
                        passwordContainer.appendChild(checkboxLabel);
                        passwordContainer.appendChild(passwordInput);
                        fieldDiv.appendChild(label);
                        fieldDiv.appendChild(passwordContainer);
                        formFields.appendChild(fieldDiv);
                        return;
                    } else {
                        // Creating new user - require password
                        input = document.createElement('input');
                        input.type = 'password';
                        input.placeholder = 'Enter password';
                        input.required = true;
                    }
                } else if (fieldType.includes('text') || fieldType.includes('json')) {
                    input = document.createElement('textarea');
                    input.rows = 3;
                } else if (fieldType.includes('enum')) {
                    input = document.createElement('select');
                    const enumValues = field.Type.match(/enum\((.*)\)/)[1].split(',').map(v => v.replace(/'/g, ''));
                    input.innerHTML = '<option value="">Select an option</option>' + 
                        enumValues.map(val => `<option value="${val}">${val}</option>`).join('');
                } else if (fieldType.includes('date')) {
                    input = document.createElement('input');
                    input.type = 'date';
                } else if (fieldType.includes('time')) {
                    input = document.createElement('input');
                    input.type = 'time';
                } else if (fieldType.includes('datetime') || fieldType.includes('timestamp')) {
                    input = document.createElement('input');
                    input.type = 'datetime-local';
                } else if (fieldType.includes('int') || fieldType.includes('decimal')) {
                    input = document.createElement('input');
                    input.type = 'number';
                    if (fieldType.includes('decimal')) {
                        input.step = '0.01';
                    }
                } else {
                    input = document.createElement('input');
                    input.type = 'text';
                }

                input.name = field.Field;
                input.id = uniqueId;
                if (isRequired && !field.Default && field.Field !== 'password') {
                    input.required = true;
                }

                // Set default values (skip password field as it's handled above)
                if (field.Field !== 'password') {
                    if (record && record[field.Field] !== null) {
                        if (input.type === 'datetime-local' && record[field.Field]) {
                            // Convert MySQL datetime to HTML datetime-local format
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
                }

                fieldDiv.appendChild(label);
                fieldDiv.appendChild(input);
                formFields.appendChild(fieldDiv);
            });
        }

        function closeForm() {
            document.getElementById('form-overlay').style.display = 'none';
            document.getElementById('record-form').reset();
            editingId = null;
        }

        // Form submission
        document.getElementById('record-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                // Handle JSON fields
                const field = tableStructure.find(f => f.Field === key);
                if (field && field.Type.toLowerCase().includes('json')) {
                    try {
                        data[key] = value ? JSON.parse(value) : null;
                    } catch (error) {
                        showMessage(`Invalid JSON in field ${key}: ${error.message}`, 'error');
                        return;
                    }
                } else if (key === 'password' && value) {
                    // Hash password if provided
                    data[key] = 'HASH:' + value; // We'll handle this server-side
                } else {
                    data[key] = value || null;
                }
            }

            // For password updates, only include password if checkbox is checked
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
                    showMessage(result.message, 'success');
                    closeForm();
                    await loadTableData();
                } else {
                    showMessage('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            }
        });

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
                    showMessage(result.message, 'success');
                    await loadTableData();
                } else {
                    showMessage('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            }
        }

        async function refreshData() {
            if (currentTable) {
                await loadTableData();
                showMessage('Data refreshed successfully', 'success');
            }
        }

        function exportData() {
            if (currentData.length === 0) {
                showMessage('No data to export', 'error');
                return;
            }

            // Convert data to CSV
            const headers = Object.keys(currentData[0]);
            const csvContent = [
                headers.join(','),
                ...currentData.map(row => 
                    headers.map(header => {
                        let value = row[header];
                        if (value === null) value = '';
                        else if (typeof value === 'object') value = JSON.stringify(value);
                        else value = String(value).replace(/"/g, '""'); // Escape quotes
                        return `"${value}"`;
                    }).join(',')
                )
            ].join('\n');

            // Download CSV file
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${currentTable}_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showMessage(`Data exported successfully as ${a.download}`, 'success');
        }

        function showMessage(message, type) {
            // Remove existing messages
            document.querySelectorAll('.error-message, .success-message').forEach(el => el.remove());

            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'error' ? 'error-message' : 'success-message';
            messageDiv.textContent = message;

            // Insert message at the top of crud section
            const crudSection = document.getElementById('crud-section');
            crudSection.insertBefore(messageDiv, crudSection.firstChild);

            // Auto-remove message after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
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

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            if (currentTable) {
                loadTableData();
            }
            loadAllRecordCounts();
        }, 30000);

        // Show loading message on page load
        console.log('MACTA Database Management script loaded!');
        console.log('Connected to MACTA Framework Database');
    </script>
</body>
</html>