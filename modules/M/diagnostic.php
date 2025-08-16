<?php
// modules/M/diagnostic.php - Check for process loading issues
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../shared/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🔍 MACTA Process Diagnostic Tool</h2>";
echo "<p>Checking for common issues that prevent processes from loading...</p>";

// Check 1: Verify database connection
echo "<h3>✅ Database Connection</h3>";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM process_models");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Database connected successfully. Found {$count} processes.<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check 2: Validate BPMN XML structure
echo "<h3>📋 BPMN XML Validation</h3>";
$stmt = $conn->prepare("SELECT id, name, model_data FROM process_models");
$stmt->execute();
$processes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($processes as $process) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<strong>Process ID {$process['id']}: {$process['name']}</strong><br>";
    
    // Check if XML is valid
    $xml = $process['model_data'];
    if (empty($xml)) {
        echo "❌ Empty XML data<br>";
        continue;
    }
    
    // Check for XML declaration
    if (strpos($xml, '<?xml') === false) {
        echo "⚠️ Missing XML declaration<br>";
    } else {
        echo "✅ XML declaration found<br>";
    }
    
    // Check for BPMN namespace
    if (strpos($xml, 'bpmn2:definitions') === false && strpos($xml, 'bpmn:definitions') === false) {
        echo "❌ Invalid BPMN format - missing definitions<br>";
    } else {
        echo "✅ BPMN definitions found<br>";
    }
    
    // Check for diagram visualization
    if (strpos($xml, 'bpmndi:BPMNDiagram') === false) {
        echo "⚠️ Missing diagram visualization (BPMNDiagram)<br>";
        echo "<small>This will cause loading issues in the visual builder</small><br>";
    } else {
        echo "✅ Diagram visualization found<br>";
    }
    
    // Try to parse XML
    try {
        $xmlDoc = new SimpleXMLElement($xml);
        echo "✅ XML is well-formed<br>";
        
        // Count elements
        $xmlDoc->registerXPathNamespace('bpmn2', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $tasks = $xmlDoc->xpath('//bpmn2:*[contains(local-name(), "Task")]');
        $events = $xmlDoc->xpath('//bpmn2:*[contains(local-name(), "Event")]');
        $flows = $xmlDoc->xpath('//bpmn2:sequenceFlow');
        
        echo "<small>Elements found: " . count($tasks) . " tasks, " . count($events) . " events, " . count($flows) . " flows</small><br>";
        
    } catch (Exception $e) {
        echo "❌ XML parsing error: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

// Check 3: Verify simulation data
echo "<h3>⚡ Simulation Configuration</h3>";
$stmt = $conn->prepare("
    SELECT sc.process_id, pm.name, sc.config_data
    FROM simulation_configs sc
    JOIN process_models pm ON sc.process_id = pm.id
");
$stmt->execute();
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($configs)) {
    echo "⚠️ No simulation configurations found<br>";
} else {
    foreach ($configs as $config) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;'>";
        echo "✅ Simulation config found for process: {$config['name']}<br>";
        
        $configData = json_decode($config['config_data'], true);
        if ($configData && isset($configData['current']) && isset($configData['optimized']) && isset($configData['future'])) {
            echo "✅ All three scenarios configured (Current, Optimized, Future)<br>";
        } else {
            echo "⚠️ Incomplete scenario configuration<br>";
        }
        echo "</div>";
    }
}

// Check 4: Verify resources
echo "<h3>👥 Resources</h3>";
$stmt = $conn->prepare("SELECT COUNT(*) as count, type FROM simulation_resources GROUP BY type");
$stmt->execute();
$resourceCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($resourceCounts)) {
    echo "⚠️ No resources found<br>";
} else {
    foreach ($resourceCounts as $resourceType) {
        echo "✅ {$resourceType['count']} {$resourceType['type']} resources<br>";
    }
}

// Check 5: Test process loading
echo "<h3>🔄 Process Loading Test</h3>";
echo "<p>Testing the load process functionality for each process:</p>";

foreach ($processes as $process) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
    echo "<strong>Testing Process ID {$process['id']}: {$process['name']}</strong><br>";
    
    try {
        // Simulate the load process function
        $stmt = $conn->prepare("
            SELECT p.*, pr.name as project_name 
            FROM process_models p
            LEFT JOIN projects pr ON p.project_id = pr.id
            WHERE p.id = ?
        ");
        $stmt->execute([$process['id']]);
        $loadedProcess = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($loadedProcess) {
            echo "✅ Process loads successfully from database<br>";
            
            // Test XML parsing
            try {
                $xml = new SimpleXMLElement($loadedProcess['model_data']);
                echo "✅ XML parses correctly<br>";
            } catch (Exception $e) {
                echo "❌ XML parsing fails: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Process not found in database<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Loading error: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

// Recommendations
echo "<h3>💡 Recommendations</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 6px;'>";
echo "<h4>To fix the 'error loading process data' issue:</h4>";
echo "<ol>";
echo "<li><strong>Run the SQL fix above</strong> to add missing diagram data to process ID 8</li>";
echo "<li><strong>Clear browser cache</strong> and try loading the process again</li>";
echo "<li><strong>Check browser console</strong> for JavaScript errors (F12 → Console)</li>";
echo "<li><strong>Verify file permissions</strong> on the modules/M/ directory</li>";
echo "<li><strong>Check PHP error logs</strong> for server-side issues</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 6px;'>";
echo "<p>After running the SQL fix:</p>";
echo "<ol>";
echo "<li>Visit <a href='visual_builder.php' target='_blank'>modules/M/visual_builder.php</a></li>";
echo "<li>Try loading 'Customer Order Processing - Complete Workflow'</li>";
echo "<li>If it works, proceed to <a href='simulation.php' target='_blank'>simulation.php</a></li>";
echo "<li>Load the same process in the simulation module</li>";
echo "<li>Run the simulation to see the results</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6; 
}
h2, h3 { 
    color: #2c3e50; 
    border-bottom: 2px solid #3498db; 
    padding-bottom: 5px; 
}
</style>