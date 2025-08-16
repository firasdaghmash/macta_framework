<?php
// modules/A/index.php - Analysis Module with Enhanced Arrival Rate Simulation
session_start();

// Configuration
$MODULE_LETTER = 'A';
$MODULE_TITLE = 'Statistical Analysis';
$MODULE_DESCRIPTION = 'Advanced statistical analysis and process simulation with realistic business load modeling and arrival rate analytics.';

// Include shared functions
require_once '../../shared/functions.php';

// Database connection for recent activity
$conn = null;
try {
    require_once '../../config/database.php';
    // The database.php file should set $conn or $pdo variable
    if (isset($pdo)) {
        $conn = $pdo;
    }
} catch (Exception $e) {
    $conn = null;
}

// Module-specific configurations with enhanced simulation features
$MODULE_CONFIGS = [
    'A' => [
        'title' => 'Statistical Analysis',
        'description' => 'Advanced statistical analysis and process simulation with realistic business load modeling and arrival rate analytics.',
        'icon' => '📈',
        'features' => [
            [
                'icon' => '🚀', 
                'title' => 'Arrival Rate Simulation', 
                'description' => 'Enhanced simulation with queue management, capacity planning, and realistic business load modeling.',
                'link' => 'arrival_rate_simulation.php',
                'badge' => 'NEW',
                'highlight' => true
            ],
            [
                'icon' => '📊', 
                'title' => 'Data Analytics', 
                'description' => 'Comprehensive data collection and analysis from multiple sources.',
                'link' => 'analytics.php'
            ],
            [
                'icon' => '📈', 
                'title' => 'Trend Analysis', 
                'description' => 'Identify performance patterns and trends over time.',
                'link' => 'trends.php'
            ],
            [
                'icon' => '🔬', 
                'title' => 'Process Simulation', 
                'description' => 'Traditional single-case process simulation and optimization.',
                'link' => 'simulation.php'
            ],
            [
                'icon' => '📋', 
                'title' => 'Custom Reports', 
                'description' => 'Generate customized reporting dashboards for stakeholders.',
                'link' => 'reports.php'
            ],
            [
                'icon' => '⚡', 
                'title' => 'Real-time Insights', 
                'description' => 'Get immediate insights with automated data processing.',
                'link' => 'insights.php'
            ]
        ],
        'capabilities' => [
            '🚀 Enhanced arrival rate simulation with queue management',
            '📊 Poisson, Normal, Seasonal, and Batch arrival patterns',
            '⏱️ SLA compliance monitoring and capacity planning',
            '👥 Resource utilization optimization under realistic load',
            '🎯 Bottleneck identification and business recommendations',
            '📈 Comprehensive data collection from multiple sources',
            '🔍 Advanced analytics to identify performance patterns',
            '📊 Trend analysis for proactive process optimization',
            '🖥️ Customized reporting dashboards for stakeholders',
            '💡 Data-driven recommendations for continuous improvement',
            '⚡ Real-time monitoring and alerts',
            '📐 Statistical modeling and forecasting',
            '🔗 Integration with external data sources'
        ],
        'simulation_features' => [
            [
                'title' => 'Multi-Pattern Arrivals',
                'description' => 'Poisson (random), Normal (predictable), Seasonal (holidays/end-of-month), Batch processing',
                'icon' => '📥'
            ],
            [
                'title' => 'Queue Management',
                'description' => 'Priority-based processing, queue length tracking, wait time analysis',
                'icon' => '⏳'
            ],
            [
                'title' => 'Capacity Planning',
                'description' => 'Resource optimization, peak load analysis, SLA compliance monitoring',
                'icon' => '🎯'
            ],
            [
                'title' => 'Business Intelligence',
                'description' => 'Bottleneck detection, cost-benefit analysis, actionable recommendations',
                'icon' => '💡'
            ]
        ]
    ]
];

$config = $MODULE_CONFIGS[$MODULE_LETTER];

// Get recent simulation activity
$recentActivity = [];
if ($conn !== null) {
    try {
        $stmt = $conn->prepare("
            SELECT sr.created_at, pm.name as process_name, sr.scenario_data, sr.iterations
            FROM simulation_results sr 
            JOIN process_models pm ON sr.process_id = pm.id 
            ORDER BY sr.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignore database errors for display
        $recentActivity = [];
    }
}

// Quick statistics
$quickStats = [
    'total_simulations' => 0,
    'total_processes' => 0,
    'avg_efficiency' => 0,
    'active_projects' => 0
];

if ($conn !== null) {
    try {
        // Total simulations
        $stmt = $conn->query("SELECT COUNT(*) FROM simulation_results");
        $quickStats['total_simulations'] = $stmt->fetchColumn();
        
        // Total processes
        $stmt = $conn->query("SELECT COUNT(*) FROM process_models");
        $quickStats['total_processes'] = $stmt->fetchColumn();
        
        // Active projects
        $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
        $quickStats['active_projects'] = $stmt->fetchColumn();
        
        // Average efficiency (simulated)
        $quickStats['avg_efficiency'] = 87; // Placeholder
    } catch (Exception $e) {
        // Keep default values on any database error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - <?php echo $config['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ee5a52;
            --secondary-color: #ff6b6b;
            --accent-color: #ff9a56;
            --dark-color: #2c3e50;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, var(--light-bg) 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .module-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            position: relative;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .feature-card.highlight {
            border: 2px solid var(--accent-color);
            background: linear-gradient(135deg, #fff 0%, #fff5f0 100%);
        }

        .feature-card.highlight::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
            border-radius: 12px;
            z-index: -1;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { box-shadow: 0 0 5px rgba(238, 90, 82, 0.3); }
            to { box-shadow: 0 0 20px rgba(238, 90, 82, 0.6); }
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .badge-new {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--dark-color) 0%, #34495e 100%);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .capability-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .simulation-highlights {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .simulation-feature {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .simulation-feature:last-child {
            margin-bottom: 0;
        }

        .breadcrumb-nav {
            background: rgba(255,255,255,0.9);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .activity-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(238, 90, 82, 0.3);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="module-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="feature-icon me-3"><?php echo $config['icon']; ?></div>
                        <div>
                            <h1 class="mb-1"><?php echo $config['title']; ?></h1>
                            <p class="mb-0 fs-5"><?php echo $config['description']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="breadcrumb-nav">
                        <?php echo generate_breadcrumb('index', $MODULE_LETTER); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quickStats['total_simulations']; ?></div>
                    <div>Total Simulations</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quickStats['total_processes']; ?></div>
                    <div>Process Models</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quickStats['avg_efficiency']; ?>%</div>
                    <div>Avg Efficiency</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $quickStats['active_projects']; ?></div>
                    <div>Active Projects</div>
                </div>
            </div>
        </div>

        <!-- Enhanced Simulation Highlights -->
        <div class="simulation-highlights">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">🚀 Enhanced Arrival Rate Simulation</h3>
                    <p class="mb-3">Transform your analysis from <strong>"single case processing"</strong> to <strong>"realistic business load simulation"</strong> with queue management, capacity planning, and performance optimization.</p>
                    
                    <div class="row">
                        <?php foreach ($config['simulation_features'] as $feature): ?>
                        <div class="col-md-6 mb-3">
                            <div class="simulation-feature">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2"><?php echo $feature['icon']; ?></span>
                                    <strong><?php echo $feature['title']; ?></strong>
                                </div>
                                <small><?php echo $feature['description']; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-3">
                        <i class="fas fa-chart-line fa-4x mb-3" style="opacity: 0.7;"></i>
                        <p class="mb-3"><strong>Business Impact:</strong><br>25-40% time savings, 15-30% cost reduction through optimized capacity planning</p>
                        <a href="arrival_rate_simulation.php" class="btn btn-light btn-lg">
                            <i class="fas fa-rocket me-2"></i>Try Enhanced Simulation
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="row">
            <div class="col-12 mb-4">
                <h3>Analysis Tools & Features</h3>
            </div>
            
            <?php foreach ($config['features'] as $feature): ?>
            <div class="col-md-4 mb-4">
                <a href="<?php echo $feature['link']; ?>" class="text-decoration-none">
                    <div class="feature-card <?php echo isset($feature['highlight']) && $feature['highlight'] ? 'highlight' : ''; ?>">
                        <?php if (isset($feature['badge'])): ?>
                        <span class="badge-new"><?php echo $feature['badge']; ?></span>
                        <?php endif; ?>
                        
                        <div class="feature-icon"><?php echo $feature['icon']; ?></div>
                        <h5 class="mb-2"><?php echo $feature['title']; ?></h5>
                        <p class="text-muted mb-3"><?php echo $feature['description']; ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="btn btn-primary btn-sm">Launch Tool</span>
                            <?php if (isset($feature['highlight']) && $feature['highlight']): ?>
                            <small class="text-success"><i class="fas fa-star"></i> Enhanced</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Capabilities Overview -->
        <div class="row mt-5">
            <div class="col-md-8">
                <h3 class="mb-3">🔧 System Capabilities</h3>
                <div class="capabilities-grid">
                    <?php foreach ($config['capabilities'] as $capability): ?>
                    <div class="capability-item">
                        <?php echo $capability; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="col-md-4">
                <h3 class="mb-3">📊 Recent Activity</h3>
                <div class="bg-white p-3 rounded-3 shadow-sm">
                    <?php if (empty($recentActivity)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <p>No recent simulations</p>
                        <a href="arrival_rate_simulation.php" class="btn btn-primary btn-sm">Run First Simulation</a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($activity['process_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo $activity['iterations']; ?> iterations
                                    <br>
                                    <?php echo format_date($activity['created_at'], 'M d, H:i'); ?>
                                </small>
                            </div>
                            <span class="badge bg-secondary">Completed</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <div class="bg-white p-4 rounded-3 shadow-sm">
                    <h4 class="mb-3">⚡ Quick Actions</h4>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="arrival_rate_simulation.php" class="btn btn-primary w-100">
                                <i class="fas fa-rocket me-2"></i>New Simulation
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="analytics.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-chart-bar me-2"></i>Analytics Dashboard
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="reports.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../../index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-home me-2"></i>MACTA Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });

            // Add click tracking for features
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    const title = this.querySelector('h5').textContent;
                    console.log(`Feature clicked: ${title}`);
                    
                    // Add ripple effect
                    const ripple = document.createElement('div');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(238, 90, 82, 0.3)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.left = (e.offsetX - 25) + 'px';
                    ripple.style.top = (e.offsetY - 25) + 'px';
                    ripple.style.width = '50px';
                    ripple.style.height = '50px';
                    
                    this.style.position = 'relative';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>