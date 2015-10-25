<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;
use \Tsugi\Util\Mersenne_Twister;

$sanity = array(
  'urllib' => 'You should use urllib to retrieve the data from the API',
  'urlencode' => 'You should use urlencode add parameters to the API url',
  'json' => 'You should use the json library to parse the API data'
);

// Compute the stuff for the output
$code = 42;
$MT = new Mersenne_Twister($code);
$sample = $MT->shuffle($LOCATIONS);
$sample_location = $sample[0];

$code = $USER->id+$LINK->id+$CONTEXT->id;
$MT = new Mersenne_Twister($code);
$actual = $MT->shuffle($LOCATIONS);
$actual_location = $actual[0];

// Retrieve the data
$url = curPageUrl();
$api_url = str_replace('index.php','data/geojson',$url);
$google_api = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=University+of+Michigan';
$sample_url = $api_url . '?sensor=false&address=' . urlencode($sample_location);
$actual_url = $api_url . '?sensor=false&address=' . urlencode($actual_location);

$sample_data = Net::doGet($sample_url);
$sample_count = strlen($sample_data);
$response = Net::getLastHttpResponse();
$sample_json = json_decode($sample_data);
if ( $response != 200 || $sample_json == null ) {
    die("Response=$response url=$sample_url json_error=".json_last_error_msg());
}
// echo("<pre>\n");echo(jsonIndent(json_encode($sample_json)));echo("</pre>\n");
$sample_place =  $sample_json->results[0]->place_id;

$actual_data = Net::doGet($actual_url);
$actual_count = strlen($actual_data);
$response = Net::getLastHttpResponse();
$actual_json = json_decode($actual_data);
if ( $response != 200 ) {
    die("Response=$response url=$actual_url json_error=".json_last_error_msg());
}
$actual_place =  $actual_json->results[0]->place_id;
// echo("sample_place=$sample_place actual_place=$actual_place");


$oldgrade = $RESULT->grade;
if ( isset($_POST['place_id']) && isset($_POST['count']) && isset($_POST['code']) ) {
    if ( $_POST['place_id'] != $actual_place ) {
        $_SESSION['error'] = "Your place_id did not match";
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( $_POST['count'] != $actual_count ) {
        $_SESSION['error'] = "Your count did not match";
        header('Location: '.addSession('index.php'));
        return;
    }

    $val = validate($sanity, $_POST['code']);
    if ( is_string($val) ) {
        $_SESSION['error'] = $val;
        header('Location: '.addSession('index.php'));
        return;
    }

    $RESULT->setJsonKey('code', $_POST['code']);

    LTIX::gradeSendDueDate(1.0, $oldgrade, $dueDate);
    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// echo($goodsha);
if ( $LINK->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($LINK->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<p>
<b>Calling a JSON API</b>
</p>
In this assignment you will write a Python program somewhat similar to 
<a href="http://www.pythonlearn.com/code/geojson.py" target="_blank">http://www.pythonlearn.com/code/geojson.py</a>.  
The program will prompt for a location, contact a web service and retrieve
JSON for the web service and parse that data, and retrieve the first
<b>place_id</b> from the JSON.
A place ID is a textual identifier that uniquely identifies a place as
within Google Maps.
</p>
<p>
<b>API End Points</b>
</p>
<p>
You have two choice in terms of the API endpoint you use for this assignment.
You can use the Google API directly at this URL:
<pre>
<a href="<?= $google_api ?>" target="_blank"><?= $google_api ?></a>
</pre>
If you cannot acesss the Google API, do not want to use the Google API,
or do not want to exceed Google's
rate limits we have made a copy of a <b>subset</b> of the Google geo location 
data at this URL:
<pre>
<a href="<?= $api_url ?>" target="_blank"><?= $api_url ?></a>
</pre>
This API uses the same parameters (sensor and address) as the Google API.  
If you visit the URL with no parameters, you get a list of all of the 
address values which can be used with our API.
</p>
<p>
To call the API, you need to provide a <b>sensor=false</b> parameter and
the address that you are requesting as the <b>address=</b> parameter that is 
properly URL encoded using the <b>urllib.urlencode()</b> fuction as shown in 
<a href="http://www.pythonlearn.com/code/geojson.py" 
target="_blank">http://www.pythonlearn.com/code/geojson.py</a>
</p>
<p><b>Test Data</b></p>
<p>
You can test to see if your program is working with a 
location of "<?= $sample_location ?>" which will have a 
<b>place_id</b> of "<?= $sample_place ?>" and 
a character count of <?= $sample_count ?>.
<pre>
$ python solution.py
Enter location: <?= $sample_location ?> 
Retrieving http://...
Retrieved <?= $sample_count ?> characters
Place id <?= $sample_place ?> 
</pre>
</p>
<p><b>Turn In</b></p>
<p>
Please run your program to find the <b>place_id</b> for "<?= $actual_location ?>" 
and enter the <b>place_id</b>
 and your Python code below.
Hint: The first seven characters of the <b>place_id</b>
are "<?= substr($actual_place,0,7) ?> ..."<br/>
<form method="post">
place_id: <input type="text" size="40" name="place_id"></br>
character count: <input type="text" size="10" name="count">
<input type="submit" value="Submit Assignment"><br/>
Python code:<br/>
<textarea rows="20" style="width: 90%" name="code"></textarea><br/>
</form>