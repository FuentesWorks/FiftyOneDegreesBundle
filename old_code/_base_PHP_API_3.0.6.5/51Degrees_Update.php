<?php

$_fiftyone_degrees_defer_execution = TRUE;

require_once '51Degrees.php';


global $_51d_suppress_update_output;


set_time_limit(0);

header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

for($p = 0; $p < 2048; $p++) {
  echo(' ');
}
flush();

fiftyone_degrees_write_message('Starting update');

if(fiftyone_degrees_start_update())
  fiftyone_degrees_write_message('Update finished.');
else
  fiftyone_degrees_write_message('The update was not successful.');


/**
 * Initiates updating the php code and data if premium licence key enabled.
 */
function fiftyone_degrees_start_update() {
  global $_fiftyone_degrees_data_file_path;
  @set_time_limit(0);
  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $start = $time;
  $dir = dirname(__FILE__);
  $body_post = "";
  $php_current = "phpCurrent=";
  $license_key = fiftyone_degrees_get_licenses($dir);

  foreach ($license_key as $key) {
    if (!preg_match("#[A-Z\d]+#", $key)) {
      fiftyone_degrees_write_message("Please check your license(s), they appear to be invalid.");
      return FALSE;
    }
  }

  $license_key = implode('|', $license_key);

  fiftyone_degrees_write_message('License file retrieved.');
  if ($license_key !== "") {
    $url = "https://51degrees.com/Products/Downloads/Premium.aspx?Download=True&Type=BinaryV3Uncompressed&LicenseKeys=$license_key";
    $update_file = fiftyone_degrees_download_to_file($url, $_fiftyone_degrees_data_file_path);
    if($update_file) {
      return TRUE;
    }
    else {
      fiftyone_degrees_write_message('The update could not be downloaded. You may need to lower security settings or enable the modules for the server to download external files via HTTPS.');
      fiftyone_degrees_print_alternate_source($license_key);
    }
  }
  else {
    fiftyone_degrees_write_message("Please check that your license file(s) are included in the 51Degrees directory and that they are readable by PHP.");
  }
  return FALSE;
}

function fiftyone_degrees_print_alternate_source($licenseKey) {
  fiftyone_degrees_write_message('Alternatively, you can download the data manually from <a href="https://51degrees.mobi/Products/Downloads/Premium.aspx?LicenseKeys='.$licenseKey.'">51Degrees.mobi</a> and place the unzipped contents in '. dirname(__FILE__).'.');
}

/**
 * Retrieves the licence key if available.
 *
 * @param string $dir
 *   Directory to search for licence file.
 * @return array
 *   Array of licence keys. Empty if no licence.
 */
function fiftyone_degrees_get_licenses($dir) {
  ini_set("auto_detect_line_endings", TRUE);
  $licenses = array();
  foreach (glob($dir . '/*.lic') as $file) {
    $handle = fopen($file, "r");
    $line = fgets($handle);
    while ($line !== FALSE) {
      $licenses[] = $line;
      $line = fgets($handle);
    }
  }
  return $licenses;
}

function ends_with($string, $test) {
  $strlen = strlen($string);
  $testlen = strlen($test);
  if ($testlen > $strlen)
    return false;
  return substr_compare($string, $test, -$testlen) === 0;
}

/**
 * Downloads the update file content. Returns a string containing update data if successful, false if there was a failure and "NoData" if there is no
 * new data to add.
 *
 * @param string $url
 *   URL to download update from.
 * @param string $data
 *   Current file hashcodes.
 * @param string $dir
 *   Working directory.
 * @param $optional_headers
 *   Optional HTTP headers.
 * @return string
 *   New file content.
 */
function fiftyone_degrees_download_to_file($url, $file_name) {
  
  $result = FALSE;
  $params = array(
    'http' => array(
      'method' => 'GET',
      'header' => "Accept: application/octet-stream\r\n".
                  "Accept-Language: en-GB,en-US;q=0.8,en;q=0.6\r\n"
    ),
  );
  $name = fiftyone_degrees_get_current_dataset_name();
  if ($name != 'Lite') {
    $data_date = fiftyone_degrees_get_data_date();
    $fdate = date('r', $data_date);
    $params['http']['header'] .= "Last-Modified: $fdate\r\n";
  }
  ini_set('user_agent', '51Degrees PHP Device Data Updater');

  $ctx = stream_context_create($params);
  stream_context_set_params($ctx, array("notification" => "fiftyone_degrees_stream_notification"));
  $file = fopen ($url, "rb", FALSE, $ctx);
  if (fiftyone_degrees_response_is_304($http_response_header)) {
    fiftyone_degrees_write_message("There is no available data file newer than the one currently installed.");
    fiftyone_degrees_write_message("Current data is dated $fdate.");
    return TRUE;
  }
  $temp_file_name = $file_name . '.tmp';
  if ($file) {
  
    $newf = fopen ($temp_file_name, "wb");
    global $fiftyone_degrees_bytes_max;
    if ($newf) {
      $bytes_loaded = 0;
      while(!feof($file)) {
        /* fread used in this way presents two seperate files as the same stream.
        This checks if the content length has been reached and stops the download
        before the file is corrupted. */
        $bytes_left = $fiftyone_degrees_bytes_max - $bytes_loaded;
        if ($bytes_left > 0) {
          $bytes_to_read = 1024 * 8;
          if ($bytes_to_read > $bytes_left) {
            $bytes_to_read = $bytes_left;
          }
          $segment = fread($file, $bytes_to_read);
          $written = fwrite($newf, $segment, $bytes_to_read);
          $bytes_loaded += $written;
        }
        else {
          break;
        }
      }
      fclose($newf);
      
      if (fiftyone_degrees_has_valid_hash($http_response_header, $file_name, $temp_file_name)) {
        $attempt = 0;
        while (!@rename($temp_file_name , $file_name) && $attempt < 20) {
          usleep(500);
          $attempt++;
        }
        if($attempt >= 20) {
          fiftyone_degrees_write_message('The data file cannot be written, probably because the server does not have sufficient permissions, or another process is locking the file.'); 
        }
        else {
          $new_data_date = fiftyone_degrees_get_data_date();
          $new_data_date_f = date('r', $new_data_date);
          fiftyone_degrees_write_message("New data downloaded published on $new_data_date_f");
          $result = TRUE;
        }
      }
      else {
        fiftyone_degrees_write_message('Invalid file hash. The file is probably corrupted.');
      }
      @unlink($temp_file_name);
    }
    fclose($file);
  }
  return $result;
}

function fiftyone_degrees_response_is_304($http_response_header) {
  foreach($http_response_header as $header) {
    if (strpos($header, 'HTTP/1.1 304 Not Modified') === 0) {
      return TRUE;
    }
  }
  return FALSE;
}

function fiftyone_degrees_has_valid_hash ($http_response_header, $current_file, $new_file) {
  foreach($http_response_header as $header) {
    if (strpos($header, 'Content-MD5: ') === 0) {
      $server_hash = str_replace ('Content-MD5: ', '', $header);
      $file_hash = md5_file($new_file);
      return $server_hash === $file_hash;
    }
  }
  return FALSE;
}

function fiftyone_degrees_get_data_date() {
  fiftyone_degrees_set_file_handle();
  global $_fiftyone_degrees_data_file;
  $info = fiftyone_degrees_get_data_info($_fiftyone_degrees_data_file);
  $date_string = "{$info['published_day']}-{$info['published_month']}-{$info['published_year']}";
  $date = strtotime($date_string);
  fclose($_fiftyone_degrees_data_file);
  return $date;
}

function fiftyone_degrees_get_current_dataset_name() {
  fiftyone_degrees_set_file_handle();
  global $_fiftyone_degrees_data_file;
  $headers = fiftyone_degrees_get_headers();
  $name = fiftyone_degrees_get_dataset_name($headers);
  fclose($_fiftyone_degrees_data_file);
  return $name;
}

/**
 * Provides notification messages during update download.
 */
function fiftyone_degrees_stream_notification($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
  global $_fiftyone_degrees_download_percentage;
  switch($notification_code) {
    case STREAM_NOTIFY_RESOLVE:
    case STREAM_NOTIFY_AUTH_REQUIRED:
    case STREAM_NOTIFY_FAILURE:
    case STREAM_NOTIFY_AUTH_RESULT:
    case STREAM_NOTIFY_REDIRECTED:
    case STREAM_NOTIFY_MIME_TYPE_IS:
    case STREAM_NOTIFY_COMPLETED:
      break;

    case STREAM_NOTIFY_CONNECT:
      fiftyone_degrees_write_message('Connecting to upgrade server...');
      $_fiftyone_degrees_download_percentage = 0;
      break;

    case STREAM_NOTIFY_FILE_SIZE_IS:
    // get MB
      if($bytes_max != 0) {
        global $fiftyone_degrees_bytes_max;
        $fiftyone_degrees_bytes_max = $bytes_max;
        $mbytes = number_format($bytes_max / 1000000, 2);
        fiftyone_degrees_write_message("Downloading $mbytes MB.");
      }
      break;

    case STREAM_NOTIFY_PROGRESS:
      global $fiftyone_degrees_bytes_max;
      if ($fiftyone_degrees_bytes_max > 0) {
        $percentage = ($bytes_transferred / $fiftyone_degrees_bytes_max) * 100;
        if($percentage > $_fiftyone_degrees_download_percentage + 10) {
          $_fiftyone_degrees_download_percentage += 10;
          fiftyone_degrees_write_message("Download ".$_fiftyone_degrees_download_percentage."% complete.");
        }
      }
      break;
  }
}

/**
 * Writes a status message to the output stream.
 *
 * $message string
 *   The message to the written.
 */
function fiftyone_degrees_write_message($message) {
  if((isset($_51d_suppress_update_output) && $_51d_suppress_update_output == TRUE) === FALSE) {
    echo $message . "\r\n";
    @flush();
  }
}