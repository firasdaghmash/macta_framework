<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - High Tech Talents</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2C5AA0;
            --accent-orange: #FF7B2B;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 20px 40px rgba(0,0,0,0.15);
            --text-dark: #2c3e50;
            --text-light: #6c757d;
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Section */
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-orange));
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue), #4a90e2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: var(--card-shadow);
        }

        .company-info h1 {
            font-size: 28px;
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .company-tagline {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-outline {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            padding: 60px 0;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 80px;
        }

        .framework-title {
            font-size: 48px;
            color: white;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .framework-subtitle {
            font-size: 20px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
            font-weight: 500;
        }

        .framework-description {
            font-size: 18px;
            color: rgba(255,255,255,0.8);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* MACTA Flow */
        .macta-framework {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: var(--hover-shadow);
            margin-bottom: 40px;
            position: relative;
        }

        .macta-framework::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff9a56, #ff6b35, #4ecdc4, #ffe066, #95e1d3);
            border-radius: 24px 24px 0 0;
        }

        .framework-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .framework-header h2 {
            font-size: 32px;
            color: var(--text-dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .methodology-subtitle {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 500;
        }

        /* New Compact Flow Design */
        .macta-flow-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 50px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .macta-flow {
            display: flex;
            align-items: center;
            gap: 0;
            position: relative;
        }

        .module-card {
            background: white;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 14px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            width: 120px;
            height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 2;
        }

        .module-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: var(--hover-shadow);
            z-index: 10;
        }

        .module-icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }

        .module-title {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Module Colors */
        .modeling {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
        }

        .analysis {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }

        .customization {
            background: linear-gradient(135deg, #4ecdc4 0%, #26a69a 100%);
        }

        .training {
            background: linear-gradient(135deg, #ffe066 0%, #ffc107 100%);
            color: var(--text-dark) !important;
        }

        .assessment {
            background: linear-gradient(135deg, #95e1d3 0%, #4caf50 100%);
        }

        /* Connecting Lines */
        .connection-line {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #ccc, #999);
            position: relative;
            z-index: 1;
        }

        .connection-line::after {
            content: '';
            position: absolute;
            right: -5px;
            top: -3px;
            width: 0;
            height: 0;
            border-left: 8px solid #999;
            border-top: 4px solid transparent;
            border-bottom: 4px solid transparent;
        }

        /* Status indicator */
        .module-status {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #4caf50;
            border: 1px solid white;
            border-radius: 50%;
            z-index: 3;
        }

        .module-status.coming-soon {
            background: #ffc107;
        }

        /* Hover Description Box */
        .description-box {
            position: absolute;
            bottom: -80px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: var(--card-shadow);
            width: 280px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 20;
            border: 2px solid #f0f0f0;
        }

        .description-box::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid white;
        }

        .module-card:hover .description-box {
            opacity: 1;
            visibility: visible;
            bottom: -90px;
        }

        .description-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .description-text {
            font-size: 12px;
            color: var(--text-light);
            line-height: 1.4;
        }

        /* Customer Satisfaction */
        .customer-satisfaction {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 60px;
            position: relative;
        }

        .customer-satisfaction::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #ff9a56, #ff6b35, #4ecdc4, #ffe066, #95e1d3);
            border-radius: 2px;
        }

        .satisfaction-icon {
            font-size: 24px;
        }

        /* Footer */
        .footer {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px 0;
            text-align: center;
            margin-top: 60px;
        }

        .footer-content {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .framework-title {
                font-size: 36px;
            }
            
            .framework-subtitle {
                font-size: 18px;
            }
            
            .macta-flow {
                flex-direction: column;
                gap: 20px;
            }
            
            .connection-line {
                width: 3px;
                height: 40px;
                transform: rotate(90deg);
            }
            
            .connection-line::after {
                right: -3px;
                top: 35px;
                transform: rotate(90deg);
            }
            
            .module-card {
                width: 140px;
                height: 120px;
            }
            
            .description-box {
                width: 250px;
                bottom: -70px;
            }
            
            .module-card:hover .description-box {
                bottom: -80px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Animation for page load */
        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="company-info">
                        <h1>High Tech Talents</h1>
                        <div class="company-tagline">Managed Services Excellence</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="admin/login.php" class="btn btn-outline">
                        <i class="fas fa-user-shield"></i>
                        Admin Login
                    </a>
                    <a href="install.php" class="btn btn-outline">
                        <i class="fas fa-cog"></i>
                        Setup
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="hero-section fade-in">
                <h1 class="framework-title">MACTA Framework</h1>
                <p class="framework-subtitle">Modeling — Analysis — Customization — Training — Assessment (Metrics)</p>
                <p class="framework-description">
                    HTT's comprehensive process management framework provides a systematic approach to strategic 
                    restructuring through visual process modeling, data-driven analysis, customized solutions, 
                    targeted training programs, and continuous performance assessment to ensure optimal customer satisfaction.
                </p>
            </section>

            <section class="macta-framework fade-in">
                <div class="framework-header">
                    <h2>Strategic Process Optimization</h2>
                    <p class="methodology-subtitle">A proven methodology for business process excellence</p>
                </div>

                <div class="macta-flow-container">
                    <div class="macta-flow">
                        <a href="modules/M/enhanced_macta_modeling.php" class="module-card modeling">
                            <div class="module-status"></div>
                            <i class="module-icon fas fa-project-diagram"></i>
                            <div class="module-title">Modeling</div>
                            <div class="description-box">
                                <div class="description-title">Process Modeling</div>
                                <div class="description-text">Visual process representation & simulation based on real client data to optimize workflows and identify improvement opportunities.</div>
                            </div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="modules/A/process_viewer_page.php" class="module-card analysis">
                            <div class="module-status"></div>
                            <i class="module-icon fas fa-chart-line"></i>
                            <div class="module-title">Analysis</div>
                            <div class="description-box">
                                <div class="description-title">Statistical Analysis</div>
                                <div class="description-text">Data-driven insights and trend analysis with custom dashboard creation and automated recommendations.</div>
                            </div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card customization" onclick="showComingSoon('Customization')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-cogs"></i>
                            <div class="module-title">Customization</div>
                            <div class="description-box">
                                <div class="description-title">Custom Solutions</div>
                                <div class="description-text">Tailored job descriptions, client portal management, and role-based access control with customizable metrics.</div>
                            </div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card training" onclick="showComingSoon('Training')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-graduation-cap"></i>
                            <div class="module-title">Training</div>
                            <div class="description-box">
                                <div class="description-title">Training Programs</div>
                                <div class="description-text">Real-world scenario-based training with interactive learning modules, progress tracking, and certification management.</div>
                            </div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card assessment" onclick="showComingSoon('Assessment')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-chart-bar"></i>
                            <div class="module-title">Assessment</div>
                            <div class="description-box">
                                <div class="description-title">Performance Metrics</div>
                                <div class="description-text">Real-time performance dashboards, KPI tracking, automated reporting, and continuous improvement insights.</div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="customer-satisfaction">
                    <i class="satisfaction-icon fas fa-smile"></i>
                    <span>Customer Satisfaction Excellence</span>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>© 2025 High Tech Talents (HTT). MACTA Framework™ and related marks are trademarks of HTT.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Support</a>
                    <a href="#">Documentation</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Show coming soon notification
        function showComingSoon(moduleName) {
            event.preventDefault();
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ffc107, #ff9800);
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-clock"></i>
                ${moduleName} module is coming soon!
            `;
            
            document.body.appendChild(notification);
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                    if (document.head.contains(style)) {
                        document.head.removeChild(style);
                    }
                }, 300);
            }, 3000);
        }

        // Add loading state for active modules
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (this.href && this.href !== '#' && !this.href.includes('javascript:')) {
                    const moduleName = this.querySelector('.module-title').textContent;
                    console.log(`Navigating to ${moduleName} module`);
                    
                    // Add subtle loading effect
                    this.style.opacity = '0.8';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 200);
                }
            });
        });

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            fadeElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>