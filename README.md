splatweb
---------


A crappy web frontend for running SPLAT reports.

Quickstart
----------
* Unpack splat-database.tar.xz into /databases/
* Edit private/splat.ini
	joe private/splat.ini
* Install the needed packages (apt for now, edit if needed) and add user
	./bin/install.sh
* Configure php-fpm pool to run as the new user serving wwwroot/
	-- this will be done automatically soon ;o
* Enjoy!
