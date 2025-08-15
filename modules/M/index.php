<?php
// modules/M/index.php - Modeling Module Main Page
session_start();

// Check if config exists
if (!file_exists('../../config/config.php')) {
    header('Location: ../../install.php');
    exit;
}

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$conn = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Modeling - MACTA Framework</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
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
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
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
            color: #ff6b35;
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
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #ff6b35;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ff6b35;
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
            background: #ff6b35;
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
            background: #ff5722;
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
            color: #ff6b35;
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
        }
    </style>
</head>
<body>
    <a href="../../index.php" class="back-button">← Back to Framework</a>
    
    <div class="container">
        <div class="header">
            <div class="module-icon">📊</div>
            <h1 class="module-title">Process Modeling</h1>
            <p class="module-description">
                Process modeling and simulation based on real client data to optimize workflows and identify improvement opportunities.
            </p>
        </div>

        <div class="navigation">
            <div class="breadcrumb">
                <a href="../../index.php">MACTA Framework</a> > Process Modeling
            </div>
            <div>
                <a href="projects.php" class="btn btn-secondary">View Projects</a>
            </div>
        </div>

        <div class="main-content">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3 class="feature-title">Visual Process Builder</h3>
                    <p class="feature-description">
                        Create visual representations of business processes with drag-and-drop functionality.
                    </p>
                    <a href="visual_builder.php" class="btn">Start Building</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3 class="feature-title">Process Simulation</h3>
                    <p class="feature-description">
                        Simulate process changes before implementation to identify potential bottlenecks.
                    </p>
                    <a href="simulation.php" class="btn">Run Simulation</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3 class="feature-title">Process Documentation</h3>
                    <p class="feature-description">
                        Generate comprehensive documentation for all your modeled processes.
                    </p>
                    <a href="documentation.php" class="btn">View Documentation</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">Bottleneck Analysis</h3>
                    <p class="feature-description">
                        Identify inefficiencies and bottlenecks in your current processes.
                    </p>
                    <a href="bottleneck_analysis.php" class="btn">Analyze Now</a>
                </div>
            </div>

            <div class="info-section">
                <h3 class="info-title">Process Modeling Capabilities</h3>
                <ul class="info-list">
                    <li>Creates visual representations of business processes</li>
                    <li>Identifies bottlenecks and inefficiencies</li>
                    <li>Simulates process changes before implementation</li>
                    <li>Establishes baseline metrics for continuous improvement</li>
                    <li>Integrates with real client data for accurate modeling</li>
                    <li>Supports multiple process modeling standards (BPMN, Flowcharts)</li>
                    <li>Collaborative editing and review capabilities</li>
                    <li>Version control and change tracking</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive functionality
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('click', function() {
                const button = this.querySelector('.btn');
                if (button) {
                    button.click();
                }
            });
        });

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