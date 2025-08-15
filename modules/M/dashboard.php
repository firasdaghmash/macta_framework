<?php
// modules/M/dashboard.php - Advanced Simulation Analytics Dashboard
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../shared/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle AJAX requests for dashboard data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_dashboard_data':
                $data = getDashboardData($conn);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_process_comparison':
                $processIds = json_decode($_POST['process_ids'], true);
                $comparison = getProcessComparison($conn, $processIds);
                echo json_encode(['success' => true, 'comparison' => $comparison]);
                break;
                
            case 'get_resource_utilization':
                $utilization = getResourceUtilization($conn);
                echo json_encode(['success' => true, 'utilization' => $utilization]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Dashboard data functions
function getDashboardData($conn) {
    $data = [];
    
    // Total processes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM process_models");
    $stmt->execute();
    $data['totalProcesses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total simulations run
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM simulation_results");
    $stmt->execute();
    $data['totalSimulations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent simulations
    $stmt = $conn->prepare("
        SELECT sr.*, pm.name as process_name 
        FROM simulation_results sr
        JOIN process_models pm ON sr.process_id = pm.id
        ORDER BY sr.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $data['recentSimulations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top bottleneck processes
    $stmt = $conn->prepare("
        SELECT pm.name, pm.id, COUNT(*) as bottleneck_count
        FROM simulation_results sr
        JOIN process_models pm ON sr.process_id = pm.id
        WHERE JSON_UNQUOTE(JSON_EXTRACT(sr.results_data, '$[0].bottlenecks')) != '[]'
        GROUP BY pm.id, pm.name
        ORDER BY bottleneck_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $data['bottleneckProcesses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Average improvements
    $data['improvements'] = calculateImprovements($conn);
    
    return $data;
}

function getProcessComparison($conn, $processIds) {
    $comparison = [];
    
    foreach ($processIds as $processId) {
        $stmt = $conn->prepare("
            SELECT pm.name, sr.results_data, sr.created_at
            FROM process_models pm
            LEFT JOIN simulation_results sr ON pm.id = sr.process_id
            WHERE pm.id = ?
            ORDER BY sr.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$processId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['results_data']) {
            $results = json_decode($result['results_data'], true);
            $comparison[] = [
                'processName' => $result['name'],
                'processId' => $processId,
                'results' => $results,
                'lastSimulation' => $result['created_at']
            ];
        }
    }
    
    return $comparison;
}

function getResourceUtilization($conn) {
    // Simulate resource utilization data
    $resources = [
        'Business Analyst' => ['utilization' => 0.85, 'cost' => 75.00],
        'Project Manager' => ['utilization' => 0.70, 'cost' => 85.00],
        'Junior Analyst' => ['utilization' => 0.95, 'cost' => 45.00],
        'Senior Developer' => ['utilization' => 0.80, 'cost' => 95.00],
        'Quality Specialist' => ['utilization' => 0.60, 'cost' => 65.00]
    ];
    
    return $resources;
}

function calculateImprovements($conn) {
    // Calculate average improvements from simulations
    $stmt = $conn->prepare("
        SELECT results_data 
        FROM simulation_results 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $timeImprovement = 0;
    $costImprovement = 0;
    $count = 0;
    
    foreach ($results as $result) {
        $data = json_decode($result['results_data'], true);
        if (count($data) >= 2) {
            $current = $data[0];
            $optimized = $data[1];
            
            if ($current['totalTime'] > 0) {
                $timeImprovement += (($current['totalTime'] - $optimized['totalTime']) / $current['totalTime']) * 100;
            }
            if ($current['totalCost'] > 0) {
                $costImprovement += (($current['totalCost'] - $optimized['totalCost']) / $current['totalCost']) * 100;
            }
            $count++;
        }
    }
    
    return [
        'timeImprovement' => $count > 0 ? round($timeImprovement / $count, 1) : 0,
        'costImprovement' => $count > 0 ? round($costImprovement / $count, 1) : 0
    ];
}

// Get processes for comparison
$stmt = $conn->prepare("SELECT id, name FROM process_models ORDER BY updated_at DESC LIMIT 20");
$stmt->execute();
$processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation Dashboard - MACTA Framework</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .metric-card {
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), #4a90e2);
            color: white;
            border-left: none;
        }
        
        .metric-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .metric-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .improvement-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-left: none;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .comparison-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .process-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-high { background: #dc3545; color: white; }
        .status-medium { background: #ffc107; color: #212529; }
        .status-low { background: #28a745; color: white; }
        
        .bottleneck-list {
            list-style: none;
            padding: 0;
        }
        
        .bottleneck-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .resource-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .utilization-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .utilization-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > 
                <a href="index.php">Process Modeling</a> > 
                Simulation Dashboard
            </div>
            <div>
                <a href="simulation.php" class="btn btn-primary">▶️ Run New Simulation</a>
                <a href="visual_builder.php" class="btn btn-secondary">🔧 Process Builder</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>🎯 Process Simulation Dashboard</h2>
        
        <!-- Key Metrics -->
        <div class="dashboard-grid">
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalProcesses">0</div>
                <div class="metric-label">Total Processes</div>
            </div>
            
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalSimulations">0</div>
                <div class="metric-label">Simulations Run</div>
            </div>
            
            <div class="dashboard-card improvement-card">
                <div class="metric-value" id="avgTimeImprovement">0%</div>
                <div class="metric-label">Avg Time Improvement</div>
            </div>
            
            <div class="dashboard-card improvement-card">
                <div class="metric-value" id="avgCostImprovement">0%</div>
                <div class="metric-label">Avg Cost Savings</div>
            </div>
        </div>

        <!-- Process Comparison Section -->
        <div class="comparison-section">
            <h3>📊 Process Comparison</h3>
            <div class="process-selector">
                <label>Select processes to compare:</label>
                <select id="processSelector" multiple style="min-width: 300px; height: 100px;">
                    <?php foreach ($processes as $process): ?>
                        <option value="<?php echo $process['id']; ?>"><?php echo htmlspecialchars($process['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" onclick="compareProcesses()">Compare</button>
            </div>
            <div class="chart-container">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Simulations -->
            <div class="dashboard-card">
                <h3>🕒 Recent Simulations</h3>
                <div class="table-container">
                    <table style="width: 100%; font-size: 14px;">
                        <thead>
                            <tr>
                                <th>Process</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentSimulationsTable">
                            <!-- Data loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottleneck Analysis -->
            <div class="dashboard-card">
                <h3>⚠️ Bottleneck Analysis</h3>
                <ul class="bottleneck-list" id="bottleneckList">
                    <!-- Data loaded via JavaScript -->
                </ul>
            </div>

            <!-- Resource Utilization -->
            <div class="dashboard-card">
                <h3>👥 Resource Utilization</h3>
                <div class="resource-grid" id="resourceGrid">
                    <!-- Data loaded via JavaScript -->
                </div>
            </div>

            <!-- Performance Trends -->
            <div class="dashboard-card">
                <h3>📈 Performance Trends</h3>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        let dashboardData = null;
        let comparisonChart = null;
        let trendsChart = null;

        // Load dashboard data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadResourceUtilization();
        });

        function loadDashboardData() {
            fetch('dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_dashboard_data' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dashboardData = data.data;
                    updateMetrics();
                    updateRecentSimulations();
                    updateBottleneckAnalysis();
                    createTrendsChart();
                }
            })
            .catch(error => console.error('Error loading dashboard data:', error));
        }

        function updateMetrics() {
            document.getElementById('totalProcesses').textContent = dashboardData.totalProcesses;
            document.getElementById('totalSimulations').textContent = dashboardData.totalSimulations;
            document.getElementById('avgTimeImprovement').textContent = dashboardData.improvements.timeImprovement + '%';
            document.getElementById('avgCostImprovement').textContent = dashboardData.improvements.costImprovement + '%';
        }

        function updateRecentSimulations() {
            const table = document.getElementById('recentSimulationsTable');
            table.innerHTML = '';

            dashboardData.recentSimulations.forEach(sim => {
                const row = table.insertRow();
                row.innerHTML = `
                    <td title="${sim.process_name}">${sim.process_name.length > 20 ? sim.process_name.substring(0, 20) + '...' : sim.process_name}</td>
                    <td>${new Date(sim.created_at).toLocaleDateString()}</td>
                    <td><span class="status-badge status-low">Completed</span></td>
                `;
            });
        }

        function updateBottleneckAnalysis() {
            const list = document.getElementById('bottleneckList');
            list.innerHTML = '';

            if (dashboardData.bottleneckProcesses.length === 0) {
                const item = document.createElement('li');
                item.innerHTML = '<div class="bottleneck-item">No bottlenecks detected in recent simulations ✅</div>';
                list.appendChild(item);
                return;
            }

            dashboardData.bottleneckProcesses.forEach(process => {
                const item = document.createElement('li');
                const severity = process.bottleneck_count > 5 ? 'high' : process.bottleneck_count > 2 ? 'medium' : 'low';
                
                item.innerHTML = `
                    <div class="bottleneck-item">
                        <span>${process.name}</span>
                        <span class="status-badge status-${severity}">${process.bottleneck_count} issues</span>
                    </div>
                `;
                list.appendChild(item);
            });
        }

        function loadResourceUtilization() {
            fetch('dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_resource_utilization' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateResourceGrid(data.utilization);
                }
            })
            .catch(error => console.error('Error loading resource utilization:', error));
        }

        function updateResourceGrid(resources) {
            const grid = document.getElementById('resourceGrid');
            grid.innerHTML = '';

            Object.entries(resources).forEach(([name, data]) => {
                const card = document.createElement('div');
                card.className = 'resource-card';
                
                const utilizationPercent = data.utilization * 100;
                const utilizationColor = utilizationPercent > 80 ? '#dc3545' : utilizationPercent > 60 ? '#ffc107' : '#28a745';
                
                card.innerHTML = `
                    <strong>${name}</strong>
                    <div class="utilization-bar">
                        <div class="utilization-fill" style="width: ${utilizationPercent}%; background-color: ${utilizationColor};"></div>
                    </div>
                    <small>${utilizationPercent.toFixed(1)}% utilized</small><br>
                    <small>${data.cost}/hour</small>
                `;
                grid.appendChild(card);
            });
        }

        function compareProcesses() {
            const selector = document.getElementById('processSelector');
            const selectedIds = Array.from(selector.selectedOptions).map(option => option.value);
            
            if (selectedIds.length < 2) {
                alert('Please select at least 2 processes to compare');
                return;
            }

            fetch('dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'get_process_comparison',
                    process_ids: JSON.stringify(selectedIds)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    createComparisonChart(data.comparison);
                }
            })
            .catch(error => console.error('Error comparing processes:', error));
        }

        function createComparisonChart(comparison) {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            
            if (comparisonChart) {
                comparisonChart.destroy();
            }

            const labels = comparison.map(c => c.processName);
            const currentTimes = comparison.map(c => {
                const currentScenario = c.results.find(r => r.name === 'Current State');
                return currentScenario ? currentScenario.totalTime : 0;
            });
            const optimizedTimes = comparison.map(c => {
                const optimizedScenario = c.results.find(r => r.name === 'Optimized');
                return optimizedScenario ? optimizedScenario.totalTime : 0;
            });

            comparisonChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Current State (min)',
                            data: currentTimes,
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Optimized (min)',
                            data: optimizedTimes,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Time (minutes)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Process Time Comparison: Current vs Optimized'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        function createTrendsChart() {
            const ctx = document.getElementById('trendsChart').getContext('2d');
            
            // Generate sample trend data
            const last30Days = [];
            const improvementData = [];
            const efficiencyData = [];
            
            for (let i = 29; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last30Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                // Simulate improvement trends
                improvementData.push(Math.random() * 20 + 10); // 10-30% improvement
                efficiencyData.push(Math.random() * 15 + 75); // 75-90% efficiency
            }

            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last30Days,
                    datasets: [
                        {
                            label: 'Process Improvement (%)',
                            data: improvementData,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Resource Efficiency (%)',
                            data: efficiencyData,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Percentage (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: '30-Day Performance Trends'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        // Auto-refresh dashboard every 5 minutes
        setInterval(loadDashboardData, 300000);

        // Add keyboard shortcuts for quick actions
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        loadDashboardData();
                        break;
                    case 's':
                        e.preventDefault();
                        window.location.href = 'simulation.php';
                        break;
                }
            }
        });

        // Add tooltips for better user experience
        function addTooltips() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                });
            });
        }

        // Initialize tooltips when page loads
        document.addEventListener('DOMContentLoaded', addTooltips);
    </script>
</body>
</html>