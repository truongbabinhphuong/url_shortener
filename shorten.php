<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_url = $_POST['url'];
    $click_limit = $_POST['click_limit'];

    $short_code = substr(md5(time() . $original_url), 0, 6); // Generate a short code
    $stmt = $pdo->prepare("INSERT INTO short_links (original_url, short_code, click_limit) VALUES (:original_url, :short_code, :click_limit)");
    $stmt->execute([':original_url' => $original_url, ':short_code' => $short_code, ':click_limit' => $click_limit]);

    $shortened_link = "http://" . $_SERVER['HTTP_HOST'] . '/url_shortener/redirect.php?c=' . $short_code; // Updated to include http
    $success_message = "Shortened link created: <a href='$shortened_link' target='_blank'>" . htmlspecialchars($shortened_link) . "</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Short Link</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Create Short Link</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <form method="post" action="shorten.php">
        <div class="form-group">
            <label for="url">Original URL</label>
            <input type="url" class="form-control" id="url" name="url" required>
        </div>
        <div class="form-group">
            <label for="click_limit">Click Limit (0 for unlimited)</label>
            <input type="number" class="form-control" id="click_limit" name="click_limit" value="0">
        </div>
        <button type="submit" class="btn btn-primary">Create Short Link</button>
    </form>
</div>

</body>
</html>
