<?php include 'include/common.php'; ?>

<?php

if (isset($_POST['username'], $_POST['password'])) {
    // sanitize incoming username and password
    $username = $_POST['username'];
    $password = $_POST['password'];
    $prep = $db->prepare("SELECT password, salt, role FROM users WHERE username = :username LIMIT 1");
    $prep->execute(array(":username" => $username));
    $row = $prep->fetch(PDO::FETCH_ASSOC);
    $db_password = $row['password'];
    $db_salt     = $row['salt'];
    $db_role     = $row['role'];
    // if creds are legit
    if (sha1($db_salt.$password) == $db_password) {
        session_start();
        $_SESSION['role'] = intval($db_role);
        $_SESSION['init'] = true;
        header('Location: ./badger.php');
        exit;
    }
    $error = 'Invalid Credentials.';
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="badger.css">
</head>
<body>
    <img class="login-logo" src="honeybadger.png" />
    <div class="login-form rounded shaded">
        <form action="" enctype="application/x-www-form-urlencoded" method="post">
            <div>
                <label for="username">Username:</label><br />
                <input name="username" type="text" />
            </div>
            <div>
                <label for="password">Password:</label><br />
                <input name="password" type="password" />
            </div>
            <div>
                <input name="submit" type="submit" value="Login" />
            </div>
            <?php if (isset($error)) { echo '<p>'.$error.'</p>'; } ?>
        </form>
    </div>
</body>
</html>
