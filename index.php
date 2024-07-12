<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

$wallpaper_type = isset($_GET['wallpaper_type']) ? $_GET['wallpaper_type'] : '';
$tags = isset($_GET['tags']) ? $_GET['tags'] : [];

$where_clauses = ["status = 'approved'"];
$params = [];

if ($wallpaper_type) {
    $where_clauses[] = "wallpaper_type = :wallpaper_type";
    $params[':wallpaper_type'] = $wallpaper_type;
}

if (!empty($tags)) {
    $tag_clauses = [];
    foreach ($tags as $index => $tag) {
        $param_name = ":tag" . $index;
        $tag_clauses[] = "FIND_IN_SET($param_name, tags)";
        $params[$param_name] = $tag;
    }
    $where_clauses[] = '(' . implode(' OR ', $tag_clauses) . ')';
}

$where_clause = implode(' AND ', $where_clauses);

switch ($sort) {
    case 'most_voted':
        $order_by = "ORDER BY (SELECT COUNT(*) FROM ip_votes WHERE ip_votes.submission_id = submissions.id AND vote_type = 'up') DESC";
        break;
    case 'most_disliked':
        $order_by = "ORDER BY (SELECT COUNT(*) FROM ip_votes WHERE ip_votes.submission_id = submissions.id AND vote_type = 'down') DESC";
        break;
    case 'newest':
    default:
        $order_by = "ORDER BY created_at DESC";
        break;
}

$offset = ($page - 1) * $per_page;

$query = "SELECT submissions.*, 
    (SELECT COUNT(*) FROM ip_votes WHERE ip_votes.submission_id = submissions.id AND vote_type = 'up') as upvotes,
    (SELECT COUNT(*) FROM ip_votes WHERE ip_votes.submission_id = submissions.id AND vote_type = 'down') as downvotes
    FROM submissions 
    WHERE $where_clause
    $order_by 
    LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

foreach ($params as $param_name => $param_value) {
    $stmt->bindValue($param_name, $param_value);
}

$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_query = "SELECT COUNT(*) FROM submissions WHERE $where_clause";
$total_stmt = $pdo->prepare($total_query);

foreach ($params as $param_name => $param_value) {
    $total_stmt->bindValue($param_name, $param_value);
}

$total_stmt->execute();
$total_submissions = $total_stmt->fetchColumn();
$total_pages = ceil($total_submissions / $per_page);

$tag_stmt = $pdo->query("SELECT DISTINCT tags FROM submissions WHERE status = 'approved'");
$all_tags = [];
while ($row = $tag_stmt->fetch(PDO::FETCH_ASSOC)) {
    $tags_array = explode(',', $row['tags']);
    $all_tags = array_merge($all_tags, $tags_array);
}
$all_tags = array_unique($all_tags);
sort($all_tags);

include 'templates/header.php';
?>

<div class="container mt-5">
    <h1>Wallpapers</h1>
    
    <form action="" method="get" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="wallpaper_type" class="form-label">Wallpaper Type</label>
                <select name="wallpaper_type" id="wallpaper_type" class="form-select">
                    <option value="">All</option>
                    <option value="phone" <?php echo $wallpaper_type == 'phone' ? 'selected' : ''; ?>>Phone</option>
                    <option value="desktop" <?php echo $wallpaper_type == 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tags</label>
                <div>
                    <?php foreach ($all_tags as $tag): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag; ?>" id="tag_<?php echo $tag; ?>" <?php echo in_array($tag, $tags) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tag_<?php echo $tag; ?>"><?php echo ucfirst($tag); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3">
                <label for="sort" class="form-label">Sort By</label>
                <select name="sort" id="sort" class="form-select">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="most_voted" <?php echo $sort == 'most_voted' ? 'selected' : ''; ?>>Most Voted</option>
                    <option value="most_disliked" <?php echo $sort == 'most_disliked' ? 'selected' : ''; ?>>Most Disliked</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Apply Filters</button>
    </form>

    <div class="row">
        <?php foreach ($submissions as $submission): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="assets/images/uploads/<?php echo $submission['image_path']; ?>" class="card-img-top" alt="<?php echo $submission['title']; ?>" loading="lazy">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $submission['title']; ?></h5>
                        <p class="card-text">Type: <?php echo ucfirst($submission['wallpaper_type']); ?></p>
                        <p class="card-text">Tags: <?php echo $submission['tags']; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-sm btn-outline-primary vote-btn" data-submission-id="<?php echo $submission['id']; ?>" data-vote-type="up">👍 <?php echo $submission['upvotes']; ?></button>
                                <button class="btn btn-sm btn-outline-danger vote-btn" data-submission-id="<?php echo $submission['id']; ?>" data-vote-type="down">👎 <?php echo $submission['downvotes']; ?></button>
                            </div>
                            <a href="download.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-success">Download</a>
                        </div>
                        <small class="text-muted"><?php echo formatDate($submission['created_at']); ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&wallpaper_type=<?php echo $wallpaper_type; ?><?php echo !empty($tags) ? '&' . http_build_query(['tags' => $tags]) : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php include 'templates/footer.php'; ?>
