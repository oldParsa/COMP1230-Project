<?php

session_start();

require_once __DIR__ . '/csrf.php';

if(isset($_SESSION['user_id'])) {
    $pdo = require __DIR__ . '/db.php';

    $errors = [];
    $Course_Code = strtoupper(trim($_POST["course_code"] ?? ''));
    $Title = trim($_POST["title"] ?? '');
    $Description = trim($_POST["description"] ?? '');
    $Capacity = trim($_POST['capacity'] ?? '');
    $Location = trim($_POST["location"] ?? '');
    $Meets_At = $_POST["meets_at"] ?? '';
    $MAX_DESCRIPTION_CHARACTERS = 1000;


    if($_SERVER["REQUEST_METHOD"] === "POST") {
        requireCsrf();
        if($Course_Code === '') $errors['Course_Code'] = 'Course code cannot be empty.';
        if($Title === '') $errors['Title'] = 'Title cannot be empty.';
        if($Description === '') $errors['Description'] = 'Description cannot be empty.';
        if($Capacity === '') $errors['Capacity'] = 'Capacity cannot be empty.';
        if($Location === '') $errors['Location'] = 'Location cannot be empty.';
        if($Meets_At === '') $errors['Meets_At'] = 'Meeting time cannot be empty.';

        if(!isset($errors['Course_Code'])) {
            $letters = substr($Course_Code, 0, 4);
            $digits = substr($Course_Code, 4, 4);

            if(mb_strlen($Course_Code) !== 8 || !ctype_alpha($letters) || !ctype_digit($digits)) {
                $errors['Course_Code'] = 'Course code must start with 4 letters followed by 4 digits.';
            }
        }
        if(!isset($errors['Title']) && mb_strlen($Title) < 3 || mb_strlen($Title) > 80) {
            $errors['Title'] = 'Title must be 3 to 80 characters long.';
        }
        if(!isset($errors['Description']) && mb_strlen($Description) > $MAX_DESCRIPTION_CHARACTERS) {
            $errors['Description'] = 'Description cannot be more than 1000 characters long.';
        }
        if(!isset($errors['Location']) && mb_strlen($Location) > 80) {
            $errors['Location'] = 'Location cannot be more than 80 characters long.';
        }

        $Capacity_Filtered = filter_var($Capacity, FILTER_VALIDATE_INT);
        if($Capacity_Filtered === false || $Capacity_Filtered < 2 || $Capacity_Filtered > 10) {
            $errors['Capacity'] = 'Capacity must be a whole number from 2 to 10.';
        }

        if($Meets_At === null || $Meets_At === '') {
            $errors['Meets_At'] = 'Meeting time cannot be empty.';
        } else if(strlen($Meets_At) !== 16) {
            $errors['Meets_At'] = 'Meeting time must be in this format: 2026-11-08 15:00.';
        } else {
            $year = substr($Meets_At, 0, 4);
            $month = substr($Meets_At, 5, 2);
            $day = substr($Meets_At, 8, 2);
            $hour = substr($Meets_At, 11, 2);
            $minute = substr($Meets_At, 14, 2);

            if ($Meets_At[4] !== '-' || $Meets_At[7] !== '-' || $Meets_At[10] !== 'T' || $Meets_At[13] !== ':' || !ctype_digit($year . $month . $day . $hour . $minute)) {
                $errors['Meets_At'] = 'Meeting time must be in this format: 2026-11-08 15:00.';
            } elseif (!checkdate((int) $month, (int) $day, (int) $year)) {
                $errors['Meets_At'] = 'Meeting time must be a real date.';
            } elseif ((int) $hour > 23 || (int) $minute > 59) {
                $errors['Meets_At'] = 'Hour must be 00-23 and minute 00-59.';
            }
        }

        if(!$errors) {
            try{
                $meets_at = str_replace('T', ' ', $Meets_At ) . ':00';

                $pdo->prepare("INSERT INTO study_groups (course_code, owner_id ,title, description, meets_at, location, capacity) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$Course_Code, $_SESSION['user_id'], $Title, $Description, $meets_at, $Location, $Capacity_Filtered]);

                $pdo->prepare("INSERT INTO memberships (user_id, group_id) VALUES (?, ?)")
                        ->execute([$_SESSION['user_id'], $pdo->lastInsertId()]);

                header('Location: index.php', true, 303);
                exit;

            } catch(PDOException $e) {
                if($e->getCode() == 23000) {
                    $errors['Course_Code'] = 'Course code already exists.';
                } else {
                    $errors['Message'] = 'Could not add study_group. Please try again later.';
                }
            }
        }

    }
} else {
    header('Location: login.php', true, 302);
    exit;
}

require __DIR__ . '/header.php';
?>

<form action="group-create.php" method="post">
    <?= csrfField()?>
    <h2>Group Create Form</h2>
    <span class="error"><?= $errors['Message'] ?? '' ?></span>

    <div>
        <label for="course_code">Course Code: </label>
        <input type="text" id="course_code" name="course_code" value="<?= htmlspecialchars($Course_Code)?>">
        <span class="error"><?= $errors['Course_Code'] ?? '' ?></span>
    </div>

    <div>
        <label for="title">Title: </label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($Title)?>">
        <span class="error"><?= $errors['Title'] ?? '' ?></span>
    </div>

    <div>
        <label for="description">Description: </label>
        <input type="text" id="description" name="description" value="<?= htmlspecialchars($Description)?>">
        <span class="error"><?= $errors['Description'] ?? '' ?></span>

    </div>

    <div>
        <label for="meets_at">Meets At: </label>
        <input type="datetime-local" id="meets_at" name="meets_at" value="<?= htmlspecialchars($Meets_At)?>">
        <span class="error"><?= $errors['Meets_At'] ?? '' ?></span>

    </div>

    <div>
        <label for="location">Location: </label>
        <input type="text" id="location" name="location" value="<?= htmlspecialchars($Location)?>">
        <span class="error"><?= $errors['Location'] ?? '' ?></span>
    </div>

    <div>
        <label for="capacity">Capacity: </label>
        <input type="text" id="capacity" name="capacity" value="<?= htmlspecialchars($Capacity)?>">
        <span class="error"><?= $errors['Capacity'] ?? '' ?></span>
    </div>

    <br>
    <button type="submit">Submit</button>
</form>

<?php require __DIR__ . '/footer.php'; ?>