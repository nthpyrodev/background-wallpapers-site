<?php

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}


$stmt = $pdo->query("SELECT * FROM submissions WHERE status = 'pending' ORDER BY created_at DESC");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($submissions)) {
    echo "<p>No pending submissions found.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Review Submissions</h2>
        <?php foreach ($submissions as $submission): ?>
            <div class="card mb-3">
    <div class="row g-0">
        <div class="col-md-4">
            <img src="../assets/images/uploads/<?php echo $submission['image_path']; ?>" class="img-fluid rounded-start" alt="<?php echo $submission['title']; ?>">
        </div>
        <div class="col-md-8">
            <div class="card-body">
                <h5 class="card-title"><?php echo $submission['title']; ?></h5>
                <p class="card-text">Type: <?php echo ucfirst($submission['wallpaper_type']); ?></p>
                <p class="card-text">Tags: <?php echo $submission['tags']; ?></p>
                <p class="card-text"><small class="text-muted">Submitted: <?php echo $submission['created_at']; ?></small></p>
                <form action="process.php" method="post" class="mt-3">
                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                </form>
            </div>
        </div>
    </div>
</div>
        <?php endforeach; ?>
    </div>
</body>
</html>