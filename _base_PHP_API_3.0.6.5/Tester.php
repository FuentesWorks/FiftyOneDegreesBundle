<?php
require_once 'ExampleMaster.php';
require_once '51Degrees.php';
?>

<html>
<head>
<title>51Degrees Image Optimser Gallery</title>
<?php fiftyone_degrees_echo_header(); ?>
</head>
<body>
<?php
fiftyone_degrees_echo_menu();
if (array_key_exists('ua', $_GET)) {
  $ua = $_GET['ua'];
}
else {
  $ua = $_SERVER['HTTP_USER_AGENT'];
}
?>
<div id="Content">
<form action="Tester.php" method="get">
  Useragent: <input type="text" name="ua" value="<?php echo $ua; ?>" />
  <input type="submit" value="Submit">
</form>
<?php

$properties = fiftyone_degrees_get_device_data($ua);
fiftyone_degrees_echo_properties($properties);

?>
</div>
</body>
</html>