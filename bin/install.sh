#!/bin/bash
echo "* Installing lighttpd and php"
sudo apt install lighttpd php-cgi
echo "* adding splatweb user..."
sudo adduser --system splatweb --disabled-password
echo "* Installing dev headers..."
sudo apt install libbz2-dev libpng-dev libjpeg-dev

echo "* Building splat"
cd src/splat
make || {
   echo "**** ERROR ****"
   echo "Failed building splat. See output above for more details."
   exit 1
}
echo "* Install splat!"
sudo ./install all
cd ..

echo "* Successfully installed! Please finish setting up and enjoy!"
