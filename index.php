<?php
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// One query: group + owner name + places left (capacity - members, owner included)
$stmt = $pdo->prepare("
    SELECT g.id, g.owner_id, g.course_code, g.title, g.meets_at,
           g.location, g.capacity, u.display_name,
           g.capacity - COUNT(m.user_id) AS places_left
    FROM study_groups g
    JOIN users u ON u.id = g.owner_id
    LEFT JOIN memberships m ON m.group_id = g.id
    GROUP BY g.id, u.display_name
    ORDER BY g.meets_at ASC
");
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/header.php';

$delete_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$me = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
?>

    <h2>Study Groups</h2>

<?php if ($delete_error !== ''): ?>
    <span class="error"><?= htmlspecialchars($delete_error) ?></span>
<?php endif; ?>

<?php if ($me): ?>
    <p><a href="group-create.php">Create a group</a></p>
<?php else: ?>
    <p><a href="login.php">Log in</a> to create a group.</p>
<?php endif; ?>

    <table border="1">
        <tr>
            <th>Course</th>
            <th>Title</th>
            <th>When</th>
            <th>Where</th>
            <th>Capacity</th>
            <th>Places left</th>
            <th>Created by</th>
            <?php if ($me): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>

        <?php foreach ($groups as $group): ?>
            <tr>
                <td><?= htmlspecialchars($group['course_code']) ?></td>
                <td><a href="group.php?id=<?= (int) $group['id'] ?>"><?= htmlspecialchars($group['title']) ?></a></td>
                <td><?= htmlspecialchars($group['meets_at']) ?></td>
                <td><?= htmlspecialchars($group['location']) ?></td>
                <td><?= htmlspecialchars($group['capacity']) ?></td>
                <td><?= htmlspecialchars($group['places_left']) ?></td>
                <td><?= htmlspecialchars($group['display_name']) ?></td>

                <?php if ($me): ?>
                    <td>
                        <?php if ((int) $group['owner_id'] === $me): ?>
                            <a href="group-edit.php?id=<?= (int) $group['id'] ?>">Edit</a>

                            <form method="post" action="group-delete.php">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>

<?php require __DIR__ . '/footer.php'; ?>
