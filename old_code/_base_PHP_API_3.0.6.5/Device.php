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
<?php fiftyone_degrees_echo_menu(); ?>
<div id="Content">

<?php
if (array_key_exists('ProfileId', $_GET)) {
  $profile_id = $_GET['ProfileId'];
  $headers = fiftyone_degrees_get_headers();
  $profile = fiftyone_degrees_get_profile_from_id($profile_id, $headers);
  $properties = fiftyone_degrees_get_profile_property_values($profile, NULL, $headers);
  fiftyone_degrees_echo_properties($properties);
}

?>

</div>
</body>
</html>