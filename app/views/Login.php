<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/login">
        <p>
            <label for="username">Username</label><br>
            <input type="text" id="username" name="username">
        </p>
        <p>
            <label for="password">Password</label><br>
            <input type="password" id="password" name="password">
        </p>
        <p>
            <button type="submit">Login</button>
        </p>
    </form>
</body>
</html>
