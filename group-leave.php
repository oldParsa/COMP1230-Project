<?php

session_start();
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

requireCsrf();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($id === false) {
    $_SESSION['error'] = 'Invalid study group.';
    header('Location: index.php', true, 303);
    exit;
}

$me = (int) $_SESSION['user_id'];
$redirect = 'group.php?id=' . $id;

try {
    $pdo = require __DIR__ . '/db.php';
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT owner_id FROM study_groups WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($group === false) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Study group not found.';
        $redirect = 'index.php';
    } elseif ((int) $group['owner_id'] === $me) {
        $pdo->rollBack();
        $_SESSION['error'] = 'You cannot leave a study group you own.';
    } else {
        $stmt = $pdo->prepare(
            'DELETE FROM memberships WHERE user_id = ? AND group_id = ?'
        );
        $stmt->execute([$me, $id]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            $_SESSION['error'] = 'You are not a member of this study group.';
        } else {
            $pdo->commit();
        }
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['error'] = 'Could not leave the study group. Please try again later.';
}

header('Location: ' . $redirect, true, 303);
exit;
