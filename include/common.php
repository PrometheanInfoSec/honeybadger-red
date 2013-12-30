<?php

$logfile = 'data/log.txt';
$dbname = 'data/data.db';

// initialize the data directory
if (!is_dir('data')) {
    if (!mkdir('data', 0700)) {
        $msg = 'filesystem error';
        respond($msg);
        // respond terminates execution
    }
}

// initialize the database
try {
    $db = new PDO('sqlite:'.$dbname);
    //$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION  );
    $query = 'CREATE TABLE IF NOT EXISTS beacons (id integer primary key, time text, target text, agent text, ip text, port text, useragent text, comment text, lat text, lng text, acc text)';
    $db->query($query);
    $query = 'CREATE TABLE IF NOT EXISTS users (username text primary key, password text, salt text, role integer)';
    $db->query($query);
} catch (Exception $e) {
    $msg = 'database error => '. $e->getMessage();
    respond($msg);
    // respond terminates execution
}

// global logging function
function logger($msg) {
    global $logfile;
    $stamp = date("m/d/Y H:i:s");
    if (!($file = fopen($logfile, 'a'))) {
        $msg = 'filesystem error';
        respond($msg);
        // respond terminates execution
    }
    fwrite($file, sprintf("[%s] %s\n", $stamp, $msg));
    fclose($file);
    return;
}

// global input filtering function
function sanitize($text) {
    $filtered = preg_replace( "/[^a-zA-Z0-9\s_\.-]/", "", $text );
    if (strcmp($text, $filtered) != 0) {
        logger(sprintf('[*] Input filtered: %s => %s', $text, $filtered));
    }
    return $filtered;
}

// global json response function
function respond($text) {
    $res = array();
    $res['msg'] = $text;
    echo json_encode($res);
    exit;
}

?>
