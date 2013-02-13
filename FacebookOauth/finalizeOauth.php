<?php
/**
 * Temboo PHP SDK example usage: Facebook oAuth
 *
 * Demonstrates how to use the Temboo PHP SDK to perform oAuth
 * authentication with Facebook.
 *
 * PHP version 5
 *
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


/**
 * Your Temboo account name (note that this is NOT your username).
 */
define('TEMBOO_ACCOUNT_NAME', 'PUT YOUR TEMBOO ACCOUNT NAME HERE!');
/**
 * Your Temboo App key name. You can get this from https://live.temboo.com/account/
 */
define('TEMBOO_APP_KEY_NAME', 'PUT YOUR TEMBOO APP KEY NAME HERE!');

/**
 * Your Temboo App key value. You can get this from https://live.temboo.com/account/
 */
define('TEMBOO_APP_KEY_VALUE', 'PUT YOUR TEMBOO APP KEY VALUE HERE!');

 // Include the Temboo PHP SDK; this can be downloaded from http://www.temboo.com/download
require 'php-sdk/src/temboo.php';
require 'php-sdk/src/Library/temboo.facebook.php';

// Instantiate a Temboo Session object; you'll need a (free) Temboo account and credentials.
$session = new Temboo_Session(TEMBOO_ACCOUNT_NAME, TEMBOO_APP_KEY_NAME, TEMBOO_APP_KEY_VALUE);

// See if the access token is already set in a cookie
$accessToken = $_COOKIE['TembooFacebookAccessToken'];

// If the access token isn't present, we need to complete the oAuth dance
if(is_null($accessToken)) {
	$accessToken = getAccessToken($session);
}

/**
 * Function to retrieve an access token for Facebook, via the Temboo SDK
 */
function getAccessToken($session) {

	// Get the callback ID from the cookie stored by initializeOauth.php
	$callbackID = $_COOKIE['TembooCallbackID'];

	// Make sure there's a valid callback ID cookie stored; if not, we need to tell the user to re-authenticate
	if(is_null($callbackID)) {
		echo "Oops! It looks like you need to re-authenticate with Facebook. <a href='initializeOauth.php'>Click Here</a>.";
		return;
	}

	// Instantiate the choreography, using a previously instantiated Temboo_Session object
	$finalizeOAuth = new Facebook_OAuth_FinalizeOAuth($session);

	// Get an input object for the choreo
	$finalizeOAuthInputs = $finalizeOAuth->newInputs();

	// Set inputs for the Choreo:
	// Specify your Temboo credentials, that will be used to securely register for and retrieve callback data from Facebook
	$finalizeOAuthInputs->setAccountName(TEMBOO_ACCOUNT_NAME)->setAppKeyName(TEMBOO_APP_KEY_NAME)->setAppKeyValue(TEMBOO_APP_KEY_VALUE);
	
	// Specify your Facebook App ID and App Secret; you can get this from https://developers.facebook.com/apps
	$finalizeOAuthInputs->setAppID("PUT YOUR FACEBOOK APP ID HERE!")->setAppSecret("PUT YOUR FACEBOOK APP SECRET HERE!");

	// Specify the callback ID (created and passed over from initializeOauth.php)
	$finalizeOAuthInputs->setCallbackID($callbackID);

	// Execute choreography and get results
	$finalizeOAuthResults = $finalizeOAuth->execute($finalizeOAuthInputs)->getResults();

	// Great; now we have a Facebook access token!
	$accessToken = $finalizeOAuthResults->getAccessToken();

	// Store the access token in a cookie (set to expire in 10 minutes)
	setcookie("TembooFacebookAccessToken", $accessToken, time()+600);

	return $accessToken;
}

// Now, execute a choreo to get a list of Facebook likes

// Instantiate the choreography
$likesChoreo = new Facebook_Reading_Likes($session);

// Get an input object for the choreo
$likesInputs = $likesChoreo->newInputs();


// Set inputs: all we need is the access token retrived above
$likesInputs->setAccessToken($accessToken);

// Execute choreography and get results
$likesResults = $likesChoreo->execute($likesInputs)->getResults();

// get the set of Likes objects
$likes = $likesResults->getLikes();
?> 

<html>
	<head>
		<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,700"/>
		<style>
			body{
					color:#333;
					font-family:"Open Sans","Trebuchet MS",Arial,sans-serif;
					font-size:12px;
					;margin:10px auto;
					min-width:800px;
					max-width:1000px;
					padding:0 10px;
				}
			
			table{
				background-color: #666;
			}
			th{
				font-weight: bold;
				background-color: #888;
			}
			td {
				background-color: #fff;
			}
		</style>
	</head>
	<body>
		You've successfully authenticated with Facebook via oAuth. Your oAuth access token is:
		<br/>
		<?php echo $accessToken ?>
		<br /><br />
		Your Facebook Likes:
		<table border="0">
			<tr>
				<th>Name</th><th>Category</th><th>Created Time</th>
			</tr>
			<?php 
				$html = "";
				foreach ($likes as $like) {
		   			$html .= "<tr><td>" . $like->getName() . "</td><td>" . $like->getCategory() . "</td><td>" . $like->getCreatedTime() . "</td></tr>";
		    	}

		    echo $html
			?>
		</table>
	</body>
</html>


