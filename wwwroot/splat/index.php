<?php
 /*
  * splat.php: A really simple wrapper around SPLAT for plotting propagation
  * for others in an automated way.
  *
  * See /svc/www/remote/bin/splat-cron.sh for the crunchy bits!
  *
  * Config lives in /svc/www/remote/private/splat.ini
  */
$start_time = microtime(true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$basedir = '/home/splatweb';
$cfgfile = "$basedir/private/splat.ini";
$cronjob = "$basedir/bin/splat-cron.sh";
$viewdir = "$basedir/private/views";
$plotdir = "$basedir/private/plots";
$queuedir = "$basedir/private/queue";

///////////////////////////////////////////////////////
// Minimal user-servicable parts below... Good luck! //
///////////////////////////////////////////////////////
$errors = false;

function render_view($view) {
   global $basedir, $cfg, $cronjob, $viewdir, $errors, $queuedir;

   // XXX: If you might trust user input for a view name, make sure it's safe
   // XXX: for use in a path here..
   $viewfile = $viewdir . "/" . $view . ".html";

   if (strstr($view, ".") != false)
      die('Invalid view name $view passed by user, it must not contain dots!');

   if (!file_exists($viewfile))
      die("Unable to find view $viewfile, please check package installation!");

   $html = file_get_contents($viewfile);

   // If this is the form, insert any passed form data before rendering..
   if ($view == "form")
      $html = populate_form_data($html);

   header("Content-Type: text/html");
   die($html);
}

function enqueue_plot($userdata) {
   global $basedir, $cfg, $cronjob, $viewdir, $errors, $queuedir;
   $plot_id = 'plot_' . time();
 
   // If the queue lockfile exists, we need to wait a bit before trying again below...
   $start_time = microtime(true);
   $loops = 0;

   // XXX: Count files in queue dir, excluding .lock before adding another...
   if (!is_dir($queuedir))
      die("Queue directory doesn't exist");

   while (file_exists($queuedir . "/.lock")) {
      $loops++;
      $now = microtime(true);

      // We can spin for up to 30 seconds...
      if ($now > $start_time + 30000 || $loops > 30) {
         die("Failed to acquire queue lock within 30 seconds, try again later... Sorry!");
      }
      // Wait a second then try again...
      sleep(1);
   }

   // If we made it here, try to grab the lockfile again....
   $lockfp = fopen($queuedir . "/.lock", "x");
   if ($lockfp === false)
      die("Cannot open queue lockfile!");

   // store our PID...
   fprintf($lockfp, "%lu\n", getmypid());

   $queued = 0;

   if ($dp = opendir($queuedir . "/")) {
      while (($t = readdir($dp)) !== false) {
         if ($t[0] == ".")
            continue;
         $queued++;
      }
      closedir($dp);
   }

   // Check queue size...
   $queue_limit = $cfg['queues']['limit'];
   if ($queued >= $queue_limit) {
      fclose($lockfp);
      unlink($queuedir . "/.lock");
      die("Queue is full, try again in a few minutes...");
   }

   // try to open our queue file..
   $qfp = fopen($queuedir . "/" . $plot_id , "w+");
   if ($qfp === false) {
      // Remove lockfile
      fclose($lockfp);
      unlink($queuedir . "/.lock");
      die("Cannot open queue file!");
   }

   // Add our item to the queue
   // Request Data
   fprintf($qfp, "EMAIL=\"%s\"\n", filter_var($userdata['user_email'], FILTER_SANITIZE_EMAIL));
   fprintf($qfp, "SUBMITTED=\"%lu\"\n", time());

   // Location Data
   fprintf($qfp, "NAME=\"%s\"\n", filter_var($userdata['xmit_label'], FILTER_SANITIZE_EMAIL));
   fprintf($qfp, "CALL=\"%s\"\n", filter_var($userdata['user_callsign'], FILTER_SANITIZE_EMAIL));
   fprintf($qfp, "LAT=\"%f\"\n", filter_var($userdata['xmit_lat'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
   fprintf($qfp, "LON=\"%f\"\n", filter_var($userdata['xmit_lon'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
   fprintf($qfp, "HTAGL=\"%d\"\n", filter_var($userdata['xmit_height'], FILTER_SANITIZE_NUMBER_INT));
   fprintf($qfp, "ERP=\"%d\"\n", filter_var($userdata['xmit_power'], FILTER_SANITIZE_NUMBER_INT));
   fprintf($qfp, "FREQMHZ=\"%f\"\n", filter_var($userdata['xmit_freq'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
   fprintf($qfp, "TGTELEV=\"%f\"\n", filter_var($userdata['recv_height'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
   fprintf($qfp, "RADIUS=\"%d\"\n", filter_var($userdata['recv_radius'], FILTER_SANITIZE_NUMBER_INT));

   if (isset($userdata['recv_label']))
      fprintf($qfp, "REMOTETGT=\"%s\"\n", filter_var($userdata['recv_label'], FILTER_SANITIZE_EMAIL));

   // Optional fields (generates a second file)
   if (isset($userdata['recv_lat']) && isset($userdata['recv_lon'])) {
      fprintf($qfp, "REMOTELAT=\"%f\"\n", filter_var($userdata['recv_lat'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
      fprintf($qfp, "REMOTELON=\"%f\"\n", filter_var($userdata['recv_lot'], FILTER_SANITIZE_NUMBER_FLOAT, array('flags' => FILTER_FLAG_ALLOW_FRACTION)));
   }
      
   // Close queue file
   fclose($qfp);

   // Release our lock on the queue
   fclose($lockfp);
   unlink($queuedir . "/.lock");
   die("Your plot ID is $plot_id, please be patient and wait for it to complete before enqueuing another plot!<br/> If you do not receive an email, try <a href=\"" . $_SERVER['PHP_SELF'] . "?plot_id=" . $plot_id . "\">this link</a> in about 5-10 minutes...");
}

function show_plot_data($plot_id) {
   global $basedir, $cfg, $cronjob, $viewdir, $errors, $queuedir;
}

function validate_form() {
   global $basedir, $cfg, $cronjob, $viewdir, $errors, $queuedir;

   if (!isset($_REQUEST['xmit_lat']) ||
       !isset($_REQUEST['xmit_lon']) ||
       !isset($_REQUEST['xmit_power']) ||
       !isset($_REQUEST['xmit_freq']) ||
       !isset($_REQUEST['xmit_label']) ||
       !isset($_REQUEST['xmit_height']) ||
       !isset($_REQUEST['recv_height']) ||
       !isset($_REQUEST['user_callsign']) ||
       !isset($_REQUEST['user_email'])) {

      $errors = 'Please complete the entire form...';
      die($errors);
    }

    return true;
}

// Insert the submitted values into the HTML as json then return it
// This lets the page refill the form on errors
function populate_form_data($html) {
   global $basedir, $cfg, $cronjob, $viewdir, $errors, $queuedir;

   $new_html = $html;
   $new_html .= "<script>var form_data = '" . json_encode($_REQUEST) . "';</script>";

   if ($errors !== false)
      $new_html .= "<script>var error_data = '" . json_encode($errors) . "';</script>";

   return $new_html;
}

// Load configuration file
if (!file_exists($cfgfile))
   die("You must create a configuration file at $cfgfile or edit \$cfgfile path in this script!");

$cfg = parse_ini_file($cfgfile, true);

if (isset($_REQUEST['plot_id'])) {
   $plot_id = $_REQUEST['plot_id'];
   if (strchr($plot_id, '/') != false || strchr($plot_id, '.') != false)
      die('Invalid plot_id requested.');

   $plot_img = $plotdir . "/" . $plot_id . ".png";

   // plot_id has been sent, show the plot
   if (file_exists($plot_img)) {
      // XXX: Read mime type of the file and send it...
      header("Content-Type: image/png");
      readfile($plot_img);
   } else {
      // XXX: Send a page which refreshes every now and then, up to 15 minutes...
      // XXX: call this views/refreshwait.html
      die("Invalid plot_id parameter '$plot_id' passed. Please check your URL and try again!");
   }
   die();
}

// If request parameters are missing, re-render the HTML template so user can fill the form
if (!isset($_REQUEST['xmit_freq'])) {
   render_view('form');
} else if (validate_form()) {
   enqueue_plot($_REQUEST);
} else {
   $errors = "Please complete the ENTIRE form and try again!";
   render_view('form');
}
die();
?>
