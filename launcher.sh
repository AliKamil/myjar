#!/bin/bash

while true
do
    if ps ax | grep -v grep | grep "listener.php"
    then
        echo "process is running..."
    else
        clear
        date
        echo "process not running, relaunching..."
        php listener.php &
    fi
    sleep 10
done
