#!/bin/bash
# Not sure what this is about but it works...
mkdir -p /databases/srtm/sdf
cd /databases/srtm/
wget -O- -q http://www.viewfinderpanoramas.org/Coverage%20map%20viewfinderpanoramas_org3.htm | grep --color=auto '<area' | cut -f 5-6 -d '"' | cut -f 2 -d '"' | while read line; do wget -p -nc $line; done
