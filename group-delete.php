<?php

session_start();

require_once __DIR__ . '/csrf.php';

function accessDenied(): void {
    http_response_code(403);
    ?>
    <?php require "header.php"; ?>
    <h1>403</h1>
    <h2>You can only change your own study groups.</h2>
    <a href="index.php">Return to the home page</a>
    <?php require "footer.php"; ?>
    <?php

    exit;
}

if(isset($_SESSION['user_id'])) {
    $pdo = require __DIR__ . '/db.php';
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
        $error = [];
        $Id = $_POST['id'] ?? '';
        $Filtered_Id = filter_var($Id, FILTER_VALIDATE_INT);
        if($Id === '' || $Filtered_Id === false) {
            header('Location: index.php', true, 302);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT owner_id FROM study_groups WHERE id = ?");
            $stmt->execute([$Filtered_Id]);
            $group = $stmt->fetch();
            if($group['owner_id'] !== $_SESSION['user_id']) {
                accessDenied();
            }

            $pdo->prepare("DELETE FROM study_groups WHERE id = ?")
                ->execute([$Filtered_Id]);

            header('Location: index.php', true, 303);
            exit;
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Group could not be deleted. Please try again later.';
            header('Location: index.php', true, 303);
            exit;
        }
    } else {
        header('Location: index.php', true, 302);
        exit;
    }

} else {
    header('Location: login.php', true, 303);
    exit;
}
