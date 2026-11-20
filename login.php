<?php
/*
// Changed line 40 the header;
// Changed escape method to htmlspecialchars method;
// Changed  line 55 the footer;
*/
if (session_status() === PHP_SESSION_NONE) {session_start();}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
}

require_once __DIR__ . '/csrf.php';

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $loginPassword = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    try {
        $pdo = require __DIR__ . '/db.php';
        $sql = 'SELECT id, username, display_name, password_hash FROM users WHERE username = :username LIMIT 1';
        $statement = $pdo->prepare($sql);
        $statement->execute(['username' => $username]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user !== false && password_verify($loginPassword, (string) $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['display_name'] = (string) $user['display_name'];
            header('Location: index.php', true, 303);
            exit;
        }
        $error = 'Username or password is incorrect.';
    } catch (PDOException $exception) {
        $error = 'Unable to log in right now. Please try again.';
    }
}

$pageTitle = 'Log in — Study Group Finder';
require __DIR__ . '/header.php';
?>
<main class="container">
    <section class="page-header">
        <h1>Log in</h1>
        <p>Sign in to create and manage study groups.</p>
    </section>
    <section class="login">
        <div class="test-accounts">
            <h2>Test Accounts</h2>
            <p>You can use either of these accounts to test the website.</p>
            <table>
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Display Name</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>tester1</td>
                    <td>Study-1230</td>
                    <td>Tester One</td>
                </tr>
                <tr>
                    <td>tester2</td>
                    <td>Study-1230</td>
                    <td>Tester Two</td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Log in</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </section>
</main>
<?php require __DIR__ . '/footer.php'; ?>
