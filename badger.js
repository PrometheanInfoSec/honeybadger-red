// marker used to create a link to the last marker info window
// make array of markers to cycle through all windows (future)
var lastMarkerObj = new google.maps.Marker();
var accMarkerObj = new google.maps.Marker();
var servicePath = 'badger.php';
var sessionJson = null;

function loadPanel() {
    loadTargets(true);
    loadClock();
}

function loadContent() {
    $.get(servicePath, 'beacons='+$('#target').val(), function(data){
        sessionJson = $.parseJSON(data);
        loadMap(sessionJson);
        loadSummary(sessionJson);
        loadFilter(sessionJson);
    });
}

function filterContent() {
    var filteredJson = {};
    for (key in sessionJson) {
        if (document.getElementById(sessionJson[key].agent).checked == true) {
            filteredJson[key] = sessionJson[key];
        }
    }
    loadMap(filteredJson);
    loadSummary(filteredJson);
}

function loadMap(json) {
    if (isEmpty(json)) {
        document.getElementById("map").innerHTML = "filter empty"
        return;
    }
    var coords = new google.maps.LatLng(0,0);
    var mapOptions = {
        zoom: 5,
        center: coords,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true,
        mapTypeControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
            position: google.maps.ControlPosition.RIGHT_TOP
        },
        panControl: true,
        panControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM
        },
        streetViewControl: true,
        streetViewControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM
        },
        zoomControl: true,
        zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM
        },
    };
    var map = new google.maps.Map(document.getElementById("map"), mapOptions);
    var bounds = new google.maps.LatLngBounds();

    function add_marker(opts, place) {
        var marker = new google.maps.Marker(opts);
        var infowindow = new google.maps.InfoWindow({
            autoScroll: false,
            content: place.details
        });
        google.maps.event.addListener(marker, 'click', function() {
            infowindow.open(map,marker);
        });
        bounds.extend(opts.position);
        return marker;
    }

    for (key in json) {
        var marker = json[key];
        // add marker to map
        var currMarker = add_marker({
            position: new google.maps.LatLng(marker.lat,marker.lng),
                title:marker.ip+":"+marker.port,
                map:map},{
            details:'<div align="left" class="iwcontent">Target: '+marker.target
                + '<br />Agent: '+marker.agent+' @ '+marker.ip+':'+marker.port
                + '<br />Time: '+marker.time
                + '<br />User-Agent: '+marker.useragent
                + '<br />Latitude: '+marker.lat
                + '<br />Longitude: '+marker.lng
                + '<br />Accuracy: '+marker.acc
                + '<br />Comment: '+marker.comment
                + '<br /></div>'});
        // set markers for latest hit from selected target
        var hitTime = new Date(marker.time);
        if (typeof lastTime === 'undefined' || hitTime > lastTime) {
            var lastTime = hitTime;
            lastMarkerObj = currMarker;
        }
        // set markers for the most accurate hit from selected target
        if (marker.acc != "Unknown") {
            hitAcc = parseInt(marker.acc);
            if (typeof lastAcc === 'undefined' || hitAcc < lastAcc) {
                var lastAcc = hitAcc;
                accMarkerObj = currMarker;
            }
        }
    }
    map.fitBounds(bounds);
}

function loadSummary(json) {
    if (isEmpty(json)) {
        document.getElementById("summary").innerHTML = ""
        return;
    }
    var accMarker = null;
    for (key in json) {
        var marker = json[key];
        // set markers for latest hit from selected target
        var hitTime = new Date(marker.time);
        if (typeof lastTime === 'undefined' || hitTime > lastTime) {
            var lastTime = hitTime;
            var lastMarker = marker;
        }
        // set markers for the most accurate hit from selected target
        if (marker.acc != "Unknown") {
            hitAcc = parseInt(marker.acc);
            if (typeof lastAcc === 'undefined' || hitAcc < lastAcc) {
                var lastAcc = hitAcc;
                var accMarker = marker;
            }
        }
    }
    // last hit
    var lastTime = parseDate(new Date(lastMarker.time));
    htmlLast = "<u>Most Recent:</u> (<a href='javascript:openMarker(lastMarkerObj)'>show</a>)"
        + "<br />Agent: " + lastMarker.agent
        + "<br />Date: " + lastTime['month'] + "/" + lastTime['day'] + "/" + lastTime['year']
        + "<br />Time: " + lastTime['hours'] + ":" + lastTime['minutes'] + ":" + lastTime['seconds']
        + "<br /><br />";
    // most accurate hit
    if (accMarker != null) {
        htmlAcc = "<u>Most Accurate:</u> (<a href='javascript:openMarker(accMarkerObj)'>show</a>)"
            + "<br />Agent: " + accMarker.agent
            + "<br />Accuracy: " + accMarker.acc + "m";
    } else {
        htmlAcc = '';
    }
    // add to panel
    var summary = document.getElementById("summary");
    summary.innerHTML = htmlLast + htmlAcc;
}

function isEmpty(o) {
    for(var i in o) {
        if(o.hasOwnProperty(i)) {
            return false;    
        }
    }
    return true;
}

function parseDate(dateTime) {
    var dateArray = new Array();
    dateArray['year'] = dateTime.getUTCFullYear();
    dateArray['month'] = dateTime.getUTCMonth() + 1;
    dateArray['day'] = dateTime.getUTCDate();
    var hours = dateTime.getHours();
    var minutes = dateTime.getMinutes();
    var seconds = dateTime.getSeconds();
    if (hours < 10){ hours = "0" + hours; }
    if (minutes < 10){ minutes = "0" + minutes; }
    if (seconds < 10){ seconds = "0" + seconds; }
    dateArray['hours'] = hours;
    dateArray['minutes'] = minutes; 
    dateArray['seconds'] = seconds; 
    return dateArray; 
}

function loadFilter(json) {
    var totalHits = 0;
    var hitSummary = new Array();
    for (key in json) {
        var marker = json[key];
        // iterate total hit count
        totalHits++;
        // build hit summary array
        if (marker.agent in hitSummary) {
            hitSummary[marker.agent]++;
        } else {
            hitSummary[marker.agent] = 1;
        }
    }
    // filter
    htmlSummary = "<u>Hits:</u> (" + totalHits + ")<br />";
    for (key in hitSummary) {
        htmlSummary += '<input type="checkbox" id="' + key + '" onchange="filterContent();" checked="checked"/>' + hitSummary[key] + " - " + key + "<br />";
    }
    // add to panel
    var summary = document.getElementById("filter");
    summary.innerHTML = htmlSummary;
}

function loadTargets(init) {
    $.get(servicePath, 'targets=_', function(data){
        var json = $.parseJSON(data);
        var select = document.getElementById("target");
        if (select.selectedIndex > -1) {
            var selected = select.options[select.selectedIndex].value;
        } else {
            var selected = '';
            init = true;
        }
        //select.setAttribute('size', 1);
        select.options.length = 0;
        for (var i=0; i<json.targets.length; i++) {
            var target = json.targets[i];
            var option = document.createElement("option");
            option.text = target;
            option.value = target;
            if (target == selected) {
                option.selected = true;
            }
            select.appendChild(option);
        }
        if (init) {
            select.selectedIndex = -1;
        }
    });
    window.setTimeout(function() { loadTargets(false); },10000);
}

function loadClock() {
    $.get(servicePath, 'time=_', function(data){
        var json = $.parseJSON(data);
        var serverTime = new Date(json.dtg);
        var localTime = new Date();
        var diffTime = serverTime - localTime;
        tick(diffTime);
    });
}

function tick(diffTime){
    var currTime = new Date();
    currTime.setTime(currTime.getTime() + diffTime);
    var clock_hours = currTime.getHours();
    var clock_minutes = currTime.getMinutes();
    var clock_seconds = currTime.getSeconds();
    if (clock_hours < 10){ clock_hours = "0" + clock_hours; }
    if (clock_minutes < 10){ clock_minutes = "0" + clock_minutes; }
    if (clock_seconds < 10){ clock_seconds = "0" + clock_seconds; }
    var clock_div = document.getElementById('js_clock');
    clock_div.innerHTML = clock_hours + ":" + clock_minutes + ":" + clock_seconds;
    window.setTimeout(function() { tick(diffTime); },1000);
}

function purge(type) {
    if (confirm("Are you sure you want to purge the "+type+"?")) {
        $.get(servicePath, 'purge='+type, function(data){
            var json = $.parseJSON(data);
            var note = document.getElementById("notification");
            note.innerHTML = json.msg
            note.style.visibility = "visible";
            setTimeout(function() { note.style.visibility = "hidden"; }, 3000);
        });
    }
}

function openMarker(marker) {
    google.maps.event.trigger(marker, "click");
}
