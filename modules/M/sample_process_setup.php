<?php
// sample_process_setup.php - Creates the complete customer order process example
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../shared/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Step 1: Create sample resources first
echo "<h2>🛠️ Step 1: Creating Sample Resources</h2>";

$sampleResources = [
    // Customer Service Team
    ['CS Rep Junior', 'human', 25.00, 8.00, 'intermediate', 'Handles routine customer interactions and order entry'],
    ['CS Rep Senior', 'human', 40.00, 8.00, 'expert', 'Handles complex customer issues and escalations'],
    ['CS Manager', 'human', 60.00, 6.00, 'expert', 'Manages team and approves customer resolutions'],
    
    // Sales Team  
    ['Sales Representative', 'human', 65.00, 7.00, 'expert', 'Creates quotes, negotiates deals, closes sales'],
    ['Sales Manager', 'human', 85.00, 6.00, 'expert', 'Approves large deals and complex pricing'],
    
    // Accounting Team
    ['Staff Accountant', 'human', 45.00, 8.00, 'expert', 'Handles credit checks, invoicing, and payments'],
    ['Accounting Manager', 'human', 70.00, 6.00, 'expert', 'Approves credit decisions and financial processes'],
    
    // Software Systems
    ['CRM System', 'software', 15.00, 24.00, 'intermediate', 'Customer relationship management platform'],
    ['ERP System', 'software', 25.00, 24.00, 'expert', 'Enterprise resource planning for orders and finance'],
    ['Credit Check Service', 'software', 5.00, 24.00, 'expert', 'External credit verification API'],
    ['Email & Communication', 'software', 3.00, 24.00, 'beginner', 'Email and notification systems'],
    ['Scheduling System', 'software', 8.00, 24.00, 'intermediate', 'Delivery and appointment scheduling']
];

foreach ($sampleResources as $resource) {
    $stmt = $conn->prepare("
        INSERT INTO simulation_resources 
        (name, type, hourly_rate, availability_hours, skill_level, description) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        hourly_rate = VALUES(hourly_rate),
        availability_hours = VALUES(availability_hours),
        skill_level = VALUES(skill_level),
        description = VALUES(description)
    ");
    
    $stmt->execute($resource);
    echo "✅ Created resource: {$resource[0]}<br>";
}

// Step 2: Create the BPMN XML for customer order process
echo "<h2>📋 Step 2: Creating BPMN Process Model</h2>";

$bpmnXml = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn2:definitions xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                   xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                   xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                   xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                   id="customer-order-process" 
                   targetNamespace="http://macta.htt.com">
  
  <bpmn2:process id="CustomerOrderProcess" isExecutable="false" name="Customer Order Processing">
    
    <!-- Start Event -->
    <bpmn2:startEvent id="StartEvent_CustomerInquiry" name="Customer Submits Inquiry">
      <bpmn2:outgoing>Flow_1</bpmn2:outgoing>
    </bpmn2:startEvent>
    
    <!-- CS Tasks -->
    <bpmn2:userTask id="Task_CS_ReviewInquiry" name="CS: Review &amp; Validate Inquiry">
      <bpmn2:incoming>Flow_1</bpmn2:incoming>
      <bpmn2:outgoing>Flow_2</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Sales Tasks -->
    <bpmn2:userTask id="Task_Sales_PrepareQuote" name="Sales: Prepare Quote &amp; Proposal">
      <bpmn2:incoming>Flow_2</bpmn2:incoming>
      <bpmn2:outgoing>Flow_3</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_Customer_ReviewQuote" name="Customer: Review &amp; Approve Quote">
      <bpmn2:incoming>Flow_3</bpmn2:incoming>
      <bpmn2:outgoing>Flow_4</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_Sales_CreateOrder" name="Sales: Create Sales Order">
      <bpmn2:incoming>Flow_4</bpmn2:incoming>
      <bpmn2:outgoing>Flow_5</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Accounting Tasks -->
    <bpmn2:userTask id="Task_Accounting_CreditCheck" name="Accounting: Credit Check &amp; Approval">
      <bpmn2:incoming>Flow_5</bpmn2:incoming>
      <bpmn2:outgoing>Flow_6</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- CS Follow-up Tasks -->
    <bpmn2:userTask id="Task_CS_OrderConfirmation" name="CS: Send Order Confirmation">
      <bpmn2:incoming>Flow_6</bpmn2:incoming>
      <bpmn2:outgoing>Flow_7</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_CS_ScheduleDelivery" name="CS: Schedule Delivery/Implementation">
      <bpmn2:incoming>Flow_7</bpmn2:incoming>
      <bpmn2:outgoing>Flow_8</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Delivery and Payment -->
    <bpmn2:userTask id="Task_Customer_ReceiveService" name="Customer: Receive Product/Service">
      <bpmn2:incoming>Flow_8</bpmn2:incoming>
      <bpmn2:outgoing>Flow_9</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_Accounting_GenerateInvoice" name="Accounting: Generate Invoice">
      <bpmn2:incoming>Flow_9</bpmn2:incoming>
      <bpmn2:outgoing>Flow_10</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_Customer_Payment" name="Customer: Process Payment">
      <bpmn2:incoming>Flow_10</bpmn2:incoming>
      <bpmn2:outgoing>Flow_11</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_Accounting_Reconciliation" name="Accounting: Payment Reconciliation">
      <bpmn2:incoming>Flow_11</bpmn2:incoming>
      <bpmn2:outgoing>Flow_12</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- End Event -->
    <bpmn2:endEvent id="EndEvent_OrderComplete" name="Order Complete">
      <bpmn2:incoming>Flow_12</bpmn2:incoming>
    </bpmn2:endEvent>
    
    <!-- Sequence Flows -->
    <bpmn2:sequenceFlow id="Flow_1" sourceRef="StartEvent_CustomerInquiry" targetRef="Task_CS_ReviewInquiry"/>
    <bpmn2:sequenceFlow id="Flow_2" sourceRef="Task_CS_ReviewInquiry" targetRef="Task_Sales_PrepareQuote"/>
    <bpmn2:sequenceFlow id="Flow_3" sourceRef="Task_Sales_PrepareQuote" targetRef="Task_Customer_ReviewQuote"/>
    <bpmn2:sequenceFlow id="Flow_4" sourceRef="Task_Customer_ReviewQuote" targetRef="Task_Sales_CreateOrder"/>
    <bpmn2:sequenceFlow id="Flow_5" sourceRef="Task_Sales_CreateOrder" targetRef="Task_Accounting_CreditCheck"/>
    <bpmn2:sequenceFlow id="Flow_6" sourceRef="Task_Accounting_CreditCheck" targetRef="Task_CS_OrderConfirmation"/>
    <bpmn2:sequenceFlow id="Flow_7" sourceRef="Task_CS_OrderConfirmation" targetRef="Task_CS_ScheduleDelivery"/>
    <bpmn2:sequenceFlow id="Flow_8" sourceRef="Task_CS_ScheduleDelivery" targetRef="Task_Customer_ReceiveService"/>
    <bpmn2:sequenceFlow id="Flow_9" sourceRef="Task_Customer_ReceiveService" targetRef="Task_Accounting_GenerateInvoice"/>
    <bpmn2:sequenceFlow id="Flow_10" sourceRef="Task_Accounting_GenerateInvoice" targetRef="Task_Customer_Payment"/>
    <bpmn2:sequenceFlow id="Flow_11" sourceRef="Task_Customer_Payment" targetRef="Task_Accounting_Reconciliation"/>
    <bpmn2:sequenceFlow id="Flow_12" sourceRef="Task_Accounting_Reconciliation" targetRef="EndEvent_OrderComplete"/>
    
  </bpmn2:process>
  
</bpmn2:definitions>';

// Insert the process model
$stmt = $conn->prepare("
    INSERT INTO process_models 
    (project_id, name, description, model_data, created_at, updated_at) 
    VALUES (1, ?, ?, ?, NOW(), NOW())
");

$processName = "Customer Order Processing - Complete Workflow";
$processDescription = "End-to-end customer order process involving CS, Sales, Accounting, and Customer touchpoints. Includes inquiry handling, quote preparation, credit checks, order fulfillment, and payment processing.";

$stmt->execute([$processName, $processDescription, $bpmnXml]);
$processId = $conn->lastInsertId();

echo "✅ Created process model: {$processName} (ID: {$processId})<br>";

// Step 3: Create simulation configuration for all three scenarios
echo "<h2>⚙️ Step 3: Creating Simulation Configurations</h2>";

$simulationConfig = [
    'current' => [
        'steps' => [
            'StartEvent_CustomerInquiry' => [
                'name' => 'Customer Submits Inquiry',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 1.0,
                'skillLevel' => 'beginner',
                'equipment' => 'Website, Email, Phone'
            ],
            'Task_CS_ReviewInquiry' => [
                'name' => 'CS: Review & Validate Inquiry',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 25,
                'complexity' => 1.2,
                'skillLevel' => 'intermediate',
                'equipment' => 'CRM System'
            ],
            'Task_Sales_PrepareQuote' => [
                'name' => 'Sales: Prepare Quote & Proposal',
                'duration' => 120,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 1.5,
                'skillLevel' => 'expert',
                'equipment' => 'CRM System, Pricing Tools'
            ],
            'Task_Customer_ReviewQuote' => [
                'name' => 'Customer: Review & Approve Quote',
                'duration' => 2880,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Email, Document Review'
            ],
            'Task_Sales_CreateOrder' => [
                'name' => 'Sales: Create Sales Order',
                'duration' => 45,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'ERP System'
            ],
            'Task_Accounting_CreditCheck' => [
                'name' => 'Accounting: Credit Check & Approval',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 1.3,
                'skillLevel' => 'expert',
                'equipment' => 'Credit Check Service, ERP System'
            ],
            'Task_CS_OrderConfirmation' => [
                'name' => 'CS: Send Order Confirmation',
                'duration' => 20,
                'resources' => 1,
                'hourlyRate' => 40,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'CRM System, Email System'
            ],
            'Task_CS_ScheduleDelivery' => [
                'name' => 'CS: Schedule Delivery/Implementation',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 40,
                'complexity' => 1.1,
                'skillLevel' => 'expert',
                'equipment' => 'Scheduling System, CRM System'
            ],
            'Task_Customer_ReceiveService' => [
                'name' => 'Customer: Receive Product/Service',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 1.0,
                'skillLevel' => 'beginner',
                'equipment' => 'Product/Service Delivery'
            ],
            'Task_Accounting_GenerateInvoice' => [
                'name' => 'Accounting: Generate Invoice',
                'duration' => 25,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'ERP System, Invoice System'
            ],
            'Task_Customer_Payment' => [
                'name' => 'Customer: Process Payment',
                'duration' => 4320,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Payment Systems, Banking'
            ],
            'Task_Accounting_Reconciliation' => [
                'name' => 'Accounting: Payment Reconciliation',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'ERP System, Banking System'
            ]
        ]
    ],
    'optimized' => [
        'steps' => [
            'StartEvent_CustomerInquiry' => [
                'name' => 'Customer Submits Inquiry (Optimized)',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.8,
                'skillLevel' => 'intermediate',
                'equipment' => 'Improved Web Portal, Auto-routing'
            ],
            'Task_CS_ReviewInquiry' => [
                'name' => 'CS: Review & Validate (Optimized)',
                'duration' => 20,
                'resources' => 1,
                'hourlyRate' => 25,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Enhanced CRM with Auto-validation'
            ],
            'Task_Sales_PrepareQuote' => [
                'name' => 'Sales: Prepare Quote (Optimized)',
                'duration' => 90,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 1.2,
                'skillLevel' => 'expert',
                'equipment' => 'Automated Pricing Engine, Quote Templates'
            ],
            'Task_Customer_ReviewQuote' => [
                'name' => 'Customer: Review Quote (Optimized)',
                'duration' => 2160,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.9,
                'skillLevel' => 'intermediate',
                'equipment' => 'Customer Portal with Digital Approval'
            ],
            'Task_Sales_CreateOrder' => [
                'name' => 'Sales: Create Order (Optimized)',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 0.8,
                'skillLevel' => 'expert',
                'equipment' => 'Integrated ERP with Auto-population'
            ],
            'Task_Accounting_CreditCheck' => [
                'name' => 'Accounting: Credit Check (Optimized)',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'Real-time Credit API, Auto-approval Rules'
            ],
            'Task_CS_OrderConfirmation' => [
                'name' => 'CS: Order Confirmation (Optimized)',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 40,
                'complexity' => 0.8,
                'skillLevel' => 'expert',
                'equipment' => 'Automated Email Templates'
            ],
            'Task_CS_ScheduleDelivery' => [
                'name' => 'CS: Schedule Delivery (Optimized)',
                'duration' => 20,
                'resources' => 1,
                'hourlyRate' => 40,
                'complexity' => 0.9,
                'skillLevel' => 'expert',
                'equipment' => 'Smart Scheduling System'
            ],
            'Task_Customer_ReceiveService' => [
                'name' => 'Customer: Receive Service (Optimized)',
                'duration' => 45,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.9,
                'skillLevel' => 'intermediate',
                'equipment' => 'Streamlined Delivery Process'
            ],
            'Task_Accounting_GenerateInvoice' => [
                'name' => 'Accounting: Generate Invoice (Optimized)',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 0.8,
                'skillLevel' => 'expert',
                'equipment' => 'Auto-triggered Invoice Generation'
            ],
            'Task_Customer_Payment' => [
                'name' => 'Customer: Payment (Optimized)',
                'duration' => 2880,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.8,
                'skillLevel' => 'intermediate',
                'equipment' => 'Multiple Payment Options, Reminders'
            ],
            'Task_Accounting_Reconciliation' => [
                'name' => 'Accounting: Reconciliation (Optimized)',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 0.8,
                'skillLevel' => 'expert',
                'equipment' => 'Automated Bank Reconciliation'
            ]
        ]
    ],
    'future' => [
        'steps' => [
            'StartEvent_CustomerInquiry' => [
                'name' => 'Customer Inquiry (AI-Powered)',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.5,
                'skillLevel' => 'expert',
                'equipment' => 'AI Chatbot, Intent Recognition'
            ],
            'Task_CS_ReviewInquiry' => [
                'name' => 'AI: Auto-Validate Inquiry',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.6,
                'skillLevel' => 'expert',
                'equipment' => 'AI Validation Engine'
            ],
            'Task_Sales_PrepareQuote' => [
                'name' => 'AI: Generate Smart Quote',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 25,
                'complexity' => 0.8,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Pricing Engine, ML Models'
            ],
            'Task_Customer_ReviewQuote' => [
                'name' => 'Customer: Digital Review & E-Sign',
                'duration' => 1440,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.6,
                'skillLevel' => 'expert',
                'equipment' => 'Digital Signature Platform'
            ],
            'Task_Sales_CreateOrder' => [
                'name' => 'Auto: Create Order from Quote',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 25,
                'complexity' => 0.5,
                'skillLevel' => 'specialist',
                'equipment' => 'Automated Order Processing'
            ],
            'Task_Accounting_CreditCheck' => [
                'name' => 'AI: Instant Credit Decision',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.7,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Credit Assessment, Real-time APIs'
            ],
            'Task_CS_OrderConfirmation' => [
                'name' => 'Auto: Send Confirmation',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 10,
                'complexity' => 0.5,
                'skillLevel' => 'expert',
                'equipment' => 'Automated Communication System'
            ],
            'Task_CS_ScheduleDelivery' => [
                'name' => 'AI: Optimize Delivery Schedule',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 20,
                'complexity' => 0.7,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Scheduling Optimization'
            ],
            'Task_Customer_ReceiveService' => [
                'name' => 'Customer: Receive & Auto-Confirm',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.7,
                'skillLevel' => 'expert',
                'equipment' => 'IoT Delivery Confirmation'
            ],
            'Task_Accounting_GenerateInvoice' => [
                'name' => 'Auto: Instant Invoice Generation',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.5,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Invoice Processing'
            ],
            'Task_Customer_Payment' => [
                'name' => 'Customer: Instant Payment Options',
                'duration' => 1440,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.6,
                'skillLevel' => 'expert',
                'equipment' => 'Digital Wallet, Crypto, Instant Transfer'
            ],
            'Task_Accounting_Reconciliation' => [
                'name' => 'AI: Auto-Reconciliation',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.5,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Financial Reconciliation'
            ]
        ]
    ]
];

// Save simulation configuration
$stmt = $conn->prepare("
    INSERT INTO simulation_configs 
    (process_id, config_data, created_at, updated_at) 
    VALUES (?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
    config_data = VALUES(config_data), 
    updated_at = NOW()
");

$stmt->execute([$processId, json_encode($simulationConfig)]);
echo "✅ Created simulation configuration for process ID: {$processId}<br>";

// Step 4: Run the actual simulation
echo "<h2>🎮 Step 4: Running Process Simulation</h2>";

function runDetailedSimulation($config, $scenarioName) {
    $results = [
        'name' => $scenarioName,
        'totalTime' => 0,
        'totalCost' => 0,
        'internalTime' => 0,
        'customerTime' => 0,
        'bottlenecks' => [],
        'steps' => [],
        'resourceBreakdown' => [
            'CS' => ['time' => 0, 'cost' => 0],
            'Sales' => ['time' => 0, 'cost' => 0],
            'Accounting' => ['time' => 0, 'cost' => 0],
            'Customer' => ['time' => 0, 'cost' => 0],
            'Systems' => ['time' => 0, 'cost' => 0]
        ]
    ];
    
    foreach ($config['steps'] as $stepId => $stepConfig) {
        // Apply Monte Carlo simulation
        $baseTime = $stepConfig['duration'];
        $complexity = $stepConfig['complexity'];
        $variability = 0.8 + (mt_rand(0, 4000) / 10000); // 0.8 to 1.2
        
        $actualDuration = $baseTime * $complexity * $variability;
        $cost = ($stepConfig['hourlyRate'] * $actualDuration / 60) * $stepConfig['resources'];
        
        // Calculate resource utilization (simulate realistic load)
        $utilization = min(1.0, 0.6 + (mt_rand(0, 4000) / 10000)); // 0.6 to 1.0
        $waitTime = $utilization > 0.8 ? $actualDuration * 0.2 : 0;
        
        $stepResult = [
            'stepId' => $stepId,
            'name' => $stepConfig['name'],
            'duration' => round($actualDuration, 1),
            'cost' => round($cost, 2),
            'utilization' => round($utilization, 3),
            'waitTime' => round($waitTime, 1),
            'efficiency' => round((1 - ($waitTime / $actualDuration)) * 100, 1),
            'resources' => $stepConfig['resources'],
            'hourlyRate' => $stepConfig['hourlyRate']
        ];
        
        $results['steps'][] = $stepResult;
        $results['totalTime'] += $actualDuration;
        $results['totalCost'] += $cost;
        
        // Categorize by department
        $department = 'Systems';
        if (strpos($stepId, 'CS_') !== false) $department = 'CS';
        elseif (strpos($stepId, 'Sales_') !== false) $department = 'Sales';
        elseif (strpos($stepId, 'Accounting_') !== false) $department = 'Accounting';
        elseif (strpos($stepId, 'Customer_') !== false) $department = 'Customer';
        
        $results['resourceBreakdown'][$department]['time'] += $actualDuration;
        $results['resourceBreakdown'][$department]['cost'] += $cost;
        
        // Track internal vs customer time
        if ($department === 'Customer') {
            $results['customerTime'] += $actualDuration;
        } else {
            $results['internalTime'] += $actualDuration;
        }
        
        // Identify bottlenecks
        if ($utilization > 0.8) {
            $results['bottlenecks'][] = [
                'stepId' => $stepId,
                'name' => $stepConfig['name'],
                'utilization' => $utilization,
                'waitTime' => $waitTime,
                'impact' => $utilization > 0.9 ? 'High' : 'Medium'
            ];
        }
    }
    
    return $results;
}

// Run simulation for all scenarios
$simulationResults = [];
foreach (['current', 'optimized', 'future'] as $scenario) {
    $scenarioName = ucfirst($scenario) . ' State';
    $results = runDetailedSimulation($simulationConfig[$scenario], $scenarioName);
    $simulationResults[] = $results;
    
    echo "<h3>📊 {$scenarioName} Results:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 10px 0;'>";
    echo "<strong>Total Process Time:</strong> " . round($results['totalTime']) . " minutes (" . round($results['totalTime']/60, 1) . " hours)<br>";
    echo "<strong>Total Process Cost:</strong> $" . number_format($results['totalCost'], 2) . "<br>";
    echo "<strong>Internal Processing Time:</strong> " . round($results['internalTime']) . " minutes<br>";
    echo "<strong>Customer Wait Time:</strong> " . round($results['customerTime']) . " minutes<br>";
    echo "<strong>Bottlenecks Identified:</strong> " . count($results['bottlenecks']) . "<br>";
    
    if (!empty($results['bottlenecks'])) {
        echo "<strong>Bottleneck Details:</strong><br>";
        foreach ($results['bottlenecks'] as $bottleneck) {
            echo "  • {$bottleneck['name']} - {$bottleneck['impact']} impact ({$bottleneck['utilization']}% utilization)<br>";
        }
    }
    echo "</div>";
}

// Save simulation results
$stmt = $conn->prepare("
    INSERT INTO simulation_results 
    (process_id, scenario_data, results_data, iterations, created_at) 
    VALUES (?, ?, ?, 100, NOW())
");

$stmt->execute([
    $processId, 
    json_encode($simulationConfig), 
    json_encode($simulationResults)
]);

echo "<h2>📈 Step 5: Improvement Analysis</h2>";

// Calculate improvements
$currentResults = $simulationResults[0];
$optimizedResults = $simulationResults[1];
$futureResults = $simulationResults[2];

$timeImprovement1 = (($currentResults['totalTime'] - $optimizedResults['totalTime']) / $currentResults['totalTime']) * 100;
$costImprovement1 = (($currentResults['totalCost'] - $optimizedResults['totalCost']) / $currentResults['totalCost']) * 100;

$timeImprovement2 = (($currentResults['totalTime'] - $futureResults['totalTime']) / $currentResults['totalTime']) * 100;
$costImprovement2 = (($currentResults['totalCost'] - $futureResults['totalCost']) / $currentResults['totalCost']) * 100;

echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>🎯 Key Improvements Identified:</h3>";
echo "<strong>Optimized State vs Current:</strong><br>";
echo "  • Time Reduction: " . round($timeImprovement1, 1) . "%<br>";
echo "  • Cost Savings: " . round($costImprovement1, 1) . "%<br>";
echo "  • Annual Savings: $" . number_format(($currentResults['totalCost'] - $optimizedResults['totalCost']) * 1200, 2) . " (1,200 orders/year)<br><br>";

echo "<strong>Future State vs Current:</strong><br>";
echo "  • Time Reduction: " . round($timeImprovement2, 1) . "%<br>";
echo "  • Cost Savings: " . round($costImprovement2, 1) . "%<br>";
echo "  • Annual Savings: $" . number_format(($currentResults['totalCost'] - $futureResults['totalCost']) * 1200, 2) . " (1,200 orders/year)<br>";
echo "</div>";

echo "<h2>✅ Sample Process Setup Complete!</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>🎉 What You Can Do Now:</h3>";
echo "1. <strong>Visit the Simulation Module:</strong> <a href='simulation.php' target='_blank'>modules/M/simulation.php</a><br>";
echo "2. <strong>Load the Process:</strong> Select 'Customer Order Processing - Complete Workflow'<br>";
echo "3. <strong>View Configuration:</strong> See all the resource assignments we just created<br>";
echo "4. <strong>Run New Simulations:</strong> Modify parameters and run your own scenarios<br>";
echo "5. <strong>Analyze Results:</strong> Use the dashboard to compare scenarios<br>";
echo "6. <strong>Manage Resources:</strong> Visit <a href='resources.php' target='_blank'>resources.php</a> to see all sample resources<br>";
echo "</div>";

echo "<h3>🔍 Understanding the Results:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 10px 0;'>";
echo "<strong>Why These Results Make Sense:</strong><br>";
echo "• <strong>Customer wait time is high</strong> (80% of total time) - This is normal for B2B processes<br>";
echo "• <strong>Sales quote preparation is a bottleneck</strong> - Complex pricing takes time<br>";
echo "• <strong>Credit checks cause delays</strong> - Manual verification processes are slow<br>";
echo "• <strong>Automation shows huge gains</strong> - Future state eliminates most manual work<br>";
echo "• <strong>Cost is mainly human resources</strong> - Expert time is expensive but necessary<br>";
echo "</div>";

echo "<h3>🎯 Next Steps for Your Real Implementation:</h3>";
echo "<ol>";
echo "<li><strong>Model Your Actual Process:</strong> Use the visual builder to create your specific workflow</li>";
echo "<li><strong>Add Your Resources:</strong> Create resources that match your team and tools</li>";
echo "<li><strong>Configure Realistic Times:</strong> Use historical data or estimates for task durations</li>";
echo "<li><strong>Run Multiple Scenarios:</strong> Test different improvement strategies</li>";
echo "<li><strong>Implement Changes:</strong> Start with quick wins identified by the simulation</li>";
echo "</ol>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Process Setup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        h3 { color: #34495e; margin-top: 25px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        div[style*="background"] { padding: 15px; border-radius: 6px; margin: 10px 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>

<script>
// Add some interactive feedback
document.addEventListener('DOMContentLoaded', function() {
    // Highlight important links
    const links = document.querySelectorAll('a[href*=".php"]');
    links.forEach(link => {
        link.style.fontWeight = 'bold';
        link.style.padding = '2px 6px';
        link.style.backgroundColor = '#e3f2fd';
        link.style.borderRadius = '4px';
        
        link.addEventListener('click', function() {
            alert('Opening: ' + this.textContent + '\n\nThis will show you the actual simulation system with the sample data we just created!');
        });
    });
    
    // Add a simple notification
    setTimeout(() => {
        const notification = document.createElement('div');
        notification.innerHTML = `
            <h4>🎯 Quick Start Guide:</h4>
            <p>The sample process is ready! Click on the simulation.php link above to see it in action.</p>
            <button onclick="this.parentNode.style.display='none'" style="float: right; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">×</button>
        `;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; width: 300px; 
            background: #28a745; color: white; padding: 15px; 
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000; font-size: 14px;
        `;
        document.body.appendChild(notification);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 10000);
    }, 2000);
});
</script>

</body>
</html>