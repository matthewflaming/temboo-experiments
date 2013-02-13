<?php
/**
 * Temboo PHP SDK example usage: Khan Academy oAuth
 *
 * Demonstrates how to use the Temboo PHP SDK to perform oAuth
 * authentication with the Khan Academy API.
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

/************** MODIFY THESE CONSTANTS TO MATCH YOUR TEMBOO AND KHAN ACADEMY CREDENTIALS **************/

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
 * Your Temboo App key value. 
 * After signing up for a Temboo account, you can get this from https://live.temboo.com/account/
 */
define('TEMBOO_APP_KEY_VALUE', '**** UPDATE THIS VALUE ***');

/**
 * Your Khan Academy consumer key. 
 * After signing up for a Khan Academy account, you can get this by creating an app at 
 * http://www.khanacademy.org/api-apps/register
 */
define('KHAN_ACADEMY_CONSUMER_KEY', '**** UPDATE THIS VALUE ***');

/**
 * Your Khan Academy consumer secret. 
 * After signing up for a Khan Academy account, you can get this by creating an app at 
 * http://www.khanacademy.org/api-apps/register
 */
define('KHAN_ACADEMY_CONSUMER_SECRET', '**** UPDATE THIS VALUE ***');

/**
 * The location at which this page is accessible; used to redirect users back to this
 * page after they click "allow" in Khan Academy
 */
define('PAGE_LOCATION', 'http://localhost:8888/oauth/khanAcademy.php'); // **** UPDATE THIS VALUE IF THE PAGE LIVES AT A DIFFERENT URL***

/************** END CONSTANTS; NOTHING BELOW THIS POINT SHOULD NEED TO BE CHANGED **************/


 // Include the Temboo PHP SDK; this can be downloaded from http://www.temboo.com/download
require 'php-sdk/src/temboo.php';
require 'php-sdk/src/library/temboo.khanacademy.php';

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

// If we've already finalized the oAuth dance, load the Khan Academy 
// oAuth token and secret from cookies, so we can use their API.
$OAuthToken = $_COOKIE["KhanAcademyOauthToken"];
$OAuthTokenSecret = $_COOKIE["KhanAcademyOauthTokenSecret"];


// If we have a stored oAuth token and secret, no need to do anything... 
if(is_null($OAuthToken) || is_null(OAuthTokenSecret)) {
	// Either initiate, or conclude, the oAuth dance
	if(!is_null($finalize) && !is_null($callbackID)) {
		finalizeOAuth($session, $callbackID);
	} else {
		initializeOAuth($session);
	}
}


// Initiate the Khan Academy oAuth process, by running the "InitializeOauth" choreo.
// This will return an authentication URL, to which we will send the user to click "allow,"
// granting access to their account on behalf of this application, and a callback ID,
// which we will use in the final step of the oAuth process to retrieve an access token.
function initializeOAuth($session) {
	// Instantiate the Choreo, and get an input object allowing us to specify parameters.
	$initializeOAuthChoreo = new KhanAcademy_OAuth_InitializeOAuth($session);
	$initializeOAuthInputs = $initializeOAuthChoreo->newInputs();

	// Set inputs for the Choreo:
	// Specify your Temboo credentials, that will be used to securely register for and retrieve callback data from Khan Academy
	$initializeOAuthInputs->setAccountName(TEMBOO_ACCOUNT_NAME)->setAppKeyName(TEMBOO_APP_KEY_NAME)->setAppKeyValue(TEMBOO_APP_KEY_VALUE);
	// Specify your Khan Academy consumer key and consumer secret
	$initializeOAuthInputs->setConsumerKey(KHAN_ACADEMY_CONSUMER_KEY)->setConsumerSecret(KHAN_ACADEMY_CONSUMER_SECRET);
	// Specify the forwarding URL, to which users should be sent after clicking "allow"
	// In this case, we simply direct the user back to this page.
	$initializeOAuthInputs->setForwardingURL(PAGE_LOCATION . "?finalize=true");
	// Execute Choreo and get results
	$initializeOAuthResults = $initializeOAuthChoreo->execute($initializeOAuthInputs)->getResults();

	// Store the retrieved callback ID in a cookie
	setcookie("TembooCallbackID", $initializeOAuthResults->getCallbackID(), time()+300);

	// Set the global authorization URL, to which we'll redirect the user
	global $authorizationURL;
	$authorizationURL = $initializeOAuthResults->getAuthorizationURL();
}

// Finalize the Khan Academy oAuth process, by retrieving callback data and exchanging it
// for an oAuth Token and Token Secret
function finalizeOAuth($session, $callbackID) {
	// Instantiate the Choreo, and get an input object allowing us to specify parameters.
	$finalizeOAuthChoreo = new KhanAcademy_OAuth_FinalizeOAuth($session);
	$finalizeOAuthInputs = $finalizeOAuthChoreo->newInputs();

	// Set inputs for the Choreo:
	// Specify your Temboo credentials, that will be used to securely register for and retrieve callback data from Khan Academy
	$finalizeOAuthInputs->setAccountName(TEMBOO_ACCOUNT_NAME)->setAppKeyName(TEMBOO_APP_KEY_NAME)->setAppKeyValue(TEMBOO_APP_KEY_VALUE);
	// Specify your Khan Academy consumer key and consumer secret
	$finalizeOAuthInputs->setConsumerKey(KHAN_ACADEMY_CONSUMER_KEY)->setConsumerSecret(KHAN_ACADEMY_CONSUMER_SECRET);
	// Specify the callback ID to retrieve
	$finalizeOAuthInputs->setCallbackID($callbackID);

	// Execute Choreo and get results
	$finalizeOAuthResults = $finalizeOAuthChoreo->execute($finalizeOAuthInputs)->getResults();

	// set the global oAuth token and token secret
	global $OAuthToken, $OAuthTokenSecret;
	$OAuthToken = $finalizeOAuthResults->getOAuthToken();
	$OAuthTokenSecret = $finalizeOAuthResults->getOAuthTokenSecret();

	// Store the OAuth token and secret in a cookie
	setcookie("KhanAcademyOauthToken", $OAuthToken, time()+600);
	setcookie("KhanAcademyOauthTokenSecret", $OAuthTokenSecret, time()+600);
}

// Get the user's information from Khan Academy
function getUserInfo($session) {
	global $OAuthToken, $OAuthTokenSecret;

	// By now, this pattern should be pretty familiar...
	$currentUserChoreo = new KhanAcademy_Users_CurrentUser($session);
	$currentUserInputs = $currentUserChoreo->newInputs();
	$currentUserInputs->setOAuthToken($OAuthToken)->setOAuthTokenSecret($OAuthTokenSecret);
	$currentUserInputs->setConsumerSecret(KHAN_ACADEMY_CONSUMER_SECRET)->setConsumerKey(KHAN_ACADEMY_CONSUMER_KEY);
	$currentUserResults = $currentUserChoreo->execute($currentUserInputs)->getResults();

	return $currentUserResults;
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
			This is a simple demonstration of doing oAuth authentication with the Khan Academy API, using the Temboo SDK.
			To authenticate with Khan Academy, <a href="<?php echo $authorizationURL ?>">click here</a>.
		<?php endif; ?>


		<?php if(!is_null($OAuthToken)): ?>
			Successfully authenticated via oAuth with Khan Academy! Your oAuth token value is: <?php echo $OAuthToken ?>
			<br />
			Now, let's load your Khan Academy user information:
			<?php

				// Get current user info for Khan Academy
				$currentUserResults = getUserInfo($session);

				// Decode the JSON response
				$userData = json_decode($currentUserResults->getResponse(), true);

				// Print out some interesting factoids
				echo "User email: " . $userData["email"] . "<br />";
				echo "Date joined: " . $userData["joined"] . "<br />";
				echo "Nickname: " . $userData["nickname"] . "<br />";
				echo "Suggested exercises: ";
				echo "<ul>";
				foreach($userData["suggested_exercises"] as $suggested) {
					echo "<li>" . $suggested . "</li>";
				}	 
				echo "</ul>";
			?>
		<?php endif; ?>		
	</body>
</html>