<?php
session_start();

$rate_limit_time = 60;
$max_requests = 2;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['upload_count'])) {
        $_SESSION['upload_count'] = 0;
        $_SESSION['first_request_time'] = time();
    }

    if (time() - $_SESSION['first_request_time'] > $rate_limit_time) {
        $_SESSION['upload_count'] = 0;
        $_SESSION['first_request_time'] = time();
    }

    $_SESSION['upload_count']++;

    if ($_SESSION['upload_count'] > $max_requests) {
        $error = "Not calling you a bot, but you have been ratelimited. Please try again in a bit.";
    } else {
        $title = trim($_POST['title']);
        $wallpaper_type = trim($_POST['wallpaper_type']);
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $image = $_FILES['image'];

        if (empty($title) || empty($wallpaper_type) || empty($tags) || empty($image)) {
            $error = "All fields are required.";
        } else {
            if (!in_array($wallpaper_type, ['phone', 'desktop'])) {
                $error = "Invalid wallpaper type.";
            }

            if (empty($tags)) {
                $error = "At least one tag is required.";
            }

            $allowed_types = ['image/png', 'image/jpeg', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime_type = finfo_file($finfo, $image['tmp_name']);
            finfo_close($finfo);

            if (!in_array($detected_mime_type, $allowed_types)) {
                $error = "Invalid file type. Only PNG, JPEG, and WebP are allowed.";
            } else {
                if ($image['size'] > 10 * 1024 * 1024) {
                    $error = "File size should be less than 10MB.";
                } else {
                    $filename = uniqid() . '.webp';
                    $upload_path = 'assets/images/uploads/' . $filename;

                    $webp_image = convertToWebP($image['tmp_name'], $detected_mime_type);
                    if (!$webp_image) {
                        $error = "Failed to process the image. Please make sure it's a valid image file.";
                    } else {
                        if (!imagewebp($webp_image, $upload_path, 80)) {
                            $error = "Failed to save the image.";
                        } else {
                            imagedestroy($webp_image);

                            $stmt = $pdo->prepare("INSERT INTO submissions (title, wallpaper_type, tags, image_path, status) VALUES (:title, :wallpaper_type, :tags, :image_path, 'pending')");
                            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                            $stmt->bindValue(':wallpaper_type', $wallpaper_type, PDO::PARAM_STR);
                            $stmt->bindValue(':tags', implode(',', $tags), PDO::PARAM_STR);
                            $stmt->bindValue(':image_path', $filename, PDO::PARAM_STR);

                            if ($stmt->execute()) {
                                $success = "Image uploaded successfully! It will be reviewed by an admin before appearing on the site.";
                            } else {
                                $error = "Failed to save the image to the database.";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<form action="upload.php" method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Wallpaper Type</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="wallpaper_type" id="phone" value="phone" required>
            <label class="form-check-label" for="phone">Phone</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="wallpaper_type" id="desktop" value="desktop" required>
            <label class="form-check-label" for="desktop">Desktop</label>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Tags (select multiple)</label>
        <?php
        $categories = [
            'Abstract', 'Animals', 'Anime', 'Architecture', 'Art', 'Cars', 'City', 
            'Dark', 'Fantasy', 'Flowers', 'Food', 'Funny', 'Games', 'Geometric', 
            'Gradient', 'Holiday', 'Landscape', 'Minimalist', 'Movies', 'Music', 
            'Nature', 'Night', 'Pattern', 'Photography', 'Space', 'Sports', 'Technology', 
            'Travel', 'Underwater', 'Vintage', 'Other'
        ];
        foreach ($categories as $category):
        ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo strtolower($category); ?>" id="<?php echo strtolower($category); ?>">
            <label class="form-check-label" for="<?php echo strtolower($category); ?>"><?php echo $category; ?></label>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mb-3">
        <label for="image" class="form-label">Image (PNG, JPEG, or WebP)</label>
        <input type="file" class="form-control" id="image" name="image" accept=".png,.jpg,.jpeg,.webp" required>
    </div>
    <button type="submit" class="btn btn-primary">Upload</button>
</form>
