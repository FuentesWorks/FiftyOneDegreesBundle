<?php
require_once 'ExampleMaster.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>51Degrees Image Optimser Gallery</title>
<?php fiftyone_degrees_echo_header(); ?>
</head>
<body>
<?php fiftyone_degrees_echo_menu(); ?>
<div class="content">
<?php

require_once '51Degrees.php';

$headers = fiftyone_degrees_get_headers();
$use_auto = fiftyone_degrees_get_dataset_name($headers) === 'Ultimate';

$files = scandir('Gallery');

echo '<table id="body_Images" class="gallery" cellspacing="0" style="border-collapse:collapse;">';
echo '<tbody>';
$row_count = 0;
foreach ($files as $file) {
  
  if (ends_with($file, '.jpg')) {
    if ($row_count == 0)
      echo '<tr>';
    $img = get_image_panel($use_auto, $file);
    echo $img;
    $row_count++;
    if ($row_count == 3) {
      echo '</tr>';
      $row_count = 0;
    }
  }
}

echo '</tbody>';
echo '</table>';

function ends_with($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
      return TRUE;
  }

  return (substr($haystack, -$length) === $needle);
}

function get_image_panel($use_auto, $image_name) {
  $output = '<td style="width: 33.3%;">';
  $output .= "<a href=\"GalleryImage.php?image=$image_name\" style=\"max-width: 200px;\" >";
  if ($use_auto) {
    $output .= "<img src=\"E.gif\" data-src=\"ImageHandler.php?src=Gallery/$image_name&width=auto\" />";
  }
  else {
    $output .= "<img src=\"ImageHandler.php?src=Gallery/$image_name&width=500\" />";
  }
  $output .= "</a>";
  $output .= "</td>";
  return $output;
}

?>
</div>
<script src="51Degrees.core.js.php"></script>
<?php
if ($use_auto) {
?>
<script>
  new FODIO();
</script>
<?php
}
?>
</body>
</html>
