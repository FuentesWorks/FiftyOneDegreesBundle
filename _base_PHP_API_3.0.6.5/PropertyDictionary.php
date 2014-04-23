<?php

require_once '51Degrees_metadata.php';

?>

<html>
	<head>
		<title>51Degrees Property Dictionary</title>
	</head>
	<body>
		<h1>Property Dictionary</h1>
		<p>The list of properties and descriptions explain how to use the available device data. 
		Use the [+] icon to display possible values associated with the property. 
		Use the (?) icons to find out more information about the property or value.
		</p>
		<table>
		<?php
		foreach($_51d_meta_data as $name => $property) {
			echo '<tr>';
			if(isset($property['Url']))
				echo "<td><a href=\"{$property['Url']}\">$name</a></td>";
			else
				echo "<td>$name</td>";
			echo "<td>{$property['Description']}";
			
			if(isset($property['Values'])) {
				echo '<div>';
				foreach($property['Values'] as $value_name => $value) {
					if(isset($value['Url']))
						echo "<span><a href=\"{$value['Url']}\">$value_name, </a></span>";
					else
						echo "<span>$value_name, </span>";
				}
				echo '</div>';
			}
			echo "</td>";
			echo '</tr>';
			
		}
		?>
		</table>
		
	</body>
</html>
		