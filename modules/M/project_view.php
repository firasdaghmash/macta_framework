<?php
// modules/M/project_view.php - Detailed Project View
session_start();

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

// Get project ID from URL
$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Get project details with statistics
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username as client_name, u.email as client_email
        FROM projects p
        LEFT JOIN users u ON p.client_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header('Location: projects.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error loading project: " . $e->getMessage());
    header('Location: projects.php');
    exit;
}

// Get process models for this project
try {
    $stmt = $conn->prepare("
        SELECT id, name, description, created_at, updated_at,
               CHAR_LENGTH(model_data) as size_bytes
        FROM process_models 
        WHERE project_id = ? 
        ORDER BY updated_at DESC, created_at DESC
    ");
    $stmt->execute([$project_id]);
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading processes: " . $e->getMessage());
    $processes = [];
}

// Get job descriptions for this project
try {
    $stmt = $conn->prepare("
        SELECT id, title, description, created_at, updated_at
        FROM job_descriptions 
        WHERE project_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$project_id]);
    $job_descriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading job descriptions: " . $e->getMessage());
    $job_descriptions = [];
}

// Get training programs for this project
try {
    $stmt = $conn->prepare("
        SELECT id, name, description, status, created_at, updated_at
        FROM training_programs 
        WHERE project_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$project_id]);
    $training_programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading training programs: " . $e->getMessage());
    $training_programs = [];
}

// Get metrics for this project
try {
    $stmt = $conn->prepare("
        SELECT id, metric_name, metric_value, target_value, category, measurement_date, created_at
        FROM metrics 
        WHERE project_id = ? 
        ORDER BY measurement_date DESC, created_at DESC
    ");
    $stmt->execute([$project_id]);
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading metrics: " . $e->getMessage());
    $metrics = [];
}

// Get customer feedback for this project
try {
    $stmt = $conn->prepare("
        SELECT id, feedback_text, satisfaction_score, feedback_date, status, created_at
        FROM customer_feedback 
        WHERE project_id = ? 
        ORDER BY feedback_date DESC, created_at DESC
    ");
    $stmt->execute([$project_id]);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading feedback: " . $e->getMessage());
    $feedback = [];
}

// Calculate statistics
$stats = [
    'processes' => count($processes),
    'jobs' => count($job_descriptions),
    'training' => count($training_programs),
    'metrics' => count($metrics),
    'feedback' => count($feedback)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Project Details</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: bold;
        }

        .breadcrumb {
            opacity: 0.9;
            margin-top: 5px;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .project-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .project-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meta-icon {
            font-size: 20px;
            color: #ff6b35;
        }

        .meta-content {
            flex: 1;
        }

        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }

        .project-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #ff6b35;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #ff6b35;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .content-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-count {
            background: #ff6b35;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .section-content {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .item-list {
            list-style: none;
        }

        .item-list li {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .item-list li:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .item-description {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        .item-date {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            margin-left: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #ff5722;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .metric-info {
            flex: 1;
        }

        .metric-name {
            font-weight: 500;
            color: #333;
        }

        .metric-category {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .metric-values {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .metric-value {
            text-align: center;
        }

        .metric-number {
            font-weight: bold;
            color: #ff6b35;
        }

        .metric-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .project-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📋 <?php echo htmlspecialchars($project['name']); ?></h1>
                <div class="breadcrumb">
                    <a href="../../index.php">MACTA Framework</a> > 
                    <a href="index.php">Modeling</a> > 
                    <a href="projects.php">Projects</a> > 
                    Project Details
                </div>
            </div>
            <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
        </div>
    </div>

    <div class="container">
        <!-- Project Header -->
        <div class="project-header">
            <div class="project-title"><?php echo htmlspecialchars($project['name']); ?></div>
            
            <?php if ($project['description']): ?>
                <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 15px 0;">
                    <?php echo htmlspecialchars($project['description']); ?>
                </p>
            <?php endif; ?>

            <div class="project-meta">
                <div class="meta-item">
                    <div class="meta-icon">👤</div>
                    <div class="meta-content">
                        <div class="meta-label">Client</div>
                        <div class="meta-value">
                            <?php echo htmlspecialchars($project['client_name'] ?: 'No client assigned'); ?>
                            <?php if ($project['client_email']): ?>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($project['client_email']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="meta-item">
                    <div class="meta-icon">📊</div>
                    <div class="meta-content">
                        <div class="meta-label">Status</div>
                        <div class="meta-value">
                            <span class="project-status status-<?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="meta-item">
                    <div class="meta-icon">📅</div>
                    <div class="meta-content">
                        <div class="meta-label">Created</div>
                        <div class="meta-value"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                    </div>
                </div>

                <div class="meta-item">
                    <div class="meta-icon">🔄</div>
                    <div class="meta-content">
                        <div class="meta-label">Last Updated</div>
                        <div class="meta-value"><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['processes']; ?></span>
                <span class="stat-label">Process Models</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['jobs']; ?></span>
                <span class="stat-label">Job Descriptions</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['training']; ?></span>
                <span class="stat-label">Training Programs</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['metrics']; ?></span>
                <span class="stat-label">Metrics</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['feedback']; ?></span>
                <span class="stat-label">Customer Feedback</span>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-grid">
            <!-- Process Models -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        📊 Process Models
                        <span class="section-count"><?php echo count($processes); ?></span>
                    </div>
                    <a href="visual_builder.php" class="btn btn-sm">+ Add Process</a>
                </div>
                <div class="section-content">
                    <?php if (empty($processes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📊</div>
                            <p>No process models yet</p>
                            <a href="visual_builder.php" class="btn btn-sm" style="margin-top: 10px;">Create First Process</a>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($processes as $process): ?>
                                <li>
                                    <div class="item-info">
                                        <div class="item-title"><?php echo htmlspecialchars($process['name']); ?></div>
                                        <?php if ($process['description']): ?>
                                            <div class="item-description"><?php echo htmlspecialchars(substr($process['description'], 0, 100)); ?><?php echo strlen($process['description']) > 100 ? '...' : ''; ?></div>
                                        <?php endif; ?>
                                        <div class="item-description">
                                            Size: <?php echo number_format($process['size_bytes'] / 1024, 1); ?> KB
                                        </div>
                                    </div>
                                    <div class="item-date"><?php echo date('M d', strtotime($process['updated_at'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Job Descriptions -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        📝 Job Descriptions
                        <span class="section-count"><?php echo count($job_descriptions); ?></span>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (empty($job_descriptions)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <p>No job descriptions yet</p>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($job_descriptions as $job): ?>
                                <li>
                                    <div class="item-info">
                                        <div class="item-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <?php if ($job['description']): ?>
                                            <div class="item-description"><?php echo htmlspecialchars(substr($job['description'], 0, 100)); ?><?php echo strlen($job['description']) > 100 ? '...' : ''; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-date"><?php echo date('M d', strtotime($job['created_at'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Training Programs -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        🎓 Training Programs
                        <span class="section-count"><?php echo count($training_programs); ?></span>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (empty($training_programs)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🎓</div>
                            <p>No training programs yet</p>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($training_programs as $training): ?>
                                <li>
                                    <div class="item-info">
                                        <div class="item-title"><?php echo htmlspecialchars($training['name']); ?></div>
                                        <?php if ($training['description']): ?>
                                            <div class="item-description"><?php echo htmlspecialchars(substr($training['description'], 0, 100)); ?><?php echo strlen($training['description']) > 100 ? '...' : ''; ?></div>
                                        <?php endif; ?>
                                        <div class="item-description">
                                            Status: <strong><?php echo ucfirst($training['status']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="item-date"><?php echo date('M d', strtotime($training['created_at'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Metrics -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        📈 Metrics
                        <span class="section-count"><?php echo count($metrics); ?></span>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (empty($metrics)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📈</div>
                            <p>No metrics data yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($metrics as $metric): ?>
                            <div class="metric-item">
                                <div class="metric-info">
                                    <div class="metric-name"><?php echo htmlspecialchars($metric['metric_name']); ?></div>
                                    <div class="metric-category"><?php echo htmlspecialchars($metric['category']); ?></div>
                                </div>
                                <div class="metric-values">
                                    <div class="metric-value">
                                        <div class="metric-number"><?php echo $metric['metric_value']; ?></div>
                                        <div class="metric-label">Current</div>
                                    </div>
                                    <?php if ($metric['target_value']): ?>
                                        <div class="metric-value">
                                            <div class="metric-number"><?php echo $metric['target_value']; ?></div>
                                            <div class="metric-label">Target</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Customer Feedback -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        💬 Customer Feedback
                        <span class="section-count"><?php echo count($feedback); ?></span>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (empty($feedback)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">💬</div>
                            <p>No customer feedback yet</p>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($feedback as $fb): ?>
                                <li>
                                    <div class="item-info">
                                        <div class="item-title">
                                            Rating: <?php echo $fb['satisfaction_score']; ?>/10
                                            <span style="color: #666; font-weight: normal;">(<?php echo ucfirst($fb['status']); ?>)</span>
                                        </div>
                                        <?php if ($fb['feedback_text']): ?>
                                            <div class="item-description"><?php echo htmlspecialchars(substr($fb['feedback_text'], 0, 100)); ?><?php echo strlen($fb['feedback_text']) > 100 ? '...' : ''; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-date"><?php echo date('M d', strtotime($fb['feedback_date'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any interactive functionality here if needed
        console.log('Project view loaded for project ID: <?php echo $project_id; ?>');
    </script>
</body>
</html>