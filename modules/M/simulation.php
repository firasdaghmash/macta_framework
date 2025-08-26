<?php
// modules/M/simulation.php - Advanced Process Simulation Sub-page
header('Content-Type: text/html; charset=utf-8');
?>

<div class="tab-header">
    <h2>
        <span class="tab-icon">⚡</span>
        Advanced Process Simulation
    </h2>
    <p>Run advanced simulations with different scenarios and color-coded runs for performance analysis</p>
</div>

<!-- Animation Status for Simulation -->
<div class="animation-status" id="simulation-status">
    <span>⚡</span>
    <div>
        <strong>Simulation Status:</strong>
        <span id="simulation-text">Ready to simulate</span>
    </div>
    <div style="margin-left: auto;">
        <strong>Total Runs: <span id="simulation-run-count">0</span></strong>
    </div>
</div>

<!-- Enhanced Simulation Controls -->
<div class="toolbar">
    <button class="btn btn-success" id="btn-start-simulation">
        ▶️ Start Simulation
    </button>
    <button class="btn btn-warning" id="btn-pause-simulation">
        ⏸️ Pause
    </button>
    <button class="btn btn-danger" id="btn-stop-simulation">
        ⏹️ Stop
    </button>
    <button class="btn btn-secondary" id="btn-reset-simulation">
        🔄 Reset All
    </button>
    <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
        <label>Speed:</label>
        <input type="range" id="sim-speed" min="0.5" max="3" step="0.1" value="1" style="width: 100px;">
        <span id="speed-display">1x</span>
    </div>
</div>

<!-- Simulation Configuration Panel -->
<div class="simulation-config">
    <h3>🎛️ Simulation Configuration</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="config-group">
            <label>Simulation Type:</label>
            <select id="simulation-type">
                <option value="single">Single Instance</option>
                <option value="multiple">Multiple Instances</option>
                <option value="stress">Stress Test</option>
                <option value="monte-carlo">Monte Carlo</option>
            </select>
        </div>
        <div class="config-group">
            <label>Number of Instances:</label>
            <input type="number" id="instance-count" value="5" min="1" max="100">
        </div>
        <div class="config-group">
            <label>Inter-arrival Time (seconds):</label>
            <input type="number" id="arrival-time" value="2" min="0.1" step="0.1">
        </div>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="config-group">
            <label>Business Scenario:</label>
            <select id="business-scenario">
                <option value="standard">Standard Operations</option>
                <option value="peak">Peak Load</option>
                <option value="holiday">Holiday Season</option>
                <option value="emergency">Emergency Response</option>
            </select>
        </div>
        <div class="config-group">
            <label>Resource Availability:</label>
            <select id="resource-availability">
                <option value="100">100% - Full Capacity</option>
                <option value="80">80% - Normal Operations</option>
                <option value="60">60% - Reduced Capacity</option>
                <option value="40">40% - Critical Shortage</option>
            </select>
        </div>
    </div>
</div>

<!-- Simulation Viewer -->
<div id="simulation-viewer" style="height: 600px; border: 2px solid #ddd; border-radius: 10px; background: #fafafa; margin-bottom: 20px;">
    <div class="loading">Click Start Simulation to begin advanced process simulation...</div>
</div>

<!-- Enhanced Performance Metrics -->
<div class="performance-metrics">
    <div class="metric-card">
        <div class="metric-value" id="total-time">--</div>
        <div class="metric-label">Total Process Time</div>
    </div>
    <div class="metric-card">
        <div class="metric-value" id="active-tokens">0</div>
        <div class="metric-label">Active Tokens</div>
    </div>
    <div class="metric-card">
        <div class="metric-value" id="completed-instances">0</div>
        <div class="metric-label">Completed Instances</div>
    </div>
    <div class="metric-card">
        <div class="metric-value" id="efficiency-score">--</div>
        <div class="metric-label">Efficiency Score</div>
    </div>
    <div class="metric-card">
        <div class="metric-value" id="throughput">--</div>
        <div class="metric-label">Throughput/Hour</div>
    </div>
    <div class="metric-card">
        <div class="metric-value" id="avg-wait-time">--</div>
        <div class="metric-label">Avg Wait Time</div>
    </div>
</div>

<!-- Simulation Results Panel -->
<div class="simulation-results">
    <h3>📊 Simulation Results & Insights</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="results-panel">
            <h4>📈 Performance Summary</h4>
            <div class="result-item">
                <span class="result-label">Total Simulation Time:</span>
                <span class="result-value" id="sim-total-time">--</span>
            </div>
            <div class="result-item">
                <span class="result-label">Instances Processed:</span>
                <span class="result-value" id="sim-processed">--</span>
            </div>
            <div class="result-item">
                <span class="result-label">Success Rate:</span>
                <span class="result-value" id="sim-success-rate">--</span>
            </div>
            <div class="result-item">
                <span class="result-label">Resource Utilization:</span>
                <span class="result-value" id="sim-utilization">--</span>
            </div>
        </div>
        <div class="results-panel">
            <h4>🎯 Key Insights</h4>
            <div class="insights-list" id="simulation-insights">
                <div class="insight-item">Start simulation to generate insights...</div>
            </div>
        </div>
    </div>
</div>

<!-- Color Legend for Simulation -->
<div class="color-legend">
    <h4>🎨 Simulation Color System</h4>
    <p>Each simulation run gets a unique color that persists until reset</p>
    <div class="legend-items" id="simulation-legend-items">
        <!-- Dynamic legend items will be populated by JavaScript -->
    </div>
</div>

<!-- BPMN.js styles -->
<link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/diagram-js.css" />
<link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.0/dist/assets/bpmn-font/css/bpmn-embedded.css" />

<style>
/* Simulation specific styles */
.simulation-config {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid var(--macta-light);
}

.config-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.config-group label {
    font-weight: bold;
    color: #333;
}

.config-group select,
.config-group input {
    padding: 8px;
    border: 2px solid var(--macta-light);
    border-radius: 6px;
    font-size: 14px;
}

.config-group select:focus,
.config-group input:focus {
    border-color: var(--htt-blue);
    outline: none;
}

/* Performance Metrics */
.performance-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.metric-card {
    background: linear-gradient(135deg, var(--macta-teal), var(--macta-green));
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    transition: transform 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-3px);
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 8px;
}

.metric-label {
    font-size: 12px;
    opacity: 0.9;
}

.simulation-results {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px solid var(--macta-light);
}

.results-panel {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.result-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.result-item:last-child {
    border-bottom: none;
}

.result-label {
    font-weight: 500;
}

.result-value {
    font-weight: bold;
    color: var(--htt-blue);
}

.insights-list {
    min-height: 120px;
}

.insight-item {
    padding: 8px;
    margin: 5px 0;
    background: white;
    border-radius: 5px;
    border-left: 4px solid var(--macta-teal);
    font-size: 14px;
}

/* Animation styles from view page */
.animation-status {
    background: white;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid var(--htt-blue);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.animation-status.running {
    border-left-color: var(--macta-green);
    background: #e8f5e8;
}

.animation-status.stopped {
    border-left-color: var(--macta-red);
    background: #ffebee;
}

.animation-status.completed {
    border-left-color: var(--macta-teal);
    background: #e0f7fa;
}

/* Same animation classes as view page for consistency */
.animation-run-1 .djs-visual > rect,
.animation-run-1 .djs-visual > circle,
.animation-run-1 .djs-visual > polygon {
    fill: #FF6B6B !important;
    stroke: #e55555 !important;
    stroke-width: 4px !important;
    animation: pulse-1 1.5s infinite;
}

.animation-run-2 .djs-visual > rect,
.animation-run-2 .djs-visual > circle,
.animation-run-2 .djs-visual > polygon {
    fill: #4ECDC4 !important;
    stroke: #3cb8b1 !important;
    stroke-width: 4px !important;
    animation: pulse-2 1.5s infinite;
}

.animation-run-3 .djs-visual > rect,
.animation-run-3 .djs-visual > circle,
.animation-run-3 .djs-visual > polygon {
    fill: #45B7D1 !important;
    stroke: #3a9bc1 !important;
    stroke-width: 4px !important;
    animation: pulse-3 1.5s infinite;
}

.animation-run-4 .djs-visual > rect,
.animation-run-4 .djs-visual > circle,
.animation-run-4 .djs-visual > polygon {
    fill: #96CEB4 !important;
    stroke: #7bb89f !important;
    stroke-width: 4px !important;
    animation: pulse-4 1.5s infinite;
}

/* Pulse animations */
@keyframes pulse-1 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
@keyframes pulse-2 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
@keyframes pulse-3 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
@keyframes pulse-4 { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
</style>

<script>
// Simulation sub-page specific JavaScript
let simulationViewer = null;
let simulationRunCount = 0;
let isSimulating = false;
let simulationTimeouts = [];
let simulationInterval = null;
let simulationStartTime = null;

// Animation colors system (same as view page)
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

function loadScript(urls, callback) {
    let currentIndex = 0;
    
    function tryNextUrl() {
        if (currentIndex >= urls.length) {
            console.error('All CDN sources failed');
            return;
        }
        
        const script = document.createElement('script');
        script.src = urls[currentIndex];
        script.onload = callback;
        script.onerror = () => {
            console.warn('Failed to load from:', urls[currentIndex]);
            currentIndex++;
            tryNextUrl();
        };
        document.head.appendChild(script);
    }
    
    tryNextUrl();
}

function initializeSimulationViewer() {
    const bpmnCdnUrls = [
        'https://unpkg.com/bpmn-js@17.0.0/dist/bpmn-viewer.development.js',
        'https://cdn.jsdelivr.net/npm/bpmn-js@17.0.0/dist/bpmn-viewer.development.js',
        'https://unpkg.com/bpmn-js@16.0.0/dist/bpmn-viewer.development.js'
    ];
    
    loadScript(bpmnCdnUrls, () => {
        try {
            if (typeof BpmnJS === 'undefined') {
                throw new Error('BpmnJS not loaded');
            }
            
            // Initialize simulation viewer
            simulationViewer = new BpmnJS({
                container: '#simulation-viewer'
            });
            
            console.log('✅ BPMN Simulation Viewer initialized successfully');
            
            // Check if there's a process to simulate
            const currentXML = sessionStorage.getItem('currentProcessXML');
            if (currentXML) {
                loadProcessInSimulation(currentXML);
                updateSimulationStatus('ready', 'Process loaded - Configure and start simulation');
            }
            
        } catch (error) {
            console.error('Failed to initialize BPMN Simulation Viewer:', error);
            document.querySelector('#simulation-viewer .loading').innerHTML = 'BPMN Simulation Viewer initialization failed: ' + error.message;
        }
    });
}

async function loadProcessInSimulation(xml) {
    if (!simulationViewer) return;
    
    try {
        await simulationViewer.importXML(xml);
        simulationViewer.get('canvas').zoom('fit-viewport');
        
        const simLoading = document.querySelector('#simulation-viewer .loading');
        if (simLoading) {
            simLoading.style.display = 'none';
        }
        
        updateSimulationStatus('ready', 'Process loaded - Ready for simulation');
        
    } catch (error) {
        console.error('Failed to load process in simulation:', error);
        updateSimulationStatus('error', 'Failed to load process');
    }
}

function startSimulation() {
    if (!simulationViewer || isSimulating) return;
    
    const simulationType = document.getElementById('simulation-type').value;
    const instanceCount = parseInt(document.getElementById('instance-count').value) || 1;
    const arrivalTime = parseFloat(document.getElementById('arrival-time').value) || 2;
    
    simulationRunCount++;
    simulationStartTime = Date.now();
    
    updateSimulationStatus('running', `Running ${simulationType} simulation with ${instanceCount} instances`);
    updateSimulationRunCount();
    
    isSimulating = true;
    
    // Start performance metrics tracking
    startMetricsSimulation();
    
    // Run simulation based on type
    if (simulationType === 'single') {
        runSingleInstanceSimulation();
    } else if (simulationType === 'multiple') {
        runMultipleInstanceSimulation(instanceCount, arrivalTime);
    } else if (simulationType === 'stress') {
        runStressTestSimulation();
    } else if (simulationType === 'monte-carlo') {
        runMonteCarloSimulation();
    }
}

function runSingleInstanceSimulation() {
    const currentRun = ((simulationRunCount - 1) % 8) + 1;
    const animationClass = `animation-run-${currentRun}`;
    
    try {
        const elementRegistry = simulationViewer.get('elementRegistry');
        const elements = elementRegistry.getAll();
        const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
        
        if (startEvent) {
            simulateProcessPath(startEvent, elementRegistry, animationClass, 1500);
        }
    } catch (error) {
        console.error('Single instance simulation error:', error);
    }
}

function runMultipleInstanceSimulation(instanceCount, arrivalTime) {
    let currentInstance = 0;
    
    const spawnInstance = () => {
        if (currentInstance < instanceCount && isSimulating) {
            const currentRun = ((currentInstance) % 8) + 1;
            const animationClass = `animation-run-${currentRun}`;
            
            try {
                const elementRegistry = simulationViewer.get('elementRegistry');
                const elements = elementRegistry.getAll();
                const startEvent = elements.find(el => el.type === 'bpmn:StartEvent');
                
                if (startEvent) {
                    simulateProcessPath(startEvent, elementRegistry, animationClass, 1000);
                }
            } catch (error) {
                console.error('Multiple instance simulation error:', error);
            }
            
            currentInstance++;
            
            if (currentInstance < instanceCount) {
                const timeout = setTimeout(spawnInstance, arrivalTime * 1000);
                simulationTimeouts.push(timeout);
            }
        }
    };
    
    spawnInstance();
}

function runStressTestSimulation() {
    updateSimulationInsights(['🔥 Stress test initiated - High volume simulation', '📈 Monitoring system performance under load']);
    
    // Simulate high load with rapid instance creation
    runMultipleInstanceSimulation(20, 0.5);
}

function runMonteCarloSimulation() {
    updateSimulationInsights(['🎲 Monte Carlo simulation started', '📊 Running statistical analysis with random variations']);
    
    // Run multiple scenarios with random parameters
    for (let i = 0; i < 10; i++) {
        const randomDelay = Math.random() * 3 + 0.5; // 0.5 to 3.5 seconds
        setTimeout(() => {
            if (isSimulating) {
                runSingleInstanceSimulation();
            }
        }, i * randomDelay * 1000);
    }
}

async function simulateProcessPath(currentElement, elementRegistry, animationClass, delay) {
    if (!currentElement || !isSimulating) return;
    
    try {
        const gfx = elementRegistry.getGraphics(currentElement);
        if (gfx) {
            gfx.classList.add(animationClass);
            updateActiveTokens();
        }
    } catch (error) {
        console.log('Element highlighting failed:', error);
    }
    
    const timeout = setTimeout(async () => {
        const outgoing = currentElement.businessObject?.outgoing;
        if (outgoing && outgoing.length > 0) {
            
            for (const flow of outgoing) {
                const flowGfx = elementRegistry.getGraphics(flow);
                if (flowGfx) {
                    flowGfx.classList.add(animationClass);
                }
                
                const nextElement = elementRegistry.get(flow.targetRef?.id);
                if (nextElement && isSimulating) {
                    if (nextElement.type !== 'bpmn:EndEvent') {
                        await simulateProcessPath(nextElement, elementRegistry, animationClass, delay);
                    } else {
                        const endGfx = elementRegistry.getGraphics(nextElement);
                        if (endGfx) {
                            endGfx.classList.add(animationClass);
                            updateCompletedInstances();
                        }
                    }
                }
            }
        }
    }, delay);
    
    simulationTimeouts.push(timeout);
}

function pauseSimulation() {
    isSimulating = false;
    simulationTimeouts.forEach(timeout => clearTimeout(timeout));
    simulationTimeouts = [];
    
    if (simulationInterval) {
        clearInterval(simulationInterval);
        simulationInterval = null;
    }
    
    updateSimulationStatus('paused', 'Simulation paused');
}

function stopSimulation() {
    isSimulating = false;
    simulationTimeouts.forEach(timeout => clearTimeout(timeout));
    simulationTimeouts = [];
    
    if (simulationInterval) {
        clearInterval(simulationInterval);
        simulationInterval = null;
    }
    
    updateSimulationStatus('stopped', 'Simulation stopped');
    generateSimulationReport();
}

function resetSimulation() {
    stopSimulation();
    
    // Clear all highlights
    if (simulationViewer) {
        clearAllHighlights();
    }
    
    // Reset counters and metrics
    simulationRunCount = 0;
    updateSimulationRunCount();
    resetMetrics();
    
    updateSimulationStatus('ready', 'Simulation reset - Ready for new simulation');
    updateSimulationInsights(['Simulation reset', 'Configure parameters and start new simulation']);
}

function clearAllHighlights() {
    try {
        const elementRegistry = simulationViewer.get('elementRegistry');
        const elements = elementRegistry.getAll();
        
        elements.forEach(element => {
            const gfx = elementRegistry.getGraphics(element);
            if (gfx) {
                for (let i = 1; i <= 8; i++) {
                    gfx.classList.remove(`animation-run-${i}`);
                }
            }
        });
        
    } catch (error) {
        console.error('Failed to clear highlights:', error);
    }
}

function startMetricsSimulation() {
    let totalTime = 0;
    
    simulationInterval = setInterval(() => {
        if (!isSimulating) {
            clearInterval(simulationInterval);
            return;
        }
        
        totalTime += 1;
        
        // Update metrics
        const minutes = Math.floor(totalTime / 60);
        const seconds = totalTime % 60;
        const timeDisplay = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
        
        document.getElementById('total-time').textContent = timeDisplay;
        
        // Calculate throughput
        const completed = parseInt(document.getElementById('completed-instances').textContent) || 0;
        if (totalTime > 0) {
            const throughput = Math.round((completed / totalTime) * 3600); // per hour
            document.getElementById('throughput').textContent = throughput;
        }
        
        // Calculate efficiency score
        if (totalTime > 0 && completed > 0) {
            const efficiency = Math.min(100, Math.round((completed / (totalTime / 30)) * 100));
            document.getElementById('efficiency-score').textContent = `${efficiency}%`;
        }
        
        // Calculate average wait time
        const avgWait = Math.max(1, Math.round(totalTime / Math.max(1, completed)));
        document.getElementById('avg-wait-time').textContent = `${avgWait}s`;
        
    }, 1000);
}

function updateActiveTokens() {
    const activeCount = document.querySelectorAll('#simulation-viewer [class*="animation-run-"]').length;
    document.getElementById('active-tokens').textContent = activeCount;
}

function updateCompletedInstances() {
    const completed = parseInt(document.getElementById('completed-instances').textContent) + 1;
    document.getElementById('completed-instances').textContent = completed;
}

function resetMetrics() {
    document.getElementById('total-time').textContent = '--';
    document.getElementById('active-tokens').textContent = '0';
    document.getElementById('completed-instances').textContent = '0';
    document.getElementById('efficiency-score').textContent = '--';
    document.getElementById('throughput').textContent = '--';
    document.getElementById('avg-wait-time').textContent = '--';
}

function updateSimulationStatus(status, text) {
    const statusElement = document.getElementById('simulation-status');
    const textElement = document.getElementById('simulation-text');
    
    if (statusElement && textElement) {
        statusElement.className = `animation-status ${status}`;
        textElement.textContent = text;
    }
}

function updateSimulationRunCount() {
    const countElement = document.getElementById('simulation-run-count');
    if (countElement) {
        countElement.textContent = simulationRunCount;
    }
}

function updateSimulationInsights(insights) {
    const insightsContainer = document.getElementById('simulation-insights');
    if (!insightsContainer) return;
    
    insightsContainer.innerHTML = '';
    insights.forEach(insight => {
        const item = document.createElement('div');
        item.className = 'insight-item';
        item.textContent = insight;
        insightsContainer.appendChild(item);
    });
}

function generateSimulationReport() {
    const simulationTime = simulationStartTime ? Math.round((Date.now() - simulationStartTime) / 1000) : 0;
    const completed = parseInt(document.getElementById('completed-instances').textContent) || 0;
    const efficiency = document.getElementById('efficiency-score').textContent;
    const throughput = document.getElementById('throughput').textContent;
    
    // Update results panel
    document.getElementById('sim-total-time').textContent = `${simulationTime}s`;
    document.getElementById('sim-processed').textContent = completed;
    document.getElementById('sim-success-rate').textContent = '100%'; // Simplified
    document.getElementById('sim-utilization').textContent = efficiency;
    
    // Generate insights
    const insights = [
        `✅ Simulation completed in ${simulationTime} seconds`,
        `📊 Processed ${completed} instances successfully`,
        `⚡ Average throughput: ${throughput} instances/hour`,
        `🎯 Efficiency score: ${efficiency}`,
        '💡 Consider optimizing bottleneck tasks for better performance'
    ];
    
    updateSimulationInsights(insights);
}

function updateColorLegend() {
    const legendContainer = document.getElementById('simulation-legend-items');
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

// Event listeners
document.addEventListener('tabContentLoaded', function(e) {
    if (e.detail.tabName === 'simulation') {
        console.log('⚡ Simulation tab content loaded');
        
        // Initialize BPMN Simulation Viewer
        initializeSimulationViewer();
        
        // Initialize color legend
        updateColorLegend();
        
        // Reset metrics
        resetMetrics();
        
        // Attach event listeners
        attachSimulationEventListeners();
    }
});

function attachSimulationEventListeners() {
    // Simulation controls
    document.getElementById('btn-start-simulation')?.addEventListener('click', () => {
        startSimulation();
    });

    document.getElementById('btn-pause-simulation')?.addEventListener('click', () => {
        pauseSimulation();
    });

    document.getElementById('btn-stop-simulation')?.addEventListener('click', () => {
        stopSimulation();
    });

    document.getElementById('btn-reset-simulation')?.addEventListener('click', () => {
        resetSimulation();
    });

    // Speed control
    document.getElementById('sim-speed')?.addEventListener('input', (e) => {
        document.getElementById('speed-display').textContent = e.target.value + 'x';
    });

    // Configuration change handlers
    document.getElementById('simulation-type')?.addEventListener('change', (e) => {
        const type = e.target.value;
        const instanceCountGroup = document.getElementById('instance-count').closest('.config-group');
        const arrivalTimeGroup = document.getElementById('arrival-time').closest('.config-group');
        
        if (type === 'single') {
            instanceCountGroup.style.opacity = '0.5';
            arrivalTimeGroup.style.opacity = '0.5';
        } else {
            instanceCountGroup.style.opacity = '1';
            arrivalTimeGroup.style.opacity = '1';
        }
    });
}

console.log('⚡ Simulation sub-page script loaded');