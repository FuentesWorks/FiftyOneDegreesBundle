<?php

require_once 'ExampleMaster.php';
require_once '51Degrees_metadata.php';

?>
<!DOCTYPE html>
<html>
<head>
<title>51Degrees Property Dictionary</title>
<?php fiftyone_degrees_echo_header(); ?>
</head>
<body>
<?php fiftyone_degrees_echo_menu(); ?>
<div class="content">
<div class="propertyDictionary">
<p>The list of properties and descriptions explainations how to use the available device data.</p>
<?php
echo '<table class="item" cellspacing="0" style="border-collapse:collapse;">';
foreach ($_51d_meta_data as $property => $data) {
  echo '<tr><td>';
  echo '<div class="property">';
  echo "<span>$property</span>";
  echo '</div>';
  if (is_array($data) && array_key_exists('Description', $data)) {
    echo '<div class="description">';
    echo "<span>{$data['Description']}</span>";
    echo '</div>';
  }
  echo '</td></tr>';
}
echo '</table>';
?>
</div>
</div>
</body>
</html>