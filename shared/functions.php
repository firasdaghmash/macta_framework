<?php
// shared/functions.php - Common functions for the MACTA Framework

// Security function to prevent XSS
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../admin/login.php');
        exit;
    }
}

// Check if user is admin
function check_admin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../../admin/login.php');
        exit;
    }
}

// Generate breadcrumb navigation
function generate_breadcrumb($current_page, $module = null) {
    $breadcrumb = '<nav class="breadcrumb">';
    $breadcrumb .= '<a href="../../index.php">MACTA Framework</a>';
    
    if ($module) {
        $breadcrumb .= ' > <a href="../' . $module . '/index.php">' . get_module_name($module) . '</a>';
    }
    
    if ($current_page && $current_page !== 'index') {
        $breadcrumb .= ' > ' . ucwords(str_replace('_', ' ', $current_page));
    }
    
    $breadcrumb .= '</nav>';
    return $breadcrumb;
}

// Get module full name from letter
function get_module_name($letter) {
    $modules = [
        'M' => 'Process Modeling',
        'A' => 'Statistical Analysis',
        'C' => 'Customization',
        'T' => 'Training Program',
        'A2' => 'Assessment (Metrics)'
    ];
    
    return $modules[$letter] ?? 'Unknown Module';
}

// Get module color scheme
function get_module_colors($letter) {
    $colors = [
        'M' => ['primary' => '#ff6b35', 'secondary' => '#ff9a56'],
        'A' => ['primary' => '#ee5a52', 'secondary' => '#ff6b6b'],
        'C' => ['primary' => '#26a69a', 'secondary' => '#4ecdc4'],
        'T' => ['primary' => '#ffc107', 'secondary' => '#ffe066'],
        'A2' => ['primary' => '#4caf50', 'secondary' => '#95e1d3']
    ];
    
    return $colors[$letter] ?? ['primary' => '#6c757d', 'secondary' => '#adb5bd'];
}

// Format date for display
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Generate random ID
function generate_id($length = 8) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', $length)), 0, $length);
}

// Log activity
function log_activity($user_id, $action, $details = '') {
    global $conn;
    
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

// Get user information
function get_user_info($user_id) {
    global $conn;
    
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user info error: " . $e->getMessage());
        return false;
    }
}

// Check if installation is complete
function check_installation() {
    if (!file_exists('../../config/installed.lock')) {
        header('Location: ../../install.php');
        exit;
    }
}

// Generate module header
function generate_module_header($module_letter, $title, $description) {
    $colors = get_module_colors($module_letter);
    
    $icons = [
        'M' => '📊',
        'A' => '📈',
        'C' => '⚙️',
        'T' => '🎓',
        'A2' => '📊'
    ];
    
    $icon = $icons[$module_letter] ?? '📋';
    
    return "
    <div class='header' style='background: linear-gradient(135deg, {$colors['secondary']} 0%, {$colors['primary']} 100%);'>
        <div class='module-icon'>{$icon}</div>
        <h1 class='module-title'>{$title}</h1>
        <p class='module-description'>{$description}</p>
    </div>";
}

// Generate notification HTML
function show_notification($message, $type = 'success') {
    $class = $type === 'error' ? 'notification-error' : 'notification-success';
    return "<div class='{$class}' id='notification'>{$message}</div>";
}

// Validate email format
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate secure password hash
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Get project statistics
function get_project_stats($project_id = null) {
    global $conn;
    
    if (!$conn) return [];
    
    try {
        $stats = [];
        
        if ($project_id) {
            $where = "WHERE project_id = ?";
            $params = [$project_id];
        } else {
            $where = "";
            $params = [];
        }
        
        // Get total projects
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects " . ($project_id ? "WHERE id = ?" : ""));
        $stmt->execute($project_id ? [$project_id] : []);
        $stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get process models count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM process_models $where");
        $stmt->execute($params);
        $stats['process_models'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get training programs count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM training_programs $where");
        $stmt->execute($params);
        $stats['training_programs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get metrics count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM metrics $where");
        $stmt->execute($params);
        $stats['metrics'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Get stats error: " . $e->getMessage());
        return [];
    }
}

// File upload handler
function handle_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size: ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types)];
    }
    
    $upload_dir = '../../uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = generate_id() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

?>