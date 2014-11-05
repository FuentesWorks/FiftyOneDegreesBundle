
<?php
session_start();

$_fiftyone_degrees_needed_properties = array('IsMobile', 'HardwareModel', 'PlatformName', 'BrowserName');

$_fiftyone_degrees_data_file_path = '51Degrees-Ultimate.dat';

require_once '51Degrees.php';

//require_once '51Degrees_metadata.php';


?>

<html>
  <head>
    <title>51Degrees PHP Validation</title>
    <script src="51Degrees.core.js.php"></script>
    
  </head>
  <body><script>
      // new FODBW();
    </script>
    <?php
      var_dump($_51d);
    ?>
    <table>
      <tr>
        <td>Device Id</td>
        <td><device_id><?php echo $_51d['DeviceId']; ?></device_id></td>
      </tr>
      <tr>
        <td>Useragent</td>
        <td><useragent><?php echo $_SERVER['HTTP_USER_AGENT']; ?></useragent></td>
      </tr>
      <tr>
        <td>Method</td>
        <td><method><?php echo $_51d['Method']; ?></method></td>
      </tr>
      <tr>
        <td>Confidence</td>
        <td><confidence><?php echo $_51d['Confidence']; ?></confidence></td>
      </tr>
      <tr>
        <td>Signatures Checked</td>
        <td><signatures_checked><?php echo $_51d['SignaturesChecked']; ?></signatures_checked></td>
      </tr>
      <tr>
        <td>Time</td>
        <td><time><?php echo $_51d['Time']; ?></time></td>
      </tr>
      <tr>
        <td>Selected Properties:</td>
      </tr>
      <?php
      foreach($_fiftyone_degrees_needed_properties as $prop) {
        echo '<tr>';
        echo "<td>$prop</td>";
        echo "<td>{$_51d[$prop]}</td>";
        echo '</tr>';
      }
      ?>
    </table>
    
    <a href="FD.php">Reload</a>
    
    <img src="E.gif" data-src="ImageHandler.php?src=Earth.jpg&width=auto" />
    <img src="E.gif" data-src="ImageHandler.php?src=Test/Test.jpg&width=100" />
    <img src="E.gif" data-src="ImageHandler.php?src=Test/Test.jpg&width=auto" />
    
  </body>
  
  <script>
  // new FODIO(); 
  // new FODPO();
  </script>
</html>
