<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $vote_type = $_POST['vote_type'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("SELECT * FROM ip_votes WHERE submission_id = ? AND ip_address = ?");
    $stmt->execute([$submission_id, $ip_address]);
    $existing_vote = $stmt->fetch();

    if ($existing_vote) {
        if ($existing_vote['vote_type'] !== $vote_type) {
            $stmt = $pdo->prepare("UPDATE ip_votes SET vote_type = ? WHERE id = ?");
            $stmt->execute([$vote_type, $existing_vote['id']]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO ip_votes (submission_id, ip_address, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$submission_id, $ip_address, $vote_type]);
    }

    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM ip_votes WHERE submission_id = ? AND vote_type = 'up') as upvotes,
        (SELECT COUNT(*) FROM ip_votes WHERE submission_id = ? AND vote_type = 'down') as downvotes");
    $stmt->execute([$submission_id, $submission_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($result);
    exit;
}

header('Location: index.php');
exit;