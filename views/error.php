<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title ?></title>
</head>
<body>
<h1><?php echo $title ?></h1>
<?php if ($debug) { ?>
    <p><?php echo $message ?></p>
<?php } ?>
</body>
</html>