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


/************** MODIFY THESE CONSTANTS TO MATCH YOUR TEMBOO AND FACEBOOK CREDENTIALS **************/

/**
 * Your Temboo account name (note that this is NOT your username).
 */
define('TEMBOO_ACCOUNT_NAME', '**** UPDATE THIS VALUE ***');

/**
 * Your Temboo App key name.
 * After signing up for a Temboo account, you can get this from https://live.temboo.com/account/
 */
define('TEMBOO_APP_KEY_NAME', '**** UPDATE THIS VALUE ***');

/**
 * Your Temboo App key value. You can get this from https://live.temboo.com/account/
 * After signing up for a Temboo account, you can get this from https://live.temboo.com/account/
 */
define('TEMBOO_APP_KEY_VALUE', '**** UPDATE THIS VALUE ***');

/**
 * Your Facebook App ID. 
 * After registering a Facebook app (see https://live.temboo.com/library/Library/Facebook/OAuth/ for configuration instructions)
 * you can get this from https://developers.facebook.com/apps
 */
define('FACEBOOK_APP_ID', '**** UPDATE THIS VALUE ***');

/**
 * Your Facebook App Secret. 
 * After registering a Facebook app (see https://live.temboo.com/library/Library/Facebook/OAuth/ for configuration instructions)
 * you can get this from https://developers.facebook.com/apps
 */
define('FACEBOOK_APP_SECRET', '**** UPDATE THIS VALUE ***');

/**
 * The location at which this page is accessible; used to redirect users back to this
 * page after they click "allow" in Facebook
 */
define('PAGE_LOCATION', 'http://localhost:8888/oauth/facebookOauth.php'); // **** UPDATE THIS VALUE IF THE PAGE LIVES IN A DIFFERENT LOCATION ***

/************** END CONSTANTS; NOTHING BELOW THIS POINT SHOULD NEED TO BE CHANGED **************/



 // Include the Temboo PHP SDK; this can be downloaded from http://www.temboo.com/download
require 'php-sdk/src/temboo.php';
require 'php-sdk/src/Library/temboo.facebook.php';

// Instantiate a Temboo Session object; you'll need a (free) Temboo account and credentials.
$session = new Temboo_Session(TEMBOO_ACCOUNT_NAME, TEMBOO_APP_KEY_NAME, TEMBOO_APP_KEY_VALUE);

// Check whether we recieved a "finalize=true" querystring parameter. If we did get a 
// "finalize=true" parameter, and we can access a Temboo Callback ID that was previously
// stored in a cookie, we should finalize the oAuth dance (the user has already clicked "allow");
// otherwise, we should start the oAuth dance and prompt the user to click "allow"
$finalize = $_GET["finalize"];
$callbackID = $_COOKIE["TembooCallbackID"];

// If we're doing the first part of the oAuth dance, we'll need to
// send the user to an authorization URL to allow access to their account
$authorizationURL = null;

// If we've already finalized the oAuth dance, load the Facebook 
// access token from a cookie, so we can use their API.
$accessToken = $_COOKIE["FacebookAccessToken"];

// If we have a stored access, no need to do anything... 
if(is_null($accessToken)) {
	// Either initiate, or conclude, the oAuth dance
	if(!is_null($finalize) && !is_null($callbackID)) {
		finalizeOAuth($session, $callbackID);
	} else {
		initializeOAuth($session);
	}
}

// Initiate the Facebook oAuth process, by running the "InitializeOauth" choreo.
// This will return an authentication URL, to which we will send the user to click "allow,"
// granting access to their account on behalf of this application, and a callback ID,
// which we will use in the final step of the oAuth process to retrieve an access token.
function initializeOAuth($session) {
	// Instantiate the Choreo, and get an input object allowing us to specify parameters.
	$initializeOAuth = new Facebook_OAuth_InitializeOAuth($session);
	$initializeOAuthInputs = $initializeOAuth->newInputs();


	// Set inputs for the Choreo:
	// Specify your Temboo credentials, that will be used to securely register for and retrieve callback data from Facebook
	$initializeOAuthInputs->setAccountName(TEMBOO_ACCOUNT_NAME)->setAppKeyName(TEMBOO_APP_KEY_NAME)->setAppKeyValue(TEMBOO_APP_KEY_VALUE);

	// Specify your Facebook App ID; you can get this from https://developers.facebook.com/apps
	// 
	// IMPORTANT!!!
	// Make sure to configure your Facebook App to accept the Temboo callback URL; see
	// https://live.temboo.com/library/Library/Facebook/OAuth/ for instructions
	$initializeOAuthInputs->setAppID(FACEBOOK_APP_ID);

	// Specify the URL that the Temboo service will forward the user to after they authorize
	// access on Facebook: in this case, we send them back to this page, with a querystring flag set.
	$initializeOAuthInputs->setForwardingURL(PAGE_LOCATION . "?finalize=true");

	// Execute choreography and get results
	$initializeOAuthResults = $initializeOAuth->execute($initializeOAuthInputs)->getResults();

	// Get oAuth URL and callback ID from the initialzeOauth choreo
	$oauthURL = $initializeOAuthResults->getAuthorizationURL();
	$callbackID = $initializeOAuthResults->getCallbackID();

	// Store the Temboo callback ID in a cookie (set to expire in 5 minutes)
	// We'll need this ID in finalizeOauth.php, to retrieve callback data from Temboo
	setcookie("TembooCallbackID", $callbackID, time()+300);

		// Set the global authorization URL, to which we'll redirect the user
	global $authorizationURL;
	$authorizationURL = $oauthURL;
}

// Finalize the Facebook oAuth process, by retrieving callback data and exchanging it
// for an oAuth Token and Token Secret
function finalizeOAuth($session, $callbackID) {
	// Instantiate the Choreo, and get an input object allowing us to specify parameters.
	$finalizeOAuthChoreo = new Facebook_OAuth_FinalizeOAuth($session);
	$finalizeOAuthInputs = $finalizeOAuthChoreo->newInputs();

	// Set inputs for the Choreo:
	// Specify your Temboo credentials, that will be used to securely register for and retrieve callback data from Facebook
	$finalizeOAuthInputs->setAccountName(TEMBOO_ACCOUNT_NAME)->setAppKeyName(TEMBOO_APP_KEY_NAME)->setAppKeyValue(TEMBOO_APP_KEY_VALUE);
	
	// Specify your Facebook App ID and App Secret; you can get this from https://developers.facebook.com/apps
	$finalizeOAuthInputs->setAppID(FACEBOOK_APP_ID)->setAppSecret(FACEBOOK_APP_SECRET);

	// Specify the callback ID (which we obtained in the initializeOAuth process)
	$finalizeOAuthInputs->setCallbackID($callbackID);

	// Execute choreography and get results; now we have a Facebook access token!
	$finalizeOAuthResults = $finalizeOAuthChoreo->execute($finalizeOAuthInputs)->getResults();

	// set the global access token
	global $accessToken;
	$accessToken = $finalizeOAuthResults->getAccessToken();

	// Store the access token in a cookie
	setcookie("FacebookAccessToken", $accessToken, time()+600);
}


// Get the set of a user's Facebook likes
function getLikes($session) {
	global $accessToken;

	// By now, this pattern should be pretty familiar...
	$likesChoreo = new Facebook_Reading_Likes($session);
	$likesInputs = $likesChoreo->newInputs();
	$likesInputs->setAccessToken($accessToken);
	$likesResults = $likesChoreo->execute($likesInputs)->getResults();

	// get the set of Likes objects
	return $likesResults->getLikes();
}
?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,700"/>
		<style>
			body{
					color:#333;
					font-family:"Open Sans","Trebuchet MS",Arial,sans-serif;
					font-size:12px
					;margin:10px auto;
					min-width:800px;
					max-width:1000px;
					padding:0 10px;
				}
		</style>
	</head>
	<body>
		<?php if(!is_null($authorizationURL)): ?>
			This is a simple demonstration of doing oAuth authentication with the Facebook API, using the Temboo SDK.
			To authenticate with Facebook, <a href="<?php echo $authorizationURL ?>">click here</a>.
		<?php endif; ?>


		<?php if(!is_null($accessToken)): ?>
			Successfully authenticated via oAuth with Facebook! Your access token value is: <?php echo $accessToken ?>
			<br /><br />
			Your recent Facebook likes are:
			<ul>

			<?php

				$likes = getLikes($session);
				foreach($likes as $like) {
					echo "<li>" . $like->getName() . " (" . $like->getCategory() . ")</li>";
				}

			?>	
			</ul>
		<?php endif; ?>
	</body>
</html>
