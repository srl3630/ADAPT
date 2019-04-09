<?php
# Set up the credentials for the MySQL server
$password="hang6Chien1";
$user="db";
$database="adapt";
$meta_data="attack_patterns";

# Set up the connection to the server (which should exist on the local host)

$mysqli = new mysqli("127.0.0.1", $user, $password, $database);
$query = "CREATE TABLE groups_and_techniques (groupname VARCHAR(20), techniques TEXT)";
$mysqli->query($query);

$file = file("./groups_techniques.txt", FILE_USE_INCLUDE_PATH);
var_dump($file);

$groupname = "a";
$techniques = "";
$num = 1;
foreach($file as $value){
    switch($num) {
        case 1:
            $groupname = trim(ltrim($value, '%'));
            $num = 2;
            break;
        case 2:
            $techniques = trim($value);
            $num = 3;
            break;
        case 3:
            #echo "$groupname : $techniques" . '</br>';
            $query = "INSERT INTO `groups_and_techniques` VALUES ('$groupname', '$techniques')";
            if (!$mysqli->query($query)){
                echo $mysqli->error;
            }
            $num = 1;
            break;
    }
}

?>