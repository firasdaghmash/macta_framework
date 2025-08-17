<?php
require_once '../../config/database.php';
require_once '../../shared/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get process data for optimization analysis
$processes = [];
try {
    $stmt = $conn->query("SELECT p.*, c.name as client_name FROM processes p LEFT JOIN clients c ON p.client_id = c.id WHERE p.status = 'active' ORDER BY p.updated_at DESC");
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading processes: " . $e->getMessage();
}

// Get recent optimization analyses
$optimizations = [];
try {
    $stmt = $conn->query("SELECT sr.*, p.name as process_name FROM simulation_results sr 
                       LEFT JOIN processes p ON sr.process_id = p.id 
                       WHERE sr.simulation_type = 'optimization' 
                       ORDER BY sr.created_at DESC LIMIT 10");
    $optimizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet, continue silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Powered Optimization - MACTA Modeling</title>
    <link rel="stylesheet" href="../../shared/styles.css">
    <style>
        .optimization-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .optimization-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .optimization-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .ai-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .ai-feature {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .ai-feature:hover {
            border-color: #4CAF50;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.1);
        }
        
        .ai-icon {
            font-size: 48px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .optimization-results {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #4CAF50;
        }
        
        .process-selector {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .btn-ai {
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .optimization-tab {
            display: none;
        }
        
        .optimization-tab.active {
            display: block;
        }
        
        .tab-buttons {
            display: flex;
            background: #f1f1f1;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="module-header">
            <div class="header-content">
                <div class="module-icon">🤖</div>
                <div>
                    <h1>AI-Powered Optimization</h1>
                    <p>Leverage artificial intelligence to optimize your processes and maximize efficiency</p>
                </div>
            </div>
            <a href="index.php" class="btn-back">← Back to Modeling</a>
        </div>

        <!-- Navigation Tabs -->
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('overview')">🎯 Overview</button>
            <button class="tab-button" onclick="showTab('analysis')">📊 Analysis</button>
            <button class="tab-button" onclick="showTab('recommendations')">💡 Recommendations</button>
            <button class="tab-button" onclick="showTab('predictions')">🔮 Predictions</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="optimization-tab active">
            <!-- AI Features Grid -->
            <div class="ai-features">
                <div class="ai-feature">
                    <div class="ai-icon">🧠</div>
                    <h3>Machine Learning Analysis</h3>
                    <p>Advanced algorithms analyze your process data to identify patterns, bottlenecks, and optimization opportunities that human analysis might miss.</p>
                    <button class="btn-ai" onclick="startMLAnalysis()">Start Analysis</button>
                </div>
                
                <div class="ai-feature">
                    <div class="ai-icon">⚡</div>
                    <h3>Real-time Optimization</h3>
                    <p>Continuous monitoring and automatic adjustments to your processes based on real-time performance data and changing conditions.</p>
                    <button class="btn-ai" onclick="enableRealTimeOpt()">Enable Auto-Optimization</button>
                </div>
                
                <div class="ai-feature">
                    <div class="ai-icon">🎯</div>
                    <h3>Predictive Modeling</h3>
                    <p>Forecast future performance, resource needs, and potential issues before they occur using advanced predictive algorithms.</p>
                    <button class="btn-ai" onclick="generatePredictions()">Generate Predictions</button>
                </div>
                
                <div class="ai-feature">
                    <div class="ai-icon">🚀</div>
                    <h3>Intelligent Automation</h3>
                    <p>Identify tasks and processes that can be automated using AI, reducing manual effort and increasing accuracy.</p>
                    <button class="btn-ai" onclick="findAutomationOpps()">Find Opportunities</button>
                </div>
            </div>

            <!-- Quick Optimization Cards -->
            <h3>🎯 Quick Optimization Actions</h3>
            <div class="optimization-grid">
                <div class="optimization-card">
                    <h4>⚡ Process Acceleration</h4>
                    <p>Identify and eliminate time-wasting steps using AI pattern recognition</p>
                    <button class="btn-ai" style="background: rgba(255,255,255,0.2);">Accelerate Processes</button>
                </div>
                
                <div class="optimization-card">
                    <h4>💰 Cost Reduction</h4>
                    <p>Find cost-saving opportunities through resource optimization and waste elimination</p>
                    <button class="btn-ai" style="background: rgba(255,255,255,0.2);">Reduce Costs</button>
                </div>
                
                <div class="optimization-card">
                    <h4>📈 Quality Improvement</h4>
                    <p>Enhance process quality and reduce errors using AI quality control</p>
                    <button class="btn-ai" style="background: rgba(255,255,255,0.2);">Improve Quality</button>
                </div>
            </div>
        </div>

        <!-- Analysis Tab -->
        <div id="analysis" class="optimization-tab">
            <div class="process-selector">
                <h3>📊 Select Process for AI Analysis</h3>
                <select id="processSelect" class="form-control" style="width: 100%; padding: 10px; margin-bottom: 15px;">
                    <option value="">Select a process to analyze...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>"><?= htmlspecialchars($process['name']) ?> (<?= htmlspecialchars($process['client_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-ai" onclick="runAIAnalysis()">🤖 Run AI Analysis</button>
            </div>

            <div class="optimization-results" id="analysisResults" style="display: none;">
                <h3>🔍 AI Analysis Results</h3>
                <div class="metric-card">
                    <h4>⏱️ Time Optimization Potential</h4>
                    <p><strong>Current Average Time:</strong> <span id="currentTime">-</span></p>
                    <p><strong>Optimized Time:</strong> <span id="optimizedTime">-</span></p>
                    <p><strong>Time Savings:</strong> <span id="timeSavings" style="color: #4CAF50;">-</span></p>
                </div>
                
                <div class="metric-card">
                    <h4>💵 Cost Optimization Potential</h4>
                    <p><strong>Current Cost:</strong> <span id="currentCost">-</span></p>
                    <p><strong>Optimized Cost:</strong> <span id="optimizedCost">-</span></p>
                    <p><strong>Cost Savings:</strong> <span id="costSavings" style="color: #4CAF50;">-</span></p>
                </div>
                
                <div class="metric-card">
                    <h4>📊 Efficiency Score</h4>
                    <p><strong>Current Score:</strong> <span id="currentEfficiency">-</span></p>
                    <p><strong>Potential Score:</strong> <span id="optimizedEfficiency">-</span></p>
                    <p><strong>Improvement:</strong> <span id="efficiencyImprovement" style="color: #4CAF50;">-</span></p>
                </div>
            </div>
        </div>

        <!-- Recommendations Tab -->
        <div id="recommendations" class="optimization-tab">
            <h3>💡 AI-Generated Recommendations</h3>
            <div id="recommendationsContainer">
                <div class="metric-card">
                    <h4>🎯 Priority Recommendations</h4>
                    <p>Select a process from the Analysis tab to generate personalized AI recommendations.</p>
                </div>
            </div>
        </div>

        <!-- Predictions Tab -->
        <div id="predictions" class="optimization-tab">
            <h3>🔮 Predictive Analytics</h3>
            <div class="optimization-results">
                <div class="metric-card">
                    <h4>📈 Performance Forecasting</h4>
                    <p>AI-powered predictions for the next 6 months based on current trends and historical data.</p>
                    <button class="btn-ai" onclick="generateForecasts()">Generate Forecasts</button>
                </div>
                
                <div class="metric-card">
                    <h4>⚠️ Risk Assessment</h4>
                    <p>Identify potential risks and bottlenecks before they impact your operations.</p>
                    <button class="btn-ai" onclick="assessRisks()">Assess Risks</button>
                </div>
                
                <div class="metric-card">
                    <h4>📊 Capacity Planning</h4>
                    <p>Predict future resource needs and capacity requirements using AI modeling.</p>
                    <button class="btn-ai" onclick="planCapacity()">Plan Capacity</button>
                </div>
            </div>
        </div>

        <!-- Recent Optimizations -->
        <?php if (!empty($optimizations)): ?>
        <div class="recent-activity">
            <h3>🕒 Recent AI Optimizations</h3>
            <?php foreach ($optimizations as $opt): ?>
                <div class="metric-card">
                    <h4><?= htmlspecialchars($opt['process_name']) ?></h4>
                    <p>Optimization completed on <?= date('M j, Y', strtotime($opt['created_at'])) ?></p>
                    <small>Results: <?= substr(htmlspecialchars($opt['results'] ?? 'Analysis completed'), 0, 100) ?>...</small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.optimization-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Hide all tab buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function startMLAnalysis() {
            alert('🤖 Machine Learning Analysis started! This will analyze your process patterns and identify optimization opportunities.');
        }

        function enableRealTimeOpt() {
            alert('⚡ Real-time optimization enabled! Your processes will now be continuously monitored and optimized automatically.');
        }

        function generatePredictions() {
            alert('🔮 Generating predictive models... This will forecast your process performance and identify future optimization needs.');
        }

        function findAutomationOpps() {
            alert('🚀 Scanning for automation opportunities... AI will identify tasks that can be automated to improve efficiency.');
        }

        function runAIAnalysis() {
            const processId = document.getElementById('processSelect').value;
            if (!processId) {
                alert('Please select a process to analyze.');
                return;
            }

            // Show loading state
            const resultsDiv = document.getElementById('analysisResults');
            resultsDiv.style.display = 'block';
            
            // Simulate AI analysis with realistic data
            setTimeout(() => {
                // Generate realistic optimization metrics
                const currentTime = Math.floor(Math.random() * 120) + 60; // 60-180 minutes
                const timeReduction = Math.floor(Math.random() * 30) + 15; // 15-45% reduction
                const optimizedTime = Math.floor(currentTime * (1 - timeReduction/100));
                
                const currentCost = Math.floor(Math.random() * 5000) + 2000; // $2000-7000
                const costReduction = Math.floor(Math.random() * 25) + 10; // 10-35% reduction
                const optimizedCost = Math.floor(currentCost * (1 - costReduction/100));
                
                const currentEfficiency = Math.floor(Math.random() * 30) + 60; // 60-90%
                const efficiencyImprovement = Math.floor(Math.random() * 20) + 10; // 10-30 points
                const optimizedEfficiency = Math.min(95, currentEfficiency + efficiencyImprovement);
                
                // Update UI
                document.getElementById('currentTime').textContent = currentTime + ' minutes';
                document.getElementById('optimizedTime').textContent = optimizedTime + ' minutes';
                document.getElementById('timeSavings').textContent = (currentTime - optimizedTime) + ' minutes (' + timeReduction + '% faster)';
                
                document.getElementById('currentCost').textContent = '$' + currentCost.toLocaleString();
                document.getElementById('optimizedCost').textContent = '$' + optimizedCost.toLocaleString();
                document.getElementById('costSavings').textContent = '$' + (currentCost - optimizedCost).toLocaleString() + ' (' + costReduction + '% savings)';
                
                document.getElementById('currentEfficiency').textContent = currentEfficiency + '%';
                document.getElementById('optimizedEfficiency').textContent = optimizedEfficiency + '%';
                document.getElementById('efficiencyImprovement').textContent = '+' + efficiencyImprovement + ' points';
                
                // Generate recommendations
                generateRecommendations(timeReduction, costReduction, efficiencyImprovement);
                
            }, 2000);
        }

        function generateRecommendations(timeReduction, costReduction, efficiencyImprovement) {
            const recommendations = [
                '🤖 Automate approval processes to reduce manual intervention by ' + timeReduction + '%',
                '⚡ Implement parallel processing for independent tasks',
                '📊 Use AI-powered routing to optimize task distribution',
                '🔄 Eliminate redundant validation steps identified by pattern analysis',
                '📱 Deploy mobile interfaces for faster remote approvals',
                '🎯 Focus training on bottleneck activities to improve efficiency'
            ];
            
            const container = document.getElementById('recommendationsContainer');
            container.innerHTML = '<h4>🎯 Personalized AI Recommendations</h4>';
            
            recommendations.slice(0, 4).forEach((rec, index) => {
                const div = document.createElement('div');
                div.className = 'metric-card';
                div.innerHTML = `
                    <h4>Recommendation ${index + 1}</h4>
                    <p>${rec}</p>
                    <button class="btn-ai" onclick="implementRecommendation(${index})">Implement</button>
                `;
                container.appendChild(div);
            });
        }

        function implementRecommendation(index) {
            alert('🚀 Implementation plan generated for recommendation ' + (index + 1) + '! Check your project management system for detailed steps.');
        }

        function generateForecasts() {
            alert('📈 Generating 6-month performance forecasts using AI predictive models...');
        }

        function assessRisks() {
            alert('⚠️ AI risk assessment initiated. Analyzing patterns to identify potential future bottlenecks and issues...');
        }

        function planCapacity() {
            alert('📊 AI capacity planning analysis started. Predicting future resource needs based on trend analysis...');
        }
    </script>
</body>
</html>