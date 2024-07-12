<?php
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT image_path, title FROM submissions WHERE id = ? AND status = 'approved'");
    $stmt->execute([$id]);
    $submission = $stmt->fetch();
    
    if ($submission) {
        $file_path = 'assets/images/uploads/' . $submission['image_path'];
        
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $submission['title'] . '.webp"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

header('Location: index.php');
exit;