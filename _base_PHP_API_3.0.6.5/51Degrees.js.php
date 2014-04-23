<?php

/**
 * @file
 * Provides image optimiser, bandwidth monitoring and feature detection
 * functionality by generating javascript from 51Degrees device data.
 */

header('Content-type: text/javascript');

header('Vary: User-Agent');
header('Cache-Control: public');
// Expires: Thu, 01 Dec 1994 16:00:00 GMT ----- SET TO NEXT UPDATE DATE.
// Last-Modified: Tue, 15 Nov 1994 12:45:26 +0000 ----- SET TO PUBLISHED DATE.

// $hash = HASH OF DATA SET METADATA AND USERAGENT.
//header("ETag: $hash");.

if (isset($_SESSION) === FALSE)
  session_start();

global $_fiftyone_degrees_defer_execution;
$_fiftyone_degrees_defer_execution = TRUE;
require_once '51Degrees.php';

echo fiftyone_degrees_get_script();

/**
 * Generates javascript for client functionality from 51Degrees data.
 */
function fiftyone_degrees_get_script() {
  global $_fiftyone_degrees_needed_properties;
  $headers = fiftyone_degrees_get_headers();

  $_fiftyone_degrees_needed_properties = array();

  $profile_scripts = array();
  $other_scripts = array();

  $output = "\n";

  for ($i = 0; $i < $headers['property_count']; $i++) {
    $property = fiftyone_degrees_read_property($i, $headers);
    if ($property['value_type_id'] == 4) {
      $_fiftyone_degrees_needed_properties[] = $property['name'];
      if (strpos($property['name'], 'Profile') !== FALSE) {
        $profile_scripts[] = $property['name'];
      }
      else {
        $other_scripts[] = $property['name'];
      }
    }
  }

  $_51d = fiftyone_degrees_get_device_data($_SERVER['HTTP_USER_AGENT']);

  foreach ($other_scripts as $property) {
    if (isset($_51d[$property])) {
      $output .= $_51d[$property];
      $output .= "\n";
    }
  }

  $output .= "function FODPO() { var profileIds = new Array();\n";
  $c = count($profile_scripts);
  if (count($profile_scripts) > 0 && (isset($_SESSION['51D_ProfileIds']) === FALSE || strlen($_SESSION['51D_ProfileIds']) == 0)) {
    foreach ($profile_scripts as $property) {
      if (isset($_51d[$property])) {
        $output .= $_51d[$property];
        $output .= "\n";
      }
    }
    $output .= "document.cookie = \"51D_ProfileIds=\" + profileIds.join(\"|\");";
  }
  $output .= "}\n";

  return str_replace('document.addEventListener("load", this.loadComplete.bind(this), true);', 'window.addEventListener("load", this.loadComplete.bind(this), true);', $output);
}
