<?php
require 'database.php';

if (isset($_GET['c'])) {
    $short_code = $_GET['c'];

    $stmt = $pdo->prepare("SELECT * FROM short_links WHERE short_code = :short_code AND is_active = 1");
    $stmt->execute([':short_code' => $short_code]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($link) {
        // Increment click count
        $stmt = $pdo->prepare("UPDATE short_links SET click_count = click_count + 1 WHERE id = :id");
        $stmt->execute([':id' => $link['id']]);

        // Check click limit
        if ($link['click_limit'] == 0 || $link['click_count'] <= $link['click_limit']) {
            header("Location: " . $link['original_url']);
            exit;
        } else {
            echo "This link has reached its click limit.";
        }
    } else {
        echo "Link not found or inactive.";
    }
} else {
    echo "No short code provided.";
}
