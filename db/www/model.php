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
 
# Create an array that will hold information regarding the techniques and how
# many tactics they're associated with.
$phases = [];
$apts = [];
   
 
$mysqli->select_db($database) or die ("Unable to select database");
$query="SELECT * FROM attack_patterns";
$results = $mysqli->query("$query");
while($row = $results->fetch_assoc()){
    $testVar = $row['Name'];
    $apt_name = $row['Phase'];
    array_push($apts, $apt_name);
    if (!array_key_exists($testVar, $phases)){
        #echo $testVar . '<br />';
        $phases[$testVar] = 1;
    } else {
    $phases[$testVar] += 1;
    }
}
 
# Here we're going to take $apts, which has all the apts (11 of them at the time of
# writing) and get a unique list of them. At that point we're going to want to have a
# dictionary value matching the apts and how often we see them. We initalize each value
# to zero because we haven't looked at the database to fill them with real values
$apts = array_unique($apts);
$apt_match = [];
foreach($apts as $apt_val){
    $apt_match[$apt_val] = 0;
}
 
# We don't care about the values in array_of_tecniques, so we just dump the array keys into a new array.
$array_of_techniques = array_keys($phases);
 
# This will be the final model we can perform lookups on. In theory
# running a query against this will return something like
# 'Logon Scripts' => ['Lateral Movement', 'Persistence']
# $apt_match is where we store the matches that we find when we use
# this model.
$model_lookup = [];
foreach($array_of_techniques as $key){
    $phase_query = "SELECT phase FROM attack_patterns WHERE name = '$key'";
    $results = $mysqli->query("$phase_query");
    #echo $phase_query . '<br />';
    while($row = $results->fetch_assoc()){
        $temp_var = $row['phase'];
        #echo $temp_var . '<br />';
        if(array_key_exists($key, $model_lookup)){
            array_push($model_lookup[$key], $temp_var);
        } else {
            $model_lookup[$key] = [$temp_var];
        }
    }
}
 
#var_dump($model_lookup);
# Now we have all the possible keys (Which are the tactics)
# We want to build something that looks like the following
# |               | lateral Movement | Discovery    | Persistance |
# | ....          |                  |              |             |
# | logon scripts |         x        |              |      x      |
#
 
 
# Closes the connection
echo "Lookup Table Generated." . '</br>';
echo "Now generating "
 
# Now we're going to look at the database.
$query = "SELECT * FROM patterns_seen";
$results = $mysqli->query("$query");
 
# $row['Name'] is going to return a technique back. Now we need to perform a lookup
# on the lookup_table to see which apts match with that result.
while($row = $results->fetch_assoc()){
    foreach($model_lookup[$row['Name']] as $thing){
        $apt_match[$thing] += 1;
    }
}
 
# apt_match now contains the likely hood of each apt
var_dump($apt_match);
 
$mysqli->close();
?>