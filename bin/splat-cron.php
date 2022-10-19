#!/usr/bin/php
<?php
$start_time = microtime(true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$basedir = '/home/splatweb/';
$cfgfile = "$basedir/private/splat.ini";
$cronjob = "$basedir/bin/splat-cron.sh";
$plotdir = "$basedir/private/plots";
$queuedir = "$basedir/private/queue";

///////////////////////////////////////////////////////
// Minimal user-servicable parts below... Good luck! //
///////////////////////////////////////////////////////
$errors = false;

// Load configuration file
if (!file_exists($cfgfile))
   die("You must create a configuration file at $cfgfile or edit \$cfgfile path in this script!\n");

$cfg = parse_ini_file($cfgfile, true);

// If the queue lockfile exists, we need to wait a bit before trying again below, as a new entry is being written...
$start_time = microtime(true);
$loops = 0;

if (!is_dir($queuedir))
   die("Queue directory doesn't exist\n");

if (file_exists($queuedir . "/.lock")) {
   echo "Waiting for lock to go away (30 seconds max)";

   while (file_exists($queuedir . "/.lock")) {
      $loops++;
      $now = microtime(true);

      // We can spin for up to 30 seconds...
      if ($now > $start_time + 30000 || $loops > 30) {
         die("Failed to acquire queue lock within 30 seconds, try again later... Sorry!\n");
      }
      // Wait a second then try again...
      echo ".";
      sleep(1);
   }
}

if (file_exists($queuedir . "/.plotlock")) {
   die("We are already running, bailing!\n");
}

// Scan the queue and work it...
if ($dp = opendir($queuedir . "/")) {
   if (($lockfile = fopen("$queuedir/.plotlock", "x")) === false) {
      die("Unable to open plotlock");
   }

   while (($t = readdir($dp)) !== false) {
      if ($t[0] == ".")
         continue;
      $fp = fopen($queuedir . "/" . $t, "r");
      if ($fp === false) {
         fclose($lockfile);
         unlink("$queuedie/.plotlock");
         die("Error processing plot " . $t . "<br/>\n");
      } else {
         echo "Processing plot " . $t . "\n";
         system("$basedir/bin/splat-plot.sh $t", $rv);

         if ($rv != 0)
            echo "Error $rv returned by splat-plot.sh\n";
      }
   }
   closedir($dp);
   fclose($lockfile);
   unlink("$queuedir/.plotlock");
}
?>
