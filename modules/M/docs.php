<?php
require_once '../../config/database.php';
require_once '../../shared/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get process data for documentation
$processes = [];
try {
    $stmt = $conn->query("SELECT p.*, c.name as client_name FROM processes p LEFT JOIN clients c ON p.client_id = c.id WHERE p.status = 'active' ORDER BY p.updated_at DESC");
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading processes: " . $e->getMessage();
}

// Get recent documentation activities
$documents = [];
try {
    $stmt = $conn->query("SELECT * FROM simulation_results WHERE simulation_type = 'documentation' ORDER BY created_at DESC LIMIT 10");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet, continue silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation & Compliance - MACTA Modeling</title>
    <link rel="stylesheet" href="../../shared/styles.css">
    <style>
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .doc-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .compliance-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .compliance-feature {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .compliance-feature:hover {
            border-color: #2196F3;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.1);
        }
        
        .doc-icon {
            font-size: 48px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .compliance-status {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .status-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #2196F3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-indicator {
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .status-compliant {
            background: #4CAF50;
        }
        
        .status-partial {
            background: #FF9800;
        }
        
        .status-non-compliant {
            background: #F44336;
        }
        
        .doc-template {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .btn-doc {
            background: linear-gradient(45deg, #2196F3, #21CBF3);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-doc:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .doc-tab {
            display: none;
        }
        
        .doc-tab.active {
            display: block;
        }
        
        .tab-buttons {
            display: flex;
            background: #f1f1f1;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: #2196F3;
            color: white;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        
        .export-btn {
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: #2196F3;
            color: white;
        }
        
        .version-control {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="module-header">
            <div class="header-content">
                <div class="module-icon">📚</div>
                <div>
                    <h1>Documentation & Compliance</h1>
                    <p>Generate comprehensive documentation and ensure regulatory compliance</p>
                </div>
            </div>
            <a href="index.php" class="btn-back">← Back to Modeling</a>
        </div>

        <!-- Navigation Tabs -->
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('documentation')">📝 Documentation</button>
            <button class="tab-button" onclick="showTab('compliance')">✅ Compliance</button>
            <button class="tab-button" onclick="showTab('templates')">📄 Templates</button>
            <button class="tab-button" onclick="showTab('reports')">📊 Reports</button>
        </div>

        <!-- Documentation Tab -->
        <div id="documentation" class="doc-tab active">
            <!-- Documentation Features -->
            <div class="compliance-features">
                <div class="compliance-feature">
                    <div class="doc-icon">📋</div>
                    <h3>Process Documentation</h3>
                    <p>Generate comprehensive process documentation including SOPs, workflow diagrams, and step-by-step procedures.</p>
                    <button class="btn-doc" onclick="generateProcessDocs()">Generate Documentation</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">📖</div>
                    <h3>User Manuals</h3>
                    <p>Create detailed user manuals and training materials with screenshots, examples, and best practices.</p>
                    <button class="btn-doc" onclick="createUserManuals()">Create Manuals</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">🔄</div>
                    <h3>Version Control</h3>
                    <p>Track document changes, maintain version history, and manage approval workflows for all documentation.</p>
                    <button class="btn-doc" onclick="manageVersions()">Manage Versions</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">🌐</div>
                    <h3>Multi-format Export</h3>
                    <p>Export documentation to PDF, Word, HTML, Wikipedia format, and other industry-standard formats.</p>
                    <button class="btn-doc" onclick="showExportOptions()">Export Options</button>
                </div>
            </div>

            <!-- Process Selection for Documentation -->
            <div class="doc-template">
                <h3>📄 Generate Process Documentation</h3>
                <select id="docProcessSelect" class="form-control" style="width: 100%; padding: 10px; margin-bottom: 15px;">
                    <option value="">Select a process to document...</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['id'] ?>"><?= htmlspecialchars($process['name']) ?> (<?= htmlspecialchars($process['client_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="btn-doc" onclick="generateSOP()">📋 Standard Operating Procedure</button>
                    <button class="btn-doc" onclick="generateWorkflow()">🔄 Workflow Diagram</button>
                    <button class="btn-doc" onclick="generateTraining()">🎓 Training Materials</button>
                </div>
                
                <div class="export-options" id="exportOptions" style="display: none;">
                    <div class="export-btn" onclick="exportFormat('pdf')">📄 PDF</div>
                    <div class="export-btn" onclick="exportFormat('docx')">📝 Word</div>
                    <div class="export-btn" onclick="exportFormat('html')">🌐 HTML</div>
                    <div class="export-btn" onclick="exportFormat('wiki')">📚 Wiki</div>
                    <div class="export-btn" onclick="exportFormat('markdown')">✍️ Markdown</div>
                </div>
            </div>
        </div>

        <!-- Compliance Tab -->
        <div id="compliance" class="doc-tab">
            <h3>✅ Compliance Dashboard</h3>
            
            <!-- Compliance Status Overview -->
            <div class="compliance-status">
                <h4>📊 Compliance Status Overview</h4>
                <div class="status-card">
                    <div>
                        <strong>ISO 9001:2015 Quality Management</strong>
                        <p>Quality management system requirements</p>
                    </div>
                    <div class="status-indicator status-compliant">COMPLIANT</div>
                </div>
                
                <div class="status-card">
                    <div>
                        <strong>SOX Compliance</strong>
                        <p>Sarbanes-Oxley financial controls</p>
                    </div>
                    <div class="status-indicator status-partial">PARTIAL</div>
                </div>
                
                <div class="status-card">
                    <div>
                        <strong>GDPR Data Protection</strong>
                        <p>General Data Protection Regulation</p>
                    </div>
                    <div class="status-indicator status-compliant">COMPLIANT</div>
                </div>
                
                <div class="status-card">
                    <div>
                        <strong>HIPAA Healthcare</strong>
                        <p>Health Insurance Portability and Accountability</p>
                    </div>
                    <div class="status-indicator status-non-compliant">NON-COMPLIANT</div>
                </div>
            </div>

            <!-- Compliance Features -->
            <div class="compliance-features">
                <div class="compliance-feature">
                    <div class="doc-icon">🔍</div>
                    <h3>Compliance Audit</h3>
                    <p>Automated compliance checking against industry standards and regulations with detailed gap analysis.</p>
                    <button class="btn-doc" onclick="runComplianceAudit()">Run Audit</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">📋</div>
                    <h3>Regulatory Mapping</h3>
                    <p>Map your processes to regulatory requirements and identify compliance gaps automatically.</p>
                    <button class="btn-doc" onclick="mapRegulations()">Map Regulations</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">⚠️</div>
                    <h3>Risk Assessment</h3>
                    <p>Identify compliance risks and generate mitigation strategies with priority recommendations.</p>
                    <button class="btn-doc" onclick="assessComplianceRisk()">Assess Risks</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">📈</div>
                    <h3>Compliance Tracking</h3>
                    <p>Monitor compliance metrics, track improvements, and generate compliance dashboards.</p>
                    <button class="btn-doc" onclick="trackCompliance()">Track Progress</button>
                </div>
            </div>
        </div>

        <!-- Templates Tab -->
        <div id="templates" class="doc-tab">
            <h3>📄 Documentation Templates</h3>
            
            <div class="doc-grid">
                <div class="doc-card">
                    <h4>📋 Standard Operating Procedure (SOP)</h4>
                    <p>Comprehensive SOP template with step-by-step procedures, responsibilities, and controls</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useSopTemplate()">Use Template</button>
                </div>
                
                <div class="doc-card">
                    <h4>🔄 Process Flow Document</h4>
                    <p>Visual process documentation with flowcharts, decision points, and stakeholder roles</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useFlowTemplate()">Use Template</button>
                </div>
                
                <div class="doc-card">
                    <h4>📊 Compliance Report</h4>
                    <p>Structured compliance reporting template with metrics, findings, and recommendations</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useComplianceTemplate()">Use Template</button>
                </div>
                
                <div class="doc-card">
                    <h4>🎓 Training Manual</h4>
                    <p>Comprehensive training documentation with learning objectives and assessments</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useTrainingTemplate()">Use Template</button>
                </div>
                
                <div class="doc-card">
                    <h4>📝 Work Instruction</h4>
                    <p>Detailed work instructions with screenshots, tips, and troubleshooting guides</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useWorkTemplate()">Use Template</button>
                </div>
                
                <div class="doc-card">
                    <h4>🔍 Audit Checklist</h4>
                    <p>Comprehensive audit checklist template with compliance criteria and evaluation forms</p>
                    <button class="btn-doc" style="background: rgba(255,255,255,0.2);" onclick="useAuditTemplate()">Use Template</button>
                </div>
            </div>

            <!-- Custom Template Creator -->
            <div class="doc-template">
                <h4>🎨 Create Custom Template</h4>
                <p>Build your own documentation template with custom fields, formatting, and branding.</p>
                <button class="btn-doc" onclick="createCustomTemplate()">Create Custom Template</button>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="doc-tab">
            <h3>📊 Documentation & Compliance Reports</h3>
            
            <div class="compliance-features">
                <div class="compliance-feature">
                    <div class="doc-icon">📈</div>
                    <h3>Executive Summary</h3>
                    <p>High-level overview of documentation coverage and compliance status for leadership.</p>
                    <button class="btn-doc" onclick="generateExecutiveReport()">Generate Report</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">📋</div>
                    <h3>Detailed Compliance Report</h3>
                    <p>Comprehensive compliance analysis with gap identification and remediation plans.</p>
                    <button class="btn-doc" onclick="generateDetailedReport()">Generate Report</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">🔄</div>
                    <h3>Process Documentation Status</h3>
                    <p>Status report on documentation coverage across all processes and departments.</p>
                    <button class="btn-doc" onclick="generateDocStatusReport()">Generate Report</button>
                </div>
                
                <div class="compliance-feature">
                    <div class="doc-icon">📊</div>
                    <h3>Audit Trail Report</h3>
                    <p>Complete audit trail of document changes, approvals, and compliance activities.</p>
                    <button class="btn-doc" onclick="generateAuditReport()">Generate Report</button>
                </div>
            </div>

            <!-- Report Configuration -->
            <div class="doc-template">
                <h4>⚙️ Report Configuration</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                    <div>
                        <label><strong>Report Period:</strong></label>
                        <select class="form-control">
                            <option>Last 30 days</option>
                            <option>Last Quarter</option>
                            <option>Last Year</option>
                            <option>Custom Range</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>Include Sections:</strong></label>
                        <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                            <label><input type="checkbox" checked> Compliance Status</label>
                            <label><input type="checkbox" checked> Risk Assessment</label>
                            <label><input type="checkbox" checked> Documentation Coverage</label>
                            <label><input type="checkbox"> Detailed Findings</label>
                        </div>
                    </div>
                </div>
                <button class="btn-doc" onclick="generateCustomReport()">Generate Custom Report</button>
            </div>
        </div>

        <!-- Recent Documents -->
        <?php if (!empty($documents)): ?>
        <div class="recent-activity">
            <h3>🕒 Recent Documentation Activity</h3>
            <?php foreach ($documents as $doc): ?>
                <div class="status-card">
                    <div>
                        <h4>Document Generated</h4>
                        <p>Created on <?= date('M j, Y', strtotime($doc['created_at'])) ?></p>
                        <small><?= substr(htmlspecialchars($doc['results'] ?? 'Document generated'), 0, 100) ?>...</small>
                    </div>
                    <button class="btn-doc" style="padding: 5px 10px; font-size: 12px;">View</button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Version Control Panel -->
        <div class="version-control" id="versionControl" style="display: none;">
            <h4>🔄 Document Version Control</h4>
            <p>Track changes, manage approvals, and maintain document history.</p>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button class="btn-doc" onclick="viewVersionHistory()">View History</button>
                <button class="btn-doc" onclick="approveDocument()">Approve Document</button>
                <button class="btn-doc" onclick="revertVersion()">Revert to Previous</button>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.doc-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Hide all tab buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function generateProcessDocs() {
            alert('📋 Generating comprehensive process documentation with SOPs, workflows, and procedures...');
        }

        function createUserManuals() {
            alert('📖 Creating user manuals with step-by-step instructions and visual guides...');
        }

        function manageVersions() {
            const versionControl = document.getElementById('versionControl');
            versionControl.style.display = versionControl.style.display === 'none' ? 'block' : 'none';
        }

        function showExportOptions() {
            const exportOptions = document.getElementById('exportOptions');
            exportOptions.style.display = exportOptions.style.display === 'none' ? 'block' : 'none';
        }

        function generateSOP() {
            const processId = document.getElementById('docProcessSelect').value;
            if (!processId) {
                alert('Please select a process to generate SOP documentation.');
                return;
            }
            alert('📋 Generating Standard Operating Procedure for selected process...');
        }

        function generateWorkflow() {
            const processId = document.getElementById('docProcessSelect').value;
            if (!processId) {
                alert('Please select a process to generate workflow documentation.');
                return;
            }
            alert('🔄 Generating workflow diagram and process flow documentation...');
        }

        function generateTraining() {
            const processId = document.getElementById('docProcessSelect').value;
            if (!processId) {
                alert('Please select a process to generate training materials.');
                return;
            }
            alert('🎓 Generating comprehensive training materials and learning objectives...');
        }

        function exportFormat(format) {
            alert('📤 Exporting documentation in ' + format.toUpperCase() + ' format...');
        }

        function runComplianceAudit() {
            alert('🔍 Running comprehensive compliance audit against industry standards...');
        }

        function mapRegulations() {
            alert('📋 Mapping processes to regulatory requirements and identifying compliance gaps...');
        }

        function assessComplianceRisk() {
            alert('⚠️ Assessing compliance risks and generating mitigation strategies...');
        }

        function trackCompliance() {
            alert('📈 Tracking compliance metrics and generating progress dashboard...');
        }

        function useSopTemplate() {
            alert('📋 Loading Standard Operating Procedure template for customization...');
        }

        function useFlowTemplate() {
            alert('🔄 Loading Process Flow Document template with visual elements...');
        }

        function useComplianceTemplate() {
            alert('📊 Loading Compliance Report template with regulatory frameworks...');
        }

        function useTrainingTemplate() {
            alert('🎓 Loading Training Manual template with learning objectives...');
        }

        function useWorkTemplate() {
            alert('📝 Loading Work Instruction template with detailed steps...');
        }

        function useAuditTemplate() {
            alert('🔍 Loading Audit Checklist template with compliance criteria...');
        }

        function createCustomTemplate() {
            alert('🎨 Opening custom template builder with drag-and-drop interface...');
        }

        function generateExecutiveReport() {
            alert('📈 Generating executive summary report for leadership review...');
        }

        function generateDetailedReport() {
            alert('📋 Generating detailed compliance report with gap analysis...');
        }

        function generateDocStatusReport() {
            alert('🔄 Generating documentation status report across all processes...');
        }

        function generateAuditReport() {
            alert('📊 Generating audit trail report with complete change history...');
        }

        function generateCustomReport() {
            alert('⚙️ Generating custom report with selected configuration options...');
        }

        function viewVersionHistory() {
            alert('🔄 Displaying document version history with change tracking...');
        }

        function approveDocument() {
            alert('✅ Document approved and marked as current version...');
        }

        function revertVersion() {
            alert('↩️ Reverting to previous document version...');
        }
    </script>
</body>
</html>