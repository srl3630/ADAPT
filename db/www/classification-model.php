<?php
# Set up the credentials for the MySQL server
$password = "hang6Chien1";
$user = "db";
$database = "adapt";

# Set up the connection to the server (which should exist on the local host)

$mysqli = new mysqli("127.0.0.1", $user, $password, $database);

# Build a list of distinct computer names that we might want to run the model
# on
$query = "SELECT DISTINCT ComputerName FROM patterns_seen";
$results = $mysqli->query($query);

# Here I end the php code to insert HTML in order to prepare the form.
?>
<h3> Select the Computers you want to run the model against below </h3>
<form action="#" method="post">

    <?php
    while ($row = $results->fetch_assoc()) :
        ?>

        <input type="checkbox" name="computers[]" value="<?php echo $row['ComputerName']; ?>"><?php echo $row['ComputerName']; ?></input></br>

    <?php endwhile; ?>
    <!-- !!! is a value used specifically for the php script. Check the logic in the model to see how it's implemented -->
    <input type="checkbox" name="computers[]" value="!!!">Run against entire infrastructure</input></br>
    <input type="submit" name="submit" value="Submit" />
</form>
<?php

# When the model is first accessed by webpage, there haven't been any
# checkboxes selected, so we don't need to run the model against anything.
if(isset($_POST['computers'])){
    run_model($_POST['computers'], $mysqli);
}

# this is the beginning of the function that runs the model. It takes two parameters
# param $array_of_computers
# Array of the computers: e.g., WINDOWS1 WINDOWS2
# param $mysqli
# the mysqli created out of this scope. That way we don't need to recreate it.
function run_model($array_of_computers, $mysqli)
{

    # Store the name of the APTs
    $apt_name = [];
    $phases = [];
    $apts2 = [];
    # We're running this query to make sure that the model will have every technique
    # regardless of whether it's observered or not. If we don't do this, we can run
    # into errors. This came up during testing.
    $query = "SELECT * FROM attack_patterns";
    $results = $mysqli->query("$query");
    while ($row = $results->fetch_assoc()) {
        $testVar = $row['Name'];
        $apt_name = $row['Phase'];
        array_push($apts2, $apt_name);
        if (!array_key_exists($testVar, $phases)) {
            $phases[$testVar] = 1;
        }
    }

    # Now we have all the possible techniques.
    $array_of_techniques = array_keys($phases);

    # Run the query and save the results to $results
    $query = "SELECT `groupname` FROM `groups_and_techniques`";
    $results = $mysqli->query($query);
    $aptnames = [];

    while ($row = $results->fetch_assoc()) {
        array_push($aptnames, $row['groupname']);
    }

    # Lets create the model right here
    $model = [];
    $apts = [];

    foreach ($array_of_techniques as $indi_tech) {
        $model[$indi_tech] = [];
    }


    foreach ($aptnames as $name) {

        $query = "select techniques from groups_and_techniques where groupname = '$name'";
        $results = $mysqli->query($query);
        $apts[$name] = 0.0;

        while ($row = $results->fetch_assoc()) {

            $techs = explode(',', $row['techniques']);

            foreach ($techs as $things) {
                # Inside of here, we know what APT we're looking at
                # As well as the techniques, which will be represented
                # in the $things variable.
                if (array_key_exists($things, $model)) {
                    array_push($model[$things], $name);
                } else {
                    $model[$things] = [];
                    array_push($model[$things], $name);
                }
            }
        }
    }
    # $array_of_computers contains all the computers we want to run against the WHERE clause.
    # The next block of code just constructs the MYSQL query we want to use. It looks confusing
    # but it's just a series of string concats.
    $long_string = "WHERE ComputerName = '" . array_pop($array_of_computers) . "'";
    foreach($array_of_computers as $computer_name){
        $long_string .= " or ComputerName = " . " '" . $computer_name . "'";
    }
    $long_string .= ";";

    # Here's a clever trick to implement a PHP only solution to a check all box.
    # What we do is set the value of a check all box to something no windows box should ever be 
    # called. This means if we see it, we know that we just need to run the query on the check
    # all. Which is just a semi-colon, no WHERE statement.
    if (strpos($long_string, '!!!') !== false) {
        $long_string = ";";
    }

    $query = "SELECT * FROM patterns_seen " . $long_string;
    echo "Query ran:" . '</br>';
    echo $query . '</br>';
    $results = $mysqli->query("$query");

    # Here's where the actual mathematics goes on. Also where the program spends the majority
    # of its time. If you're looking to change the model, this is a good place to start.
    while ($row = $results->fetch_assoc()) {
        foreach ($model[$row['Name']] as $ind_apts) {
            $apts[$ind_apts] += (1 / count($model[$row['Name']]));
        }
    }
    arsort($apts);
    var_dump($apts);
} # This is the end of the function

?>