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
        'SELECT owner_id, capacity FROM study_groups WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($group === false) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Study group not found.';
        $redirect = 'index.php';
    } elseif ((int) $group['owner_id'] === $me) {
        $pdo->rollBack();
        $_SESSION['error'] = 'You cannot join your own study group.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM memberships WHERE group_id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $me]);
        $alreadyJoined = (int) $stmt->fetchColumn() > 0;

        // The group owner occupies one place. The Phase 2 migration adds
        // the owner's membership row, but count that place if it is missing.
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) + CASE WHEN EXISTS (
                 SELECT 1 FROM memberships WHERE group_id = ? AND user_id = ?
             ) THEN 0 ELSE 1 END
             FROM memberships WHERE group_id = ?'
        );
        $stmt->execute([$id, (int) $group['owner_id'], $id]);
        $memberCount = (int) $stmt->fetchColumn();

        if ($alreadyJoined) {
            $pdo->rollBack();
            $_SESSION['error'] = 'You have already joined this group.';
        } elseif ($memberCount >= (int) $group['capacity']) {
            $pdo->rollBack();
            $_SESSION['error'] = 'This study group is full.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO memberships (user_id, group_id) VALUES (?, ?)'
            );
            $stmt->execute([$me, $id]);
            $pdo->commit();
        }
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['error'] = 'Could not join the study group. Please try again later.';
}

header('Location: ' . $redirect, true, 303);
exit;
