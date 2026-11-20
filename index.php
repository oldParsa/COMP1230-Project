<?php
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$groups = [];

$stmt = $pdo->prepare("
    SELECT study_groups.id, study_groups.course_code, study_groups.title,
           study_groups.meets_at, study_groups.location, study_groups.capacity,
           users.display_name
    FROM study_groups
    JOIN users ON users.id = study_groups.owner_id
    ORDER BY study_groups.meets_at ASC
");
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/header.php';

$delete_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

    <h2>Study Groups</h2>

<?php if($delete_error !== ''): ?>
    <span class="error"><?= $delete_error ?></span>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>
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
            <th>Created by</th>
            <?php if (isset($_SESSION['user_id'])): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>

        <?php foreach ($groups as $group): ?>
            <tr>
                <td><?= htmlspecialchars($group['course_code']) ?></td>
                <td><?= htmlspecialchars($group['title']) ?></td>
                <td><?= htmlspecialchars($group['meets_at']) ?></td>
                <td><?= htmlspecialchars($group['location']) ?></td>
                <td><?= htmlspecialchars($group['capacity']) ?></td>
                <td><?= htmlspecialchars($group['display_name']) ?></td>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <td>
                        <a href="group-edit.php?id=<?= (int)$group['id'] ?>">Edit</a>

                        <form method="post" action="group-delete.php">
                            <?= csrfField()?>
                            <input type="hidden" name="id" value="<?= (int)$group['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>

<?php require __DIR__ . '/footer.php'; ?>
