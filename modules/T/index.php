<?php
// Template for other MACTA modules (A, C, T, A2)
// Copy this file to create other modules: modules/[LETTER]/index.php

// Example: modules/A/index.php (Analysis Module)
session_start();

// Configuration
$MODULE_LETTER = 'T'; // Change this for each module: A, C, T, A2
$MODULE_TITLE = 'Training Module'; // Change this for each module
$MODULE_DESCRIPTION = 'All Training plans and process for etup and furure changes.'; // Change this

// Module-specific configurations
$MODULE_CONFIGS = [
    'A' => [
        'title' => 'Statistical Analysis',
        'description' => 'Detailed statistical analysis reports generated from collected data to identify trends, patterns, and opportunities for improvement.',
        'icon' => '📈',
        'features' => [
            ['icon' => '📊', 'title' => 'Data Analytics', 'description' => 'Comprehensive data collection and analysis from multiple sources.', 'link' => 'analytics.php'],
            ['icon' => '📈', 'title' => 'Trend Analysis', 'description' => 'Identify performance patterns and trends over time.', 'link' => 'trends.php'],
            ['icon' => '📋', 'title' => 'Custom Reports', 'description' => 'Generate customized reporting dashboards for stakeholders.', 'link' => 'reports.php'],
            ['icon' => '⚡', 'title' => 'Real-time Insights', 'description' => 'Get immediate insights with automated data processing.', 'link' => 'insights.php']
        ],
        'capabilities' => [
            'Comprehensive data collection from multiple sources',
            'Advanced analytics to identify performance patterns',
            'Trend analysis for proactive process optimization',
            'Customized reporting dashboards for stakeholders',
            'Data-driven recommendations for continuous improvement',
            'Real-time monitoring and alerts',
            'Statistical modeling and forecasting',
            'Integration with external data sources'
        ]
    ],
    'C' => [
        'title' => 'Customization',
        'description' => 'Tailored job descriptions and customized client portals providing access to all process documentation and resources.',
        'icon' => '⚙️',
        'features' => [
            ['icon' => '📝', 'title' => 'Job Descriptions', 'description' => 'Create customized job descriptions with specific requirements.', 'link' => 'job_descriptions.php'],
            ['icon' => '🖥️', 'title' => 'Client Portal', 'description' => 'Manage client portals with role-based access control.', 'link' => 'client_portal.php'],
            ['icon' => '👥', 'title' => 'Team Profiles', 'description' => 'Detailed team profiles with skills and expertise.', 'link' => 'team_profiles.php'],
            ['icon' => '📚', 'title' => 'Knowledge Base', 'description' => 'Searchable knowledge base with best practices.', 'link' => 'knowledge_base.php']
        ],
        'capabilities' => [
            'Tailored job descriptions incorporating client requirements',
            'Customized client portals with secure access',
            'Role-based access control and permissions',
            'Team member skill alignment with client expectations',
            'Performance metrics and target establishment',
            'Real-time updates and notifications',
            'Document management and version control',
            'Collaborative editing and review processes'
        ]
    ],
    'T' => [
        'title' => 'Training Program',
        'description' => 'Tailor-made training programs featuring real client examples and detailed scenarios to ensure team members are fully prepared.',
        'icon' => '🎓',
        'features' => [
            ['icon' => '📚', 'title' => 'Training Modules', 'description' => 'Create and manage comprehensive training modules.', 'link' => 'modules.php'],
            ['icon' => '🎯', 'title' => 'Scenarios', 'description' => 'Real-world scenarios and case studies for practical learning.', 'link' => 'scenarios.php'],
            ['icon' => '📊', 'title' => 'Progress Tracking', 'description' => 'Monitor training progress and performance evaluation.', 'link' => 'progress.php'],
            ['icon' => '🏆', 'title' => 'Certification', 'description' => 'Issue certificates and track completion status.', 'link' => 'certification.php']
        ],
        'capabilities' => [
            'Customized programs based on client-specific processes',
            'Hands-on practice with real-world scenarios',
            'Interactive learning modules and assessments',
            'Progress tracking and performance evaluation',
            'Certification and completion tracking',
            'Continuous skill development and updates',
            'Integration with job descriptions and requirements',
            'Feedback collection and program improvement'
        ]
    ],
    'A2' => [
        'title' => 'Assessment (Metrics)',
        'description' => 'Comprehensive tools to measure and track key process metrics, ensuring performance targets are consistently met or exceeded.',
        'icon' => '📊',
        'features' => [
            ['icon' => '📈', 'title' => 'Metrics Dashboard', 'description' => 'Real-time performance dashboards and KPI tracking.', 'link' => 'dashboard.php'],
            ['icon' => '⏱️', 'title' => 'Performance Tracking', 'description' => 'Monitor response times, accuracy rates, and efficiency.', 'link' => 'performance.php'],
            ['icon' => '🔔', 'title' => 'Alerts & Notifications', 'description' => 'Threshold alerts for proactive issue resolution.', 'link' => 'alerts.php'],
            ['icon' => '😊', 'title' => 'Customer Satisfaction', 'description' => 'Feedback collection and satisfaction monitoring.', 'link' => 'satisfaction.php']
        ],
        'capabilities' => [
            'Real-time performance dashboards and KPI tracking',
            'Automated data collection and processing',
            'Customizable metrics based on client requirements',
            'Threshold alerts for proactive issue resolution',
            'Trend analysis for continuous improvement',
            'Customer satisfaction monitoring and reporting',
            'Performance benchmarking and comparisons',
            'Automated report generation and distribution'
        ]
    ]
];

// Get current module configuration
$config = $MODULE_CONFIGS[$MODULE_LETTER] ?? $MODULE_CONFIGS['T'];

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
$colors = get_module_colors($MODULE_LETTER);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['title']; ?> - MACTA Framework</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, <?php echo $colors['secondary']; ?> 0%, <?php echo $colors['primary']; ?> 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, <?php echo $colors['secondary']; ?> 0%, <?php echo $colors['primary']; ?> 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .module-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .module-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .module-description {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .navigation {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumb {
            color: #6c757d;
        }

        .breadcrumb a {
            color: <?php echo $colors['primary']; ?>;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .main-content {
            padding: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: <?php echo $colors['primary']; ?>;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: <?php echo $colors['primary']; ?>;
        }

        .feature-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .btn {
            background: <?php echo $colors['primary']; ?>;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn:hover {
            background: <?php echo $colors['secondary']; ?>;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .info-section {
            background: #e8f4f8;
            border-radius: 10px;
            padding: 30px;
            margin-top: 40px;
        }

        .info-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .info-list {
            list-style: none;
            padding: 0;
        }

        .info-list li {
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list li::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 15px;
            font-size: 18px;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: <?php echo $colors['primary']; ?>;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: white;
            transform: translateY(-2px);
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: <?php echo $colors['primary']; ?>;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .navigation {
                flex-direction: column;
                gap: 15px;
            }
            
            .main-content {
                padding: 20px;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <a href="../../index.php" class="back-button">← Back to Framework</a>
    
    <div class="container">
        <div class="header">
            <div class="module-icon"><?php echo $config['icon']; ?></div>
            <h1 class="module-title"><?php echo $config['title']; ?></h1>
            <p class="module-description"><?php echo $config['description']; ?></p>
        </div>

        <div class="navigation">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > <?php echo $config['title']; ?>
            </div>
            <div>
                <a href="#" class="btn btn-secondary">View Projects</a>
            </div>
        </div>

        <div class="main-content">
            <!-- Statistics Section -->
            <div class="stats-section">
                <div class="stat-card">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Active Projects</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Completed Tasks</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Reports Generated</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Data Points</span>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="features-grid">
                <?php foreach ($config['features'] as $feature): ?>
                <div class="feature-card" onclick="window.location.href='<?php echo $feature['link']; ?>'">
                    <div class="feature-icon"><?php echo $feature['icon']; ?></div>
                    <h3 class="feature-title"><?php echo $feature['title']; ?></h3>
                    <p class="feature-description"><?php echo $feature['description']; ?></p>
                    <a href="<?php echo $feature['link']; ?>" class="btn">Open Tool</a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Capabilities Section -->
            <div class="info-section">
                <h3 class="info-title"><?php echo $config['title']; ?> Capabilities</h3>
                <ul class="info-list">
                    <?php foreach ($config['capabilities'] as $capability): ?>
                    <li><?php echo $capability; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive functionality
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animate stats on load
        function animateStats() {
            document.querySelectorAll('.stat-number').forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current);
                }, 30);
            });
        }

        // Run animation on page load
        window.addEventListener('load', animateStats);

        // Add animation on scroll
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

        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>