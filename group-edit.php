<?php

session_start();

if(isset($_SESSION['user_id'])) {
    $pdo = require __DIR__ . '/db.php';

    $id = filter_var($_GET['id'] ?? $_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if($id === false) {
        headeR('Location: index.php', true, 303);
    }

    $errors = [];
    $Course_Code = strtoupper(trim($_POST["course_code"] ?? ''));
    $Title = trim($_POST["title"] ?? '');
    $Description = trim($_POST["description"] ?? '');
    $Capacity = filter_var(trim($_POST['capacity'] ?? 0), FILTER_VALIDATE_INT);
    $Location = trim($_POST["location"] ?? '');
    $Meets_At = $_POST["meets-at"] ?? '';
    $MAX_DESCRIPTION_CHARACTERS = 1000;


    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if($Course_Code === '') $errors['Course_Code'] = 'Course code cannot be empty.';
        if($Title === '') $errors['Title'] = 'Title cannot be empty.';
        if($Description === '') $errors['Description'] = 'Description cannot be empty.';
        if($Capacity === '') $errors['Capacity'] = 'Capacity cannot be empty.';
        if($Location === '') $errors['Location'] = 'Location cannot be empty.';
        if($Meets_At === '') $errors['Meets_At'] = 'Meeting time cannot be empty.';

        if(!isset($errors['Course_Code'])) {
            $letters = substr($Course_Code, 0, 4);
            $digits = filter_var(substr($Course_Code, 4, 4), FILTER_VALIDATE_INT);

            if(mb_strlen($Course_Code) !== 8 || !ctype_alpha($letters) || $digits === false) {
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

        if(!$errors) {
            try{

                $meets_at = str_replace('T', ' ', $Meets_At ) . ':00';

                $pdo->prepare("UPDATE study_groups SET course_code = ?, title = ?, description = ?, meets_at = ?, location = ?, capacity = ? WHERE id = ?")
                    ->execute([$Course_Code, $Title, $Description, $meets_at, $Location, $Capacity_Filtered, $id]);

                header('Location: index.php', true, 303);
                exit;

            } catch(PDOException $e) {
                if($e->getCode() == 23000) {
                    $errors['Course_Code'] = 'Course code already exists.';
                } else {
                    $errors['Message'] = $e->getMessage();
                }
            }
        }

    }

} else {
    header('Location: login.php', true, 302);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Study Group</title>
</head>
<body>
<form action="group-edit.php?id=<?= $id ?>" method="post">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <h2>Edit Group Form</h2>
    <span style="color: red"><?= $errors['Message'] ?? '' ?></span>

    <div>
        <label for="course_code">Course Code: </label>
        <input type="text" id="course_code" name="course_code" value="<?= htmlspecialchars($Course_Code)?>">
        <span style="color: red"><?= $errors['Course_Code'] ?? '' ?></span>
    </div>

    <div>
        <label for="title">Title: </label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($Title)?>">
        <span style="color: red"><?= $errors['Title'] ?? '' ?></span>
    </div>

    <div>
        <label for="description">Description: </label>
        <input type="text" id="description" name="description" value="<?= htmlspecialchars($Description)?>">
        <span style="color: red"><?= $errors['Description'] ?? '' ?></span>

    </div>

    <div>
        <label for="meets-at">Meets At: </label>
        <input type="datetime-local" id="meets-at" name="meets-at" value="<?= htmlspecialchars($Meets_At)?>">
        <span style="color: red"><?= $errors['Meets_At'] ?? '' ?></span>

    </div>

    <div>
        <label for="location">Location: </label>
        <input type="text" id="location" name="location" value="<?= htmlspecialchars($Location)?>">
        <span style="color: red"><?= $errors['Location'] ?? '' ?></span>
    </div>

    <div>
        <label for="capacity">Capacity: </label>
        <input type="text" id="capacity" name="capacity" value="<?= htmlspecialchars($Capacity)?>">
        <span style="color: red"><?= $errors['Capacity'] ?? '' ?></span>
    </div>

    <br>
    <button type="submit">Make Changes</button>
</form>

<a href="index.php">Return to the home page</a>

</body>
</html>