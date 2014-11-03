#!/bin/bash

MEMCACHED_BIN=/usr/local/memcached/bin/memcached
MEMCACHED_PORTS=$(seq 11211 11215)
PHP_BIN=/usr/local/php/bin/php

killall memcached

for port in $MEMCACHED_PORTS
do
    `$MEMCACHED_BIN -d -m 32 -l 127.0.0.1 -p $port -P /tmp/memcached.$port.pid`
done

# set data into memcached
if [ `$PHP_BIN $PWD/setitems.php` = "ok" ] ; then 
    $PHP_BIN $PWD/getstatus.php
else
    echo "set items error "
fi

exit 0
