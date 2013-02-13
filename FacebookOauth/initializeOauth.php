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


// Instantiate the Facebook "InitializeOAuth" Choreo, which will perform the first part of the
// oAuth process, and get an InputSet object that lets us provide parameters to the Choreo.
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
$initializeOAuthInputs->setAppID("PUT YOUR FACEBOOK APP ID HERE!");

// Specify the URL that the Temboo service will forward the user to after they authorize
// access on Facebook: in this case, the finalizeOauth.php page.

// EDIT THIS URL TO POINT TO WHEREVER finalizeOauth.php LIVES
$initializeOAuthInputs->setForwardingURL("http://localhost:8888/finalizeOauth.php");

// Execute choreography and get results
$initializeOAuthResults = $initializeOAuth->execute($initializeOAuthInputs)->getResults();

// Get oAuth URL and callback ID from the initialzeOauth choreo
$oauthURL = $initializeOAuthResults->getAuthorizationURL();
$callbackID = $initializeOAuthResults->getCallbackID();

// Store the Temboo callback ID in a cookie (set to expire in 5 minutes)
// We'll need this ID in finalizeOauth.php, to retrieve callback data from Temboo
setcookie("TembooCallbackID", $callbackID, time()+300);

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
		This page is a simple demo of how to authenticate with Facebook via oAuth, using the Temboo SDK.
		<br />
		<a href="<?php echo $oauthURL ?>">Click here to Log in with Facebook</a>
	</body>
</html>
