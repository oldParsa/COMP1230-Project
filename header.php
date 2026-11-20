<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/csrf.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Group Finder</title>
</head>

<body>

<header>

    <h1>Study Group Finder</h1>

    <nav>
        <a href="index.php">Home</a>

        <a href="team.php">Team</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <span>
                Logged in as
                <?= htmlspecialchars($_SESSION['display_name']) ?>
            </span>

            <form method="post" action="logout.php">
                <?= csrfField() ?>
                <button type="submit">Log out</button>
            </form>

        <?php else: ?>

            <a href="login.php">Log in</a>

            <a href="register.php">Register</a>

        <?php endif; ?>

    </nav>

</header>

<main>

