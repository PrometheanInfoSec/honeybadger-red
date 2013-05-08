<?php

session_start();

//$path = explode('?', $_SERVER['REQUEST_URI']);
//$path = $path[0];

if (!isset($_SESSION['init'])) {
	session_destroy();
    header('Location: ./');//.$path);
    exit;
}

$admin = false;
if ($_SESSION['role'] === 0) {
	$admin = true;
}

// roles
// 0 = admin
// 1 = user

?>
