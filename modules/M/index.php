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
            margin-bottom: 30px;
        }
        
        .quick-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
            display: block;
        }
        
        .feature-title {
            font-size: 1.4em;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
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
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .workflow-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .workflow-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .workflow-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .workflow-step {
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
            margin: 0 auto 15px;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .step-description {
            font-size: 0.9em;
            color: #666;
        }
        
        .workflow-arrow {
            font-size: 1.5em;
            color: #ccc;
            margin: 0 10px;
        }
        
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #333;
        }
        
        .activity-time {
            font-size: 0.85em;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .workflow-steps {
                flex-direction: column;
            }
            
            .workflow-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
            
            .quick-stats {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .hero-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > Process Modeling
            </div>
            <div>
                <a href="projects.php" class="btn btn-secondary">📋 View Projects</a>
                <a href="dashboard.php" class="btn btn-primary">📊 Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Hero Section -->
        <div class="module-hero">
            <div class="hero-content">
                <h1 class="hero-title">🔄 Process Modeling & Simulation</h1>
                <p class="hero-subtitle">
                    Model, analyze, and optimize your business processes with advanced simulation capabilities
                </p>
                
                <div class="quick-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['totalProcesses']; ?></span>
                        <span class="stat-label">Active Processes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['totalSimulations']; ?></span>
                        <span class="stat-label">Simulations Run</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['totalResources']; ?></span>
                        <span class="stat-label">Available Resources</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow Overview -->
        <div class="workflow-section">
            <h2 class="workflow-title">🎯 Complete Process Optimization Workflow</h2>
            <div class="workflow-steps">
                <div class="workflow-step">
                    <div class="step-number">1</div>
                    <div class="step-title">Model</div>
                    <div class="step-description">Create visual process diagrams using BPMN standards</div>
                </div>
                <div class="workflow-arrow">→</div>
                <div class="workflow-step">
                    <div class="step-number">2</div>
                    <div class="step-title">Configure</div>
                    <div class="step-description">Assign resources, time, and costs to each step</div>
                </div>
                <div class="workflow-arrow">→</div>
                <div class="workflow-step">
                    <div class="step-number">3</div>
                    <div class="step-title">Simulate</div>
                    <div class="step-description">Run multiple scenarios to identify bottlenecks</div>
                </div>
                <div class="workflow-arrow">→</div>
                <div class="workflow-step">
                    <div class="step-number">4</div>
                    <div class="step-title">Optimize</div>
                    <div class="step-description">Implement improvements based on simulation results</div>
                </div>
            </div>
        </div>

        <!-- Main Features -->
        <div class="features-grid">
            <!-- Visual Process Builder -->
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3 class="feature-title">Visual Process Builder</h3>
                <p class="feature-description">
                    Create comprehensive business process diagrams with our intuitive drag-and-drop BPMN editor. 
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

            <!-- Process Documentation -->
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3 class="feature-title">Documentation & Compliance</h3>
                <p class="feature-description">
                    Generate comprehensive process documentation, standard operating procedures, and compliance reports. 
                    Export to multiple formats and maintain version control.
                </p>
                <div class="feature-actions">
                    <a href="documentation.php" class="btn-feature btn-primary">
                        📝 Create Docs
                    </a>
                    <a href="compliance.php" class="btn-feature btn-secondary">
                        ✅ Compliance
                    </a>
                </div>
            </div>

            <!-- Process Optimization -->
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">AI-Powered Optimization</h3>
                <p class="feature-description">
                    Leverage artificial intelligence to identify optimization opportunities, suggest process improvements, 
                    and predict performance outcomes based on historical data and industry best practices.
                </p>
                <div class="feature-actions">
                    <a href="optimization.php" class="btn-feature btn-primary">
                        🤖 Optimize Now
                    </a>
                    <a href="recommendations.php" class="btn-feature btn-secondary">
                        💡 Get Suggestions
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h3 style="margin-bottom: 20px;">🕒 Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <div style="text-align: center; color: #666; padding: 20px;">
                    <p>No recent activity. Start by creating your first process!</p>
                    <a href="visual_builder.php" class="btn-feature btn-primary" style="margin-top: 10px;">
                        🚀 Create Process
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php echo $activity['type'] === 'process' ? '🔄' : '⚡'; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php echo htmlspecialchars($activity['activity']); ?>: 
                                <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                            </div>
                            <div class="activity-time">
                                <?php echo timeAgo($activity['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case 'b':
                            e.preventDefault();
                            window.location.href = 'visual_builder.php';
                            break;
                        case 's':
                            e.preventDefault();
                            window.location.href = 'simulation.php';
                            break;
                        case 'd':
                            e.preventDefault();
                            window.location.href = 'dashboard.php';
                            break;
                        case 'r':
                            e.preventDefault();
                            window.location.href = 'resources.php';
                            break;
                    }
                }
            });

            // Add tooltips for keyboard shortcuts
            const shortcuts = [
                { selector: 'a[href="visual_builder.php"]', shortcut: 'Ctrl+B' },
                { selector: 'a[href="simulation.php"]', shortcut: 'Ctrl+S' },
                { selector: 'a[href="dashboard.php"]', shortcut: 'Ctrl+D' },
                { selector: 'a[href="resources.php"]', shortcut: 'Ctrl+R' }
            ];

            shortcuts.forEach(item => {
                const elements = document.querySelectorAll(item.selector);
                elements.forEach(el => {
                    el.title = `${el.textContent.trim()} (${item.shortcut})`;
                });
            });

            // Add click tracking for analytics
            document.querySelectorAll('.btn-feature').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.textContent.trim();
                    const card = this.closest('.feature-card');
                    const feature = card ? card.querySelector('.feature-title').textContent : 'Unknown';
                    
                    // You could send this to an analytics service
                    console.log('Feature clicked:', { feature, action });
                });
            });
        });

        // Add a simple notification system for quick feedback
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
                color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
                border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#b6d4fe'};
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 1000;
                max-width: 300px;
                font-size: 14px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Check if we're coming from a successful action
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success')) {
            const action = urlParams.get('action');
            let message = 'Action completed successfully!';
            
            switch(action) {
                case 'process_created':
                    message = 'Process created successfully! 🎉';
                    break;
                case 'simulation_completed':
                    message = 'Simulation completed successfully! 📊';
                    break;
                case 'resource_added':
                    message = 'Resource added successfully! 👥';
                    break;
            }
            
            showNotification(message, 'success');
            
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2629746) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>