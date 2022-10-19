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
* Download and build the database (this takes a bit)
	./bin/update-db.sh
  (or get a copy of srtm-sdf.tar.xz from someone)
* Configure php-fpm pool to run as the new user serving wwwroot/
  This will be done automatically soon ;o
* Enjoy!



-------

This is a weekend project i long forgot about. Enjoy! Contribute changes
