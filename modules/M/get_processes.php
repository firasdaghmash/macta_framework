<?php
// modules/M/get_processes.php - Helper file to get processes for a project
header('Content-Type: application/json; charset=utf-8');

try {
    if (file_exists('../../config/config.php')) {
        require_once '../../config/config.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        
        $project_id = isset($_GET['project_id']) ? $_GET['project_id'] : '';
        
        if ($project_id) {
            $stmt = $pdo->prepare("SELECT id, name FROM process_models WHERE project_id = ? ORDER BY name");
            $stmt->execute(array($project_id));
            $processes = $stmt->fetchAll();
            
            echo json_encode($processes);
        } else {
            echo json_encode(array());
        }
        
    } else {
        echo json_encode(array('error' => 'Database configuration not found'));
    }
    
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>