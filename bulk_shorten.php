<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require 'database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$success_message = '';
$shortened_links = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urls = explode("\n", $_POST['urls']);
    $click_limit = isset($_POST['click_limit']) ? (int)$_POST['click_limit'] : 0;

    foreach ($urls as $original_url) {
        $original_url = trim($original_url);
        if (!empty($original_url)) {
            do {
                // Generate a new short code
                $short_code = substr(md5(time() . $original_url . uniqid()), 0, 6);

                // Check if the short code already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM short_links WHERE short_code = :short_code");
                $stmt->execute([':short_code' => $short_code]);
                $exists = $stmt->fetchColumn();
            } while ($exists > 0); // Regenerate if it exists

            try {
                // Insert the new link into the database
                $stmt = $pdo->prepare("INSERT INTO short_links (original_url, short_code, click_limit) VALUES (:original_url, :short_code, :click_limit)");
                $stmt->execute([':original_url' => $original_url, ':short_code' => $short_code, ':click_limit' => $click_limit]);

                // Store the original and shortened links
                $shortened_links[] = [
                    'original' => $original_url,
                    'shortened' => "http://" . $_SERVER['HTTP_HOST'] . '/url_shortener/redirect.php?c=' . $short_code
                ];
            } catch (PDOException $e) {
                echo "Error inserting link: " . $e->getMessage();
                exit; // Stop execution on error
            }
        }
    }

    // Create a success message with shortened links
    $success_message = "Shortened links created: <br>" . implode("<br>", array_map(function($link) {
        return "<a href='{$link['shortened']}' target='_blank'>" . htmlspecialchars($link['shortened']) . "</a>";
    }, $shortened_links));

    // Handle file export (txt/csv)
    if (isset($_POST['export_format'])) {
        $filename = "bulk_links_" . date('Y-m-d') . "." . $_POST['export_format'];
        header("Content-Type: text/{$_POST['export_format']}");
        header("Content-Disposition: attachment;filename={$filename}");

        if ($_POST['export_format'] === 'csv') {
            echo "Original Link,Shortened Link\n";
            foreach ($shortened_links as $link) {
                echo "\"{$link['original']}\",\"{$link['shortened']}\"\n";
            }
        } else {
            foreach ($shortened_links as $link) {
                echo "Original Link: {$link['original']}\nShortened Link: {$link['shortened']}\n\n";
            }
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bulk Short Links</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Create Bulk Short Links</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <form method="post" action="bulk_shorten.php">
        <div class="form-group">
            <label for="urls">Enter URLs (one per line)</label>
            <textarea class="form-control" id="urls" name="urls" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <label for="click_limit">Click Limit (0 for unlimited)</label>
            <input type="number" class="form-control" id="click_limit" name="click_limit" value="0">
        </div>
        <button type="submit" class="btn btn-primary">Create Short Links</button>
    </form>

    <!-- Export options -->
    <?php if (!empty($shortened_links)): ?>
    <form method="post" action="bulk_shorten.php">
        <input type="hidden" name="urls" value="<?= htmlspecialchars(implode("\n", array_column($shortened_links, 'original'))) ?>">
        <input type="hidden" name="click_limit" value="<?= htmlspecialchars($click_limit) ?>">
        <div class="form-group">
            <label for="export_format">Export Links As</label>
            <select class="form-control" id="export_format" name="export_format">
                <option value="txt">TXT</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Export Links</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
