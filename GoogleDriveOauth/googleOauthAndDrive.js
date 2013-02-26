/**
 * Temboo Node.js SDK example usage: Google Drive + oAuth
 *
 * Demonstrates how to use the Temboo Node.js SDK to perform oAuth
 * authentication with Google, and use API functions.
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


/************** MODIFY THESE CONSTANTS TO MATCH YOUR TEMBOO AND GOOGLE CREDENTIALS **************/

// Require a couple core libraries
var http = require('http');
var url = require('url');

// We need a Temboo session to do anything with the SDK; also import their OAuth package
var tsession = require("temboo/core/temboosession");
var Google = require("temboo/Library/Google/OAuth");

// Temboo settings
var TEMBOO_ACCOUNT_NAME = '***FILL IN***';					// Your Temboo account name (note that this is NOT your username).
var TEMBOO_APP_KEY_NAME = '***FILL IN***';		// Get this from https://live.temboo.com/account/
var TEMBOO_APP_KEY_VALUE = '***FILL IN***';						// Get this from https://live.temboo.com/account/

// Google app settings: get these from registering an app at https://code.google.com/apis/console/
// Make sure the callback URL for the google app is set to https://{Your Temboo Acount Name}.temboolive.com/callback/google
var GOOGLE_CLIENT_ID = '***FILL IN***';
var GOOGLE_CLIENT_SECRET = '***FILL IN***';
var GOOGLE_OAUTH_SCOPE = 'https://www.googleapis.com/auth/drive';

// The hostname + port at which this app lives (no trailing slash, please!)
var PAGE_LOCATION = 'http://localhost:8080';

/************** END CONSTANTS; NOTHING BELOW THIS POINT SHOULD NEED TO BE CHANGED **************/


/* 
 * Create Temboo session object
 */
var session = new tsession.TembooSession(TEMBOO_ACCOUNT_NAME, TEMBOO_APP_KEY_NAME, TEMBOO_APP_KEY_VALUE);


/* 
 * Main entry point; dispatch incoming page requests to appropriate handler functions
 */
var server = http.createServer(function(request, response) {
	var uriParse = url.parse(request.url,true);
	var pathname = uriParse.pathname;
	console.log("Got a request for: " + request.url);

	if(pathname == "/init") {
		// Start the oAuth dance
		initOAuth(response);		
	} else if(pathname == "/finalize") {
		// Finalize the oAuth dance
		finishOAuth(request, response);
	} else if(pathname == "/list") {
		// Show off what we can do after finishing up OAuth
		listFiles(request, response);
	} else if(pathname == "/revisions") {
		// Show off what we can do after finishing up OAuth
		listRevisions(request, response);
	} else {
		// Default/unsupported page
		response.writeHead(500);
    	response.write("<html><body>That path doesn't exist. <a href='/init'>Click here</a> to connect to Google Drive.");
    	response.write("</body></html>");
    	response.end();
	}
});
server.listen(8080);


/* 
 * Initiate the Google OAuth process: the Temboo SDK will return an authentication URL
 * and a callback ID, that we'll need to finalize the OAuth handshake after the user
 * has clicked "allow" to grant us access.
 */
function initOAuth(response) {

	var initializeOAuthChoreo = new Google.InitializeOAuth(session);

	// Instantiate and populate the input set for the choreo
	var initializeOAuthInputs = initializeOAuthChoreo.newInputSet();

	// Set inputs
	initializeOAuthInputs.set_AccountName(TEMBOO_ACCOUNT_NAME);
	initializeOAuthInputs.set_AppKeyName(TEMBOO_APP_KEY_NAME);
	initializeOAuthInputs.set_AppKeyValue(TEMBOO_APP_KEY_VALUE);

	initializeOAuthInputs.set_ForwardingURL(PAGE_LOCATION + "/finalize");
	initializeOAuthInputs.set_ClientID(GOOGLE_CLIENT_ID);
	initializeOAuthInputs.set_Scope(GOOGLE_OAUTH_SCOPE);

	// Run the choreo, specifying success and error callback handlers
	initializeOAuthChoreo.execute(
	    initializeOAuthInputs,
	    
	    function(results) {
	    	setCookie(response, "TMBCallbackID", results.get_CallbackID(), 5);
	    	response.writeHead(200);
	    	response.write("<html><body>");
			response.write("This is a simple example of authenticating with Google Drive via OAuth using the temboo SDK.<br>");
			response.write("Initializing OAuth now...<br>");
	    	response.write("<a href='" + results.get_AuthorizationURL() + "'>Click here</a> to continue the OAuth process.");
	    	response.write("</body></html>");

	    	response.end();

	    	console.log("Registered for an OAuth callback successfully. Callback ID: " + results.get_CallbackID());
	    },
	    
	    function(error){
	    	response.writeHead(500);
	    	response.write("<html><body>Uh-oh, something went wrong. The error was:<br>");
	    	response.write(error.message + "<br>");
	    	response.write("<a href='/init'>Try again?</a>");
	    	response.write("</body></html>");
	    	response.end();

	    	console.log(error.type); console.log(error.message);
	    }
	);
}

/* 
 * Finish the OAuth dance, by calling the Temboo SDK with the callback ID we provided
 * in the initOAuth step.
 */
function finishOAuth(request, response) {

	// See if there's a stored callback ID cookie. If we can't retrieve it,
	// go to the "init oauth" step
	var callbackID = getCookie(request, "TMBCallbackID");

	// Clear the stored callback ID (we never want to use the same callback ID twice)
	setCookie(response, "TMBCallbackID", "", 1);

	if(callbackID == null || callbackID.length < 1)
		initOAuth(response);

	console.log("About to retrieve OAuth callback data. Callback ID: " + callbackID);

	var finalizeOAuthChoreo = new Google.FinalizeOAuth(session);

	// Instantiate and populate the input set for the choreo
	var finalizeOAuthInputs = finalizeOAuthChoreo.newInputSet();

	// Set inputs
	finalizeOAuthInputs.set_CallbackID(callbackID);
	finalizeOAuthInputs.set_AccountName(TEMBOO_ACCOUNT_NAME);
	finalizeOAuthInputs.set_AppKeyName(TEMBOO_APP_KEY_NAME);
	finalizeOAuthInputs.set_AppKeyValue(TEMBOO_APP_KEY_VALUE);
	finalizeOAuthInputs.set_ClientID(GOOGLE_CLIENT_ID);
	finalizeOAuthInputs.set_ClientSecret(GOOGLE_CLIENT_SECRET);

	// Run the choreo, specifying success and error callback handlers
	finalizeOAuthChoreo.execute(
	    finalizeOAuthInputs,
	    function(results){
	    	// store the retrieved access token in a cookie
	    	setCookie(response, "GoogleAccessToken", results.get_AccessToken(), 10);

			response.writeHead(200);
	    	response.write("<html><body>");
			response.write("Success! <br>");
			response.write("Your Google Access Token is: " + results.get_AccessToken() + "<br>");
			response.write("<a href='/list'>Click here</a> to view your Google Drive files.");
	    	response.write("</body></html>");

	    	response.end();
	    },
	    
	    function(error){
	    	response.writeHead(500);
	    	response.write("<html><body>Uh-oh, something went wrong. The error was:<br>");
	    	response.write(error.message + "<br>");
	    	response.write("<a href='/init'>Try again?</a>");
	    	response.write("</body></html>");
	    	response.end();
	    	console.log(error.type); console.log(error.message);
	    }
	);
}

function listFiles(request, response) {

	// See if there's a stored Google access token. If we can't retrieve it,
	// go to the "init oauth" step
	var accessToken = getCookie(request, "GoogleAccessToken");
	if(accessToken == null || accessToken.length < 1)
		initOAuth(response);

	var GoogleDriveFiles = require("temboo/Library/Google/Drive/Files");

	var listChoreo = new GoogleDriveFiles.List(session);

	// Instantiate and populate the input set for the choreo
	var listInputs = listChoreo.newInputSet();

	// Set inputs
	listInputs.set_ClientID(GOOGLE_CLIENT_ID);
	listInputs.set_ClientSecret(GOOGLE_CLIENT_SECRET);
	listInputs.set_AccessToken(accessToken);

	// Run the choreo, specifying success and error callback handlers
	listChoreo.execute(
    listInputs,
    function(results){

		response.writeHead(200);
    	response.write("<html><body>");
		response.write("Your Google Drive contains: <br>");
		response.write("<table border=0 cellpadding=10><tr><th>NAME</th><th>MIME TYPE</th><th>REVISION HISTORY</th></tr>");

    	var fileList = results.getFileList();
    	var files = fileList.getFiles();
    	for(var i=0; i < files.length; i++) {
    		file = files[i];
    		response.write("<tr><td><a href='" + file.getAlternateLink() + "'>" + file.getTitle() + "</a></td>");
    		response.write("<td>" + file.getMimeType() + "</td><td>");

    		// if this is a document, show a list to view file revisions
    		if(file.getMimeType().indexOf('document') > -1)
	    		response.write("[<a href='/revisions?id=" + file.getId() + "'>view</a>]");

	    	response.write("</td></tr>")
    	}

    	response.write("</table></body></html>");
    },
    function(error){
    	response.writeHead(500);
    	response.write("<html><body>Uh-oh, something went wrong. The error was:<br>");
    	response.write(error.message + "<br>");
    	response.write("<a href='/init'>Try again?</a>");
    	response.write("</body></html>");
    	response.end();
    }
);
}


function listRevisions(request, response) {
	// See if there's a stored Google access token. If we can't retrieve it,
	// go to the "init oauth" step
	var accessToken = getCookie(request, "GoogleAccessToken");
	if(accessToken == null || accessToken.length < 1)
		initOAuth(response);

	console.log("Access token: " + accessToken);

	// Get the ID of the file we want revisions for out of the querystring
	var queryData = url.parse(request.url, true).query;

	console.log("File ID: " + queryData.id);

	var GoogleDriveRevisions = require("temboo/Library/Google/Drive/Revisions");

	var listChoreo = new GoogleDriveRevisions.List(session);
	var listInputs = listChoreo.newInputSet();

	listInputs.set_ClientID(GOOGLE_CLIENT_ID);
	listInputs.set_ClientSecret(GOOGLE_CLIENT_SECRET);
	listInputs.set_AccessToken(accessToken);
	listInputs.set_FileID(queryData.id);

	// Run the choreo, specifying success and error callback handlers
	listChoreo.execute(
	    listInputs,
	    function(results){
	    	response.writeHead(200);
	    	response.write("<html><body>");
			response.write("File revisions: <ul>");

	    	var revisionList = results.getRevisionList();
	    	var revisions = revisionList.getRevisions();
	    	for(var i=0; i < revisions.length; i++) {
	    		var revision = revisions[i];
	    		response.write("<li>Updated on " + revision.getModifiedDate() + " by " + revision.getLastModifyingUserName() + " ");
	    		response.write("<a href='" + revision.getSelfLink() + "'>[view revision]</a></li>");
	    	}
	    	response.write("</ul></body></html>");
	    	response.end();

	    	console.log("Success!" + results.get_NewAccessToken());
	    },
	    function(error){
	    	response.writeHead(500);
	    	response.write("<html><body>Uh-oh, something went wrong. The error was:<br>");
	    	response.write(error.message + "<br>");
	    	response.write("<a href='/init'>Try again?</a>");
	    	response.write("</body></html>");
	    	response.end();
	    	console.log(error.type); console.log(error.message);
	    }
);
}


// Simple utility function to write a cookie. There are better ways of doing this through
// a 3rd-party library, but trying to keep dependencies minimal here.
function setCookie(response, name, value, expirationTimeInMinutes) {
	try {
		response.setHeader('Set-Cookie', name + "=" + value + "; expires=" + new Date(new Date().getTime() + (1000 * 60 * expirationTimeInMinutes)).toUTCString() + ";");
	} catch(e) {
		console.log("Error setting cookie value for " + name);
		console.log(e);
	}
}

// Simple utility function to get the value of the specified cookie
function getCookie(request, name) {
	var cookies = {};
	if(!request.headers.cookie)
		return null;

  	var cookieParts = request.headers.cookie.split(';')
  	for(var i=0; i < cookieParts.length; i++) {
  		var parts = cookieParts[i].split('=');
  		cookies[parts[0].trim()] = parts[1];
  	}

    return cookies[name];
}