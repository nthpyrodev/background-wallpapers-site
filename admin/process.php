<?php

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $stmt = $pdo->prepare("UPDATE submissions SET status = ? WHERE id = ?");
        $stmt->execute([$action . 'd', $submission_id]);

        if ($action === 'reject') {
            $stmt = $pdo->prepare("SELECT image_path FROM submissions WHERE id = ?");
            $stmt->execute([$submission_id]);
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($submission && !empty($submission['image_path'])) {
                $imagePath = '../assets/images/uploads/' . $submission['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
            $stmt->execute([$submission_id]);
        }
    }

    header('Location: review.php');
    exit;
}

header('Location: review.php');
exit;
