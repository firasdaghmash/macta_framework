<?php
// modules/M/index.php - Updated Process Modeling Module with Simulation Integration
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

// Get quick stats for dashboard
$stats = [];

// Total processes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM process_models");
$stmt->execute();
$stats['totalProcesses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total simulations
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM simulation_results");
$stmt->execute();
$stats['totalSimulations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total resources
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM simulation_resources");
$stmt->execute();
$stats['totalResources'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent activity
$stmt = $conn->prepare("
    SELECT 'process' as type, name, created_at, 'Created new process' as activity
    FROM process_models 
    UNION ALL
    SELECT 'simulation' as type, 
           (SELECT name FROM process_models WHERE id = sr.process_id) as name,
           sr.created_at, 
           'Ran simulation' as activity
    FROM simulation_results sr
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Modeling - MACTA Framework</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .module-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 12px;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero-title {
            font-size: 2.5em;
            margin-bottom: 15px;
            font-weight: 300;
        }
        
        .hero-subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 25px;
        }
        
        .hero-cta {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .hero-cta:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4CAF50;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .feature-card:nth-child(1) { border-top-color: #4CAF50; }
        .feature-card:nth-child(2) { border-top-color: #2196F3; }
        .feature-card:nth-child(3) { border-top-color: #FF9800; }
        .feature-card:nth-child(4) { border-top-color: #9C27B0; }
        .feature-card:nth-child(5) { border-top-color: #F44336; }
        .feature-card:nth-child(6) { border-top-color: #607D8B; }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .feature-title {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .feature-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-feature {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .activity-title {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .activity-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .activity-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .type-process {
            background: #e8f5e8;
            color: #4CAF50;
        }
        
        .type-simulation {
            background: #e3f2fd;
            color: #2196F3;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .workflow-guide {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .workflow-step {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            position: relative;
        }
        
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #4CAF50;
            color: white;
            border-radius: 50%;
            line-height: 40px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .keyboard-shortcuts {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 0.8em;
            opacity: 0.8;
            z-index: 1000;
        }
        
        .keyboard-shortcuts h5 {
            margin: 0 0 10px 0;
        }
        
        .shortcut {
            margin: 5px 0;
        }
        
        .key {
            background: #555;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../index.php" class="back-button">← Back to MACTA Framework</a>
        
        <!-- Hero Section -->
        <div class="module-hero">
            <div class="hero-content">
                <h1 class="hero-title">🎯 Process Modeling</h1>
                <p class="hero-subtitle">Design, simulate, and optimize your business processes with advanced modeling tools</p>
                <a href="visual_builder.php" class="hero-cta">🚀 Start Building Processes</a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['totalProcesses'] ?></div>
                <div class="stat-label">Total Processes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['totalSimulations'] ?></div>
                <div class="stat-label">Simulations Run</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['totalResources'] ?></div>
                <div class="stat-label">Resources Available</div>
            </div>
        </div>

        <!-- Workflow Guide -->
        <div class="workflow-guide">
            <h3>🗺️ Process Optimization Workflow</h3>
            <p>Follow this proven workflow to maximize your process improvement results:</p>
            <div class="workflow-steps">
                <div class="workflow-step">
                    <div class="step-number">1</div>
                    <h4>Design</h4>
                    <p>Create visual process models</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">2</div>
                    <h4>Resources</h4>
                    <p>Configure required resources</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">3</div>
                    <h4>Simulate</h4>
                    <p>Run multiple scenarios</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">4</div>
                    <h4>Analyze</h4>
                    <p>Review performance metrics</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">5</div>
                    <h4>Optimize</h4>
                    <p>Implement improvements</p>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="feature-grid">
            <!-- Visual Process Builder -->
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3 class="feature-title">Visual Process Builder</h3>
                <p class="feature-description">
                    Create stunning process diagrams with drag-and-drop functionality. 
                    Design workflows, define decision points, and map complex business logic visually.
                </p>
                <div class="feature-actions">
                    <a href="visual_builder.php" class="btn-feature btn-primary">
                        🚀 Start Building
                    </a>
                    <a href="templates.php" class="btn-feature btn-secondary">
                        📋 Templates
                    </a>
                </div>
            </div>

            <!-- Process Simulation -->
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Advanced Process Simulation</h3>
                <p class="feature-description">
                    Run sophisticated simulations with multiple scenarios. Analyze current state, optimized workflows, 
                    and future state possibilities with detailed performance metrics and bottleneck identification.
                </p>
                <div class="feature-actions">
                    <a href="simulation.php" class="btn-feature btn-primary">
                        ▶️ Run Simulation
                    </a>
                    <a href="dashboard.php" class="btn-feature btn-secondary">
                        📊 View Results
                    </a>
                </div>
            </div>

            <!-- Resource Management -->
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Resource Management</h3>
                <p class="feature-description">
                    Manage human resources, equipment, software, and materials. Track costs, availability, 
                    skill levels, and utilization rates to optimize resource allocation across processes.
                </p>
                <div class="feature-actions">
                    <a href="resources.php" class="btn-feature btn-primary">
                        🛠️ Manage Resources
                    </a>
                    <a href="resources.php#templates" class="btn-feature btn-secondary">
                        📦 Load Templates
                    </a>
                </div>
            </div>

            <!-- Analytics Dashboard -->
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3 class="feature-title">Analytics & Insights</h3>
                <p class="feature-description">
                    Comprehensive analytics dashboard with performance trends, cost analysis, bottleneck detection, 
                    and process comparison tools. Make data-driven decisions for process optimization.
                </p>
                <div class="feature-actions">
                    <a href="dashboard.php" class="btn-feature btn-primary">
                        📊 View Dashboard
                    </a>
                    <a href="reports.php" class="btn-feature btn-secondary">
                        📋 Generate Reports
                    </a>
                </div>
            </div>

            <!-- AI-Powered Optimization -->
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">AI-Powered Optimization</h3>
                <p class="feature-description">
                    Leverage artificial intelligence to identify optimization opportunities, suggest process improvements, 
                    and predict performance outcomes based on historical data and industry best practices.
                </p>
                <div class="feature-actions">
                    <a href="AI_opt.php" class="btn-feature btn-primary">
                        🤖 Optimize Now
                    </a>
                    <a href="AI_opt.php#recommendations" class="btn-feature btn-secondary">
                        💡 Get Suggestions
                    </a>
                </div>
            </div>

            <!-- Documentation & Compliance -->
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3 class="feature-title">Documentation & Compliance</h3>
                <p class="feature-description">
                    Generate comprehensive process documentation, standard operating procedures, and compliance reports. 
                    Export to multiple formats and maintain version control with automated workflows.
                </p>
                <div class="feature-actions">
                    <a href="docs.php" class="btn-feature btn-primary">
                        📝 Create Docs
                    </a>
                    <a href="docs.php#compliance" class="btn-feature btn-secondary">
                        ✅ Compliance
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if (!empty($recentActivity)): ?>
        <div class="recent-activity">
            <h3 class="activity-title">🕒 Recent Activity</h3>
            <?php foreach ($recentActivity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <h4><?= htmlspecialchars($activity['name'] ?? 'Unknown') ?></h4>
                        <p><?= htmlspecialchars($activity['activity']) ?> • <?= date('M j, Y', strtotime($activity['created_at'])) ?></p>
                    </div>
                    <div class="activity-type type-<?= $activity['type'] ?>">
                        <?= ucfirst($activity['type']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="recent-activity">
            <h3 class="activity-title">🕒 Recent Activity</h3>
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>No recent activity. Start by creating your first process model!</p>
                <a href="visual_builder.php" class="btn-feature btn-primary">🎨 Create Process</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Keyboard Shortcuts -->
        <div class="keyboard-shortcuts">
            <h5>⌨️ Shortcuts</h5>
            <div class="shortcut"><span class="key">Ctrl+B</span> - Process Builder</div>
            <div class="shortcut"><span class="key">Ctrl+S</span> - Run Simulation</div>
            <div class="shortcut"><span class="key">Ctrl+R</span> - Manage Resources</div>
            <div class="shortcut"><span class="key">Ctrl+D</span> - Dashboard</div>
        </div>
    </div>

    <script>
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'b':
                        e.preventDefault();
                        window.location.href = 'visual_builder.php';
                        break;
                    case 's':
                        e.preventDefault();
                        window.location.href = 'simulation.php';
                        break;
                    case 'r':
                        e.preventDefault();
                        window.location.href = 'resources.php';
                        break;
                    case 'd':
                        e.preventDefault();
                        window.location.href = 'dashboard.php';
                        break;
                }
            }
        });

        // Add hover effects
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Hide shortcuts after 10 seconds
        setTimeout(() => {
            const shortcuts = document.querySelector('.keyboard-shortcuts');
            if (shortcuts) {
                shortcuts.style.opacity = '0.3';
            }
        }, 10000);
    </script>
</body>
</html>