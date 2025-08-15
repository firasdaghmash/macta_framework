<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - High Tech Talents</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .framework-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 1000px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .company-name {
            font-size: 28px;
            color: #333;
            font-weight: bold;
            margin: 0;
        }

        .framework-title {
            font-size: 36px;
            color: #333;
            margin: 10px 0;
            font-weight: bold;
        }

        .framework-subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
        }

        .macta-flow {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }

        .macta-module {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .macta-module:hover {
            transform: translateY(-10px);
        }

        .module-card {
            width: 140px;
            height: 140px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .module-card:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            transform: scale(1.05);
        }

        .module-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

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
        }

        .assessment {
            background: linear-gradient(135deg, #95e1d3 0%, #4caf50 100%);
        }

        .arrow {
            font-size: 24px;
            color: #666;
            margin: 0 10px;
        }

        .customer-satisfaction {
            background: linear-gradient(135deg, #b8b8b8 0%, #8a8a8a 100%);
            padding: 20px 40px;
            border-radius: 15px;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin: 30px auto;
            max-width: 300px;
        }

        .description {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .macta-flow {
                flex-direction: column;
            }
            
            .arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
            
            .module-card {
                width: 120px;
                height: 120px;
                font-size: 14px;
            }
            
            .framework-title {
                font-size: 28px;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="framework-container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">🔍</div>
                <h1 class="company-name">High Tech Talents</h1>
            </div>
            <h2 class="framework-title">MACTA Framework</h2>
            <p class="framework-subtitle">Modeling – Analysis – Customization – Training – Assessment (Metrics)</p>
        </div>

        <div class="description">
            HTT's Controlled Process Framework provides a comprehensive approach to strategic restructuring 
            through systematic process modeling, detailed analysis, customized solutions, targeted training, 
            and continuous assessment to ensure optimal customer satisfaction.
        </div>

        <div class="macta-flow">
            <a href="modules/M/index.php" class="macta-module" data-module="Modeling">
                <div class="module-card modeling">
                    <div class="module-icon">📊</div>
                    <div>Modeling</div>
                </div>
            </a>

            <div class="arrow">→</div>

            <a href="modules/A/index.php" class="macta-module" data-module="Analysis">
                <div class="module-card analysis">
                    <div class="module-icon">📈</div>
                    <div>Analysis</div>
                </div>
            </a>

            <div class="arrow">→</div>

            <a href="modules/C/index.php" class="macta-module" data-module="Customization">
                <div class="module-card customization">
                    <div class="module-icon">⚙️</div>
                    <div>Customization</div>
                </div>
            </a>

            <div class="arrow">→</div>

            <a href="modules/T/index.php" class="macta-module" data-module="Training">
                <div class="module-card training">
                    <div class="module-icon">🎓</div>
                    <div>Training</div>
                </div>
            </a>

            <div class="arrow">→</div>

            <a href="modules/A2/index.php" class="macta-module" data-module="Assessment">
                <div class="module-card assessment">
                    <div class="module-icon">📊</div>
                    <div>Assessment<br><small>(Metrics)</small></div>
                </div>
            </a>
        </div>

        <div class="customer-satisfaction">
            <div>🙂 Customer Satisfaction</div>
        </div>

        <div class="footer">
            <p>© 2025 High Tech Talents (HTT). MACTA Framework™ and related marks are trademarks of HTT.</p>
            <p style="margin-top: 10px;">
                <a href="admin/login.php" style="color: #666; text-decoration: none;">Admin Login</a> | 
                <a href="install.php" style="color: #666; text-decoration: none;">Setup</a>
            </p>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Add click handlers for modules
        document.querySelectorAll('.macta-module').forEach(module => {
            module.addEventListener('click', function(e) {
                const moduleName = this.getAttribute('data-module');
                
                // Check if the module directory exists (you might want to implement this check)
                // For now, we'll show a notification
                showNotification(`Navigating to ${moduleName} module...`);
                
                // Allow the default link behavior to continue
            });
        });

        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Add some interactive animations
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05) rotateY(5deg)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotateY(0deg)';
            });
        });
    </script>
</body>
</html>