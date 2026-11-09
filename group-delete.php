<?php

session_start();

if(isset($_SESSION['user_id'])) {

    $pdo = require __DIR__ . '/db.php';
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = [];
        $Id = $_POST['id'] ?? '';
        $Filtered_Id = filter_var($Id, FILTER_VALIDATE_INT);
        if($Id === '' || $Filtered_Id === false) {
            header('Location: index.php', true, 302);
            exit;
        }

        try {
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
