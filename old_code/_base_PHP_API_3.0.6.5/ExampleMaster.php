<?php

function fiftyone_degrees_echo_header() {
?>
<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
<link id="LinkStyleDefault" runat="server" rel="Stylesheet" type="text/css" href="Default.css" />
<?php
}

function fiftyone_degrees_echo_properties($properties) {
  foreach ($properties as $property => $value) {
    if (is_array($value))
      $value = implode('|', $value);
    echo "<p>$property: $value</p>";
  }
}

function fiftyone_degrees_echo_menu() {
  $pathinfo = pathinfo($_SERVER['PHP_SELF']);
  $dir = $pathinfo['dirname'];
  ?>
  <div class="menu">
    <ul>
      <li class="">
        <a id="Menu_MenuItem_0" href="<?php echo "$dir"; ?>/Tester.php">Tester</a>
      </li>
      <li class="">
        <a id="Menu_MenuItem_1" href="<?php echo "$dir"; ?>/Dictionary.php">Properties</a>
      </li>
      <li class="">
        <a id="Menu_MenuItem_2" href="<?php echo "$dir"; ?>/Devices.php">Explore Devices</a>
      </li>
      <li class="">
        <a id="Menu_MenuItem_3" href="<?php echo "$dir"; ?>/Gallery.php">Image Gallery</a>
      </li>
    </ul>
  </div>
  <?php
}