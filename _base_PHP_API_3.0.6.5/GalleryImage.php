<?php
require_once 'ExampleMaster.php';
?>

<html>
<head>
<title>51Degrees Image Optimser Gallery</title>
<?php fiftyone_degrees_echo_header(); ?>
</head>
<body>
<?php fiftyone_degrees_echo_menu(); ?>
<div id="Content">
<?php

require_once '51Degrees.php';

$headers = fiftyone_degrees_get_headers();
$use_auto = fiftyone_degrees_get_dataset_name($headers) === 'Ultimate';
$file = $_GET['image'];

$path = 'Gallery/' . $file;

if (file_exists($path)) {
  $img = get_image_panel($use_auto, $file);
   echo $img;
}

function get_image_panel($use_auto, $image_name) {
  if ($use_auto) {
    $output = "<img src=\"E.gif\" data-src=\"ImageHandler.php?src=Gallery/$image_name&width=auto\" class=\"GalleryImage\"/>";
  }
  else {
    if (array_key_exists('ScreenPixelsWidth', $_51d) && is_numeric($_51d['ScreenPixelsWidth']))
      $width = $_51d['ScreenPixelsWidth'];
    else
      $width = 800;
    $output = "<img src=\"ImageHandler.php?src=Gallery/$image_name&width=$width\" class=\"GalleryImage\" />";
  }
  return $output;
}

?>
</div>
<script src="51Degrees.core.js.php"></script>
<script>
  new FODIO();
</script>
</body>
</html>