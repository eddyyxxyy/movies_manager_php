<?php
use App\Core\View;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? 'Movies Manager') ?></title>
    <meta name="description"
        content="<?= htmlspecialchars($description ?? 'Get to know, register, rate and share your opinions about movies with a modern, simple and intuitive platform. Access Movies Manager and create your account.') ?>">
</head>

<body>
    <?= View::renderPartial('_header') ?>

    <main>
        <?= $content ?>
    </main>

</body>

</html>