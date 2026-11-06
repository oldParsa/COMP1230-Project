<?php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
}
$username = '';
$displayNameInput = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $displayNameInput = $_POST['display_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (preg_match('/\A[a-z0-9_]{3,20}\z/', $username) !== 1) {
        $errors[] = 'Username must be 3–20 characters and contain only lowercase letters, numbers, or underscores.';
    }
    if (trim($displayNameInput) === '') {
        $errors[] = 'Display name is required.';
    }
    elseif (mb_strlen($displayNameInput) > 50) {
        $errors[] = 'Display name must be 50 characters or fewer.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }
    if ($errors === []) {
        try {
            $pdo = require __DIR__ . '/db.php';
            $sql = "SELECT id FROM users WHERE username = :username";
            $statement = $pdo->prepare($sql);
            $statement->execute(['username' => $username]);
            $existingUser = $statement->fetchColumn();
            if ($existingUser !== false) {$errors[] = 'That username is already taken.';}
            else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username,display_name,password_hash)VALUES (:username,:display_name,:password_hash)";
                $statement = $pdo->prepare($sql);
                try {
                    $statement->execute(['username' => $username, 'display_name' => $displayNameInput, 'password_hash' => $passwordHash]);
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errors[] = 'That username is already taken.';
                    }
                    else {throw $exception;}
                }
                if ($errors === []) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['display_name'] = $displayNameInput;
                    header('Location: index.php', true, 303);
                    exit;
                }
            }
        } catch (PDOException $exception) {
            $errors[] = 'Registration is unavailable right now. Please try again.';
        }
    }
}

$pageTitle = 'Register — Study Group Finder';
require_once __DIR__ . '/includes/sanitize.php';
?>

<main class="container">
    <section class="page-header">
        <h1>Create an Account</h1>
        <p>Register to create and manage study groups.</p>
    </section>
    <section class="registration">
        <?php if ($errors !== []): ?>
            <div class="error" role="alert">
                <?php foreach ($errors as $message): ?>
                    <p><?= escape($message)?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?=  escape($username)?>" minlength="3" maxlength="20" required>
            </div>
            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" value="<?= escape($displayNameInput) ?>" maxlength="50" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account?<a href="login.php">Log in</a></p>
    </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
