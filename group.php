<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($id === false) {
    http_response_code(404);
    exit('Study group not found.');
}

try {
    $pdo = require __DIR__ . '/db.php';

    $stmt = $pdo->prepare('
        SELECT g.id, g.owner_id, g.course_code, g.title, g.description,
               g.meets_at, g.location, g.capacity, u.display_name AS owner_name
        FROM study_groups g
        JOIN users u ON u.id = g.owner_id
        WHERE g.id = ?
    ');
    $stmt->execute([$id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($group === false) {
        http_response_code(404);
        exit('Study group not found.');
    }

    // Fetch joined members. Some existing groups may not have an owner
    // membership row, so the owner is shown separately below in that case.
    $stmt = $pdo->prepare('
        SELECT u.id, u.display_name, m.joined_at
        FROM memberships m
        JOIN users u ON u.id = m.user_id
        WHERE m.group_id = ?
        ORDER BY m.joined_at ASC, u.id ASC
    ');
    $stmt->execute([$id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('Unable to load the study group. Please try again later.');
}

$me = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$ownerId = (int) $group['owner_id'];
$isOwner = $me > 0 && $me === $ownerId;
$isMember = false;
$ownerInMemberships = false;

foreach ($members as $member) {
    if ((int) $member['id'] === $ownerId) {
        $ownerInMemberships = true;
    }
    if ($me > 0 && (int) $member['id'] === $me) {
        $isMember = true;
    }
}

$occupied = count($members) + ($ownerInMemberships ? 0 : 1);
$placesLeft = max(0, (int) $group['capacity'] - $occupied);

$membershipError = is_string($_SESSION['error'] ?? null) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

require __DIR__ . '/header.php';
?>

<?php if ($membershipError !== ''): ?>
    <p class="error"><?= htmlspecialchars($membershipError, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<h2><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></h2>

<dl>
    <dt>Course</dt>
    <dd><?= htmlspecialchars($group['course_code'], ENT_QUOTES, 'UTF-8') ?></dd>

    <dt>Description</dt>
    <dd><?= nl2br(htmlspecialchars($group['description'], ENT_QUOTES, 'UTF-8')) ?></dd>

    <dt>When</dt>
    <dd><?= htmlspecialchars($group['meets_at'], ENT_QUOTES, 'UTF-8') ?></dd>

    <dt>Where</dt>
    <dd><?= htmlspecialchars($group['location'], ENT_QUOTES, 'UTF-8') ?></dd>

    <dt>Created by</dt>
    <dd><?= htmlspecialchars($group['owner_name'], ENT_QUOTES, 'UTF-8') ?></dd>

    <dt>Capacity</dt>
    <dd><?= (int) $group['capacity'] ?></dd>

    <dt>Places left</dt>
    <dd><?= $placesLeft ?></dd>
</dl>

<h3>Members</h3>
<ul>
    <?php if (!$ownerInMemberships): ?>
        <li><?= htmlspecialchars($group['owner_name'], ENT_QUOTES, 'UTF-8') ?> (Owner)</li>
    <?php endif; ?>
    <?php foreach ($members as $member): ?>
        <li>
            <?= htmlspecialchars($member['display_name'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ((int) $member['id'] === $ownerId): ?>(Owner)<?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($me === 0): ?>
    <p><a href="login.php">Log in</a> to join this group.</p>
<?php elseif ($isOwner): ?>
    <p>You own this group.</p>
    <p><a href="group-edit.php?id=<?= (int) $group['id'] ?>">Edit group</a></p>
<?php elseif ($isMember): ?>
    <form method="post" action="group-leave.php">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
        <button type="submit">Leave Group</button>
    </form>
<?php elseif ($placesLeft > 0): ?>
    <form method="post" action="group-join.php">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
        <button type="submit">Join Group</button>
    </form>
<?php else: ?>
    <p class="error">This study group is full.</p>
<?php endif; ?>

<p><a href="index.php">Back to study groups</a></p>

<?php require __DIR__ . '/footer.php'; ?>
