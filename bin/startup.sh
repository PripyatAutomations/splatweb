#!/bin/bash
[ -f "tmp/lighttpd.pid" ] && {
   echo "Killing old instance of lighttpd (pid: $(cat tmp/lighttpd.pid)"
   kill -9 $(cat tmp/lighttpd.pid)
   rm tmp/lighttpd.pid
   sleep 3
}

echo "* Trying to start lighttd on port 9123"
lighttpd -f etc/lighttpd.conf
echo "* Listening port on port 9123"
