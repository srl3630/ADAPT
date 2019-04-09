<?php
# Set up the credentials for the MySQL server
$password="hang6Chien1";
$user="db";
$database="adapt";

$mysqli = new mysqli("127.0.0.1", $user, $password, $database);
$query = "SELECT `groupname` FROM `groups_and_techniques`";
$results = $mysqli->query($query);

$aptnames = [];

while($row = $results->fetch_assoc()){
    array_push($aptnames, $row['groupname']);
}

# Lets create the model right here
$model = [];
$apts = [];

foreach($aptnames as $name){
    #$query = "SELECT `techniques` FROM `groups_and_techniques` WHERE `groupname` = $name";
    $query = "select techniques from groups_and_techniques where groupname = '$name'";
    $results = $mysqli->query($query);
    $apts[$name] = 0.0;

    while($row = $results->fetch_assoc()){
       # Inside of here we have a 
        $techs = explode(',', $row['techniques']);
        #echo $name . '</br>' . "=======" . '</br>';
        foreach($techs as $things){
            # Inside of here, we know what APT we're looking at
            # As well as the techniques, which will be represented
            # in the $things variable.
            if (array_key_exists($things, $model)){
                array_push($model[$things], $name);
            } else {
                $model[$things] = [];
                array_push($model[$things], $name);
            }
        }
    }
}

$query = "SELECT * FROM patterns_seen limit 2000";
$results = $mysqli->query("$query");

while($row = $results->fetch_assoc()){
    foreach($model[$row['Name']] as $ind_apts){
        $apts[$ind_apts] += (1/count($model[$row['Name']]));
    }
}

var_dump($apts);



?>