<?php
// complex_process_setup.php - Creates a sophisticated insurance claim process with decisions, parallel paths, and lanes
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../shared/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🏗️ Creating Complex Multi-Path Process</h2>";
echo "<p>Building an Insurance Claim Processing workflow with:</p>";
echo "<ul>";
echo "<li>🏊 <strong>Swimlanes</strong>: Customer, Claims Agent, Investigator, Manager, Finance</li>";
echo "<li>🔀 <strong>Decision Gateways</strong>: Claim type routing, approval decisions, investigation triggers</li>";
echo "<li>⚡ <strong>Parallel Paths</strong>: Simultaneous document verification and damage assessment</li>";
echo "<li>🔄 <strong>Loops</strong>: Rework cycles for incomplete information</li>";
echo "<li>⏰ <strong>Timer Events</strong>: Escalation and SLA management</li>";
echo "</ul>";

// Create the complex BPMN with lanes and multiple paths
$complexBpmnXml = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn2:definitions xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                   xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                   xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                   xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                   id="insurance-claim-process" 
                   targetNamespace="http://macta.htt.com">

  <!-- Main Process -->
  <bpmn2:process id="InsuranceClaimProcess" isExecutable="false" name="Insurance Claim Processing">
    
    <!-- Start Event -->
    <bpmn2:startEvent id="StartEvent_ClaimSubmitted" name="Claim Submitted">
      <bpmn2:outgoing>Flow_ToInitialReview</bpmn2:outgoing>
    </bpmn2:startEvent>
    
    <!-- Claims Agent Lane Tasks -->
    <bpmn2:userTask id="Task_InitialReview" name="Initial Claim Review">
      <bpmn2:incoming>Flow_ToInitialReview</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToClaimTypeGateway</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Claim Type Decision Gateway -->
    <bpmn2:exclusiveGateway id="Gateway_ClaimType" name="Claim Type?">
      <bpmn2:incoming>Flow_ToClaimTypeGateway</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToMinorClaim</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_ToMajorClaim</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_ToFraudCheck</bpmn2:outgoing>
    </bpmn2:exclusiveGateway>
    
    <!-- Minor Claim Path -->
    <bpmn2:userTask id="Task_ProcessMinorClaim" name="Process Minor Claim">
      <bpmn2:incoming>Flow_ToMinorClaim</bpmn2:incoming>
      <bpmn2:outgoing>Flow_FromMinorToApproval</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Major Claim Path - Parallel Gateway -->
    <bpmn2:parallelGateway id="Gateway_ParallelStart" name="Start Parallel Processing">
      <bpmn2:incoming>Flow_ToMajorClaim</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToDocVerification</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_ToDamageAssessment</bpmn2:outgoing>
    </bpmn2:parallelGateway>
    
    <!-- Document Verification Path -->
    <bpmn2:userTask id="Task_VerifyDocuments" name="Verify Documents">
      <bpmn2:incoming>Flow_ToDocVerification</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToDocComplete</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Document Completeness Gateway -->
    <bpmn2:exclusiveGateway id="Gateway_DocsComplete" name="Documents Complete?">
      <bpmn2:incoming>Flow_ToDocComplete</bpmn2:incoming>
      <bpmn2:outgoing>Flow_DocsOK</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_DocsIncomplete</bpmn2:outgoing>
    </bpmn2:exclusiveGateway>
    
    <bpmn2:userTask id="Task_RequestAdditionalDocs" name="Request Additional Documents">
      <bpmn2:incoming>Flow_DocsIncomplete</bpmn2:incoming>
      <bpmn2:outgoing>Flow_BackToVerify</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Damage Assessment Path -->
    <bpmn2:userTask id="Task_ScheduleInspection" name="Schedule Damage Inspection">
      <bpmn2:incoming>Flow_ToDamageAssessment</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToInspection</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_ConductInspection" name="Conduct Damage Inspection">
      <bpmn2:incoming>Flow_ToInspection</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToEstimate</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:userTask id="Task_PrepareEstimate" name="Prepare Repair Estimate">
      <bpmn2:incoming>Flow_ToEstimate</bpmn2:incoming>
      <bpmn2:outgoing>Flow_FromEstimate</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Parallel Gateway Join -->
    <bpmn2:parallelGateway id="Gateway_ParallelJoin" name="Join Parallel Processes">
      <bpmn2:incoming>Flow_DocsOK</bpmn2:incoming>
      <bpmn2:incoming>Flow_FromEstimate</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToAmountGateway</bpmn2:outgoing>
    </bpmn2:parallelGateway>
    
    <!-- Claim Amount Decision -->
    <bpmn2:exclusiveGateway id="Gateway_ClaimAmount" name="Claim Amount > $10,000?">
      <bpmn2:incoming>Flow_ToAmountGateway</bpmn2:incoming>
      <bpmn2:incoming>Flow_FromMinorToApproval</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToAutoApprove</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_ToManagerApproval</bpmn2:outgoing>
    </bpmn2:exclusiveGateway>
    
    <!-- Auto Approval Path -->
    <bpmn2:serviceTask id="Task_AutoApprove" name="Auto-Approve Claim">
      <bpmn2:incoming>Flow_ToAutoApprove</bpmn2:incoming>
      <bpmn2:outgoing>Flow_FromAutoApprove</bpmn2:outgoing>
    </bpmn2:serviceTask>
    
    <!-- Manager Approval Path -->
    <bpmn2:userTask id="Task_ManagerReview" name="Manager Review & Decision">
      <bpmn2:incoming>Flow_ToManagerApproval</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToApprovalDecision</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Approval Decision Gateway -->
    <bpmn2:exclusiveGateway id="Gateway_ApprovalDecision" name="Approved?">
      <bpmn2:incoming>Flow_ToApprovalDecision</bpmn2:incoming>
      <bpmn2:outgoing>Flow_Approved</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_Rejected</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_NeedsInvestigation</bpmn2:outgoing>
    </bpmn2:exclusiveGateway>
    
    <!-- Investigation Path -->
    <bpmn2:userTask id="Task_Investigation" name="Conduct Fraud Investigation">
      <bpmn2:incoming>Flow_ToFraudCheck</bpmn2:incoming>
      <bpmn2:incoming>Flow_NeedsInvestigation</bpmn2:incoming>
      <bpmn2:outgoing>Flow_FromInvestigation</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <bpmn2:exclusiveGateway id="Gateway_InvestigationResult" name="Investigation Result?">
      <bpmn2:incoming>Flow_FromInvestigation</bpmn2:incoming>
      <bpmn2:outgoing>Flow_InvestigationOK</bpmn2:outgoing>
      <bpmn2:outgoing>Flow_FraudDetected</bpmn2:outgoing>
    </bpmn2:exclusiveGateway>
    
    <!-- Payment Processing -->
    <bpmn2:userTask id="Task_ProcessPayment" name="Process Payment">
      <bpmn2:incoming>Flow_FromAutoApprove</bpmn2:incoming>
      <bpmn2:incoming>Flow_Approved</bpmn2:incoming>
      <bpmn2:incoming>Flow_InvestigationOK</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToNotifyCustomer</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- Customer Notification -->
    <bpmn2:serviceTask id="Task_NotifyCustomer" name="Notify Customer">
      <bpmn2:incoming>Flow_ToNotifyCustomer</bpmn2:incoming>
      <bpmn2:incoming>Flow_Rejected</bpmn2:incoming>
      <bpmn2:incoming>Flow_FraudDetected</bpmn2:incoming>
      <bpmn2:outgoing>Flow_ToEnd</bpmn2:outgoing>
    </bpmn2:serviceTask>
    
    <!-- Timer Event for SLA -->
    <bpmn2:boundaryEvent id="Timer_SLA" name="5 Day SLA" attachedToRef="Task_ManagerReview">
      <bpmn2:outgoing>Flow_SLAEscalation</bpmn2:outgoing>
      <bpmn2:timerEventDefinition>
        <bpmn2:timeDuration>P5D</bpmn2:timeDuration>
      </bpmn2:timerEventDefinition>
    </bpmn2:boundaryEvent>
    
    <bpmn2:userTask id="Task_EscalateToDirector" name="Escalate to Director">
      <bpmn2:incoming>Flow_SLAEscalation</bpmn2:incoming>
      <bpmn2:outgoing>Flow_FromEscalation</bpmn2:outgoing>
    </bpmn2:userTask>
    
    <!-- End Events -->
    <bpmn2:endEvent id="EndEvent_ClaimProcessed" name="Claim Processed">
      <bpmn2:incoming>Flow_ToEnd</bpmn2:incoming>
      <bpmn2:incoming>Flow_FromEscalation</bpmn2:incoming>
    </bpmn2:endEvent>
    
    <!-- Sequence Flows -->
    <bpmn2:sequenceFlow id="Flow_ToInitialReview" sourceRef="StartEvent_ClaimSubmitted" targetRef="Task_InitialReview"/>
    <bpmn2:sequenceFlow id="Flow_ToClaimTypeGateway" sourceRef="Task_InitialReview" targetRef="Gateway_ClaimType"/>
    
    <bpmn2:sequenceFlow id="Flow_ToMinorClaim" name="Minor ($0-$1,000)" sourceRef="Gateway_ClaimType" targetRef="Task_ProcessMinorClaim"/>
    <bpmn2:sequenceFlow id="Flow_ToMajorClaim" name="Major ($1,000+)" sourceRef="Gateway_ClaimType" targetRef="Gateway_ParallelStart"/>
    <bpmn2:sequenceFlow id="Flow_ToFraudCheck" name="Suspicious" sourceRef="Gateway_ClaimType" targetRef="Task_Investigation"/>
    
    <bpmn2:sequenceFlow id="Flow_FromMinorToApproval" sourceRef="Task_ProcessMinorClaim" targetRef="Gateway_ClaimAmount"/>
    
    <bpmn2:sequenceFlow id="Flow_ToDocVerification" sourceRef="Gateway_ParallelStart" targetRef="Task_VerifyDocuments"/>
    <bpmn2:sequenceFlow id="Flow_ToDamageAssessment" sourceRef="Gateway_ParallelStart" targetRef="Task_ScheduleInspection"/>
    
    <bpmn2:sequenceFlow id="Flow_ToDocComplete" sourceRef="Task_VerifyDocuments" targetRef="Gateway_DocsComplete"/>
    <bpmn2:sequenceFlow id="Flow_DocsOK" name="Complete" sourceRef="Gateway_DocsComplete" targetRef="Gateway_ParallelJoin"/>
    <bpmn2:sequenceFlow id="Flow_DocsIncomplete" name="Incomplete" sourceRef="Gateway_DocsComplete" targetRef="Task_RequestAdditionalDocs"/>
    <bpmn2:sequenceFlow id="Flow_BackToVerify" sourceRef="Task_RequestAdditionalDocs" targetRef="Task_VerifyDocuments"/>
    
    <bpmn2:sequenceFlow id="Flow_ToInspection" sourceRef="Task_ScheduleInspection" targetRef="Task_ConductInspection"/>
    <bpmn2:sequenceFlow id="Flow_ToEstimate" sourceRef="Task_ConductInspection" targetRef="Task_PrepareEstimate"/>
    <bpmn2:sequenceFlow id="Flow_FromEstimate" sourceRef="Task_PrepareEstimate" targetRef="Gateway_ParallelJoin"/>
    
    <bpmn2:sequenceFlow id="Flow_ToAmountGateway" sourceRef="Gateway_ParallelJoin" targetRef="Gateway_ClaimAmount"/>
    
    <bpmn2:sequenceFlow id="Flow_ToAutoApprove" name="≤ $10,000" sourceRef="Gateway_ClaimAmount" targetRef="Task_AutoApprove"/>
    <bpmn2:sequenceFlow id="Flow_ToManagerApproval" name="> $10,000" sourceRef="Gateway_ClaimAmount" targetRef="Task_ManagerReview"/>
    
    <bpmn2:sequenceFlow id="Flow_FromAutoApprove" sourceRef="Task_AutoApprove" targetRef="Task_ProcessPayment"/>
    
    <bpmn2:sequenceFlow id="Flow_ToApprovalDecision" sourceRef="Task_ManagerReview" targetRef="Gateway_ApprovalDecision"/>
    <bpmn2:sequenceFlow id="Flow_Approved" name="Approved" sourceRef="Gateway_ApprovalDecision" targetRef="Task_ProcessPayment"/>
    <bpmn2:sequenceFlow id="Flow_Rejected" name="Rejected" sourceRef="Gateway_ApprovalDecision" targetRef="Task_NotifyCustomer"/>
    <bpmn2:sequenceFlow id="Flow_NeedsInvestigation" name="Needs Investigation" sourceRef="Gateway_ApprovalDecision" targetRef="Task_Investigation"/>
    
    <bpmn2:sequenceFlow id="Flow_FromInvestigation" sourceRef="Task_Investigation" targetRef="Gateway_InvestigationResult"/>
    <bpmn2:sequenceFlow id="Flow_InvestigationOK" name="No Fraud" sourceRef="Gateway_InvestigationResult" targetRef="Task_ProcessPayment"/>
    <bpmn2:sequenceFlow id="Flow_FraudDetected" name="Fraud Detected" sourceRef="Gateway_InvestigationResult" targetRef="Task_NotifyCustomer"/>
    
    <bpmn2:sequenceFlow id="Flow_ToNotifyCustomer" sourceRef="Task_ProcessPayment" targetRef="Task_NotifyCustomer"/>
    <bpmn2:sequenceFlow id="Flow_ToEnd" sourceRef="Task_NotifyCustomer" targetRef="EndEvent_ClaimProcessed"/>
    
    <bpmn2:sequenceFlow id="Flow_SLAEscalation" sourceRef="Timer_SLA" targetRef="Task_EscalateToDirector"/>
    <bpmn2:sequenceFlow id="Flow_FromEscalation" sourceRef="Task_EscalateToDirector" targetRef="EndEvent_ClaimProcessed"/>
    
  </bpmn2:process>
  
  <!-- Collaboration with Lanes -->
  <bpmn2:collaboration id="Collaboration_InsuranceClaim">
    <bpmn2:participant id="Participant_InsuranceCompany" name="Insurance Company" processRef="InsuranceClaimProcess"/>
  </bpmn2:collaboration>
  
  <!-- BPMN Diagram with Lanes -->
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Collaboration_InsuranceClaim">
      
      <!-- Participant/Pool -->
      <bpmndi:BPMNShape id="Participant_InsuranceCompany_di" bpmnElement="Participant_InsuranceCompany" isHorizontal="true">
        <dc:Bounds x="120" y="80" width="2000" height="800"/>
      </bpmndi:BPMNShape>
      
      <!-- Customer Lane -->
      <bpmndi:BPMNShape id="Lane_Customer" bpmnElement="Lane_Customer" isHorizontal="true">
        <dc:Bounds x="150" y="80" width="1970" height="120"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="130" y="130" width="20" height="80"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Claims Agent Lane -->
      <bpmndi:BPMNShape id="Lane_ClaimsAgent" bpmnElement="Lane_ClaimsAgent" isHorizontal="true">
        <dc:Bounds x="150" y="200" width="1970" height="160"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="130" y="260" width="20" height="100"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Investigator Lane -->
      <bpmndi:BPMNShape id="Lane_Investigator" bpmnElement="Lane_Investigator" isHorizontal="true">
        <dc:Bounds x="150" y="360" width="1970" height="120"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="130" y="410" width="20" height="80"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Manager Lane -->
      <bpmndi:BPMNShape id="Lane_Manager" bpmnElement="Lane_Manager" isHorizontal="true">
        <dc:Bounds x="150" y="480" width="1970" height="120"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="130" y="530" width="20" height="60"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Finance Lane -->
      <bpmndi:BPMNShape id="Lane_Finance" bpmnElement="Lane_Finance" isHorizontal="true">
        <dc:Bounds x="150" y="600" width="1970" height="120"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="130" y="650" width="20" height="60"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Process Elements with positioning -->
      
      <!-- Start Event -->
      <bpmndi:BPMNShape id="StartEvent_ClaimSubmitted_di" bpmnElement="StartEvent_ClaimSubmitted">
        <dc:Bounds x="200" y="122" width="36" height="36"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="180" y="165" width="76" height="14"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Initial Review -->
      <bpmndi:BPMNShape id="Task_InitialReview_di" bpmnElement="Task_InitialReview">
        <dc:Bounds x="280" y="240" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Claim Type Gateway -->
      <bpmndi:BPMNShape id="Gateway_ClaimType_di" bpmnElement="Gateway_ClaimType" isMarkerVisible="true">
        <dc:Bounds x="420" y="255" width="50" height="50"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="415" y="225" width="60" height="14"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Minor Claim -->
      <bpmndi:BPMNShape id="Task_ProcessMinorClaim_di" bpmnElement="Task_ProcessMinorClaim">
        <dc:Bounds x="520" y="240" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Parallel Start Gateway -->
      <bpmndi:BPMNShape id="Gateway_ParallelStart_di" bpmnElement="Gateway_ParallelStart">
        <dc:Bounds x="520" y="315" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <!-- Document Verification Path -->
      <bpmndi:BPMNShape id="Task_VerifyDocuments_di" bpmnElement="Task_VerifyDocuments">
        <dc:Bounds x="600" y="240" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <bpmndi:BPMNShape id="Gateway_DocsComplete_di" bpmnElement="Gateway_DocsComplete" isMarkerVisible="true">
        <dc:Bounds x="740" y="255" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <bpmndi:BPMNShape id="Task_RequestAdditionalDocs_di" bpmnElement="Task_RequestAdditionalDocs">
        <dc:Bounds x="715" y="300" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Damage Assessment Path -->
      <bpmndi:BPMNShape id="Task_ScheduleInspection_di" bpmnElement="Task_ScheduleInspection">
        <dc:Bounds x="600" y="380" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <bpmndi:BPMNShape id="Task_ConductInspection_di" bpmnElement="Task_ConductInspection">
        <dc:Bounds x="740" y="380" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <bpmndi:BPMNShape id="Task_PrepareEstimate_di" bpmnElement="Task_PrepareEstimate">
        <dc:Bounds x="880" y="380" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Parallel Join Gateway -->
      <bpmndi:BPMNShape id="Gateway_ParallelJoin_di" bpmnElement="Gateway_ParallelJoin">
        <dc:Bounds x="1020" y="315" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <!-- Amount Gateway -->
      <bpmndi:BPMNShape id="Gateway_ClaimAmount_di" bpmnElement="Gateway_ClaimAmount" isMarkerVisible="true">
        <dc:Bounds x="1120" y="315" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <!-- Auto Approval -->
      <bpmndi:BPMNShape id="Task_AutoApprove_di" bpmnElement="Task_AutoApprove">
        <dc:Bounds x="1200" y="240" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Manager Review -->
      <bpmndi:BPMNShape id="Task_ManagerReview_di" bpmnElement="Task_ManagerReview">
        <dc:Bounds x="1200" y="500" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Timer Boundary Event -->
      <bpmndi:BPMNShape id="Timer_SLA_di" bpmnElement="Timer_SLA">
        <dc:Bounds x="1282" y="562" width="36" height="36"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="1320" y="573" width="52" height="14"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Escalation -->
      <bpmndi:BPMNShape id="Task_EscalateToDirector_di" bpmnElement="Task_EscalateToDirector">
        <dc:Bounds x="1380" y="500" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Approval Decision Gateway -->
      <bpmndi:BPMNShape id="Gateway_ApprovalDecision_di" bpmnElement="Gateway_ApprovalDecision" isMarkerVisible="true">
        <dc:Bounds x="1340" y="515" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <!-- Investigation -->
      <bpmndi:BPMNShape id="Task_Investigation_di" bpmnElement="Task_Investigation">
        <dc:Bounds x="520" y="380" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <bpmndi:BPMNShape id="Gateway_InvestigationResult_di" bpmnElement="Gateway_InvestigationResult" isMarkerVisible="true">
        <dc:Bounds x="1420" y="395" width="50" height="50"/>
      </bpmndi:BPMNShape>
      
      <!-- Payment Processing -->
      <bpmndi:BPMNShape id="Task_ProcessPayment_di" bpmnElement="Task_ProcessPayment">
        <dc:Bounds x="1540" y="620" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- Customer Notification -->
      <bpmndi:BPMNShape id="Task_NotifyCustomer_di" bpmnElement="Task_NotifyCustomer">
        <dc:Bounds x="1700" y="120" width="100" height="80"/>
      </bpmndi:BPMNShape>
      
      <!-- End Event -->
      <bpmndi:BPMNShape id="EndEvent_ClaimProcessed_di" bpmnElement="EndEvent_ClaimProcessed">
        <dc:Bounds x="1850" y="142" width="36" height="36"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="1826" y="185" width="84" height="14"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      
      <!-- Sequence Flow Edges (showing key flows) -->
      <bpmndi:BPMNEdge id="Flow_ToInitialReview_di" bpmnElement="Flow_ToInitialReview">
        <di:waypoint x="236" y="140"/>
        <di:waypoint x="258" y="140"/>
        <di:waypoint x="258" y="280"/>
        <di:waypoint x="280" y="280"/>
      </bpmndi:BPMNEdge>
      
      <bpmndi:BPMNEdge id="Flow_ToClaimTypeGateway_di" bpmnElement="Flow_ToClaimTypeGateway">
        <di:waypoint x="380" y="280"/>
        <di:waypoint x="420" y="280"/>
      </bpmndi:BPMNEdge>
      
      <bpmndi:BPMNEdge id="Flow_ToMinorClaim_di" bpmnElement="Flow_ToMinorClaim">
        <di:waypoint x="470" y="280"/>
        <di:waypoint x="520" y="280"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="472" y="262" width="47" height="27"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      
      <bpmndi:BPMNEdge id="Flow_ToMajorClaim_di" bpmnElement="Flow_ToMajorClaim">
        <di:waypoint x="445" y="305"/>
        <di:waypoint x="445" y="340"/>
        <di:waypoint x="520" y="340"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="448" y="346" width="44" height="27"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      
      <bpmndi:BPMNEdge id="Flow_ToFraudCheck_di" bpmnElement="Flow_ToFraudCheck">
        <di:waypoint x="445" y="305"/>
        <di:waypoint x="445" y="420"/>
        <di:waypoint x="520" y="420"/>
        <bpmndi:BPMNLabel>
          <dc:Bounds x="448" y="426" width="54" height="14"/>
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      
      <!-- Additional key flows -->
      <bpmndi:BPMNEdge id="Flow_ToEnd_di" bpmnElement="Flow_ToEnd">
        <di:waypoint x="1800" y="160"/>
        <di:waypoint x="1850" y="160"/>
      </bpmndi:BPMNEdge>
      
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
  
</bpmn2:definitions>';

// Insert the complex process model
$stmt = $conn->prepare("
    INSERT INTO process_models 
    (project_id, name, description, model_data, created_at, updated_at) 
    VALUES (1, ?, ?, ?, NOW(), NOW())
");

$processName = "Insurance Claim Processing - Multi-Path with Lanes";
$processDescription = "Complex insurance claim workflow featuring multiple decision points, parallel processing paths, swimlanes for different roles, timer events for SLA management, and various routing scenarios including fraud investigation and escalation procedures.";

$stmt->execute([$processName, $processDescription, $complexBpmnXml]);
$complexProcessId = $conn->lastInsertId();

echo "✅ Created complex process model: {$processName} (ID: {$complexProcessId})<br>";

// Create comprehensive simulation configuration for the complex process
echo "<h3>⚙️ Creating Advanced Simulation Configuration</h3>";

$complexSimulationConfig = [
    'current' => [
        'steps' => [
            'StartEvent_ClaimSubmitted' => [
                'name' => 'Customer Submits Claim',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 1.0,
                'skillLevel' => 'beginner',
                'equipment' => 'Online Portal, Phone, Email'
            ],
            'Task_InitialReview' => [
                'name' => 'Claims Agent: Initial Review',
                'duration' => 45,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.2,
                'skillLevel' => 'intermediate',
                'equipment' => 'Claims System, Phone'
            ],
            'Task_ProcessMinorClaim' => [
                'name' => 'Process Minor Claim',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Claims System'
            ],
            'Task_VerifyDocuments' => [
                'name' => 'Verify Documents',
                'duration' => 90,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.3,
                'skillLevel' => 'intermediate',
                'equipment' => 'Document Management System'
            ],
            'Task_RequestAdditionalDocs' => [
                'name' => 'Request Additional Documents',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Email, Phone'
            ],
            'Task_ScheduleInspection' => [
                'name' => 'Schedule Damage Inspection',
                'duration' => 45,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.1,
                'skillLevel' => 'intermediate',
                'equipment' => 'Scheduling System'
            ],
            'Task_ConductInspection' => [
                'name' => 'Conduct Damage Inspection',
                'duration' => 180,
                'resources' => 1,
                'hourlyRate' => 55,
                'complexity' => 1.4,
                'skillLevel' => 'expert',
                'equipment' => 'Vehicle, Camera, Measuring Tools'
            ],
            'Task_PrepareEstimate' => [
                'name' => 'Prepare Repair Estimate',
                'duration' => 120,
                'resources' => 1,
                'hourlyRate' => 55,
                'complexity' => 1.3,
                'skillLevel' => 'expert',
                'equipment' => 'Estimation Software'
            ],
            'Task_AutoApprove' => [
                'name' => 'Auto-Approve Small Claim',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 10,
                'complexity' => 0.5,
                'skillLevel' => 'expert',
                'equipment' => 'Automated System'
            ],
            'Task_ManagerReview' => [
                'name' => 'Manager Review & Decision',
                'duration' => 240,
                'resources' => 1,
                'hourlyRate' => 75,
                'complexity' => 1.5,
                'skillLevel' => 'expert',
                'equipment' => 'Claims System, Analytics'
            ],
            'Task_Investigation' => [
                'name' => 'Fraud Investigation',
                'duration' => 480,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 2.0,
                'skillLevel' => 'specialist',
                'equipment' => 'Investigation Tools, Database Access'
            ],
            'Task_EscalateToDirector' => [
                'name' => 'Escalate to Director',
                'duration' => 180,
                'resources' => 1,
                'hourlyRate' => 95,
                'complexity' => 1.2,
                'skillLevel' => 'expert',
                'equipment' => 'Executive Dashboard'
            ],
            'Task_ProcessPayment' => [
                'name' => 'Process Payment',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'Payment System, Banking'
            ],
            'Task_NotifyCustomer' => [
                'name' => 'Notify Customer',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 10,
                'complexity' => 0.8,
                'skillLevel' => 'beginner',
                'equipment' => 'Email, SMS, Phone'
            ]
        ]
    ],
    'optimized' => [
        'steps' => [
            'StartEvent_ClaimSubmitted' => [
                'name' => 'Smart Claim Submission',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.7,
                'skillLevel' => 'intermediate',
                'equipment' => 'AI-Powered Portal, Auto-routing'
            ],
            'Task_InitialReview' => [
                'name' => 'AI-Assisted Initial Review',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'AI Claims Assistant, Smart Routing'
            ],
            'Task_ProcessMinorClaim' => [
                'name' => 'Streamlined Minor Claim Processing',
                'duration' => 40,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 0.8,
                'skillLevel' => 'intermediate',
                'equipment' => 'Automated Workflow System'
            ],
            'Task_VerifyDocuments' => [
                'name' => 'AI Document Verification',
                'duration' => 45,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.0,
                'skillLevel' => 'intermediate',
                'equipment' => 'OCR, AI Document Analysis'
            ],
            'Task_RequestAdditionalDocs' => [
                'name' => 'Automated Document Request',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 0.6,
                'skillLevel' => 'intermediate',
                'equipment' => 'Auto-generated Templates'
            ],
            'Task_ScheduleInspection' => [
                'name' => 'Smart Inspection Scheduling',
                'duration' => 20,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 0.8,
                'skillLevel' => 'intermediate',
                'equipment' => 'AI Scheduling Optimization'
            ],
            'Task_ConductInspection' => [
                'name' => 'Digital-Enhanced Inspection',
                'duration' => 120,
                'resources' => 1,
                'hourlyRate' => 55,
                'complexity' => 1.2,
                'skillLevel' => 'expert',
                'equipment' => 'Drone, AI Image Analysis, Mobile App'
            ],
            'Task_PrepareEstimate' => [
                'name' => 'AI-Generated Estimate',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 55,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'AI Estimation Engine, Market Data'
            ],
            'Task_AutoApprove' => [
                'name' => 'Instant Auto-Approval',
                'duration' => 2,
                'resources' => 1,
                'hourlyRate' => 10,
                'complexity' => 0.3,
                'skillLevel' => 'specialist',
                'equipment' => 'Machine Learning System'
            ],
            'Task_ManagerReview' => [
                'name' => 'Data-Driven Manager Review',
                'duration' => 120,
                'resources' => 1,
                'hourlyRate' => 75,
                'complexity' => 1.2,
                'skillLevel' => 'expert',
                'equipment' => 'Analytics Dashboard, Risk Scoring'
            ],
            'Task_Investigation' => [
                'name' => 'AI-Assisted Investigation',
                'duration' => 240,
                'resources' => 1,
                'hourlyRate' => 65,
                'complexity' => 1.5,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Pattern Detection, Data Mining'
            ],
            'Task_EscalateToDirector' => [
                'name' => 'Executive Dashboard Escalation',
                'duration' => 90,
                'resources' => 1,
                'hourlyRate' => 95,
                'complexity' => 1.0,
                'skillLevel' => 'expert',
                'equipment' => 'Real-time Dashboard, Alerts'
            ],
            'Task_ProcessPayment' => [
                'name' => 'Automated Payment Processing',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 45,
                'complexity' => 0.8,
                'skillLevel' => 'intermediate',
                'equipment' => 'Instant Payment Gateway'
            ],
            'Task_NotifyCustomer' => [
                'name' => 'Multi-Channel Notification',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 10,
                'complexity' => 0.5,
                'skillLevel' => 'beginner',
                'equipment' => 'Omnichannel Communication Platform'
            ]
        ]
    ],
    'future' => [
        'steps' => [
            'StartEvent_ClaimSubmitted' => [
                'name' => 'Voice/Photo Instant Claim',
                'duration' => 2,
                'resources' => 1,
                'hourlyRate' => 0,
                'complexity' => 0.4,
                'skillLevel' => 'specialist',
                'equipment' => 'Voice AI, Computer Vision, IoT'
            ],
            'Task_InitialReview' => [
                'name' => 'AI Complete Auto-Review',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.6,
                'skillLevel' => 'specialist',
                'equipment' => 'Advanced AI, Predictive Analytics'
            ],
            'Task_ProcessMinorClaim' => [
                'name' => 'Instant Minor Claim Resolution',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.5,
                'skillLevel' => 'specialist',
                'equipment' => 'End-to-End AI Automation'
            ],
            'Task_VerifyDocuments' => [
                'name' => 'Blockchain Document Verification',
                'duration' => 10,
                'resources' => 1,
                'hourlyRate' => 20,
                'complexity' => 0.6,
                'skillLevel' => 'specialist',
                'equipment' => 'Blockchain, Digital Identity'
            ],
            'Task_RequestAdditionalDocs' => [
                'name' => 'Smart Document Request',
                'duration' => 3,
                'resources' => 1,
                'hourlyRate' => 15,
                'complexity' => 0.3,
                'skillLevel' => 'specialist',
                'equipment' => 'Predictive Document Engine'
            ],
            'Task_ScheduleInspection' => [
                'name' => 'Autonomous Inspection Dispatch',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 20,
                'complexity' => 0.4,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Scheduling, IoT Integration'
            ],
            'Task_ConductInspection' => [
                'name' => 'Drone/AR Autonomous Inspection',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 30,
                'complexity' => 0.8,
                'skillLevel' => 'specialist',
                'equipment' => 'Autonomous Drones, AR, AI Analysis'
            ],
            'Task_PrepareEstimate' => [
                'name' => 'AI Instant Estimate',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 25,
                'complexity' => 0.5,
                'skillLevel' => 'specialist',
                'equipment' => 'Real-time Market AI, Digital Twin'
            ],
            'Task_AutoApprove' => [
                'name' => 'Quantum-Speed Approval',
                'duration' => 1,
                'resources' => 1,
                'hourlyRate' => 5,
                'complexity' => 0.2,
                'skillLevel' => 'specialist',
                'equipment' => 'Quantum Computing, Neural Networks'
            ],
            'Task_ManagerReview' => [
                'name' => 'AI Executive Assistant Review',
                'duration' => 30,
                'resources' => 1,
                'hourlyRate' => 40,
                'complexity' => 0.8,
                'skillLevel' => 'specialist',
                'equipment' => 'Executive AI, Predictive Risk Models'
            ],
            'Task_Investigation' => [
                'name' => 'AI Fraud Detection Network',
                'duration' => 60,
                'resources' => 1,
                'hourlyRate' => 35,
                'complexity' => 1.0,
                'skillLevel' => 'specialist',
                'equipment' => 'Global AI Network, Pattern Recognition'
            ],
            'Task_EscalateToDirector' => [
                'name' => 'AI Executive Briefing',
                'duration' => 15,
                'resources' => 1,
                'hourlyRate' => 50,
                'complexity' => 0.6,
                'skillLevel' => 'specialist',
                'equipment' => 'AI Executive Assistant'
            ],
            'Task_ProcessPayment' => [
                'name' => 'Cryptocurrency/Instant Transfer',
                'duration' => 5,
                'resources' => 1,
                'hourlyRate' => 20,
                'complexity' => 0.4,
                'skillLevel' => 'specialist',
                'equipment' => 'Blockchain, Digital Currency'
            ],
            'Task_NotifyCustomer' => [
                'name' => 'Personalized AI Communication',
                'duration' => 2,
                'resources' => 1,
                'hourlyRate' => 5,
                'complexity' => 0.3,
                'skillLevel' => 'specialist',
                'equipment' => 'Personalized AI, Emotional Intelligence'
            ]
        ]
    ]
];

// Save complex simulation configuration
$stmt = $conn->prepare("
    INSERT INTO simulation_configs 
    (process_id, config_data, created_at, updated_at) 
    VALUES (?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
    config_data = VALUES(config_data), 
    updated_at = NOW()
");

$stmt->execute([$complexProcessId, json_encode($complexSimulationConfig)]);
echo "✅ Created advanced simulation configuration for process ID: {$complexProcessId}<br>";

echo "<h2>🎉 Complex Process Creation Complete!</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>🚀 What You Can Now Explore:</h3>";
echo "<ol>";
echo "<li><strong>Visual Builder:</strong> Load 'Insurance Claim Processing - Multi-Path with Lanes'</li>";
echo "<li><strong>See Complex Elements:</strong>";
echo "<ul>";
echo "<li>🏊 <strong>5 Swimlanes:</strong> Customer, Claims Agent, Investigator, Manager, Finance</li>";
echo "<li>🔀 <strong>6 Decision Gateways:</strong> Claim type, document completeness, amount thresholds, approvals</li>";
echo "<li>⚡ <strong>Parallel Processing:</strong> Simultaneous document verification and damage assessment</li>";
echo "<li>🔄 <strong>Loops:</strong> Document rework cycles</li>";
echo "<li>⏰ <strong>Timer Events:</strong> 5-day SLA with escalation</li>";
echo "</ul></li>";
echo "<li><strong>Simulation Scenarios:</strong>";
echo "<ul>";
echo "<li><strong>Current State:</strong> Traditional manual processes (~15-20 hours)</li>";
echo "<li><strong>Optimized:</strong> AI-assisted workflows (~8-12 hours)</li>";
echo "<li><strong>Future State:</strong> Full automation with IoT/Blockchain (~2-4 hours)</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<h3>📊 Process Complexity Comparison</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Feature</th><th>Simple Sequential</th><th>Complex Multi-Path</th></tr>";
echo "<tr><td>Decision Points</td><td>0</td><td>6 Gateways</td></tr>";
echo "<tr><td>Parallel Paths</td><td>0</td><td>2 Concurrent branches</td></tr>";
echo "<tr><td>Roles/Lanes</td><td>4 departments</td><td>5 specialized roles</td></tr>";
echo "<tr><td>Possible Routes</td><td>1 linear path</td><td>12+ different routing combinations</td></tr>";
echo "<tr><td>Timer Events</td><td>0</td><td>1 SLA escalation</td></tr>";
echo "<tr><td>Loop/Rework</td><td>0</td><td>Document rework cycle</td></tr>";
echo "<tr><td>Outcomes</td><td>1 (completed)</td><td>3 (approved/rejected/fraud)</td></tr>";
echo "</table>";

echo "<h3>🎯 Simulation Insights You'll See</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 10px 0;'>";
echo "<h4>🔍 Key Analytics:</h4>";
echo "<ul>";
echo "<li><strong>Path Analysis:</strong> Which routes are most common (minor vs major claims)</li>";
echo "<li><strong>Bottleneck Detection:</strong> Manager approval and investigation delays</li>";
echo "<li><strong>Resource Utilization:</strong> Investigator overload, inspector availability</li>";
echo "<li><strong>SLA Performance:</strong> How often escalations occur</li>";
echo "<li><strong>Decision Impact:</strong> Cost of fraud investigation vs risk</li>";
echo "<li><strong>Parallel Efficiency:</strong> Time savings from concurrent processing</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🛠️ Next Steps</h3>";
echo "<p>1. <strong>Visual Builder:</strong> <a href='visual_builder.php' target='_blank'>Load and explore the complex process</a></p>";
echo "<p>2. <strong>Simulation:</strong> <a href='simulation.php' target='_blank'>Run multi-scenario analysis</a></p>";
echo "<p>3. <strong>Dashboard:</strong> <a href='dashboard.php' target='_blank'>Compare simple vs complex processes</a></p>";

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
table {
    margin: 10px 0;
    font-size: 14px;
}
th, td {
    padding: 8px;
    text-align: left;
}
th {
    background: #f8f9fa;
}
</style>