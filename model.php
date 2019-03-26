<?php
# Author: Alexander Kellermann Nieves
# Date: March 20th, 2019

# Here's how the table looks like
# | ID | Name | Phase | URL | ExternalID |


# Set up the credentials for the MySQL server
$password="hang6Chien1";
$user="db";
$database="adapt";
$meta_data="attack_patterns";

# Set up the connection to the server (which should exist on the local host)
$mysqli = new mysqli("127.0.0.1", $user, $password, $database);

# Create an array
$phases = [];
	

$mysqli->select_db($database) or die ("Unable to select database");
$query="SELECT * FROM attack_patterns";
$results = $mysqli->query("$query");
while($row = $results->fetch_assoc()){
	$testVar = $row['Name'];

	if (!array_key_exists($testVar, $phases)){
		echo $testVar . '<br />';
		$phases[$testVar] = 1;
	} else {
	$phases[$testVar] += 1;
	}
}
var_dump($phases);

$array_of_techniques = array_keys($phases);
var_dump($array_of_techniques);

# This will be the final model we can perform lookups on. In theory
# running a query against this will return something like
# 'Logon Scripts' => ['Lateral Movement', 'Persistence']
$model_lookup = [];
foreach($array_of_techniques as $key){
	$phase_query = "SELECT phase FROM attack_patterns WHERE name = '$key'";
	$results = $mysqli->query("$phase_query");
	#echo $phase_query . '<br />';
	while($row = $results->fetch_assoc()){
		$temp_var = $row['phase'];
		echo $temp_var . '<br />'; 
		if(array_key_exists($key, $model_lookup)){
			array_push($model_lookup[$key], $temp_var);
		} else {
			$model_lookup[$key] = [$temp_var];
		}
	}
}
var_dump($model_lookup);
# Now we have all the possible keys (Which are the tactics)
# We want to build something that looks like the following
# |               | lateral Movement | Discovery    | Persistance |
# | ....          |                  |              |             |
# | logon scripts |         x        |              |      x       |
#


# Closes the connection
$mysqli->close();
?>