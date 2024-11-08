<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require 'database.php';

// Handle form actions for updating, resetting, and deleting links
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $link_id = $_POST['link_id'];
    $action = $_POST['action'];

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE short_links SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $link_id]);
    } elseif ($action === 'reset_clicks') {
        $stmt = $pdo->prepare("UPDATE short_links SET click_count = 0 WHERE id = :id");
        $stmt->execute([':id' => $link_id]);
    } elseif ($action === 'update_click_limit') {
        $new_click_limit = $_POST['click_limit'];
        $stmt = $pdo->prepare("UPDATE short_links SET click_limit = :click_limit WHERE id = :id");
        $stmt->execute([':click_limit' => $new_click_limit, ':id' => $link_id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM short_links WHERE id = :id");
        $stmt->execute([':id' => $link_id]);
    }
}

// Fetch all the links from the database
$stmt = $pdo->query("SELECT * FROM short_links");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Links</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Shortened Links</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Original URL</th>
                <th>Short URL</th>
                <th>Clicks</th>
                <th>Click Limit</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td><?= $link['id'] ?></td>
                    <td><?= htmlspecialchars($link['original_url']) ?></td>
                    <td>
                        <a href="http://<?= $_SERVER['HTTP_HOST'] . '/url_shortener/redirect.php?c=' . $link['short_code'] ?>" target="_blank">
                            <?= htmlspecialchars($_SERVER['HTTP_HOST'] . '/url_shortener/redirect.php?c=' . $link['short_code']) ?>
                        </a>
                    </td>
                    <td><?= $link['click_count'] ?></td>
                    <td><?= $link['click_limit'] == 0 ? 'Unlimited' : $link['click_limit'] ?></td>
                    <td><?= $link['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <!-- Toggle Status -->
                        <form method="post" action="manage_links.php" style="display:inline;">
                            <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                            <button type="submit" name="action" value="toggle_status" class="btn btn-sm <?= $link['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                <?= $link['is_active'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>

                        <!-- Reset Clicks -->
                        <form method="post" action="manage_links.php" style="display:inline;">
                            <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                            <button type="submit" name="action" value="reset_clicks" class="btn btn-sm btn-warning">Reset Clicks</button>
                        </form>

                        <!-- Update Click Limit -->
                        <form method="post" action="manage_links.php" style="display:inline;">
                            <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                            <input type="number" name="click_limit" value="<?= $link['click_limit'] ?>" class="form-control-sm" style="width:80px; display:inline;">
                            <button type="submit" name="action" value="update_click_limit" class="btn btn-sm btn-primary">Update Limit</button>
                        </form>

                        <!-- Delete Link -->
                        <form method="post" action="manage_links.php" style="display:inline;">
                            <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
