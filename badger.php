<?php include 'include/auth_check.php'; ?>
<?php include 'include/common.php'; ?>

<?php

// logs out of the ui
if (isset($_REQUEST['action'])) {
    $item = sanitize($_REQUEST['action']);
    if ($item == 'logout') {
        session_destroy();
        header('Location: ./');
        exit;
    }
}

// returns contents of log file
if (isset($_REQUEST['view'])) {
    $item = sanitize($_REQUEST['view']);
    if ($item == 'log') {
        $content = '';
        if (file_exists($logfile)) { $content = file_get_contents($logfile); }
        echo '<pre>'.$content.'</pre>';
    } else {
        //header('HTTP/1.0 404 Not Found');
        echo "<h1>404 File not found</h1>";
    }
    exit;
}

// purges data files
if (isset($_REQUEST['purge'])) {
    if ($admin) {
        $item = sanitize($_REQUEST['purge']);
        if ($item == 'log') {
            if (file_exists($logfile)) { exec('rm -f '.$logfile); }
            $msg = 'log purged';
        } elseif ($item == 'db') {
            $prep = $db->prepare("DELETE FROM beacons");
            $prep->execute();
            $msg = 'database purged';
        } else {
            $msg = 'file does not exist';
        }
    } else {
        $msg = 'unauthorized';
    }
    respond($msg);
    // respond terminates execution
}

// returns the current server time
if (isset($_REQUEST['time'])) {
    $res = array();
    $dtg = date("F d, Y H:i:s");
    $res['dtg'] = $dtg;
    echo json_encode($res);
    exit;
}

// returns a list of all reporting agents
if (isset($_REQUEST['targets'])) {
    $res = array();
    $res['targets'] = array();
    $prep = $db->prepare("SELECT DISTINCT target FROM beacons ORDER BY target");
    $prep->execute();
    while ($row = $prep->fetch(PDO::FETCH_ASSOC)) {
        array_push($res['targets'], $row['target']);
    }
    echo json_encode($res);
    exit;
}

// returns specified target beacons
if (isset($_REQUEST['beacons'])) {
    $target = sanitize($_REQUEST['beacons']);
    $res = array();
    $prep = $db->prepare("SELECT * FROM beacons WHERE target = :target");
    $prep->execute(array(":target" => $target));
    while ($row = $prep->fetch(PDO::FETCH_ASSOC)) {
        $res[$row["id"]] = $row;
    }
    if (empty($res)) {
        respond('invalid target value');
        // respond terminates execution
    } else {
        echo json_encode($res);
        exit;
    }
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
    <script src="http://maps.google.com/maps/api/js?sensor=false&key=AIzaSyCpjNE-0bpWRD3NlREOz9jo0WDiu2AsmRM"></script>
    <script type="text/javascript" src="badger.js"></script>
    <link rel="stylesheet" type="text/css" href="badger.css">
</head>
<body onload="loadPanel();">
    <div id="notification" class="rounded"></div>
    <div id="nav" class="rounded shaded">
        <img class="logo" src="honeybadger.png" />
        <div>
            <input type="button" class="button" value="View Log" onclick="window.open('./badger.php?view=log','_blank');"><br />
            <?php if ($admin) { echo '<input type="button" class="button" value="Purge Log" onclick="purge(\'log\');"><br />'; } ?>
            <?php if ($admin) { echo '<input type="button" class="button" value="Purge Database" onclick="purge(\'db\');"><br />'; } ?>
            <input type="button" class="button" value="Log Out" onclick="document.location='./badger.php?action=logout';"><br />
        </div>
        Server time:<div id="js_clock"></div>
        <div class="list rounded">
            <h2>Targets</h2>
            <select id="target" onchange="loadContent();"></select>
        </div>
        <div id="filter"></div>
        <div id="summary"></div>
    </div>
    <div id="map">Select a target to begin.</div>
</body>
</html>
