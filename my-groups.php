<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
$me = (int) $_SESSION['user_id'];

// Groups I own
$stmt = $pdo->prepare("
    SELECT id, course_code, title, meets_at, location, capacity
    FROM study_groups
    WHERE owner_id = ?
    ORDER BY meets_at ASC
");
$stmt->execute([$me]);
$owned = $stmt->fetchAll();

// Groups I joined (I am a member but not the owner)
$stmt = $pdo->prepare("
    SELECT g.id, g.course_code, g.title, g.meets_at, g.location, g.capacity
    FROM memberships m
    JOIN study_groups g ON g.id = m.group_id
    WHERE m.user_id = ? AND g.owner_id <> ?
    ORDER BY g.meets_at ASC
");
$stmt->execute([$me, $me]);
$joined = $stmt->fetchAll();

require __DIR__ . '/header.php';
?>

    <h2>My groups</h2>

    <h3>Groups I own</h3>
<?php if (!$owned): ?>
    <p>You do not own any study groups yet.</p>
<?php else: ?>
    <table border="1">
        <tr><th>Course</th><th>Title</th><th>When</th><th>Where</th><th>Capacity</th></tr>
        <?php foreach ($owned as $group): ?>
            <tr>
                <td><?= htmlspecialchars($group['course_code']) ?></td>
                <td><a href="group.php?id=<?= (int) $group['id'] ?>"><?= htmlspecialchars($group['title']) ?></a></td>
                <td><?= htmlspecialchars($group['meets_at']) ?></td>
                <td><?= htmlspecialchars($group['location']) ?></td>
                <td><?= htmlspecialchars($group['capacity']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

    <h3>Groups I joined</h3>
<?php if (!$joined): ?>
    <p>You have not joined any study groups yet.</p>
<?php else: ?>
    <table border="1">
        <tr><th>Course</th><th>Title</th><th>When</th><th>Where</th><th>Capacity</th></tr>
        <?php foreach ($joined as $group): ?>
            <tr>
                <td><?= htmlspecialchars($group['course_code']) ?></td>
                <td><a href="group.php?id=<?= (int) $group['id'] ?>"><?= htmlspecialchars($group['title']) ?></a></td>
                <td><?= htmlspecialchars($group['meets_at']) ?></td>
                <td><?= htmlspecialchars($group['location']) ?></td>
                <td><?= htmlspecialchars($group['capacity']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>