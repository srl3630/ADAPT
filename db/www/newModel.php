<?php
# Author: Alexander Kellermann Nieves
# Date: April 9th, 2019
# Summary:
# This file contains the model that's used to identify APTs on the server


# Set up the credentials for the MySQL server
# If you've changed this for your install, you'll need to change these usename
# and passwords. It would be useful if you sourced these from a config file
# that you've set up. This is here just as a reference
$password="hang6Chien1";
$user="db";
$database="adapt";

# Use the recommended interface to connect with the database. In order for this
# to work properly you need to make sure that the file is running on the same server
# that the MySQL DB is on. Otherwise all you'll need to change is the IP address
# from 127.0.0.1 to the new IP Address.
$mysqli = new mysqli("127.0.0.1", $user, $password, $database);



# Store the name of the APTs
$apt_name = [];

# I don't remember
$phases = [];

# I also don't remember
$apts2 = [];

# We're running this query to make sure that the model will have every technique
# regardless of whether it's observered or not. If we don't do this, we can run
# into errors. This came up during testing.
$query="SELECT * FROM attack_patterns";

# Run the query and save the results to $results
$results = $mysqli->query("$query");

# Going through each row of the returns
while($row = $results->fetch_assoc()){

    # Save the name of the apt
    $testVar = $row['Name'];
    
    # Save the phase it's from
    $apt_name = $row['Phase'];
    
    # TODO: I don't think we need this anymore. Try removing.
	array_push($apts2, $apt_name);
	if (!array_key_exists($testVar, $phases)){
		$phases[$testVar] = 1;
	} else {
        $phases[$testVar] += 1;
	}
}

$array_of_techniques = array_keys($phases);

# Get the name of all the groups from the groups_and_techniques table.
# Remember, this table is not generated from the load-data.php like the
# other tables are. You need to run the script from ./PythonRandoms/stixx_gen.py 
# which will output a file similar to groups_techniques.txt 
# Then use the load-groups.php to load it to the database.
$query = "SELECT `groupname` FROM `groups_and_techniques`";
$results = $mysqli->query($query);
$aptnames = [];

while($row = $results->fetch_assoc()){
    array_push($aptnames, $row['groupname']);
}

# Lets create the model right here
$model = [];
$apts = [];

foreach($array_of_techniques as $indi_tech){
       $model[$indi_tech] =[];
}


foreach($aptnames as $name){
    $query = "select techniques from groups_and_techniques where groupname = '$name'";
    $results = $mysqli->query($query);
    $apts[$name] = 0.0; # Use floats because we're going to be doing fractions.

    while($row = $results->fetch_assoc()){
        # explode works similarly to .split() in python
        # basically we get a string of 'techniques' that are delimited by
        # commas, since I want all of these individually, I explode them.
        $techs = explode(',', $row['techniques']);

        foreach($techs as $things){
            # Inside of here, we know what APT we're looking at
            # As well as the techniques, which will be represented
            # in the $things variable.
            if (array_key_exists($things, $model)){
                array_push($model[$things], $name);
            } else {
                # TODO: 
                # We probably don't need either of these if statements
                # because this was created before we put the technique builder
                $model[$things] = [];
                array_push($model[$things], $name);
            }
        }
    }
}

# patterns_seen is the table of the database that has all the events that sysmon
# has seen. This is when we use the model to classify the most likely APT
$query = "SELECT * FROM patterns_seen";
$results = $mysqli->query("$query");

while($row = $results->fetch_assoc()){
    foreach($model[$row['Name']] as $ind_apts){
        $apts[$ind_apts] += (1/count($model[$row['Name']]));
    }
}

# arsort sorts the keys in the 2D array by the value, in descending order.
# that way when we var_dump it, the most likely APT appears at the top.
arsort($apts);
var_dump($apts);


?>