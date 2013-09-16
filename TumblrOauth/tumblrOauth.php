<?php
/**
 * Temboo PHP SDK example usage: Tumblr  oAuth
 *
 * Demonstrates how to use the Temboo PHP SDK to perform oAuth
 * authentication with the Tumblr API.
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

/************** MODIFY THESE CONSTANTS TO MATCH YOUR TEMBOO AND TUMBLR CREDENTIALS **************/

/**
 * Your Temboo account name (note that this is NOT your username).
 */
define('TEMBOO_ACCOUNT_NAME', 'XXXXXXXXXX');

/**
 * Your Temboo App key name. 
 * After signing up for a Temboo account, you can get this from https://www.temboo.com/account/
 */
define('TEMBOO_APP_KEY_NAME', 'XXXXXXXXXX');

/**
 * Your Temboo App key value. 
 * After signing up for a Temboo account, you can get this from https://www.temboo.com/account/
 */
define('TEMBOO_APP_KEY_VALUE', 'XXXXXXXXXX');

/**
 * Your Tumblr consumer key. 
 * After signing up for a Tumblr account, you can get this by creating an app at 
 * http://www.tumblr.com/oauth/apps
 */
define('TUMBLR_CONSUMER_KEY', 'XXXXXXXXXX');

/**
 * Your Tumblr consumer secret. 
 * After signing up for a Tumblr account, you can get this by creating an app at 
 * http://www.tumblr.com/oauth/apps
 */
define('TUMBLR_CONSUMER_SECRET', 'XXXXXXXXXX');

/**
 * The location at which this page is accessible; used to redirect users back to this
 * page after they click "allow" in Tumblr
 */
define('PAGE_LOCATION', 'http://localhost:8888/oauth/tumblrOauth.php');

/************** END CONSTANTS; NOTHING BELOW THIS POINT SHOULD NEED TO BE CHANGED **************/


 // Include the Temboo PHP SDK; this can be downloaded from http://www.temboo.com/download
require 'php-sdk/src/temboo.php';
require 'php-sdk/src/library/temboo.tumblr.php';

// Instantiate a Temboo Session object; you'll need a (free) Temboo account and credentials.
$session = new Temboo_Session(TEMBOO_ACCOUNT_NAME, TEMBOO_APP_KEY_NAME, TEMBOO_APP_KEY_VALUE);


// Check whether we recieved a "finalize=true" querystring parameter. If we did get a 
// "finalize=true" parameter, and we can access a Temboo Callback ID and token secret that was previously
// stored in a cookie, we should finalize the oAuth dance (the user has already clicked "allow");
// otherwise, we should start the oAuth dance and prompt the user to click "allow"
$finalize = $_GET["finalize"];
$callbackID = $_COOKIE["TembooCallbackID"];
$OAuthTokenSecret = $_COOKIE["OAuthTokenSecret"];

// If we're doing the first part of the oAuth dance, we'll need to
// send the user to an authorization URL to allow access to their account
$authorizationURL = null;

// If we've already finalized the oAuth dance, load the Tumblr 
// oAuth token and secret from cookies, so we can use their API.
$AccessToken = $_COOKIE["TumblrAccessToken"];
$AccessTokenSecret = $_COOKIE["TumblrAccessTokenSecret"];


// If we have a stored oAuth token and secret, no need to do anything... 
if(is_null($AccessToken) || is_null($AccessTokenSecret)) {
	// Either initiate, or conclude, the oAuth dance
	if(!is_null($finalize) && !is_null($callbackID)) {
		finalizeOAuth($session, $callbackID, $OAuthTokenSecret);
	} else {
		initializeOAuth($session);
	}
}


// Initiate the Tumblr oAuth process, by running the "InitializeOauth" choreo.
// This will return an authentication URL, to which we will send the user to click "allow,"
// granting access to their account on behalf of this application, and a callback ID,
// which we will use in the final step of the oAuth process to retrieve an access token.
function initializeOAuth($session) {
	// Instantiate the Choreo, using a previously instantiated Temboo_Session object, eg:
	$initializeOAuth = new Tumblr_OAuth_InitializeOAuth($session);

	// Get an input object for the Choreo
	$initializeOAuthInputs = $initializeOAuth->newInputs();

	// Set inputs
	$initializeOAuthInputs->setAPIKey(TUMBLR_CONSUMER_KEY);
	$initializeOAuthInputs->setSecretKey(TUMBLR_CONSUMER_SECRET);

	// Specify the forwarding URL, to which users should be sent after clicking "allow"
	// In this case, we simply direct the user back to this page.
	$initializeOAuthInputs->setForwardingURL(PAGE_LOCATION . "?finalize=true");

	// Execute Choreo and get results
	$initializeOAuthResults = $initializeOAuth->execute($initializeOAuthInputs)->getResults();

	// Store the retrieved callback ID and token secret in a cookie
	setcookie("TembooCallbackID", $initializeOAuthResults->getCallbackID(), time()+300);
	setcookie("OAuthTokenSecret", $initializeOAuthResults->getOAuthTokenSecret(), time()+300);

	// Set the global authorization URL, to which we'll redirect the user
	global $authorizationURL;
	$authorizationURL = $initializeOAuthResults->getAuthorizationURL();
}

// Finalize the Tumblr oAuth process, by retrieving callback data and exchanging it
// for an oAuth Token and Token Secret
function finalizeOAuth($session, $callbackID, $OAuthTokenSecret) {
	// Instantiate the Choreo, using a previously instantiated Temboo_Session object, eg:
	$finalizeOAuth = new Tumblr_OAuth_FinalizeOAuth($session);

	// Get an input object for the Choreo
	$finalizeOAuthInputs = $finalizeOAuth->newInputs();

	// Set inputs
	$finalizeOAuthInputs->setCallbackID($callbackID);
	$finalizeOAuthInputs->setAPIKey(TUMBLR_CONSUMER_KEY);
	$finalizeOAuthInputs->setSecretKey(TUMBLR_CONSUMER_SECRET);
	$finalizeOAuthInputs->setOAuthTokenSecret($OAuthTokenSecret);

	// Execute Choreo and get results
	$finalizeOAuthResults = $finalizeOAuth->execute($finalizeOAuthInputs)->getResults();

	// set the global oAuth token and token secret
	global $AccessToken, $AccessTokenSecret;
	$AccessToken = $finalizeOAuthResults->getAccessToken();
	$AccessTokenSecret = $finalizeOAuthResults->getAccessTokenSecret();

	// Store the OAuth token and secret in a cookie
	setcookie("TumblrAccessToken", $AccessToken, time()+600);
	setcookie("TumblrAccessTokenSecret", $AccessTokenSecret, time()+600);
}

// Get the user's information from Tumblr
function getUserInfo($session) {

	global $AccessToken, $AccessTokenSecret;

	// Instantiate the Choreo, using a previously instantiated Temboo_Session object, eg:
	$getUserInformation = new Tumblr_User_GetUserInformation($session);

	// Get an input object for the Choreo
	$getUserInformationInputs = $getUserInformation->newInputs();

	// Set inputs
	$getUserInformationInputs->setAPIKey(TUMBLR_CONSUMER_KEY)->setAccessToken($AccessToken)->setAccessTokenSecret($AccessTokenSecret)->setSecretKey(TUMBLR_CONSUMER_SECRET);

	// Execute Choreo and get results
	$getUserInformationResults = $getUserInformation->execute($getUserInformationInputs)->getResults();

	return $getUserInformationResults;
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
			This is a simple demonstration of doing oAuth authentication with the Tumblr API, using the Temboo SDK.
			To authenticate with Tumblr, <a href="<?php echo $authorizationURL ?>">click here</a>.
		<?php endif; ?>


		<?php if(!is_null($AccessToken)): ?>
			Successfully authenticated via oAuth with Tumblr! Your oAuth access token value is: <?php echo $AccessToken ?>
			<br />
			Now, let's load your Tumblr user information:
			<hr noshade size=1>
			Raw response returned by Tumblr:<br>
			<?php

				// Get current user info for Tumblr
				$currentUserResults = getUserInfo($session);

				print_r($currentUserResults);
			?>
		<?php endif; ?>		
	</body>
</html>