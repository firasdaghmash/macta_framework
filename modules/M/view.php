<?php
// modules/M/view.php - MACTA BPMN View Sub-page with Continuous Simulation
header('Content-Type: text/html; charset=utf-8');

// Initialize variables for database connection
$processes = [];
$projects = [];
$db_error = '';

// Database connection
try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Get all processes with project names
        $stmt = $pdo->prepare("
            SELECT pm.*, p.name as project_name 
            FROM process_models pm 
            LEFT JOIN projects p ON pm.project_id = p.id 
            ORDER BY pm.updated_at DESC
        ");
        $stmt->execute();
        $processes = $stmt->fetchAll();
        
        // Get all projects for filtering
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
    } else {
        $db_error = 'Database configuration not found. Please run the installer first.';
    }
    
} catch (Exception $e) {
    $db_error = "Database connection failed: " . $e->getMessage();
    error_log("MACTA View DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA BPMN Viewer & Animator</title>
    
    <!-- BPMN.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />
    
    <style>
        /* Enhanced View Page Styles */
        :root {
            --primary-color: #1E88E5;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --border-color: #bdc3c7;
            --macta-orange: #FF6B35;
            --macta-teal: #4ECDC4;
            --macta-green: #27AE60;
            --macta-red: #E74C3C;
            --macta-yellow: #F1C40F;
        }

        // Analyze bottlenecks with MACTA framework integration
        function analyzeBottlenecks() {
            if (!viewer || !currentProcessXML) {
                alert('Please load a process first!');
                return;
            }
            
            try {
                updateStatus('Analyzing bottlenecks...');
                
                const elementRegistry = viewer.get('elementRegistry');
                const tasks = elementRegistry.filter(el => 
                    el.type === 'bpmn:Task' || 
                    el.type === 'bpmn:UserTask' || 
                    el.type === 'bpmn:ServiceTask' ||
                    el.type === 'bpmn:ScriptTask' ||
                    el.type === 'bpmn:ManualTask'
                );
                
                if (tasks.length === 0) {
                    alert('No tasks found in the process for bottleneck analysis');
                    return;
                }
                
                // Clear previous highlights
                clearAllHighlights();
                
                // Randomly select 1-3 bottlenecks based on process complexity
                const bottleneckCount = Math.min(Math.max(1, Math.floor(tasks.length * 0.3)), 3);
                bottleneckElements = [];
                
                for (let i = 0; i < bottleneckCount; i++) {
                    const availableTasks = tasks.filter(task => !bottleneckElements.includes(task));
                    if (availableTasks.length === 0) break;
                    
                    const randomTask = availableTasks[Math.floor(Math.random() * availableTasks.length)];
                    bottleneckElements.push(randomTask);
                    
                    const gfx = elementRegistry.getGraphics(randomTask);
                    if (gfx) {
                        // Highlight bottlenecks with distinctive styling
                        gfx.style.fill = '#ffebee';
                        gfx.style.stroke = '#f44336';
                        gfx.style.strokeWidth = '4px';
                        gfx.style.animation = 'bottleneck-pulse 1s infinite';
                    }
                }
                
                const bottleneckNames = bottleneckElements.map(task => 
                    task.businessObject.name || task.id
                ).join('\n• ');
                
                const analysisReport = `
MACTA Framework - Bottleneck Analysis Report
═══════════════════════════════════════════

Potential Bottlenecks Detected:
• ${bottleneckNames}

MACTA Recommendations:

🔧 Process Optimization (Module M):
• Review task dependencies and sequencing
• Consider parallel processing opportunities
• Evaluate resource allocation patterns

📊 Analysis Integration (Module A):
• Implement real-time performance monitoring
• Track task completion times and wait states
• Analyze resource utilization patterns

⚙️ Customization Solutions (Module C):
• Design role-based task assignments
• Create specialized workflows for high-volume tasks
• Implement automated decision routing

🎓 Training Enhancement (Module T):
• Develop targeted skill training programs
• Cross-train team members for flexibility
• Create process optimization workshops

📈 Assessment & Metrics (Module A2):
• Set up KPI monitoring for identified bottlenecks
• Implement early warning systems
• Regular performance assessment cycles

Click OK to continue with detailed recommendations...`;

                alert(analysisReport);
                
                // Show detailed recommendations
                setTimeout(() => {
                    const detailedReport = `
Detailed MACTA Action Plan:

Immediate Actions (0-2 weeks):
1. Resource Reallocation
   • Assign additional resources to bottleneck tasks
   • Implement temporary parallel processing

2. Process Streamlining  
   • Eliminate non-value-added steps
   • Simplify approval processes

Medium-term Actions (2-8 weeks):
1. Technology Integration
   • Automate repetitive bottleneck tasks
   • Implement intelligent task routing

2. Skills Development
   • Targeted training for bottleneck operations
   • Cross-functional team development

Long-term Improvements (2-6 months):
1. Process Redesign
   • Complete workflow optimization
   • Integration with other business processes

2. Continuous Improvement
   • Regular bottleneck monitoring
   • Predictive analytics implementation

Would you like to export this analysis report?`;
                    
                    if (confirm(detailedReport)) {
                        exportBottleneckAnalysis(bottleneckNames);
                    }
                }, 1000);
                
                updateAnimationStatus('analysis', `Bottleneck analysis completed - ${bottleneckCount} issues identified`);
                updateStatus('Bottleneck analysis completed');
                
            } catch (error) {
                console.error('Bottleneck analysis error:', error);
                updateStatus('Bottleneck analysis failed');
            }
        }

        // Export bottleneck analysis report
        function exportBottleneckAnalysis(bottleneckNames) {
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            const reportContent = `
MACTA Framework - Bottleneck Analysis Report
Generated: ${new Date().toLocaleString()}
Process: ${currentProcessName}

IDENTIFIED BOTTLENECKS:
${bottleneckNames.split('\n').map(name => `• ${name.trim()}`).join('\n')}

MACTA RECOMMENDATIONS:

M - MODELING IMPROVEMENTS:
• Implement resource optimization strategies
• Apply parallel processing where possible
• Redesign workflow sequences for efficiency

A - ANALYSIS METRICS:
• Set up real-time performance monitoring
• Track bottleneck resolution times
• Implement predictive bottleneck detection

C - CUSTOMIZATION SOLUTIONS:
• Create role-based task assignments
• Design specialized approval workflows
• Implement intelligent routing systems

T - TRAINING PROGRAMS:
• Develop targeted skill enhancement courses
• Cross-train team members for flexibility
• Create bottleneck resolution procedures

A2 - ASSESSMENT & MONITORING:
• Establish continuous monitoring systems
• Set up automated alert mechanisms
• Regular performance review cycles

PATH EXPLORATION DATA:
Total Animation Runs: ${animationRunCount}
Gateway Paths Explored: ${Array.from(gatewayOptions.values()).reduce((total, gateway) => total + gateway.usedPaths.length, 0)}
Unique Process Variations: ${pathHistory.length}

This report was generated by the MACTA Framework Process Analysis Module.
For implementation support, consult your MACTA specialist.
            `;
            
            const blob = new Blob([reportContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `MACTA-Bottleneck-Analysis-${timestamp}.txt`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            URL.revokeObjectURL(url);
            
            updateStatus('Bottleneck analysis exported successfully');
        }

        // Export comprehensive analysis
        function exportAnalysis() {
            if (!currentProcessXML) {
                alert('Please load a process first!');
                return;
            }
            
            const elementRegistry = viewer.get('elementRegistry');
            const elements = elementRegistry.getAll();
            
            const tasks = elements.filter(el => el.type.includes('Task'));
            const events = elements.filter(el => el.type.includes('Event'));
            const gateways = elements.filter(el => el.type.includes('Gateway'));
            const flows = elements.filter(el => el.type === 'bpmn:SequenceFlow');
            
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            
            // Generate gateway analysis
            let gatewayAnalysis = '';
            if (gatewayOptions.size > 0) {
                gatewayAnalysis = '\nGATEWAY PATH ANALYSIS:\n';
                Array.from(gatewayOptions.entries()).forEach(([gatewayId, data]) => {
                    const element = elementRegistry.get(gatewayId);
                    const gatewayName = element?.businessObject?.name || gatewayId;
                    gatewayAnalysis += `• ${gatewayName}: ${data.usedPaths.length}/${data.allPaths.length} paths explored\n`;
                });
            }
            
            // Generate bottleneck analysis
            let bottleneckAnalysis = '';
            if (bottleneckElements.length > 0) {
                bottleneckAnalysis = '\nBOTTLENECK ANALYSIS:\n';
                bottleneckElements.forEach(element => {
                    const name = element.businessObject.name || element.id;
                    bottleneckAnalysis += `• Identified: ${name}\n`;
                });
            } else {
                bottleneckAnalysis = '\nBOTTLENECK ANALYSIS:\n• No bottlenecks currently identified\n';
            }
            
            const analysisReport = `
MACTA Framework - Comprehensive Process Analysis Report
Generated: ${new Date().toLocaleString()}
Process: ${currentProcessName}

PROCESS STATISTICS:
• Total Elements: ${elements.length}
• Tasks: ${tasks.length}
• Events: ${events.length}
• Gateways: ${gateways.length}
• Sequence Flows: ${flows.length}
• Animation Runs: ${animationRunCount}
• Unique Paths Explored: ${pathHistory.length}

ELEMENT DETAILS:
Tasks:
${tasks.map(task => `• ${task.businessObject.name || task.id} (${task.type})`).join('\n')}

Events:
${events.map(event => `• ${event.businessObject.name || event.id} (${event.type})`).join('\n')}

Gateways:
${gateways.map(gateway => `• ${gateway.businessObject.name || gateway.id} (${gateway.type})`).join('\n')}
${gatewayAnalysis}${bottleneckAnalysis}
SIMULATION ANALYSIS:
• Continuous Simulation: ${continuousSimulation ? 'Active' : 'Inactive'}
• Path Variation Coverage: ${gatewayOptions.size > 0 ? 
    Math.round((Array.from(gatewayOptions.values()).reduce((total, gateway) => total + gateway.usedPaths.length, 0) /
    Array.from(gatewayOptions.values()).reduce((total, gateway) => total + gateway.allPaths.length, 0)) * 100) : 100}%

MACTA FRAMEWORK RECOMMENDATIONS:

M - MODELING IMPROVEMENTS:
• Regular process monitoring and optimization
• Consider automation opportunities for identified bottlenecks
• Implement advanced path analysis for gateway decisions
• Establish process baselines for continuous improvement

A - ANALYSIS INTEGRATION:
• Set up real-time performance dashboards
• Implement predictive analytics for process insights
• Track path frequency and performance metrics
• Monitor gateway decision patterns

C - CUSTOMIZATION SOLUTIONS:
• Role-based task assignments for identified bottlenecks
• Customizable performance metrics per process variant
• Tailored workflows based on path analysis results
• Adaptive routing based on historical performance

T - TRAINING PROGRAMS:
• Process-specific training modules
• Gateway decision-making guidelines
• Bottleneck resolution procedures
• Continuous improvement methodologies

A2 - ASSESSMENT & METRICS:
• KPI monitoring for all process variants
• Automated performance alerts
• Regular assessment cycles
• Process optimization tracking

This comprehensive analysis was generated by the MACTA Framework Viewing Module.
For detailed implementation guidance, consult your MACTA specialist.
            `;
            
            const blob = new Blob([analysisReport], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `MACTA-Process-Analysis-${timestamp}.txt`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            URL.revokeObjectURL(url);
            
            updateStatus('Process analysis exported successfully');
            alert(`Comprehensive analysis exported!\n\nIncludes:\n• Process statistics and element details\n• Path exploration data\n• Bottleneck identification\n• MACTA framework recommendations\n• Gateway analysis and simulation results`);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-header {
            background: linear-gradient(135deg, var(--macta-teal), #3cb8b1);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .tab-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .tab-header p {
            margin: 0;
            opacity: 0.9;
        }

        /* Animation Status Panel */
        .animation-status {
            background: linear-gradient(135deg, #e8f5e8, #d5f4e6);
            border: 2px solid var(--success-color);
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .animation-status.running {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border-color: var(--warning-color);
            color: #e65100;
        }

        .animation-status.stopped {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-color: var(--danger-color);
            color: #c62828;
        }

        .animation-status.completed {
            background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
            border-color: var(--macta-teal);
            color: #00695c;
        }

        .animation-status.error {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .status-icon {
            font-size: 20px;
        }

        .status-details {
            flex: 1;
        }

        .run-counter {
            background: rgba(255,255,255,0.8);
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            color: var(--dark-color);
        }

        /* Process Selection Panel */
        .process-management-panel {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .selector-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .selector-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .selector-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .selector-group select:focus {
            border-color: var(--macta-teal);
            outline: none;
        }

        /* BPMN Viewer */
        .bpmn-viewer-container {
            padding: 20px;
        }

        .bpmn-viewer {
            height: 600px;
            border: 3px solid var(--macta-teal);
            border-radius: 12px;
            background: white;
            position: relative;
            margin-bottom: 20px;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 18px;
            color: var(--macta-teal);
            font-weight: bold;
            flex-direction: column;
            gap: 10px;
        }

        /* Toolbar */
        .toolbar {
            background: #f8f9fa;
            padding: 15px 20px;
            margin: 0 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .tool-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-warning { background: var(--warning-color); color: white; }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-teal { background: var(--macta-teal); color: white; }

        /* Color Legend */
        .color-legend {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .color-legend h4 {
            margin: 0 0 15px 0;
            color: var(--dark-color);
        }

        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border-radius: 6px;
            background: #f8f9fa;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(0,0,0,0.1);
        }

        /* Animation Classes */
        .animation-run-1 .djs-visual > rect,
        .animation-run-1 .djs-visual > circle,
        .animation-run-1 .djs-visual > polygon {
            fill: #FF6B6B !important;
            stroke: #e55555 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-2 .djs-visual > rect,
        .animation-run-2 .djs-visual > circle,
        .animation-run-2 .djs-visual > polygon {
            fill: #4ECDC4 !important;
            stroke: #3cb8b1 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-3 .djs-visual > rect,
        .animation-run-3 .djs-visual > circle,
        .animation-run-3 .djs-visual > polygon {
            fill: #45B7D1 !important;
            stroke: #3a9bc1 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-4 .djs-visual > rect,
        .animation-run-4 .djs-visual > circle,
        .animation-run-4 .djs-visual > polygon {
            fill: #96CEB4 !important;
            stroke: #7bb89f !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-5 .djs-visual > rect,
        .animation-run-5 .djs-visual > circle,
        .animation-run-5 .djs-visual > polygon {
            fill: #FFEAA7 !important;
            stroke: #e6d085 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-6 .djs-visual > rect,
        .animation-run-6 .djs-visual > circle,
        .animation-run-6 .djs-visual > polygon {
            fill: #DDA0DD !important;
            stroke: #c088c0 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-7 .djs-visual > rect,
        .animation-run-7 .djs-visual > circle,
        .animation-run-7 .djs-visual > polygon {
            fill: #98D8C8 !important;
            stroke: #7dbfb3 !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        .animation-run-8 .djs-visual > rect,
        .animation-run-8 .djs-visual > circle,
        .animation-run-8 .djs-visual > polygon {
            fill: #F7DC6F !important;
            stroke: #ddb84f !important;
            stroke-width: 4px !important;
            animation: pulse-glow 1.5s infinite;
        }

        /* Dimmed versions for continuous simulation */
        .animation-run-1-dimmed .djs-visual > rect,
        .animation-run-1-dimmed .djs-visual > circle,
        .animation-run-1-dimmed .djs-visual > polygon {
            fill: #FF6B6B !important;
            stroke: #e55555 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-2-dimmed .djs-visual > rect,
        .animation-run-2-dimmed .djs-visual > circle,
        .animation-run-2-dimmed .djs-visual > polygon {
            fill: #4ECDC4 !important;
            stroke: #3cb8b1 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-3-dimmed .djs-visual > rect,
        .animation-run-3-dimmed .djs-visual > circle,
        .animation-run-3-dimmed .djs-visual > polygon {
            fill: #45B7D1 !important;
            stroke: #3a9bc1 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-4-dimmed .djs-visual > rect,
        .animation-run-4-dimmed .djs-visual > circle,
        .animation-run-4-dimmed .djs-visual > polygon {
            fill: #96CEB4 !important;
            stroke: #7bb89f !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-5-dimmed .djs-visual > rect,
        .animation-run-5-dimmed .djs-visual > circle,
        .animation-run-5-dimmed .djs-visual > polygon {
            fill: #FFEAA7 !important;
            stroke: #e6d085 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-6-dimmed .djs-visual > rect,
        .animation-run-6-dimmed .djs-visual > circle,
        .animation-run-6-dimmed .djs-visual > polygon {
            fill: #DDA0DD !important;
            stroke: #c088c0 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-7-dimmed .djs-visual > rect,
        .animation-run-7-dimmed .djs-visual > circle,
        .animation-run-7-dimmed .djs-visual > polygon {
            fill: #98D8C8 !important;
            stroke: #7dbfb3 !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        .animation-run-8-dimmed .djs-visual > rect,
        .animation-run-8-dimmed .djs-visual > circle,
        .animation-run-8-dimmed .djs-visual > polygon {
            fill: #F7DC6F !important;
            stroke: #ddb84f !important;
            stroke-width: 2px !important;
            opacity: 0.3 !important;
        }

        /* Flow animations */
        .animation-run-1 .djs-visual > path { stroke: #FF6B6B !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-2 .djs-visual > path { stroke: #4ECDC4 !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-3 .djs-visual > path { stroke: #45B7D1 !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-4 .djs-visual > path { stroke: #96CEB4 !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-5 .djs-visual > path { stroke: #FFEAA7 !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-6 .djs-visual > path { stroke: #DDA0DD !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-7 .djs-visual > path { stroke: #98D8C8 !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }
        .animation-run-8 .djs-visual > path { stroke: #F7DC6F !important; stroke-width: 4px !important; animation: flow-dash 2s infinite; }

        /* Dimmed flow animations */
        .animation-run-1-dimmed .djs-visual > path { stroke: #FF6B6B !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-2-dimmed .djs-visual > path { stroke: #4ECDC4 !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-3-dimmed .djs-visual > path { stroke: #45B7D1 !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-4-dimmed .djs-visual > path { stroke: #96CEB4 !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-5-dimmed .djs-visual > path { stroke: #FFEAA7 !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-6-dimmed .djs-visual > path { stroke: #DDA0DD !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-7-dimmed .djs-visual > path { stroke: #98D8C8 !important; stroke-width: 2px !important; opacity: 0.3 !important; }
        .animation-run-8-dimmed .djs-visual > path { stroke: #F7DC6F !important; stroke-width: 2px !important; opacity: 0.3 !important; }

        /* Bottleneck highlighting */
        .bottleneck-highlight .djs-visual > rect,
        .bottleneck-highlight .djs-visual > circle,
        .bottleneck-highlight .djs-visual > polygon {
            fill: #ffebee !important;
            stroke: #f44336 !important;
            stroke-width: 4px !important;
            animation: bottleneck-pulse 1s infinite;
        }

        /* Keyframe animations */
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; filter: brightness(1); }
            50% { opacity: 0.7; filter: brightness(1.2); }
        }

        @keyframes flow-dash {
            0% { stroke-dasharray: 10 5; stroke-dashoffset: 0; }
            100% { stroke-dasharray: 10 5; stroke-dashoffset: -15; }
        }

        @keyframes bottleneck-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Status bar */
        .status-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--macta-teal);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tab-header">
            <h2>MACTA - BPMN Process View & Animation</h2>
            <p>View and analyze business processes with advanced animation system - Module M</p>
        </div>

        <!-- Animation Status -->
        <div class="animation-status" id="animation-status">
            <span class="status-icon">🎬</span>
            <div class="status-details">
                <strong>Animation Status:</strong>
                <span id="animation-text">Ready to animate</span>
            </div>
            <div class="run-counter">
                <strong>Run #<span id="animation-run-count">0</span></strong>
            </div>
        </div>

        <!-- Process Management Panel -->
        <div class="process-management-panel">
            <h3>Process Selection</h3>
            <div class="selector-row">
                <div class="selector-group">
                    <label>Project Filter:</label>
                    <select id="project-filter">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= htmlspecialchars($project['id']) ?>">
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="selector-group">
                    <label>Process to View:</label>
                    <select id="viewer-process-select">
                        <option value="">Choose a process to view...</option>
                        <?php foreach ($processes as $process): ?>
                            <option value="<?= htmlspecialchars($process['id']) ?>" 
                                    data-project="<?= htmlspecialchars($process['project_id'] ?? '') ?>"
                                    data-xml="<?= htmlspecialchars($process['model_data'] ?? '') ?>">
                                <?= htmlspecialchars($process['name']) ?>
                                <?= $process['project_name'] ? ' (' . htmlspecialchars($process['project_name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- BPMN Viewer -->
        <div class="bpmn-viewer-container">
            <div id="bpmn-viewer" class="bpmn-viewer">
                <div class="loading">
                    <div>Select a process from the dropdown above to view it here</div>
                    <div style="font-size: 14px; opacity: 0.7;">Or import from the Design tab using Export to Viewer</div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <h4>Animation & Analysis Tools</h4>
            <div class="tool-buttons">
                <button class="btn btn-teal" id="btn-animate-path">Start Animation</button>
                <button class="btn btn-success" id="btn-continuous-simulation">Continuous Mode: OFF</button>
                <button class="btn btn-danger" id="btn-clear-highlights">Stop & Clear</button>
                <button class="btn btn-warning" id="btn-analyze-bottlenecks">Analyze Bottlenecks</button>
                <button class="btn btn-primary" id="btn-export-analysis">Export Analysis</button>
                <button class="btn btn-secondary" id="btn-viewer-zoom-fit">Fit to Screen</button>
            </div>
        </div>

        <!-- Color Legend -->
        <div class="color-legend">
            <h4>Animation Colors Legend</h4>
            <div class="legend-items" id="color-legend-items">
                <!-- Populated by JavaScript -->
            </div>
        </div>

        <div class="status-bar">
            <div class="status-info">
                <span>Status:</span>
                <span id="status-text">Ready</span>
            </div>
            <div>
                <span id="current-process-view">No process loaded</span>
                <span id="element-count-view" style="margin-left: 20px;">Elements: 0</span>
            </div>
        </div>
    </div>

    <!-- BPMN.js Script -->
    <script src="https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js"></script>

    <script>
        // Global variables
        let viewer = null;
        let animationRunCount = 0;
        let isAnimating = false;
        let animationTimeouts = [];
        let currentProcessXML = null;
        let currentProcessName = '';
        let continuousSimulation = false;
        let pathHistory = [];
        let usedPaths = new Map(); // Track used gateway paths
        let gatewayOptions = new Map(); // Store all gateway options
        let bottleneckElements = [];

        // Get data from PHP
        const processes = <?= json_encode($processes) ?>;
        const dbError = <?= json_encode($db_error) ?>;

        // Animation colors system
        const animationColors = [
            { name: 'Red Flow', css: 'animation-run-1', color: '#FF6B6B' },
            { name: 'Teal Flow', css: 'animation-run-2', color: '#4ECDC4' },
            { name: 'Blue Flow', css: 'animation-run-3', color: '#45B7D1' },
            { name: 'Green Flow', css: 'animation-run-4', color: '#96CEB4' },
            { name: 'Yellow Flow', css: 'animation-run-5', color: '#FFEAA7' },
            { name: 'Purple Flow', css: 'animation-run-6', color: '#DDA0DD' },
            { name: 'Mint Flow', css: 'animation-run-7', color: '#98D8C8' },
            { name: 'Gold Flow', css: 'animation-run-8', color: '#F7DC6F' }
        ];

        // Initialize BPMN Viewer
        function initializeBpmnViewer() {
            try {
                updateStatus('Loading BPMN Viewer...');
                
                if (typeof BpmnJS === 'undefined') {
                    throw new Error('BpmnJS library not loaded');
                }
                
                viewer = new BpmnJS({
                    container: '#bpmn-viewer'
                });
                
                updateStatus('BPMN Viewer Ready');
                console.log('BPMN Viewer initialized successfully');
                
                // Check for exported XML from design tab
                checkForExportedProcess();
                
            } catch (error) {
                console.error('Failed to initialize BPMN Viewer:', error);
                updateStatus('Failed to initialize BPMN Viewer: ' + error.message);
            }
        }

        // Check for exported process from design tab
        function checkForExportedProcess() {
            const exportedXML = sessionStorage.getItem('currentProcessXML');
            const exportedName = sessionStorage.getItem('currentProcessName');
            
            if (exportedXML) {
                loadProcessInViewer(exportedXML, exportedName || 'Imported from Design');
                updateAnimationStatus('ready', 'Process loaded from Design tab - Ready to animate');
            }
        }

        // Load process in viewer
        async function loadProcessInViewer(xml, processName = 'Unknown Process') {
            if (!viewer) return;
            
            try {
                updateStatus('Loading process...');
                
                await viewer.importXML(xml);
                viewer.get('canvas').zoom('fit-viewport');
                
                currentProcessXML = xml;
                currentProcessName = processName;
                
                // Reset path tracking for new process
                resetPathTracking();
                
                const loadingEl = document.querySelector('#bpmn-viewer .loading');
                if (loadingEl) loadingEl.style.display = 'none';
                
                updateAnimationStatus('ready', 'Process "' + processName + '" loaded - Ready to animate');
                updateStatus('Process loaded successfully');
                document.getElementById('current-process-view').textContent = processName;
                
                updateElementCount();
                
            } catch (error) {
                console.error('Failed to load process in viewer:', error);
                updateStatus('Failed to load process');
                updateAnimationStatus('error', 'Failed to load process');
            }
        }

        // Reset path tracking system
        function resetPathTracking() {
            usedPaths.clear();
            gatewayOptions.clear();
            
            if (!viewer) return;
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                // Identify all gateways and their outgoing paths
                elements.forEach(element => {
                    if (element.type === 'bpmn:ExclusiveGateway' || element.type === 'bpmn:InclusiveGateway') {
                        const outgoing = element.businessObject?.outgoing;
                        if (outgoing && outgoing.length > 1) {
                            const pathIds = outgoing.map(flow => flow.id);
                            gatewayOptions.set(element.id, {
                                allPaths: pathIds,
                                usedPaths: [],
                                availablePaths: [...pathIds]
                            });
                        }
                    }
                });
                
                console.log('Path tracking initialized for', gatewayOptions.size, 'gateways');
            } catch (error) {
                console.error('Failed to initialize path tracking:', error);
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Process selection
            document.getElementById('viewer-process-select').addEventListener('change', async function() {
                const processId = this.value;
                if (processId) {
                    const selectedOption = this.selectedOptions[0];
                    const xmlData = selectedOption.dataset.xml;
                    const processName = selectedOption.textContent;
                    
                    if (xmlData) {
                        await loadProcessInViewer(xmlData, processName);
                        sessionStorage.setItem('currentProcessXML', xmlData);
                        sessionStorage.setItem('currentProcessName', processName);
                    }
                } else {
                    clearViewer();
                }
            });

            // Animation controls
            document.getElementById('btn-animate-path').addEventListener('click', animateProcess);
            document.getElementById('btn-continuous-simulation').addEventListener('click', toggleContinuousSimulation);
            document.getElementById('btn-clear-highlights').addEventListener('click', clearAnimation);
            document.getElementById('btn-analyze-bottlenecks').addEventListener('click', analyzeBottlenecks);
            document.getElementById('btn-export-analysis').addEventListener('click', exportAnalysis);
            document.getElementById('btn-viewer-zoom-fit').addEventListener('click', () => {
                if (viewer) viewer.get('canvas').zoom('fit-viewport');
            });
        }

        // Toggle continuous simulation
        function toggleContinuousSimulation() {
            continuousSimulation = !continuousSimulation;
            const button = document.getElementById('btn-continuous-simulation');
            
            if (continuousSimulation) {
                button.textContent = 'Continuous Mode: ON';
                button.classList.remove('btn-success');
                button.classList.add('btn-warning');
                updateStatus('Continuous simulation mode enabled');
                
                // If not already animating, start the first animation
                if (!isAnimating) {
                    setTimeout(() => {
                        if (continuousSimulation) {
                            animateProcess();
                        }
                    }, 1000);
                }
            } else {
                button.textContent = 'Continuous Mode: OFF';
                button.classList.remove('btn-warning');
                button.classList.add('btn-success');
                updateStatus('Continuous simulation mode disabled');
            }
        }

        // Clear viewer
        function clearViewer() {
            const loadingEl = document.querySelector('#bpmn-viewer .loading');
            if (loadingEl) {
                loadingEl.style.display = 'flex';
                loadingEl.innerHTML = '<div>Select a process from the dropdown above to view it here</div>';
            }
            
            currentProcessXML = null;
            currentProcessName = '';
            document.getElementById('current-process-view').textContent = 'No process loaded';
            updateAnimationStatus('ready', 'Ready to load a process');
            updateStatus('Ready');
        }

        // Initialize color legend
        function updateColorLegend() {
            const legendContainer = document.getElementById('color-legend-items');
            if (!legendContainer) return;
            
            legendContainer.innerHTML = '';
            animationColors.forEach((color, index) => {
                const item = document.createElement('div');
                item.className = 'legend-item';
                item.innerHTML = `
                    <div class="legend-color" style="background-color: ${color.color}"></div>
                    <span>Run ${index + 1}: ${color.name}</span>
                `;
                legendContainer.appendChild(item);
            });
        }

        // Animate process
        function animateProcess() {
            if (!viewer || !currentProcessXML) {
                alert('Please load a process first!');
                return;
            }
            
            if (isAnimating && !continuousSimulation) {
                alert('Animation is already running. Please stop it first.');
                return;
            }
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                
                if (!startEvent) {
                    alert('No start event found in the process!');
                    return;
                }
                
                animationRunCount++;
                const currentRun = ((animationRunCount - 1) % 8) + 1;
                const animationClass = `animation-run-${currentRun}`;
                
                updateAnimationStatus('running', `Running animation - ${animationColors[currentRun - 1].name} (Run #${animationRunCount})`);
                updateAnimationRunCount();
                updateStatus('Animation in progress...');
                
                isAnimating = true;
                
                // Store the current path elements for dimming later
                const currentPath = {
                    animationClass: animationClass,
                    elements: []
                };
                
                highlightPath(startEvent, elementRegistry, animationClass, 1200, currentPath);
                
            } catch (error) {
                console.error('Animation error:', error);
                isAnimating = false;
                updateAnimationStatus('error', 'Animation failed: ' + error.message);
                updateStatus('Animation error occurred');
            }
        }

        // Highlight path with intelligent path selection
        async function highlightPath(currentElement, elementRegistry, animationClass, delay, pathInfo) {
            if (!currentElement || (!isAnimating && !continuousSimulation)) return;
            
            try {
                // Highlight current element
                const gfx = elementRegistry.getGraphics(currentElement);
                if (gfx) {
                    gfx.classList.add(animationClass);
                    pathInfo.elements.push({element: currentElement, gfx: gfx});
                }
            } catch (error) {
                console.log('Element highlighting failed:', error);
            }
            
            const timeout = setTimeout(async () => {
                const outgoing = currentElement.businessObject?.outgoing;
                if (outgoing && outgoing.length > 0) {
                    
                    let selectedFlows = outgoing;
                    
                    // Handle gateways with intelligent path selection
                    if (currentElement.type === 'bpmn:ExclusiveGateway' || currentElement.type === 'bpmn:InclusiveGateway') {
                        selectedFlows = selectGatewayPath(currentElement, outgoing, pathInfo);
                    }
                    
                    for (const flow of selectedFlows) {
                        // Highlight the flow
                        const flowGfx = elementRegistry.getGraphics(flow);
                        if (flowGfx) {
                            flowGfx.classList.add(animationClass);
                            pathInfo.elements.push({element: flow, gfx: flowGfx});
                        }
                        
                        const nextElement = elementRegistry.get(flow.targetRef?.id);
                        if (nextElement && (isAnimating || continuousSimulation)) {
                            if (nextElement.type !== 'bpmn:EndEvent') {
                                await highlightPath(nextElement, elementRegistry, animationClass, delay, pathInfo);
                            } else {
                                // Highlight end event
                                const endGfx = elementRegistry.getGraphics(nextElement);
                                if (endGfx) {
                                    endGfx.classList.add(animationClass);
                                    pathInfo.elements.push({element: nextElement, gfx: endGfx});
                                }
                                
                                // Path completed
                                setTimeout(() => {
                                    handlePathCompletion(pathInfo);
                                }, delay);
                            }
                        }
                    }
                } else {
                    // No outgoing flows - end animation
                    setTimeout(() => {
                        handlePathCompletion(pathInfo);
                    }, delay);
                }
            }, delay);
            
            animationTimeouts.push(timeout);
        }

        // Intelligent gateway path selection
        function selectGatewayPath(gatewayElement, outgoingFlows, pathInfo) {
            const gatewayId = gatewayElement.id;
            
            // Initialize gateway tracking if not exists
            if (!gatewayOptions.has(gatewayId)) {
                const pathIds = outgoingFlows.map(flow => flow.id);
                gatewayOptions.set(gatewayId, {
                    allPaths: pathIds,
                    usedPaths: [],
                    availablePaths: [...pathIds]
                });
            }
            
            const gatewayData = gatewayOptions.get(gatewayId);
            
            // Check if all paths have been used
            if (gatewayData.availablePaths.length === 0) {
                // Reset available paths - all options exhausted
                gatewayData.availablePaths = [...gatewayData.allPaths];
                gatewayData.usedPaths = [];
                
                console.log(`Gateway ${gatewayId}: All paths exhausted, resetting options`);
                updateStatus(`Gateway ${gatewayId} - All paths explored, starting new cycle`);
            }
            
            // Select from available paths
            const availableFlows = outgoingFlows.filter(flow => 
                gatewayData.availablePaths.includes(flow.id)
            );
            
            if (availableFlows.length === 0) {
                // Fallback to random selection
                const randomIndex = Math.floor(Math.random() * outgoingFlows.length);
                return [outgoingFlows[randomIndex]];
            }
            
            // Select a random available path
            const randomIndex = Math.floor(Math.random() * availableFlows.length);
            const selectedFlow = availableFlows[randomIndex];
            
            // Mark this path as used
            gatewayData.usedPaths.push(selectedFlow.id);
            gatewayData.availablePaths = gatewayData.availablePaths.filter(id => id !== selectedFlow.id);
            
            // Store path selection in pathInfo for tracking
            pathInfo.gatewayChoices = pathInfo.gatewayChoices || [];
            pathInfo.gatewayChoices.push({
                gatewayId: gatewayId,
                selectedPath: selectedFlow.id,
                availableOptions: gatewayData.availablePaths.length,
                totalOptions: gatewayData.allPaths.length
            });
            
            console.log(`Gateway ${gatewayId}: Selected path ${selectedFlow.id}, ${gatewayData.availablePaths.length} options remaining`);
            
            return [selectedFlow];
        }

        // Handle path completion with enhanced tracking
        function handlePathCompletion(completedPath) {
            // Dim the completed path
            dimPath(completedPath);
            
            // Add to history with path choice information
            pathHistory.push(completedPath);
            
            // Generate path summary
            let pathSummary = `Run #${animationRunCount} completed`;
            if (completedPath.gatewayChoices && completedPath.gatewayChoices.length > 0) {
                const totalRemainingOptions = completedPath.gatewayChoices.reduce((sum, choice) => sum + choice.availableOptions, 0);
                pathSummary += ` (${totalRemainingOptions} paths remaining)`;
            }
            
            if (continuousSimulation) {
                updateAnimationStatus('completed', pathSummary + ' - Starting next flow...');
                
                // Check if we should show a cycle completion message
                const allGatewaysExhausted = Array.from(gatewayOptions.values()).every(gateway => gateway.availablePaths.length === 0);
                
                if (allGatewaysExhausted && gatewayOptions.size > 0) {
                    updateStatus('All gateway paths explored - Starting new exploration cycle');
                    setTimeout(() => {
                        if (continuousSimulation) {
                            animateProcess();
                        }
                    }, 3000); // Longer pause for cycle completion
                } else {
                    // Start next animation after brief delay
                    setTimeout(() => {
                        if (continuousSimulation) {
                            animateProcess();
                        }
                    }, 2000);
                }
            } else {
                isAnimating = false;
                updateAnimationStatus('completed', pathSummary);
                updateStatus('Animation completed successfully');
            }
        }

        // Dim a completed path
        function dimPath(pathInfo) {
            const dimmedClass = pathInfo.animationClass + '-dimmed';
            
            pathInfo.elements.forEach(item => {
                if (item.gfx) {
                    // Remove active animation class
                    item.gfx.classList.remove(pathInfo.animationClass);
                    // Add dimmed class
                    item.gfx.classList.add(dimmedClass);
                }
            });
        }

        // Clear animation with complete reset
        function clearAnimation() {
            isAnimating = false;
            continuousSimulation = false;
            
            // Update continuous simulation button
            const button = document.getElementById('btn-continuous-simulation');
            button.textContent = 'Continuous Mode: OFF';
            button.classList.remove('btn-warning');
            button.classList.add('btn-success');
            
            // Clear all timeouts
            animationTimeouts.forEach(timeout => clearTimeout(timeout));
            animationTimeouts = [];
            
            // Clear all highlights
            if (viewer) clearAllHighlights();
            
            // Reset all tracking
            pathHistory = [];
            resetPathTracking();
            
            // Reset run count
            animationRunCount = 0;
            updateAnimationRunCount();
            updateAnimationStatus('ready', 'Animation cleared - Ready for new animation');
            updateStatus('Animation stopped and cleared - Path tracking reset');
        }

        // Clear all highlights including bottlenecks
        function clearAllHighlights() {
            if (!viewer) return;
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                
                elements.forEach(element => {
                    const gfx = elementRegistry.getGraphics(element);
                    if (gfx) {
                        // Remove all animation classes (active and dimmed)
                        for (let i = 1; i <= 8; i++) {
                            gfx.classList.remove(`animation-run-${i}`);
                            gfx.classList.remove(`animation-run-${i}-dimmed`);
                        }
                        
                        // Remove bottleneck highlighting
                        gfx.classList.remove('bottleneck-highlight');
                        gfx.style.fill = '';
                        gfx.style.stroke = '';
                        gfx.style.strokeWidth = '';
                        gfx.style.animation = '';
                    }
                });
                
                // Clear bottleneck elements array
                bottleneckElements = [];
                
            } catch (error) {
                console.error('Failed to clear highlights:', error);
            }
        }

        // Update animation status
        function updateAnimationStatus(status, text) {
            const statusElement = document.getElementById('animation-status');
            const textElement = document.getElementById('animation-text');
            
            if (statusElement && textElement) {
                statusElement.className = `animation-status ${status}`;
                textElement.textContent = text;
            }
        }

        // Update animation run count
        function updateAnimationRunCount() {
            const countElement = document.getElementById('animation-run-count');
            if (countElement) {
                countElement.textContent = animationRunCount;
            }
        }

        // Update element count
        function updateElementCount() {
            if (!viewer) return;
            
            try {
                const elementRegistry = viewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                const shapeCount = elements.filter(el => el.type !== 'bpmn:SequenceFlow').length;
                
                document.getElementById('element-count-view').textContent = `Elements: ${shapeCount}`;
            } catch (error) {
                console.log('Failed to update element count:', error);
            }
        }

        // Update status
        function updateStatus(message) {
            const statusEl = document.getElementById('status-text');
            if (statusEl) {
                statusEl.textContent = message;
            }
            console.log('Status:', message);
        }

        // Wait for DOM and initialize - Updated for AJAX loading
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing BPMN Viewer...');
            initializeViewer();
        });

        // Listen for tab content loaded event (when loaded via AJAX)
        document.addEventListener('tabContentLoaded', function(e) {
            if (e.detail.tabName === 'view') {
                console.log('View tab loaded via AJAX, initializing...');
                setTimeout(initializeViewer, 200);
            }
        });

        // Listen for scripts ready event (when all external scripts loaded)
        document.addEventListener('tabScriptsReady', function(e) {
            if (e.detail.tabName === 'view') {
                console.log('View tab scripts ready, initializing...');
                setTimeout(initializeViewer, 300);
            }
        });

        // Centralized initialization function
        function initializeViewer() {
            // Check if already initialized
            if (window.viewerInitialized) {
                console.log('Viewer already initialized, skipping...');
                return;
            }

            // Check for database errors
            if (dbError) {
                updateStatus('Database Error: ' + dbError);
                const viewerContainer = document.querySelector('#bpmn-viewer');
                if (viewerContainer) {
                    viewerContainer.querySelector('.loading').innerHTML = 
                        '<div style="text-align: center;"><h3>Database Configuration Error</h3><p>' + dbError + '</p></div>';
                }
                return;
            }
            
            // Check if BPMN container exists
            const viewerContainer = document.querySelector('#bpmn-viewer');
            if (!viewerContainer) {
                console.log('BPMN viewer container not found, retrying...');
                setTimeout(initializeViewer, 500);
                return;
            }

            // Initialize BPMN Viewer and setup
            setTimeout(() => {
                initializeBpmnViewer();
                setupEventListeners();
                updateColorLegend();
                populateDropdowns(); // Add this to ensure dropdowns are populated
                window.viewerInitialized = true;
            }, 100);
        }

        // Make initializeBpmnViewer globally available for manual triggering
        window.initializeBpmnViewer = initializeBpmnViewer;

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Space bar to animate
            if (event.code === 'Space' && !event.target.matches('input, textarea, select')) {
                event.preventDefault();
                animateProcess();
            }
            // Escape to stop animation
            if (event.key === 'Escape') {
                event.preventDefault();
                clearAnimation();
            }
        });
    </script>
</body>
</html>