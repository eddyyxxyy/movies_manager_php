<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName ?? 'Movies Manager') ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #f8fafc;
            color: #1a202c;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100dvh;
            margin: 0;
        }

        .container {
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <?= $content ?? '' ?>
    </div>
</body>

</html>